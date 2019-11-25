/**
 * @author   Twispay
 * @version  1.0.1
 */

/** List of listener that is visible from both the catalog and the admin sides*/

/** CANCEL OPERATION START */
/** Click listener for cancel button */
$(document).on('click', '.cancel_subscription', function(e) {
  e.stopPropagation();
  e.preventDefault();
  /** Read button attribute */
  var button = $(this);
  var td = $(this)

  var orderid = td.attr('data-orderid');
  var tworderid = td.attr('data-tworderid');
  var actionsURL = td.attr('data-admin-dir');

  $(button).css('opacity', '0.5');
  $(button).addClass('disabled');

  var cancel = actionsURL;

  setTimeout(function() {
    /** user confirmation popup */
    if (window.confirm(td.attr('data-popup-message'))) {
      $(button).css('opacity', '1');
      $(button).removeClass('disabled');
      $.ajax({
        url: cancel,
        dataType: 'json',
        type: 'post',
        /** ajax request parameters */
        data: {
          'orderid': orderid,
          'tworderid': tworderid,
          'action': 'cancel'
        },
        success: function(data) {
          /** if ajax call succeeded */
          if (data['canceled'] == 1) {
            alert(data['message']);
            window.location.reload(true);
          } else {
            /** if ajax call failed */
            alert(data['message']);
          }
        },
        /** if ajax call failed */
        error: function(xhr, ajaxOptions, thrownError) {
          $(button).css('opacity', '1');
          var err = thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText;
          alert(err);
        }
      });
    } else {
      /** Enable the button */
      $(button).css('opacity', '1');
      $(button).removeClass('disabled');
    }
  }, 50);
});
/** CANCEL OPERATION STOP */
