<?php
/**
 * @author   Twistpay
 * @version  1.0.1
 */

chdir('../../../../');
require('includes/application_top.php');
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Status_Updater.php');
$languages = tep_get_languages();
/** Include language */
$languages_array = array();
$languages_selected = DEFAULT_LANGUAGE;
for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
    $languages_array[] = array('id' => $languages[$i]['code'],
        'text' => $languages[$i]['name']);
    if ($languages[$i]['directory'] == $language) {
        $languages_selected = $languages[$i]['code'];
    }
}
require(DIR_WS_INCLUDES . 'template_top.php');

/** Get query field 'id' of each row
 *
 * @param int query - The query string to be called
 *
 * @return array([key => value]) - Query result
 *
 */
function getids($query){
    $data = array();
    if(!empty(tep_db_num_rows($query))){
        while ($dat = tep_db_fetch_array($query)){
            array_push($data, $dat['id']);
        }
    }
    return $data;
}

/** Get query values
 *
 * @param int query - The query string to be called
 *
 * @return array([key => value]) - Query result
 *
 */
function getdata($query){
    $data = array();
    if(!empty(tep_db_num_rows($query))){
        while ($dat = tep_db_fetch_array($query)){
            array_push($data, json_decode(json_encode($dat)));
        }
    }
    return json_decode(json_encode($data));
}

/** Remove argument from query string
 *
 * @param int url - The query string
 * @param int which_argument - The GET argument to be removed
 *
 * @return array([key => value]) - Query result
 *
 */
function remove_query($url, $which_argument=false){
    return preg_replace('/'. ($which_argument ? '(\&|)'.$which_argument.'(\=(.*?)((?=&(?!amp\;))|$)|(.*?)\b)' : '(\?.*)').'/i' , '', $url);
}

/** GET DATA */
/** Read and validate GET arguments*/

/** Transaction status */
$statuses = Twispay_Status_Updater::$RESULT_STATUSES;
$selected_status = '0';
if (isset($_GET["f_status"])) {
    if (in_array($_GET["f_status"], $statuses)) {
      $selected_status = $_GET["f_status"];
    }
}

/** Sortable columns name */
$query = tep_db_query("SHOW COLUMNS FROM `twispay_transactions`");
$transaction_columns = array();
foreach (getdata($query) as $dt) {
    array_push($transaction_columns, $dt->Field);
}

$sort_col = '0';
$sort_order = '0';
if (isset($_GET["sort"])) {
    $sort = $_GET["sort"];
    if (strpos($sort, '_') !== false) {
        $sort = explode("_", $sort);
        /** Validate GET value */
        if(in_array($sort[0], $transaction_columns)){
          $sort_col = $sort[0];
        }
        /** Validate GET value */
        if(in_array($sort[1], array("ASC","DESC"))){
          $sort_order = $sort[1];
        }
    }
}

/** Customers */
$customers = getdata(tep_db_query("SELECT `customers_id` AS id, CONCAT_WS(' ',`customers_firstname`,`customers_lastname`) AS name, `customers_email_address` AS email FROM `customers`"));
$customer_ids = getids(tep_db_query("SELECT `customers_id` AS id FROM `customers`"));
$selected_customer = '0';
$selected_customer = (!empty($_GET['id']) && in_array($_GET['id'], $customer_ids)) ? $_GET['id'] : '0';

/** Create transaction query */
$query_transactions = "SELECT * from `twispay_transactions`";
if($selected_customer!='0' || $selected_status!='0'){
  $query_transactions .= " WHERE ";
}
if($selected_customer!='0'){
    $query_transactions .= "`identifier`='" . $selected_customer . "'";
}
if($selected_status!='0'){
    if($selected_customer!='0'){
      $query_transactions .= " AND ";
    }
    $query_transactions .= "`status`='" . $selected_status . "'";
}
if($sort_col!='0' && $sort_order!='0'){
    $query_transactions .= " ORDER BY `".$sort_col."` ".$sort_order;
}else{
    $query_transactions .= " ORDER BY `date` DESC";
}

$records = (!empty(MODULE_PAYMENT_TWISPAY_PAGINATION) && is_numeric(MODULE_PAYMENT_TWISPAY_PAGINATION) && !(strpos($_POST['numar'][$i],'.')>-1 || strpos($_POST['numar'][$i],',')>-1)) ? (int)MODULE_PAYMENT_TWISPAY_PAGINATION : 20 ;
$option_page = (!empty($_GET['option_page'])) ? $_GET['option_page'] : 1;
$options_split = new splitPageResults($option_page, $records, $query_transactions, $options_query_numrows);
$transactions = getdata(tep_db_query($query_transactions));

?>
<div id="contentTwispay">
    <div class="container-fluid">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h2 class="panel-title">Twispay Transactions</h2>
                <div class="trans-filter pull-right">
                    <select class="trans-status">
                        <option value="0" <?php if($selected_status=='0'){ ?> selected="selected"<?php } ?>>All Statuses</option>
                        <?php foreach($statuses as $status) {?>
                          <option value="<?=$status?>" <?php if($selected_status==$status){ ?>selected="selected"<?php } ?> title="<?=$status?>"><?=$status?></option>
                        <?php } ?>
                    </select>
                    <select class="trans-customers">
                        <option value="0" <?php if($selected_customer=='0'){ ?> selected="selected"<?php } ?>>All Customers</option>
                        <?php
                        foreach($customers as $customer) {
                            ?>
                            <option value="<?php echo $customer->id; ?>"
                                <?php if($selected_customer==$customer->id) { ?>
                                    selected="selected"
                                <?php } ?>
                                    title="<?php echo $customer->email; ?>"><?php echo $customer->name; ?>
                            </option>
                            <?php
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="panel-body">
                <?php
                if(empty($transactions)) {
                    ?>
                    <div class="nodata">No transactions</div>
                    <?php
                } else {
                ?>
                <table class="twispay-logs" cellpading="10px" cellspacing="0" width="100%" border="1">
                    <thead>
                    <tr>
                        <th colspan="2" class="big-border">Website</th>
                        <th colspan="10">Twispay</th>
                    </tr>
                    <tr>
                        <th>User Id</th>
                        <th class="sortable big-border" data-val="orderId">Order Id</th>
                        <th>Customer Id</th>
                        <th >Order Id</th>
                        <th>Card Id</th>
                        <th class="sortable" data-val="transactionId">Transaction Id</th>
                        <th>Status</th>
                        <th>Amount</th>
                        <th>Currency</th>
                        <th class="sortable desc" data-val="date">Date</th>
                        <th>Refund Date</th>
                        <th>Refund</th>
                    </tr>
                    </thead>
                    <tbody>

                    <?php
                    foreach($transactions as $tran) {
                        ?>
                        <tr>
                            <td><?php echo $tran->identifier; ?></td>
                            <td class="big-border"><?php echo $tran->order_id; ?></td>
                            <td><?php echo $tran->customerId; ?></td>
                            <td><?php echo $tran->orderId; ?></td>
                            <td><?php echo $tran->cardId; ?></td>
                            <td><?php echo $tran->transactionId; ?></td>
                            <td><?php echo $tran->status; ?></td>
                            <td><?php echo $tran->amount; ?></td>
                            <td><?php echo $tran->currency; ?></td>
                            <td><?php echo $tran->date; ?></td>
                            <td><?php if($tran->status==Twispay_Status_Updater::$RESULT_STATUSES['REFUND_REQUESTED']){ echo $tran->refund_date; }?></td>
                            <td><?php if($tran->status==Twispay_Status_Updater::$RESULT_STATUSES['COMPLETE_OK']){ ?>
                                    <img src="images/icons/cross.gif" class="refund fa fa-times red"
                                         aria-hidden="true"
                                         data-customerid="<?php echo $tran->identifier; ?>"
                                         data-transid="<?php echo $tran->transactionId; ?>"
                                         data-orderid="<?php echo $tran->order_id; ?>"
                                         data-store="<?php echo $tran->store_id; ?>"
                                         data-sendto="<?php echo $tran->sendto; ?>"
                                         data-billto="<?php echo $tran->billto; ?>" />
                                <?php } ?>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                </table>
                <div class="twispay-pagination">
                    <?php
                    echo $options_split->display_links($options_query_numrows, $records, MAX_DISPLAY_PAGE_LINKS, $option_page, remove_query($_SERVER['QUERY_STRING'],"option_page"), 'option_page');
                    }?>
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        /** FILTERS START */
        /** Change listener for filters - customer selector */
        $(document).on('change','select.trans-customers',function(){
          var f_val = $(this).val();
          window.location.href = updateQueryStringParameter(window.location.href, "id", f_val);
        });

        /** Change listener for filters - status selector */
        $(document).on('change', 'select.trans-status', function() {
          var f_val = $(this).val();
          window.location.href = updateQueryStringParameter(window.location.href, "f_status", f_val);
        });

        /** Read sort parameter from GET and set the buttons state */
        $(function() {
          var GET_sort = $.urlParam('sort');
          var GET_sort_order = "";
          var GET_sort_field = "";
          var current_sort_th = "";
          /** Parse sort value.
           *  correct: sort = field-name_sort-order
           */
          if (GET_sort) {
            GET_sort_order = GET_sort.split("_")[1];
            GET_sort_field = GET_sort.split("_")[0];
            current_sort_th = $('th.sortable[data-val=' + GET_sort_field + ']');
          }
          if (current_sort_th) {
            $('th.sortable').removeClass("asc").removeClass("desc");
            if (GET_sort_order == "ASC") {
              current_sort_th.addClass("asc");
            } else if (GET_sort_order == "DESC") {
              current_sort_th.addClass("desc");
            }
          }
        })

        /** Write sort parameter to GET and set the buttons state */
        $('th.sortable').click(function() {
          /** Read from uri*/
          var GET_sort = $.urlParam('sort');
          var GET_sort_order = "";
          var GET_sort_field = "";
          if (GET_sort) {
            GET_sort_order = GET_sort.split("_")[1];
            GET_sort_field = GET_sort.split("_")[0];
          }
          /** Update the values*/
          var current_name = $(this).attr("data-val");
          if (current_name == GET_sort_field && GET_sort_order == "ASC") {
            current_order = "DESC";
          } else {
            current_order = "ASC";
          }
          /** Reload the page with the new value for sort parameter */
          window.location.href = updateQueryStringParameter(window.location.href, "sort", current_name + '_' + current_order);
        })

          /** Add a GET parameter into a URI string or update it if already exists
           *
           * @param string uri - jQuery selector for button element
           *        string key - GET parameter key
           *        string value - GET parameter value
           *
           * @return string - the new uri address
           */
          function updateQueryStringParameter(uri, key, value) {
            var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
            var separator = uri.indexOf('?') !== -1
              ? "&"
              : "?";
            if (uri.match(re)) {
              return uri.replace(re, '$1' + key + "=" + value + '$2');
            } else {
              return uri + separator + key + "=" + value;
            }
          }

          /** Read the GET parameter value by key
           *
           * @param string key - the key of the element to be returned
           *
           * @return string - the element value
           */
          $.urlParam = function(key) {
            var results = new RegExp('[\?&]' + key + '=([^&#]*)').exec(window.location.href);
            if (results == null) {
              return null;
            }
            return decodeURI(results[1]) || 0;
          }
        /** FILTERS STOP */

        /** REFUND START */
        /** Click listener for refund button */
        $(document).on('click', 'img.refund', function(){
            /** Read button attribute */
            var transid = $(this).attr('data-transid');
            var parent = $(this).parents('tr');
            $(parent).css('opacity','0.2');
            /** Endpoint URL */
            var refund = window.location.pathname+'/twispay_actions.php';
            setTimeout(function(){
                /** user confirmation popup */
                if(window.confirm("Are you sure you want to refund transaction #"+transid+ " ?\nProcess is not reversible !!!")){
                    $(parent).css('opacity','1');
                    $.ajax({
                        url: refund,
                        dataType: 'json',
                        type: 'post',
                        /** ajax request parameters */
                        data: {'transid':transid, 'action': 'refund'},
                        /** if ajax call succeeded */
                        success: function(data){
                            if(data['refunded'] == 1){
                                alert("Successfully refunded");
                                window.location.reload(true);
                            } else {
                                /** if ajax call failed */
                                alert(data['status']);
                            }
                        },
                        /** if ajax call failed */
                        error: function(xhr, ajaxOptions, thrownError) {
                            $(parent).css('opacity', '1');
                            var err = thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText;
                            alert(err);
                            console.log(err);
                        }
                    });
                /** if user deny popup confirmation */
                } else {
                    $(parent).css('opacity','1');
                }
            },50);
        });
    </script>
    <style>
        #contentTwispay{
            margin-left: 20px;
            padding-right: 20px;
        }
        .big-border{
            border-right: 4px solid #a7a7a7;
        }
        i.refund{
            font-size: 20px;
        }
        table.twispay-logs tr:nth-child(even) td{
            background-color: #ffffff;
        }
        table.twispay-logs tr:nth-child(odd) td{
            background-color: #f5f5f5;
        }
        table.twispay-logs td{
            text-align: center;
            padding: 4px;
        }
        table.twispay-logs th{
            text-align: center;
            padding: 4px;
        }
        .red{
            color:#dd0000;
            cursor: pointer;
        }
        i.refund{
            background-image: url("images/icons/cross.gif");
        }
        div#contentTwispay div.twispay-pagination,  div#contentTwispay div.trans-filter {
            text-align: right;
            padding: 20px;
        }
        .sortable{
          color: #1e91cf;
          cursor: pointer;
        }
        .sortable:hover,.sortable:focus{
          color: #14628c;
        }
        .sortable.asc::after,.sortable.desc::after{
          content: '';
          position: relative;
          display: inline-block;
          border: solid #1e91cf;
          border-width: 0 2px 2px 0;
          padding: 3px;
          margin-left: 5px;
        }
        .sortable.desc::after{
          transform: rotate(45deg);
          -webkit-transform: rotate(45deg);
          top: -2px;
        }
        .sortable.asc::after{
          transform: rotate(-135deg);
          -webkit-transform: rotate(-135deg);
          top: 2px;
        }
    </style>
</div>

<?php
require(DIR_WS_INCLUDES . 'template_bottom.php');
require(DIR_WS_INCLUDES . 'application_bottom.php');
?>
