<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class requerimiento_bit extends Model
{
    //
    protected $connection = 'mysql';
    protected $table = 'mc_requerimientos_bit';
    protected $primaryKey = 'id_bit';
    public $timestamps = false;

}
