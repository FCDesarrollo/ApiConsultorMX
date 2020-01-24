<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class AppController extends Controller
{
    public function DatosUsuarios(Request $request)
    {
        $usuario = $request->usuario;
        $pwd = $request->pwd;

        $autenticacion  = $this->ValidaUsuario($usuario, $pwd);
        
        $array["error"] = $autenticacion[0]["error"];

        if($autenticacion[0]['error'] == 0){
            $idusuario = $autenticacion[0]['idusuario'];

            $array["menus"] = DB::connection("General")->select("SELECT * FROM mc1005 WHERE idmenu=5");

            $empresas = DB::connection("General")->select("SELECT e.* FROM mc1000 e INNER JOIN mc1002 r ON e.idempresa=r.idempresa 
                                                            WHERE idusuario=$idusuario");
            for ($i=0; $i < count($empresas); $i++) {

                //$array["empresas"][$i] = $empresas[$i];
                ConnectDatabase($empresas[$i]->idempresa);
                $sucursales = DB::select("SELECT * FROM mc_catsucursales");
                $empresas[$i]->sucursales = $sucursales;
   
                $permisos = DB::select("SELECT * FROM mc_usersubmenu WHERE idmenu = 5 AND idusuario=$idusuario");

                $empresas[$i]->permisos = $permisos;
                $array["empresas"][$i] = $empresas[$i];

            }

        }

        return $array;

    }

    public function ValidaUsuario($Usuario , $Password)
    {
        $conexion[0]['error'] = 0;

        $Pwd = $Password;
        $Usuario = DB::connection("General")->select("SELECT idusuario, password FROM mc1001 WHERE correo = '$Usuario'");
        if(!empty($Usuario)){                 

            $conexion[0]['idusuario'] = $Usuario[0]->idusuario;

            $ID = $Usuario[0]->idusuario;

            //if(password_verify($request->contra, $hash_BD)) {
            if(password_verify($Pwd, $Usuario[0]->password)) {

                // if($Modulo != 0 && $Menu != 0 && $SubMenu != 0){

                //     ConnectDatabase($idempresa[0]->idempresa);                    

                //     $permisos = DB::select("SELECT modulo.tipopermiso AS modulo, menu.tipopermiso AS menu, submenu.tipopermiso AS submenu FROM mc_usermod modulo, mc_usermenu menu, mc_usersubmenu submenu WHERE modulo.idusuario = $ID And menu.idusuario = $ID And submenu.idusuario = $ID And modulo.idmodulo = $Modulo AND menu.idmenu = $Menu AND submenu.idsubmenu = $SubMenu;");

                //     if(!empty($permisos)){
                //         //if($permisos[0]->modulo != 0 And $permisos[0]->menu != 0 And $permisos[0]->submenu != 0){

                //             $conexion[0]['permisomodulo'] = $permisos[0]->modulo;
                //             $conexion[0]['permisomenu'] = $permisos[0]->menu;
                //             $conexion[0]['permisosubmenu'] = $permisos[0]->submenu;

                //             if($TipoDocumento != 0){
                //                 $tipodocto = DB::connection("General")->select("SELECT tipo FROM mc1011 WHERE clave = $TipoDocumento");

                //                 if(!empty($tipodocto)){                            
                //                     $conexion[0]['tipodocumento'] = $tipodocto[0]->tipo;
                //                 }else{
                //                     $conexion[0]['error'] = 5; //Tipo de documento no valido
                //                 }
                //             }                    

                //         //}else{
                //         //    $conexion[0]['error'] = 4; //El Usuario no tiene permisos
                //         //}                        

                //     }else{
                //         $conexion[0]['error'] = 4; //El Usuario no tiene permisos
                //     }
                
                // }

            }else{
                $conexion[0]['error'] = 3; //Contraseña Incorrecta
            }
        }else{
            $conexion[0]['error'] = 2; //Correo Incorrecto          
        }
        return $conexion;
    }
}
