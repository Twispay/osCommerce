<?php

/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2017 osCommerce

  Released under the GNU General Public License
*/


class twispay
{
    var $code, $title, $description, $enabled, $base_dir, $local_dir, $log_path, $log_file, $preparing_status_id, $complete_status_id, $refunded_status_id;

// class constructor
    function __construct()
    {
        global $order;

        $this->signature = 'twispay|twispay|1.1|1.1';
        $this->api_version = '1.1';
        $this->code = 'twispay';
        $this->title = MODULE_PAYMENT_TWISPAY_TEXT_TITLE;
        $this->public_title = MODULE_PAYMENT_TWISPAY_TEXT_PUBLIC_TITLE;
        $this->description = MODULE_PAYMENT_TWISPAY_TEXT_DESCRIPTION;
        $this->sort_order = MODULE_PAYMENT_TWISPAY_SORT_ORDER;
        $this->enabled = ((MODULE_PAYMENT_TWISPAY_STATUS == 'True') ? true : false);
        $this->local_dir = DIR_FS_CATALOG;
        $this->log_path = $this->local_dir.'ext/modules/payment/twispay/logs';
        if (getenv('HTTPS') == 'on') { // We are loading an SSL page
            $this->base_dir = HTTPS_SERVER;
        } else {
            $this->base_dir = HTTP_SERVER;
        }

        if ((int)MODULE_PAYMENT_TWISPAY_PREPARE_ORDER_STATUS_ID > 0) {
            $this->order_status = MODULE_PAYMENT_TWISPAY_PREPARE_ORDER_STATUS_ID;
        }

        if(MODULE_PAYMENT_TWISPAY_TESTMODE == "True") {
            $this->form_action_url = 'https://secure-stage.twispay.com';
            //$this->form_action_url = 'http://tw-dev.ml/test.php';
            if (($this->enabled === true) && (!tep_not_null(MODULE_PAYMENT_TWISPAY_STAGE_ID) || !tep_not_null(MODULE_PAYMENT_TWISPAY_STAGE_KEY))) {
                $this->description = '<div class="secWarning">' . MODULE_PAYMENT_TWISPAY_ERROR_STAGE . '</div>' . $this->description;
                $this->enabled = false;
                tep_db_query("UPDATE " . TABLE_CONFIGURATION . " SET `configuration_value`='False' WHERE `configuration_key`='MODULE_PAYMENT_TWISPAY_STATUS'");
            }
        } else {
            $this->form_action_url = 'https://secure.twispay.com';
            if (($this->enabled === true) && (!tep_not_null(MODULE_PAYMENT_TWISPAY_LIVE_ID) || !tep_not_null(MODULE_PAYMENT_TWISPAY_LIVE_KEY))) {
                $this->description = '<div class="secWarning">' . MODULE_PAYMENT_TWISPAY_ERROR_LIVE . '</div>' . $this->description;
                $this->enabled = false;
                tep_db_query("UPDATE " . TABLE_CONFIGURATION . " SET `configuration_value`='False' WHERE `configuration_key`='MODULE_PAYMENT_TWISPAY_STATUS'");
            }
        }

        if(strpos($_SERVER['SCRIPT_NAME'],'/modules.php') > -1){
            echo '<script type="text/javascript" src="'.$this->base_dir.'/ext/modules/payment/twispay/js/twispay.js"></script>';
            echo '<link rel="stylesheet" type="text/css" href="'.$this->base_dir.'/ext/modules/payment/twispay/css/twispay.css"/>';
        }


    }

// class methods
    function update_status()
    {
        global $order;

        if (($this->enabled == true) && ((int)MODULE_PAYMENT_TWISPAY_ZONE > 0)) {
            $check_flag = false;
            $check_query = tep_db_query("SELECT zone_id FROM " . TABLE_ZONES_TO_GEO_ZONES . " WHERE geo_zone_id = '" . MODULE_PAYMENT_TWISPAY_ZONE . "' AND zone_country_id = '" . $order->billing['country']['id'] . "' ORDER BY zone_id");
            while ($check = tep_db_fetch_array($check_query)) {
                if ($check['zone_id'] < 1) {
                    $check_flag = true;
                    break;
                } elseif ($check['zone_id'] == $order->billing['zone_id']) {
                    $check_flag = true;
                    break;
                }
            }

            if ($check_flag == false) {
                $this->enabled = false;
            }
        }
    }

    function javascript_validation()
    {
        return false;
    }



    function selection()
    {
        global $cart_Twispay_ID;

        if (tep_session_is_registered('cart_Twispay_ID')) {
            $order_id = substr($cart_Twispay_ID, strpos($cart_Twispay_ID, '-') + 1);

            $check_query = tep_db_query('SELECT orders_id FROM ' . TABLE_ORDERS_STATUS_HISTORY . ' WHERE orders_id = "' . (int)$order_id . '" limit 1');

            if (tep_db_num_rows($check_query) < 1) {
                tep_db_query('DELETE FROM ' . TABLE_ORDERS . ' WHERE orders_id = "' . (int)$order_id . '"');
                tep_db_query('DELETE FROM ' . TABLE_ORDERS_TOTAL . ' WHERE orders_id = "' . (int)$order_id . '"');
                tep_db_query('DELETE FROM ' . TABLE_ORDERS_STATUS_HISTORY . ' WHERE orders_id = "' . (int)$order_id . '"');
                tep_db_query('DELETE FROM ' . TABLE_ORDERS_PRODUCTS . ' WHERE orders_id = "' . (int)$order_id . '"');
                tep_db_query('DELETE FROM ' . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . ' WHERE orders_id = "' . (int)$order_id . '"');
                tep_db_query('DELETE FROM ' . TABLE_ORDERS_PRODUCTS_DOWNLOAD . ' WHERE orders_id = "' . (int)$order_id . '"');

                tep_session_unregister('cart_Twispay_ID');
            }
        }

        return array('id' => $this->code,
            'module' => $this->public_title);
    }

    function pre_confirmation_check()
    {
        global $cartID, $cart;

        if (empty($cart->cartID)) {
            $cartID = $cart->cartID = $cart->generate_cart_id();
        }

        if (!tep_session_is_registered('cartID')) {
            tep_session_register('cartID');
        }
    }

    function confirmation()
    {
        global $cartID, $cart_Twispay_ID, $customer_id, $languages_id, $order, $order_total_modules;

        if (tep_session_is_registered('cartID')) {
            $insert_order = false;

            if (tep_session_is_registered('cart_Twispay_ID')) {
                $order_id = substr($cart_Twispay_ID, strpos($cart_Twispay_ID, '-') + 1);

                $curr_check = tep_db_query("SELECT currency FROM " . TABLE_ORDERS . " WHERE orders_id = '" . (int)$order_id . "'");
                $curr = tep_db_fetch_array($curr_check);

                if (($curr['currency'] != $order->info['currency']) || ($cartID != substr($cart_Twispay_ID, 0, strlen($cartID)))) {
                    $check_query = tep_db_query('SELECT orders_id FROM ' . TABLE_ORDERS_STATUS_HISTORY . ' WHERE orders_id = "' . (int)$order_id . '" limit 1');

                    if (tep_db_num_rows($check_query) < 1) {
                        tep_db_query('DELETE FROM ' . TABLE_ORDERS . ' WHERE orders_id = "' . (int)$order_id . '"');
                        tep_db_query('DELETE FROM ' . TABLE_ORDERS_TOTAL . ' WHERE orders_id = "' . (int)$order_id . '"');
                        tep_db_query('DELETE FROM ' . TABLE_ORDERS_STATUS_HISTORY . ' WHERE orders_id = "' . (int)$order_id . '"');
                        tep_db_query('DELETE FROM ' . TABLE_ORDERS_PRODUCTS . ' WHERE orders_id = "' . (int)$order_id . '"');
                        tep_db_query('DELETE FROM ' . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . ' WHERE orders_id = "' . (int)$order_id . '"');
                        tep_db_query('DELETE FROM ' . TABLE_ORDERS_PRODUCTS_DOWNLOAD . ' WHERE orders_id = "' . (int)$order_id . '"');
                    }

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
                    'currency_value' => $order->info['currency_value']);

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
                /* HERE ADDING PREPARING ORDERS*/

//                $sql_data_array = array('orders_id' => $insert_id,
//                    'orders_status_id' => (MODULE_PAYMENT_TWISPAY_PREPARE_ORDER_STATUS_ID > 0 ? (int)MODULE_PAYMENT_TWISPAY_PREPARE_ORDER_STATUS_ID : (int)DEFAULT_ORDERS_STATUS_ID),
//                    'date_added' => 'now()',
////                    'customer_notified' => (SEND_EMAILS == 'true') ? '1' : '0',
//                    'comments' => $order->info['comments']);
//                tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
            }
        }

        return false;
    }

    function process_button()
    {
        global $customer_id, $order, $currencies, $currency, $languages_id, $cart_Twispay_ID,$sendto, $billto;
        tep_db_query("SET NAMES 'utf8'");
        $order_id = substr($cart_Twispay_ID, strpos($cart_Twispay_ID, '-') + 1);
        $testMode = MODULE_PAYMENT_TWISPAY_TESTMODE;

        $postfields = [];
        $arr_chk = $itm = $unit = $unitP = $subT = array();
        if($testMode == "True") {
            $postfields['siteId'] = MODULE_PAYMENT_TWISPAY_STAGE_ID;
            $privateKEY = MODULE_PAYMENT_TWISPAY_STAGE_KEY;
        } else {
            $postfields['siteId'] = MODULE_PAYMENT_TWISPAY_LIVE_ID;
            $privateKEY = MODULE_PAYMENT_TWISPAY_LIVE_KEY;
        }
        $postfields['identifier'] = '_' . $customer_id;
        $postfields['currency'] = $order->info['currency'];
        $total = round($order->info['total'],2);
        $postfields['amount'] = (string)$total;
        $postfields['backUrl'] = $this->base_dir.'/ext/modules/payment/twispay/twispay.php';

        $postfields['description'] = (empty(html_entity_decode($order->billing['company'], ENT_QUOTES, 'UTF-8'))) ? trim(ucwords(html_entity_decode($order->billing['firstname'], ENT_QUOTES, 'UTF-8') . ' ' . html_entity_decode($order->billing['lastname'], ENT_QUOTES, 'UTF-8'))) :  html_entity_decode($order->billing['company'], ENT_QUOTES, 'UTF-8');
        $postfields['orderType'] = 'purchase';
        $postfields['orderId'] = $order_id ;
        $postfields['orderId'] .= '_' . time();
        $postfields['firstName'] = html_entity_decode($order->billing['firstname'], ENT_QUOTES, 'UTF-8');
        $postfields['lastName'] = html_entity_decode($order->billing['lastname'], ENT_QUOTES, 'UTF-8');
        $postfields['country'] = html_entity_decode($order->billing['country']['iso_code_2'], ENT_QUOTES, 'UTF-8');
        $postfields['city'] = html_entity_decode($order->billing['city'], ENT_QUOTES, 'UTF-8');
        $postfields['zipCode'] = html_entity_decode($order->billing['postcode'], ENT_QUOTES, 'UTF-8');
        $postfields['address'] = html_entity_decode($order->billing['street_address'], ENT_QUOTES, 'UTF-8');
        $phone = preg_replace('/[^\d|+]+/', '',html_entity_decode($order->customer['telephone'], ENT_QUOTES, 'UTF-8'));
        $first = substr($phone,0,1);
        $subphone = substr($phone,1);
        $postfields['phone'] = $first . str_replace('+','',$subphone);
        $postfields['email'] = html_entity_decode($order->customer['email_address'], ENT_QUOTES, 'UTF-8');
        $postfields['custom[cartID]'] = $cart_Twispay_ID;
        $postfields['custom[sendTo]'] = $sendto;
        $postfields['custom[billTo]'] = $billto;
        //coupons
        $order_subtotal = $order->info['subtotal'];
        $i= 0;
        $subtotal = 0;
        $arr_chk = $item = $units = $unitPrice = $subTotal = array();

        foreach($order->products as $item){
            $postfields["item[$i]"] = str_replace(array('"','&quot;'),"''",stripslashes($item['name'])) . ', model: '. $item['model'];
            $postfields["units[$i]"] = (string)$item['qty'];
            $postfields['unitPrice[' . $i . ']'] = number_format($item['price'], 2);
            $postfields['subTotal[' . $i . ']'] = number_format($item['final_price'], 2);
            //$subtotal += number_format($item['total'], 2);
            ++$i;
        }

        if(DISPLAY_PRICE_WITH_TAX == "false"){
            if(!empty($order->info['tax'])){
                if (count($order->info["tax_groups"]) == 1 ){
                    foreach ($order->info["tax_groups"] as $key => $value) {
                        break;
                    }
                    $postfields['item[' . $i . ']'] = str_replace(array('"','&quot;'),"''",stripslashes($key)) . ':';
                } else {
                    $postfields['item[' . $i . ']'] = 'Taxes:';
                }
                $postfields['units[' . $i . ']'] = '1';
                $postfields['unitPrice[' . $i . ']'] = number_format($order->info['tax'], 2);
                $postfields['subTotal[' . $i . ']'] = number_format($order->info['tax'], 2);
                $subtotal += round($order->info['tax'],2);
                ++$i;
            }
        }

        if(!empty($order->info['shipping_cost'])){
            $postfields['item[' . $i . ']'] = 'Shipping: ' . $order->info['shipping_method'];
            $postfields['units[' . $i . ']'] = '1';
            $postfields['unitPrice[' . $i . ']'] = number_format($order->info['shipping_cost'], 2);
            $postfields['subTotal[' . $i . ']'] = number_format($order->info['shipping_cost'], 2);
            $subtotal += round($order->info['shipping_cost'],2);
            ++$i;
        }
        $sub_tax = $this->precision($order_subtotal,2)+$this->precision($subtotal,2);
        $dif = $this->precision($total,2) - $this->precision($sub_tax,2);
        switch((true)){
            case $dif>=0.02 :
                $postfields['item[' . $i . ']'] = 'Other charges:';
                $postfields['units[' . $i . ']'] = '1';
                $postfields['unitPrice[' . $i . ']'] = number_format($dif, 2);
                $postfields['subTotal[' . $i . ']'] = number_format($dif, 2);
                break;
            case $dif <= (-0.02) :
                $postfields['item[' . $i . ']'] = 'Discount:';
                $postfields['units[' . $i . ']'] = '1';
                $postfields['unitPrice[' . $i . ']'] = number_format($dif, 2);
                $postfields['subTotal[' . $i . ']'] = number_format($dif, 2);
                break;
            default:
                break;
        }


        $postfields['cardTransactionMode'] = 'authAndCapture';

        if(!empty($order->info['comments'])){
            $postfields['custom[comments]'] = $order->info['comments'];
        }

        $htmlOutput = '';
        foreach ($postfields as $k => $v) {
            if(preg_match('/\[[0-9\(\)]+\]/',$k,$result)){
                $newkey = str_replace($result[0],'',$k);
                $newindex = str_replace(array('[',']'),'',$result[0]);
                $postfields[$newkey][$newindex] = $v;
                unset($postfields[$k]);
            }
            $htmlOutput .= tep_draw_hidden_field($k, $v);
        }

        /* Checksum */

        /* This is needed to match checksum */
        if(!empty($order->info['comments'])){
            $postfields['comments'] = $order->info['comments'];
        }
        /* END This is needed to match checksum */

        ksort($postfields);
        $query = http_build_query($postfields);
        $encoded = hash_hmac('sha512', $query, $privateKEY, true);
        $checksum = base64_encode($encoded);
        $htmlOutput .= tep_draw_hidden_field('checksum', $checksum);
        //@file_put_contents(DIR_FS_DOWNLOAD.'form.txt',$htmlOutput);

        return $htmlOutput;
    }
    function precision($number,$precision){
        return $number = intval($number * ($p = pow(10, $precision))) / $p;
    }

    function recursiveKeySort(array &$data){
        ksort($data, SORT_STRING);
        foreach($data as $key => $value){
            if (is_array($value)){
                $this->recursiveKeySort($data[$key]);
            }
        }
    }
    function truncate($val, $f="0")
    {
        if(($p = strpos($val, '.')) !== false) {
            $val = floatval(substr($val, 0, $p + 1 + $f));
        }
        return $val;
    }


    function twispayDecrypt($encrypted)
    {
        $testMode = MODULE_PAYMENT_TWISPAY_TESTMODE;
        $apiKey = ($testMode == 'True') ? MODULE_PAYMENT_TWISPAY_STAGE_KEY : MODULE_PAYMENT_TWISPAY_LIVE_KEY;

        $encrypted = (string)$encrypted;
        if(!strlen($encrypted)) {
            return null;
        }
        if(strpos($encrypted, ',') !== false) {
            $encryptedParts = explode(',', $encrypted, 2);
            $iv = base64_decode($encryptedParts[0]);
            if(false === $iv) {
                throw new Exception("Invalid encryption iv");
            }
            $encrypted = base64_decode($encryptedParts[1]);
            if(false === $encrypted) {
                throw new Exception("Invalid encrypted data");
            }
            $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $apiKey, OPENSSL_RAW_DATA, $iv);
            if(false === $decrypted) {
                throw new Exception("Data could not be decrypted");
            }
            return $decrypted;
        }
        return null;
    }

    function twispay_log($string = false) {
        if(!$string) {
            $string = PHP_EOL.PHP_EOL;
        } else {
            $string = "[".date('Y-m-d H:i:s')."] ".$string;
        }
        @file_put_contents($this->log_file, PHP_EOL.$string.PHP_EOL, FILE_APPEND);
    }

    function makeDir($path)
    {
        return is_dir($path) || mkdir($path);
    }
    function delTree($dir)
    {
        $files = array_diff(scandir($dir), array('.', '..'));

        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
        }

        return rmdir($dir);
    }

    function redirect_page(){

        if(empty(trim(MODULE_PAYMENT_TWISPAY_PAGE_REDIRECT))){
            $page_to_redirect = FILENAME_CHECKOUT_SUCCESS;
        } else {
            $page_to_redirect = trim(MODULE_PAYMENT_TWISPAY_PAGE_REDIRECT);
            if(stripos($page_to_redirect,'/') !==0){
                $page_to_redirect = '/'. $page_to_redirect;
            }
            $page_to_redirect = $this->base_dir. $page_to_redirect;
            stream_context_set_default(
                array(
                    'http' => array(
                        'method' => 'HEAD'
                    )
                )
            );
            $headers = @get_headers($page_to_redirect);
            $status = substr($headers[0], 9, 3);
            if (!($status >= 200 && $status < 400 )) {
                $page_to_redirect = FILENAME_CHECKOUT_SUCCESS;
            }

        }
        return tep_href_link($page_to_redirect, '', 'SSL');
    }

    function getResultStatuses() {
        return array("complete-ok");
    }
    function prn($string=''){
        if (ob_get_level() == 0)
            ob_start();
        for ($i = 0; $i<10; $i++)
        {
            echo '<!-- bufferme -->';
            echo str_pad('',4096*2)."\n";
            ob_flush();
            flush();
        }
        echo $string;
    }
    function tl($string=''){
        return $string;
    }

    function checkValidation($json){
        global $order,$cart;
        $_errors = array();
        $wrong_status = array();
        if(!in_array($json->status, $this->getResultStatuses())) {
            $_errors[] = sprintf($this->tl('[RESPONSE-ERROR] Wrong status (%s)'), $json->status);
        }
        if(empty($json->externalOrderId)) {
            $_errors[] = 'Empty externalOrderId';
        } else {
            $order_id = explode('_',$json->externalOrderId);
            $order_id = $order_id[0];
            $order_info = tep_db_query("SELECT * FROM `" . TABLE_ORDERS . "` WHERE `orders_id`='" . $order_id . "'" );
            if(empty(tep_db_num_rows($order_info))){
                $_errors[] = sprintf('Order #%s not found',$order_id);
            }
        }
        if(empty($json->transactionStatus)) {
            $_errors[] = 'Empty status';
        }
        if(empty($json->amount)) {
            $_errors[] = 'Empty amount';
        }
        if(empty($json->currency)) {
            $_errors[] = 'Empty currency';
        }
        if(empty($json->identifier)) {
            $_errors[] = 'Empty identifier';
        }
        if(empty($json->orderId)) {
            $_errors[] = 'Empty orderId';
        }
        if(empty($json->transactionId)) {
            $_errors[] = 'Empty transactionId';
        }
        if(empty($json->transactionMethod)) {
            $_errors[] = 'Empty transactionMethod';
        }

        if(sizeof($_errors)) {
            foreach($_errors as $err) {
                $this->twispay_log('[RESPONSE-ERRORS] '.$err);
                $this->twispay_log();
            }
            $this->twispay_log('err '.json_encode($_errors));
            $this->twispay_log();
            return false;
        } else {
            $comms = (!empty($json->custom->comments)) ? $json->custom->comments : '';
            $data = array(
                'invoice' => '',
                'order_id' => $order_id,
                'status' => $json->transactionStatus,
                'amount' => (float)$json->amount,
                'currency' => $json->currency,
                'identifier' => $json->identifier,
                'orderId' => (int)$json->orderId,
                'transactionId' => (int)$json->transactionId,
                'customerId' => (int)$json->customerId,
                'transactionKind' => $json->transactionMethod,
                'cardId' => (!empty($json->cardId)) ? (int)$json->cardId : 0,
                'timestamp' => (is_object($json->timestamp)) ? time() : $json->timestamp,
                'sendto'    => (int)$json->custom->sendTo,
                'billto'    => (int)$json->custom->billTo,
                'comments'  => $comms,
            );
            $this->twispay_log('[RESPONSE] Data: '.json_encode($data));
            if(!in_array($data['status'], $this->getResultStatuses())) {
                $wrong_status['status'] = $data['status'];

                $this->twispay_log(sprintf($this->tl('[RESPONSE-ERROR] Wrong status (%s)'), json_encode($wrong_status)));
                $this->twispay_log();

                return false;
            }
            $this->twispay_log('[RESPONSE] Status complete-ok');

        }
        return json_decode(json_encode($data));

    }
    function checkTransaction($transaction=0){
        $transaction = tep_db_query("SELECT 1 FROM `twispay_transactions` WHERE `transactionId`='" . $transaction . "'");
        return tep_db_num_rows($transaction);
    }

    function loggTransaction($data) {
        $data =json_decode(json_encode($data),TRUE);

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
            'sendto',
            'billto'
        );
        if(!empty($data['timestamp'])) {
            $data['date'] = date('Y-m-d H:i:s', $data['timestamp']);
            unset($data['timestamp']);
        }
        if(!empty($data['identifier'])) {
            $data['identifier'] = (int)str_replace('_', '', $data['identifier']);
        }
        $query = "INSERT INTO `twispay_transactions` SET ";
        foreach($data as $key => $value) {
            if(!in_array($key, $columns)) {
                unset($data[$key]);
            } else {
                $query .= $key."="."'" . $value. "',";
            }
        }
        $query = rtrim($query,',');
        tep_db_query($query);

        return $query;
    }


    function reset_cart(){
        global $cart;
        $cart->reset(true);
    }

    function success_process($order_id=0,$sendto=0,$billto=0,$transaction=0, $comms='')
    {
        global $customer_id, $order, $order_totals, $languages_id, $payment, $currencies, $cart, $cart_Twispay_ID;

        require_once(DIR_WS_CLASSES . 'order.php');
        $order = new order($order_id);
        $order_totals = $order->totals;
        $customer_id = $order->customer['id'];
        require_once(DIR_WS_CLASSES . 'language.php');
        $lang = new language($languages_id);
        include_once(DIR_WS_LANGUAGES . $lang->language['directory'] . '/modules/payment/twispay.php');

        tep_db_query("UPDATE " . TABLE_ORDERS . " SET `orders_status` = '" . (MODULE_PAYMENT_TWISPAY_ORDER_STATUS_ID > 0 ? (int)MODULE_PAYMENT_TWISPAY_ORDER_STATUS_ID : (int)DEFAULT_ORDERS_STATUS_ID) . "', `last_modified` = now() WHERE `orders_id` = '" . (int)$order_id . "'");
        $comments = (!empty($transaction)) ? 'Transaction id #'.$transaction."\r\n" : '';
        $comments .= $comms;
        $sql_data_array = array('orders_id' => $order_id,
            'orders_status_id' => (MODULE_PAYMENT_TWISPAY_ORDER_STATUS_ID > 0 ? (int)MODULE_PAYMENT_TWISPAY_ORDER_STATUS_ID : (int)DEFAULT_ORDERS_STATUS_ID),
            'date_added' => 'now()',
            'customer_notified' => (SEND_EMAILS == 'true') ? '1' : '0',
            'comments' => $comments);

        tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);


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
            MODULE_PAYMENT_TWISPAY_EMAIL_SEPARATOR . "\n" .
            MODULE_PAYMENT_TWISPAY_EMAIL_TEXT_ORDER_NUMBER . ' ' . $order_id . "\n" .
            MODULE_PAYMENT_TWISPAY_EMAIL_TEXT_INVOICE_URL . ' ' . tep_href_link(FILENAME_ACCOUNT_HISTORY_INFO, 'order_id=' . $order_id, 'SSL', false) . "\n" .
            MODULE_PAYMENT_TWISPAY_EMAIL_TEXT_DATE_ORDERED . ' ' . strftime(DATE_FORMAT_LONG) . "\n\n";
        if ($order->info['comments']) {
            $email_order .= tep_db_output($order->info['comments']) . "\n\n";
        }
        $email_order .= MODULE_PAYMENT_TWISPAY_EMAIL_TEXT_PRODUCTS . "\n" .
            MODULE_PAYMENT_TWISPAY_EMAIL_SEPARATOR . "\n" .
            $products_ordered .
            MODULE_PAYMENT_TWISPAY_EMAIL_SEPARATOR . "\n";

        for ($i = 0, $n = sizeof($order_totals); $i < $n; $i++) {
            $email_order .= strip_tags($order_totals[$i]['title']) . ' ' . strip_tags($order_totals[$i]['text']) . "\n";
        }

        if ($order->content_type != 'virtual') {
            $email_order .= "\n" . MODULE_PAYMENT_TWISPAY_EMAIL_TEXT_DELIVERY_ADDRESS . "\n" .
                MODULE_PAYMENT_TWISPAY_EMAIL_SEPARATOR . "\n" .
                tep_address_label($customer_id, $sendto, 0, '', "\n") . "\n";
        }

        $email_order .= "\n" . MODULE_PAYMENT_TWISPAY_EMAIL_TEXT_BILLING_ADDRESS . "\n" .
            MODULE_PAYMENT_TWISPAY_EMAIL_SEPARATOR . "\n" .
            tep_address_label($customer_id, $billto, 0, '', "\n") . "\n\n";

        //if (is_object(${$payment})) {
        $email_order .= MODULE_PAYMENT_TWISPAY_EMAIL_TEXT_PAYMENT_METHOD . "\n" .
            MODULE_PAYMENT_TWISPAY_EMAIL_SEPARATOR . "\n";
        $email_order .= MODULE_PAYMENT_TWISPAY_TEXT_TITLE . "\n\n";
        if ($this->email_footer) {
            $email_order .= $this->email_footer . "\n\n";
        }
        //}

        tep_mail($order->customer['firstname'] . ' ' . $order->customer['lastname'], $order->customer['email_address'], MODULE_PAYMENT_TWISPAY_EMAIL_TEXT_SUBJECT, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);

// send emails to other people
        if (SEND_EXTRA_ORDER_EMAILS_TO != '') {
            tep_mail('', SEND_EXTRA_ORDER_EMAILS_TO, MODULE_PAYMENT_TWISPAY_EMAIL_TEXT_SUBJECT, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
        }

//// load the after_process function FROM the payment modules
//        $this->after_process();
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

    /* REFUND */

    function refund($transid = 0, $order_id = 0, $customer_id = 0, $sendto = 0, $billto = 0)
    {

        if(empty($transid) || empty($order_id) || empty($customer_id) || empty($sendto) || empty($billto)){
            echo json_encode("NO DATA");
        }

        if(defined(MODULE_PAYMENT_TWISPAY_TESTMODE) && MODULE_PAYMENT_TWISPAY_TESTMODE == 'True'){
            $url = 'https://api-stage.twispay.com/transaction/' . $transid;
            $apiKey = MODULE_PAYMENT_TWISPAY_STAGE_KEY;
        } else {
            $url = 'https://api.twispay.com/transaction/' . $transid;
            $apiKey = MODULE_PAYMENT_TWISPAY_LIVE_KEY;
        }

        $ch = curl_init();

        curl_setopt( $ch, CURLOPT_HTTPHEADER, array( "Authorization: Bearer " . $apiKey, "Accept: application/json" ) );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "DELETE" );
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

        $contents = curl_exec( $ch );

        curl_close( $ch );

        $json = json_decode($contents );
        $this->twispay_log('[TRANSACTION ID #] '.$transid);
        $this->twispay_log('[REFUND RESPONSE] '.$contents);
        $this->twispay_log();


        if($json->message == 'Success' ) {
            require_once(DIR_WS_CLASSES . 'order.php');
            $order = new order($order_id);
            global $languages_id, $payment, $currencies;
            $payment = 'Refunded [Twispay]';
            $order_totals = $order->totals;
            require_once(DIR_WS_CLASSES . 'language.php');
            $lang = new language($languages_id);
            include_once(DIR_WS_LANGUAGES . $lang->language['directory'] . '/modules/payment/twispay.php');

            tep_db_query("UPDATE " . TABLE_ORDERS . " SET `orders_status` = '" . (MODULE_PAYMENT_TWISPAY_REFUND_ORDER_STATUS_ID > 0 ? (int)MODULE_PAYMENT_TWISPAY_REFUND_ORDER_STATUS_ID : (int)DEFAULT_ORDERS_STATUS_ID) . "', `last_modified` = now() WHERE `orders_id` = '" . (int)$order_id . "'");

            $sql_data_array = array('orders_id' => $order_id,
                'orders_status_id' => (MODULE_PAYMENT_TWISPAY_REFUND_ORDER_STATUS_ID > 0 ? (int)MODULE_PAYMENT_TWISPAY_REFUND_ORDER_STATUS_ID : (int)DEFAULT_ORDERS_STATUS_ID),
                'date_added' => 'now()',
                'customer_notified' => (SEND_EMAILS == 'true') ? '1' : '0',
                'comments' => $order->info['comments']);

            tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);


// initialized for the email confirmation
            $products_ordered = '';
            $subtotal = 0;
            $total_tax = 0;
            $n = count($order->products);
            for ($i = 0; $i < $n; $i++) {
                //if (STOCK_LIMITED == 'true') {
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
                if (tep_db_num_rows($stock_query) >= 0) {
                    $stock_values = tep_db_fetch_array($stock_query);
// do not decrement quantities if products_attributes_filename exists
                    if ((DOWNLOAD_ENABLED != 'true') || (!$stock_values['products_attributes_filename'])) {
                        $stock_left = $stock_values['products_quantity'] + $order->products[$i]['qty'];
                    } else {
                        $stock_left = $stock_values['products_quantity'];
                    }
                    tep_db_query("UPDATE " . TABLE_PRODUCTS . " SET `products_quantity` = '" . $stock_left . "' WHERE `products_id` = '" . tep_get_prid($order->products[$i]['id']) . "'");

                }
                // }

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
                MODULE_PAYMENT_TWISPAY_EMAIL_SEPARATOR . "\n" .
                MODULE_PAYMENT_TWISPAY_EMAIL_TEXT_ORDER_NUMBER . ' ' . $order_id . "\n" .
                MODULE_PAYMENT_TWISPAY_EMAIL_TEXT_INVOICE_URL . ' ' . tep_href_link(FILENAME_ACCOUNT_HISTORY_INFO, 'order_id=' . $order_id, 'SSL', false) . "\n" .
                MODULE_PAYMENT_TWISPAY_EMAIL_TEXT_DATE_ORDERED . ' ' . strftime(DATE_FORMAT_LONG) . "\n\n";
            if ($order->info['comments']) {
                $email_order .= tep_db_output($order->info['comments']) . "\n\n";
            }
            $email_order .= MODULE_PAYMENT_TWISPAY_EMAIL_TEXT_PRODUCTS . "\n" .
                MODULE_PAYMENT_TWISPAY_EMAIL_SEPARATOR . "\n" .
                $products_ordered .
                MODULE_PAYMENT_TWISPAY_EMAIL_SEPARATOR . "\n";

            for ($i = 0, $n = sizeof($order_totals); $i < $n; $i++) {
                $email_order .= strip_tags($order_totals[$i]['title']) . ' ' . strip_tags($order_totals[$i]['text']) . "\n";
            }

            if ($order->content_type != 'virtual') {
                $email_order .= "\n" . MODULE_PAYMENT_TWISPAY_EMAIL_TEXT_DELIVERY_ADDRESS . "\n" .
                    MODULE_PAYMENT_TWISPAY_EMAIL_SEPARATOR . "\n" .
                    tep_address_label($customer_id, $sendto, 0, '', "\n") . "\n";
            }

            $email_order .= "\n" . MODULE_PAYMENT_TWISPAY_EMAIL_TEXT_BILLING_ADDRESS . "\n" .
                MODULE_PAYMENT_TWISPAY_EMAIL_SEPARATOR . "\n" .
                tep_address_label($customer_id, $billto, 0, '', "\n") . "\n\n";

            //if (is_object(${$payment})) {
            $email_order .= MODULE_PAYMENT_TWISPAY_EMAIL_TEXT_PAYMENT_METHOD . "\n" .
                MODULE_PAYMENT_TWISPAY_EMAIL_SEPARATOR . "\n";
            $email_order .= $payment . "\n\n";
            if ($this->email_footer) {
                $email_order .= $this->email_footer . "\n\n";
            }
            //}

            tep_mail($order->customer['firstname'] . ' ' . $order->customer['lastname'], $order->customer['email_address'], MODULE_PAYMENT_TWISPAY_EMAIL_TEXT_SUBJECT, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);

// send emails to other people
            if (SEND_EXTRA_ORDER_EMAILS_TO != '') {
                tep_mail('', SEND_EXTRA_ORDER_EMAILS_TO, MODULE_PAYMENT_TWISPAY_EMAIL_TEXT_SUBJECT . ' - REFUND', $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
            }

//// load the after_process function FROM the payment modules
//        $this->after_process();
//
//        $cart->reset(true);

// unregister session variables used during checkout
            tep_session_unregister('sendto');
            tep_session_unregister('billto');
            tep_session_unregister('shipping');
            tep_session_unregister('payment');
            tep_session_unregister('comments');

            tep_session_unregister('cart_Twispay_ID');
            $query = "UPDATE `twispay_transactions` SET `status`='refunded',`refund_date`='" . date('Y-m-d H:i:s'). "' WHERE `transactionId`='" . $transid. "' AND `status`!='refunded'";
            tep_db_query($query);
            if(tep_db_affected_rows() == 1){
                return json_encode('OK');
            } else {
                return json_encode("Refund successfull,\nCan not update transaction table !!!");
            }

        } // refunded success
        else {
            return json_encode($json->error[0]->message);
        }
    }
    function delete_unpaid(){

        tep_db_query("DELETE FROM " . TABLE_ORDERS . " WHERE `orders_status`='". MODULE_PAYMENT_TWISPAY_PREPARE_ORDER_STATUS_ID ."'" );
        return json_encode(tep_db_affected_rows());
    }





    function before_process()
    {
        return false;
    }
    function after_process()
    {
        return false;
    }

    function output_error()
    {
        return false;
    }

    function check()
    {
        if (!isset($this->_check)) {
            $check_query = tep_db_query("SELECT configuration_value FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'MODULE_PAYMENT_TWISPAY_STATUS'");
            $this->_check = tep_db_num_rows($check_query);
        }
        return $this->_check;
    }

    function checktable(){
        $sql = "
            CREATE TABLE IF NOT EXISTS `twispay_transactions` (
                `id_transaction` int(11) NOT NULL AUTO_INCREMENT,
                `status` varchar(16) NOT NULL,
                `invoice` varchar(30) NOT NULL,
                `order_id` int(11) NOT NULL,
                `identifier` int(11) NOT NULL,
                `customerId` int(11) NOT NULL,
                `orderId` int(11) NOT NULL,
                `cardId` int(11) NOT NULL,
                `transactionId` int(11) NOT NULL,
                `transactionKind` varchar(16) NOT NULL,
                `amount` float NOT NULL,
                `currency` varchar(8) NOT NULL,
                `date` DATETIME NOT NULL,
                `refund_date` DATETIME NOT NULL,
                `sendto` INT NOT NULL,
                 `billto` INT NOT NULL, 
                PRIMARY KEY (`id_transaction`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
        tep_db_query($sql);
    }
    function twispay_statuses(){
        $check_query = tep_db_query("SELECT orders_status_id FROM " . TABLE_ORDERS_STATUS . " WHERE orders_status_name = 'Preparing [Twispay]' limit 1");
        if (tep_db_num_rows($check_query) < 1) {
            $status_query = tep_db_query("SELECT max(orders_status_id) as status_id FROM " . TABLE_ORDERS_STATUS);
            $status = tep_db_fetch_array($status_query);

            $this->preparing_status_id = $status['status_id'] + 1;

            $languages = tep_get_languages();

            for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
                tep_db_query("INSERT INTO " . TABLE_ORDERS_STATUS . " (orders_status_id, language_id, orders_status_name) VALUES ('" . $this->preparing_status_id . "', '" . $languages[$i]['id'] . "', 'Preparing [Twispay]')");
            }

            $flags_query = tep_db_query("DESCRIBE " . TABLE_ORDERS_STATUS . " public_flag");
            if (tep_db_num_rows($flags_query) == 1) {
                tep_db_query("UPDATE " . TABLE_ORDERS_STATUS . " SET `public_flag` = '1',`downloads_flag` = '1' WHERE `orders_status_id` = '" . $this->preparing_status_id . "'");
            }
        } else {
            $check = tep_db_fetch_array($check_query);

            $this->preparing_status_id = $check['orders_status_id'];
        }

        $check_query = tep_db_query("SELECT orders_status_id FROM " . TABLE_ORDERS_STATUS . " WHERE orders_status_name = 'Paid [Twispay]' limit 1");
        if (tep_db_num_rows($check_query) < 1) {
            $status_query = tep_db_query("SELECT max(orders_status_id) as status_id FROM " . TABLE_ORDERS_STATUS);
            $status = tep_db_fetch_array($status_query);

            $this->complete_status_id = $status['status_id'] + 1;

            $languages = tep_get_languages();

            for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
                tep_db_query("INSERT INTO " . TABLE_ORDERS_STATUS . " (orders_status_id, language_id, orders_status_name) VALUES ('" . $this->complete_status_id . "', '" . $languages[$i]['id'] . "', 'Paid [Twispay]')");
            }

            $flags_query = tep_db_query("DESCRIBE " . TABLE_ORDERS_STATUS . " public_flag");
            if (tep_db_num_rows($flags_query) == 1) {
                tep_db_query("UPDATE " . TABLE_ORDERS_STATUS . " SET `public_flag` = '1', `downloads_flag` = '1' WHERE `orders_status_id` = '" . $this->complete_status_id . "'");
            }
        } else {
            $check = tep_db_fetch_array($check_query);

            $this->complete_status_id = $check['orders_status_id'];
        }


        $check_query = tep_db_query("SELECT orders_status_id FROM " . TABLE_ORDERS_STATUS . " WHERE orders_status_name = 'Refunded [Twispay]' limit 1");
        if (tep_db_num_rows($check_query) < 1) {
            $status_query = tep_db_query("SELECT max(orders_status_id) as status_id FROM " . TABLE_ORDERS_STATUS);
            $status = tep_db_fetch_array($status_query);

            $this->refunded_status_id = $status['status_id'] + 1;

            $languages = tep_get_languages();

            for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
                tep_db_query("INSERT INTO " . TABLE_ORDERS_STATUS . " (orders_status_id, language_id, orders_status_name) VALUES ('" . $this->refunded_status_id . "', '" . $languages[$i]['id'] . "', 'Refunded [Twispay]')");
            }

            $flags_query = tep_db_query("DESCRIBE " . TABLE_ORDERS_STATUS . " public_flag");

            if (tep_db_num_rows($flags_query) == 1) {
                tep_db_query("UPDATE " . TABLE_ORDERS_STATUS . " SET `public_flag` = '1', `downloads_flag` = '1' WHERE `orders_status_id` = '" . $this->refunded_status_id . "'");
            }
        } else {
            $check = tep_db_fetch_array($check_query);

            $this->refunded_status_id = $check['orders_status_id'];
        }


    }
    function install()
    {
        $this->checktable();
        $this->makeDir($this->log_path);
        $this->twispay_statuses();
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('Enable Twispay', 'MODULE_PAYMENT_TWISPAY_STATUS', 'False', 'Do you want to enable Twispay payments?', '6', '3', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('Test Mode', 'MODULE_PAYMENT_TWISPAY_TESTMODE', 'True', 'Do you want to enable test mode?', '6', '3', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Stage Site ID', 'MODULE_PAYMENT_TWISPAY_STAGE_ID', '', 'Twispay ID for testing', '6', '4', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Stage Site KEY', 'MODULE_PAYMENT_TWISPAY_STAGE_KEY', '', 'Twispay private KEY for testing', '6', '4', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Live Site ID', 'MODULE_PAYMENT_TWISPAY_LIVE_ID', '', 'Twispay ID for live site', '6', '4', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Live Site KEY', 'MODULE_PAYMENT_TWISPAY_LIVE_KEY', '', 'Twispay private KEY for live site', '6', '4', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Custom redirect page', 'MODULE_PAYMENT_TWISPAY_PAGE_REDIRECT', '', 'Leave empty for default', '6', '4', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Server 2 server notification', 'MODULE_PAYMENT_TWISPAY_S2S', '$this->base_dir/ext/modules/payment/twispay/twispay_s2s.php', 'Put this link to Twispay site:', '6', '4', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) VALUES ('Payment Zone', 'MODULE_PAYMENT_TWISPAY_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'tep_get_zone_class_title', 'tep_cfg_pull_down_zone_classes(', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) VALUES ('Set Preparing Order Status', 'MODULE_PAYMENT_TWISPAY_PREPARE_ORDER_STATUS_ID', '" . $this->preparing_status_id . "', 'Set the status of completed orders made with this payment module to this value', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) VALUES ('Set Completed Order Status', 'MODULE_PAYMENT_TWISPAY_ORDER_STATUS_ID', '" . $this->complete_status_id . "', 'Set the status of completed orders made with this payment module to this value', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) VALUES ('Set Refund Order Status', 'MODULE_PAYMENT_TWISPAY_REFUND_ORDER_STATUS_ID', '" . $this->refunded_status_id . "', 'Set the status of refunded orders made with this payment module to this value', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Transactions on page', 'MODULE_PAYMENT_TWISPAY_PAGINATION', '20', 'Set number of records on the page', '6', '4', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Sort order of display.', 'MODULE_PAYMENT_TWISPAY_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");


    }

    function remove()
    {
        $this->delTree($this->log_path);
        tep_db_query("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key in ('" . implode("', '", $this->keys()) . "')");
        tep_db_query("DROP TABLE IF EXISTS `twispay_transactions`");
        tep_db_query("DELETE FROM " . TABLE_ORDERS_STATUS . " WHERE orders_status_name IN ('Preparing [Twispay]','Paid [Twispay]','Refunded [Twispay]') " );
    }

    function keys()
    {
        return array('MODULE_PAYMENT_TWISPAY_STATUS', 'MODULE_PAYMENT_TWISPAY_TESTMODE', 'MODULE_PAYMENT_TWISPAY_STAGE_ID', 'MODULE_PAYMENT_TWISPAY_STAGE_KEY', 'MODULE_PAYMENT_TWISPAY_LIVE_ID', 'MODULE_PAYMENT_TWISPAY_LIVE_KEY', 'MODULE_PAYMENT_TWISPAY_PAGE_REDIRECT', 'MODULE_PAYMENT_TWISPAY_S2S', 'MODULE_PAYMENT_TWISPAY_ZONE','MODULE_PAYMENT_TWISPAY_PREPARE_ORDER_STATUS_ID', 'MODULE_PAYMENT_TWISPAY_ORDER_STATUS_ID', 'MODULE_PAYMENT_TWISPAY_REFUND_ORDER_STATUS_ID', 'MODULE_PAYMENT_TWISPAY_PAGINATION' , 'MODULE_PAYMENT_TWISPAY_SORT_ORDER' );
    }

}