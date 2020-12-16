<?php

namespace App\Http\Controllers;
use Mail;
use App\Mail\MensajesValidacion;
use App\Mail\MensajesGenerales;
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
            $servicio = DB::connection("General")->select("SELECT * FROM mc0001 WHERE status=1");
                $datos = array(
                   "servicio" => $servicio,
                );
        }else{
            $datos = $valida;
        }
        return $datos;
    }

    public function serviciosfcmodulo(Request $request)
    {
        $valida = $this->usuarioadmin($request->correo, $request->contra);
        if ($valida != "2" and $valida != "3"){
            $servicio = DB::connection("General")->select("SELECT * FROM mc0001 WHERE idfcmodulo=$request->idmodulo AND status=1");
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

    public function servicioscontratadosRFC(Request $request)
    {
        $valida = $this->usuarioadmin($request->correo, $request->contra);
        if ($valida != "2" and $valida != "3"){
            $rfcempresa = $request->Rfc;
            $ejercicio = $request->Ejercicio;
            $empresa = DB::connection("General")->select('select * from mc1000 where rfc = ?', [$rfcempresa]);
            if (!empty($empresa)) {
                $bdd = $empresa[0]->rutaempresa;
                $servicio = DB::select("select s.*,e.rfc,b.periodo,b.ejercicio,b.fecha from " . env('DB_DATABASE_GENERAL') . ".mc0002 s 
                inner join " . env('DB_DATABASE_GENERAL') . ".mc1000 e on s.idempresa=e.idempresa
                inner join $bdd.mc_bitcontabilidad b on s.idservicio=b.idservicio
                where RFC = ? and ejercicio= ?", [$rfcempresa, $ejercicio]);
                    $datos = array(
                    "serviciocon" => $servicio,
                    );
            }else{
                $valida = "2"; 
            }
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
                    $now = $registros[$i]['Fecha'];
                    $periodo= $registros[$i]['Periodo'];
                    $ejercicio = $registros[$i]['Ejercicio']; 
                    $archivo = $registros[$i]['Archivo'];
                    $nomarchi = $registros[$i]['Nombrearchivo'];
                    $url = $registros[$i]['Url'];
                    $idusersub = $usuario[0]->idusuario;
                    
                    $status=  0;
                    $iduserentrega = 0;
                    $servi = DB::connection("General")->select('select id from mc0001 where codigoservicio = ?', [$request->Tipodocumento]);
                    if (!empty($servi)) {
                        $idser = $servi[0]->id;
                    }else{
                       $idser = 0; 
                    }
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
                            'idusuarioG' => $idusersub,'url' => $url, 'idservicio' => $idser ]);
                    }else{
                        DB::table('mc_bitcontabilidad')->where("idsubmenu", $request->Idsubmenu)->
                            where("tipodocumento", $request->Tipodocumento)->
                            where("periodo", $periodo)->where('ejercicio', $ejercicio)->
                            update(['fechamodificacion' => $fechamod, 'fecha' => $now,'url' => $url,'idservicio' => $idser ]);
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
                $idservicio = $request->Idservicio;
                DB::table('mc_bitcontabilidad')->where("tipodocumento", $request->Tipodocumento)->
                        where("periodo", $request->Periodo)->where('ejercicio', $request->Ejercicio)->
                        update(['status' => $request->Status, 'idusuarioE' => $iduserent,'fechacorte' => $fechacor,
                             'fechaentregado' => $now, 'idservicio' => $idservicio]);
                            
                DB::table('mc_bitcontabilidad_det')->where('idbitacora', $result[0]->id)->delete();
                for ($i=0; $i < $num_registros; $i++) {
                    DB::table('mc_bitcontabilidad_det')->insertGetId(['idbitacora' => $result[0]->id,
                         'nombrearchivoE' => $movtos[$i]['NombreE'],'fechacorte' => $movtos[$i]['FechaCorte']]);    
                }
                $datos="true";
                DB::insert('insert into mc_agente_entregas (idusuario, idservicio, tipodocumento, 
                        ejercicio, periodo, fechacorte, status) values (?, ?, ?, ?, ?, ?, ?)', [$iduserent,
                             $idservicio, $request->Tipodocumento, $request->Ejercicio, $request->Periodo,
                              $fechacor, $request->Status]); 
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
        $fechacor = $request->FechaCorte;
        $valida = $this->usuarioadmin($request->Correo, $request->Contra);
        if ($valida != "2" and $valida != "3"){
            ConnectDatabaseRFC($request->Rfc);
            $usuario = $valida['usuario'];
            $iduserent = $usuario[0]->idusuario;
            $idservicio = $request->Idservicio;
            $archivoen = $request->Archivoentrega;
            $result = DB::select("SELECT id FROM mc_bitcontabilidad WHERE tipodocumento= '$request->Tipodocumento'
                                                AND periodo= $request->Periodo
                                                AND ejercicio=$request->Ejercicio");
            if(!empty($result)){
                DB::table('mc_bitcontabilidad')->where("tipodocumento", $request->Tipodocumento)->
                        where("periodo", $request->Periodo)->where('ejercicio', $request->Ejercicio)->
                        update(['status' => $request->Status, 'idusuarioE' => $iduserent,
                             'fechaentregado' => $now, 'fechacorte' => $fechacor, 
                             'idservicio' => $idservicio, 'nombrearchivoE' => $archivoen]);
                $datos="true";
                DB::insert('insert into mc_agente_entregas (idusuario, idservicio, tipodocumento, 
                        ejercicio, periodo, fechacorte, status) values (?, ?, ?, ?, ?, ?, ?)', [$iduserent,
                             $idservicio, $request->Tipodocumento, $request->Ejercicio, $request->Periodo,
                             $now, $request->Status]); 
                if($request->Status == 1){
                    $rfcempresa = $request->Rfc;
                    $empresa = DB::connection("General")->select('select * from mc1000 where rfc = ?', [$rfcempresa]);
                    if (!empty($empresa)) {
                        $bdd = $empresa[0]->rutaempresa;
                        $nomempresa = $empresa[0]->nombreempresa;
                        $resultser = DB::connection("General")->select("SELECT idmodulo,idmenu,idsubmenu,nombreservicio FROM mc0001 WHERE id=$idservicio");
                        if(!empty($resultser)){
                            $link = "http://crm2.dublock.com/#/?ruta=estadosFinancieros&idempresa=35&idmodulo="
                            .$resultser[0]->idmodulo.'&idmenu='.$resultser[0]->idmenu.'&idsubmenu='.$resultser[0]->idsubmenu;
                            $idsub= $resultser[0]->idsubmenu;
                            $salto = chr(13).chr(10);
                            $datosNoti[0]["idusuario"] = $iduserent;
                            $datosNoti[0]["encabezado"] = "Entrega del servicio CRM ".$resultser[0]->nombreservicio;
                            
                            $datosNoti[0]["fecha"] = now();
                            $datosNoti[0]["idmodulo"] = $resultser[0]->idmodulo;
                            $datosNoti[0]["idmenu"] = $resultser[0]->idmenu;
                            $datosNoti[0]["idsubmenu"] = $resultser[0]->idsubmenu;
                            $datosNoti[0]["idregistro"] = 0;

                            $datosNoti[0]["usuarioent"] = "Usuario Entrego: ".$usuario[0]->nombre." ".$usuario[0]->apellidop." ".$usuario[0]->apellidom;
                            $datosNoti[0]["empresa"] = "De la Empresa: ".$nomempresa;
                            $datosNoti[0]["modulo"] = "Modulo: ".GetNomModulo($resultser[0]->idmodulo);
                            $datosNoti[0]["menu"] = "   Menu: ".GetNommenu($resultser[0]->idmenu);
                            $datosNoti[0]["submenu"] = "        SubMenu: ".GetNomSubMenu($resultser[0]->idsubmenu);
                            $datosNoti[0]["mensaje"] = $link;
                            $usuarios = DB::select("select s.notificaciones,u.correo,s.idusuario as id_usuario from  $bdd.mc_usersubmenu s 
                                        inner join " . env('DB_DATABASE_GENERAL') . ".mc1001 u on s.idusuario=u.idusuario
                                        where  s.idsubmenu= ?", [$idsub]);
                            if (!empty($usuarios)) {
                                $datosNoti[0]["usuarios"] = $usuarios;
                                $resp = enviaNotificacionEntre($datosNoti);
                            }
                        }
                    }
                }
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
                                WHERE sucursal= '$request->Sucursal' AND rutaadw='$request->Ruta' AND idadw = $request->idAdw AND sincronizado=1");
                if(!empty($result)){
                    $idsuc = $result[0]->idsucursal;
                    DB::table('mc_catsucursales')->where("idsucursal", $idsuc)->
                                update(['rutaadw' => $request->Ruta, 'idadw' => $request->idAdw]);
                }else{
                    $idsuc = DB::table('mc_catsucursales')->insertGetId(
                        ['sucursal' => $request->Sucursal,'rutaadw' => $request->Ruta,
                        'sincronizado' => 1, 'idadw' => $request->idAdw]);
                }
            }
            $datos = $idsuc;
        }
        return $datos;
    }

    public function addRubros(Request $request)
    {
        
        $valida = $this->usuarioadmin($request->Correo, $request->Contra);
        $datos ="false";
        if ($valida != "2" and $valida != "3"){
            ConnectDatabase($request->Idempresa);
            $movtos = $request->Rubros;
            $num_registros = count($request->Rubros);
            
            for ($i=0; $i < $num_registros; $i++) {
                $tipo = $movtos[$i]['tipo'];
                $idmenu = $movtos[$i]['idmenu'];
                $clave = $movtos[$i]['clave'];
                $nombre =$movtos[$i]['nombre'];
                $result = DB::select("SELECT id FROM mc_rubros WHERE tipo= $tipo
                                AND idmenu=$idmenu AND clave='$clave' AND nombre='$nombre'");
                if(empty($result) and $movtos[$i]['activo'] == 1){
                    DB::table('mc_rubros')->insertGetId(['clave' => $movtos[$i]['clave'],
                    'nombre' => $movtos[$i]['nombre'],'tipo' => $movtos[$i]['tipo'], 
                    'status' => $movtos[$i]['status'], 'idmenu' => $movtos[$i]['idmenu'],
                     'idsubmenu' => $movtos[$i]['idsubmenu'], 'claveplantilla' => $movtos[$i]['plantilla']]);
                }elseif(!empty($result)){
                    DB::table('mc_rubros')->where("id", $result[0]->id)->
                                update(['status' => $movtos[$i]['status'], 'claveplantilla' => $movtos[$i]['plantilla']]);
                }
            }
            $datos ="true"; 
        }

        return $datos;
    }

    public function datosRubros(Request $request)
    {
        $valida = $this->usuarioadmin($request->Correo, $request->Contra);
        $datos ="false";
        if ($valida != "2" and $valida != "3"){
            ConnectDatabase($request->Idempresa);
            $result = DB::select("SELECT * FROM mc_rubros WHERE idmenu=$request->idmenu");
            $datos = array(
                "Rubros" => $result,
             );
        }
        return $datos;
    }

    public function datosSucursal(Request $request)
    {
        $valida = $this->usuarioadmin($request->Correo, $request->Contra);
        $datos ="false";
        if ($valida != "2" and $valida != "3"){
            ConnectDatabase($request->Idempresa);
            $result = DB::select("SELECT * FROM mc_catsucursales WHERE sincronizado=$request->Sincronizado");
            $datos = array(
                "Sucursales" => $result,
             );
        }
        return $datos;
    }

    public function datosRubrosSubMenu(Request $request)
    {
        $valida = $this->usuarioadmin($request->Correo, $request->Contra);
        $datos ="false";
        if ($valida != "2" and $valida != "3"){
            ConnectDatabase($request->Idempresa);
            
            $result = DB::select( "SELECT * FROM mc_rubros WHERE idmenu=$request->idmenu AND idsubmenu=$request->idsubmenu");
            $datos = array(
                "Rubros" => $result,
             );
        }
        return $datos;
    }

    public function documentosdigitales(Request $request)
    {
        $valida = $this->usuarioadmin($request->Correo, $request->Contra);
        $datos ="false";
        if ($valida != "2" and $valida != "3"){
            ConnectDatabase($request->Idempresa);
            
            
            if($request->All == "false"){
                $sucursal = DB::select("SELECT idsucursal FROM mc_catsucursales WHERE sucursal='$request->Sucursal'");

                if(!empty($sucursal)){
                    $idsuc = $sucursal[0]->idsucursal;
                    $result = DB::select( "SELECT m.*,d.* FROM mc_almdigital m INNER JOIN mc_almdigital_det d ON m.id=d.idalmdigital
                                        WHERE YEAR(m.fechadocto)=$request->Ejercicio AND MONTH(m.fechadocto)=$request->Periodo 
                                        AND m.idmodulo='$request->Idmodulo' AND m.idsucursal=$idsuc ORDER BY m.fechadocto DESC");
                    
                    
                }else{
                    return $datos;
                }
            }else{
                $result = DB::select( "SELECT m.*,d.* FROM mc_almdigital m INNER JOIN mc_almdigital_det d ON m.id=d.idalmdigital
                                            ORDER BY m.fechadocto DESC");
            }
            $datos = array(
                "documentos" => $result,
             );
        }
        return $datos;
    }

    public function usuarionube(Request $request)
    {
        // $valida = $this->usuarioadmin($request->Correo, $request->Contra);
        // $datos ="false";
        // if ($valida != "2" and $valida != "3"){
        //     ConnectDatabase($request->Idempresa);
            
            $result = DB::select( "SELECT usuario_storage,password_storage FROM mc1000 WHERE RFC='$request->rfc'");
            $datos = array(
                "usuario" => $result,
             );
        //}
        return $datos;
    }

    public function Plantillas(Request $request)
    {
        $datos="false";
        $valida = $this->usuarioadmin($request->Correo, $request->Contra);
        if ($valida != "2" and $valida != "3"){
            $result = DB::select( "SELECT * FROM mc1011");
            $datos = array(
                "plantillas" => $result,
             );
        }
        return $datos;
    }

    public function traerPlantillas(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] == 0) {
            $permiso = $valida[0]['permiso'];
            if ($permiso < 2) {
                $array["error"] = 4;
            } else {
                $plantillas = DB::connection("General")->select( "select * from mc1011");
                $array["plantillas"] = $plantillas;
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function listaempresas(Request $request)
    {
        $valida = $this->usuarioadmin($request->usuario, $request->pwd);
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
        return json_encode($datos, JSON_UNESCAPED_UNICODE);
        //return $datos;
    }

    public function DatosServicios(Request $request)
    {
        $idservicio = $request->idservicio;
        $array["error"] = "0";
        $servicio = DB::connection("General")->select("SELECT m01.codigoservicio,m01.idsubmenu,
            m03.nombre_carpeta AS carModulos,m04.nombre_carpeta AS carMenu,m05.nombre_carpeta AS carSubMenu
                FROM mc0001 m01 INNER JOIN mc1003 m03 ON m01.idmodulo=m03.idmodulo 
                    INNER JOIN mc1004 m04 ON m01.idmenu=m04.idmenu 
                    INNER JOIN mc1005 m05 ON m01.idsubmenu=m05.idsubmenu WHERE id=$idservicio");
        if (!empty($servicio)){
            $array["datos"] =array(
                "servicio" => $servicio,
             );
        }else{
            $array["error"] = "6";
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

}
