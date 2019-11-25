<?php
/**
 * Twispay Helpers
 *
 * Creates helper methods for operations with orders
 * The code below is a copy of the osCommerce framework code adapted in some places to the needs of the module
 *
 * @author   Twispay
 * @version  1.0.1
 */

/* Security class check */
if (! class_exists('Oscommerce_Order')) :
    /**
     * Class that implements oscommerce operations with orders
     */
    class Oscommerce_Order
    {
        /**
         * Create an uncommited(ghost) order
         */
        public static function create()
        {
            global $cartID, $cart_Twispay_ID, $customer_id, $languages_id, $order, $order_total_modules;

            /** Subscriptions */
            $subscription = false;
            if (sizeof($order->products) == 1) {
                /** Get the recurring product ID */
                $prodId = tep_get_prid($order->products[0]['id']);
                /** Read the subscription's fields from recurring Products database table */
                $query = tep_db_query("SELECT `products_custom_recurring_status`,`products_custom_recurring_duration`,`products_custom_recurring_cycle`,`products_custom_recurring_frequency`,`products_custom_trial_status`,`products_custom_trial_cycle`,`products_custom_trial_frequency`,`products_custom_trial_price` FROM " . TABLE_PRODUCTS . " WHERE products_id = '" . (int)$prodId . "' AND products_custom_recurring_status = 1");
                if (tep_db_num_rows($query)) {
                    $subscription = tep_db_fetch_array($query);
                }
            }
            /** Subscriptions */

            if (tep_session_is_registered('cartID')) {
                $insert_order = false;

                if (tep_session_is_registered('cart_Twispay_ID')) {
                    $order_id = substr($cart_Twispay_ID, strpos($cart_Twispay_ID, '-') + 1);

                    $curr_check = tep_db_query("SELECT currency FROM " . TABLE_ORDERS . " WHERE orders_id = '" . (int)$order_id . "'");
                    $curr = tep_db_fetch_array($curr_check);

                    if (($curr['currency'] != $order->info['currency']) || ($cartID != substr($cart_Twispay_ID, 0, strlen($cartID)))) {
                        Oscommerce_Order::delete($order_id);
                        $insert_order = true;
                    }
                } else {
                    $insert_order = true;
                }

                if ($insert_order == true) {
                    $order_totals = array();
                    if (is_array($order_total_modules->modules)) {
                        reset($order_total_modules->modules);
                        while (list(, $value) = each($order_total_modules->modules)) {
                            $class = substr($value, 0, strrpos($value, '.'));
                            if ($GLOBALS[$class]->enabled) {
                                for ($i = 0, $n = sizeof($GLOBALS[$class]->output); $i < $n; $i++) {
                                    if (tep_not_null($GLOBALS[$class]->output[$i]['title']) && tep_not_null($GLOBALS[$class]->output[$i]['text'])) {
                                        $order_totals[] = array('code' => $GLOBALS[$class]->code,
                                          'title' => $GLOBALS[$class]->output[$i]['title'],
                                          'text' => $GLOBALS[$class]->output[$i]['text'],
                                          'value' => $GLOBALS[$class]->output[$i]['value'],
                                          'sort_order' => $GLOBALS[$class]->sort_order);
                                    }
                                }
                            }
                        }
                    }

                    $sql_data_array = array('customers_id' => $customer_id,
                      'customers_name' => $order->customer['firstname'] . ' ' . $order->customer['lastname'],
                      'customers_company' => $order->customer['company'],
                      'customers_street_address' => $order->customer['street_address'],
                      'customers_suburb' => $order->customer['suburb'],
                      'customers_city' => $order->customer['city'],
                      'customers_postcode' => $order->customer['postcode'],
                      'customers_state' => $order->customer['state'],
                      'customers_country' => $order->customer['country']['title'],
                      'customers_telephone' => $order->customer['telephone'],
                      'customers_email_address' => $order->customer['email_address'],
                      'customers_address_format_id' => $order->customer['format_id'],
                      'delivery_name' => $order->delivery['firstname'] . ' ' . $order->delivery['lastname'],
                      'delivery_company' => $order->delivery['company'],
                      'delivery_street_address' => $order->delivery['street_address'],
                      'delivery_suburb' => $order->delivery['suburb'],
                      'delivery_city' => $order->delivery['city'],
                      'delivery_postcode' => $order->delivery['postcode'],
                      'delivery_state' => $order->delivery['state'],
                      'delivery_country' => $order->delivery['country']['title'],
                      'delivery_address_format_id' => $order->delivery['format_id'],
                      'billing_name' => $order->billing['firstname'] . ' ' . $order->billing['lastname'],
                      'billing_company' => $order->billing['company'],
                      'billing_street_address' => $order->billing['street_address'],
                      'billing_suburb' => $order->billing['suburb'],
                      'billing_city' => $order->billing['city'],
                      'billing_postcode' => $order->billing['postcode'],
                      'billing_state' => $order->billing['state'],
                      'billing_country' => $order->billing['country']['title'],
                      'billing_address_format_id' => $order->billing['format_id'],
                      'payment_method' => $order->info['payment_method'],
                      'cc_type' => $order->info['cc_type'],
                      'cc_owner' => $order->info['cc_owner'],
                      'cc_number' => $order->info['cc_number'],
                      'cc_expires' => $order->info['cc_expires'],
                      'date_purchased' => 'now()',
                      'orders_status' => MODULE_PAYMENT_TWISPAY_PREPARE_ORDER_STATUS_ID,
                      'currency' => $order->info['currency'],
                      'currency_value' => $order->info['currency_value'],
                    );

                    /** Subscriptions */
                    if ($subscription) {
                        require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Subscriptions.php');
                        $sql_data_array = array_merge($sql_data_array, [
                        'orders_custom_recurring_status' => Twispay_Subscriptions::$STATUSES['PENDING'],
                        'orders_custom_recurring_duration' => $subscription['products_custom_recurring_duration'],
                        'orders_custom_recurring_cycle' => $subscription['products_custom_recurring_cycle'],
                        'orders_custom_recurring_frequency' => $subscription['products_custom_recurring_frequency'],
                        'orders_custom_trial_status' => $subscription['products_custom_trial_status'],
                        'orders_custom_trial_cycle' => $subscription['products_custom_trial_cycle'],
                        'orders_custom_trial_frequency' => $subscription['products_custom_trial_frequency'],
                        'orders_custom_trial_price' => $subscription['products_custom_trial_price']
                      ]);
                    }
                    /** Subscriptions */

                    tep_db_perform(TABLE_ORDERS, $sql_data_array);

                    $insert_id = tep_db_insert_id();

                    for ($i = 0, $n = sizeof($order_totals); $i < $n; $i++) {
                        $sql_data_array = array('orders_id' => $insert_id,
                          'title' => $order_totals[$i]['title'],
                          'text' => $order_totals[$i]['text'],
                          'value' => $order_totals[$i]['value'],
                          'class' => $order_totals[$i]['code'],
                          'sort_order' => $order_totals[$i]['sort_order']);

                        tep_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);
                    }

                    for ($i = 0, $n = sizeof($order->products); $i < $n; $i++) {
                        $sql_data_array = array('orders_id' => $insert_id,
                          'products_id' => tep_get_prid($order->products[$i]['id']),
                          'products_model' => $order->products[$i]['model'],
                          'products_name' => $order->products[$i]['name'],
                          'products_price' => $order->products[$i]['price'],
                          'final_price' => $order->products[$i]['final_price'],
                          'products_tax' => $order->products[$i]['tax'],
                          'products_quantity' => $order->products[$i]['qty']);

                        tep_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);

                        $order_products_id = tep_db_insert_id();

                        $attributes_exist = '0';
                        if (isset($order->products[$i]['attributes'])) {
                            $attributes_exist = '1';
                            for ($j = 0, $n2 = sizeof($order->products[$i]['attributes']); $j < $n2; $j++) {
                                if (DOWNLOAD_ENABLED == 'true') {
                                    $attributes_query = "SELECT popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pad.products_attributes_maxdays, pad.products_attributes_maxcount , pad.products_attributes_filename
                                         FROM " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                         LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                         ON pa.products_attributes_id=pad.products_attributes_id
                                         WHERE pa.products_id = '" . $order->products[$i]['id'] . "'
                                         AND pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "'
                                         AND pa.options_id = popt.products_options_id
                                         AND pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "'
                                         AND pa.options_values_id = poval.products_options_values_id
                                         AND popt.language_id = '" . $languages_id . "'
                                         AND poval.language_id = '" . $languages_id . "'";
                                    $attributes = tep_db_query($attributes_query);
                                } else {
                                    $attributes = tep_db_query("SELECT popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix FROM " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa WHERE pa.products_id = '" . $order->products[$i]['id'] . "' AND pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' AND pa.options_id = popt.products_options_id AND pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "' AND pa.options_values_id = poval.products_options_values_id AND popt.language_id = '" . $languages_id . "' AND poval.language_id = '" . $languages_id . "'");
                                }
                                $attributes_values = tep_db_fetch_array($attributes);

                                $sql_data_array = array('orders_id' => $insert_id,
                                  'orders_products_id' => $order_products_id,
                                  'products_options' => $attributes_values['products_options_name'],
                                  'products_options_values' => $attributes_values['products_options_values_name'],
                                  'options_values_price' => $attributes_values['options_values_price'],
                                  'price_prefix' => $attributes_values['price_prefix']);

                                tep_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);

                                if ((DOWNLOAD_ENABLED == 'true') && isset($attributes_values['products_attributes_filename']) && tep_not_null($attributes_values['products_attributes_filename'])) {
                                    $sql_data_array = array('orders_id' => $insert_id,
                                      'orders_products_id' => $order_products_id,
                                      'orders_products_filename' => $attributes_values['products_attributes_filename'],
                                      'download_maxdays' => $attributes_values['products_attributes_maxdays'],
                                      'download_count' => $attributes_values['products_attributes_maxcount']);

                                    tep_db_perform(TABLE_ORDERS_PRODUCTS_DOWNLOAD, $sql_data_array);
                                }
                            }
                        }
                    }

                    $cart_Twispay_ID = $cartID . '-' . $insert_id;
                    tep_session_register('cart_Twispay_ID');
                }
            }

            return false;
        }

        /**
         * Remove an order based on order id.
         *
         * @param int order_id - The id of the order to be removed
         *
         * @return boolean - true / false - Operation success indicator
         *
         */
        public static function delete($order_id)
        {
            $check_query = tep_db_query('SELECT orders_id FROM ' . TABLE_ORDERS_STATUS_HISTORY . ' WHERE orders_id = "' . (int)$order_id . '" limit 1');
            if (tep_db_num_rows($check_query) < 1) {
                tep_db_query('DELETE FROM ' . TABLE_ORDERS . ' WHERE orders_id = "' . (int)$order_id . '"');
                tep_db_query('DELETE FROM ' . TABLE_ORDERS_TOTAL . ' WHERE orders_id = "' . (int)$order_id . '"');
                tep_db_query('DELETE FROM ' . TABLE_ORDERS_STATUS_HISTORY . ' WHERE orders_id = "' . (int)$order_id . '"');
                tep_db_query('DELETE FROM ' . TABLE_ORDERS_PRODUCTS . ' WHERE orders_id = "' . (int)$order_id . '"');
                tep_db_query('DELETE FROM ' . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . ' WHERE orders_id = "' . (int)$order_id . '"');
                tep_db_query('DELETE FROM ' . TABLE_ORDERS_PRODUCTS_DOWNLOAD . ' WHERE orders_id = "' . (int)$order_id . '"');
                return true;
            }
            return false;
        }

        /**
         * Remove all uncommited orders
         */
        public static function delete_unpaid()
        {
            tep_db_query("DELETE FROM " . TABLE_ORDERS . " WHERE `orders_status`='". MODULE_PAYMENT_TWISPAY_PREPARE_ORDER_STATUS_ID ."'");
            return tep_db_affected_rows();
        }

        /**
         * Commit the order - apply all settings for the order to be fully initialized
         *
         * @param int order_id: The order id.
         * @param int sendto: The order address id.
         * @param int billto: The order billing address id.
         * @param int transaction_id: The transaction id.
         *
         */
        public static function commit($order_id=0, $sendto=0, $billto=0, $transaction_id=0)
        {
            global $customer_id, $order, $order_totals, $languages_id, $payment, $currencies, $cart, $$payment;
            require_once(DIR_WS_CLASSES . 'language.php');
            $lang = new language($languages_id);
            include_once(DIR_WS_LANGUAGES . $lang->language['directory'] . '/modules/payment/twispay.php');
            require_once(DIR_WS_CLASSES . 'order.php');
            $order = new order($order_id);
            $order_totals = $order->totals;
            $customer_id = $order->customer['id'];

            /* FROM HERE */
            // initialized for the email confirmation
            $products_ordered = '';
            $subtotal = 0;
            $total_tax = 0;
            $n = count($order->products);
            for ($i = 0 ; $i < $n; $i++) {
                if (STOCK_LIMITED == 'true') {
                    if (DOWNLOAD_ENABLED == 'true') {
                        $stock_query_raw = "SELECT products_quantity, pad.products_attributes_filename
                                  FROM " . TABLE_PRODUCTS . " p
                                  LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                  ON p.products_id=pa.products_id
                                  LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                  ON pa.products_attributes_id=pad.products_attributes_id
                                  WHERE p.products_id = '" . tep_get_prid($order->products[$i]['id']) . "'";
                        // Will work with only one option for downloadable products
                        // otherwise, we have to build the query dynamically with a loop
                        $products_attributes = $order->products[$i]['attributes'];
                        if (is_array($products_attributes)) {
                            $stock_query_raw .= " AND pa.options_id = '" . $products_attributes[0]['option_id'] . "' AND pa.options_values_id = '" . $products_attributes[0]['value_id'] . "'";
                        }
                        $stock_query = tep_db_query($stock_query_raw);
                    } else {
                        $stock_query = tep_db_query("SELECT `products_quantity` FROM `" . TABLE_PRODUCTS . "` WHERE `products_id` = '" . tep_get_prid($order->products[$i]['id']) . "'");
                    }
                    if (tep_db_num_rows($stock_query) > 0) {
                        $stock_values = tep_db_fetch_array($stock_query);
                        // do not decrement quantities if products_attributes_filename exists
                        if ((DOWNLOAD_ENABLED != 'true') || (!$stock_values['products_attributes_filename'])) {
                            $stock_left = $stock_values['products_quantity'] - $order->products[$i]['qty'];
                        } else {
                            $stock_left = $stock_values['products_quantity'];
                        }
                        tep_db_query("UPDATE " . TABLE_PRODUCTS . " SET `products_quantity` = '" . $stock_left . "' WHERE `products_id` = '" . tep_get_prid($order->products[$i]['id']) . "'");
                        if (($stock_left < 1) && (STOCK_ALLOW_CHECKOUT == 'false')) {
                            tep_db_query("UPDATE " . TABLE_PRODUCTS . " SET `products_status` = '0' WHERE `products_id` = '" . tep_get_prid($order->products[$i]['id']) . "'");
                        }
                    }
                }

                // Update products_ordered (for bestsellers list)
                tep_db_query("UPDATE " . TABLE_PRODUCTS . " SET `products_ordered` = `products_ordered` + " . sprintf('%d', $order->products[$i]['qty']) . " WHERE `products_id` = '" . tep_get_prid($order->products[$i]['id']) . "'");

                //------insert customer choosen option to order--------
                $attributes_exist = '0';
                $products_ordered_attributes = '';
                if (isset($order->products[$i]['attributes'])) {
                    $attributes_exist = '1';
                    for ($j = 0, $n2 = sizeof($order->products[$i]['attributes']); $j < $n2; $j++) {
                        if (DOWNLOAD_ENABLED == 'true') {
                            $attributes_query = "SELECT popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pad.products_attributes_maxdays, pad.products_attributes_maxcount , pad.products_attributes_filename
                                     FROM " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                     LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                     ON pa.products_attributes_id=pad.products_attributes_id
                                     WHERE pa.products_id = '" . $order->products[$i]['id'] . "'
                                     AND pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "'
                                     AND pa.options_id = popt.products_options_id
                                     AND pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "'
                                     AND pa.options_values_id = poval.products_options_values_id
                                     AND popt.language_id = '" . $languages_id . "'
                                     AND poval.language_id = '" . $languages_id . "'";
                            $attributes = tep_db_query($attributes_query);
                        } else {
                            $attributes = tep_db_query("SELECT popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix FROM " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa WHERE pa.products_id = '" . $order->products[$i]['id'] . "' AND pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' AND pa.options_id = popt.products_options_id AND pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "' AND pa.options_values_id = poval.products_options_values_id AND popt.language_id = '" . $languages_id . "' AND poval.language_id = '" . $languages_id . "'");
                        }
                        $attributes_values = tep_db_fetch_array($attributes);

                        $products_ordered_attributes .= "\n\t" . $attributes_values['products_options_name'] . ' ' . $attributes_values['products_options_values_name'];
                    }
                }
                //------insert customer choosen option eof ----
                $total_weight += ($order->products[$i]['qty'] * $order->products[$i]['weight']);
                $total_tax += tep_calculate_tax($total_products_price, $products_tax) * $order->products[$i]['qty'];
                $total_cost += $total_products_price;

                $products_ordered .= $order->products[$i]['qty'] . ' x ' . $order->products[$i]['name'] . ' (' . $order->products[$i]['model'] . ') = ' . $currencies->display_price($order->products[$i]['final_price'], $order->products[$i]['tax'], $order->products[$i]['qty']) . $products_ordered_attributes . "\n";
            }

            // lets start with the email confirmation
            $email_order = STORE_NAME . "\n" .
              EMAIL_SEPARATOR . "\n" .
              EMAIL_TEXT_ORDER_NUMBER . ' ' . $order_id . "\n" .
              EMAIL_TEXT_INVOICE_URL . ' ' . tep_href_link(FILENAME_ACCOUNT_HISTORY_INFO, 'order_id=' . $order_id, 'SSL', false) . "\n" .
              EMAIL_TEXT_DATE_ORDERED . ' ' . strftime(DATE_FORMAT_LONG) . "\n\n";
            if ($order->info['comments']) {
                $email_order .= tep_db_output($order->info['comments']) . "\n\n";
            }
            $email_order .= EMAIL_TEXT_PRODUCTS . "\n" .
              EMAIL_SEPARATOR . "\n" .
              $products_ordered .
              EMAIL_SEPARATOR . "\n";

            for ($i = 0, $n = sizeof($order_totals); $i < $n; $i++) {
                $email_order .= strip_tags($order_totals[$i]['title']) . ' ' . strip_tags($order_totals[$i]['text']) . "\n";
            }

            if ($order->content_type != 'virtual') {
                $email_order .= "\n" . EMAIL_TEXT_DELIVERY_ADDRESS . "\n" .
                  EMAIL_SEPARATOR . "\n" .
                  tep_address_label($customer_id, $sendto, 0, '', "\n") . "\n";
            }

            $email_order .= "\n" . EMAIL_TEXT_BILLING_ADDRESS . "\n" .
              EMAIL_SEPARATOR . "\n" .
              tep_address_label($customer_id, $billto, 0, '', "\n") . "\n\n";

            if (is_object($$payment)) {
                $email_order .= EMAIL_TEXT_PAYMENT_METHOD . "\n" .
                            EMAIL_SEPARATOR . "\n";
                $payment_class = $$payment;
                $email_order .= $payment_class->title . "\n\n";
                if ($payment_class->email_footer) {
                    $email_order .= $payment_class->email_footer . "\n\n";
                }
            }

            tep_mail($order->customer['firstname'] . ' ' . $order->customer['lastname'], $order->customer['email_address'], EMAIL_TEXT_SUBJECT, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);

            // send emails to other people
            if (SEND_EXTRA_ORDER_EMAILS_TO != '') {
                tep_mail('', SEND_EXTRA_ORDER_EMAILS_TO, EMAIL_TEXT_SUBJECT, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
            }

            //// load the after_process function FROM the payment modules
            //   $this->after_process();
            //
            $cart->reset(true);

            // unregister session variables used during checkout
            tep_session_unregister('sendto');
            tep_session_unregister('billto');
            tep_session_unregister('shipping');
            tep_session_unregister('payment');
            tep_session_unregister('comments');

            tep_session_unregister('cart_Twispay_ID');
            return true;
        }

        /**
         * Update order status
         *
         * @param int oID: The order id.
         * @param int status: Status id. -1 to add a history registration with the same status
         * @param boolean allow_samestatus_overwrite: Flag that  represents the operation ability to accept the same status as previous one
         * @param boolean notify_customer: If the customers should be notified or not via email.
         * @param string notify_comments: The comment to be added to confirmation email body.
         *
         * @return boolean - true / false - Operation success indicator
         */
        public static function updateStatus($oID, $status, $allow_samestatus_overwrite, $comments, $notify_customer = 0, $notify_comments = 0)
        {
            $order_updated = false;
            $allow_overwrite = false;

            $check_status_query = tep_db_query("select customers_name, customers_email_address, orders_status, date_purchased from " . TABLE_ORDERS . " where orders_id = '" . (int)$oID . "'");
            $check_status = tep_db_fetch_array($check_status_query);
            if (!isset($check_status)) {
                return $order_updated;
            }
            /** If status is the same as the previous one return and $allow_samestatus_overwrite flag is true false*/
            if (!$allow_samestatus_overwrite && $status == $check_status['orders_status']) {
                return $order_updated;
            }
            /** If status value is -1 then keep the current status valie */
            if ($status == -1) {
                $status = $check_status['orders_status'];
            }

            tep_db_query("update " . TABLE_ORDERS . " set orders_status = '" . tep_db_input($status) . "', last_modified = now() where orders_id = '" . (int)$oID . "'");
            $customer_notified = '0';
            if ($notify_customer) {
                if ($notify_comments) {
                    $notify_comments = sprintf(EMAIL_TEXT_COMMENTS_UPDATE, $comments) . "\n\n";
                }
                $email = STORE_NAME . "\n" . EMAIL_SEPARATOR . "\n" . EMAIL_TEXT_ORDER_NUMBER . ' ' . $oID . "\n" . EMAIL_TEXT_INVOICE_URL . ' ' . tep_catalog_href_link(FILENAME_CATALOG_ACCOUNT_HISTORY_INFO, 'order_id=' . $oID, 'SSL') . "\n" . EMAIL_TEXT_DATE_ORDERED . ' ' . tep_date_long($check_status['date_purchased']) . "\n\n" . $notify_comments . sprintf(EMAIL_TEXT_STATUS_UPDATE, $orders_status_array[$status]);
                tep_mail($check_status['customers_name'], $check_status['customers_email_address'], EMAIL_TEXT_SUBJECT, $email, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
                $customer_notified = '1';
            }
            tep_db_query("insert into " . TABLE_ORDERS_STATUS_HISTORY . " (orders_id, orders_status_id, date_added, customer_notified, comments) values ('" . (int)$oID . "', '" . tep_db_input($status) . "', now(), '" . tep_db_input($customer_notified) . "', '" . tep_db_input($comments)  . "')");
            $order_updated = true;

            return $order_updated;
        }

        /**
         * Update order recurring status
         *
         * @param int oID: The order id.
         * @param string status: The status.
         *
         * @return object - The mysqli_result object
         */
        public static function updateRecurringStatus($oID, $status)
        {
            $query = tep_db_query("update " . TABLE_ORDERS . " set orders_custom_recurring_status = '" . tep_db_input($status) . "' where orders_id = '" . (int)$oID . "'");
            return $query;
        }

        /**
         * Get the order status
         *
         * @param int oID: The order id.
         *
         * @return int - The order status ID
         */
        public static function getStatus($oID)
        {
            $order_updated = false;
            $check_status = tep_db_fetch_array(tep_db_query("select orders_status from " . TABLE_ORDERS . " where orders_id = '" . (int)$oID . "'"));

            if (sizeof($check_status)) {
                return $check_status['orders_status'];
            } else {
                return false;
            }
        }

        /**
         * Get the all the order fields by order id
         *
         * @param int oID: The order id.
         * @param int languagesId: The selected language id.
         *
         * @return array - The order data | false - if no order found
         */
        public static function getOrderInfo($oID, $languagesId)
        {
            $order_info = tep_db_fetch_array(tep_db_query("select * from " . TABLE_ORDERS . " o, " . TABLE_ORDERS_STATUS . " s where o.orders_id = '". (int)$oID . "' and o.orders_status = s.orders_status_id and s.language_id = '" . (int)$languagesId . "' and s.public_flag = '1'"));

            if (sizeof($order_info)) {
                return $order_info;
            } else {
                return false;
            }
        }
    }
endif; /* End if class_exists. */
