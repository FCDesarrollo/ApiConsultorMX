<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class requerimiento extends Model
{
    protected $connection = 'empresa06';
    protected $table = 'mc_requerimientos';
    protected $primaryKey = 'id_req';
    public $timestamps = false;
}
