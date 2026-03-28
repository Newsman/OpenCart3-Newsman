<?php
// Heading
$_['heading_title'] = 'NewsMAN';
$_['heading_title_main'] = 'NewsMAN konfigurieren';

// Text
$_['text_module'] = 'Modul';
$_['text_extension'] = 'Erweiterungen';
$_['text_menu_settings'] = 'Einstellungen';
$_['text_menu_remarketing'] = 'Remarketing';
$_['text_header_edit'] = 'Einstellungen';
$_['text_header_developer_edit'] = 'Entwicklereinstellungen';
$_['text_close'] = 'Schließen';
$_['text_success'] = 'Ihre Daten wurden gespeichert';
$_['text_please_select_list'] = 'Bitte wählen Sie eine Liste';
$_['text_please_select_segment'] = 'Bitte wählen Sie ein Segment (optional)';
$_['text_credentials_valid'] = 'Anmeldedaten sind gültig';
$_['text_credentials_invalid'] = 'Anmeldedaten sind ungültig oder es liegt ein vorübergehender API-Fehler vor!';
$_['text_export_authorize_header_name_hint'] = 'Ein alternativer Autorisierungs-Header-Name kann angegeben werden. Er kann in Newsman.app im Produkt-Feed festgelegt werden.';
$_['text_export_authorize_header_key_hint'] = 'Ein alternativer Autorisierungs-Header-Schlüssel kann angegeben werden. Er kann in Newsman.app im Produkt-Feed festgelegt werden.';
$_['text_export_authorize_header_name_help'] = $_['text_export_authorize_header_key_help'] = 'Bitte verwenden Sie nur alphanumerische Zeichen und das Minuszeichen.';
$_['text_api_status_hint'] = 'Die verwendete ID und der API-Schlüssel sind gültig. Die Verbindung zur NewsMAN-API wurde getestet und funktioniert.';
$_['text_remarketing_settings'] = 'Remarketing-Einstellungen';
$_['text_cron'] = 'CRON für Newsletter-Abonnenten und Bestellungen';
$_['text_reconfigure'] = 'Mit Newsman-Anmeldung neu konfigurieren';
$_['text_config_for_store'] = 'Neu konfigurieren für Shop: %s (ID: %s)';
$_['text_setup_for_store'] = 'Einrichtung für Shop: %s (ID: %s)';
$_['text_store'] = 'Shop';
$_['text_version'] = 'Newsman-Erweiterungsversion';
$_['button_export_subscribers'] = 'Alle Abonnenten exportieren';
$_['button_export_orders'] = 'Alle Bestellungen exportieren';
$_['button_export_orders_60_days'] = 'Bestellungen exportieren (60 Tage)';
$_['button_reconfigure'] = 'Mit Newsman-Anmeldung neu konfigurieren';

// Entry
$_['entry_api_status'] = 'NewsMAN-API-Status';
$_['entry_module_status'] = 'Status';
$_['entry_user_id'] = 'Benutzer-ID';
$_['entry_api_key'] = 'API-Schlüssel';
$_['entry_list_id'] = 'Liste';
$_['entry_segment'] = 'Segment';
$_['entry_newsletter_double_optin'] = 'Double-Opt-in';
$_['entry_send_user_ip'] = 'Benutzer-IP-Adresse senden';
$_['entry_server_ip'] = 'Server-IP';
$_['entry_export_authorize_header_name'] = 'Export-Autorisierungs-Header-Name';
$_['entry_export_authorize_header_key'] = 'Export-Autorisierungs-Header-Schlüssel';
$_['entry_developer_log_severity'] = 'Protokollebene';
$_['entry_developer_log_clean_days'] = 'Protokoll-Bereinigungstage';
$_['entry_developer_api_timeout'] = 'API-Zeitüberschreitung';
$_['entry_developer_active_user_ip'] = 'Benutzer-IP aktivieren';
$_['entry_developer_user_ip'] = 'Test-IP';
$_['entry_checkout_newsletter'] = 'Newsletter-Kontrollkästchen beim Checkout aktivieren';
$_['entry_checkout_newsletter_default'] = 'Newsletter-Kontrollkästchen standardmäßig aktiviert';
$_['entry_checkout_newsletter_label'] = 'Beschriftung des Newsletter-Kontrollkästchens';
$_['entry_export_subscribers_by_store'] = 'Abonnenten nach Shop exportieren';
$_['entry_export_subscribers_by_store_help'] = 'Aktivieren Sie dies, wenn Sie nur die Abonnenten exportieren möchten, die zu diesem Shop gehören.';
$_['entry_export_customers_by_store'] = 'Kunden nach Shop exportieren';
$_['entry_export_customers_by_store_help'] = 'Kunden in OpenCart können sich in allen Shops anmelden. Es spielt keine Rolle, in welchem Shop sie erstellt wurden. Aktivieren Sie dies, wenn Sie sie nach Shop filtern möchten.';
$_['entry_send_user_ip_help'] = 'Die Benutzer-IP-Adresse wird für Abonnement- oder Abmelde-API-Anfragen an die NewsMAN-API gesendet.';
$_['entry_server_ip_help'] = 'Die Server-IP-Adresse wird anstelle der Benutzer-IP-Adresse an die NewsMAN-API gesendet. Wird verwendet, wenn „Benutzer-IP-Adresse senden" auf „Deaktiviert" gesetzt ist.';
$_['entry_developer_active_user_ip_help'] = 'Test-IP immer an die NewsMAN-API senden. Diese Option sollte in der Produktionsumgebung nicht aktiviert werden.';

// Error
$_['error_permission'] = 'Sie haben keine Berechtigung, das Modul NewsMAN zu ändern!';
$_['error_step3_save'] = 'Beim Speichern der NewsMAN-Anmeldedaten in der Administration ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.';
$_['error_access_denied'] = 'Zugriff verweigert.';
$_['error_missing_lists'] = 'Es gibt keine Listen in Ihrem NewsMAN-Konto.';
$_['error_token_missing'] = 'Token fehlt.';

// Step 1
$_['text_step1_connect'] = 'Verbinden Sie Ihre Website mit NewsMAN für:';
$_['text_step1_sync'] = 'Abonnenten-Synchronisierung';
$_['text_step1_remarketing'] = 'E-Commerce-Remarketing';
$_['text_step1_forms'] = 'Formulare erstellen und verwalten';
$_['text_step1_popups'] = 'Popups erstellen und verwalten';
$_['text_step1_automation'] = 'Formulare mit Automatisierung verbinden';
$_['button_login'] = 'Mit NewsMAN anmelden';

// Step 2
$_['text_step2_retry'] = 'Bitte versuchen Sie es erneut:';
$_['button_retry'] = 'Erneut versuchen';
$_['text_step2_list_title'] = 'NewsMAN E-Mail-Liste';
$_['text_step2_list_select_finalize'] = 'Bitte wählen Sie eine Liste, um die Konfiguration abzuschließen.';
$_['text_step2_list_select_proceed'] = 'Bitte wählen Sie eine Liste, um fortzufahren';
