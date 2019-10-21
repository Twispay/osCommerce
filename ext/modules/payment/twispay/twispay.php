<?php
/**
* @author   Twistpay
* @version  1.0.1
*/
chdir('../../../../');
require('includes/application_top.php');

require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Encoder.php');
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Logger.php');
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Notification.php');
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Response.php');
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Transactions.php');
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Status_Updater.php');
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Thankyou.php');
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Oscommerce_Order.php');

global $language;
require('includes/languages/' . $language . '/modules/payment/twispay.php');

/** Get the Private Key. */
if (defined("MODULE_PAYMENT_TWISPAY_TESTMODE") &&  MODULE_PAYMENT_TWISPAY_TESTMODE == "True") {
    $secretKey = MODULE_PAYMENT_TWISPAY_STAGE_KEY;
} else {
    $secretKey = MODULE_PAYMENT_TWISPAY_LIVE_KEY;
}

/** Check if there is NO secret key. */
if ('' == $secretKey) {
    Twispay_Logger::log(LOG_ERROR_INVALID_PRIVATE_TEXT);
    Twispay_Notification::print_notice();
    die();
}

if(!empty($_POST)){
    echo PROCESSING_TEXT;
    sleep(1);

    /** Check if the POST is corrupted: Doesn't contain the 'opensslResult' and the 'result' fields. */
    if (((false == isset($_POST['opensslResult'])) && (false == isset($_POST['result'])))) {
        Twispay_Logger::log(LOG_ERROR_EMPTY_RESPONSE_TEXT);
        Twispay_Notification::print_notice();
        die();
    }

    /** Extract the server response and decrypt it. */
    $decrypted = Twispay_Response::decryptMessage(/**tw_encryptedResponse*/(isset($_POST['opensslResult'])) ? ($_POST['opensslResult']) : ($_POST['result']), $secretKey);

    /** Check if decryption failed.  */
    if (false === $decrypted) {
        Twispay_Logger::log(LOG_ERROR_DECRYPTION_ERROR_TEXT);
        Twispay_Notification::print_notice();
        die();
    } else {
        Twispay_Logger::log(LOG_OK_STRING_DECRYPTED_TEXT. json_encode($decrypted));
    }

    /** Check if transaction already exist */
    if (Twispay_Transactions::checkTransaction($decrypted['transactionId'])) {
        Twispay_Logger::log(LOG_ERROR_TRANSACTION_EXIST_TEXT . $decrypted['transactionId']);
        global $cart;
        $cart->reset(true);
        Twispay_Thankyou::redirect(MODULE_PAYMENT_TWISPAY_PAGE_REDIRECT);
        die();
    }

    /** Validate the decripted response. */
    $orderValidation = Twispay_Response::checkValidation($decrypted);
    if (TRUE !== $orderValidation) {
        Twispay_Logger::log(LOG_ERROR_VALIDATING_FAILED_TEXT);
        Twispay_Notification::print_notice();
        die();
    }

    /** Extract the order. */
    $order_id = $decrypted['externalOrderId'];
    $db_order_id = tep_db_input($order_id);
    $order_query = tep_db_query("SELECT * FROM `" . TABLE_ORDERS . "` WHERE `orders_id`='" . $db_order_id . "'" );

    /*** Check if the order extraction failed. */
    if (empty(tep_db_num_rows($order_query))) {
        Twispay_Logger::log(LOG_ERROR_INVALID_ORDER_TEXT);
        Twispay_Notification::print_notice();
        die();
    }

    /** Extract the status received from server. */
    Oscommerce_Order::commit($order_id, $decrypted['custom']['sendTo'], $decrypted['custom']['billTo']);
    Twispay_Status_Updater::updateStatus_backUrl($decrypted);

} else {
    Twispay_Logger::log(NO_POST_TEXT);
    Twispay_Notification::print_notice(NO_POST_TEXT);
    die();
}
