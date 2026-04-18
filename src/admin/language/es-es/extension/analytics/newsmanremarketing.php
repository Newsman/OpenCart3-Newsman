<?php
$_['heading_title'] = 'NewsMAN Remarketing';

// Text
$_['text_extension'] = 'Extensiones';
$_['text_success'] = '¡Éxito: Ha modificado NewsMAN Remarketing!';
$_['text_edit'] = 'Editar NewsMAN Remarketing';
$_['text_signup'] = 'Inicie sesión en su <a href="https://www.newsman.app/" target="_blank"><u>cuenta NewsMAN</u></a> y obtenga su ID';
$_['text_default'] = 'Predeterminado';
$_['text_version'] = 'Versión de la extensión Newsman';
$_['text_store'] = 'Tienda';
$_['text_newsman_settings'] = 'Ajustes de Newsman';
$_['text_credentials_valid'] = 'El ID de remarketing es válido';
$_['text_credentials_invalid'] = '¡El ID de remarketing es inválido o hay un error temporal de la API!';
$_['text_api_status_hint'] = 'El ID de remarketing es válido y coincide con el de NewsMAN.';
$_['text_config_for_store'] = 'Reconfigurar para la tienda: %s (ID: %s)';
$_['entry_api_status'] = 'Estado del ID de remarketing';

// Entry
$_['entry_tracking'] = 'ID de remarketing NewsMAN';
$_['entry_status'] = 'Estado';
$_['entry_anonymize_ip'] = 'Anonimizar dirección IP';
$_['entry_send_telephone'] = 'Enviar número de teléfono';
$_['entry_theme_cart_compatibility'] = 'Compatibilidad del Carrito con el Tema';
$_['entry_theme_cart_compatibility_help'] = 'Active esta opción para la detección más fiable de cambios en el carrito en cualquier tema (utiliza sondeo en segundo plano y escucha las solicitudes AJAX/fetch). Desactívela para utilizar un mecanismo más ligero que lee el contenido del carrito directamente desde el bloque minicart del tema predeterminado de OpenCart 3 (sin sondeo en segundo plano, pero solo funciona si su tema utiliza el bloque minicart estándar <code>#cart</code>). Si desactiva esta opción, vacíe la caché de OpenCart y luego utilice la herramienta <strong>Check installation</strong> Remarketing de newsman.app para verificar que los eventos del carrito se detectan correctamente.';
$_['entry_order_date'] = 'Fecha mínima de pedido';

// Error
$_['error_permission'] = '¡Advertencia: No tiene permiso para modificar NewsMAN Remarketing!';
$_['error_code'] = '¡Se requiere el ID de seguimiento!';
