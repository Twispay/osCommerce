<?php
/**
 * @author   Twistpay
 * @version  1.0.1
 */

define('MODULE_PAYMENT_TWISPAY_TEXT_TITLE', 'Credit card secure payment | Twispay');
define('MODULE_PAYMENT_TWISPAY_TEXT_PUBLIC_TITLE', 'Credit card secure payment | Twispay');
define('MODULE_PAYMENT_TWISPAY_CLEAR_BUTTON_TEXT', 'Delete unfinished orders');
define('MODULE_PAYMENT_TWISPAY_TRANSACTIONS_BUTTON_TEXT', 'Transactions Log');
define('MODULE_PAYMENT_TWISPAY_IMAGE_TITLE_TEXT', 'Visit Twispay Site');
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

/* General */
define('PROCESSING_TEXT','Processing ...');
define('JSON_DECODE_ERROR_TEXT','Json decode error');
define('NO_POST_TEXT','[RESPONSE-ERROR]: no_post');

define('GENERAL_ERROR_TITLE_TEXT','An error occurred:');
define('GENERAL_ERROR_DESC_F_TEXT','The payment could not be processed. Please');
define('GENERAL_ERROR_DESC_CONTACT_TEXT',' contact');
define('GENERAL_ERROR_DESC_S_TEXT',' the website administrator.');
define('GENERAL_ERROR_HOLD_NOTICE_TEXT',' Payment is on hold.');
define('GENERAL_ERROR_INVALID_ORDER_TEXT',' Invalid order.');

/* Checkout validation */
// define('CHECKOUT_ERROR_TOO_MANY_PRODS_TEXT','In case of recurring products, the order must contain only one subscription at a time.');
// define('CHECKOUT_NOTICE_FREE_TRIAL_TEXT','Free trial is not suported by payment processor.');
// define('CHECKOUT_NOTICE_CYCLES_NUMBER_TEXT','Trial period can only have one payment. If multiple trial duration is configured, the periods and payments will be summed up and only one payment will be performed.');

/* Order Notice */
define('A_ORDER_FAILED_NOTICE_TEXT','Twispay payment failed');
define('A_ORDER_HOLD_NOTICE_TEXT','Twispay payment is on hold');
define('A_ORDER_VOID_NOTICE_TEXT','Twispay payment was voided #');
define('A_ORDER_CHARGEDBACK_NOTICE_TEXT','Twispay payment was charged_back #');
define('A_ORDER_REFUNDED_NOTICE_TEXT','Twispay payment was refunded #');
define('A_ORDER_REFUNDED_REQUESTED_NOTICE_TEXT','Twispay refund requested');
define('A_ORDER_PAID_NOTICE_TEXT','Paid Twispay #');
define('A_ORDER_CANCELED_NOTICE_TEXT','Twispay payment was canceled');

/* LOG insertor */
define('LOG_REFUND_RESPONSE_TEXT','[RESPONSE]: Refund operation data: ');
// define('LOG_CANCEL_RESPONSE_TEXT','[RESPONSE]: Cancel operation data: ');
// define('LOG_SYNC_RESPONSE_TEXT','[RESPONSE]: Sync operation data: ');

define('LOG_OK_RESPONSE_DATA_TEXT','[RESPONSE]: Data: ');
define('LOG_OK_STRING_DECRYPTED_TEXT','[RESPONSE]: decrypted string: ');
define('LOG_OK_STATUS_COMPLETE_TEXT','[RESPONSE]: Status complete-ok for order ID: ');
define('LOG_OK_STATUS_REFUND_TEXT','[RESPONSE]: Status refund-ok for order ID: ');
define('LOG_OK_STATUS_FAILED_TEXT','[RESPONSE]: Status failed for order ID: ');
define('LOG_OK_STATUS_VOIDED_TEXT','[RESPONSE]: Status voided for order ID: ');
define('LOG_OK_STATUS_CANCELED_TEXT','[RESPONSE]: Status canceled for order ID: ');
define('LOG_OK_STATUS_CHARGED_BACK_TEXT','[RESPONSE]: Status charged back for order ID: ');
define('LOG_OK_STATUS_HOLD_TEXT','[RESPONSE]: Status on-hold for order ID: ');
define('LOG_OK_VALIDATING_COMPLETE_TEXT','[RESPONSE]: Validating completed for order ID: ');

define('LOG_ERROR_VALIDATING_FAILED_TEXT','[RESPONSE-ERROR]: Validation failed.');
define('LOG_ERROR_DECRYPTION_ERROR_TEXT','[RESPONSE-ERROR]: Decryption failed.');
define('LOG_ERROR_INVALID_ORDER_TEXT','[RESPONSE-ERROR]: Order does not exist.');
define('LOG_ERROR_WRONG_STATUS_TEXT','[RESPONSE-ERROR]: Wrong status: ');
define('LOG_ERROR_EMPTY_STATUS_TEXT','[RESPONSE-ERROR]: Empty status.');
define('LOG_ERROR_EMPTY_IDENTIFIER_TEXT','[RESPONSE-ERROR]: Empty identifier.');
define('LOG_ERROR_EMPTY_EXTERNAL_TEXT','[RESPONSE-ERROR]: Empty externalOrderId.');
define('LOG_ERROR_EMPTY_TRANSACTION_TEXT','[RESPONSE-ERROR]: Empty transactionId.');
define('LOG_ERROR_EMPTY_RESPONSE_TEXT','[RESPONSE-ERROR]: Received empty response.');
define('LOG_ERROR_INVALID_PRIVATE_TEXT','[RESPONSE-ERROR]: Private key is not valid.');
define('LOG_ERROR_TRANSACTION_EXIST_TEXT','[RESPONSE-ERROR]: Transaction cannot be overwritten #');

// define('SUBSCRIPTIONS_LOG_OK_SET_STATUS_TEXT','[RESPONSE]: Server status set for order ID: ');
// define('SUBSCRIPTIONS_LOG_ERROR_SET_STATUS_TEXT','[RESPONSE-ERROR]: Failed to set server status for order ID: ');
// define('SUBSCRIPTIONS_LOG_ERROR_GET_STATUS_TEXT','[RESPONSE-ERROR]: Failed to get server status for order ID: ');
// define('SUBSCRIPTIONS_LOG_ERROR_CALL_FAILED_TEXT','[RESPONSE-ERROR]: Failed to call server: ');
// define('SUBSCRIPTIONS_LOG_ERROR_HTTP_CODE_TEXT','[RESPONSE-ERROR]: Unexpected HTTP response code: ');
// define('SUBSCRIPTIONS_LOG_ERROR_ORDER_NOT_FOUND_TEXT','[RESPONSE-ERROR]: Not found by twispay server for order ID: ');
// define('SUBSCRIPTIONS_LOG_ERROR_NO_ORDERS_FOUND_TEXT','[RESPONSE-ERROR]: No orders found.');
