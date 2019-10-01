<?php
chdir('../../../../');
require('includes/application_top.php');
$languages = tep_get_languages();
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

function getids($resource){
    $data = array();
    if(!empty(tep_db_num_rows($resource))){
        while ($dat = tep_db_fetch_array($resource)){
            array_push($data, $dat['id']);
        }
    }
    return $data;
}
function getdata($resource){
    $data = array();
    if(!empty(tep_db_num_rows($resource))){
        while ($dat = tep_db_fetch_array($resource)){
            array_push($data, json_decode(json_encode($dat)));
        }
    }
    return json_decode(json_encode($data));
}
/* GET DATA*/
$customers = getdata(tep_db_query("SELECT `customers_id` AS id, CONCAT_WS(' ',`customers_firstname`,`customers_lastname`) AS name, `customers_email_address` AS email FROM `customers`"));
$customer_ids = getids(tep_db_query("SELECT `customers_id` AS id FROM `customers`"));

$selected = (!empty($_GET['id']) && in_array($_GET['id'], $customer_ids)) ? $_GET['id'] : '0';
$query_transactions = "SELECT * from `twispay_transactions`";
if(!empty($selected)){
    $query_transactions .= " WHERE `identifier`='" . $selected . "'";
}
$query_transactions .= " ORDER BY `date` DESC";
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
                    <select class="trans-customers">
                        <option value="0" <?php if($selected=='0'){ ?> selected="selected"<?php } ?>>All Customers</option>
                        <?php
                        foreach($customers as $customer) {
                            ?>
                            <option value="<?php echo $customer->id; ?>"
                                <?php if($selected==$customer->id) { ?>
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
                        <th class="big-border">Order Id</th>
                        <!--                                    <th class="big-border">Invoice Id</th>-->

                        <th>Customer Id</th>
                        <th>Order Id</th>
                        <th>Card Id</th>
                        <th>Transaction Id</th>
                        <th>Status</th>
                        <th>Amount</th>
                        <th>Currency</th>
                        <th>Date</th>
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
                            <!--                                        <td class="big-border">--><?php //echo $tran->invoice; ?><!--</td>-->


                            <td><?php echo $tran->customerId; ?></td>
                            <td><?php echo $tran->orderId; ?></td>
                            <td><?php echo $tran->cardId; ?></td>
                            <td><?php echo $tran->transactionId; ?></td>
                            <td><?php echo $tran->status; ?></td>
                            <td><?php echo $tran->amount; ?></td>
                            <td><?php echo $tran->currency; ?></td>
                            <td><?php echo $tran->date; ?></td>
                            <td><?php if($tran->status=='refunded'){ echo $tran->refund_date; }?></td>
                            <td><?php if($tran->status=='complete-ok'){ ?>
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

                    echo $options_split->display_links($options_query_numrows, $records, MAX_DISPLAY_PAGE_LINKS, $option_page, 'id=' . $selected, 'option_page');
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        var redir="<?php echo $_SERVER['PHP_SELF']?>";
        $(document).on('change','select.trans-customers',function(){
            var encoded = redir+'?id='+$(this).val();
            var decoded = encoded.replace(/&amp;/g, '&');
            window.location.href = decoded;
        });
        $(document).on('click', 'img.refund', function(){
            var transid = $(this).attr('data-transid');
            var orderid = $(this).attr('data-orderid');
            var storeid = $(this).attr('data-store');
            var parent = $(this).parents('tr');
            var customerid = $(this).attr('data-customerid');
            var sendto = $(this).attr('data-sendto');
            var billto = $(this).attr('data-billto');
            $(parent).css('opacity','0.5');
            var refund = '/ext/modules/payment/twispay/twispay_actions.php';
            setTimeout(function(){
                if(window.confirm("Are you sure you want to refund transaction #"+transid+ " ?\nProcess is not reversible !!!")){
                    $(parent).css('opacity','1');
                    $.ajax({
                        url: refund,
                        dataType: 'json',
                        type: 'post',
                        data: {'transid':transid, 'orderid': orderid, 'customerid': customerid, 'sendto': sendto, 'billto': billto, 'action': 'refund'},
                        success: function(data){
                            console.log(data);
                            if(data == 'OK'){
                                alert("Successfully refunded");
                                window.location.reload(true);
                            } else {
                                alert(data);
                            }
                        },
                        error: function(xhr, ajaxOptions, thrownError) {
                            console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                        }
                    });
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
    </style>

</div>
<?php
require(DIR_WS_INCLUDES . 'template_bottom.php');
require(DIR_WS_INCLUDES . 'application_bottom.php');
?>
