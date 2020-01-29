<?php
/**
 * @author   Twispay
 * @version  1.0.1
 */

/* ADMIN */
/* Configuration */
define('MODULE_PAYMENT_TWISPAY_TEXT_TITLE', 'Plateste in siguranta cu cardul | Twispay');
define('MODULE_PAYMENT_TWISPAY_TEXT_PUBLIC_TITLE', 'Plateste in siguranta cu cardul | Twispay');
define('MODULE_PAYMENT_TWISPAY_LOADING_TEXT', 'Se incarca...');
define('MODULE_PAYMENT_TWISPAY_CLEAR_BUTTON_TEXT', 'Sterge comenzile nefinalizate');
define('MODULE_PAYMENT_TWISPAY_SYNC_BUTTON_TEXT', 'Sincronizeaza comenzile');
define('MODULE_PAYMENT_TWISPAY_TRANSACTIONS_BUTTON_TEXT', 'Log Tranzactii');
define('MODULE_PAYMENT_TWISPAY_IMAGE_TITLE_TEXT', 'Viziteaza siteul Twispay');
define('MODULE_PAYMENT_TWISPAY_ERROR_STAGE_TEXT', 'Configurarea pentru Modul de Testare nu este completa, modulul nu va fi incarcat. Va rugam editati ID-ul si Cheia pentru Modul de Testare');
define('MODULE_PAYMENT_TWISPAY_ERROR_LIVE_TEXT', 'Configurarea pentru Modul Live nu este completa, modulul nu va fi incarcat. Va rugam editati ID-ul si Cheia pentru Modul Live');
define('MODULE_PAYMENT_TWISPAY_CLEANALL_NOTICE_TEXT', 'Sigur doriti sa stergeti toate platile nefinalizate? Procesul este ireversibil!');

/* Transactions */
define('MODULE_PAYMENT_TWISPAY_TRANSACTIONS_TITLE_TEXT', 'Tranzactii Twispay');
define('MODULE_PAYMENT_TWISPAY_ALLSTATUSES_TEXT', 'Toate Statusurile');
define('MODULE_PAYMENT_TWISPAY_ALLCUSTOMERS_TEXT', 'Toti Clientii');
define('MODULE_PAYMENT_TWISPAY_NOTRANSACTIONS_TEXT', '0 rezultate');
define('MODULE_PAYMENT_TWISPAY_WEBSITE_TEXT', 'Website');
define('MODULE_PAYMENT_TWISPAY_TWISPAY_TEXT', 'Twispay');
define('MODULE_PAYMENT_TWISPAY_USERID_TEXT', 'ID utilizator');
define('MODULE_PAYMENT_TWISPAY_ORDERID_TEXT', 'ID comanda');
define('MODULE_PAYMENT_TWISPAY_CUSTOMERID_TEXT', 'ID client');
define('MODULE_PAYMENT_TWISPAY_CARDID_TEXT', 'ID card');
define('MODULE_PAYMENT_TWISPAY_TRANSACTION_TEXT', 'Tranzactie');
define('MODULE_PAYMENT_TWISPAY_STATUS_TEXT', 'Status');
define('MODULE_PAYMENT_TWISPAY_AMOUNT_TEXT', 'Valoare');
define('MODULE_PAYMENT_TWISPAY_CURRENCY_TEXT', 'Moneda');
define('MODULE_PAYMENT_TWISPAY_DATE_TEXT', 'Data');
define('MODULE_PAYMENT_TWISPAY_REFUND_TEXT', 'Valoare refund');
define('MODULE_PAYMENT_TWISPAY_CLEAN_SUCCESS_TEXT', '%s rezultate sterse');
define('MODULE_PAYMENT_TWISPAY_REFUND_SUCCESS_TEXT', 'Rambursare s-a efectuat cu succes');
define('MODULE_PAYMENT_TWISPAY_CANCEL_SUCCESS_TEXT', 'Anularea s-a efectuat cu succes');
define('MODULE_PAYMENT_TWISPAY_REFUND_NOTICE_TEXT', 'Sunteti sigur ca doriti rambursarea tranzactiei %s? Procesul este ireversibil!');
define('MODULE_PAYMENT_TWISPAY_CANCEL_SUBSCRIPTION_NOTICE_TEXT', 'Sunteti sigur ca doriti inchiderea abonamentului? Procesul este ireversibil!');
define('MODULE_PAYMENT_TWISPAY_SYNC_NOTICE_TEXT', 'Sunteti sigur ca doriti actualizarea comenzilor?');
define('MODULE_PAYMENT_TWISPAY_SYNC_SUCCESS_TEXT', 'Sincronizare cu success!');
define('MODULE_PAYMENT_TWISPAY_CHECK_NOTICE_ADMIN_TEXT', ' Va rugam verificati aceasta problema in panoul de administrare Twispay.');
define('MODULE_PAYMENT_TWISPAY_CHECK_NOTICE_CUSTOMER_TEXT', ' Va rugam contactati administratorul site-ului.');
define('MODULE_PAYMENT_TWISPAY_REFUND_AMOUNT_NOTICE_TEXT', 'Valoare introdusa nu este valida!');
define('MODULE_PAYMENT_TWISPAY_ACCESS_ERROR_TEXT', 'Access nepermis!');
define('MODULE_PAYMENT_TWISPAY_ERROR_UNDEFINED_ID_TEXT', 'ID invalid!');
define('MODULE_PAYMENT_TWISPAY_SUBSCRIPTION_TOOMANYPRODUCTS', 'Eroare de plata Twispay: In cazul produselor recurente, comanda trebuie sa contina doar un singur abonament!');
define('MODULE_PAYMENT_TWISPAY_INVALID_SUBSCRIPTION_FREETRIAL', 'Eroare de plata Twispay: Perioada de incercare gratuita nu este suportata!');

/* CATALOG */
/* Email */
define('EMAIL_TEXT_SUBJECT', 'Comanda in curs de procesare');
define('EMAIL_TEXT_ORDER_NUMBER', 'Numar comanda:');
define('EMAIL_TEXT_INVOICE_URL', 'Factura detaliata:');
define('EMAIL_TEXT_DATE_ORDERED', 'Data comenzii:');
define('EMAIL_TEXT_PRODUCTS', 'Produse');
define('EMAIL_TEXT_SUBTOTAL', 'Sub-Total:');
define('EMAIL_TEXT_TAX', 'Taxe:        ');
define('EMAIL_TEXT_SHIPPING', 'Livrare: ');
define('EMAIL_TEXT_TOTAL', 'Total:    ');
define('EMAIL_TEXT_DELIVERY_ADDRESS', 'Adresa detaliata');
define('EMAIL_TEXT_BILLING_ADDRESS', 'Adresa de facturare');
define('EMAIL_TEXT_PAYMENT_METHOD', 'Metoda de plata');
define('EMAIL_SEPARATOR', '------------------------------------------------------');
define('TEXT_EMAIL_VIA', 'prin');

/* General */
define('PROCESSING_TEXT','Se proceseaza ...');
define('JSON_DECODE_ERROR_TEXT','Eroare decodificare JSON');
define('NO_POST_TEXT','[RASPUNS-EROARE]: lipsa_post');
define('TABLE_HEADING_SUBSCRIPTION_STATUS_TEXT','Status Abonament');
define('BUTTON_CANCEL_SUBSCRIPTION_TEXT','Inchide Abonamentul');
define('TEXT_ENABLE','Activat');
define('TEXT_DISABLE','Dezactivat');
define('TEXT_DAY','zilnic');
define('TEXT_WEEK','saptamanal');
define('TEXT_MONTH','lunar');
define('TEXT_YEAR','anual');
define('TEXT_PRODUCTS_CUSTOM_RECURRING_STATUS','Status abonament:');
define('TEXT_PRODUCTS_CUSTOM_RECURRING_DURATION','Durata abonament (de cate ori sa se repete plata)):');
define('TEXT_PRODUCTS_CUSTOM_RECURRING_CYCLE','Cicluri abonament:');
define('TEXT_PRODUCTS_CUSTOM_RECURRING_FREQUENCY','Frecventa abonament:');
define('TEXT_PRODUCTS_CUSTOM_TRIAL_STATUS','Status perioada de incercare:');
define('TEXT_PRODUCTS_CUSTOM_TRIAL_CYCLE','Cicluri perioada de incercare:');
define('TEXT_PRODUCTS_CUSTOM_TRIAL_FREQUENCY','Frecventa perioada de incercare:');
define('TEXT_PRODUCTS_CUSTOM_TRIAL_PRICE','Pret perioada de incercare:');

define('GENERAL_ERROR_TITLE_TEXT','A aparut o eroare:');
define('GENERAL_ERROR_DESC_F_TEXT','Plata nu a putut fi procesata. Va rugam ');
define('GENERAL_ERROR_DESC_CONTACT_TEXT',' contactati');
define('GENERAL_ERROR_DESC_S_TEXT',' administratorul siteului.');
define('GENERAL_ERROR_HOLD_NOTICE_TEXT',' Plata este in asteptare.');
define('GENERAL_ERROR_INVALID_ORDER_TEXT',' Comanda invalida.');

/* Order Notice */
define('ORDER_FAILED_NOTICE_TEXT','Plata Twispay a esuat');
define('ORDER_HOLD_NOTICE_TEXT','Plata Twispay este in asteptare');
define('ORDER_VOID_NOTICE_TEXT','Plata Twispay a fost anulata #');
define('ORDER_CHARGEDBACK_NOTICE_TEXT','Plata Twispay a fost returnata #');
define('ORDER_REFUNDED_NOTICE_TEXT','Plata Twispay a fost rambursata #');
define('ORDER_REFUND_REQUESTED_NOTICE_TEXT','Cererea de rambursare inregistrata pentru tranzactia #');
define('ORDER_REFUNDED_REQUESTED_NOTICE_TEXT','Twispay refund requested');
define('ORDER_PAID_NOTICE_TEXT','Platit Twispay #');
define('ORDER_CANCELED_NOTICE_TEXT','Plata Twispay a fost anulata');
define('ORDER_NO_ACTION_NOTICE_TEXT','Parametrul actiune nu este definit.');
define('ORDER_INVLID_ACTION_NOTICE_TEXT','Actiunea specificata nu este valida.');

/* LOG insertor */
define('LOG_REFUND_RASPUNS_TEXT','[RASPUNS]: Datele operatiunii de rambursare: ');
define('LOG_CANCEL_RESPONSE_TEXT','[RESPONSE]: Datele operatiunii de anulare: ');

define('LOG_OK_RASPUNS_DATA_TEXT','[RASPUNS]: Date: ');
define('LOG_OK_STRING_DECRYPTED_TEXT','[RASPUNS]: rezultate decriptare: ');
define('LOG_OK_STATUS_COMPLETE_TEXT','[RASPUNS]: Status complete-ok pentru comanda cu ID-ul: ');
define('LOG_OK_STATUS_REFUNDED_TEXT','[RASPUNS]: Status refund-ok pentru comanda cu ID-ul: ');
define('LOG_OK_STATUS_FAILED_TEXT','[RASPUNS]: Status failed pentru comanda cu ID-ul: ');
define('LOG_OK_STATUS_VOIDED_TEXT','[RASPUNS]: Status voided pentru comanda cu ID-ul: ');
define('LOG_OK_STATUS_CANCELED_TEXT','[RASPUNS]: Status canceled pentru comanda cu ID-ul: ');
define('LOG_OK_STATUS_CHARGED_BACK_TEXT','[RASPUNS]: Status charged back for order ID: ');
define('LOG_OK_STATUS_HOLD_TEXT','[RASPUNS]: Status on-hold pentru comanda cu ID-ul: ');
define('LOG_OK_VALIDATING_COMPLETE_TEXT','[RASPUNS]: Validare completa pentru comanda cu ID-ul: ');

define('LOG_ERROR_VALIDATING_FAILED_TEXT','[RASPUNS-EROARE]: Validarea a esuat.');
define('LOG_ERROR_DECRYPTION_ERROR_TEXT','[RASPUNS-EROARE]: Decriptarea a esuat.');
define('LOG_ERROR_INVALID_ORDER_TEXT','[RASPUNS-EROARE]: Comanda nu exista.');
define('LOG_ERROR_WRONG_STATUS_TEXT','[RASPUNS-EROARE]: Status invalid: ');
define('LOG_ERROR_EMPTY_STATUS_TEXT','[RASPUNS-EROARE]: Status gol.');
define('LOG_ERROR_EMPTY_IDENTIFIER_TEXT','[RASPUNS-EROARE]: Identificator gol.');
define('LOG_ERROR_EMPTY_EXTERNAL_TEXT','[RASPUNS-EROARE]: ExternalOrderId gol.');
define('LOG_ERROR_EMPTY_TRANSACTION_TEXT','[RASPUNS-EROARE]: TransactionId gol.');
define('LOG_ERROR_EMPTY_RASPUNS_TEXT','[RASPUNS-EROARE]: A fost primit un raspuns gol.');
define('LOG_ERROR_INVALID_PRIVATE_TEXT','[RASPUNS-EROARE]: Cheia privata nu este valida.');
define('LOG_ERROR_TRANSACTION_EXIST_TEXT','[RASPUNS-EROARE]: Tranzactia nu poate fi suprascrisa #');

define('SUBSCRIPTIONS_LOG_OK_SET_STATUS_TEXT','[RASPUNS]: Starea de pe server setata pentru comanda cu ID-ul: ');
define('SUBSCRIPTIONS_LOG_ERROR_CALL_FAILED_TEXT','[RASPUNS-EROARE]: Eroare la apelarea server-ului: ');
define('SUBSCRIPTIONS_LOG_ERROR_HTTP_CODE_TEXT','[RASPUNS-EROARE]: Cod HTTP neasteptat: ');
define('SUBSCRIPTIONS_LOG_ERROR_ORDER_NOT_FOUND_TEXT','[RASPUNS-EROARE]: Nu a fost gasit nici un abonament cu ID-ul: ');
define('SUBSCRIPTIONS_LOG_ERROR_NO_ORDERS_FOUND_TEXT','[RASPUNS-EROARE]: 0 abonamente modificate.');
define('SUBSCRIPTIONS_LOG_ERROR_GET_STATUS_TEXT','[RASPUNS-EROARE]: Eroare la extragerea starii de pe server pentru comanda cu ID-ul: ');
define('SUBSCRIPTIONS_LOG_ERROR_SET_STATUS_TEXT','[RASPUNS-EROARE]: Eroare la setarea starii pentru comanda cu ID-ul: ');
