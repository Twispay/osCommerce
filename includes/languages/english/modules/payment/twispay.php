<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2007 osCommerce

  Released under the GNU General Public License
*/
$description = '<a href="http://www.twispay.com" target="_blank" style="text-decoration: none;"><img src="images/twispay_logo.png" border="0" title="Visit Twispay Website"></a>';
if(defined('MODULE_PAYMENT_TWISPAY_STATUS')){
    $description .= '<br/><a class="twispay-logs" href="ext/modules/payment/twispay/">Transactions Log</a><br/>';
//    <a class="twispay-clean" href="javascript:clean();">Delete unfinished orders</a>';
}
define('MODULE_PAYMENT_TWISPAY_TEXT_TITLE', 'Secure Credit Card Payment by Twispay');
define('MODULE_PAYMENT_TWISPAY_TEXT_PUBLIC_TITLE', 'Secure Credit Card Payment by Twispay');
define('MODULE_PAYMENT_TWISPAY_TEXT_DESCRIPTION', $description);
define('MODULE_PAYMENT_TWISPAY_ERROR_STAGE', 'Configuration for TESTING is not complete, module will not load. Please edit ID and KEY for stage site.');
define('MODULE_PAYMENT_TWISPAY_ERROR_LIVE', 'Configuration for LIVE SITE is not complete, module will not load. Please edit ID and KEY for live site.');

define('MODULE_PAYMENT_TWISPAY_EMAIL_TEXT_SUBJECT', 'Order Process');
define('MODULE_PAYMENT_TWISPAY_EMAIL_TEXT_ORDER_NUMBER', 'Order Number:');
define('MODULE_PAYMENT_TWISPAY_EMAIL_TEXT_INVOICE_URL', 'Detailed Invoice:');
define('MODULE_PAYMENT_TWISPAY_EMAIL_TEXT_DATE_ORDERED', 'Date Ordered:');
define('MODULE_PAYMENT_TWISPAY_EMAIL_TEXT_PRODUCTS', 'Products');
define('MODULE_PAYMENT_TWISPAY_EMAIL_TEXT_SUBTOTAL', 'Sub-Total:');
define('MODULE_PAYMENT_TWISPAY_EMAIL_TEXT_TAX', 'Tax:        ');
define('MODULE_PAYMENT_TWISPAY_EMAIL_TEXT_SHIPPING', 'Shipping: ');
define('MODULE_PAYMENT_TWISPAY_EMAIL_TEXT_TOTAL', 'Total:    ');
define('MODULE_PAYMENT_TWISPAY_EMAIL_TEXT_DELIVERY_ADDRESS', 'Delivery Address');
define('MODULE_PAYMENT_TWISPAY_EMAIL_TEXT_BILLING_ADDRESS', 'Billing Address');
define('MODULE_PAYMENT_TWISPAY_EMAIL_TEXT_PAYMENT_METHOD', 'Payment Method');

define('MODULE_PAYMENT_TWISPAY_EMAIL_SEPARATOR', '------------------------------------------------------');
define('TEXT_EMAIL_VIA', 'via');

