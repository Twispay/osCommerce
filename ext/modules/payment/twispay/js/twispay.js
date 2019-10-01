$(window).load(function(){
    var url = new URL(window.location.href);
    var action = url.searchParams.get("action");
    var module = url.searchParams.get("module");
    var delint1 = false;
    var delint2 = false;
    var delint3 = false;
    var delint4 = false;
    if(module == 'twispay' && action == 'edit'){
        var disable_interval = setInterval(function(){
            console.log('interval');
            var select1 = $(document).find('select[name="configuration[MODULE_PAYMENT_TWISPAY_PREPARE_ORDER_STATUS_ID]"]');
            if ($(select1).length > 0){
                $(select1).attr('disabled','disabled');
                delint1 = true;
            }
            var select1 = $(document).find('select[name="configuration[MODULE_PAYMENT_TWISPAY_ORDER_STATUS_ID]"]');
            if ($(select1).length > 0){
                $(select1).attr('disabled','disabled');
                delint2 = true;
            }
            var select1 = $(document).find('select[name="configuration[MODULE_PAYMENT_TWISPAY_REFUND_ORDER_STATUS_ID]"]');
            if ($(select1).length > 0){
                $(select1).attr('disabled','disabled');
                delint3 = true;
            }
            var select1 = $(document).find('input[name="configuration[MODULE_PAYMENT_TWISPAY_S2S]"]');
            if ($(select1).length > 0){
                textbox = $(document.createElement('textarea')).attr('name',"configuration[MODULE_PAYMENT_TWISPAY_S2S]").empty().append($(select1).val());
                $(select1).replaceWith(textbox);
                $(textbox).attr('readonly','readonly').attr('rows','4');
                delint4 = true;
            }


            if(delint1 === true && delint2 === true && delint3 === true && delint4 === true){
                clearInterval(disable_interval);
            }
        },500);
    }

});

function logs(){
    console.log('log');
}
function clean(){
    var url = '/ext/modules/payment/twispay/twispay_actions.php'
    setTimeout(function(){
        if(window.confirm("Are you sure you want to delete unfinished twispay payments?\nProcess is not reversible !!!")){
            $.ajax({
                url: url,
                dataType: 'json',
                type: 'post',
                data: {'action': 'clean'},
                success: function(data){
                    console.log(data);
                    alert(data+' records deleted');
                },
                error: function(xhr, ajaxOptions, thrownError) {
                    console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                }
            });
        } else {
            $(parent).css('opacity','1');
        }
    },50);
}