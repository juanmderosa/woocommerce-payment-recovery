<?php

if (! defined('ABSPATH')) exit;

class WC_Email_Payment_Recovery_2 extends WC_Email_Payment_Recovery_Base
{

    public function __construct()
    {

        $this->id = 'payment_recovery_2';
        $this->title = 'Payment Recovery 2';
        $this->description = 'Recordatorio de pago pendiente. (segundo e-mail).';

        $this->heading = 'Tu pedido sigue pendiente';
        $this->subject = 'Recordatorio: tu pedido sigue reservado';

        parent::__construct();
    }

    protected function get_message()
    {

        return "Te recordamos que tu pago aún no se completó, pero tu pedido sigue reservado.";
    }
}
