<?php
/**
 * Twispay Helpers
 *
 * Creates helper methods for operations over transactions table
 *
 * @author   Twistpay
 * @version  1.0.1
 */

/* Security class check */
if (! class_exists('Twispay_Transactions')) :
    /**
     * Class that implements custom transaction table and the assigned operations
     */
    class Twispay_Transactions
    {
        public static function createTransactionsTable()
        {
            $sql = "
              CREATE TABLE IF NOT EXISTS `twispay_transactions` (
                  `transaction_id` int(11) NOT NULL AUTO_INCREMENT,
                  `order_id` int(11) NOT NULL,
                  `status` varchar(16) NOT NULL,
                  `invoice` varchar(30) NOT NULL,
                  `identifier` varchar(30) NOT NULL,
                  `customerId` int(11) NOT NULL,
                  `orderId` int(11) NOT NULL,
                  `cardId` int(11) NOT NULL,
                  `transactionId` int(11) NOT NULL,
                  `transactionKind` varchar(16) NOT NULL,
                  `amount` float NOT NULL,
                  `currency` varchar(8) NOT NULL,
                  `date` DATETIME NOT NULL,
                  `refund_date` DATETIME NOT NULL,
                  PRIMARY KEY (`transaction_id`)
              ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
            return tep_db_query($sql);
        }

        public static function dropTransactionsTable()
        {
            return tep_db_query("DROP TABLE IF EXISTS `twispay_transactions`");
        }

        /**
         * Function that insert a recording into twispay_transactions table.
         *
         * @param array([key => value]) data - Array of data to be populated
         *
         * @return array([key => value]) - The data that was added
         *
         */
        public static function insertTransaction($data)
        {
            $data =json_decode(json_encode($data), true);

            $columns = array(
              'order_id',
              'status',
              'invoice',
              'identifier',
              'customerId',
              'orderId',
              'cardId',
              'transactionId',
              'transactionKind',
              'amount',
              'currency',
              'date',
          );

            if (!empty($data['timestamp'])) {
                $data['date'] = date('Y-m-d H:i:s', $data['timestamp']);
                unset($data['timestamp']);
            }
            if (!empty($data['identifier'])) {
                $data['identifier'] = explode("_", $data['identifier'])[1];
            }
            $query = "INSERT INTO `twispay_transactions` SET ";
            foreach ($data as $key => $value) {
                if (!in_array($key, $columns)) {
                    unset($data[$key]);
                } else {
                    $query .= tep_db_input($key)."="."'" .tep_db_input($value). "',";
                }
            }
            $query = rtrim($query, ',');
            tep_db_query($query);

            return $query;
        }

        /**
         * Function that returns transaction data
         *
         * @param int id - Transaction id
         *
         * @return array([key => value]) - Transaction array
         *
        **/
        public static function getTransaction($id)
        {
            $query = tep_db_query("SELECT * FROM `twispay_transactions` WHERE `transactionId`='" . (int)$id . "'");
            if (tep_db_num_rows($query)) {
                return tep_db_fetch_array($query);
            } else {
                return false;
            }
        }

        /**
         * Check if a transaction exist or not
         *
         * @param int id - Transaction id
         *
         * @return array([key => value]) - Transaction array
         *
        **/
        public static function checkTransaction($id)
        {
            $query = tep_db_query("SELECT 1 FROM `twispay_transactions` WHERE `transactionId`='" . (int)$id . "'");
            if (tep_db_num_rows($query)) {
                return true;
            } else {
                return false;
            }
        }

        /**
         * Function that update transactions from twispay_transactions table based on the transaction id.
         *
         * @param string id - The id of the transaction to be updated
         * @param string status - The new status of the transaction to be updated
         *
         * @return array([key => value]) - string 'query'     - The query that was called
         *                                 integer 'affected' - Number of affected rows
         *
         */
        public static function updateTransactionStatus($id, $status)
        {
            require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Status_Updater.php');
            $db_status = tep_db_input($status);

            if ($status == Twispay_Status_Updater::$RESULT_STATUSES['REFUND_REQUESTED']) {
                $query_txt = "UPDATE `twispay_transactions` SET `status`='".$db_status."',`refund_date`= NOW() WHERE `transactionId`='" . (int)$id . "' AND `status`!='".$db_status."'";
            } else {
                $query_txt = "UPDATE `twispay_transactions` SET `status`='".$db_status."' WHERE `transactionId`='" . (int)$id . "' AND `status`!='".$db_status."'";
            }
            $query = tep_db_query($query_txt);
            $array = array(
             'query' => $query_txt,
             'affected'  => $query,
         );
            return $array;
        }

        /**
         * Function that call the refund operation via Twispay API and update the local order based on the response.
         *
         * @param array trans_id - Twispay transaction id
         *
         * @return array([key => value,]) - string 'status'         - Operation message
         *                                  string 'rawdata'        - Unprocessed response
         *                                  string 'trans_id'       - The twispay id of the refunded transaction
         *                                  string 'id_cart'        - The opencart id of the canceled order
         *                                  boolean 'refunded'      - Operation success indicator
         *
         */
        public static function refundTransaction($trans_id)
        {
            require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Logger.php');
            require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Status_Updater.php');
            $transaction = self::getTransaction($trans_id);

            /** Get the Private Key. */
            if (defined("MODULE_PAYMENT_TWISPAY_TESTMODE") &&  MODULE_PAYMENT_TWISPAY_TESTMODE == "True") {
                $url = 'https://api-stage.twispay.com/transaction/' . $transaction['transactionId'];
                $secretKey = MODULE_PAYMENT_TWISPAY_STAGE_KEY;
            } else {
                $url = 'https://api.twispay.com/transaction/' . $transaction['transactionId'];
                $secretKey = MODULE_PAYMENT_TWISPAY_LIVE_KEY;
            }
            $postData = 'amount=' . $transaction['amount'] . '&' . 'message=' . 'Refund for order ' . $transaction['orderId'];

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

            if ($json->message == 'Success') {
                $data = array(
                     'status'          => Twispay_Status_Updater::$RESULT_STATUSES['REFUND_OK'],
                     'rawdata'         => $json,
                     'transactionId'   => $trans_id,
                     'externalOrderId' => $transaction['order_id'],
                     'refunded'        => 1,
                 );
                Twispay_Status_Updater::updateStatus_IPN($data);
                self::updateTransactionStatus($trans_id, Twispay_Status_Updater::$RESULT_STATUSES['REFUND_REQUESTED']);
            } else {
                $data = array(
                     'status'          => isset($json->error)?$json->error[0]->message:$json->message,
                     'rawdata'         => $json,
                     'transactionId'   => $trans_id,
                     'externalOrderId' => $transaction['order_id'],
                     'refunded'        => 0,
                 );
            }
            Twispay_Logger::api_log(LOG_REFUND_RESPONSE_TEXT.json_encode($data));
            return $data;
        }
    }
endif; /* End if class_exists. */
