<?php

if (! defined('ABSPATH')) exit;

class WC_Email_Payment_Recovery_Cancel extends WC_Email_Payment_Recovery_Base
{

    public function __construct()
    {

        $this->id = 'payment_recovery_cancel';
        $this->title = 'Payment Recovery - Cancelación';
        $this->description = 'Email enviado cuando la orden se cancela por falta de pago.';

        $this->heading = 'Tu pedido ha sido cancelado';
        $this->subject = 'Pedido #{order_number} ha sido cancelado';

        parent::__construct();
    }

    /**
     * Override de get_subject para reemplazar placeholders correctamente
     */
    public function get_subject()
    {
        $subject = parent::get_subject();

        if ($this->object) {
            $subject = str_replace(
                array('{order_number}', '{site_title}'),
                array($this->object->get_order_number(), $this->get_blogname()),
                $subject
            );
        }

        return $subject;
    }

    /**
     * Override de get_heading para reemplazar placeholders
     */
    public function get_heading()
    {
        $heading = parent::get_heading();

        if ($this->object) {
            $heading = str_replace(
                array('{order_number}', '{site_title}'),
                array($this->object->get_order_number(), $this->get_blogname()),
                $heading
            );
        }

        return $heading;
    }

    /**
     * Override del trigger - Para cancelación NO verificamos si está unpaid
     */
    public function trigger($order_id)
    {
        wcpr_log('🔴 Trigger de email de cancelación: INICIANDO', ['order_id' => $order_id, 'class' => get_class($this)]);

        // Para cancelación, NO verificamos si está unpaid
        // La orden ya se canceló, así que simplemente enviamos el email

        $this->object = wc_get_order($order_id);

        if (!$this->object) {
            wcpr_log('❌ ERROR: No se pudo obtener la orden para email de cancelación', ['order_id' => $order_id]);
            return;
        }

        wcpr_log('✓ Orden obtenida', ['order_id' => $order_id, 'status' => $this->object->get_status()]);

        // Verificar que la orden esté realmente cancelada
        if ($this->object->get_status() !== 'cancelled') {
            wcpr_log('❌ ERROR: Email de cancelación - orden NO está en estado cancelled', ['order_id' => $order_id, 'status' => $this->object->get_status()]);
            return;
        }

        $this->recipient = $this->object->get_billing_email();

        wcpr_log('✓ Recipiente: ' . $this->recipient, ['order_id' => $order_id]);

        if (!$this->is_enabled()) {
            wcpr_log('❌ ERROR: Email de cancelación está DESHABILITADO en configuración de WooCommerce', ['order_id' => $order_id]);
            return;
        }

        if (!$this->get_recipient()) {
            wcpr_log('❌ ERROR: No hay destinatario para el email', ['order_id' => $order_id]);
            return;
        }

        wcpr_log('📧 Preparando envío de email de cancelación', ['order_id' => $order_id, 'recipient' => $this->recipient]);

        try {
            $subject = $this->get_subject();
            $content = $this->get_content();

            wcpr_log('✓ Email listo:', [
                'order_id' => $order_id,
                'subject' => $subject,
                'content_length' => strlen($content)
            ]);

            $this->send(
                $this->get_recipient(),
                $subject,
                $content,
                $this->get_headers(),
                $this->get_attachments()
            );
            wcpr_log('✅ Email de cancelación enviado exitosamente', ['order_id' => $order_id, 'to' => $this->recipient]);
        } catch (Exception $e) {
            wcpr_log('❌ ERROR al enviar email de cancelación: ' . $e->getMessage(), ['order_id' => $order_id, 'trace' => substr($e->getTraceAsString(), 0, 500)]);
        }
    }

    protected function get_message()
    {
        $cancel_hours = 36; // por defecto

        if (function_exists('wcpr_get_email_settings')) {
            try {
                $settings = wcpr_get_email_settings();
                if (isset($settings['cancel_delay']) && $settings['cancel_delay']) {
                    $cancel_hours = ceil($settings['cancel_delay'] / 60);
                }
            } catch (Exception $e) {
                wcpr_log('⚠️ Error al obtener configuración de cancelación', ['error' => $e->getMessage()]);
            }
        }

        return "Lamentamos informarte que tu pedido ha sido cancelado por falta de pago después de {$cancel_hours} horas. Si deseas, puedes volver a realizar la compra desde el inicio.";
    }

    /**
     * Contenido HTML del email de cancelación
     */
    public function get_content_html()
    {
        $order = $this->object;

        ob_start();

        // Hook: Encabezado del email (incluye logo)
        do_action('woocommerce_email_header', $this->get_heading(), $this); ?>

        <div class="email-introduction">
            <p><?php printf(
                    /* translators: %s: Customer first name */
                    esc_html_x('Hola %s,', 'customer name', 'wc-payment-recovery'),
                    esc_html($order->get_billing_first_name() ?: '')
                ); ?></p>
            <p><?php echo wp_kses_post($this->get_message()); ?></p>
            <p><?php esc_html_e('Aquí está el resumen del pedido cancelado:', 'wc-payment-recovery'); ?></p>
        </div>

        <?php

        /*
         * @hooked WC_Emails::order_details() - Muestra tabla de detalles
         * @hooked WC_Structured_Data::generate_order_data() - Datos estructurados
         */
        do_action('woocommerce_email_order_details', $order, $this->is_enabled(), false, $this);

        /*
         * @hooked WC_Emails::order_meta() - Meta de la orden
         */
        do_action('woocommerce_email_order_meta', $order, $this->is_enabled(), false, $this);

        /*
         * @hooked WC_Emails::customer_details() - Detalles del cliente
         */
        do_action('woocommerce_email_customer_details', $order, $this->is_enabled(), false, $this);

        ?>
        <table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation">
            <tr>
                <td class="td" style="text-align: left; color: #999; font-size: 12px;">
                    <p><?php esc_html_e('Si tienes preguntas sobre tu cancelación, contáctanos.', 'wc-payment-recovery'); ?></p>
                </td>
            </tr>
        </table>

<?php

        // Hook: Footer del email
        do_action('woocommerce_email_footer', $this);

        return ob_get_clean();
    }
}
