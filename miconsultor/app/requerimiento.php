<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class requerimiento extends Model
{
    protected $connection = 'mysql';
    protected $table = 'mc_requerimientos';
    protected $primaryKey = 'id_req';
    public $timestamps = false;
}
