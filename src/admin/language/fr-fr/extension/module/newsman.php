<?php
// Heading
$_['heading_title'] = 'NewsMAN';
$_['heading_title_main'] = 'Configurer NewsMAN';

// Text
$_['text_module'] = 'Module';
$_['text_extension'] = 'Extensions';
$_['text_menu_settings'] = 'Paramètres';
$_['text_menu_remarketing'] = 'Remarketing';
$_['text_header_edit'] = 'Paramètres';
$_['text_header_developer_edit'] = 'Paramètres développeur';
$_['text_close'] = 'Fermer';
$_['text_success'] = 'Vos données ont été enregistrées';
$_['text_please_select_list'] = 'Veuillez sélectionner une liste';
$_['text_please_select_segment'] = 'Veuillez sélectionner un segment (optionnel)';
$_['text_credentials_valid'] = 'Les identifiants sont valides';
$_['text_credentials_invalid'] = 'Les identifiants sont invalides ou il y a une erreur temporaire de l\'API !';
$_['text_export_authorize_header_name_hint'] = 'Un nom d\'en-tête d\'autorisation alternatif peut être spécifié. Il peut être défini dans Newsman.app dans le flux de produits.';
$_['text_export_authorize_header_key_hint'] = 'Une clé d\'en-tête d\'autorisation alternative peut être spécifiée. Elle peut être définie dans Newsman.app dans le flux de produits.';
$_['text_export_authorize_header_name_help'] = $_['text_export_authorize_header_key_help'] = 'Veuillez utiliser uniquement des caractères alphanumériques et le tiret.';
$_['text_api_status_hint'] = 'L\'ID et la clé API utilisés sont valides. La connexion à l\'API NewsMAN a été testée et fonctionne.';
$_['text_remarketing_settings'] = 'Paramètres de remarketing';
$_['text_cron'] = 'CRON pour les abonnés à la newsletter et les commandes';
$_['text_reconfigure'] = 'Reconfigurer avec la connexion Newsman';
$_['text_config_for_store'] = 'Reconfigurer pour la boutique : %s (ID : %s)';
$_['text_setup_for_store'] = 'Configuration pour la boutique : %s (ID : %s)';
$_['text_store'] = 'Boutique';
$_['text_version'] = 'Version de l\'extension Newsman';
$_['button_export_subscribers'] = 'Exporter tous les abonnés';
$_['button_export_orders'] = 'Exporter toutes les commandes';
$_['button_export_orders_60_days'] = 'Exporter les commandes (60 jours)';
$_['button_reconfigure'] = 'Reconfigurer avec la connexion Newsman';

// Entry
$_['entry_api_status'] = 'Statut de l\'API NewsMAN';
$_['entry_module_status'] = 'Statut';
$_['entry_user_id'] = 'ID utilisateur';
$_['entry_api_key'] = 'Clé API';
$_['entry_list_id'] = 'Liste';
$_['entry_segment'] = 'Segment';
$_['entry_newsletter_double_optin'] = 'Double opt-in';
$_['entry_send_user_ip'] = 'Envoyer l\'adresse IP de l\'utilisateur';
$_['entry_server_ip'] = 'IP du serveur';
$_['entry_export_authorize_header_name'] = 'Nom de l\'en-tête d\'autorisation d\'export';
$_['entry_export_authorize_header_key'] = 'Clé de l\'en-tête d\'autorisation d\'export';
$_['entry_developer_log_severity'] = 'Niveau de journalisation';
$_['entry_developer_log_clean_days'] = 'Jours de nettoyage des journaux';
$_['entry_developer_api_timeout'] = 'Délai d\'expiration de l\'API';
$_['entry_developer_active_user_ip'] = 'Activer l\'IP utilisateur';
$_['entry_developer_user_ip'] = 'IP de test';
$_['entry_checkout_newsletter'] = 'Activer la case newsletter lors du paiement';
$_['entry_checkout_newsletter_default'] = 'Case newsletter cochée par défaut';
$_['entry_checkout_newsletter_label'] = 'Libellé de la case newsletter';
$_['entry_export_subscribers_by_store'] = 'Exporter les abonnés par boutique';
$_['entry_export_subscribers_by_store_help'] = 'Activez cette option si vous souhaitez exporter uniquement les abonnés appartenant à cette boutique.';
$_['entry_export_customers_by_store'] = 'Exporter les clients par boutique';
$_['entry_export_customers_by_store_help'] = 'Les clients dans OpenCart peuvent se connecter à toutes les boutiques. Peu importe dans quelle boutique ils ont été créés. Activez cette option si vous souhaitez les filtrer par boutique.';
$_['entry_send_user_ip_help'] = 'L\'adresse IP de l\'utilisateur sera envoyée à l\'API NewsMAN pour les requêtes d\'abonnement ou de désabonnement.';
$_['entry_server_ip_help'] = 'L\'adresse IP du serveur sera envoyée à l\'API NewsMAN à la place de l\'adresse IP de l\'utilisateur. Utilisé lorsque « Envoyer l\'adresse IP de l\'utilisateur » est défini sur « Désactivé ».';
$_['entry_developer_active_user_ip_help'] = 'Toujours envoyer l\'IP de test à l\'API NewsMAN. Cette option ne doit pas être activée en environnement de production.';

// Error
$_['error_permission'] = 'Vous n\'avez pas la permission de modifier le module NewsMAN !';
$_['error_step3_save'] = 'Une erreur s\'est produite lors de l\'enregistrement des identifiants NewsMAN dans l\'administration. Veuillez réessayer.';
$_['error_access_denied'] = 'Accès refusé.';
$_['error_missing_lists'] = 'Il n\'y a aucune liste dans votre compte NewsMAN.';
$_['error_token_missing'] = 'Le jeton est manquant.';

// Step 1
$_['text_step1_connect'] = 'Connectez votre site avec NewsMAN pour :';
$_['text_step1_sync'] = 'Synchronisation des abonnés';
$_['text_step1_remarketing'] = 'Remarketing e-commerce';
$_['text_step1_forms'] = 'Créer et gérer des formulaires';
$_['text_step1_popups'] = 'Créer et gérer des popups';
$_['text_step1_automation'] = 'Connecter vos formulaires à l\'automatisation';
$_['button_login'] = 'Se connecter avec NewsMAN';

// Step 2
$_['text_step2_retry'] = 'Veuillez réessayer :';
$_['button_retry'] = 'Réessayer';
$_['text_step2_list_title'] = 'Liste d\'e-mails NewsMAN';
$_['text_step2_list_select_finalize'] = 'Veuillez sélectionner une liste pour finaliser la configuration.';
$_['text_step2_list_select_proceed'] = 'Veuillez sélectionner une liste pour continuer';
