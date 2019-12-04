<?php
/**
 * @author   Twispay
 * @version  1.0.1
 *
 * Controller that handels all the API actions available just from the catalog side
 */
chdir('../../../../');
require('includes/application_top.php');

/** Include language file */
require_once(DIR_WS_LANGUAGES.$language.'/modules/payment/twispay.php');
/** Load dependencies */
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Actions.php');

/** Check if action parameter is defined */
switch ($_POST['action']) {
    case 'cancel':
        /** Error body **/
        $data = ['status'   => ''
                ,'canceled' => 0
                ];
        /** Check if the orderid and tworderid parameter was sent */
        if (empty($_POST['tworderid']) || empty($_POST['orderid'])) {
            /** Print the error */
            $data['status'] = ACCESS_ERROR_TEXT;
            echo json_encode($data);
            die(MODULE_PAYMENT_TWISPAY_ERROR_UNDEFINED_ID_TEXT);
        }

        /** Check if the logged user has the same customer_id as the one who placed the order */
        require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Oscommerce_Order.php');
        $orderInfo = Oscommerce_Order::getOrderInfo($_POST['orderid'], $_SESSION['languages_id']);
        if (!$orderInfo || !tep_session_is_registered('customer_id') || $orderInfo['customers_id'] != $_SESSION['customer_id']) {
            /** Print the error */
            $data['status'] = MODULE_PAYMENT_TWISPAY_ACCESS_ERROR_TEXT;
            echo json_encode($data);
            Twispay_Logger::api_log(LOG_CANCEL_RESPONSE_TEXT.json_encode($data));
            die(MODULE_PAYMENT_TWISPAY_ACCESS_ERROR_TEXT);
        }

        /** Print the action respons */
        echo json_encode(Twispay_Actions::cancelSubscription($_POST['tworderid'], $_POST['orderid']));
    break;

    default:
        if (empty($_POST['action'])) {
          Twispay_Logger::api_log(ORDER_NO_ACTION_NOTICE_TEXT);
        }else{
          Twispay_Logger::api_log(ORDER_INVALID_ACTION_NOTICE_TEXT);
        }
    break;
}
