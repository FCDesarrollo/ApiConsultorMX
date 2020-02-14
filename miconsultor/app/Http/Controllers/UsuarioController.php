<?php


namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    function inicioUsuario(Request $request)
    {
        $valida = verificaLogin($request->usuario, $request->pwd);

        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $array["usuario"] = $valida[0]['usuario'];
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function permisosUsuario(Request $request)
    {
        $rfc = $request->rfc;
        $valida = verificaUsuario($request->usuario, $request->pwd);

        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            
            ConnectDatabaseRFC($rfc);

            $usuario = $valida[0]['usuario'];
            $idusuario = $usuario[0]->idusuario;
            $modpermiso = DB::select('select idmodulo,tipopermiso,nombre AS nombreperfil from mc_usermod muser 
			                        INNER JOIN mc_profiles mp ON muser.idperfil = mp.idperfil where idusuario = ?', [$idusuario]);
            for ($i=0; $i < count($modpermiso); $i++) {
                $idmodulo = $modpermiso[$i]->idmodulo; 
                $menupermiso = DB::select('select idmenu,tipopermiso from mc_usermenu 
                                where idusuario = ? AND idmodulo= ?', [$idusuario, $idmodulo]);
                for ($x=0; $x < count($menupermiso); $x++) { 
                    $idmenu = $menupermiso[$x]->idmenu;
                    $submenupermiso = DB::select('select idsubmenu,tipopermiso,notificaciones from mc_usersubmenu 
                                        where idusuario = ? AND idmenu = ?', [$idusuario, $idmenu]);
                    $menupermiso[$x]->permisossubmenus = $submenupermiso;
                }
                $modpermiso[$i]->permisosmenu = $menupermiso;
                $array["permisomodulos"][$i] = $modpermiso[$i];
            }
            
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
}
