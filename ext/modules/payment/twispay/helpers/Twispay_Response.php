<?php
/**
 * Twispay Helpers
 *
 * Decodes and validates notifications sent by the Twispay server.
 *
 * @author   Twistpay
 * @version  1.0.1
 */

require_once(DIR_FS_CATALOG.'/ext/modules/payment/twispay/helpers/Twispay_Status_Updater.php');

/* Security class check */
if (! class_exists('Twispay_Response')) :
    /**
     * Class that implements methods to decrypt
     * Twispay server responses.
     */
    class Twispay_Response
    {
        /**
         * Decrypt the response from Twispay server.
         *
         * @param string $tw_encryptedMessage - The encripted server message.
         * @param string $tw_secretKey        - The secret key (from Twispay).
         *
         * @return Array([key => value,]) - If everything is ok array containing the decrypted data.
         *         bool(FALSE)            - If decription fails.
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
            $decryptedResponse['externalOrderId'] = explode('_', $decryptedResponse['externalOrderId'])[0];
            $decryptedResponse['cardId'] = (!empty($decryptedResponse['cardId'])) ? ($decryptedResponse['cardId']) : (0);

            return $decryptedResponse;
        }

        /**
         * Function that validates a decripted response.
         *
         * @param tw_response The server decripted and JSON decoded response
         * @param that Controller instance use for accessing runtime values like configuration, active language, etc.
         *
         * @return bool(FALSE)     - If any error occurs
         *         bool(TRUE)      - If the validation is successful
         */

        public static function checkValidation($tw_response)
        {
            $tw_errors = array();

            if (!$tw_response) {
                return false;
            }

            if (empty($tw_response['status']) && empty($tw_response['transactionStatus'])) {
                $tw_errors[] = LOG_ERROR_EMPTY_STATUS_TEXT;
            }

            if (empty($tw_response['identifier'])) {
                $tw_errors[] = LOG_ERROR_EMPTY_IDENTIFIER_TEXT;
            }

            if (empty($tw_response['externalOrderId'])) {
                $tw_errors[] = LOG_ERROR_EMPTY_EXTERNAL_TEXT;
            }

            if (empty($tw_response['transactionId'])) {
                $tw_errors[] = LOG_ERROR_EMPTY_TRANSACTION_TEXT;
            }

            if (sizeof($tw_errors)) {
                foreach ($tw_errors as $err) {
                    Twispay_Logger::log($err);
                }
                return false;
            } else {
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
                      , 'timestamp'       => $tw_response['timestamp']];

                Twispay_Transactions::insertTransaction($data);
                Twispay_Logger::log(LOG_OK_RESPONSE_DATA_TEXT . json_encode($data));

                // TODO
                if (!in_array($data['status'], Twispay_Status_Updater::$RESULT_STATUSES)) {
                    Twispay_Logger::log(LOG_ERROR_WRONG_STATUS_TEXT . $data['status']);
                    return false;
                }

                Twispay_Logger::log(LOG_OK_VALIDATING_COMPLETE_TEXT . $data['id_cart']);
                return true;
            }
        }
    }
endif; /* End if class_exists. */
