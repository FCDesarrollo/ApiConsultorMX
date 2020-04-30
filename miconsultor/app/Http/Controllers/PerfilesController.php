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

    public function eliminarPerfil(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $idPerfil = $request->idperfil;
            DB::table('mc_profiles')->where("idperfil", $idPerfil)->delete();
            DB::table('mc_modpermis')->where("idperfil", $idPerfil)->delete();
            DB::table('mc_menupermis')->where("idperfil", $idPerfil)->delete();
            DB::table('mc_submenupermis')->where("idperfil", $idPerfil)->delete();
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function datosPerfil(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $idPerfil = $request->idperfil;
            $perfil = DB::select('select * from mc_profiles where idperfil = ?', [$idPerfil]);
            $array["perfil"] = $perfil;

            $empresa = DB::connection("General")->select('select * from mc1000 where rfc = ?', [$request->rfc]);
            $bdd = $empresa[0]->rutaempresa;

            $modulos = DB::select("SELECT g.*, (SELECT m.tipopermiso FROM $bdd.mc_modpermis m WHERE m.idperfil = ? AND m.idmodulo = g.idmodulo) AS permisos FROM " . env('DB_DATABASE_GENERAL') . ".mc1003 g", [$idPerfil]);
            for ($i=0; $i < count($modulos); $i++) {
                $idmodulo =  $modulos[$i]->idmodulo;
                $menus = DB::select("SELECT g.*, (SELECT m.tipopermiso FROM $bdd.mc_menupermis m WHERE m.idperfil = ? AND m.idmenu = g.idmenu) AS permisos FROM " . env('DB_DATABASE_GENERAL') . ".mc1004 g WHERE g.idmodulo = ?", [$idPerfil, $idmodulo]);
                $modulos[$i]->menus = $menus;
                for ($x=0; $x < count($menus); $x++) {
                    $idmenu = $menus[$x]->idmenu;
                    $submenus = DB::select("SELECT g.*, (SELECT m.tipopermiso FROM $bdd.mc_submenupermis m WHERE m.idperfil = ? AND m.idsubmenu = g.idsubmenu) AS permisos, (SELECT m.notificaciones FROM $bdd.mc_submenupermis m WHERE m.idperfil = ? AND m.idsubmenu = g.idsubmenu) AS permisosNotificaciones FROM " . env('DB_DATABASE_GENERAL') . ".mc1005 g WHERE g.idmenu = ?", [$idPerfil, $idPerfil, $idmenu]);
                    $menus[$x]->submenus = $submenus;
                }
                $array["modulos"][$i] = $modulos[$i];
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function editarPerfil(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd,$request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $permisosDatos = $request->permisosdatos;
            $nombre = $request->nombre;
            $descripcion = $request->descripcion;
            $idperfil = $request->idperfil;
            DB::update('update mc_profiles set nombre = ?, descripcion = ? where idperfil = ?', [$nombre, $descripcion, $idperfil]);
            for($x=0 ; $x<count($permisosDatos) ; $x++) {
                DB::update('update mc_modpermis set tipopermiso = ? where idperfil = ? and idmodulo = ?', [$permisosDatos[$x]["permisos"], $idperfil, $permisosDatos[$x]["idModulo"]]);
                for($y=0; $y<count($permisosDatos[$x]["menus"]) ; $y++) {
                    DB::update('update mc_menupermis set tipopermiso = ? where idperfil = ? and idmenu = ?', [$permisosDatos[$x]["menus"][$y]["permisos"], $idperfil, $permisosDatos[$x]["menus"][$y]["idMenu"]]);
                    for($z=0; $z<count($permisosDatos[$x]["menus"][$y]["submenus"]) ; $z++) {
                        DB::update('update mc_submenupermis set tipopermiso = ?, notificaciones = ? where idperfil = ? and idsubmenu = ?', [$permisosDatos[$x]["menus"][$y]["submenus"][$z]["permisos"], $permisosDatos[$x]["menus"][$y]["submenus"][$z]["permisosNotificaciones"], $idperfil, $permisosDatos[$x]["menus"][$y]["submenus"][$z]["idSubmenu"]]);
                    }
                }
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
}
