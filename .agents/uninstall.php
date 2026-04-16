<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Limpiar opciones del plugin
delete_option('wcpr_settings');

// Si se quiere, limpiar opciones de red (multisite)
if (is_multisite()) {
    delete_site_option('wcpr_settings');
}
