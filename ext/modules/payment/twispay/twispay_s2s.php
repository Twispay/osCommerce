<?php
/**
 * @author   Twispay
 * @version  1.0.1
 *
 * Controller that provides mechanisms to process the IPN REQUESTS
 */

chdir('../../../../');
require('includes/application_top.php');

/** Include language file */
require_once(DIR_WS_LANGUAGES.$language.'/modules/payment/twispay.php');
/** Load dependencies */
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Oscommerce_Order.php');
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Logger.php');
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Response.php');
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Transactions.php');
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Status_Updater.php');

/** Get the Private Key. */
if (defined("MODULE_PAYMENT_TWISPAY_TESTMODE") &&  MODULE_PAYMENT_TWISPAY_TESTMODE == "True") {
    $secretKey = MODULE_PAYMENT_TWISPAY_STAGE_KEY;
} else {
    $secretKey = MODULE_PAYMENT_TWISPAY_LIVE_KEY;
}

/** Check if there is NO secret key. */
if ('' == $secretKey) {
    Twispay_Logger::log(LOG_ERROR_INVALID_PRIVATE_TEXT);
    die(LOG_ERROR_INVALID_PRIVATE_TEXT);
}

if (!empty($_POST)) {
    /** Check if the POST is corrupted: Doesn't contain the 'opensslResult' and the 'result' fields. */
    if (((false == isset($_POST['opensslResult'])) && (false == isset($_POST['result'])))) {
        Twispay_Logger::log(LOG_ERROR_EMPTY_RESPONSE_TEXT);
        die(LOG_ERROR_EMPTY_RESPONSE_TEXT);
    }

    /** Extract the server response and decrypt it. */
    $decrypted = Twispay_Response::decryptMessage(/**tw_encryptedResponse*/(isset($_POST['opensslResult'])) ? ($_POST['opensslResult']) : ($_POST['result']), $secretKey);

    /** Check if decryption failed.  */
    if (false === $decrypted) {
        Twispay_Logger::log(LOG_ERROR_DECRYPTION_ERROR_TEXT);
        die(LOG_ERROR_DECRYPTION_ERROR_TEXT);
    } else {
        Twispay_Logger::log(LOG_OK_STRING_DECRYPTED_TEXT. json_encode($decrypted));
    }

    /** Check if transaction already exist */
    if (Twispay_Transactions::checkTransaction($decrypted['transactionId']) != false) {
        Twispay_Logger::log(LOG_ERROR_TRANSACTION_EXIST_TEXT . $decrypted['transactionId']);
        die("OK");
    }

    /** Validate the decrypted response. */
    $orderValidation = Twispay_Response::checkValidation($decrypted);
    if (false == $orderValidation) {
        Twispay_Logger::log(LOG_ERROR_VALIDATING_FAILED_TEXT);
        die(LOG_ERROR_VALIDATING_FAILED_TEXT);
    }

    /** Extract the order. */
    $order_id = $decrypted['externalOrderId'];
    $db_order_id = tep_db_input($order_id);
    $order_query = tep_db_query("SELECT * FROM `" . TABLE_ORDERS . "` WHERE `orders_id`='" . $db_order_id . "'");
    /** Check if the order extraction failed. */
    if (empty(tep_db_num_rows($order_query))) {
        Twispay_Logger::log(LOG_ERROR_INVALID_ORDER_TEXT);
        Twispay_Notification::notice_to_cart();
        die(LOG_ERROR_INVALID_ORDER_TEXT);
    }

    /** Extract the status received from server. */
    Oscommerce_Order::commit($order_id, $decrypted['orderId'], $decrypted['custom']['sendTo'], $decrypted['custom']['billTo']);

    $status = Twispay_Status_Updater::updateStatus_IPN($decrypted);
    $orderValidation['completed'] = $status['success'];

    /** Register transaction */
    Twispay_Transactions::insertTransaction($orderValidation);

    /** If transaction succeded */
    if ($status['success']) {
        die("OK");
    } else {
        die("Internal processing failure");
    }
} else {
    Twispay_Logger::log(NO_POST_TEXT);
    die(NO_POST_TEXT);
}
