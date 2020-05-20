<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ProveedoresController extends Controller
{

    function getEmpresas(Request $request)
    {
        $empresas = DB::connection("General")->select("SELECT * FROM mc1000");

        $array["empresas"] = $empresas;

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function getUsuarios(Request $request)
    {
        $usuarios = DB::connection("General")->select("SELECT mc1001.*, mc1006.nombre AS tipoUsuario FROM mc1001 LEFT JOIN mc1006 ON mc1001.tipo = mc1006.idperfil");

        $array["usuarios"] = $usuarios;

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function getPerfiles(Request $request)
    {
        $perfiles = DB::connection("General")->select("SELECT * FROM mc1006");

        $array["perfiles"] = $perfiles;

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
}