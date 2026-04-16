<?php

if (! defined('ABSPATH')) exit;

abstract class WC_Email_Payment_Recovery_Base extends WC_Email
{

    public function trigger($order_id)
    {

        if (!wcpr_order_still_unpaid($order_id)) {
            return;
        }

        $this->object = wc_get_order($order_id);

        if (!$this->object) return;

        $this->recipient = $this->object->get_billing_email();

        if (!$this->is_enabled() || !$this->get_recipient()) {
            return;
        }

        $this->send(
            $this->get_recipient(),
            $this->get_subject(),
            $this->get_content(),
            $this->get_headers(),
            $this->get_attachments()
        );
    }

    public function get_content_html()
    {
        $order = $this->object;

        ob_start();

        // Calcular horas de cancelación AL INICIO
        $cancel_hours = 36;
        if (function_exists('wcpr_get_email_settings')) {
            try {
                $settings = wcpr_get_email_settings();
                if (isset($settings['cancel_delay']) && $settings['cancel_delay']) {
                    $cancel_hours = ceil($settings['cancel_delay'] / 60);
                }
            } catch (Exception $e) {
                // usar valor por defecto
            }
        }

        // Hook: Encabezado del email (incluye logo)
        do_action('woocommerce_email_header', $this->get_heading(), $this); ?>

        <div class="email-introduction">
            <p><?php printf(
                    /* translators: %s: Customer first name */
                    esc_html_x('Hola %s,', 'customer name', 'wc-payment-recovery'),
                    esc_html($order->get_billing_first_name() ?: '')
                ); ?></p>
            <p><?php echo wp_kses_post($this->get_message()); ?></p>
            <p><?php esc_html_e('Aquí está el resumen de tu pedido:', 'wc-payment-recovery'); ?></p>
        </div>

        <?php
        /*
         * @hooked WC_Emails::order_details() - Muestra tabla de detalles
         * @hooked WC_Structured_Data::generate_order_data() - Datos estructurados
         */
        do_action('woocommerce_email_order_details', $order, $this->is_enabled(), false, $this);
        ?>

        <table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation">
            <tr>
                <td class="td" style="text-align: center; padding: 20px 0;">
                    <a href="<?php echo esc_url($order->get_checkout_payment_url()); ?>" class="button button-primary" style="font-weight: normal;text-decoration: underline;display: inline-block;background-color: #1b1626; color: #ffffff;border-radius: 6px;padding: 0.5em 1em;"><?php esc_html_e('Completar pago', 'wc-payment-recovery'); ?></a>
                </td>
            </tr>
            <tr>
                <td class="td" style="text-align: center; padding: 10px 0;">
                    <a href="<?php echo esc_url(wcpr_generate_cart_restore_link($order)); ?>" class="button" style="font-weight: normal;text-decoration: underline;display: inline-block;background-color: #494551; color: #ffffff;border-radius: 6px;padding: 0.5em 1em;"><?php esc_html_e('Volver al carrito', 'wc-payment-recovery'); ?></a>
                </td>
            </tr>
        </table>

        <table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation">
            <tr>
                <td class="td" style="text-align: left;">
                    <p><?php printf(
                            /* translators: %s: hours */
                            esc_html__('Tu pedido está reservado durante %s horas desde el momento que creaste tu pedido.', 'wc-payment-recovery'),
                            esc_html($cancel_hours)
                        ); ?></p>
                </td>
            </tr>
        </table>

        <?php

        /*
         * @hooked WC_Emails::order_meta() - Meta de la orden
         */
        do_action('woocommerce_email_order_meta', $order, $this->is_enabled(), false, $this);

        /*
         * @hooked WC_Emails::customer_details() - Detalles del cliente
         */
        do_action('woocommerce_email_customer_details', $order, $this->is_enabled(), false, $this);

        ?>

<?php

        // Hook: Footer del email
        do_action('woocommerce_email_footer', $this);

        return ob_get_clean();
    }

    abstract protected function get_message();
}
