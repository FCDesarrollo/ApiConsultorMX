<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ConsumoController extends Controller
{
    public function ValidarConexion($RFCEmpresa, $Usuario, $Password, $TipoDocumento, $Modulo, $Menu, $SubMenu){

        $conexion[0]['error'] = 0;
        
        $idempresa = DB::connection("General")->select("SELECT idempresa, rutaempresa FROM mc1000 WHERE RFC = '$RFCEmpresa'");
        
        if(!empty($idempresa)){

            $Pwd = md5($Password);

            $conexion[0]['idempresa'] = $idempresa[0]->idempresa;

            $idusuario = DB::connection("General")->select("SELECT idusuario, password FROM mc1001 WHERE correo = '$Usuario'");

            if(!empty($idusuario)){                 

                $conexion[0]['idusuario'] = $idusuario[0]->idusuario;

                $ID = $idusuario[0]->idusuario;

                if($Pwd == $idusuario[0]->password){

                    ConnectDatabase($idempresa[0]->idempresa);                    

                    $permisos = DB::select("SELECT modulo.tipopermiso AS modulo, menu.tipopermiso AS menu, submenu.tipopermiso AS submenu FROM mc_usermod modulo, mc_usermenu menu, mc_usersubmenu submenu WHERE modulo.idusuario = $ID And menu.idusuario = $ID And submenu.idusuario = $ID And modulo.idmodulo = $Modulo AND menu.idmenu = $Menu AND submenu.idsubmenu = $SubMenu;");


                    //$conexion[0]['tipopermiso'] = $permisos[0]->tipopermiso;

                    if($permisos[0]->modulo != 0 And $permisos[0]->menu != 0 And $permisos[0]->submenu != 0){

                        $conexion[0]['permisomodulo'] = $permisos[0]->modulo;
                        $conexion[0]['permisomenu'] = $permisos[0]->menu;
                        $conexion[0]['permisosubmenu'] = $permisos[0]->submenu;

                        $tipodocto = DB::connection("General")->select("SELECT tipo FROM mc1011 WHERE clave = $TipoDocumento");

                        if(!empty($tipodocto)){                            
                            $conexion[0]['tipodocumento'] = $tipodocto[0]->tipo;
                        }else{
                            $conexion[0]['error'] = 5; //Tipo de documento no valido
                        }
                    }else{
                        $conexion[0]['error'] = 4; //El Usuario no tiene permisos
                    }
                }else{
                    $conexion[0]['error'] = 3; //Contraseña Incorrecta
                }
            }else{
                $conexion[0]['error'] = 2; //Correo Incorrecto          
            }   
        }else{
            $conexion[0]['error'] = 1; //RFC no existe
            //$conexion[0]['idusuario'] = 1;
        }
        return $conexion;
    }

    public function ObtenerDatos(Request $request){ 
        
        $RFCEmpresa = $request->rfcempresa;
        $Usuario = $request->usuario;
        $Pwd = $request->pwd;
        $FecI = $request->fechai;
        $FecF = $request->fechaf;
        $TipoDocumento = $request->tipodocto;

        $autenticacion = $this->ValidarConexion($RFCEmpresa, $Usuario, $Pwd, $TipoDocumento, 2, 6, 17);  

        $array[0]["error"] = $autenticacion[0]["error"];
        
        if($autenticacion[0]['error'] == 0){     

            ConnectDatabase($autenticacion[0]['idempresa']);
            
            $doctos = DB::select("SELECT l.fechadecarga, l.usuario, d.* FROM mc_lotes l, mc_lotesdocto d WHERE l.id = d.idlote AND fecha >= '$FecI' AND fecha <= '$FecF' AND l.tipo = $TipoDocumento");

            if(empty($doctos)){
                $doctos[0]["id"] = NULL;
            }else{
                for($i=0; $i < count($doctos); $i++){
                    
                    $idusuario = $doctos[$i]->usuario;            

                    $datosuser = DB::connection("General")->select("SELECT nombre FROM mc1001 WHERE idusuario = $idusuario");

                    $doctos[$i]->usuario = $datosuser[0]->nombre;
                }                
            }

            $movtos = DB::select("SELECT m.* FROM mc_lotes l, mc_lotesdocto d, mc_lotesmovtos m WHERE l.id = d.idlote AND d.id = m.iddocto AND d.fecha >= '$FecI' AND d.fecha <= '$FecF' AND l.tipo = $TipoDocumento");

            if(empty($movtos)){
                $movtos[0]["id"] = NULL;
            }

            $array[1] = $doctos;
            $array[2] = $movtos;

        }
        
        //echo $array[0]['error'];

        return $array;

    }

    function ProcesarLote(Request $request){

        $RFCEmpresa = $request->rfcempresa;
        $Usuario = $request->usuario;
        $Pwd = $request->pwd;        
        $TipoDocumento = $request->tipodocto;
        $registros = $request->registros;

        $a  = isset($request->conexion) ? $request->conexion : 0;

        $autenticacion = $this->ValidarConexion($RFCEmpresa, $Usuario, $Pwd, $TipoDocumento, 2, 6, 17);  

        $array[0]["error"] = $autenticacion[0]["error"];

        if($autenticacion[0]['error'] == 0){
            
            ConnectDatabase($autenticacion[0]['idempresa']);
            
            for ($i=0; $i < count($registros); $i++) {               
                
                $id = $registros[$i]['iddocto'];
                $idadw = $registros[$i]['iddoctoadw'];                
                
                DB::table('mc_lotesdocto')->where("id", $id)->update(['idadw' => $idadw, 'idsupervisor' => $autenticacion[0]["idusuario"], 'fechaprocesado' => now(), 'estatus' => "1"]);                

            }

        }

        return $array;
        
    }    
}
