<?php
/**
 * Twispay Helpers
 *
 * Updates the statused of orders based
 *  on the status read from the server response.
 *
 * @author   Twispay
 * @version  1.0.1
 */

/* Security class check */
if (! class_exists('Twispay_Status_Updater')) :
    /**
     * Class that implements methods to update the statuses
     * of orders based on the status received
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
                                         , 'REFUND_OK' => 'refund-ok' /* Refund received */
                                         , 'PARTIAL_REFUNDED' => 'partial-refunded' /* Partial refunded */
                                         , 'TOTAL_REFUNDED' => 'total-refunded' /* Fully refunded */
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
         * @return array([key => value]) - boolean success - The order success flag
         *                                 string message - The error message
         *
         */
        public static function updateStatus_backUrl($decrypted)
        {
            $order_id = $decrypted['externalOrderId'];
            if (isset($decrypted['custom']) && isset($decrypted['custom']['comments'])) {
                $comments = $decrypted['custom']['comments'];
                $comments = strlen($comments)>0?" - ".$comments:$comments;
            } else {
                $comments = "";
            }

            switch ($decrypted['status']) {
                case Twispay_Status_Updater::$RESULT_STATUSES['COMPLETE_FAIL']:
                    /* Mark order as Failed. */
                    Oscommerce_Order::updateStatus($order_id, MODULE_PAYMENT_TWISPAY_FAILED_ORDER_STATUS_ID/*Failed*/, ORDER_FAILED_NOTICE_TEXT.$comments);
                    Twispay_Logger::log(LOG_OK_STATUS_FAILED_TEXT . $order_id);
                    return ['success' => false
                           ,'message' => ORDER_FAILED_NOTICE_TEXT
                           ];
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['THREE_D_PENDING']:
                    /* Mark order as Pending. */
                    Oscommerce_Order::updateStatus($order_id, 1/*Pending*/, ORDER_HOLD_NOTICE_TEXT.$comments);
                    Twispay_Logger::log(LOG_OK_STATUS_HOLD_TEXT . $order_id);
                    return ['success' => false
                           ,'message' => ORDER_HOLD_NOTICE_TEXT
                           ];
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['IN_PROGRESS']:
                case Twispay_Status_Updater::$RESULT_STATUSES['COMPLETE_OK']:
                    /* Mark order as Processing. */
                    Oscommerce_Order::updateStatus($order_id, 2/*Processing*/, ORDER_PAID_NOTICE_TEXT.$decrypted['transactionId'].$comments);
                    Twispay_Logger::log(LOG_OK_STATUS_COMPLETE_TEXT . $order_id);
                    return ['success' => true];
                break;

                default:
                    Twispay_Logger::log(LOG_ERROR_WRONG_STATUS_TEXT . $decrypted['status']);
                    return ['success' => false
                           ,'message' => ""
                           ];
                break;
            }
        }


        /**
         * Update the status of an order according to the received server status.
         *
         * @param array([key => value]) decrypted: Decrypted order message.
         *
         * @return boolean success - The order success flag
         *
         */
        public static function updateStatus_IPN($decrypted)
        {
            /* Extract the order. */
            $order_id = $decrypted['externalOrderId'];
            if (isset($decrypted['custom']) && isset($decrypted['custom']['comments'])) {
                $comments = $decrypted['custom']['comments'];
                $comments = strlen($comments)>0?"-".$comments:$comments;
            } else {
                $comments = "";
            }

            switch ($decrypted['status']) {
                /** no case for UNCERTAIN status */
                case Twispay_Status_Updater::$RESULT_STATUSES['EXPIRING']:
                case Twispay_Status_Updater::$RESULT_STATUSES['CANCEL_OK']:
                    /* Mark order as Canceled. */
                    Oscommerce_Order::updateStatus($order_id, MODULE_PAYMENT_TWISPAY_CANCELED_ORDER_STATUS_ID/*Canceled*/, ORDER_CANCELED_NOTICE_TEXT);
                    Twispay_Logger::log(LOG_OK_STATUS_CANCELED_TEXT . $order_id);
                    return ['success' => false];
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['COMPLETE_FAIL']:
                    /* Mark order as Failed. */
                    Oscommerce_Order::updateStatus($order_id, MODULE_PAYMENT_TWISPAY_FAILED_ORDER_STATUS_ID/*Failed*/, ORDER_FAILED_NOTICE_TEXT.$comments);
                    Twispay_Logger::log(LOG_OK_STATUS_FAILED_TEXT . $order_id);
                    return ['success' => false];
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['VOID_OK']:
                    /* Mark order as Voided. */
                    Oscommerce_Order::updateStatus($order_id, MODULE_PAYMENT_TWISPAY_VOIDED_ORDER_STATUS_ID/*Voided*/, ORDER_VOIDED_NOTICE_TEXT.$comments);
                    Twispay_Logger::log(LOG_OK_STATUS_VOIDED_TEXT . $order_id);
                    return ['success' => false];
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['CHARGE_BACK']:
                    /* Mark order as Chargedback. */
                    Oscommerce_Order::updateStatus($order_id, MODULE_PAYMENT_TWISPAY_CHARGEDBACK_ORDER_STATUS_ID/*Chargedback*/, ORDER_CHARGEDBACK_NOTICE_TEXT.$comments);
                    Twispay_Logger::log(LOG_OK_STATUS_CHARGEDBACK_TEXT . $order_id);
                    return ['success' => false];
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['REFUND_REQUESTED']:
                    /* Mark order as refunded. */
                    Oscommerce_Order::updateStatus($order_id, MODULE_PAYMENT_TWISPAY_REFUNDED_ORDER_STATUS_ID/*Refunded*/, ORDER_REFUND_REQUESTED_NOTICE_TEXT.$decrypted['transactionId']);
                    Twispay_Logger::log(LOG_OK_STATUS_REFUNDED_TEXT . $order_id);
                    return ['success' => false];
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['REFUND_OK']:
                    /* Add refund transaction to order status history. */
                    Oscommerce_Order::updateStatus($order_id, -1/*Same status*/, ORDER_REFUNDED_NOTICE_TEXT.$decrypted['transactionId']);
                    Twispay_Logger::log(LOG_OK_STATUS_REFUNDED_TEXT . $order_id);
                    return ['success' => false];
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['PARTIAL_REFUNDED']:
                    /* Add partial refund to transaction history. */
                    Oscommerce_Order::updateStatus($order_id, -1/*Same status*/, ORDER_REFUND_REQUESTED_NOTICE_TEXT.$decrypted['transactionId']);
                    Twispay_Logger::log(LOG_OK_STATUS_REFUNDED_TEXT . $order_id);
                    return ['success' => false];
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['TOTAL_REFUNDED']:
                    /* Mark order as refunded. */
                    Oscommerce_Order::updateStatus($order_id, MODULE_PAYMENT_TWISPAY_REFUNDED_ORDER_STATUS_ID/*Refunded*/, ORDER_REFUND_REQUESTED_NOTICE_TEXT.$decrypted['transactionId']);
                    Twispay_Logger::log(LOG_OK_STATUS_REFUNDED_TEXT . $order_id);
                    return ['success' => false];
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['THREE_D_PENDING']:
                    /* Mark order as on-hold. */
                    Oscommerce_Order::updateStatus($order_id, 1/*Panding*/, ORDER_HOLD_NOTICE_TEXT.$comments);
                    Twispay_Logger::log(LOG_OK_STATUS_HOLD_TEXT . $order_id);
                    return ['success' => false];
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['IN_PROGRESS']:
                case Twispay_Status_Updater::$RESULT_STATUSES['COMPLETE_OK']:
                    Oscommerce_Order::updateStatus($order_id, 2/*Processing*/, ORDER_PAID_NOTICE_TEXT.$decrypted['transactionId'].$comments);
                    Twispay_Logger::log(LOG_OK_STATUS_COMPLETE_TEXT . $order_id);
                    return ['success' => true];
                break;

                default:
                    Twispay_Logger::log(IPN_LOG_ERROR_WRONG_STATUS_TEXT . $decrypted['status']);
                    return ['success' => false];
                break;
            }
        }
    }
endif; /* End if class_exists. */
