<?php
/**
 * Twispay Helpers
 *
 * Creates helper methods for operations over transactions table
 *
 * @author   Twispay
 * @version  1.0.1
 */

/* Security class check */
if (! class_exists('Twispay_Actions')) :
    /**
     * Class that implements operations avalable via Twispay API
     */
    class Twispay_Actions
    {
        /**
         * Function that calls the refund operation via Twispay API and update the local order based on the response.
         *
         * @param array trans_id - Twispay transaction id
         * @param float amount - The partial refund amount
         *
         * @return array([key => value]) - string 'status' - Operation message
         *                                 string 'rawdata' - Unprocessed response
         *                                 string 'transactionId' - The twispay id of the refunded transaction
         *                                 string 'externalOrderId' - The twispay id of the refunded order
         *                                 boolean 'refunded' - Operation success indicator
         *
         */
        public static function refundTransaction($trans_id, $amount)
        {
            /** Load dependencies */
            require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Logger.php');
            require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Transactions.php');
            require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Status_Updater.php');

            $transaction = Twispay_Transactions::getTransaction($trans_id);
            /** Total transaction amount*/
            $transaction['amount'] = floatval($transaction['amount']);
            /** If the amount is not specified retunr false*/
            if(isset($amount)){
              $postAmount = $amount;
            }else{
              return false;
            }

            /** Get the Private Key. */
            if (defined("MODULE_PAYMENT_TWISPAY_TESTMODE") &&  MODULE_PAYMENT_TWISPAY_TESTMODE == "True") {
                $url = 'https://api-stage.twispay.com/transaction/' . $transaction['transactionId'];
                $secretKey = MODULE_PAYMENT_TWISPAY_STAGE_KEY;
            } else {
                $url = 'https://api.twispay.com/transaction/' . $transaction['transactionId'];
                $secretKey = MODULE_PAYMENT_TWISPAY_LIVE_KEY;
            }
            $postData = 'amount=' . $postAmount . '&' . 'message=' . 'Refund for order ' . $transaction['orderId'];

            /** Create a new cURL session. */
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Authorization: ' . $secretKey]);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            $response = curl_exec($ch);
            curl_close($ch);
            $json = json_decode($response);

            /** Check if curl/decode fails */
            if (!isset($json)) {
                $json = new stdClass();
                $json->message = JSON_DECODE_ERROR_TEXT;
                Twispay_Logger::api_log(JSON_DECODE_ERROR_TEXT);
            }

            if ((NULL !== $json) && (200 == $json->code) && ('Success' == $json->message)) {
                $data = ['message'         => MODULE_PAYMENT_TWISPAY_REFUND_SUCCESS_TEXT
                        ,'rawdata'         => $json
                        ,'transactionId'   => $trans_id
                        ,'externalOrderId' => $transaction['order_id']
                        ,'refunded'        => 1
                        ];

                /** Update current transaction refunded amount and status*/
                if (Twispay_Transactions::addTransactionRefundedAmount($trans_id, $postAmount) < $transaction['amount']) {
                    $data['status'] = Twispay_Status_Updater::$RESULT_STATUSES['PARTIAL_REFUNDED'];
                    Twispay_Transactions::updateTransactionStatus($trans_id, Twispay_Status_Updater::$RESULT_STATUSES['PARTIAL_REFUNDED']);
                } else {
                    $data['status'] = Twispay_Status_Updater::$RESULT_STATUSES['TOTAL_REFUNDED'];
                    Twispay_Transactions::updateTransactionStatus($trans_id, Twispay_Status_Updater::$RESULT_STATUSES['TOTAL_REFUNDED']);
                }

                /** Update the order status status*/
                Twispay_Status_Updater::updateStatus_IPN($data);
            } else {
                $data = ['message'         => isset($json->error)?$json->error[0]->message:$json->message
                        ,'rawdata'         => $json
                        ,'transactionId'   => $trans_id
                        ,'externalOrderId' => $transaction['order_id']
                        ,'refunded'        => 0
                        ];
                $data['status'].=MODULE_PAYMENT_TWISPAY_CHECK_NOTICE_ADMIN_TEXT;
            }
            Twispay_Logger::api_log(LOG_REFUND_RESPONSE_TEXT.json_encode($data));
            return $data;
        }

        /**
         * Function that calls the cancel operation via Twispay API and update the local order based on the response.
         *
         * @param string twOrderId - The twispay order id of the transaction to be canceled
         * @param string orderId - The local id of the order that needs to be canceled
         * @param string type - The operation type - 'Manual'|'Automatic'
         * @param string initByAdmin - If the operation is called from one of the admin panel pages
         *
         * @return array([key => value]) - string 'status'          - Order status
         *                                 string 'message'         - API Message
         *                                 string 'rawdata'         - Unprocessed response
         *                                 string 'orderId'         - The twispay id of the canceled order
         *                                 string 'externalOrderId' - The opencart id of the canceled order
         *                                 boolean 'canceled'       - Operation success indicator
         */
        public static function cancelSubscription($twOrderId, $orderId, $type = 'Manual', $initByAdmin = false)
        {
            /** Load dependencies */
            require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Logger.php');
            require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Status_Updater.php');

            $postData = 'reason='.'customer-demand'.'&'.'message=' . $type .'cancel';
            /** Get the Private Key. */
            if (defined("MODULE_PAYMENT_TWISPAY_TESTMODE") &&  MODULE_PAYMENT_TWISPAY_TESTMODE == "True") {
                $url = 'https://api-stage.twispay.com/order/' . $twOrderId;
                $secretKey = MODULE_PAYMENT_TWISPAY_STAGE_KEY;
            } else {
                $url = 'https://api.twispay.com/order/' . $twOrderId;
                $secretKey = MODULE_PAYMENT_TWISPAY_LIVE_KEY;
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Authorization: ' . $secretKey]);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            $response = curl_exec($ch);
            curl_close($ch);
            $json = json_decode($response);

            /** Check if curl/decode fails */
            if (!isset($json)) {
                $json = new stdClass();
                $json->message = JSON_DECODE_ERROR_TEXT;
                Twispay_Logger::api_log(JSON_DECODE_ERROR_TEXT);
            }

            /** If success */
            if ((NULL !== $json) && (200 == $json->code) && ('Success' == $json->message)) {
                $data = ['status'          => Twispay_Status_Updater::$RESULT_STATUSES['CANCEL_OK']
                        ,'message'         => MODULE_PAYMENT_TWISPAY_CANCEL_SUCCESS_TEXT
                        ,'rawdata'         => $json
                        ,'orderId'         => $twOrderId
                        ,'externalOrderId' => $orderId
                        ,'canceled'        => 1
                        ];
                Twispay_Status_Updater::updateStatus_IPN($data, 0);
            } else {
                $data = ['status'          => 0
                        ,'message'         => isset($json->error)?$json->error[0]->message:$json->message
                        ,'rawdata'         => $json
                        ,'orderId'         => $twOrderId
                        ,'externalOrderId' => $orderId
                        ,'canceled'        => 0
                        ];
                /** If operation is called from one of the admin page add a different notice from the one is called from a catalog page */
                if ($initByAdmin) {
                    $data['status'].= MODULE_PAYMENT_TWISPAY_CHECK_NOTICE_ADMIN_TEXT;
                } else {
                    $data['status'].= MODULE_PAYMENT_TWISPAY_CHECK_NOTICE_CUSTOMER_TEXT;
                }
            }
            Twispay_Logger::api_log(LOG_CANCEL_RESPONSE_TEXT.json_encode($data));
            return $data;
        }

        /**
         * Function that calls the GET operation via Twispay API and update all of the local recurring orders based on the response.
         *
         * @return array([key => value]) - string 'status' - API Message
         *                                 int 'synced' - Number of afected orders
         */
        public static function syncSubscriptions()
        {
            /** Load dependencies */
            require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Logger.php');
            require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Subscriptions.php');
            require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Status_Updater.php');
            require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Transactions.php');

            /** Get the Private Key. */
            if (defined("MODULE_PAYMENT_TWISPAY_TESTMODE") &&  MODULE_PAYMENT_TWISPAY_TESTMODE == "True") {
                $baseUrl = 'https://api-stage.twispay.com/order/__ORDER_ID__';
                $secretKey = MODULE_PAYMENT_TWISPAY_STAGE_KEY;
            } else {
                $baseUrl = 'https://api.twispay.com/order/__ORDER_ID__';
                $secretKey = MODULE_PAYMENT_TWISPAY_LIVE_KEY;
            }

            $total_synced = 0;
            $error = array('message' => '','error' => 0);
            $subscriptions = Twispay_Subscriptions::getAllSubscriptions();

            if(!$subscriptions){
              return array(
                  'status' => SUBSCRIPTIONS_LOG_ERROR_NO_ORDERS_FOUND_TEXT,
                  'synced' => 0,
              );
            }
            foreach ($subscriptions as $key => $subscription) {

                /** Construct the URL. */
                $orderPlatformId = Oscommerce_Order::getOrderPlatformId($subscription['orders_id']);

                $url = str_replace('__ORDER_ID__', $orderPlatformId, $baseUrl);

                /** Create a new cURL session. */
                $ch = curl_init();

                /** Set the URL and other needed fields. */
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Authorization: ' . $secretKey]);
                $response = curl_exec($ch);

                /** Check if the CURL call failed. */
                if (false === $response) {
                    Twispay_Logger::api_log(SUBSCRIPTIONS_LOG_ERROR_CALL_FAILED_TEXT . curl_error($ch));
                    curl_close($ch);
                    continue;
                }

                if ((200 != curl_getinfo($ch, CURLINFO_HTTP_CODE))) {
                    Twispay_Logger::api_log(SUBSCRIPTIONS_LOG_ERROR_HTTP_CODE_TEXT . curl_getinfo($ch, CURLINFO_HTTP_CODE));
                    curl_close($ch);
                    continue;
                }
                curl_close($ch);
                $json = json_decode($response, true);

                /** Check if decode fails */
                if (!isset($json) || !sizeof($json['data'])) {
                    Twispay_Logger::api_log(SUBSCRIPTIONS_LOG_ERROR_ORDER_NOT_FOUND_TEXT . $subscription['orders_id']);
                    continue;
                }
                if ((NULL !== $json) && (200 == $json['code']) && ('Success' == $json['message'])) {
                    $update_data = $json['data'];
                    /** normalize the response */
                    $update_data['status'] = $update_data['orderStatus'];
                    $update_data['orderId'] = $orderPlatformId;

                    /** Update the local order status */
                    Twispay_Status_Updater::updateStatus_IPN($update_data, 0);
                    Twispay_Logger::api_log(SUBSCRIPTIONS_LOG_OK_SET_STATUS_TEXT . $subscription['orders_id']);
                    $total_synced += 1;
                } else {
                    Twispay_Logger::api_log(SUBSCRIPTIONS_LOG_ERROR_GET_STATUS_TEXT . $subscription['orders_id']);
                    continue;
                }
            }
            if ($total_synced == 0 && $error['error'] == 0) {
                $error = array('message' => SUBSCRIPTIONS_LOG_ERROR_NO_ORDERS_FOUND_TEXT, 'error' => 1);
            }
            if ($error['error'] == 0) {
                $data = array(
                'status' => 'Success',
                'synced' => $total_synced,
            );
            } elseif ($error['error'] == 1) {
                $data = array(
                'status' => $error['message'],
                'synced' => 0,
            );
            }
            return $data;
        }
    }
endif; /* End if class_exists. */
