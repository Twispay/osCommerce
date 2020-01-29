/**
 * @author   Twistpay
 * @version  1.0.1
 */

/** Function that call via ajax the action that cleans unpaid orders */
function clean(){
    /** Get the current URL: */
    var currentLocation = window.location.pathname;
    /** Get action URL: */
    var url = currentLocation.substring(0, currentLocation.lastIndexOf('/')+1)+'ext/modules/payment/twispay/twispay_actions.php';
    setTimeout(function(){
        /** user confirmation popup */
        if(window.confirm("Are you sure you want to delete unfinished twispay payments?\nProcess is not reversible !!!")){
            $.ajax({
                url: url,
                dataType: 'json',
                type: 'post',
                /** ajax request parameters */
                data: {'action': 'clean'},
                /** if ajax call succeeded */
                success: function(data){
                    alert(data+' records deleted');
                },
                /** if ajax call failed */
                error: function(xhr, ajaxOptions, thrownError) {
                    console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                }
            });
        }
    },50);
}
