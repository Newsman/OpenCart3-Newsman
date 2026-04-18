<?php
$_['heading_title'] = 'NewsMAN Remarketing';

// Text
$_['text_extension'] = 'Estensioni';
$_['text_success'] = 'Successo: Hai modificato NewsMAN Remarketing!';
$_['text_edit'] = 'Modifica NewsMAN Remarketing';
$_['text_signup'] = 'Accedi al tuo <a href="https://www.newsman.app/" target="_blank"><u>account NewsMAN</u></a> e ottieni il tuo ID';
$_['text_default'] = 'Predefinito';
$_['text_version'] = 'Versione estensione Newsman';
$_['text_store'] = 'Negozio';
$_['text_newsman_settings'] = 'Impostazioni Newsman';
$_['text_credentials_valid'] = 'L\'ID di remarketing è valido';
$_['text_credentials_invalid'] = 'L\'ID di remarketing non è valido o si è verificato un errore temporaneo dell\'API!';
$_['text_api_status_hint'] = 'L\'ID di remarketing è valido e corrisponde a quello in NewsMAN.';
$_['text_config_for_store'] = 'Riconfigura per il negozio: %s (ID: %s)';
$_['entry_api_status'] = 'Stato ID remarketing';

// Entry
$_['entry_tracking'] = 'ID remarketing NewsMAN';
$_['entry_status'] = 'Stato';
$_['entry_anonymize_ip'] = 'Anonimizza indirizzo IP';
$_['entry_send_telephone'] = 'Invia numero di telefono';
$_['entry_theme_cart_compatibility'] = 'Compatibilità Carrello con il Tema';
$_['entry_theme_cart_compatibility_help'] = 'Abilita per la rilevazione più affidabile delle modifiche al carrello in qualsiasi tema (utilizza polling in background e ascolta le richieste AJAX/fetch). Disabilita per utilizzare un meccanismo più leggero che legge il contenuto del carrello direttamente dal blocco minicart del tema predefinito di OpenCart 3 (nessun polling in background, ma funziona solo se il tuo tema utilizza il blocco minicart standard <code>#cart</code>). Se disabiliti questa opzione, svuota la cache di OpenCart e poi utilizza lo strumento <strong>Check installation</strong> Remarketing di newsman.app per verificare che gli eventi del carrello siano rilevati correttamente.';
$_['entry_order_date'] = 'Data ordine minima';

// Error
$_['error_permission'] = 'Attenzione: Non hai il permesso di modificare NewsMAN Remarketing!';
$_['error_code'] = 'ID di tracciamento richiesto!';
