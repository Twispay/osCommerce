<?php
/**
 * @author   Twistpay
 * @version  1.0.1
 */
chdir('../../../../');
require('includes/application_top.php');
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Transactions.php');
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Oscommerce_Order.php');

/** Get the Private Key. */
switch($_POST['action']){
    /** If refund action is called via Ajax. */
    case 'refund':
        if(empty($_POST['transid'])){
            die("NO POST 1");
        }
        echo json_encode(Twispay_Transactions::refundTransaction($_POST['transid']));
        break;

    /** If clean action is called via Ajax. */
    case 'clean':
        echo json_encode(Oscommerce_Order::delete_unpaid());
        break;
}
?>
