<?php
/**
 * @author   Twispay
 * @version  1.0.1
 *
 * Controller that provides mechanisms to process the BACKURL REQUESTS
 */

chdir('../../../../');
require('includes/application_top.php');

/** Include language file */
require_once(DIR_WS_LANGUAGES.$language.'/modules/payment/twispay.php');
/** Load dependencies */
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Oscommerce_Order.php');
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Logger.php');
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Notification.php');
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Response.php');
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Transactions.php');
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Status_Updater.php');
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Thankyou.php');

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

if (!empty($_POST)) {
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
    $transactionCheck = Twispay_Transactions::checkTransaction($decrypted['transactionId']);
    if ($transactionCheck != false) {
        Twispay_Logger::log(LOG_ERROR_TRANSACTION_EXIST_TEXT . $decrypted['transactionId']);
        /** If transaction was completed redirect cu success page */
        if ($transactionCheck['completed'] == 1) {
            Twispay_Thankyou::redirect(MODULE_PAYMENT_TWISPAY_PAGE_REDIRECT);
        } else {
            /** If transaction failed show notice */
            Twispay_Notification::print_notice();
        }
        die();
    }

    /** Validate the decrypted response. */
    $orderValidation = Twispay_Response::checkValidation($decrypted);
    if (false == $orderValidation) {
        Twispay_Logger::log(LOG_ERROR_VALIDATING_FAILED_TEXT);
        Twispay_Notification::print_notice();
        die();
    }

    /** Extract the order. */
    $order_id = $decrypted['externalOrderId'];
    $order_query = tep_db_query("SELECT * FROM `" . TABLE_ORDERS . "` WHERE `orders_id`='" . tep_db_input($order_id) . "'");

    /*** Check if the order extraction failed. */
    if (empty(tep_db_num_rows($order_query))) {
        Twispay_Logger::log(LOG_ERROR_INVALID_ORDER_TEXT);
        Twispay_Notification::print_notice();
        die();
    }

    /** Extract the status received from server. */
    Oscommerce_Order::commit($order_id, $decrypted['custom']['sendTo'], $decrypted['custom']['billTo']);

    $status = Twispay_Status_Updater::updateStatus_backUrl($decrypted);
    /** Success state */
    $orderValidation['completed'] = $status['success'];

    /** Register transaction */
    Twispay_Transactions::insertTransaction($orderValidation);

    /** If transaction succeded redirect to success page */
    if ($status['success']) {
        Twispay_Thankyou::redirect(MODULE_PAYMENT_TWISPAY_PAGE_REDIRECT);
    } else {
        /** If transaction fails redirect show notice and the error message */
        Twispay_Notification::print_notice($status['message']);
    }
} else {
    Twispay_Logger::log(NO_POST_TEXT);
    Twispay_Notification::print_notice(NO_POST_TEXT);
    die();
}
