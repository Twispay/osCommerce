<?php
/**
 * Twispay Helpers
 *
 * Redirects user to the thank you page.
 *
 * @author   Twispay
 * @version  1.0.1
 */

/* Security class check */
if (! class_exists('Twispay_Thankyou')) :
    /**
     *Class that redirects user to the thank you page.
     */
    class Twispay_Thankyou
    {
        /**
         * Redirect to page
         *
         * @param $page: page url - Ex: index.php?route=checkout/cart.
         *
         * @return void
         */
        public static function redirect($page = '')
        {
            if (empty(trim($page))) {
                $page_to_redirect = FILENAME_CHECKOUT_SUCCESS;
            } else {
                $page_to_redirect = trim($page);
                if (stripos($page_to_redirect, '/') === 0) {
                    $page_to_redirect = substr($page_to_redirect, 1);
                }
            }
            $page_to_redirect = tep_href_link($page_to_redirect, '', 'SSL');
            echo '<meta http-equiv="refresh" content="1;url='. $page_to_redirect.'" />';
            header('Refresh: 1;url=' . $page_to_redirect);
        }
    }
endif; /* End if class_exists. */
