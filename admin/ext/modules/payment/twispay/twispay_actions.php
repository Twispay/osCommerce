<?php
/**
 * @author   Twispay
 * @version  1.0.1
 */

chdir('../../../../');
require('includes/application_top.php');
require('../includes/languages/' . $language . '/modules/payment/twispay.php');

require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Transactions.php');
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Oscommerce_Order.php');

/** Check if action parameter is defined */
switch($_POST['action']){
    /** If refund action is called via Ajax. */
    case 'refund':
        /** Check if the transid parameter was sent */
        if(empty($_POST['transid'])){
            die("Undefined transaction id for refund operation");
        }
        /** Call refundTransaction method from Twispay_Transactions helper and print the response */
        echo json_encode(refundTransaction($_POST['transid'],floatval($_POST['amount'])));
        break;

    /** If clean action is called via Ajax. */
    case 'clean':
        /** Call delete_unpaid method from Oscommerce_Order helper and print the response */
        echo json_encode(sprintf(MODULE_PAYMENT_TWISPAY_CLEAN_SUCCESS_TEXT,Oscommerce_Order::delete_unpaid()));
        break;
}

/**
 * Function that calls the refund operation via Twispay API and update the local order based on the response.
 *
 * @param array trans_id - Twispay transaction id
 *
 * @return array([key => value]) - string 'status'   - Operation message
 *                                 string 'message'  - Success message translation
 *                                 string 'rawdata'  - Unprocessed response
 *                                 string 'transactionId' - The twispay id of the refunded transaction
 *                                 string 'externalOrderId' - The twispay id of the refunded order
 *                                 boolean 'refunded'- Operation success indicator
 *
 */
function refundTransaction($trans_id,$amount)
{
    require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Logger.php');
    require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Status_Updater.php');
    require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Transactions.php');
    $transaction = Twispay_Transactions::getTransaction($trans_id);
    $transaction['amount'] = floatval($transaction['amount']);

    /** Get the Private Key. */
    if (defined("MODULE_PAYMENT_TWISPAY_TESTMODE") &&  MODULE_PAYMENT_TWISPAY_TESTMODE == "True") {
        $url = 'https://api-stage.twispay.com/transaction/' . $transaction['transactionId'];
        $secretKey = MODULE_PAYMENT_TWISPAY_STAGE_KEY;
    } else {
        $url = 'https://api.twispay.com/transaction/' . $transaction['transactionId'];
        $secretKey = MODULE_PAYMENT_TWISPAY_LIVE_KEY;
    }
    $postAmount = isset($amount)?$amount:$transaction['amount'];
    $postData = 'amount=' . $postAmount . '&' . 'message=' . 'Refund for order ' . $transaction['orderId'];

    /** Create a new cURL session. */
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Authorization: ' . $secretKey]);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    $response = curl_exec($ch);
    curl_close($ch);
    $json = json_decode($response);

    /** Check if curl/decode fails */
    if (!isset($json)) {
        $json = new stdClass();
        $json->message = JSON_DECODE_ERROR_TEXT;
        Twispay_Logger::api_log(JSON_DECODE_ERROR_TEXT);
    }

    if (($json->code == 200) && ($json->message == 'Success')) {
        $data = ['status'          => ''
                ,'message'         => MODULE_PAYMENT_TWISPAY_REFUND_SUCCESS_TEXT
                ,'rawdata'         => $json
                ,'transactionId'   => $trans_id
                ,'externalOrderId' => $transaction['order_id']
                ,'refunded'        => 1
                ];

        /** Update current transaction */
        if(Twispay_Transactions::addTransactionRefundedAmount($trans_id,$postAmount) < $transaction['amount']){
          $data['status'] = Twispay_Status_Updater::$RESULT_STATUSES['PARTIAL_REFUNDED'];
          Twispay_Transactions::updateTransactionStatus($trans_id, Twispay_Status_Updater::$RESULT_STATUSES['PARTIAL_REFUNDED']);
        }else{
          $data['status'] = Twispay_Status_Updater::$RESULT_STATUSES['TOTAL_REFUNDED'];
          Twispay_Transactions::updateTransactionStatus($trans_id, Twispay_Status_Updater::$RESULT_STATUSES['TOTAL_REFUNDED']);
        }

        Twispay_Status_Updater::updateStatus_IPN($data);
    } else {
        $data = ['status'          => isset($json->error)?$json->error[0]->message:$json->message
                ,'rawdata'         => $json
                ,'transactionId'   => $trans_id
                ,'externalOrderId' => $transaction['order_id']
                ,'refunded'        => 0
                ];
        $data['status'].=MODULE_PAYMENT_TWISPAY_REFUND_ERROR_TEXT;
    }
    Twispay_Logger::api_log(LOG_REFUND_RESPONSE_TEXT.json_encode($data));
    return $data;
}
?>
