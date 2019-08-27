<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ConsumoController extends Controller
{
    public function ValidarConexion($RFCEmpresa, $Usuario, $Password){

        $conexion[0]['error'] = 0;
        
        $idempresa = DB::connection("General")->select("SELECT idempresa, rutaempresa FROM mc1000 WHERE RFC = '$RFCEmpresa'");
        
        if(!empty($idempresa)){

            $Pwd = md5($Password);

            $conexion[0]['idempresa'] = $idempresa[0]->idempresa;

            $idusuario = DB::connection("General")->select("SELECT idusuario FROM mc1001 WHERE correo = '$Usuario'");


            if(!empty($idusuario)){                 

                $pass = DB::connection("General")->select("SELECT  FROM mc1001 WHERE idusuario = $idusuario[0]->idusuario And password = '$Password'");    

                if(!empty($idusuario)){

                }else{
                    $conexion[0]['error'] = 3; //Contraseña Incorrecta
                }

                ConnectDatabase($idempresa[0]->idempresa);

                $conexion[0]['idusuario'] = $idusuario[0]->idusuario;

                $validacion = DB::select("SELECT tipopermiso FROM mc_usersubmenu WHERE idusuario = $idusuario[0]->idusuario AND idmenu = 6 GROUP BY tipopermiso");                
                
                $conexion[0]['tipopermiso'] = $validacion[0]->tipopermiso;                   


            }else{
                $conexion[0]['error'] = 2; //Correo Incorrecto          
            }            
            

        }else{

            $conexion[0]['error'] = 1; //
            //$conexion[0]['idusuario'] = 1;

        }

        
        return $conexion;
    }

    public function ObtenerDatos(Request $request){ 
        
        $RFCEmpresa = $request->RFCEmpresa;
        $Usuario = $request->Usuario;
        $Pwd = $request->Pwd;
        $FecI = $request->FechaI;
        $FecF = $request->FechaF;
        $TipoDocumento = $request->TipoDocto;

        $idempresa = $this->ValidarConexion($RFCEmpresa, $Usuario, $Pwd);       
        
        
        if($idempresa[0]['idempresa'] > 0 And $idempresa[0]['idusuario'] > 0){        
        //if($idempresa[0]['idempresa'] > 0 And $idempresa[0]['idusuario'] > 0){        
            //$idempresa = $request->idempresa;
            ConnectDatabase($idempresa[0]['idempresa']);
            $lotes = DB::select("SELECT l.fechadecarga, l.usuario, l.tipo, d.id, d.concepto, d.proveedor, d.fecha, d.campoextra1, d.estatus, d.idadw, m.producto, m.almacen, m.kilometros, m.horometro, m.unidad, m.cantidad, m.total FROM mc_lotes l, mc_lotesdocto d, mc_lotesmovtos m WHERE l.id = d.idlote And d.id = m.iddocto AND fecha >= 'FecI' AND fecha <= 'FecF'");

            for($i=0; $i < count($lotes); $i++){
                
                $idusuario = $lotes[$i]->usuario;            

                $datosuser = DB::connection("General")->select("SELECT nombre FROM mc1001 WHERE idusuario = $idusuario");

                $lotes[$i]->usuario = $datosuser[0]->nombre;
            }        

            return $lotes;            
        }else{

            return $idempresa; // 0 -> Error de conexion (RFC, Usuario o Contraseña no validos).
        }



    }

    function ProcesarLote(Request $request){

        $RFCEmpresa = $request->RFCEmpresa;
        $Usuario = $request->Usuario;
        $Pwd = $request->Pwd;
        $idadw = $request->idadw;
        $iddocto = $request->iddocto;

        $idempresa = $this->ValidarConexion($RFCEmpresa, $Usuario, $Pwd);

         if($idempresa[0]['idempresa'] > 0 And $idempresa[0]['idusuario'] > 0){  

            ConnectDatabase($idempresa[0]['idempresa']);

            $result = DB::table('mc_lotesdocto')->where("id", $iddocto)->update(['idadw' => $idadw, 'estatus' => "1"]);

            return $result;

        }else{
            return $idempresa;
        }
    }    
}
