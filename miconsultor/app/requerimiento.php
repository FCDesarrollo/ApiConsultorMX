<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class requerimiento extends Model
{
    protected $connection = 'mysql';
    protected $table = 'mc_requerimientos';
    protected $primaryKey = 'id_req';
    public $timestamps = false;

    public function usuario() {
        return $this->hasOne('App\Usuario', 'idusuario', 'id_usuario');
     }
}
