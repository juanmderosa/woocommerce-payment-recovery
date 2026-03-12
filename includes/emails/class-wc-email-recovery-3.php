<?php

if (! defined('ABSPATH')) exit;

class WC_Email_Payment_Recovery_3 extends WC_Email_Payment_Recovery_Base
{

    public function __construct()
    {

        $this->id = 'payment_recovery_3';
        $this->title = 'Payment Recovery 3';
        $this->description = 'Último recordatorio antes de cancelar el pedido. (tercer e-mail)';

        $this->heading = 'Último recordatorio';
        $this->subject = 'Última oportunidad para completar tu pedido';

        parent::__construct();
    }

    protected function get_message()
    {

        return "Este es el último recordatorio para completar tu pago antes de que el pedido sea cancelado.";
    }
}
