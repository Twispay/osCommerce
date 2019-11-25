/**
 * @author   Twispay
 * @version  1.0.1
 */

/** REFUND OPERATION START */
$(function() {
  $.each($('input[name="amount"]'), function(i, v) {
    var td = $(v).parents('td');
    var refunded_amount = parseFloat(td.attr('data-refunded-amount'));
    var total_amount = parseFloat(td.attr('data-trans-amount'));
    $(v).val((total_amount - refunded_amount).toFixed(2));
  })
})

/** Click listener for refund button */
$(document).on('click', 'img.refund', function() {
  /** Read button attribute */
  var parent = $(this).parents('tr');
  var td = $(this).parents('td');

  var transid = td.attr('data-transid');
  var refunded_amount = parseFloat(td.attr('data-refunded-amount'));
  var total_amount = parseFloat(td.attr('data-trans-amount'));
  var input_amount = parseFloat(parent.find('input[name="amount"]').val());

  if (input_amount > 0 && input_amount < total_amount) {
    var refund_amount = input_amount;
  } else if (input_amount >= total_amount || !parent.find('input[name="amount"]').val()) {
    var refund_amount = (total_amount - refunded_amount);
  } else {
    alert(td.attr('data-amount-message'));
    return false;
  }

  $(parent).css('opacity', '0.2');
  $(parent).addClass('disabled');

  /** Endpoint URL */
  var actionURL = window.location.pathname + '/twispay_actions.php';
  setTimeout(function() {
    /** user confirmation popup */
    if (window.confirm(td.attr('data-popup-message'))) {
      $(parent).css('opacity', '1');
      $(parent).removeClass('disabled');
      $.ajax({
        url: actionURL,
        dataType: 'json',
        type: 'post',
        /** ajax request parameters */
        data: {
          'transid': transid,
          'amount': refund_amount.toFixed(2),
          'action': 'refund'
        },
        /** if ajax call succeeded */
        success: function(data) {
          if (data['refunded'] == 1) {
            alert(data['message']);
            window.location.reload(true);
          } else {
            /** if ajax call failed */
            alert(data['message']);
          }
        },
        /** if ajax call failed */
        error: function(xhr, ajaxOptions, thrownError) {
          $(parent).css('opacity', '1');
          $(parent).removeClass('disabled');
          var err = thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText;
          alert(err);
        }
      });
      /** if user deny popup confirmation */
    } else {
      $(parent).css('opacity', '1');
      $(parent).removeClass('disabled');
    }
  }, 50);
});
/** REFUND OPERATION STOP */

/** SYNC ALL RECURRINGS OPERATION START */
/** Click listener for sync all button */
$(document).on('click', '.twispay-sync', function() {
  var location = $(this).attr('data-location');
  var that = this;
  /** Endpoint URL + GET parameters */
  var actionURL = location + 'ext/modules/payment/twispay/twispay_actions.php';
  /** Preload the button */
  buttonLoadingState($(that));
  setTimeout(function() {
    $.ajax({
      url: actionURL,
      dataType: 'json',
      type: 'post',
      /** ajax request parameters */
      data: {
        'action': 'sync'
      },
      success: function(data) {
        /** if ajax call succeeded */
        if (data.status == 'Success') {
          alert('Successful synced! - ' + data.synced + ' orders');
          buttonDefaultState($(that));
          window.location.reload();
        } else {
          buttonDefaultState($(that));
          alert(data.status);
        }
      },
      /** if ajax call failed */
      error: function(xhr, ajaxOptions, thrownError) {
        buttonDefaultState($(that));
        alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
      }
    });
  }, 50);
});
/** SYNC ALL RECURRINGS OPERATION STOP */

/** Helper functions
 * Set the loading state for button
 *
 * @param button - jQuery selector for button element
 */
function buttonLoadingState(button) {
  button.attr('disabled', 'disabled').addClass('disabled');
  button.text(button.attr('data-loading-text'));
}

/** Set the normal state for button
 *
 * @param button - jQuery selector for button element
 */
function buttonDefaultState(button) {
  button.removeAttr("disabled").removeClass('disabled');
  button.text(button.attr('data-default-text'));
}
