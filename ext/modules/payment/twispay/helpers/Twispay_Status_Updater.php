<?php
/**
 * Twispay Helpers
 *
 * Updates the statused of orders and subscriptions based
 *  on the status read from the server response.
 *
 * @author   Twistpay
 * @version  1.0.1
 */

/* Security class check */
if (! class_exists('Twispay_Status_Updater')) :
    /**
     * Class that implements methods to update the statuses
     * of orders and subscriptions based on the status received
     * from the server.
     */
    class Twispay_Status_Updater
    {
        /* Array containing the possible result statuses. */
        public static $RESULT_STATUSES = [ 'UNCERTAIN' => 'uncertain' /* No response from provider */
                                         , 'IN_PROGRESS' => 'in-progress' /* Authorized */
                                         , 'COMPLETE_OK' => 'complete-ok' /* Captured */
                                         , 'COMPLETE_FAIL' => 'complete-failed' /* Not authorized */
                                         , 'CANCEL_OK' => 'cancel-ok' /* Capture reversal */
                                         , 'REFUND_OK' => 'refund-ok' /* Settlement reversal */
                                         , 'REFUND_REQUESTED' => 'refund-requested' /* The recurring order has expired */
                                         , 'VOID_OK' => 'void-ok' /* Authorization reversal */
                                         , 'CHARGE_BACK' => 'charge-back' /* Charge-back received */
                                         , 'THREE_D_PENDING' => '3d-pending' /* Waiting for 3d authentication */
                                         , 'EXPIRING' => 'expiring' /* The recurring order has expired */
                                         ];
        /**
         * Update the status of an order according to the received server status.
         *
         * @param array([key => value]) decrypted: Decrypted order message.
         *
         * @return void
         *
         */
        public static function updateStatus_backUrl($decrypted)
        {
            $order_id = $decrypted['externalOrderId'];
            if (isset($decrypted['custom']) && isset($decrypted['custom']['comments'])) {
                $comments = $decrypted['custom']['comments'];
                $comments = strlen($comments)>0?' - '.$comments:$comments;
            } else {
                $comments = '';
            }

            $order_recurring = 0;

            switch ($decrypted['status']) {
                case Twispay_Status_Updater::$RESULT_STATUSES['COMPLETE_FAIL']:
                    /* Mark order as Failed. */
                    Oscommerce_Order::updateStatus($order_id, MODULE_PAYMENT_TWISPAY_FAILED_ORDER_STATUS_ID/*Failed*/, ORDER_FAILED_NOTICE_TEXT.$comments);
                    Twispay_Logger::log(LOG_OK_STATUS_FAILED_TEXT . $order_id);
                    Twispay_Notification::print_notice();
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['THREE_D_PENDING']:
                    /* Mark order as Pending. */
                    Oscommerce_Order::updateStatus($order_id, 1/*Pending*/, ORDER_HOLD_NOTICE_TEXT.$comments);
                    Twispay_Logger::log(LOG_OK_STATUS_HOLD_TEXT . $order_id);
                    Twispay_Notification::print_notice(LOG_OK_STATUS_HOLD_TEXT);
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['IN_PROGRESS']:
                case Twispay_Status_Updater::$RESULT_STATUSES['COMPLETE_OK']:
                    /* Mark order as Processing. */
                    Oscommerce_Order::updateStatus($order_id, 2/*Processing*/, ORDER_PAID_NOTICE_TEXT.$decrypted['transactionId'].$comments);
                    Twispay_Logger::log(LOG_OK_STATUS_COMPLETE_TEXT . $order_id);
                    Twispay_Thankyou::redirect(MODULE_PAYMENT_TWISPAY_PAGE_REDIRECT);
                break;

                default:
                    Twispay_Logger::log(BU_LOG_ERROR_WRONG_STATUS_TEXT . $decrypted['status']);
                    Twispay_Notification::print_notice();
                break;
            }

            // //In case the order is a subscription, update it
            // if ($order_recurring) {
            //     Twispay_Status_Updater::updateSubscription($order_recurring, $decrypted, $that);
            // }
        }


        /**
         * Update the status of an subscription according to the received server status.
         *
         * @param array([key => value]) decrypted: Decrypted order message.
         *
         * @return void
         *
         */
        public static function updateStatus_IPN($decrypted)
        {
            /* Extract the order. */
            $order_id = $decrypted['externalOrderId'];
            if (isset($decrypted['custom']) && isset($decrypted['custom']['comments'])) {
                $comments = $decrypted['custom']['comments'];
                $comments = strlen($comments)>0?'-'.$comments:$comments;
            } else {
                $comments = '';
            }
            $order_recurring = 0;

            switch ($decrypted['status']) {
                /** no case for UNCERTAIN status */
                case Twispay_Status_Updater::$RESULT_STATUSES['EXPIRING']:
                case Twispay_Status_Updater::$RESULT_STATUSES['CANCEL_OK']:
                    /* Mark order as Canceled. */
                    Oscommerce_Order::updateStatus($order_id, MODULE_PAYMENT_TWISPAY_CANCELED_ORDER_STATUS_ID/*Canceled*/, ORDER_CANCELED_NOTICE_TEXT);
                    Twispay_Logger::log(LOG_OK_STATUS_CANCELED_TEXT . $order_id);
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['COMPLETE_FAIL']:
                    /* Mark order as Failed. */
                    Oscommerce_Order::updateStatus($order_id, MODULE_PAYMENT_TWISPAY_FAILED_ORDER_STATUS_ID/*Failed*/, ORDER_FAILED_NOTICE_TEXT.$comments);
                    Twispay_Logger::log(LOG_OK_STATUS_FAILED_TEXT . $order_id);
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['VOID_OK']:
                    /* Mark order as Voided. */
                    Oscommerce_Order::updateStatus($order_id, MODULE_PAYMENT_TWISPAY_VOIDED_ORDER_STATUS_ID/*Voided*/, ORDER_VOIDED_NOTICE_TEXT.$comments);
                    Twispay_Logger::log(LOG_OK_STATUS_VOIDED_TEXT . $order_id);
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['CHARGE_BACK']:
                    /* Mark order as Chargedback. */
                    Oscommerce_Order::updateStatus($order_id, MODULE_PAYMENT_TWISPAY_CHARGEDBACK_ORDER_STATUS_ID/*Chargedback*/, ORDER_CHARGEDBACK_NOTICE_TEXT.$comments);
                    Twispay_Logger::log(LOG_OK_STATUS_CHARGEDBACK_TEXT . $order_id);
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['REFUND_OK']:
                    /* Mark order as refunded. */
                    if ($order_recurring) {
                      // TODO Add order history with the same status
                    } else {
                      Oscommerce_Order::updateStatus($order_id, MODULE_PAYMENT_TWISPAY_REFUNDED_ORDER_STATUS_ID/*Refunded*/, ORDER_REFUNDED_NOTICE_TEXT.$decrypted['transactionId']);
                    }
                    Twispay_Logger::log(LOG_OK_STATUS_REFUNDED_TEXT);
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['THREE_D_PENDING']:
                    /* Mark order as on-hold. */
                    Oscommerce_Order::updateStatus($order_id, 1/*Panding*/, ORDER_HOLD_NOTICE_TEXT.$comments);
                    Twispay_Logger::log(LOG_OK_STATUS_HOLD_TEXT . $order_id);
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['IN_PROGRESS']:
                case Twispay_Status_Updater::$RESULT_STATUSES['COMPLETE_OK']:
                    Oscommerce_Order::updateStatus($order_id, 2/*Processing*/, ORDER_PAID_NOTICE_TEXT.$decrypted['transactionId'].$comments);
                    Twispay_Logger::log(LOG_OK_STATUS_COMPLETE_TEXT . $order_id);
                break;

                default:
                    Twispay_Logger::log(IPN_LOG_ERROR_WRONG_STATUS_TEXT . $decrypted['status']);
                break;
            }

            //In case the order is a subscription, update it
            // if ($order_recurring) {
            //     Twispay_Status_Updater::updateSubscription($order_recurring, $decrypted, $that);
            // }
        }


        // /**
        //  * Update the status of an subscription according to the received server status.
        //  *
        //  * @param array([key => value]) order_recurring: The recurring order data.
        //  * @param array([key => value]) decrypted: Decrypted order message.
        //  * @param object that: Controller instance use for accessing runtime values like configuration, active language, etc.
        //  *
        //  * @return void
        //  */
        // private static function updateSubscription($order_recurring, $decrypted, $that)
        // {
        //     /** load dependencies */
        //     $that->load->model('extension/payment/twispay_recurring');
        //
        //     $order_id = $decrypted['externalOrderId'];
        //     if(isset($decrypted['orderId'])){
        //         $tw_order_id = $decrypted['orderId'];
        //     }
        //     $order_recurring_id = $order_recurring['order_recurring_id'];
        //
        //     //link twispay order with opencart order
        //     if (!$order_recurring['reference']) {
        //         $that->load->model('checkout/recurring');
        //         if(isset($tw_order_id)){
        //           $resp = $that->model_checkout_recurring->editReference($order_recurring_id, 'tw_'.$tw_order_id);
        //         }
        //     }
        //
        //     //transaction header
        //     $transaction_data = [ 'order_recurring_id' => (int)$order_recurring_id
        //                         , 'date_added' => "NOW()"
        //                         , 'amount' => isset($decrypted['amount'])?(float)$decrypted['amount']:0
        //                         , 'type' => NULL
        //                         , 'reference' => isset($decrypted['transactionId'])?'tw_'.$decrypted['transactionId']:0 /** tw_@transaction_id */ ];
        //
        //     switch ($decrypted['status']) {
        //       // no case for UNCERTAIN status
        //       case Twispay_Status_Updater::$RESULT_STATUSES['COMPLETE_FAIL']:
        //         $that->model_extension_payment_twispay_recurring->editOrderRecurringStatus($order_recurring_id, 2);//inactive
        //         if($transaction_data['reference']){
        //           $transaction_data['type'] = 4;//payment_failed
        //           $that->model_extension_payment_twispay_recurring->addRecurringTransaction($transaction_data);
        //         }
        //       break;
        //
        //       case Twispay_Status_Updater::$RESULT_STATUSES['CANCEL_OK']:
        //         $that->model_extension_payment_twispay_recurring->editOrderRecurringStatus($order_recurring_id, 3);//cancelled
        //         if($transaction_data['reference']){
        //           $transaction_data['type'] = 5;//cancelled
        //           $that->model_extension_payment_twispay_recurring->addRecurringTransaction($transaction_data);
        //         }
        //       break;
        //
        //       case Twispay_Status_Updater::$RESULT_STATUSES['REFUND_OK']:
        //         // $that->model_extension_payment_twispay_recurring->editOrderRecurringStatus($order_recurring_id, 4);//suspended
        //         if($transaction_data['reference']){
        //           $transaction_data['type'] = 6;//suspended
        //           $that->model_extension_payment_twispay_recurring->addRecurringTransaction($transaction_data);
        //         }
        //       break;
        //
        //       case Twispay_Status_Updater::$RESULT_STATUSES['VOID_OK']:
        //         $that->model_extension_payment_twispay_recurring->editOrderRecurringStatus($order_recurring_id, 4);//suspended
        //         //no transaction
        //       break;
        //
        //       case Twispay_Status_Updater::$RESULT_STATUSES['CHARGE_BACK']:
        //         $that->model_extension_payment_twispay_recurring->editOrderRecurringStatus($order_recurring_id, 4);//suspended
        //         if($transaction_data['reference']){
        //           $transaction_data['type'] = 6;//suspended
        //           $that->model_extension_payment_twispay_recurring->addRecurringTransaction($transaction_data);
        //         }
        //       break;
        //
        //       case Twispay_Status_Updater::$RESULT_STATUSES['THREE_D_PENDING']:
        //         $that->model_extension_payment_twispay_recurring->editOrderRecurringStatus($order_recurring_id, 6);//pending
        //         if($transaction_data['reference']){
        //           $transaction_data['type'] = 4;//payment_failed
        //           $that->model_extension_payment_twispay_recurring->addRecurringTransaction($transaction_data);
        //         }
        //       break;
        //
        //       case Twispay_Status_Updater::$RESULT_STATUSES['EXPIRING']:
        //         $that->model_extension_payment_twispay_recurring->editOrderRecurringStatus($order_recurring_id, 5);//expired
        //         if($transaction_data['reference']){
        //           $transaction_data['type'] = 9;//transaction_expired
        //           $that->model_extension_payment_twispay_recurring->addRecurringTransaction($transaction_data);
        //         }
        //       break;
        //
        //       case Twispay_Status_Updater::$RESULT_STATUSES['IN_PROGRESS']:
        //       case Twispay_Status_Updater::$RESULT_STATUSES['COMPLETE_OK']:
        //           $that->model_extension_payment_twispay_recurring->editOrderRecurringStatus($order_recurring_id, 1);//active
        //           if($transaction_data['reference']){
        //             $transaction_data['type'] = 1;//payment_ok
        //             $that->model_extension_payment_twispay_recurring->addRecurringTransaction($transaction_data);
        //             if ($that->model_extension_payment_twispay_recurring->isLastRecurringTransaction($order_recurring)) {
        //                 $that->model_extension_payment_twispay_recurring->cancelRecurring($tw_order_id, $order_id, 'Automatic');
        //             }
        //           }
        //       break;
        //    }
        // }
    }
endif; /* End if class_exists. */
