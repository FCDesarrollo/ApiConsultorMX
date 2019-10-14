<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class AdministradorController extends Controller
{
    function LoginAdmin(Request $request)
    {
        $usuario = DB::connection("General")->select("SELECT * FROM mc1001 WHERE 
                correo='$request->correo' AND password='$request->contra' AND status=1 AND tipo=4");
       
        $datos = $usuario;       
        return $datos;
    }

    public function numEstadistica(Request $request)
    {   
        $num = DB::connection("General")->select("SELECT count(*) as num FROM $request->tabla ");
        return $num;
    }

    function allempresas(Request $request)
    {
        $iduser = $request->idusuario;
        if ($iduser <> 0){
            $empresas = DB::connection("General")->select("SELECT * FROM mc1000");
            $datos = $empresas; 
        }else{
            $datos = 0;
        }
              
        return $datos;
    }

    public function usuarioadmin($user, $pass){
        $usuario = DB::select("SELECT * FROM mc1001 WHERE correo='$user' AND status=1 ");
        if (!empty($usuario)){
            $hash_BD = $usuario[0]->password;

            if (password_verify($pass, $hash_BD)) {
                $datos = array(
                   "usuario" => $usuario,
                );
                //$datos = $usuario;
              
            } else {
                $datos = "3";
            } 
        }else {
            $datos = "2";
        }
        return $datos;
    }

    public function datosadmin(Request $request)
    {
        return $this->usuarioadmin($request->correo, $request->contra);
    }

    public function empresasadmin(Request $request)
    {
        $valida = $this->usuarioadmin($request->correo, $request->contra);
        if ($valida != "2" and $valida != "3"){
            $usuario = $valida['usuario'];
            $iduser = $usuario[0]->idusuario;
            $empresa = DB::connection("General")->select("SELECT mc1000.* FROM mc1002 m02 
                                                    INNER JOIN mc1000 on m02.idempresa=mc1000.idempresa 
                                                    WHERE m02.idusuario=$iduser AND mc1000.status=1");
                $datos = array(
                   "empresa" => $empresa,
                );
        }else{
            $datos = $valida;
        }
        return $datos;
    }

    public function serviciosfc(Request $request)
    {
        $valida = $this->usuarioadmin($request->correo, $request->contra);
        if ($valida != "2" and $valida != "3"){
            $servicio = DB::connection("General")->select("SELECT * FROM mc0001");
                $datos = array(
                   "servicio" => $servicio,
                );
        }else{
            $datos = $valida;
        }
        return $datos;
    }

    public function servicioscontratados(Request $request)
    {
        $valida = $this->usuarioadmin($request->correo, $request->contra);
        if ($valida != "2" and $valida != "3"){
            $servicio = DB::connection("General")->select("SELECT * FROM mc0002  WHERE idempresa=$request->idempresa");
                $datos = array(
                   "serviciocon" => $servicio,
                );
        }else{
            $datos = $valida;
        }
        return $datos;
    }

    public function bitacoraservicios(Request $request){
        $valida = $this->usuarioadmin($request->correo, $request->contra);
        if ($valida != "2" and $valida != "3"){
            ConnectDatabase($request->idempresa);
            $bitacora = DB::select("SELECT * FROM mc_bitcontabilidad  
                        WHERE tipodocumento='$request->codigoservicio' AND ejercicio=$request->ejercicio");
                $datos = array(
                   "bitacora" => $bitacora,
                );
        }else{
            $datos = $valida;
        }
        return $datos;
    }

    public function updateBitacora(Request $request)
    {
        $valida = $this->usuarioadmin($request->Correo, $request->Contra);
        $now = date('Y-m-d');
        $datos="false"; 
        if ($valida != "2" and $valida != "3"){
            ConnectDatabaseRFC($request->Rfc);
            $movtos = $request->Documento;
            $num_registros = count($request->Documento);  
            
            $result = DB::select("SELECT id FROM mc_bitcontabilidad WHERE tipodocumento= '$request->Tipodocumento'
                                                AND periodo= $request->Periodo
                                                AND ejercicio=$request->Ejercicio");
            if(!empty($result)){
                DB::table('mc_bitcontabilidad')->where("tipodocumento", $request->Tipodocumento)->
                        where("periodo", $request->Periodo)->where('ejercicio', $request->Ejercicio)->
                        update(['status' => $request->Status, 'idusuarioE' => $request->IdusuarioE,
                             'fechacorte' => $request->Fechacorte,'fechaentregado' => $now]);
                            
                DB::table('mc_detallebitcontabilidad')->where('idbitacora', $result[0]->id)->delete();
                for ($i=0; $i < $num_registros; $i++) {
                    DB::table('mc_detallebitcontabilidad')->insertGetId(['idbitacora' => $result[0]->id,
                         'nombrearchivoE' => $movtos[$i]['NombreE']]);    
                }
                $datos="true"; 
            }
        }else{
            $datos = $valida;
        }
        return $datos;
    }

    public function listaejercicios(Request $request)
    {
        $valida = $this->usuarioadmin($request->correo, $request->contra);
        if ($valida != "2" and $valida != "3"){
            ConnectDatabase($request->idempresa);
            $ejercicio = DB::select("SELECT DISTINCT ejercicio FROM mc_bitcontabilidad");
                $datos = array(
                   "ejercicios" => $ejercicio,
                );
        }else{
            $datos = $valida;
        }
        return $datos;
    }
}
