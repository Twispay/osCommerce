<?php
/**
 * Twispay Helpers
 *
 * Print HTML notices.
 *
 * @author   Twispay
 * @version  1.0.1
 */

/* Security class check */
if (! class_exists('Twispay_Notification')) :
    /**
     * Class that prints HTML notices.
     */
    class Twispay_Notification
    {
        public static function print_notice($text = '')
        {
            ?>
          <div class="error notice" style="margin-top: 20px;">
            <h3><?= GENERAL_ERROR_TITLE_TEXT ?></h3>
            <?php if (strlen($text)) { ?>
              <span><?= $text; ?></span>
            <?php } ?>
            <?php if (!defined('MODULE_PAYMENT_TWISPAY_EMAIL') || strlen(MODULE_PAYMENT_TWISPAY_EMAIL) == 0) { ?>
              <p><?= GENERAL_ERROR_DESC_F_TEXT ?> <?= GENERAL_ERROR_DESC_CONTACT_TEXT . GENERAL_ERROR_DESC_S_TEXT ?></p>
            <?php } else { ?>
              <p><?= GENERAL_ERROR_DESC_F_TEXT ?> <a href="mailto:<?= MODULE_PAYMENT_TWISPAY_EMAIL ?>"><?= GENERAL_ERROR_DESC_CONTACT_TEXT ?></a> <?= GENERAL_ERROR_DESC_S_TEXT ?></p>
            <?php } ?>
          </div>
        <?php
        }
    }
endif; /* End if class_exists. */
