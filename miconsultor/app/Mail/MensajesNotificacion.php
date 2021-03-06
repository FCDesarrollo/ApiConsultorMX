<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class MensajesNotificacion extends Mailable
{
    use Queueable, SerializesModels;

    public $datosNotificacion;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($datosNotificacion)
    {
        $this->datosNotificacion = $datosNotificacion;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->datosNotificacion[0]["encabezado"])->view('mails.correonotificacion');
    }
}
