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

    function registraBitacora(Request $request){
        $rfcempresa = $request->Rfc;
        $registros = $request->Regbitacora;
        $num_registros = count($request->Regbitacora);

        $now = date('Y-m-d');
        $fechamod = date('Y-m-d H:i:s');
        $reg = "false";

        $valida = $this->usuarioadmin($request->Correo, $request->Contra);
        
        if ($valida != "2" and $valida != "3"){
            $usuario = $valida['usuario'];
            $empresa = DB::connection("General")->select("SELECT idempresa FROM mc1000 WHERE rfc='$rfcempresa' AND status=1");
            if (!empty($empresa)){
                $idempresa = $empresa[0]->idempresa;
                ConnectDatabase($idempresa);
                for ($i=0; $i < $num_registros; $i++) {
                    $fechamod = $registros[$i]['Fechamodificacion'];

                    $periodo= $registros[$i]['Periodo'];
                    $ejercicio = $registros[$i]['Ejercicio']; 
                    $archivo = $registros[$i]['Archivo'];
                    $nomarchi = $registros[$i]['Nombrearchivo'];
                    
                    $idusersub = $usuario[0]->idusuario;
                    
                    $status=  $request->Status;
                    $iduserentrega = $request->Idusuarioentrega;
                    $result = DB::select("SELECT id FROM mc_bitcontabilidad WHERE idsubmenu = $request->Idsubmenu
                                                        AND tipodocumento= '$request->Tipodocumento'
                                                        AND periodo= $periodo
                                                        AND ejercicio=$ejercicio");
                    if(empty($result)){
                        $idU = DB::table('mc_bitcontabilidad')->insertGetId(
                            ['idsubmenu' => $request->Idsubmenu,'tipodocumento' => $request->Tipodocumento,
                            'periodo' => $periodo, 'ejercicio' => $ejercicio,
                            'fecha' => $now, 'fechamodificacion' => $fechamod,
                            'archivo' => $archivo, 'nombrearchivoG' => $nomarchi,
                            'status' => $status,'idusuarioE' => $iduserentrega,
                            'idusuarioG' => $idusersub]);
                    }else{
                        DB::table('mc_bitcontabilidad')->where("idsubmenu", $request->Idsubmenu)->
                            where("tipodocumento", $request->Tipodocumento)->
                            where("periodo", $periodo)->where('ejercicio', $ejercicio)->
                            update(['fechamodificacion' => $fechamod]);
                    } 
                }
                $reg = "true";
            }
        } 
        return $reg;
    }

    public function updateBitacora(Request $request)
    {
        $valida = $this->usuarioadmin($request->Correo, $request->Contra);
        $now = $request->Fechaentregado;
        $fechacor = $request->FechaCorte;
        $datos="false"; 
        if ($valida != "2" and $valida != "3"){
            $usuario = $valida['usuario'];
            $iduserent = $usuario[0]->idusuario;
            ConnectDatabaseRFC($request->Rfc);
            $movtos = $request->Documento;
            $num_registros = count($request->Documento);  
            
            $result = DB::select("SELECT id FROM mc_bitcontabilidad WHERE tipodocumento= '$request->Tipodocumento'
                                                AND periodo= $request->Periodo
                                                AND ejercicio=$request->Ejercicio");
            if(!empty($result)){
                DB::table('mc_bitcontabilidad')->where("tipodocumento", $request->Tipodocumento)->
                        where("periodo", $request->Periodo)->where('ejercicio', $request->Ejercicio)->
                        update(['status' => $request->Status, 'idusuarioE' => $iduserent,'fechacorte' => $fechacor,
                             'fechaentregado' => $now]);
                            
                DB::table('mc_bitcontabilidad_det')->where('idbitacora', $result[0]->id)->delete();
                for ($i=0; $i < $num_registros; $i++) {
                    DB::table('mc_bitcontabilidad_det')->insertGetId(['idbitacora' => $result[0]->id,
                         'nombrearchivoE' => $movtos[$i]['NombreE'],'fechacorte' => $movtos[$i]['FechaCorte']]);    
                }
                $datos="true"; 
            }
        }else{
            $datos = $valida;
        }
        return $datos;
    }

    public function MarcaBitacora(Request $request)
    {
        $now = $request->Fechaentregado;
        $datos="false";
        $valida = $this->usuarioadmin($request->Correo, $request->Contra);
        if ($valida != "2" and $valida != "3"){
            ConnectDatabaseRFC($request->Rfc);
            $usuario = $valida['usuario'];
            $iduserent = $usuario[0]->idusuario;
            $result = DB::select("SELECT id FROM mc_bitcontabilidad WHERE tipodocumento= '$request->Tipodocumento'
                                                AND periodo= $request->Periodo
                                                AND ejercicio=$request->Ejercicio");
            if(!empty($result)){
                DB::table('mc_bitcontabilidad')->where("tipodocumento", $request->Tipodocumento)->
                        where("periodo", $request->Periodo)->where('ejercicio', $request->Ejercicio)->
                        update(['status' => $request->Status, 'idusuarioE' => $iduserent,
                             'fechaentregado' => $now]);
                $datos="true"; 
            }
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

    public function listaServicios_bit(Request $request)
    {
        $x = 0;
        $datos ="";
        $valida = $this->usuarioadmin($request->correo, $request->contra);
        if ($valida != "2" and $valida != "3"){
            ConnectDatabase($request->idempresa);
            $servicios = DB::select("SELECT DISTINCT tipodocumento FROM mc_bitcontabilidad WHERE status=1");
            foreach($servicios as $t){
                $serivicio = DB::connection("General")->select("SELECT codigoservicio,nombreservicio FROM mc0001 WHERE codigoservicio='$t->tipodocumento'");
                if (!empty($serivicio)){
                    $archivose[$x] = array("codigoservicio" => $serivicio[0]->codigoservicio,"nombreservicio" => $serivicio[0]->nombreservicio);
                    $x = $x + 1;
                }
            }
            $datos = $archivose;
        }else{
            $datos = $valida;
        }
        return $datos;
    }

    public function listaAgentes_bit(Request $request)
    {
        $x = 0;
        $datos ="";
        $valida = $this->usuarioadmin($request->correo, $request->contra);
        if ($valida != "2" and $valida != "3"){
            ConnectDatabase($request->idempresa);
            $agentes = DB::select("SELECT DISTINCT idusuarioE FROM mc_bitcontabilidad WHERE status=1");
            foreach($agentes as $t){
                $agente = DB::connection("General")->select("SELECT idusuario,nombre,apellidop,apellidom FROM mc1001 WHERE idusuario=$t->idusuarioE");
                if (!empty($agente)){
                    $archivose[$x] = array("idusuario" => $agente[0]->idusuario,"nombre" => 
                                    $agente[0]->nombre, "apellidop" => $agente[0]->apellidop, "apellidom" =>$agente[0]->apellidom);
                    $x = $x + 1;
                    $datos = $archivose;
                }
            }
            
        }else{
            $datos = $valida;
        }
        return $datos;
    }

    public function Existe_bitacora(Request $request)
    {
        $datos ="false";
        $valida = $this->usuarioadmin($request->Correo, $request->Contra);
        if ($valida != "2" and $valida != "3"){
            ConnectDatabaseRFC($request->Rfc);
            $result = DB::select("SELECT id FROM mc_bitcontabilidad WHERE tipodocumento= '$request->Tipodocumento'
                                                AND periodo= $request->Periodo
                                                AND ejercicio=$request->Ejercicio");
            if(!empty($result)){
                $datos ="true";   
            }                                   
        }
        return $datos;
    }

    public function EntregadoDocumento(Request $request)
    {
        $datos ="false";
        $valida = $this->usuarioadmin($request->Correo, $request->Contra);
        if ($valida != "2" and $valida != "3"){
            ConnectDatabaseRFC($request->Rfc);
            $result = DB::select("SELECT id FROM mc_bitcontabilidad WHERE tipodocumento= '$request->Tipodocumento'
                                                AND periodo= $request->Periodo
                                                AND ejercicio=$request->Ejercicio AND status=1");
            if(!empty($result)){
                $datos ="true";   
            }                                   
        }
        return $datos;
    }

    public function addSucursal(Request $request)
    {
        $datos ="false";
        $valida = $this->usuarioadmin($request->Correo, $request->Contra);
        if ($valida != "2" and $valida != "3"){
            ConnectDatabase($request->Idempresa);
            if ($request->Idsucursal != 0){

            }else{
                $result = DB::select("SELECT idsucursal FROM mc_catsucursales 
                                WHERE sucursal= '$request->Sucursal' AND rutaadw='$request->Ruta' AND sincronizado=1");
                if(!empty($result)){
                    $idsuc = $result[0]->idsucursal;
                    DB::table('mc_catsucursales')->where("idsucursal", $idsuc)->
                                update(['rutaadw' => $request->Ruta]);
                }else{
                    $idsuc = DB::table('mc_catsucursales')->insertGetId(
                        ['sucursal' => $request->Sucursal,'rutaadw' => $request->Ruta,
                        'sincronizado' => 1]);
                }
            }
            $datos = $idsuc;
        }
        return $datos;
    }

    public function addRubros(Request $request)
    {
        
        $valida = $this->usuarioadmin($request->Correo, $request->Contra);
        $datos = $valida;
        if ($valida != "2" and $valida != "3"){
            ConnectDatabase($request->Idempresa);
            $movtos = $request->Rubros;
            $num_registros = count($request->Rubros);
            
            for ($i=0; $i < $num_registros; $i++) {
                $result = DB::select("SELECT id FROM mc_rubros WHERE tipo= $movtos[$i]['tipo'] 
                                AND idmenu=$movtos[$i]['idmenu'] AND clave='$movtos[$i]['clave']' AND nombre='$movtos[$i]['nombre']'");
                if(empty($result) and $movtos[$i]['activo'] == 1){
                    DB::table('mc_rubros')->insertGetId(['clave' => $movtos[$i]['clave'],
                    'nombre' => $movtos[$i]['nombre'],'tipo' => $movtos[$i]['tipo'], 
                    'status' => $movtos[$i]['status'], 'idmenu' => $movtos[$i]['idmenu'], 'idsubmenu' => $movtos[$i]['idsubmenu']]);
                }else{
                    DB::table('mc_rubros')->where("id", $result[0]->id)->
                                update(['status' => $movtos[$i]['status']]);
                }
            }
            $datos ="true"; 
        }

        return $datos;
    }

}
