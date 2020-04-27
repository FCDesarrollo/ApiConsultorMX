<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerfilesController extends Controller
{
    public function listaPerfiles(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $todos = $request->all;
            if ($todos == 1) {
                $perfiles = DB::select('select * from mc_profiles', []); 
            }else{
                $perfiles = DB::select('select * from mc_profiles where status = ?', [1]);
            }
            $array["perfiles"] = $perfiles;
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function agregarPerfil(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $permisosDatos = $request->permisosdatos;
            $nombre = $request->nombre;
            $descripcion = $request->descripcion;
            $fecha = $request->fecha;
            $perfil = DB::select('select * from mc_profiles order by idperfil desc limit 1');
            $idperfil = $perfil[0]->idperfil + 1;
            DB::insert('insert into mc_profiles (idperfil, nombre, descripcion, fecha, status) values (?, ?, ?, ?, ?)', [$idperfil, $nombre, $descripcion, $fecha, 1]);
            for($x=0 ; $x<count($permisosDatos) ; $x++) {
                DB::insert('insert into mc_modpermis (idperfil, idmodulo, tipopermiso) values (?, ?, ?)', [$idperfil, $permisosDatos[$x]["idModulo"], $permisosDatos[$x]["permisos"]]);
                for($y=0; $y<count($permisosDatos[$x]["menus"]) ; $y++) {
                    DB::insert('insert into mc_menupermis (idperfil, idmodulo, idmenu, tipopermiso) values (?, ?, ?, ?)', [$idperfil, $permisosDatos[$x]["menus"][$y]["idModulo"], $permisosDatos[$x]["menus"][$y]["idMenu"], $permisosDatos[$x]["menus"][$y]["permisos"]]);
                    for($z=0; $z<count($permisosDatos[$x]["menus"][$y]["submenus"]) ; $z++) {
                        DB::insert('insert into mc_submenupermis (idperfil, idmenu, idsubmenu, tipopermiso, notificaciones) values (?, ?, ?, ?, ?)', [$idperfil, $permisosDatos[$x]["menus"][$y]["submenus"][$z]["idMenu"], $permisosDatos[$x]["menus"][$y]["submenus"][$z]["idSubmenu"], $permisosDatos[$x]["menus"][$y]["submenus"][$z]["permisos"], $permisosDatos[$x]["menus"][$y]["submenus"][$z]["permisosNotificaciones"]]);
                    }
                }
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
}
