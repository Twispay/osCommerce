<?php
    chdir('../../../../');
    require('includes/application_top.php');
    require('includes/modules/payment/twispay.php');
    
    $tw = new twispay();
    $tw->log_file = $tw->log_path.'/twispay.log';
    $page_to_redirect = $tw->redirect_page();
    if(file_exists($tw->log_file) && filesize($tw->log_file) > 2097152){
        @file_put_contents($tw->log_file, PHP_EOL.PHP_EOL);
    }
    
    if(!empty($_POST)){
        $tw->prn("Processing ...");
        sleep(2);
        $datas = (!empty($_POST['opensslResult'])) ? json_decode($tw->twispayDecrypt($_POST['opensslResult'])) : json_decode($tw->twispayDecrypt($_POST['result']));
        if(!empty($datas)){
            $tw->twispay_log('[INFO]: Decrypted response:');
            $tw->twispay_log(json_encode($datas));
            $result = $tw->checkValidation($datas);
            if(!empty($result)){
                if(empty($tw->checkTransaction($result->transactionId))){
                    $tw->loggTransaction($result);
                    $tw->success_process($result->order_id, $result->sendto, $result->billto,$result->transactionId, $result->comments);
                } else {
                    $tw->twispay_log(sprintf('[RESPONSE ERROR]: Transaction #%s already exists', $result->transactionId));
                    $tw->twispay_log();
                    $tw->reset_cart();
                }
            } else {
                $tw->twispay_log('[RESPONSE ERROR]: No result data');
                $tw->twispay_log();
            }
        } else {
            $tw->twispay_log("[RESPONSE ERROR] : no datas ");
            $tw->twispay_log();
        }
    } else {
        $tw->twispay_log("NO POST");
    }
    echo '<meta http-equiv="refresh" content="1;url='. $page_to_redirect.'" />';
    header('Refresh: 1;url=' . $page_to_redirect);
?>