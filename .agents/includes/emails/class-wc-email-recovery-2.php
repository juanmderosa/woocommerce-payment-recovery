<?php

if (! defined('ABSPATH')) exit;

class WC_Email_Payment_Recovery_2 extends WC_Email_Payment_Recovery_Base
{

    public function __construct()
    {

        $this->id = 'payment_recovery_2';
        $this->title = 'Payment Recovery 2';
        $this->description = 'Recordatorio de pago pendiente. (segundo e-mail).';

        $this->heading = 'Tu look sigue reservado';
        $this->subject = 'Tu look sigue reservado';

        parent::__construct();
    }

    protected function get_message()
    {

        return "Las prendas que elegiste siguen reservadas para vos, pero tu pago aún está pendiente.

Si querés asegurarte tu pedido, podés completarlo ahora y nosotros nos encargamos del resto";
    }
}
