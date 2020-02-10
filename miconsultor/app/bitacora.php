<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class bitacora extends Model
{
    //
    protected $connection = 'mysql';
    protected $table = 'mc_';
    public $timestamps = false;
}
