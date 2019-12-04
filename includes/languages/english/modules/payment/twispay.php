<?php
/**
 * @author   Twispay
 * @version  1.0.1
 */

/* ADMIN */
/* Configuration */
define('MODULE_PAYMENT_TWISPAY_TEXT_TITLE', 'Credit card secure payment | Twispay');
define('MODULE_PAYMENT_TWISPAY_TEXT_PUBLIC_TITLE', 'Credit card secure payment | Twispay');
define('MODULE_PAYMENT_TWISPAY_LOADING_TEXT', 'Loading...');
define('MODULE_PAYMENT_TWISPAY_CLEAR_BUTTON_TEXT', 'Delete unfinished orders');
define('MODULE_PAYMENT_TWISPAY_SYNC_BUTTON_TEXT', 'Sync subscriptions');
define('MODULE_PAYMENT_TWISPAY_TRANSACTIONS_BUTTON_TEXT', 'Transactions Log');
define('MODULE_PAYMENT_TWISPAY_IMAGE_TITLE_TEXT', 'Visit Twispay Site');
define('MODULE_PAYMENT_TWISPAY_ERROR_STAGE_TEXT', 'Configuration for TESTING is not complete, module will not load. Please edit ID and KEY for stage site.');
define('MODULE_PAYMENT_TWISPAY_ERROR_LIVE_TEXT', 'Configuration for LIVE SITE is not complete, module will not load. Please edit ID and KEY for live site.');
define('MODULE_PAYMENT_TWISPAY_CLEANALL_NOTICE_TEXT', 'Are you sure you want to delete unfinished twispay payments? Process is not reversible!');

/* Transactions */
define('MODULE_PAYMENT_TWISPAY_TRANSACTIONS_TITLE_TEXT', 'Twispay transactions');
define('MODULE_PAYMENT_TWISPAY_ALLSTATUSES_TEXT', 'All Statuses');
define('MODULE_PAYMENT_TWISPAY_ALLCUSTOMERS_TEXT', 'All Customers');
define('MODULE_PAYMENT_TWISPAY_NOTRANSACTIONS_TEXT', 'No transactions');
define('MODULE_PAYMENT_TWISPAY_WEBSITE_TEXT', 'Website');
define('MODULE_PAYMENT_TWISPAY_TWISPAY_TEXT', 'Twispay');
define('MODULE_PAYMENT_TWISPAY_USERID_TEXT', 'User ID');
define('MODULE_PAYMENT_TWISPAY_ORDERID_TEXT', 'Order ID');
define('MODULE_PAYMENT_TWISPAY_CUSTOMERID_TEXT', 'Customer ID');
define('MODULE_PAYMENT_TWISPAY_CARDID_TEXT', 'Card ID');
define('MODULE_PAYMENT_TWISPAY_TRANSACTION_TEXT', 'Transaction');
define('MODULE_PAYMENT_TWISPAY_STATUS_TEXT', 'Status');
define('MODULE_PAYMENT_TWISPAY_AMOUNT_TEXT', 'Amount');
define('MODULE_PAYMENT_TWISPAY_CURRENCY_TEXT', 'Currency');
define('MODULE_PAYMENT_TWISPAY_DATE_TEXT', 'Date');
define('MODULE_PAYMENT_TWISPAY_REFUND_TEXT', 'Refund amount');
define('MODULE_PAYMENT_TWISPAY_CLEAN_SUCCESS_TEXT', '%s records deleted');
define('MODULE_PAYMENT_TWISPAY_REFUND_SUCCESS_TEXT', 'Successfully refunded');
define('MODULE_PAYMENT_TWISPAY_CANCEL_SUCCESS_TEXT', 'Successfully canceled');
define('MODULE_PAYMENT_TWISPAY_REFUND_NOTICE_TEXT', 'Are you sure you want to Refund Transaction #%s? Process is not reversible!');
define('MODULE_PAYMENT_TWISPAY_CANCEL_SUBSCRIPTION_NOTICE_TEXT', 'Are you sure you want to Cancel Subscription? Process is not reversible!');
define('MODULE_PAYMENT_TWISPAY_SYNC_NOTICE_TEXT', 'Are you sure you want to Sync All Orders?');
define('MODULE_PAYMENT_TWISPAY_SYNC_SUCCESS_TEXT', 'Successful synced!');
define('MODULE_PAYMENT_TWISPAY_CHECK_NOTICE_ADMIN_TEXT', ' Please check the issue on Twispay admin panel.');
define('MODULE_PAYMENT_TWISPAY_CHECK_NOTICE_CUSTOMER_TEXT', ' Please contact the website administrator.');

define('MODULE_PAYMENT_TWISPAY_REFUND_AMOUNT_NOTICE_TEXT', 'The inserted amount is not valid!');
define('MODULE_PAYMENT_TWISPAY_ACCESS_ERROR_TEXT', 'Access denied!');
define('MODULE_PAYMENT_TWISPAY_ERROR_UNDEFINED_ID_TEXT', 'Invalid ID!');
define('MODULE_PAYMENT_TWISPAY_SUBSCRIPTION_TOOMANYPRODUCTS', 'Twispay payment error: In case of recurring products, the order must contain only one subscription at a time!');
define('MODULE_PAYMENT_TWISPAY_INVALID_SUBSCRIPTION_FREETRIAL', 'Twispay payment error: Free trial is not suported by payment processor!');

/* CATALOG */
/* Email */
define('EMAIL_TEXT_SUBJECT', 'Order Process');
define('EMAIL_TEXT_ORDER_NUMBER', 'Order Number:');
define('EMAIL_TEXT_INVOICE_URL', 'Detailed Invoice:');
define('EMAIL_TEXT_DATE_ORDERED', 'Date Ordered:');
define('EMAIL_TEXT_PRODUCTS', 'Products');
define('EMAIL_TEXT_SUBTOTAL', 'Sub-Total:');
define('EMAIL_TEXT_TAX', 'Tax:        ');
define('EMAIL_TEXT_SHIPPING', 'Shipping: ');
define('EMAIL_TEXT_TOTAL', 'Total:    ');
define('EMAIL_TEXT_DELIVERY_ADDRESS', 'Delivery Address');
define('EMAIL_TEXT_BILLING_ADDRESS', 'Billing Address');
define('EMAIL_TEXT_PAYMENT_METHOD', 'Payment Method');
define('EMAIL_SEPARATOR', '------------------------------------------------------');
define('TEXT_EMAIL_VIA', 'via');

/* General */
define('PROCESSING_TEXT','Processing ...');
define('JSON_DECODE_ERROR_TEXT','Json decode error');
define('NO_POST_TEXT','[RESPONSE-ERROR]: no_post');
define('TABLE_HEADING_SUBSCRIPTION_STATUS_TEXT','Subscriptions status');
define('BUTTON_CANCEL_SUBSCRIPTION_TEXT','Cancel Subscription');
define('TEXT_ENABLE','Enable');
define('TEXT_DISABLE','Disable');
define('TEXT_DAY','daily');
define('TEXT_WEEK','weekly');
define('TEXT_MONTH','monthly');
define('TEXT_YEAR','yearly');
define('TEXT_PRODUCTS_CUSTOM_RECURRING_STATUS','Recurring status:');
define('TEXT_PRODUCTS_CUSTOM_RECURRING_DURATION','Recurring duration (how many times to repeat):');
define('TEXT_PRODUCTS_CUSTOM_RECURRING_CYCLE','Recurring cycles:');
define('TEXT_PRODUCTS_CUSTOM_RECURRING_FREQUENCY','Recurring frequency:');
define('TEXT_PRODUCTS_CUSTOM_TRIAL_STATUS','Trial status:');
define('TEXT_PRODUCTS_CUSTOM_TRIAL_CYCLE','Trial cycles:');
define('TEXT_PRODUCTS_CUSTOM_TRIAL_FREQUENCY','Trial frequency:');
define('TEXT_PRODUCTS_CUSTOM_TRIAL_PRICE','Trial price:');

define('GENERAL_ERROR_TITLE_TEXT','An error occurred:');
define('GENERAL_ERROR_DESC_F_TEXT','The payment could not be processed. Please');
define('GENERAL_ERROR_DESC_CONTACT_TEXT',' contact');
define('GENERAL_ERROR_DESC_S_TEXT',' the website administrator.');
define('GENERAL_ERROR_HOLD_NOTICE_TEXT',' Payment is on hold.');
define('GENERAL_ERROR_INVALID_ORDER_TEXT',' Invalid order.');

/* Order Notice */
define('ORDER_FAILED_NOTICE_TEXT','Twispay payment failed');
define('ORDER_HOLD_NOTICE_TEXT','Twispay payment is on hold');
define('ORDER_VOID_NOTICE_TEXT','Twispay payment was voided #');
define('ORDER_CHARGEDBACK_NOTICE_TEXT','Twispay payment was charged_back #');
define('ORDER_REFUNDED_NOTICE_TEXT','Twispay payment was refunded #');
define('ORDER_REFUND_REQUESTED_NOTICE_TEXT','Twispay refund was requested for transaction #');
define('ORDER_REFUNDED_REQUESTED_NOTICE_TEXT','Twispay refund requested');
define('ORDER_PAID_NOTICE_TEXT','Paid Twispay #');
define('ORDER_CANCELED_NOTICE_TEXT','Twispay payment was canceled');
define('ORDER_NO_ACTION_NOTICE_TEXT','The action parameter is not defined.');
define('ORDER_INVLID_ACTION_NOTICE_TEXT','The specified action is not valid.');


/* LOG insertor */
define('LOG_REFUND_RESPONSE_TEXT','[RESPONSE]: Refund operation data: ');
define('LOG_CANCEL_RESPONSE_TEXT','[RESPONSE]: Cancel operation data: ');

define('LOG_OK_RESPONSE_DATA_TEXT','[RESPONSE]: Data: ');
define('LOG_OK_STRING_DECRYPTED_TEXT','[RESPONSE]: decrypted string: ');
define('LOG_OK_STATUS_COMPLETE_TEXT','[RESPONSE]: Status complete-ok for order ID: ');
define('LOG_OK_STATUS_REFUNDED_TEXT','[RESPONSE]: Status refund-ok for order ID: ');
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

define('SUBSCRIPTIONS_LOG_OK_SET_STATUS_TEXT','[RESPONSE]: Server status set for order ID: ');
define('SUBSCRIPTIONS_LOG_ERROR_CALL_FAILED_TEXT','[RESPONSE-ERROR]: Failed to call server: ');
define('SUBSCRIPTIONS_LOG_ERROR_HTTP_CODE_TEXT','[RESPONSE-ERROR]: Unexpected HTTP response code: ');
define('SUBSCRIPTIONS_LOG_ERROR_ORDER_NOT_FOUND_TEXT','[RESPONSE-ERROR]: Not found by twispay server for subscription with ID: ');
define('SUBSCRIPTIONS_LOG_ERROR_NO_ORDERS_FOUND_TEXT','[RESPONSE-ERROR]: No subscription found.');
define('SUBSCRIPTIONS_LOG_ERROR_GET_STATUS_TEXT','[RESPONSE-ERROR]: Failed to get server status for order ID: ');
define('SUBSCRIPTIONS_LOG_ERROR_SET_STATUS_TEXT','[RESPONSE-ERROR]: Failed to set server status for order ID: ');
