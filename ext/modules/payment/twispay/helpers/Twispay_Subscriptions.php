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
if (! class_exists('Twispay_Subscriptions')) :
    /**
     * Class that implements custom transaction table and the assigned operations
     */
    class Twispay_Subscriptions
    {
        /* Array containing the possible subscription statuses. */
        public static $STATUSES = [ 'ACTIVE' => 'Active' /* Active */
                                  , 'PENDING' => 'Pending' /* Canceled */
                                  , 'CANCELED' => 'Canceled' /* Canceled */
                                  ];

        /**
         * Function that returns all of the order recurring products
         *
         * @param int orderId - The order id
         *
         * @return array - The recurring products | false
         */
        public static function getOrderRecurringProductsByOrderId($orderId)
        {
            $query = tep_db_query("SELECT * FROM " . TABLE_ORDERS_PRODUCTS . " op LEFT JOIN " . TABLE_PRODUCTS . " p on(op.products_id = p.products_id) WHERE op.orders_id = '" . (int)$orderId . "' AND products_custom_recurring_status = 1");
            while ($result = tep_db_fetch_array($query)) {
                $subscriptions[] = $result;
            }
            if (sizeof($subscriptions)) {
                return $subscriptions;
            } else {
                return false;
            }
        }

        /**
         * Function that returns all the information defined in the database about a product including the custom defined recurrence fields
         *
         * @param int id - The product id
         *
         * @return array - The product data | false
         */
        public static function getRecurringProduct($productId)
        {
          $query = tep_db_query("SELECT * FROM " . TABLE_PRODUCTS . " WHERE products_id = '" . (int)$productId . "' AND products_custom_recurring_status = 1");
          if (tep_db_num_rows($query)) {
              return tep_db_fetch_array($query);
          }
          return false;
        }

        /**
         * Function that check if the given list of products contain any recurring ones
         *
         * @param array $products - The list of products to be checked
         *
         * @return boolean - The recurring products existance
         */
        public static function containRecurrings($products)
        {
            foreach ($products as $product) {
              if (self::getRecurringProduct($product['id'])) {
                  return true;
              }
            }
            return false;
        }

        /**
         * Function that return the subscription status
         *
         * @param int orderId - The recurring order (subscription) id
         *
         * @return string - The subscription status | false
         */
        public static function subscriptionStatus($orderId)
        {
            $query = tep_db_query("SELECT `orders_custom_recurring_status`, `orders_id` FROM " . TABLE_ORDERS . " WHERE orders_id = '" . (int)$orderId . "'");
            if (tep_db_num_rows($query)) {
                return tep_db_fetch_array($query)['orders_custom_recurring_status'];
            } else {
                return false;
            }
        }

        /**
         * Function that returns all of the recurring orders
         *
         * @return array - The subscription fields | false
         */
        public static function getAllSubscriptions()
        {
            $query = tep_db_query("SELECT `orders_custom_recurring_status`, `orders_id` FROM " . TABLE_ORDERS . " WHERE orders_custom_recurring_status IS NOT NULL");
            while ($result = tep_db_fetch_array($query)) {
                $subscriptions[] = $result;
            }
            if (sizeof($subscriptions)) {
                return $subscriptions;
            } else {
                return false;
            }
        }
    }
endif; /* End if class_exists. */
