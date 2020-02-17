<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class MensajesValidacion extends Mailable
{
    use Queueable, SerializesModels;

    public $datosUser;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($datosUser)
    {
        $this->datosUser = $datosUser;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Confirma tu Cuenta')->view('mails.correovalidacion');
    }
}
