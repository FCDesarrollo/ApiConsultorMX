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
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
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

    public function actualizaPermisosUsuario(Request $request)
    {
        set_time_limit(300);
        $valida = verificaUsuario($request->usuario, $request->pwd);

        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $usuarios = DB::connection("General")->select("SELECT m2.idusuario,m0.empresaBD FROM mc1002 m2 
                                                INNER JOIN mc1000 m0 ON m2.idempresa=m0.idempresa" );
            for ($i=0; $i <count($usuarios) ; $i++) { 
                $bdd = $usuarios[$i]->empresaBD;
                $idusuario = $usuarios[$i]->idusuario;
                ConnectaEmpresaDatabase($bdd); 
                if ($bdd != "") {   
                    $userperfil = DB::select('select idperfil from mc_userprofile where idusuario = ?', [$idusuario]);
                    if (!empty($userperfil)) {
                        $idperfil = $userperfil[0]->idperfil;
                        $modper = DB::select('select idmodulo,tipopermiso from mc_modpermis where idperfil = ?', [$idperfil]);
                        for ($x=0; $x < count($modper); $x++) { 
                            $idmodulo = $modper[$x]->idmodulo;
                            $usemod = DB::select('select id from mc_usermod where idusuario = ? AND idperfil = ? 
                                                    AND idmodulo = ? ', [$idusuario, $idperfil, $idmodulo]);
                            if (empty($usemod)) {
                                $tipopermiso = $modper[$x]->tipopermiso;
                                DB::insert('insert into mc_usermod (idusuario, idperfil, idmodulo,tipopermiso) values 
                                                (?, ?, ? , ?)', [$idusuario, $idperfil, $idmodulo, $tipopermiso]);     
                            }

                            $menuper = DB::select('select idmenu,tipopermiso from mc_menupermis where 
                                                    idperfil = ? AND idmodulo = ?', [$idperfil, $idmodulo]);
                            for ($z=0; $z < count($menuper); $z++) {
                                $idmenu = $menuper[$z]->idmenu; 
                                $usermenu = DB::select('select id from mc_usermenu where idusuario = ? AND idperfil = ? 
                                                            AND idmodulo = ? AND idmenu = ?', [$idusuario, $idperfil, $idmodulo, $idmenu]);
                                if (empty($usermenu)) {
                                    $tipopermiso = $menuper[$z]->tipopermiso;
                                    DB::insert('insert into mc_usermenu (idusuario, idperfil, idmodulo, idmenu, tipopermiso) 
                                            values (?, ?, ?, ?, ?)', [$idusuario, $idperfil, $idmodulo,$idmenu, $tipopermiso]);
                                }

                                $submenuper = DB::select('select idsubmenu,tipopermiso,notificaciones from mc_submenupermis 
                                                            where idperfil = ? AND idmenu = ? ', [$idperfil, $idmenu]);
                                for ($r=0; $r < count($submenuper); $r++) { 
                                    $idsubmenu = $submenuper[$r]->idsubmenu;
                                    $usersubmenu = DB::select('select id from mc_usersubmenu where idusuario = ? AND idperfil = ? 
                                                        AND idmenu = ? AND idsubmenu = ?', [$idusuario, $idperfil, $idmenu, $idsubmenu]);
                                    if (empty($usersubmenu)) {
                                        $tipopermiso = $submenuper[$r]->tipopermiso;
                                        $notificacion = $submenuper[$r]->notificaciones;
                                        DB::insert('insert into mc_usersubmenu (idusuario, idperfil, idmenu, idsubmenu, tipopermiso, notificaciones)
                                                                values (?, ?, ?, ?, ?, ?)', [$idusuario, $idperfil, $idmenu, $idsubmenu,$tipopermiso,$notificacion]);
                                    }

                                }
                            }
                        }
                    }
                }
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function creaTablasRequerimientos(Request $request)
    {
        set_time_limit(300);
        $valida = verificaUsuario($request->usuario, $request->pwd);

        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] == 0){
            $empresas = DB::connection("General")->select("SELECT rutaempresa FROM mc1000" );
            for ($i=0; $i < count($empresas); $i++) { 
                $empresaBD = $empresas[$i]->rutaempresa;        
                ConnectaEmpresaDatabase($empresaBD);
                
                $mc_conceptos = "create table if not exists mc_conceptos (
                    id int(11) NOT NULL AUTO_INCREMENT,
                    nombre_concepto varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL,
                    descripcion varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL,
                    fecha date DEFAULT NULL,
                    status int(11) DEFAULT NULL,
                    PRIMARY KEY (id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
                    DB::statement($mc_conceptos);

                $mc_requerimientos = "create table if not exists mc_requerimientos (
                    idReq int(11) NOT NULL AUTO_INCREMENT,
                    fecha date DEFAULT NULL,
                    id_usuario int(11) DEFAULT NULL,
                    id_departamento int(11) DEFAULT NULL,
                    descripcion varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL,
                    importe_estimado double DEFAULT NULL,
                    estado_documento int(11) DEFAULT NULL,
                    id_concepto int(11) DEFAULT NULL,
                    serie varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL,
                    folio varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL,
                    PRIMARY KEY (idReq)
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
                    DB::statement($mc_requerimientos);
    
                  $mc_requerimientos_bit = "create table if not exists mc_requerimientos_bit (
                    id_bit int(11) NOT NULL,
                    id_req int(11) DEFAULT NULL,
                    fecha date DEFAULT NULL,
                    status int(11) DEFAULT NULL,
                    PRIMARY KEY (id_bit)
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
                  DB::statement($mc_requerimientos_bit);

                  $mc_requerimientos_doc = "create table if not exists mc_requerimientos_doc (
                        id_req int(11) DEFAULT NULL,
                        documento varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL,
                        codigo_documento varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL,
                        tipo_doc int(11) DEFAULT NULL,
                        download varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL
                        ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;";
                  DB::statement($mc_requerimientos_doc);
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
}
