<?php
/**
 * Twispay Helpers
 *
 * Logs messages and transactions.
 *
 * @author   Twispay
 * @version  1.0.1
 */

/* Security class check */
if (! class_exists('Twispay_Logger')) :
    /**
     * Class that implements methods to log
     * messages and transactions.
     */
    class Twispay_Logger
    {
        public static $DIR_LOGS = DIR_FS_CATALOG.'ext/modules/payment/twispay/logs/';

        /**
         * Attempts to create the directory specified by pathname.
         *
         * @param string path - The logs directory path.
         *
         * @return boolean - true / false
         *
         */
        public static function makeLogDir($path = false)
        {
            if (!$path) {
                $path = self::$DIR_LOGS;
            }
            return is_dir($path) || mkdir($path);
        }

        /**
         * Recursively removes directory and its content
         *
         * @param string path - The logs directory path.
         *
         * @return boolean - TRUE / FALSE
         *
         */
        public static function delLogDir($path = false)
        {
            if (!$path) {
                $path = self::$DIR_LOGS;
            }
            $files = array_diff(scandir($path), array('.', '..'));

            foreach ($files as $file) {
                (is_dir("$path/$file")) ? delLogDir("$path/$file") : unlink("$path/$file");
            }

            return rmdir($path);
        }

        /**
         * Function that logs a message to the transaction log file.
         *
         * @param string - Message to be logged.
         *
         * @return void
         *
         */
        public static function log($message = false)
        {
            $log_file = self::$DIR_LOGS.'transactions.log';
            /* Build the log message. */
            $message = (!$message) ? (PHP_EOL . PHP_EOL) : ("[" . date('Y-m-d H:i:s') . "] " . $message);
            /* Try to append log to file and silence and PHP errors may occur. */
            @file_put_contents($log_file, $message . PHP_EOL, FILE_APPEND);
        }

        /**
         * Function that logs a message to the requests log file.
         *
         * @param string - Message to be logged.
         *
         * @return void
         *
         */
        public static function api_log($message = false)
        {
            $log_file = self::$DIR_LOGS.'requests.log';
            /* Build the log message. */
            $message = (!$message) ? (PHP_EOL . PHP_EOL) : ("[" . date('Y-m-d H:i:s') . "] " . $message);
            /* Try to append log to file and silence and PHP errors may occur. */
            @file_put_contents($log_file, $message . PHP_EOL, FILE_APPEND);
        }
    }
endif; /* End if class_exists. */
