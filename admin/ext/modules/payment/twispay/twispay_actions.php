<?php
/**
 * @author   Twispay
 * @version  1.0.1
 *
 * Controller that handels all the API actions available just from the admin side
 */
chdir('../../../../');
require('includes/application_top.php');

/** Include language file */
require('../'.DIR_WS_LANGUAGES.$language.'/modules/payment/twispay.php');
/** Load dependencies */
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Oscommerce_Order.php');
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Actions.php');

/** Check if action parameter is defined */
switch ($_POST['action']) {
    /** If refund action is called via Ajax. */
    case 'refund':
        /** Check if the transid parameter was sent */
        if (empty($_POST['transid'])) {
            $data = ['status'   => MODULE_PAYMENT_TWISPAY_ERROR_UNDEFINED_ID_TEXT
                    ,'refunded' => 0
                    ];
            echo json_encode($data);
            Twispay_Logger::api_log(LOG_REFUND_RESPONSE_TEXT.json_encode($data));
            die(MODULE_PAYMENT_TWISPAY_ERROR_UNDEFINED_ID_TEXT);
        }
        echo json_encode(Twispay_Actions::refundTransaction($_POST['transid'], floatval($_POST['amount'])));
    break;

    /** If refund action is called via Ajax. */
    case 'cancel':
        /** Check if the orderid and tworderid parameter was sent */
        if (empty($_POST['orderid']) || empty($_POST['tworderid'])) {
            $data = ['status'   => MODULE_PAYMENT_TWISPAY_ERROR_UNDEFINED_ID_TEXT
                    ,'canceled' => 0
                    ];
            echo json_encode($data);
            Twispay_Logger::api_log(LOG_CANCEL_RESPONSE_TEXT.json_encode($data));
            die(MODULE_PAYMENT_TWISPAY_ERROR_UNDEFINED_ID_TEXT);
        }
        echo json_encode(Twispay_Actions::cancelSubscription($_POST['tworderid'], $_POST['orderid'], 'Manual', 1));
    break;

    /** If sync action is called via Ajax. */
    case 'sync':
        echo json_encode(Twispay_Actions::syncSubscriptions());
    break;

    /** If clean action is called via Ajax. */
    case 'clean':
        /** Call delete_unpaid method from Oscommerce_Order helper and print the response */
        echo json_encode(sprintf(MODULE_PAYMENT_TWISPAY_CLEAN_SUCCESS_TEXT, Oscommerce_Order::delete_unpaid()));
    break;
}
