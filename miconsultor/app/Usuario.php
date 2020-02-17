<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $connection = 'General';
    protected $table = 'mc1001';
    protected $primaryKey = 'idusuario';
    public $timestamps = false;

    public function requerimiento() {
        return $this->hasMany('App\Requerimiento', 'id_usuario','idusuario'); 
        // Estoy suponiendo que el id del rol lo almacenás 
        // en el campo role_id pero si se llama de otra manera 
        // debes modificar eso
    }
}
