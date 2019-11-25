oscommerce-Twispay_Payments
=========================

The official [Twispay Payment Gateway][twispay] extension for OScommerce.

At the time of purchase, after checkout confirmation, the customer will be redirected to the secure Twispay Payment Gateway.

All payments will be processed in a secure PCI DSS compliant environment so you don't have to think about any such compliance requirements in your web shop.

Install
=======

TODO 1. Download the Twispay payment module from OScommerce Marketplace, where you can find [The Official Twispay Payment Gateway Extension][marketplace]
2. Unzip the archive files and upload the content of folder "uploads" in the corresponding files on the server.
3. Sign in to your OScommerce admin.
4. Click **Modules** tab and **Payment**.
5. Click **Install Module**.
6. Find **Credit card secure payment | Twispay** click on it and then click **Install Module**.
7. Click **Modules** tab and **Payment**.
8. Find **Credit card secure payment | Twispay** click on it and then click **Edit**.
9. Select **True** under **Enable Twispay**.
10. Select **False** under **Test Mode**. _(Unless you are testing)_
11. Enter your **Live Site ID**. _(Twispay ID for live site)_
12. Enter your **Live Site Key**. _(Twispay private KEY for live site)_
13. Enter your **Custom redirect page**. _(Optional, if you have a custom thank you page)_
14. Select **Payment Zone**. _(Optional, if any valid payment zone is defined)_
15. Enter your **Contact email**. _(Optional, The email address for technical support)_
16. Enter **Transactions on page**. _(The number of results on a transaction list page)_
17. Enter the **Sort Order**. _(The order that the payment option will appear on the checkout payment tab in accordance with the other payment methods. Lowest is displayed first.)_
18. Select **___ Order Status**. _(Optional, if you want to assign other order statuses to Twispay predefined ones)_
19. Save your changes.

Subscription Patch
==================

Our module also provides a basic solution for recurring purchases which are processes on our platform but requires some changes inside the oscommerce platform frontend files which can only be done manually.
!!! Note: This feauture is only compatible with Twispay payment method.
!!! Note: No free trial accepted.
!!! Note: Only one product per subscription is accepted.

Interface
==========
---> Page: Product page
Here you can now setup the recurrence options like:
Recurring status - Activate / Deactivate the recurring process.
Recurring duration - how many times to repeat the payment. After the specified number of payments is reached the automatic cancel operation will be called
Recurring cycles - The length of time period.
Recurring frequency - The type of period
Trial status - Activate / Deactivate the trial period.
Trial cycles - The length of time period.
Trial frequency - The type of period
Trial price - the price for trial period

!!! Note: The information about recurring product is not visible by customer. The administrator has the obligation to create a suggestive name like "Subscription Product" and a suggestive description that should contain the recurring information listed above.  

---> Page: Order edit page
The administration has the option to close the subscription from here only if that has the "Recurring Status" = "Active".

---> Page: My account > History > Order Information
The customer has the option to close the subscription from here only if that has the "Recurring Status" = "Active".

Install - Manually
==================
In order to enable the subscription feature provided by our module, please change the content of the following files as described below:

---> File: oscommerce/admin/orders.php
==== add after line 13 "... require('includes/application_top.php'); ...":

"... require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Transactions.php');
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Subscriptions.php');
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Oscommerce_Order.php');
require_once('../'.DIR_WS_LANGUAGES . $language . '/modules/payment/twispay.php'); ..."

==== add after line 295 "... <td class="smallText" valign="top"><?php echo tep_draw_button(IMAGE_UPDATE, 'disk', null, 'primary'); ?></td> ..."

"... <?php
    if (getenv('HTTPS') == 'on') {
        $admin_dir = HTTPS_SERVER.DIR_WS_HTTPS_ADMIN;
        $catalog_dir = HTTPS_SERVER.DIR_WS_CATALOG;
    } else {
        $admin_dir = HTTP_SERVER.DIR_WS_ADMIN;
        $catalog_dir = HTTP_SERVER.DIR_WS_CATALOG;
    }
    $subscriptionStatus = Twispay_Subscriptions::subscriptionStatus($oID);
    $twOrderId = Twispay_Transactions::getTwispayOrderId($oID);
?>
<script type="text/javascript" src="<?=$catalog_dir?>ext/modules/payment/twispay/js/twispay_user_actions.js"></script>
<td class="cancel_subscription" class="smallText" valign="top" data-popup-message="<?= sprintf(MODULE_PAYMENT_TWISPAY_CANCEL_SUBSCRIPTION_NOTICE_TEXT) ?>" data-orderid="<?= $oID; ?>" data-tworderid="<?= $twOrderId; ?>" data-admin-dir="<?= $admin_dir.'ext/modules/payment/twispay/twispay_actions.php'; ?>">
    <?php if($subscriptionStatus == Twispay_Subscriptions::$STATUSES['ACTIVE'] && $twOrderId){ ?>
      <?php echo tep_draw_button(BUTTON_CANCEL_SUBSCRIPTION_TEXT, 'close', '#', 'primary'); ?>
    <?php } ?>
</td> ..."

==== add after line 321 "... <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
  <tr class="dataTableHeadingRow"> ...":

"... <td class="dataTableHeadingContent"><?= TABLE_HEADING_SUBSCRIPTION_STATUS_TEXT; ?></td> ..."

==== add before line 352 "... <td class="dataTableContent"><?php echo '<a href="' . tep_href_link(FILENAME_ORDERS, tep_get_all_get_params(array('oID', 'action')) . 'oID=' . $orders['orders_id'] . '&action=edit') . '">' . tep_image(DIR_WS_ICONS . 'preview.gif', ICON_PREVIEW) . '</a>&nbsp;' . $orders['customers_name']; ?></td>
 ...":

"... <td class="dataTableContent"><?php echo Twispay_Subscriptions::subscriptionStatus($orders['orders_id']); ?></td> ..."

---> File: oscommerce/admin/categories.php
==== add after line 219 "... 'products_weight' => (float)tep_db_prepare_input($HTTP_POST_VARS['products_weight']), ...":

"... 'products_custom_recurring_status' => tep_db_prepare_input($HTTP_POST_VARS['products_custom_recurring_status']),
'products_custom_recurring_duration' => (int)tep_db_prepare_input($HTTP_POST_VARS['products_custom_recurring_duration']),
'products_custom_recurring_cycle' => (int)tep_db_prepare_input($HTTP_POST_VARS['products_custom_recurring_cycle']),
'products_custom_recurring_frequency' => (string)tep_db_prepare_input($HTTP_POST_VARS['products_custom_recurring_frequency']),
'products_custom_trial_status' => tep_db_prepare_input($HTTP_POST_VARS['products_custom_trial_status']),
'products_custom_trial_cycle' => (int)tep_db_prepare_input($HTTP_POST_VARS['products_custom_trial_cycle']),
'products_custom_trial_frequency' => (string)tep_db_prepare_input($HTTP_POST_VARS['products_custom_trial_frequency']),
'products_custom_trial_price' => (float)tep_db_prepare_input($HTTP_POST_VARS['products_custom_trial_price']), ..."

==== replace line 346 "... $product_query = tep_db_query("select products_quantity, products_model, products_image, products_price, products_date_available, products_weight, products_tax_class_id, manufacturers_id from " . TABLE_PRODUCTS . " where products_id = '" . (int)$products_id . "'"); ..." with:

"... $product_query = tep_db_query("select products_quantity, products_model, products_image, products_price, products_date_available, products_weight, products_custom_recurring_status, products_custom_recurring_duration, products_custom_recurring_cycle, products_custom_recurring_frequency, products_custom_trial_status, products_custom_trial_cycle, products_custom_trial_frequency, products_custom_trial_price , products_tax_class_id, manufacturers_id from " . TABLE_PRODUCTS . " where products_id = '" . (int)$products_id . "'"); ..."

==== replace line 349 ""... tep_db_query("insert into " . TABLE_PRODUCTS . " (products_quantity, products_model,products_image, products_price, products_date_added, products_date_available, products_weight, products_status, products_tax_class_id, manufacturers_id) values ('" . tep_db_input($product['products_quantity']) . "', '" . tep_db_input($product['products_model']) . "', '" . tep_db_input($product['products_image']) . "', '" . tep_db_input($product['products_price']) . "',  now(), " . (empty($product['products_date_available']) ? "null" : "'" . tep_db_input($product['products_date_available']) . "'") . ", '" . tep_db_input($product['products_weight']) . "', '" . (int)$product['products_tax_class_id'] . "', '" . (int)$product['manufacturers_id'] . "')");" with: ..."

"... tep_db_query("insert into " . TABLE_PRODUCTS . " (products_quantity, products_model,products_image, products_price, products_date_added, products_date_available, products_weight, products_custom_recurring_status, products_custom_recurring_duration, products_custom_recurring_cycle, products_custom_recurring_frequency, products_custom_trial_status, products_custom_trial_cycle, products_custom_trial_frequency, products_custom_trial_price , products_status, products_tax_class_id, manufacturers_id) values ('" . tep_db_input($product['products_quantity']) . "', '" . tep_db_input($product['products_model']) . "', '" . tep_db_input($product['products_image']) . "', '" . tep_db_input($product['products_price']) . "',  now(), " . (empty($product['products_date_available']) ? "null" : "'" . tep_db_input($product['products_date_available']) . "'") . ", '" . tep_db_input($product['products_weight']) . "', '" . tep_db_input($product['products_custom_recurring_status']) . "', '" . tep_db_input($product['products_custom_recurring_duration']) . "', '" . tep_db_input($product['products_custom_recurring_cycle']) . "', '" . tep_db_input($product['products_custom_recurring_frequency']) . "', '" . tep_db_input($product['products_custom_trial_status']) . "', '" . tep_db_input($product['products_custom_trial_cycle']) . "', '" . tep_db_input($product['products_custom_trial_frequency']) . "', '" . tep_db_input($product['products_custom_trial_price']) . "', '0', '" . (int)$product['products_tax_class_id'] . "', '" . (int)$product['manufacturers_id'] . "')"); ..."


==== add after line 396 "... 'products_weight' => '', ...":

"... 'products_custom_recurring_status' => false,
'products_custom_recurring_duration' => '',
'products_custom_recurring_cycle' => '',
'products_custom_recurring_frequency' => '',
'products_custom_trial_status' => false,
'products_custom_trial_cycle' => '',
'products_custom_trial_frequency' => '',
'products_custom_trial_price' => '' ..."


==== replace line 407 "... $product_query = tep_db_query("select pd.products_name, pd.products_description, pd.products_url, p.products_id, p.products_quantity, p.products_model, p.products_image, p.products_price, p.products_weight, p.products_date_added, p.products_last_modified, date_format(p.products_date_available, '%Y-%m-%d') as products_date_available, p.products_status, p.products_tax_class_id, p.manufacturers_id from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = '" . (int)$HTTP_GET_VARS['pID'] . "' and p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "'");
 ..."

"... $product_query = tep_db_query("select pd.products_name, pd.products_description, pd.products_url, p.products_id, p.products_quantity, p.products_model, p.products_image, p.products_price, p.products_weight, p.products_custom_recurring_status, p.products_custom_recurring_duration, p.products_custom_recurring_cycle, p.products_custom_recurring_frequency, p.products_custom_trial_status, p.products_custom_trial_cycle, p.products_custom_trial_frequency, p.products_custom_trial_price , p.products_date_added, p.products_last_modified, date_format(p.products_date_available, '%Y-%m-%d') as products_date_available, p.products_status, p.products_tax_class_id, p.manufacturers_id from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = '" . (int)$HTTP_GET_VARS['pID'] . "' and p.products_id = pd.products_id and pd.language_id = '" . (int)$languages_id . "'"); ..."

==== replace line 699 "... $product_query = tep_db_query("select p.products_id, pd.language_id, pd.products_name, pd.products_description, pd.products_url, p.products_quantity, p.products_model, p.products_image, p.products_price, p.products_weight, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status, p.manufacturers_id  from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = pd.products_id and p.products_id = '" . (int)$HTTP_GET_VARS['pID'] . "'");
 ..."

"... $product_query = tep_db_query("select p.products_id, pd.language_id, pd.products_name, pd.products_description, pd.products_url, p.products_quantity, p.products_model, p.products_image, p.products_price, p.products_weight, p.products_custom_recurring_status, p.products_custom_recurring_duration, p.products_custom_recurring_cycle, p.products_custom_recurring_frequency, p.products_custom_trial_status, p.products_custom_trial_cycle, p.products_custom_trial_frequency, p.products_custom_trial_price, p.products_date_added, p.products_last_modified, p.products_date_available, p.products_status, p.manufacturers_id  from " . TABLE_PRODUCTS . " p, " . TABLE_PRODUCTS_DESCRIPTION . " pd where p.products_id = pd.products_id and p.products_id = '" . (int)$HTTP_GET_VARS['pID'] . "'"); ..."

---> File: oscommerce/account_history_info.php
==== add after line 53 "... <?php echo HEADING_ORDER_DATE . ' ' . tep_date_long($order->info['date_purchased']); ?></div><table border="0" width="100%" cellspacing="1" cellpadding="2"> ..."

"... <?php
    require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Transactions.php');
    require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Subscriptions.php');
    require(DIR_WS_LANGUAGES.$language.'/modules/payment/twispay.php');

    if (getenv('HTTPS') == 'on') {
        $catalog_dir = HTTPS_SERVER.DIR_WS_CATALOG;
    } else {
        $catalog_dir = HTTP_SERVER.DIR_WS_CATALOG;
    }

    $subscriptionStatus = Twispay_Subscriptions::subscriptionStatus($HTTP_GET_VARS['order_id']);
    $twOrderId = Twispay_Transactions::getTwispayOrderId($HTTP_GET_VARS['order_id']);
?>
<?php if($subscriptionStatus == Twispay_Subscriptions::$STATUSES['ACTIVE'] && $twOrderId) : ?>
  <script type="text/javascript" src="<?=$catalog_dir?>ext/modules/payment/twispay/js/twispay_user_actions.js"></script>
  <tr>
    <td class="cancel_subscription" class="smallText" valign="top" data-popup-message="<?= sprintf(MODULE_PAYMENT_TWISPAY_CANCEL_SUBSCRIPTION_NOTICE_TEXT) ?>" data-orderid="<?= $HTTP_GET_VARS['order_id']; ?>" data-tworderid="<?= $twOrderId; ?>" data-admin-dir="<?= $catalog_dir.'ext/modules/payment/twispay/twispay_user_actions.php'; ?>">
      <?php if($subscriptionStatus ==== "Active"){ ?>
        <?php echo tep_draw_button(BUTTON_CANCEL_SUBSCRIPTION_TEXT, 'close', '#', 'primary'); ?>
      <?php } ?>
    </td>
  </tr>
<?php endif; ?> ..."

---> File: oscommerce/checkout_payment.php
==== replace line 191 "... for ($i=0, $n=sizeof($selection); $i<$n; $i++) { ..." with:

"... require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Subscriptions.php');
$cartContainRecurringProds = Twispay_Subscriptions::containRecurrings($order->products);
for ($i=0, $n=sizeof($selection); $i<$n; $i++) {
  if($selection[$i]['id']!='twispay' && $cartContainRecurringProds){
    continue;
  } ..."

Changelog
=========

= 1.0.1 =
* Added subscriptions support.
* Updated the way requests are sent to the Twispay server.
* Updated the server response handling to process all the possible server response statuses.
* Added support for refunds.
* Added filter by status an sorting support for transactions table.

= 1.0.0 =
* Initial Plugin version

<!-- Other Notes
===========

A functional description of the extension can be found on the [wiki page][doc] -->

TODO
[twispay]: http://twispay.com/
[marketplace]: https://apps.oscommerce.com/elkhN&twispay-credit-card-payments
[github]: https://github.com/Twispay/osCommerce
