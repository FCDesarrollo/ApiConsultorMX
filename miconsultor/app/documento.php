<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class documento extends Model
{
    //
    protected $connection = 'empresa06';
    protected $table = 'mc_requerimientos_doc';
    public $timestamps = false;
}
