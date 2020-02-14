<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActualizarBaseDatosController extends Controller
{
    public function actualizaPerfilesGeneral(Request $request)
    {
        $valida = verificaUsuario($request->usuario, $request->pwd);

        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){

            $perfiles = DB::connection("General")->select("SELECT idperfil FROM mc1006" );
            
            for ($i=0; $i < count($perfiles); $i++) {
                $idperfil = $perfiles[$i]->idperfil;
                $modulos = DB::connection("General")->select("SELECT idmodulo FROM mc1003");
                for ($x=0; $x < count($modulos) ; $x++) { 
                    $idmodulo = $modulos[$x]->idmodulo;
                    $perModulo = DB::connection("General")->select("SELECT id FROM mc1007 
                                                    WHERE idperfil=$idperfil AND idmodulo=$idmodulo");
                    if (empty($perModulo)){
                        DB::connection("General")->table("mc1007")->insert(["idperfil" => $idperfil,
                                                               "idmodulo" => $idmodulo, "tipopermiso" =>0]);
                    }

                    $menus = DB::connection("General")->select("SELECT idmenu FROM mc1004 WHERE idmodulo=$idmodulo");
                    for ($z=0; $z < count($menus) ; $z++) { 
                        $idmenu = $menus[$z]->idmenu;
                        $perMenu = DB::connection("General")->select("SELECT id FROM mc1008 
                                                    WHERE idperfil=$idperfil AND idmodulo=$idmodulo AND idmenu=$idmenu");
                        if (empty($perMenu)) {
                            DB::connection("General")->table("mc1008")->insert(["idperfil" => $idperfil,
                                                               "idmodulo" => $idmodulo,"idmenu" => $idmenu, "tipopermiso" =>0]);
                        }

                        $submenus = DB::connection("General")->select("SELECT idsubmenu FROM mc1005 WHERE idmenu=$idmenu");
                        for ($r=0; $r < count($submenus); $r++) { 
                            $idsubmenu = $submenus[$r]->idsubmenu;
                            $perSubMenu = DB::connection("General")->select("SELECT id FROM mc1009
                                                WHERE idperfil=$idperfil AND idmenu=$idmenu AND idsubmenu=$idsubmenu");
                            if (empty($perSubMenu)) {
                                DB::connection("General")->table("mc1009")->insert(["idperfil" => $idperfil,
                                                               "idmenu" => $idmenu,"idsubmenu" => $idsubmenu,
                                                                "tipopermiso" =>0, "notificaciones" => 0]);
                            }
                        }

                    }
                }
            }
            
            return json_encode($array, JSON_UNESCAPED_UNICODE);
        }
    }

    public function actualizaPerfilesEmpresa(Request $request)
    {
        $valida = verificaUsuario($request->usuario, $request->pwd);

        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $empresas = DB::connection("General")->select("SELECT rutaempresa FROM mc1000" );
            for ($i=0; $i < count($empresas) ; $i++) { 
                $bdd = $empresas[$i]->rutaempresa;
                ConnectaEmpresaDatabase($bdd);                
                if ($bdd != "") {
                    $mc1007 = "INSERT IGNORE ".$bdd.".mc_modpermis SELECT * FROM dublockc_MCGenerales.mc1007;";
                     DB::statement($mc1007);

                    $mc1008 = "INSERT IGNORE ".$bdd.".mc_menupermis SELECT * FROM dublockc_MCGenerales.mc1008;";
                    DB::statement($mc1008);
                    
                    $mc1009 = "INSERT IGNORE ".$bdd.".mc_submenupermis SELECT * FROM dublockc_MCGenerales.mc1009;";
                    DB::statement($mc1009);
                }
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
}
