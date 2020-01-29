/**
 * @author   Twispay
 * @version  1.0.1
 */

/** SORT/FILTERS START */
/**
 * Change listener for filters - customer selector.
 * Add customer filter to session.
 */
$(document).on('change', 'select.trans-customers', function() {
  var f_val = $(this).val();
  window.location.href = updateQueryStringParameter(window.location.href, "id", f_val);
});

/**
 * Change listener for filters - status selector.
 * Add status filter to session.
 */
$(document).on('change', 'select.trans-status', function() {
  var f_val = $(this).val();
  window.location.href = updateQueryStringParameter(window.location.href, "f_status", f_val);
});

/**
 * Read sort parameter from GET and set the buttons state.
 * Add sorting to session.
 */
$(function() {
  var GET_sort = $.urlParam('sort');
  var GET_sort_order = "";
  var GET_sort_field = "";
  var current_sort_th = "";
  /**
   * Parse sort value.
   * correct: sort = field-name_sort-order
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
})

/** Add a GET parameter into a URL string or update it if already exists
 *
 * @param string url - jQuery selector for button element
 * @param string key - GET parameter key
 * @param string value - GET parameter value
 *
 * @return string - the new uri address
 */
function updateQueryStringParameter(url, key, value) {
  var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
  var separator = url.indexOf('?') !== -1 ? "&" : "?";
  if (url.match(re)) {
    return url.replace(re, '$1' + key + "=" + value + '$2');
  } else {
    return url + separator + key + "=" + value;
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
    return 0;
  }
  return decodeURI(results[1]) || 0;
}
/** SORT/FILTERS STOP */
