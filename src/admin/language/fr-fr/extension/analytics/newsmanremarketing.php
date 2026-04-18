<?php
$_['heading_title'] = 'NewsMAN Remarketing';

// Text
$_['text_extension'] = 'Extensions';
$_['text_success'] = 'Succès : Vous avez modifié NewsMAN Remarketing !';
$_['text_edit'] = 'Modifier NewsMAN Remarketing';
$_['text_signup'] = 'Connectez-vous à votre <a href="https://www.newsman.app/" target="_blank"><u>compte NewsMAN</u></a> et obtenez votre ID';
$_['text_default'] = 'Par défaut';
$_['text_version'] = 'Version de l\'extension Newsman';
$_['text_store'] = 'Boutique';
$_['text_newsman_settings'] = 'Paramètres Newsman';
$_['text_credentials_valid'] = 'L\'ID de remarketing est valide';
$_['text_credentials_invalid'] = 'L\'ID de remarketing est invalide ou il y a une erreur temporaire de l\'API !';
$_['text_api_status_hint'] = 'L\'ID de remarketing est valide et correspond à celui dans NewsMAN.';
$_['text_config_for_store'] = 'Reconfigurer pour la boutique : %s (ID : %s)';
$_['entry_api_status'] = 'Statut de l\'ID de remarketing';

// Entry
$_['entry_tracking'] = 'ID de remarketing NewsMAN';
$_['entry_status'] = 'Statut';
$_['entry_anonymize_ip'] = 'Anonymiser l\'adresse IP';
$_['entry_send_telephone'] = 'Envoyer le numéro de téléphone';
$_['entry_theme_cart_compatibility'] = 'Compatibilité du Panier avec le Thème';
$_['entry_theme_cart_compatibility_help'] = 'Activez pour la détection la plus fiable des modifications du panier dans n\'importe quel thème (utilise un sondage en arrière-plan et écoute les requêtes AJAX/fetch). Désactivez pour utiliser un mécanisme plus léger qui lit le contenu du panier directement depuis le bloc minicart du thème par défaut d\'OpenCart 3 (sans sondage en arrière-plan, mais ne fonctionne que si votre thème utilise le bloc minicart standard <code>#cart</code>). Si vous désactivez cette option, videz le cache OpenCart puis utilisez l\'outil <strong>Check installation</strong> Remarketing de newsman.app pour vérifier que les événements du panier sont détectés correctement.';
$_['entry_order_date'] = 'Date de commande minimale';

// Error
$_['error_permission'] = 'Attention : Vous n\'avez pas la permission de modifier NewsMAN Remarketing !';
$_['error_code'] = 'L\'ID de suivi est requis !';
