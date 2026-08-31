<?php
// Heading
$_['heading_title'] = 'NewsMAN';
$_['heading_title_main'] = 'NewsMAN configureren';

// Text
$_['text_module'] = 'Module';
$_['text_extension'] = 'Extensies';
$_['text_menu_settings'] = 'Instellingen';
$_['text_menu_remarketing'] = 'Remarketing';
$_['text_header_edit'] = 'Instellingen';
$_['text_header_developer_edit'] = 'Ontwikkelaarsinstellingen';
$_['text_close'] = 'Sluiten';
$_['text_success'] = 'Uw gegevens zijn opgeslagen';
$_['text_please_select_list'] = 'Selecteer een lijst';
$_['text_please_select_segment'] = 'Selecteer een segment (optioneel)';
$_['text_credentials_valid'] = 'Inloggegevens zijn geldig';
$_['text_credentials_invalid'] = 'Inloggegevens zijn ongeldig of er is een tijdelijke API-fout!';
$_['text_export_authorize_header_name_hint'] = 'Een alternatieve autorisatieheadernaam kan worden opgegeven. Deze kan worden ingesteld in Newsman.app in de productfeed.';
$_['text_export_authorize_header_key_hint'] = 'Een alternatieve autorisatieheadersleutel kan worden opgegeven. Deze kan worden ingesteld in Newsman.app in de productfeed.';
$_['text_export_authorize_header_name_help'] = $_['text_export_authorize_header_key_help'] = 'Gebruik alleen alfanumerieke tekens en het minteken.';
$_['text_api_status_hint'] = 'De gebruikte ID en API-sleutel zijn geldig. De verbinding met de NewsMAN API is getest en werkt.';
$_['text_remarketing_settings'] = 'Remarketing-instellingen';
$_['text_cron'] = 'CRON voor nieuwsbriefabonnees en bestellingen';
$_['text_reconfigure'] = 'Herconfigureren met Newsman-login';
$_['text_config_for_store'] = 'Herconfigureren voor winkel: %s (ID: %s)';
$_['text_setup_for_store'] = 'Instelling voor winkel: %s (ID: %s)';
$_['text_store'] = 'Winkel';
$_['text_version'] = 'Newsman extensieversie';
$_['button_export_subscribers'] = 'Alle abonnees exporteren';
$_['button_export_orders'] = 'Alle bestellingen exporteren';
$_['button_export_orders_60_days'] = 'Bestellingen exporteren (60 dagen)';
$_['button_reconfigure'] = 'Herconfigureren met Newsman-login';

// Entry
$_['entry_api_status'] = 'NewsMAN API-status';
$_['entry_module_status'] = 'Status';
$_['entry_user_id'] = 'Gebruikers-ID';
$_['entry_api_key'] = 'API-sleutel';
$_['entry_list_id'] = 'Lijst';
$_['entry_segment'] = 'Segment';
$_['entry_newsletter_double_optin'] = 'Dubbele opt-in';
$_['entry_send_user_ip'] = 'Gebruikers-IP-adres verzenden';
$_['entry_server_ip'] = 'Server-IP';
$_['entry_export_authorize_header_name'] = 'Export autorisatieheadernaam';
$_['entry_export_authorize_header_key'] = 'Export autorisatieheadersleutel';
$_['entry_developer_log_severity'] = 'Logniveau';
$_['entry_developer_log_clean_days'] = 'Log opschoondagen';
$_['entry_developer_api_timeout'] = 'API-timeout';
$_['entry_developer_active_user_ip'] = 'Gebruikers-IP inschakelen';
$_['entry_developer_user_ip'] = 'Test-IP';
$_['entry_checkout_newsletter'] = 'Nieuwsbrief-selectievakje bij afrekenen inschakelen';
$_['entry_checkout_newsletter_default'] = 'Nieuwsbrief-selectievakje standaard aangevinkt';
$_['entry_checkout_newsletter_label'] = 'Label nieuwsbrief-selectievakje';
$_['entry_export_subscribers_by_store'] = 'Abonnees per winkel exporteren';
$_['entry_export_subscribers_by_store_help'] = 'Schakel dit in als u alleen de abonnees wilt exporteren die bij deze winkel horen.';
$_['entry_export_customers_by_store'] = 'Klanten per winkel exporteren';
$_['entry_export_customers_by_store_help'] = 'Klanten in OpenCart kunnen inloggen bij alle winkels. Het maakt niet uit in welke winkel ze zijn aangemaakt. Schakel dit in als u ze per winkel wilt filteren.';
$_['entry_complete_order_status'] = 'Als voltooid gemelde bestelstatussen';
$_['entry_complete_order_status_help'] = 'Bestelstatussen die als voltooide aankoop aan Newsman worden gemeld, naast de winkelinstelling \'Status voltooide bestelling\'. Gebruik dit wanneer de eindstatus van een bestelling er een is die de winkel niet als voltooid telt, bijvoorbeeld een verzend- of overdrachtsstatus. Statussen die in geen van beide lijsten staan, worden onder hun eigen naam naar Newsman gestuurd.';
$_['entry_processing_order_status'] = 'Als in behandeling gemelde bestelstatussen';
$_['entry_processing_order_status_help'] = 'Bestelstatussen die als lopende aankoop aan Newsman worden gemeld, naast de winkelinstelling \'Status bestelling in behandeling\'. Een hierboven als voltooid aangevinkte status gaat voor op een hier aangevinkte. Statussen die in geen van beide lijsten staan, worden onder hun eigen naam naar Newsman gestuurd.';
$_['entry_feed_image_generate'] = 'Productfeed: ontbrekende afbeeldingen genereren';
$_['entry_feed_image_generate_help'] = 'Maakt de verkleinde productafbeelding op de server aan wanneer deze nog niet bestaat. Indien uitgeschakeld gebruikt de feed de reeds bestaande verkleinde afbeelding of de originele afbeelding.';
$_['entry_feed_image_custom_size'] = 'Productfeed: aangepast afbeeldingsformaat';
$_['entry_feed_image_custom_size_help'] = 'Gebruik onderstaande breedte en hoogte voor de productafbeeldingen in de feed in plaats van het popup-formaat van het thema. Stem de waarden af op het afbeeldingsformaat van uw thema.';
$_['entry_feed_image_width'] = 'Productfeed: afbeeldingsbreedte';
$_['entry_feed_image_height'] = 'Productfeed: afbeeldingshoogte';
$_['entry_send_user_ip_help'] = 'Het IP-adres van de gebruiker wordt verzonden naar de NewsMAN API voor abonnement- of afmeldingsverzoeken.';
$_['entry_server_ip_help'] = 'Het IP-adres van de server wordt verzonden naar de NewsMAN API in plaats van het IP-adres van de gebruiker. Wordt gebruikt wanneer "Gebruikers-IP-adres verzenden" is ingesteld op "Uitgeschakeld".';
$_['entry_developer_active_user_ip_help'] = 'Altijd test-IP verzenden naar de NewsMAN API. Deze optie moet niet worden ingeschakeld in een productieomgeving.';

// Error
$_['error_permission'] = 'U heeft geen toestemming om de module NewsMAN te wijzigen!';
$_['error_step3_save'] = 'Er is een fout opgetreden bij het opslaan van de NewsMAN-inloggegevens in de administratie. Probeer het opnieuw.';
$_['error_access_denied'] = 'Toegang geweigerd.';
$_['error_missing_lists'] = 'Er zijn geen lijsten in uw NewsMAN-account.';
$_['error_token_missing'] = 'Token ontbreekt.';

// Step 1
$_['text_step1_connect'] = 'Verbind uw site met NewsMAN voor:';
$_['text_step1_sync'] = 'Synchronisatie van abonnees';
$_['text_step1_remarketing'] = 'E-commerce remarketing';
$_['text_step1_forms'] = 'Formulieren aanmaken en beheren';
$_['text_step1_popups'] = 'Popups aanmaken en beheren';
$_['text_step1_automation'] = 'Uw formulieren verbinden met automatisering';
$_['button_login'] = 'Inloggen met NewsMAN';

// Step 2
$_['text_step2_retry'] = 'Probeer het opnieuw:';
$_['button_retry'] = 'Opnieuw proberen';
$_['text_step2_list_title'] = 'NewsMAN e-maillijst';
$_['text_step2_list_select_finalize'] = 'Selecteer een lijst om de configuratie te voltooien.';
$_['text_step2_list_select_proceed'] = 'Selecteer een lijst om door te gaan';
