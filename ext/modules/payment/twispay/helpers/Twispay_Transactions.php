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
if (! class_exists('Twispay_Transactions')) :
    /**
     * Class that implements custom transaction table and the assigned operations
     */
    class Twispay_Transactions
    {
        /* The custom transactions table name */
        public static $TABLE_TWISPAY_TRANSACTIONS = 'twispay_transactions';
        /**
         * Function that initializes the database table twispay_transactions.
         *
         * @return object - The mysqli_result object
         */
        public static function createTransactionsTable()
        {
            return tep_db_query(
                "CREATE TABLE IF NOT EXISTS `".self::$TABLE_TWISPAY_TRANSACTIONS."` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `order_id` int(11) NOT NULL,
                  `status` varchar(16) NOT NULL,
                  `invoice` varchar(30) NOT NULL,
                  `identifier` varchar(30) NOT NULL,
                  `customerId` int(11) NOT NULL,
                  `orderId` int(11) NOT NULL,
                  `cardId` int(11) NOT NULL,
                  `transactionId` int(11) NOT NULL UNIQUE KEY,
                  `transactionKind` varchar(16) NOT NULL,
                  `amount` float NOT NULL,
                  `currency` varchar(8) NOT NULL,
                  `date` DATETIME NOT NULL,
                  `completed` BOOLEAN NOT NULL,
                  `refunded_amount` float NOT NULL DEFAULT 0,
                  `refund_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                  PRIMARY KEY (`id`)
              ) ENGINE=MyISAM DEFAULT CHARSET=utf8;"
            );
        }

        /**
         * Function that removes the database table twispay_transactions.
         *
         * @return object - The mysqli_result object
         */
        public static function dropTransactionsTable()
        {
            return tep_db_query("DROP TABLE IF EXISTS `".self::$TABLE_TWISPAY_TRANSACTIONS."`");
        }

        /**
         * Function that inserts a recording into twispay_transactions table.
         *
         * @param array([key => value]) data - Array of data to be populated
         *
         * @return array([key => value]) - The data that was added
         */
        public static function insertTransaction($data)
        {
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
              'completed'
            );

            /** Convert timestamp to date format*/
            if (!empty($data['timestamp'])) {
                if (is_array($data['timestamp'])) {
                    $data['date'] = date('Y-m-d H:i:s', strtotime($data['timestamp']['date']));
                } else {
                    $data['date'] = date('Y-m-d H:i:s', $data['timestamp']);
                }
                unset($data['timestamp']);
            }

            /** Keep just the customer id from identifier */
            if (!empty($data['identifier']) && strpos($data['identifier'], '_') !== false) {
                $explodedVal = explode("_", $data['identifier'])[1];
                /** Check if customer id contains only digits and is not empty */
                if (!empty($explodedVal) && ctype_digit($explodedVal)) {
                    $data['identifier'] = tep_db_input($explodedVal);
                }
            }

            $query = "INSERT INTO `".self::$TABLE_TWISPAY_TRANSACTIONS."` SET ";
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
         * Function that returns the transaction data
         *
         * @param int id - Transaction id
         *
         * @return array([key => value]) - Transaction array | false
         */
        public static function getTransaction($id)
        {
            $query = tep_db_query("SELECT * FROM `".self::$TABLE_TWISPAY_TRANSACTIONS."` WHERE `transactionId`='" . (int)$id . "'");
            if (tep_db_num_rows($query)) {
                return tep_db_fetch_array($query);
            } else {
                return false;
            }
        }

        /**
         * Function that returns all the transactions asociated with the given order id
         *
         * @param int order_id - Order id
         *
         * @return array([key => value]) - Transaction array | false
         */
        public static function getOrderTransactions($order_id)
        {
            $query = tep_db_query("SELECT * FROM `".self::$TABLE_TWISPAY_TRANSACTIONS."` WHERE `order_id`='" . (int)$order_id . "'");
            if (tep_db_num_rows($query)) {
                $transactions = [];
                while ($result = tep_db_fetch_array($query)) {
                    $transactions[] = $result;
                }
                return $transactions;
            } else {
                return false;
            }
        }

        /**
         * Function that returns the number of transaction asociated with the given order id filtred by their status
         *
         * @param int order_id - Order id
         * @param string status - Transaction status
         *
         * @return int - The number transactions
         */
        public static function getOrderTransactionsNum($order_id, $status='')
        {
            $query_string = "SELECT * FROM `".self::$TABLE_TWISPAY_TRANSACTIONS."` WHERE `order_id`='" . (int)$order_id . "'";
            if ($status!='') {
                $query_string .= " AND `status` = '" . tep_db_input($status) . "'";
            }
            $query = tep_db_query($query_string);
            return tep_db_num_rows($query);
        }

        /**
         * Checks if a transaction exist or not
         *
         * @param int id - Transaction id
         *
         * @return array([key => value]) - Transaction array
         */
        public static function checkTransaction($id)
        {
            $query = tep_db_query("SELECT `completed` FROM `".self::$TABLE_TWISPAY_TRANSACTIONS."` WHERE `transactionId`='" . (int)$id . "'");
            if (tep_db_num_rows($query)) {
                return tep_db_fetch_array($query);
            } else {
                return false;
            }
        }

        /**
         * Function that updates the status of a transaction.
         *
         * @param string id - The id of the transaction to be updated
         * @param string status - The new status of the transaction to be updated
         *
         * @return array([key => value]) - string 'query'     - The query that was called
         *                                 integer 'affected' - Number of affected rows
         */
        public static function updateTransactionStatus($id, $status)
        {
            $db_status = tep_db_input($status);
            $query_txt = "UPDATE `".self::$TABLE_TWISPAY_TRANSACTIONS."` SET `status`='".$db_status."' WHERE `transactionId`='" . (int)$id . "'";
            $query = tep_db_query($query_txt);
            $array = array(
                'query' => $query_txt,
                'affected'  => $query,
            );
            return $array;
        }

        /**
         * Function that updates the refunded amount of a transaction.
         *
         * @param string id - The id of the transaction to be updated
         * @param float val - The the value to be added to actual refunded amount
         *
         * @return float - The updated refunded amount
         */
        public static function addTransactionRefundedAmount($id, $val)
        {
            $update_query = tep_db_query("UPDATE `".self::$TABLE_TWISPAY_TRANSACTIONS."` SET `refunded_amount`= `refunded_amount`+'".tep_db_input($val)."' WHERE `transactionId`='" . (int)$id . "'");
            $get_query = tep_db_query("SELECT `refunded_amount` FROM `".self::$TABLE_TWISPAY_TRANSACTIONS."` WHERE `transactionId`='" . (int)$id . "'");
            return floatval(tep_db_fetch_array($get_query)['refunded_amount']);
        }

        /**
         * Function that check if the next transaction that will be added is the last one.
         *
         * @param int orderId - The recurring order id
         * @param array subscription - The recurring order fields
         * @param string successStatus - The succes status string used by platform
         *
         * @return boolean - TRUE / FALSE
         */
        public static function isLastRecurringTransaction($orderId, $subscription, $successStatus = "complete-ok")
        {
            $trialState = intval($subscription['products_custom_trial_status']);
            $transactions = self::getOrderTransactionsNum($orderId, $successStatus);
            $duration = intval($subscription['products_custom_recurring_duration']) + $trialState;

            /** if number of successful transactions is lower then recurring duration + 1(if the trial period is active) */
            if ($transactions < $duration - 1/** The transaction is added after this call*/) {
                return false;
            } else {
                return true;
            }
        }

        /**
         * Function that returns the Twispay's order id
         *
         * @param int localOrderId - The oscommerce order id
         *
         * @return int - Twispay order id | false
         */
        public static function getTwispayOrderId($localOrderId)
        {
            if ($twOrderId = self::getOrderTransactions($localOrderId)) {
                return $twOrderId[0]['orderId'];
            } else {
                return false;
            }
        }
    }
endif; /* End if class_exists. */
