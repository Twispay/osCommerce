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
        public static $RESULT_STATUSES = [ /** Platform statuses */
                                           'UNCERTAIN' => 'uncertain' /* No response from provider */
                                         , 'IN_PROGRESS' => 'in-progress' /* Authorized */
                                         , 'COMPLETE_OK' => 'complete-ok' /* Captured */
                                         , 'COMPLETE_FAIL' => 'complete-failed' /* Not authorized */
                                         , 'CANCEL_OK' => 'cancel-ok' /* Capture reversal */
                                         , 'REFUND_OK' => 'refund-ok' /* Refund received */
                                         , 'VOID_OK' => 'void-ok' /* Authorization reversal */
                                         , 'CHARGE_BACK' => 'charge-back' /* Charge-back received */
                                         , 'THREE_D_PENDING' => '3d-pending' /* Waiting for 3d authentication */
                                         , 'EXPIRING' => 'expiring' /* The recurring order has expired */
                                           /** Local statuses */
                                         , 'PARTIAL_REFUNDED' => 'partial-refunded' /* Partial refunded */
                                         , 'TOTAL_REFUNDED' => 'total-refunded' /* Fully refunded */
                                         ];
        /**
         * Update the status of an order according to the received server status.
         *
         * @param array([key => value]) decrypted: Decrypted order message.
         *
         * @return array([key => value]) - boolean success - The order success flag
         *
         */
        public static function updateStatus_backUrl($decrypted)
        {
            /** Load dependencies */
            require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Oscommerce_Order.php');
            require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Logger.php');
            require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Subscriptions.php');
            require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Actions.php');

            /* Extract the order. */
            $orderId = $decrypted['externalOrderId'];
            /* Extract the recurring products. */
            $orderRecurring = Twispay_Subscriptions::getOrderRecurringProductsByOrderId($orderId);
            if (isset($decrypted['custom']) && isset($decrypted['custom']['comments'])) {
                $comments = $decrypted['custom']['comments'];
                $comments = strlen($comments)>0?" - ".$comments:$comments;
            } else {
                $comments = "";
            }
            /* The return object body. */
            $result = ['success' => false,'message' => ""];
            switch ($decrypted['status']) {
                /** no case for UNCERTAIN status */
                case Twispay_Status_Updater::$RESULT_STATUSES['COMPLETE_FAIL']:
                    /** Mark order as Failed. */
                    Oscommerce_Order::updateStatus($orderId, MODULE_PAYMENT_TWISPAY_FAILED_ORDER_STATUS_ID/*Failed*/, 1, ORDER_FAILED_NOTICE_TEXT.$comments);
                    Twispay_Logger::log(LOG_OK_STATUS_FAILED_TEXT . $orderId);
                    $result = ['success' => false,'message' => ORDER_FAILED_NOTICE_TEXT];
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['THREE_D_PENDING']:
                    /* Mark order as Pending. */
                    Oscommerce_Order::updateStatus($orderId, 1/*Pending*/, 1, ORDER_HOLD_NOTICE_TEXT.$comments);
                    Twispay_Logger::log(LOG_OK_STATUS_HOLD_TEXT . $orderId);
                    $result = ['success' => false,'message' => ORDER_HOLD_NOTICE_TEXT];
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['IN_PROGRESS']:
                case Twispay_Status_Updater::$RESULT_STATUSES['COMPLETE_OK']:
                    if ($orderRecurring) {
                        /* Mark order as Active. */
                        Oscommerce_Order::updateStatus($orderId, MODULE_PAYMENT_TWISPAY_ACTIVE_ORDER_STATUS_ID/*Active*/, 1, ORDER_PAID_NOTICE_TEXT.$decrypted['transactionId'].$comments);
                    } else {
                        /* Mark order as Processing. */
                        Oscommerce_Order::updateStatus($orderId, 2/*Processing*/, 1, ORDER_PAID_NOTICE_TEXT.$decrypted['transactionId'].$comments);
                    }
                    Twispay_Logger::log(LOG_OK_STATUS_COMPLETE_TEXT . $orderId);
                    $result = ['success' => true];
                break;

                default:
                    Twispay_Logger::log(LOG_ERROR_WRONG_STATUS_TEXT . $decrypted['status']);
                    $result = ['success' => false,'message' => ""];
                break;
            }

            /** Update the subscription if exists */
            if ($orderRecurring) {
                self::updateRecurring($decrypted, $orderRecurring[0]);
            }

            return $result;
        }


        /**
         * Update the status of an order according to the received server status.
         *
         * @param array([key => value]) decrypted: Decrypted order message.
         *
         * @return boolean success - The order success flag
         *
         */
        public static function updateStatus_IPN($decrypted, $allowSameStatusOverwrite = 1)
        {
            /** Load dependencies */
            require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Oscommerce_Order.php');
            require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Logger.php');
            require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Subscriptions.php');
            require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Actions.php');

            /* Extract the order. */
            $orderId = $decrypted['externalOrderId'];
            /* Extract the recurring products. */
            $orderRecurring = Twispay_Subscriptions::getOrderRecurringProductsByOrderId($orderId);
            if (isset($decrypted['custom']) && isset($decrypted['custom']['comments'])) {
                $comments = $decrypted['custom']['comments'];
                $comments = strlen($comments)>0?"-".$comments:$comments;
            } else {
                $comments = "";
            }

            /* The return object body. */
            $result = ['success' => false];
            switch ($decrypted['status']) {
                /** no case for UNCERTAIN status */
                case Twispay_Status_Updater::$RESULT_STATUSES['EXPIRING']:
                case Twispay_Status_Updater::$RESULT_STATUSES['CANCEL_OK']:
                    /* Mark order as Canceled. */
                    Oscommerce_Order::updateStatus($orderId, MODULE_PAYMENT_TWISPAY_CANCELED_ORDER_STATUS_ID/*Canceled*/, $allowSameStatusOverwrite, ORDER_CANCELED_NOTICE_TEXT);
                    Twispay_Logger::log(LOG_OK_STATUS_CANCELED_TEXT . $orderId);
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['COMPLETE_FAIL']:
                    /* Mark order as Failed. */
                    Oscommerce_Order::updateStatus($orderId, MODULE_PAYMENT_TWISPAY_FAILED_ORDER_STATUS_ID/*Failed*/, $allowSameStatusOverwrite, ORDER_FAILED_NOTICE_TEXT.$comments);
                    Twispay_Logger::log(LOG_OK_STATUS_FAILED_TEXT . $orderId);
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['VOID_OK']:
                    /* Mark order as Voided. */
                    Oscommerce_Order::updateStatus($orderId, MODULE_PAYMENT_TWISPAY_VOIDED_ORDER_STATUS_ID/*Voided*/, $allowSameStatusOverwrite, ORDER_VOIDED_NOTICE_TEXT.$comments);
                    Twispay_Logger::log(LOG_OK_STATUS_VOIDED_TEXT . $orderId);
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['CHARGE_BACK']:
                    /* Mark order as Chargedback. */
                    Oscommerce_Order::updateStatus($orderId, MODULE_PAYMENT_TWISPAY_CHARGEDBACK_ORDER_STATUS_ID/*Chargedback*/, $allowSameStatusOverwrite, ORDER_CHARGEDBACK_NOTICE_TEXT.$comments);
                    Twispay_Logger::log(LOG_OK_STATUS_CHARGEDBACK_TEXT . $orderId);
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['REFUND_OK']:
                    /* Add refund transaction to order status history. */
                    Oscommerce_Order::updateStatus($orderId, -1/*Same status*/, $allowSameStatusOverwrite, ORDER_REFUNDED_NOTICE_TEXT.$decrypted['transactionId']);
                    Twispay_Logger::log(LOG_OK_STATUS_REFUNDED_TEXT . $orderId);
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['PARTIAL_REFUNDED']:
                    /* Add partial refund to transaction history. */
                    Oscommerce_Order::updateStatus($orderId, -1/*Same status*/, $allowSameStatusOverwrite, ORDER_REFUND_REQUESTED_NOTICE_TEXT.$decrypted['transactionId']);
                    Twispay_Logger::log(LOG_OK_STATUS_REFUNDED_TEXT . $orderId);
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['TOTAL_REFUNDED']:
                    /* Mark order as refunded. */
                    if ($orderRecurring) {
                        Oscommerce_Order::updateStatus($orderId, -1/*Same status*/, $allowSameStatusOverwrite, ORDER_REFUND_REQUESTED_NOTICE_TEXT.$decrypted['transactionId']);
                    } else {
                        Oscommerce_Order::updateStatus($orderId, MODULE_PAYMENT_TWISPAY_REFUNDED_ORDER_STATUS_ID/*Refunded*/, $allowSameStatusOverwrite, ORDER_REFUND_REQUESTED_NOTICE_TEXT.$decrypted['transactionId']);
                    }
                    Twispay_Logger::log(LOG_OK_STATUS_REFUNDED_TEXT . $orderId);
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['THREE_D_PENDING']:
                    /* Mark order as on-hold. */
                    Oscommerce_Order::updateStatus($orderId, 1/*Pending*/, $allowSameStatusOverwrite, ORDER_HOLD_NOTICE_TEXT.$comments);
                    Twispay_Logger::log(LOG_OK_STATUS_HOLD_TEXT . $orderId);
                break;

                case Twispay_Status_Updater::$RESULT_STATUSES['IN_PROGRESS']:
                case Twispay_Status_Updater::$RESULT_STATUSES['COMPLETE_OK']:
                    if ($orderRecurring) {
                        /* Mark order as Active. */
                        Oscommerce_Order::updateStatus($orderId, MODULE_PAYMENT_TWISPAY_ACTIVE_ORDER_STATUS_ID/*Active*/, $allowSameStatusOverwrite, ORDER_PAID_NOTICE_TEXT.$decrypted['transactionId'].$comments);
                    } else {
                        /* Mark order as Processing. */
                        Oscommerce_Order::updateStatus($orderId, 2/*Processing*/, $allowSameStatusOverwrite, ORDER_PAID_NOTICE_TEXT.$decrypted['transactionId'].$comments);
                    }
                    Twispay_Logger::log(LOG_OK_STATUS_COMPLETE_TEXT . $orderId);
                    $result = ['success' => true];
                break;

                default:
                    Twispay_Logger::log(LOG_ERROR_WRONG_STATUS_TEXT . $decrypted['status']);
                break;
            }

            /** Update the subscription if exists */
            if ($orderRecurring) {
                self::updateRecurring($decrypted, $orderRecurring[0], $allowSameStatusOverwrite);
            }

            return $result;
        }

        /**
         * Update the subscription status.
         *
         * @param array([key => value]) decrypted: Decrypted order message.
         * @param array([key => value]) subscription: The recurring product contained by the order.
         *
         */
        private static function updateRecurring($decrypted, $subscription, $allowSameStatusOverwrite)
        {
            require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Transactions.php');
            $orderId = $decrypted['externalOrderId'];
            switch ($decrypted['status']) {
              case Twispay_Status_Updater::$RESULT_STATUSES['EXPIRING']:
              case Twispay_Status_Updater::$RESULT_STATUSES['CANCEL_OK']:
              case Twispay_Status_Updater::$RESULT_STATUSES['COMPLETE_FAIL']:
              case Twispay_Status_Updater::$RESULT_STATUSES['VOID_OK']:
              case Twispay_Status_Updater::$RESULT_STATUSES['CHARGE_BACK']:
              case Twispay_Status_Updater::$RESULT_STATUSES['THREE_D_PENDING']:
                  Oscommerce_Order::updateRecurringStatus($orderId, Twispay_Subscriptions::$STATUSES['CANCELED']/*Canceled*/);
              break;

              case Twispay_Status_Updater::$RESULT_STATUSES['IN_PROGRESS']:
              case Twispay_Status_Updater::$RESULT_STATUSES['COMPLETE_OK']:
                  Oscommerce_Order::updateRecurringStatus($orderId, Twispay_Subscriptions::$STATUSES['ACTIVE']/*Active*/);
                  if (Twispay_Transactions::isLastRecurringTransaction($orderId, $subscription) && $allowSameStatusOverwrite) {
                     Twispay_Actions::cancelSubscription($decrypted['orderId']/*Twispay order id*/, $orderId, 'Automatic');
                  }
              break;
          }
        }
    }
endif; /** End if class_exists. */
