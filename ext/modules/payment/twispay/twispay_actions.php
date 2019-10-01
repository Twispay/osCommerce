<?php

chdir('../../../../');
require('includes/application_top.php');
require('includes/modules/payment/twispay.php');

$tw = new twispay();
$tw->log_file = $tw->log_path.'/twispay_refunds.log';

if(file_exists($tw->log_file) && filesize($tw->log_file) > 2097152){
    @file_put_contents($tw->log_file, PHP_EOL.PHP_EOL);
}
switch($_POST['action']){
    case 'refund':
        if(empty($_POST['transid']) || empty($_POST['orderid']) || empty($_POST['customerid']) || empty($_POST['sendto']) || empty($_POST['billto']) || empty($_POST['action'])){
            die("NO POST 1");
        }
        echo $result = $tw->refund($_POST['transid'], $_POST['orderid'], $_POST['customerid'], $_POST['sendto'], $_POST['billto']);
        break;
    case 'clean':
        echo $tw->delete_unpaid();
        break;
}


?>