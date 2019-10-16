<?php
/**
 * @author   Twistpay
 * @version  1.0.1
 */

require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Encoder.php');
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Logger.php');
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Transactions.php');
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Notification.php');
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Status_Updater.php');
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Oscommerce_Order.php');

class twispay
{
    var $code, $title, $description, $enabled, $base_dir;

    function __construct()
    {
      global $order;
      $this->enabled = ((MODULE_PAYMENT_TWISPAY_STATUS == 'True') ? true : false);
      if ( $this->enabled === true ) {
        if ( isset($order) && is_object($order) ) {
          $this->update_status();
        }
      }
        $this->signature = 'twispay|twispay|1.0|1.0';
        $this->api_version = '1.0';
        $this->code = 'twispay';
        $this->title = MODULE_PAYMENT_TWISPAY_TEXT_TITLE;
        $this->public_title = MODULE_PAYMENT_TWISPAY_TEXT_PUBLIC_TITLE;
        $this->description = MODULE_PAYMENT_TWISPAY_TEXT_DESCRIPTION;
        $this->description = '<a href="http://www.twispay.com" target="_blank" style="text-decoration: none;"><img src="images/twispay_logo.png" border="0" title="'.MODULE_PAYMENT_TWISPAY_IMAGE_TITLE_TEXT.'"></a>';
        if(defined('MODULE_PAYMENT_TWISPAY_STATUS')){
            $this->description .= '<br/><a class="twispay-logs" href="ext/modules/payment/twispay/">'.MODULE_PAYMENT_TWISPAY_TRANSACTIONS_BUTTON_TEXT.'</a>';
            $this->description .= '<br/><a class="twispay-clean" href="javascript:clean();">'.MODULE_PAYMENT_TWISPAY_CLEAR_BUTTON_TEXT.'</a>';
        }
        $this->sort_order = MODULE_PAYMENT_TWISPAY_SORT_ORDER;

        if (getenv('HTTPS') == 'on') { // We are loading an SSL page
            $this->base_dir = HTTPS_CATALOG_SERVER.DIR_WS_HTTPS_CATALOG;
        } else {
            $this->base_dir = HTTP_CATALOG_SERVER.DIR_WS_CATALOG;
        }

        if ((int)MODULE_PAYMENT_TWISPAY_PREPARE_ORDER_STATUS_ID > 0) {
            $this->order_status = MODULE_PAYMENT_TWISPAY_PREPARE_ORDER_STATUS_ID;
        }

        if(MODULE_PAYMENT_TWISPAY_TESTMODE == "True") {
            $this->form_action_url = 'https://secure-stage.twispay.com';
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

    function javascript_validation()
    {
        return false;
    }

    /** Selection page */
    function selection()
    {
        global $cart_Twispay_ID;

        if (tep_session_is_registered('cart_Twispay_ID')) {
            $order_id = substr($cart_Twispay_ID, strpos($cart_Twispay_ID, '-') + 1);
            if(Oscommerce_Order::delete($order_id)){
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

    function confirmation(){
      return Oscommerce_Order::create();
    }

    /** Helper function - decode html strings */
    function htmlEntityDecodeUTF8($val){
      return html_entity_decode($val, ENT_QUOTES, 'UTF-8');
    }

    /** Function that loads the message that needs to be sent to the server via ajax. */
    function process_button()
    {
        global $customer_id, $order, $currencies, $currency, $languages_id, $cart_Twispay_ID, $sendto, $billto;
        tep_db_query("SET NAMES 'utf8'");
        $order_id = substr($cart_Twispay_ID, strpos($cart_Twispay_ID, '-') + 1);
        $testMode = MODULE_PAYMENT_TWISPAY_TESTMODE;

        $postfields = [];
        $arr_chk = $itm = $unit = $unitP = $subT = array();
        if($testMode == "True") {
            $siteID = MODULE_PAYMENT_TWISPAY_STAGE_ID;
            $secretKey = MODULE_PAYMENT_TWISPAY_STAGE_KEY;
        } else {
            $siteID = MODULE_PAYMENT_TWISPAY_LIVE_ID;
            $secretKey = MODULE_PAYMENT_TWISPAY_LIVE_KEY;
        }

        $telephone = $this->htmlEntityDecodeUTF8($order->customer['telephone']);
        $customer = [ 'identifier' => '_' . $customer_id . '_' . date('YmdHis')
                    , 'firstName' => $this->htmlEntityDecodeUTF8($order->billing['firstname'])
                    , 'lastName' => $this->htmlEntityDecodeUTF8($order->billing['lastname'])
                    , 'country' => $this->htmlEntityDecodeUTF8($order->billing['country']['iso_code_2'])
                    , 'city' => $this->htmlEntityDecodeUTF8($order->billing['city'])
                    , 'zipCode' => $this->htmlEntityDecodeUTF8($order->billing['postcode'])
                    , 'address' => $this->htmlEntityDecodeUTF8($order->billing['street_address'])
                    , 'phone' => ((strlen($telephone) && $telephone[0] == '+') ? ('+') : ('')) . preg_replace('/([^0-9]*)+/', '', $telephone)
                    , 'email' => $this->htmlEntityDecodeUTF8($order->customer['email_address'])
                    ];

        foreach($order->products as $item){
            $items[] = [ 'item' => str_replace(array('"','&quot;'),"''",stripslashes($item['name'])) . ', model: '. $item['model']
                       , 'units' =>  (string)$item['qty']
                       , 'unitPrice' => number_format($item['price'], 2)
                       ];
        }

        /* Calculate the backUrl through which the server will provide the status of the order. */
        $backUrl = tep_href_link('ext/modules/payment/twispay/twispay.php', '', 'SSL');

        /* Build the data object to be posted to Twispay. */
        $orderData = [ 'siteId' => $siteID
                     , 'customer' => $customer
                     , 'order' => [ 'orderId' => $order_id
                                  , 'type' => 'purchase'
                                  , 'amount' =>  (string)round($order->info['total'],2)
                                  , 'currency' => $order->info['currency']
                                  , 'items' => $items
                                  ]
                     , 'cardTransactionMode' => 'authAndCapture'
                     /* , 'cardId' => 0 */
                     , 'invoiceEmail' => ''
                     , 'backUrl' => $backUrl
                     , 'customData' => [ 'sendTo' => $sendto
                                       , 'billTo' => $billto
                                       , 'comments' => $order->info['comments']
                                       ]
                     ];

        $base64JsonRequest = Twispay_Encoder::getBase64JsonRequest($orderData);
        $base64Checksum = Twispay_Encoder::getBase64Checksum($orderData, $secretKey);

        $htmlOutput = "<form action='".$this->hostName."' method='POST' accept-charset='UTF-8' id='twispay_payment_form'>
            <input type='hidden' name='jsonRequest' value='".$base64JsonRequest."'>
            <input type='hidden' name='checksum' value='".$base64Checksum."'>
        </form>";

        return $htmlOutput;
    }

    /** Function that checks if module is enabled
    *
    * @return boolean
    *
    */
    function check()
    {
        if (!isset($this->_check)) {
            $check_query = tep_db_query("SELECT configuration_value FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'MODULE_PAYMENT_TWISPAY_STATUS'");
            $this->_check = tep_db_num_rows($check_query);
        }
        return $this->_check;
    }

    /** Function that creates a new status for orders
    *
    * @param string - Status name
    *
    * @return int - Status id
    *
    */
    function create_status($name){
      $check_query = tep_db_query("SELECT orders_status_id FROM " . TABLE_ORDERS_STATUS . " WHERE orders_status_name = '".$name."' limit 1");
      if (tep_db_num_rows($check_query) < 1) {
          $status_query = tep_db_query("SELECT max(orders_status_id) as status_id FROM " . TABLE_ORDERS_STATUS);
          $status = tep_db_fetch_array($status_query);
          $status_id = $status['status_id'] + 1;
          $languages = tep_get_languages();
          for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
              tep_db_query("INSERT INTO " . TABLE_ORDERS_STATUS . " (orders_status_id, language_id, orders_status_name) VALUES ('" . $status_id . "', '" . $languages[$i]['id'] . "', '".$name."')");
          }
          $flags_query = tep_db_query("DESCRIBE " . TABLE_ORDERS_STATUS . " public_flag");
          if (tep_db_num_rows($flags_query) == 1) {
              tep_db_query("UPDATE " . TABLE_ORDERS_STATUS . " SET `public_flag` = '1',`downloads_flag` = '1' WHERE `orders_status_id` = '" . $status_id . "'");
          }
      } else {
          $check = tep_db_fetch_array($check_query);
          $status_id = $check['orders_status_id'];
      }
      return $status_id;
    }

    /** Register custom statuses for order */
    function twispay_statuses(){
        $this->canceled_status_id = $this->create_status("Canceled [Twispay]");
        $this->voided_status_id = $this->create_status("Voided [Twispay]");
        $this->chargedback_status_id = $this->create_status("Chargedback [Twispay]");
        $this->refunded_status_id = $this->create_status("Refunded [Twispay]");
        $this->failed_status_id = $this->create_status("Failed [Twispay]");

        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) VALUES ('Set Canceled Order Status', 'MODULE_PAYMENT_TWISPAY_CANCELED_ORDER_STATUS_ID', '" . $this->canceled_status_id . "', 'Set the status of canceled orders made with this payment module to this value', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) VALUES ('Set Voided Order Status', 'MODULE_PAYMENT_TWISPAY_VOIDED_ORDER_STATUS_ID', '" . $this->voided_status_id . "', 'Set the status of voided orders made with this payment module to this value', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) VALUES ('Set Chargedback Order Status', 'MODULE_PAYMENT_TWISPAY_CHARGEDBACK_ORDER_STATUS_ID', '" . $this->chargedback_status_id . "', 'Set the status of chargedback orders made with this payment module to this value', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) VALUES ('Set Refunded Order Status', 'MODULE_PAYMENT_TWISPAY_REFUNDED_ORDER_STATUS_ID', '" . $this->refunded_status_id . "', 'Set the status of refunded orders made with this payment module to this value', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) VALUES ('Set Failed Order Status', 'MODULE_PAYMENT_TWISPAY_FAILED_ORDER_STATUS_ID', '" . $this->failed_status_id . "', 'Set the status of failed orders made with this payment module to this value', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
    }

    /** Module install function */
    function install()
    {
        Twispay_Transactions::createTransactionsTable();
        Twispay_Logger::makeLogDir();
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('Enable Twispay', 'MODULE_PAYMENT_TWISPAY_STATUS', 'False', 'Do you want to enable Twispay payments?', '6', '4', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('Test Mode', 'MODULE_PAYMENT_TWISPAY_TESTMODE', 'True', 'Do you want to enable test mode?', '6', '4', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Stage Site ID', 'MODULE_PAYMENT_TWISPAY_STAGE_ID', '', 'Twispay ID for testing', '6', '4', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Stage Site KEY', 'MODULE_PAYMENT_TWISPAY_STAGE_KEY', '', 'Twispay private KEY for testing', '6', '4', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Live Site ID', 'MODULE_PAYMENT_TWISPAY_LIVE_ID', '', 'Twispay ID for live site', '6', '4', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Live Site KEY', 'MODULE_PAYMENT_TWISPAY_LIVE_KEY', '', 'Twispay private KEY for live site', '6', '4', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Custom redirect page', 'MODULE_PAYMENT_TWISPAY_PAGE_REDIRECT', '', 'Leave empty for default', '6', '4', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Server 2 server notification', 'MODULE_PAYMENT_TWISPAY_S2S', '$this->base_dir/ext/modules/payment/twispay/twispay_s2s.php', 'Put this link to Twispay site:', '6', '4', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) VALUES ('Payment Zone', 'MODULE_PAYMENT_TWISPAY_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '4', 'tep_get_zone_class_title', 'tep_cfg_pull_down_zone_classes(', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Contact email', 'MODULE_PAYMENT_TWISPAY_EMAIL', '', 'Set the contact email', '6', '4', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Transactions on page', 'MODULE_PAYMENT_TWISPAY_PAGINATION', '20', 'Set number of records on the page', '6', '4', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('Sort order of display.', 'MODULE_PAYMENT_TWISPAY_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
        $this->twispay_statuses();
    }

    /** Module uninstall function */
    function remove()
    {
        tep_db_query("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key in ('" . implode("', '", $this->keys()) . "')");
        tep_db_query("DELETE FROM " . TABLE_ORDERS_STATUS . " WHERE orders_status_name IN ('Canceled [Twispay]','Voided [Twispay]','Chargedback [Twispay]','Refunded [Twispay]','Failed [Twispay]') " );
        Twispay_Transactions::dropTransactionsTable();
        Twispay_Logger::delLogDir();
        Twispay_Logger::makeLogDir();
    }

    /** Register custom constants */
    function keys()
    {
        return array('MODULE_PAYMENT_TWISPAY_STATUS', 'MODULE_PAYMENT_TWISPAY_TESTMODE', 'MODULE_PAYMENT_TWISPAY_STAGE_ID', 'MODULE_PAYMENT_TWISPAY_STAGE_KEY', 'MODULE_PAYMENT_TWISPAY_LIVE_ID', 'MODULE_PAYMENT_TWISPAY_LIVE_KEY', 'MODULE_PAYMENT_TWISPAY_PAGE_REDIRECT', 'MODULE_PAYMENT_TWISPAY_S2S', 'MODULE_PAYMENT_TWISPAY_ZONE' , 'MODULE_PAYMENT_TWISPAY_EMAIL' , 'MODULE_PAYMENT_TWISPAY_PAGINATION' , 'MODULE_PAYMENT_TWISPAY_SORT_ORDER', 'MODULE_PAYMENT_TWISPAY_CANCELED_ORDER_STATUS_ID', 'MODULE_PAYMENT_TWISPAY_VOIDED_ORDER_STATUS_ID', 'MODULE_PAYMENT_TWISPAY_CHARGEDBACK_ORDER_STATUS_ID','MODULE_PAYMENT_TWISPAY_REFUNDED_ORDER_STATUS_ID','MODULE_PAYMENT_TWISPAY_FAILED_ORDER_STATUS_ID' );
    }
}
