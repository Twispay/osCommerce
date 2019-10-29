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
         *
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

            if (!empty($data['timestamp'])) {
                $data['date'] = date('Y-m-d H:i:s', $data['timestamp']);
                unset($data['timestamp']);
            }
            if (!empty($data['identifier'])) {
                $data['identifier'] = explode("_", $data['identifier'])[1];
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
         * @return array([key => value]) - Transaction array
         *
        **/
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
         * Checks if a transaction exist or not
         *
         * @param int id - Transaction id
         *
         * @return array([key => value]) - Transaction array
         *
        **/
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
         *
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
         * Function that updates the status of a transaction.
         *
         * @param string id - The id of the transaction to be updated
         * @param float amount - The new status of the transaction to be updated
         *
         * @return float - The refunded amount
         *
         */
        public static function addTransactionRefundedAmount($id, $val)
        {
            $update_query = tep_db_query("UPDATE `".self::$TABLE_TWISPAY_TRANSACTIONS."` SET `refunded_amount`= `refunded_amount`+'".tep_db_input($val)."' WHERE `transactionId`='" . (int)$id . "'");
            $get_query = tep_db_query("SELECT `refunded_amount` FROM `".self::$TABLE_TWISPAY_TRANSACTIONS."` WHERE `transactionId`='" . (int)$id . "'");
            return floatval(tep_db_fetch_array($get_query)['refunded_amount']);
        }
    }
endif; /* End if class_exists. */
