<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class MensajeLayoutsPagosAdicionales extends Mailable
{
    use Queueable, SerializesModels;

    public $datosCorreo;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($datosCorreo)
    {
        $this->datosCorreo = $datosCorreo;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->datosCorreo["titulo"])->view('mails.correolayoutspagosadicionales');
    }
}
