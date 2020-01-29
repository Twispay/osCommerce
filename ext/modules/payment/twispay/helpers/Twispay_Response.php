<?php
/**
 * Twispay Helpers
 *
 * Decodes and validates notifications sent by the Twispay server.
 *
 * @author   Twispay
 * @version  1.0.1
 */

require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Encoder.php');
require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Status_Updater.php');

/* Security class check */
if (! class_exists('Twispay_Response')) :
    /**
     * Class that implements methods to decrypt Twispay server responses.
     */
    class Twispay_Response
    {
        /**
         * Decrypt the response from Twispay server.
         *
         * @param string $tw_encryptedMessage - The encripted server message.
         * @param string $tw_secretKey        - The secret key (from Twispay).
         *
         * @return bool(false) - If decription fails.
         *         array([key => value]) - If everything is ok array containing the decrypted data.
         *
         */
        public static function decryptMessage($tw_encryptedMessage, $tw_secretKey)
        {
            $encrypted = ( string )$tw_encryptedMessage;

            if (!strlen($encrypted) || (false == strpos($encrypted, ','))) {
                return false;
            }

            /* Get the IV and the encrypted data */
            $encryptedParts = explode(/*delimiter*/',', $encrypted, /*limit*/2);
            $iv = base64_decode($encryptedParts[0]);
            if (false === $iv) {
                return false;
            }

            $encryptedData = base64_decode($encryptedParts[1]);
            if (false === $encryptedData) {
                return false;
            }

            /* Decrypt the encrypted data */
            $decryptedResponse = openssl_decrypt($encryptedData, /*method*/'aes-256-cbc', $tw_secretKey, /*options*/OPENSSL_RAW_DATA, $iv);

            if (false === $decryptedResponse) {
                return false;
            }

            /* JSON decode the decrypted data. */
            $decryptedResponse = json_decode($decryptedResponse, /*assoc*/true, /*depth*/4);

            /* Normalize values */
            $decryptedResponse['status'] = (empty($decryptedResponse['status'])) ? ($decryptedResponse['transactionStatus']) : ($decryptedResponse['status']);
            /** Check if externalOrderId uses '_' separator */
            if (strpos($decryptedResponse['externalOrderId'], '_') !== false) {
                $explodedVal = explode('_', $decryptedResponse['externalOrderId'])[0];
                /** Check if externalOrderId contains only digits and is not empty */
                if (!empty($explodedVal) && ctype_digit($explodedVal)) {
                    $decryptedResponse['externalOrderId'] = $explodedVal;
                }
            }
            $decryptedResponse['cardId'] = (!empty($decryptedResponse['cardId'])) ? ($decryptedResponse['cardId']) : (0);

            return $decryptedResponse;
        }

        /**
         * Function that validates a decrypted response.
         *
         * @param tw_response The server decripted and JSON decoded response
         * @param that Controller instance use for accessing runtime values like configuration, active language, etc.
         *
         * @return bool(false) - If any error occurs
         *         array([key => value]) - Validated decrypted data.
         *
         */

        public static function checkValidation($tw_response)
        {
            $tw_errors = array();

            if (!$tw_response) {
                return false;
            }
            /** Check if transaction status exists */
            if (empty($tw_response['status']) && empty($tw_response['transactionStatus'])) {
                $tw_errors[] = LOG_ERROR_EMPTY_STATUS_TEXT;
            }
            /** Check if identifier exists */
            if (empty($tw_response['identifier'])) {
                $tw_errors[] = LOG_ERROR_EMPTY_IDENTIFIER_TEXT;
            }
            /** Check if external order id exists */
            if (empty($tw_response['externalOrderId'])) {
                $tw_errors[] = LOG_ERROR_EMPTY_EXTERNAL_TEXT;
            }
            /** Check if transaction id exists */
            if (empty($tw_response['transactionId'])) {
                $tw_errors[] = LOG_ERROR_EMPTY_TRANSACTION_TEXT;
            }
            /** Check if status is valid */
            if (!in_array($tw_response['status'], Twispay_Status_Updater::$RESULT_STATUSES)) {
                $tw_errors[] = LOG_ERROR_WRONG_STATUS_TEXT . $tw_response['status'];
            }
            /** Check for error and log them all */
            if (sizeof($tw_errors)) {
                foreach ($tw_errors as $err) {
                    Twispay_Logger::log($err);
                }
                return false;
            /** If the response is valid */
            } else {
                /** Prepare the data object related to transaction table format */
                $data = ['order_id'         => (int)$tw_response['externalOrderId']
                        , 'status'          => $tw_response['status']
                        , 'invoice'         => ''
                        , 'identifier'      => $tw_response['identifier']
                        , 'customerId'      => (int)$tw_response['customerId']
                        , 'orderId'         => (int)$tw_response['orderId']
                        , 'cardId'          => (int)$tw_response['cardId']
                        , 'transactionId'   => (int)$tw_response['transactionId']
                        , 'transactionKind' => $tw_response['transactionKind']
                        , 'amount'          => (float)$tw_response['amount']
                        , 'currency'        => $tw_response['currency']
                        , 'timestamp'       => $tw_response['timestamp']
                        ];

                /** Insert the new transaction */
                Twispay_Logger::log(LOG_OK_RESPONSE_DATA_TEXT . json_encode($data));
                Twispay_Logger::log(LOG_OK_VALIDATING_COMPLETE_TEXT . $data['order_id']);
                return $data;
            }
        }
    }
endif; /* End if class_exists. */
