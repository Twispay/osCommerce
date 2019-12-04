<?php
/**
 * @author   Twispay
 * @version  1.0.1
 */

chdir('../../../../');
require('includes/application_top.php');
require(DIR_WS_INCLUDES.'template_top.php');
/** Include language file */
require('../'.DIR_WS_LANGUAGES.$language.'/modules/payment/twispay.php');
/** Load dependencies */
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Transactions.php');
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Oscommerce_Order.php');
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Subscriptions.php');
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Status_Updater.php');

/** Get catalog directory */
if (getenv('HTTPS') == 'on') { // We are loading an SSL page
    $admin_dir = HTTPS_SERVER.DIR_WS_HTTPS_ADMIN;
} else {
    $admin_dir = HTTP_SERVER.DIR_WS_ADMIN;
}

/** Include module css/js files */
echo '<script type="text/javascript" src="'.$admin_dir.'/ext/modules/payment/twispay/js/twispay_transactions.js"></script>';
echo '<script type="text/javascript" src="'.$admin_dir.'/ext/modules/payment/twispay/js/twispay_actions.js"></script>';
echo '<link rel="stylesheet" type="text/css" href="'.$admin_dir.'/ext/modules/payment/twispay/css/twispay.css"/>';

/** Get query field 'id' of each row
 *
 * @param int query - The query string to be called
 *
 * @return array([key => value]) - Query result
 *
 */
function getids($query)
{
    $data = array();
    if (!empty(tep_db_num_rows($query))) {
        while ($dat = tep_db_fetch_array($query)) {
            array_push($data, $dat['id']);
        }
    }
    return $data;
}

/** Get query values
 *
 * @param int query - The query string to be called
 *
 * @return array(stdObject) - Query result
 *
 */
function getdata($query)
{
    $data = array();
    if (!empty(tep_db_num_rows($query))) {
        while ($dat = tep_db_fetch_array($query)) {
            /** Append fetched value to data array */
            array_push($data, json_decode(json_encode($dat)) /** Cast to stdObject type */);
        }
    }
    return $data;
}

/** Remove argument from query string
 *
 * @param int url - The query string
 * @param int which_argument - The GET argument to be removed
 *
 * @return string - Resulted query
 *
 */
function remove_query($url, $which_argument=false)
{
    return preg_replace('/'. ($which_argument ? '(\&|)'.$which_argument.'(\=(.*?)((?=&(?!amp\;))|$)|(.*?)\b)' : '(\?.*)').'/i', '', $url);
}

/** GET DATA */
/** Read and validate GET arguments*/
/** Transaction status */
$statuses = Twispay_Status_Updater::$RESULT_STATUSES;
/** Default status | All statuses */
$selected_status = '0';
if (isset($_GET["f_status"])) {
    /** Check if selected status is valid */
    if (in_array($_GET["f_status"], $statuses)) {
        /** Set the valid status */
        $selected_status = $_GET["f_status"];
    }
}

$transaction_columns = array();
/** Extract the columns name of the transactions tale. */
foreach (getdata(tep_db_query("SHOW COLUMNS FROM `".Twispay_Transactions::$TABLE_TWISPAY_TRANSACTIONS."`")) as $dt) {
    array_push($transaction_columns, $dt->Field);
}

/** Default value for sort column | No sort column */
$sort_col = '0';
/** Default value for sort order | No sort order */
$sort_order = '0';
/** Check if any sorting must be applied */
if (isset($_GET["sort"])) {
    $sort = $_GET["sort"];
    if (strpos($sort, '_') !== false) {
        $sort = explode("_", $sort);
        /** Check if sort column value is valid */
        if (in_array($sort[0], $transaction_columns)) {
            $sort_col = $sort[0];
        }
        /** Check if sort order value is valid */
        if (in_array($sort[1], array("ASC","DESC"))) {
            $sort_order = $sort[1];
        }
    }
}

/** Customers */
$customers = getdata(tep_db_query("SELECT `customers_id` AS id, CONCAT_WS(' ',`customers_firstname`,`customers_lastname`) AS name, `customers_email_address` AS email FROM `customers`"));
$customer_ids = getids(tep_db_query("SELECT `customers_id` AS id FROM `customers`"));
/** Default value for sort column | All Customers*/
$selected_customer = '0';
/** Extracting and Validating selected customer, if any. */
$selected_customer = (!empty($_GET['id']) && in_array($_GET['id'], $customer_ids)) ? $_GET['id'] : '0';

/** Create transaction query */
$query_transactions = "SELECT * from `".Twispay_Transactions::$TABLE_TWISPAY_TRANSACTIONS."`";
if ($selected_customer!='0' || $selected_status!='0') {
    $query_transactions .= " WHERE ";
}
if ($selected_customer!='0') {
    $query_transactions .= "`identifier`='" . $selected_customer . "'";
}
if ($selected_status!='0') {
    if ($selected_customer!='0') {
        $query_transactions .= " AND ";
    }
    $query_transactions .= "`status`='" . $selected_status . "'";
}
if ($sort_col!='0' && $sort_order!='0') {
    $query_transactions .= " ORDER BY `".$sort_col."` ".$sort_order;
} else {
    $query_transactions .= " ORDER BY `date` DESC";
}

/** Extract number of items per page and the page number. */
$records = (!empty(MODULE_PAYMENT_TWISPAY_PAGINATION) && is_numeric(MODULE_PAYMENT_TWISPAY_PAGINATION)) ? (int)MODULE_PAYMENT_TWISPAY_PAGINATION : 20 ;
$option_page = (!empty($_GET['option_page'])) ? $_GET['option_page'] : 1;
$options_split = new splitPageResults($option_page /** passed by reference */, $records, $query_transactions /** passed by reference */, $options_query_numrows /** passed by reference */);
$transactions = getdata(tep_db_query($query_transactions));
?>

<div id="contentTwispay">
    <div class="container-fluid">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h2 class="panel-title"><?= MODULE_PAYMENT_TWISPAY_TRANSACTIONS_TITLE_TEXT ?></h2>
                <div class="trans-filter pull-right">
                    <select class="trans-status">
                        <option value="0" <?php if ($selected_status=='0') { ?> selected="selected"<?php } ?>><?= MODULE_PAYMENT_TWISPAY_ALLSTATUSES_TEXT ?></option>
                        <?php foreach ($statuses as $status) {?>
                          <option value="<?=$status?>" <?php if ($selected_status==$status) { ?>selected="selected"<?php } ?> title="<?=$status?>"><?=$status?></option>
                        <?php } ?>
                    </select>
                    <select class="trans-customers">
                        <option value="0" <?php if ($selected_customer=='0') { ?> selected="selected"<?php } ?>><?= MODULE_PAYMENT_TWISPAY_ALLCUSTOMERS_TEXT ?></option>
                        <?php
                        foreach ($customers as $customer) {
                            ?>
                            <option value="<?= $customer->id; ?>"
                                <?php if ($selected_customer==$customer->id) { ?>
                                    selected="selected"
                                <?php } ?>
                                    title="<?= $customer->email; ?>"><?= $customer->name; ?>
                            </option>
                            <?php
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="panel-body">
                <?php
                if (empty($transactions)) {
                ?>
                <div class="nodata"><?= MODULE_PAYMENT_TWISPAY_NOTRANSACTIONS_TEXT ?></div>
                <?php
                } else {
                ?>
                <table class="twispay-logs" cellpading="10px" cellspacing="0" width="100%" border="1">
                    <thead>
                    <tr>
                        <th colspan="2" class="big-border"><?= MODULE_PAYMENT_TWISPAY_WEBSITE_TEXT ?></th>
                        <th colspan="10"><?= MODULE_PAYMENT_TWISPAY_TWISPAY_TEXT ?></th>
                    </tr>
                    <tr>
                        <th><?= MODULE_PAYMENT_TWISPAY_USERID_TEXT ?></th>
                        <th class="sortable big-border" data-val="orderId"><?= MODULE_PAYMENT_TWISPAY_ORDERID_TEXT ?></th>
                        <th><?= MODULE_PAYMENT_TWISPAY_CUSTOMERID_TEXT ?></th>
                        <th ><?= MODULE_PAYMENT_TWISPAY_ORDERID_TEXT ?></th>
                        <th><?= MODULE_PAYMENT_TWISPAY_CARDID_TEXT ?></th>
                        <th class="sortable" data-val="transactionId"><?= MODULE_PAYMENT_TWISPAY_TRANSACTION_TEXT ?></th>
                        <th><?= MODULE_PAYMENT_TWISPAY_STATUS_TEXT ?></th>
                        <th><?= MODULE_PAYMENT_TWISPAY_AMOUNT_TEXT ?></th>
                        <th><?= MODULE_PAYMENT_TWISPAY_CURRENCY_TEXT ?></th>
                        <th class="sortable desc" data-val="date"><?= MODULE_PAYMENT_TWISPAY_DATE_TEXT ?></th>
                        <th><?= MODULE_PAYMENT_TWISPAY_REFUND_TEXT ?></th>
                    </tr>
                    </thead>
                    <tbody>

                    <?php
                    foreach ($transactions as $tran) {?>
                        <tr>
                            <td><?= $tran->identifier; ?></td>
                            <td class="big-border"><?= $tran->order_id; ?></td>
                            <td><?= $tran->customerId; ?></td>
                            <td><?= $tran->orderId; ?></td>
                            <td><?= $tran->cardId; ?></td>
                            <td><?= $tran->transactionId; ?></td>
                            <td><?= $tran->status; ?></td>
                            <td><?= $tran->amount; ?></td>
                            <td><?= $tran->currency; ?></td>
                            <td><?= $tran->date; ?></td>
                            <td data-popup-message="<?= sprintf(MODULE_PAYMENT_TWISPAY_REFUND_NOTICE_TEXT, $tran->transactionId); ?>"
                            data-amount-message="<?= MODULE_PAYMENT_TWISPAY_REFUND_AMOUNT_NOTICE_TEXT ?>"
                            data-trans-amount="<?= $tran->amount; ?>"
                            data-transid="<?= $tran->transactionId; ?>"
                            data-refunded-amount="<?= $tran->refunded_amount; ?>"><?php if ($tran->status==Twispay_Status_Updater::$RESULT_STATUSES['COMPLETE_OK'] || $tran->status==Twispay_Status_Updater::$RESULT_STATUSES['PARTIAL_REFUNDED']) { ?>
                                    <input type="number" name="amount" min="0" max="<?= $tran->amount - $tran->refunded_amount; ?>" style="min-width:50px;top:0">
                                    <img src="images/icons/cross.gif" class="refund fa fa-times red" aria-hidden="true"/>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php
                    } ?>
                    </tbody>
                </table>
                <div class="twispay-pagination">
                <?php
                    echo $options_split->display_links($options_query_numrows, $records, MAX_DISPLAY_PAGE_LINKS, $option_page, remove_query($_SERVER['QUERY_STRING'], "option_page"), 'option_page');
                }?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require(DIR_WS_INCLUDES . 'template_bottom.php');
require(DIR_WS_INCLUDES . 'application_bottom.php');
?>
