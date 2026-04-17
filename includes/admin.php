<?php

/**
 * Panel de administración del plugin
 */

add_action('admin_menu', 'wcpr_admin_menu');

function wcpr_admin_menu()
{
    add_submenu_page(
        'woocommerce',
        'Payment Recovery',
        'Payment Recovery',
        'manage_options',
        'wcpr-settings',
        'wcpr_settings_page'
    );
}

function wcpr_get_dashboard_metrics($period_days = 30)
{
    $transient_key = 'wcpr_dashboard_metrics_' . $period_days;
    $metrics = get_transient($transient_key);

    if (false === $metrics) {
        $current_time = current_time('timestamp');
        $start_date = $current_time - ($period_days * DAY_IN_SECONDS);
        $prev_start_date = $start_date - ($period_days * DAY_IN_SECONDS);
        
        $get_counts = function($start, $end) {
            $attempts_args = array(
                'limit' => -1,
                'return' => 'ids',
                'meta_key' => '_wcpr_recovery_scheduled',
                'meta_value' => '1',
                'date_created' => $start . '...' . $end
            );
            $attempts = count(wc_get_orders($attempts_args));

            $recovered_args = array(
                'limit' => -1,
                'return' => 'objects',
                'meta_key' => '_wcpr_recovered',
                'meta_value' => '1',
                'date_created' => $start . '...' . $end
            );
            $recovered_orders = wc_get_orders($recovered_args);
            $recovered = count($recovered_orders);

            $revenue = 0;
            foreach ($recovered_orders as $order) {
                $revenue += (float) $order->get_total();
            }

            $conversion = $attempts > 0 ? round(($recovered / $attempts) * 100, 2) : 0;
            
            return array(
                'attempts' => $attempts,
                'recovered' => $recovered,
                'revenue' => $revenue,
                'conversion' => $conversion
            );
        };

        $current = $get_counts($start_date, $current_time);
        $previous = $get_counts($prev_start_date, $start_date);
        
        $calc_diff = function($curr, $prev) {
            if ($prev == 0) return $curr > 0 ? 100 : 0;
            return round((($curr - $prev) / $prev) * 100, 1);
        };

        $metrics = array(
            'current' => $current,
            'current_formatted' => array(
                'revenue' => wc_price($current['revenue'])
            ),
            'comparative' => array(
                'attempts' => $calc_diff($current['attempts'], $previous['attempts']),
                'recovered' => $calc_diff($current['recovered'], $previous['recovered']),
                'conversion' => round($current['conversion'] - $previous['conversion'], 1),
                'revenue' => $calc_diff($current['revenue'], $previous['revenue'])
            )
        );

        set_transient($transient_key, $metrics, 5 * MINUTE_IN_SECONDS);
    }

    return $metrics;
}

function wcpr_settings_page()
{
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <h2 class="nav-tab-wrapper">
            <a href="?page=wcpr-settings&tab=dashboard" class="nav-tab <?php echo $active_tab == 'dashboard' ? 'nav-tab-active' : ''; ?>">Dashboard</a>
            <a href="?page=wcpr-settings&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">Configuración</a>
        </h2>

        <?php if ($active_tab == 'dashboard') : ?>
            <?php 
                $period_days = isset($_GET['period_days']) ? intval($_GET['period_days']) : 30;
                $valid_periods = array(7 => 'Última Semana (7 días)', 15 => 'Últimos 15 Días', 30 => 'Último Mes (30 días)');
                if (!array_key_exists($period_days, $valid_periods)) $period_days = 30;
                $metrics = wcpr_get_dashboard_metrics($period_days); 
                
                if (!function_exists('wcpr_render_diff')) {
                    function wcpr_render_diff($diff) {
                        if ($diff == 0) return '<span style="color: #666; font-size: 14px;">= 0% vs anterior</span>';
                        $color = $diff > 0 ? '#46b450' : '#dc3232';
                        $arrow = $diff > 0 ? '↑' : '↓';
                        return sprintf('<span style="color: %s; font-size: 14px; font-weight: normal;">%s %s%% vs anterior</span>', $color, $arrow, abs($diff));
                    }
                }
            ?>
            
            <form method="get" action="">
                <input type="hidden" name="page" value="wcpr-settings">
                <input type="hidden" name="tab" value="dashboard">
                <div style="margin-top: 20px; padding: 10px; background: #fff; border: 1px solid #ccd0d4; border-radius: 5px; display: flex; gap: 15px; align-items: center;">
                    <label for="period_days"><strong>Mostrar datos de:</strong></label>
                    <select name="period_days" id="period_days" onchange="this.form.submit()">
                        <?php foreach($valid_periods as $val => $label): ?>
                            <option value="<?php echo $val; ?>" <?php selected($period_days, $val); ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="submit" name="wcpr_clear_cache" class="button button-secondary" value="Actualizar Estadísticas Ahora">
                </div>
                <?php wp_nonce_field('wcpr_clear_cache_nonce', 'wcpr_nonce'); ?>
            </form>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
                <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; text-align: center; border-radius: 5px;">
                    <h3 style="margin-top:0;">Intentos de Recuperación</h3>
                    <p style="font-size: 2em; margin: 10px 0; font-weight: bold; color: #0073aa;"><?php echo esc_html($metrics['current']['attempts']); ?></p>
                    <?php echo wcpr_render_diff($metrics['comparative']['attempts']); ?>
                </div>
                <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; text-align: center; border-radius: 5px;">
                    <h3 style="margin-top:0;">Pedidos Recuperados</h3>
                    <p style="font-size: 2em; margin: 10px 0; font-weight: bold; color: #46b450;"><?php echo esc_html($metrics['current']['recovered']); ?></p>
                    <?php echo wcpr_render_diff($metrics['comparative']['recovered']); ?>
                </div>
                <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; text-align: center; border-radius: 5px;">
                    <h3 style="margin-top:0;">Tasa de Conversión</h3>
                    <p style="font-size: 2em; margin: 10px 0; font-weight: bold; color: #d64e07;"><?php echo esc_html($metrics['current']['conversion']); ?>%</p>
                    <?php echo wcpr_render_diff($metrics['comparative']['conversion']); ?>
                </div>
                <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; text-align: center; border-radius: 5px;">
                    <h3 style="margin-top:0;">Ingresos Recuperados</h3>
                    <p style="font-size: 2em; margin: 10px 0; font-weight: bold; color: #46b450;"><?php echo wp_kses_post($metrics['current_formatted']['revenue']); ?></p>
                    <?php echo wcpr_render_diff($metrics['comparative']['revenue']); ?>
                </div>
            </div>
            
            <?php
            if (isset($_GET['wcpr_clear_cache']) && check_admin_referer('wcpr_clear_cache_nonce', 'wcpr_nonce')) {
                delete_transient('wcpr_dashboard_metrics_' . $period_days);
                echo '<script>window.location.href="?page=wcpr-settings&tab=dashboard&period_days=' . $period_days . '";</script>';
            }
            ?>

        <?php else : ?>
            <form action="options.php" method="post" style="margin-top: 20px;">
                <?php settings_fields('wcpr_settings_group'); ?>
                <?php do_settings_sections('wcpr_settings'); ?>
                <?php submit_button('Guardar configuración'); ?>
            </form>
        <?php endif; ?>
    </div>
<?php
}

// Registrar settings
add_action('admin_init', 'wcpr_register_settings');

function wcpr_register_settings()
{
    register_setting('wcpr_settings_group', 'wcpr_settings', 'wcpr_sanitize_settings');

    // Email 1 - 30 minutos
    add_settings_section(
        'wcpr_email_1',
        'Primer correo',
        'wcpr_email_1_section_callback',
        'wcpr_settings'
    );

    add_settings_field(
        'wcpr_email_1_enabled',
        'Habilitado',
        'wcpr_email_1_enabled_callback',
        'wcpr_settings',
        'wcpr_email_1'
    );

    add_settings_field(
        'wcpr_email_1_delay',
        'Retraso (minutos)',
        'wcpr_email_1_delay_callback',
        'wcpr_settings',
        'wcpr_email_1'
    );

    // Email 2 - 6 horas
    add_settings_section(
        'wcpr_email_2',
        'Segundo correo',
        'wcpr_email_2_section_callback',
        'wcpr_settings'
    );

    add_settings_field(
        'wcpr_email_2_enabled',
        'Habilitado',
        'wcpr_email_2_enabled_callback',
        'wcpr_settings',
        'wcpr_email_2'
    );

    add_settings_field(
        'wcpr_email_2_delay',
        'Retraso (minutos)',
        'wcpr_email_2_delay_callback',
        'wcpr_settings',
        'wcpr_email_2'
    );

    // Email 3 - 24 horas
    add_settings_section(
        'wcpr_email_3',
        'Tercer correo',
        'wcpr_email_3_section_callback',
        'wcpr_settings'
    );

    add_settings_field(
        'wcpr_email_3_enabled',
        'Habilitado',
        'wcpr_email_3_enabled_callback',
        'wcpr_settings',
        'wcpr_email_3'
    );

    add_settings_field(
        'wcpr_email_3_delay',
        'Retraso (minutos)',
        'wcpr_email_3_delay_callback',
        'wcpr_settings',
        'wcpr_email_3'
    );

    // Cancelación automática
    add_settings_section(
        'wcpr_cancel',
        'Cancelación automática',
        'wcpr_cancel_section_callback',
        'wcpr_settings'
    );

    add_settings_field(
        'wcpr_cancel_enabled',
        'Habilitado',
        'wcpr_cancel_enabled_callback',
        'wcpr_settings',
        'wcpr_cancel'
    );

    add_settings_field(
        'wcpr_cancel_delay',
        'Retraso (minutos)',
        'wcpr_cancel_delay_callback',
        'wcpr_settings',
        'wcpr_cancel'
    );
}

// Callbacks para Email 1
function wcpr_email_1_section_callback()
{
    echo 'Configurar el primer correo de recuperación después del pago fallido.';
}

function wcpr_email_1_enabled_callback()
{
    $opts = wcpr_get_settings();
    $value = isset($opts['email_1_enabled']) ? $opts['email_1_enabled'] : 'yes';
?>
    <input type="checkbox" name="wcpr_settings[email_1_enabled]" value="yes" <?php checked($value, 'yes'); ?> />
<?php
}

function wcpr_email_1_delay_callback()
{
    $opts = wcpr_get_settings();
    $value = isset($opts['email_1_delay']) ? $opts['email_1_delay'] : 30;
?>
    <input type="number" name="wcpr_settings[email_1_delay]" value="<?php echo esc_attr($value); ?>" min="1" />
<?php
}

// Callbacks para Email 2
function wcpr_email_2_section_callback()
{
    echo 'Configurar el segundo correo de recuperación después del pago fallido.';
}

function wcpr_email_2_enabled_callback()
{
    $opts = wcpr_get_settings();
    $value = isset($opts['email_2_enabled']) ? $opts['email_2_enabled'] : 'yes';
?>
    <input type="checkbox" name="wcpr_settings[email_2_enabled]" value="yes" <?php checked($value, 'yes'); ?> />
<?php
}

function wcpr_email_2_delay_callback()
{
    $opts = wcpr_get_settings();
    $value = isset($opts['email_2_delay']) ? $opts['email_2_delay'] : 360;
?>
    <input type="number" name="wcpr_settings[email_2_delay]" value="<?php echo esc_attr($value); ?>" min="1" />
<?php
}

// Callbacks para Email 3
function wcpr_email_3_section_callback()
{
    echo 'Configurar el tercer correo de recuperación después del pago fallido.';
}

function wcpr_email_3_enabled_callback()
{
    $opts = wcpr_get_settings();
    $value = isset($opts['email_3_enabled']) ? $opts['email_3_enabled'] : 'yes';
?>
    <input type="checkbox" name="wcpr_settings[email_3_enabled]" value="yes" <?php checked($value, 'yes'); ?> />
<?php
}

function wcpr_email_3_delay_callback()
{
    $opts = wcpr_get_settings();
    $value = isset($opts['email_3_delay']) ? $opts['email_3_delay'] : 1440;
?>
    <input type="number" name="wcpr_settings[email_3_delay]" value="<?php echo esc_attr($value); ?>" min="1" />
<?php
}

// Callbacks para Cancelación
function wcpr_cancel_section_callback()
{
    echo 'Cancelar automáticamente las órdenes no pagadas.';
}

function wcpr_cancel_enabled_callback()
{
    $opts = wcpr_get_settings();
    $value = isset($opts['cancel_enabled']) ? $opts['cancel_enabled'] : 'yes';
?>
    <input type="checkbox" name="wcpr_settings[cancel_enabled]" value="yes" <?php checked($value, 'yes'); ?> />
<?php
}

function wcpr_cancel_delay_callback()
{
    $opts = wcpr_get_settings();
    $value = isset($opts['cancel_delay']) ? $opts['cancel_delay'] : 2160;
?>
    <input type="number" name="wcpr_settings[cancel_delay]" value="<?php echo esc_attr($value); ?>" min="1" />
<?php
}

