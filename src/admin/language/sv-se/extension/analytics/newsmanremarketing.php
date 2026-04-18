<?php
$_['heading_title'] = 'NewsMAN Remarketing';

// Text
$_['text_extension'] = 'Tillägg';
$_['text_success'] = 'Framgång: Du har ändrat NewsMAN Remarketing!';
$_['text_edit'] = 'Redigera NewsMAN Remarketing';
$_['text_signup'] = 'Logga in på ditt <a href="https://www.newsman.app/" target="_blank"><u>NewsMAN-konto</u></a> och hämta ditt ID';
$_['text_default'] = 'Standard';
$_['text_version'] = 'Newsman tilläggsversion';
$_['text_store'] = 'Butik';
$_['text_newsman_settings'] = 'Newsman-inställningar';
$_['text_credentials_valid'] = 'Remarketing-ID är giltigt';
$_['text_credentials_invalid'] = 'Remarketing-ID är ogiltigt eller så finns det ett tillfälligt API-fel!';
$_['text_api_status_hint'] = 'Remarketing-ID är giltigt och matchar det i NewsMAN.';
$_['text_config_for_store'] = 'Omkonfigurera för butik: %s (ID: %s)';
$_['entry_api_status'] = 'Remarketing-ID-status';

// Entry
$_['entry_tracking'] = 'NewsMAN Remarketing-ID';
$_['entry_status'] = 'Status';
$_['entry_anonymize_ip'] = 'Anonymisera IP-adress';
$_['entry_send_telephone'] = 'Skicka telefonnummer';
$_['entry_theme_cart_compatibility'] = 'Temakompatibilitet Varukorg';
$_['entry_theme_cart_compatibility_help'] = 'Aktivera för den mest tillförlitliga detekteringen av varukorgsändringar i alla teman (använder bakgrundspollning och lyssnar på AJAX/fetch-förfrågningar). Inaktivera för att använda en lättare mekanism som läser varukorgens innehåll direkt från standard-minikundvagnsblocket i OpenCart 3-temat (ingen bakgrundspollning, men fungerar endast om ditt tema använder standard-minikundvagnsblocket <code>#cart</code>). Om du inaktiverar detta alternativ, rensa OpenCart-cachen och använd sedan verktyget <strong>Check installation</strong> Remarketing från newsman.app för att verifiera att varukorgens händelser detekteras korrekt.';
$_['entry_order_date'] = 'Minsta beställningsdatum';

// Error
$_['error_permission'] = 'Varning: Du har inte behörighet att ändra NewsMAN Remarketing!';
$_['error_code'] = 'Spårnings-ID krävs!';
