/**
 * @author   Twispay
 * @version  1.0.1
 */

$(function() {
  /** Triggers for hiding and showing LIVE/STAGING INPUTS */
  $(document).ready(TwispayCheckLiveOrStaging);
  $(document).on('change', 'input[name="configuration[MODULE_PAYMENT_TWISPAY_TESTMODE]"]', TwispayCheckLiveOrStaging);

  /** Function that calls via ajax the action that cleans unpaid orders */
  $(".twispay-clean").on("click", function() {
    /** Get the current URL: */
    var currentLocation = window.location.pathname;
    /** Get action URL: */
    var url = currentLocation.substring(0, currentLocation.lastIndexOf('/') + 1) + 'ext/modules/payment/twispay/twispay_actions.php';
    var that = this;
    setTimeout(function() {
      /** user confirmation popup */
      if (window.confirm($(that).attr("data-popup-message"))) {
        $.ajax({
          url: url,
          dataType: 'json',
          type: 'post',
          /** ajax request parameters */
          data: {
            'action': 'clean'
          },
          /** if ajax call succeeded */
          success: function(data) {
            alert(data);
          },
          /** if ajax call failed */
          error: function(xhr, ajaxOptions, thrownError) {
            alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
          }
        });
      }
    }, 50);
  })
})

/** Function to hide or show LIVE/STAGING inputs on module configuration page */
function TwispayCheckLiveOrStaging() {
  var radionVal = $(document).find('input[name="configuration[MODULE_PAYMENT_TWISPAY_TESTMODE]"]:checked').val();
  if (!radionVal.length) {
    return;
  }
  /** If the live mode is chacked */
  if (radionVal == "True") {
    /** Disable - Staging - Site ID / Private Key */
    $('input[name="configuration[MODULE_PAYMENT_TWISPAY_LIVE_ID]"]').attr("disabled", "disabled");
    $('input[name="configuration[MODULE_PAYMENT_TWISPAY_LIVE_KEY]"]').attr("disabled", "disabled");
    /** Enable - Live - Site ID / Private Key */
    $('input[name="configuration[MODULE_PAYMENT_TWISPAY_STAGE_ID]"]').removeAttr("disabled");
    $('input[name="configuration[MODULE_PAYMENT_TWISPAY_STAGE_KEY]"]').removeAttr("disabled");
  } else {
    /** Enable - Staging - Site ID / Private Key */
    $('input[name="configuration[MODULE_PAYMENT_TWISPAY_LIVE_ID]"]').removeAttr("disabled");
    $('input[name="configuration[MODULE_PAYMENT_TWISPAY_LIVE_KEY]"]').removeAttr("disabled");
    /** Disable - Live - Site ID / Private Key */
    $('input[name="configuration[MODULE_PAYMENT_TWISPAY_STAGE_ID]"]').attr("disabled", "disabled");
    $('input[name="configuration[MODULE_PAYMENT_TWISPAY_STAGE_KEY]"]').attr("disabled", "disabled");
  }
}
