<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class documento extends Model
{
    //
    protected $connection = 'mysql';
    protected $table = 'mc_requerimientos_doc';
    public $timestamps = false;
}
