<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\requerimiento;
use App\requerimiento_bit as Bitacora;
use App\documento as Documento;


class ComprasController extends Controller
{

// VALIDAR CONEXION
    public function ValidarConexion($RFCEmpresa, $Usuario, $Password, $TipoDocumento, $Modulo, $Menu, $SubMenu){
        //Se puede omitir la validacion de tipo de usuario, y la de permisos de modulo, menu y submenu
        //mandando como parametro el valor 0 en cada uno.
        $conexion[0]['error'] = 0;
        $idempresa = DB::connection("General")->select("SELECT idempresa,rutaempresa,usuario_storage,password_storage,RFC FROM mc1000 WHERE RFC = '$RFCEmpresa'");
        if(!empty($idempresa)){
            $Pwd = $Password; //password_hash($Password, PASSWORD_BCRYPT); //md5($Password);
            $conexion[0]['idempresa'] = $idempresa[0]->idempresa;
            $conexion[0]['rfc'] = $idempresa[0]->RFC;
            $conexion[0]['userstorage'] = $idempresa[0]->usuario_storage;
            $conexion[0]['passstorage'] = $idempresa[0]->password_storage;
            $idusuario = DB::connection("General")->select("SELECT idusuario, password FROM mc1001 WHERE correo = '$Usuario'");
            if(!empty($idusuario)){                 
                $conexion[0]['idusuario'] = $idusuario[0]->idusuario;
                $ID = $idusuario[0]->idusuario;
                //if(password_verify($request->contra, $hash_BD)) {
                if(password_verify($Pwd, $idusuario[0]->password)) {
                //if($Pwd == $idusuario[0]->password){
                    if($Modulo != 0 && $Menu != 0 && $SubMenu != 0){
                        ConnectDatabase($idempresa[0]->idempresa);                    
                        $permisos = DB::select("SELECT modulo.tipopermiso AS modulo, menu.tipopermiso AS menu, submenu.tipopermiso AS submenu FROM mc_usermod modulo, mc_usermenu menu, mc_usersubmenu submenu WHERE modulo.idusuario = $ID And menu.idusuario = $ID And submenu.idusuario = $ID And modulo.idmodulo = $Modulo AND menu.idmenu = $Menu AND submenu.idsubmenu = $SubMenu;");

                        if(!empty($permisos)){
                                $conexion[0]['permisomodulo'] = $permisos[0]->modulo;
                                $conexion[0]['permisomenu'] = $permisos[0]->menu;
                                $conexion[0]['permisosubmenu'] = $permisos[0]->submenu;
                                if($TipoDocumento != 0){
                                    $tipodocto = DB::connection("General")->select("SELECT tipo FROM mc1011 WHERE clave = $TipoDocumento");
                                    if(!empty($tipodocto)){                            
                                        $conexion[0]['tipodocumento'] = $tipodocto[0]->tipo;
                                    }else{
                                        $conexion[0]['error'] = 5; //Tipo de documento no valido
                                    }
                                }                    

                            //}else{
                            //    $conexion[0]['error'] = 4; //El Usuario no tiene permisos
                            //}                        

                        }else{
                            $conexion[0]['error'] = 4; //El Usuario no tiene permisos
                        }
                    
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
    





    // GET REQUERIMIENTO NO HISTORIAL
    function getRequerimiento(Request $request){
        $autenticacion = $this->ValidarConexion($request->rfcempresa, $request->usuario, $request->pwd, 0, 4,$request->idmenu,$request->idsubmenu);
        $array["error"] = $autenticacion[0]["error"]; 
        if($autenticacion[0]['error'] == 0){
         // if($autenticacion[0]['permisosubmenu'] == 3){ 
            ConnectDatabase($autenticacion[0]["idempresa"]);
            $idmenu = $request->idmenu;
            $idsubmenu = $request->idsubmenu;
            $iduser = $autenticacion[0]["idusuario"];
            $idconcepto = $request->idconcepto;
            $permisos = DB::select("SELECT * FROM mc_usuarios_concepto WHERE id_usuario= $iduser And id_concepto = $idconcepto");
            // si tengo permisos el id que esta logeado sea igual al id concpeto y de id usuario
            if(!empty($permisos)){

                $req = DB::select("SELECT * FROM mc_requerimientos WHERE id_concepto = $idconcepto");
                
            }else{
                $req = DB::select("SELECT * FROM mc_requerimientos WHERE id_concepto = $idconcepto And id_usuario= $iduser");
            } 
            if (!empty($req)) {
                for ($i=0; $i < count($req); $i++) {
                    // Concepto del Documento
                    $idconcepto = $req[$i]->id_concepto;
                    $concepto = DB::select("SELECT * FROM mc_conceptos WHERE id = $idconcepto");
                    $req[$i]->concepto = $concepto[0]->nombre_concepto;
                    // Estado del documento
                    $idestado = $req[$i]->estado_documento;
                    $estado_documentos = DB::connection("General")->select("SELECT nombre_estado FROM mc1015 WHERE id = $idestado");
                    $req[$i]->estado = $estado_documentos[0]->nombre_estado;
                }
             $array["req"] = $req;
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
// }
}


// TRAE EL HISTORIAL DE REQUERIMIENTOS
    function Bitacora(Request $request){
        $autenticacion = $this->ValidarConexion($request->rfcempresa, $request->usuario, $request->pwd, 0, 4, $request->idmenu,$request->idsubmenu);
        $array["error"] = $autenticacion[0]["error"];
        if($autenticacion[0]['error'] == 0){
            ConnectDatabase($autenticacion[0]["idempresa"]); 
            $idmenu = $request->idmenu;
            $idsubmenu = $request->idsubmenu;
            $idreq = $request->idreq;
            $bit = DB::select("SELECT * FROM mc_requerimientos_bit where id_req = $idreq"); 
            for ($i=0; $i < count($bit); $i++) {
                // Estado de la bitacora
                $idestado = $bit[$i]->status;
                $estado_documentos = DB::connection("General")->select("SELECT nombre_estado FROM mc1015 WHERE id = $idestado");
                $bit[$i]->estado = $estado_documentos[0]->nombre_estado;                
            }
            $array["bit"] = $bit;
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }



// Requerimentos Documentos
    function ArchivosRequerimientos(Request $request){
        $autenticacion = $this->ValidarConexion($request->rfcempresa, $request->usuario, $request->pwd, 0, 4, $request->idmenu,$request->idsubmenu);
        $array["error"] = $autenticacion[0]["error"];

        if ($autenticacion[0]['error'] == 0) {
            ConnectDatabase($autenticacion[0]["idempresa"]);
            $idreq = $request->idreq;
            $documentos = DB::select("SELECT * FROM mc_requerimientos_doc WHERE id_req = $idreq");
            // for ($i=0; $i < count($documentos) ; $i++) { 
            //     $idestado = $documentos[$i]->status;
            //     $estado_documentos = DB::connection("General")->select("SELECT nombre_estado FROM mc1015 WHERE id = $idestado");
            //     $documentos[$i]->estado = $estado_documentos[0]->nombre_estado;
            // }
            $array["documentos"] = $documentos;
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }    

    




    // POST REQUERIMIENTOS
    function addRequerimiento(Request $request){

        $descripcion = $request->descripcion;
        $folio = $request->folio;
        $concepto = $request->concepto;
        $serie = $request->serie;
        $fecha = date("Ymd"); 
        $importe = $request->importe;
        $idempresa = $request->idempresa;
        $rfc = $request->rfc;
        $idsucursal = $request->idsucursal;
        $idusuario = $request->idusuario;
        
        $autenticacion = $this->ValidarConexion($request->rfcempresa, $request->usuario, $request->pwd, 0, 4, $request->idmenu,$request->idsubmenu);
        $array["error"] = $autenticacion[0]["error"];
        
        if($autenticacion[0]['error'] == 0){
            
        $usuarios = DB::connection("General")->select("SELECT idusuario FROM mc1001");

        newConexion($rfc);
        $requerimientos = requerimiento::get();


        // Guardamos un nuevo registro en requerimientos
        $requerimiento = new requerimiento();
        $requerimiento->fecha = $fecha;
        $requerimiento->id_usuario = idusuario;
        $requerimiento->descripcion = $descripcion;
        $requerimiento->importe_estimado = $importe;
        $requerimiento->estado_documento = 1;
        $requerimiento->id_concepto = $concepto;
        $requerimiento->serie = $serie;
        $requerimiento->folio = $folio;
        $requerimiento->id_sucursal = $idsucursal;
        $requerimiento->save(); 

        // guardamos un segundo regristro en bitacora
        $bitacora = new Bitacora();
        $bitacora->id_usuario = $requerimiento->id_usuario;
        $bitacora->id_req = $requerimiento->id_req;
        $bitacora->fecha = $fecha;
        $bitacora->descripcion = $descripcion;
        $bitacora->status = 1;
        $bitacora->save();


        // Subir documento a unidad de storage
        $documento = new Documento();
        $documento->documento = 'document.' . $request->documento->extension(); 
        $documento->id_req = $requerimiento->id_req;
        $documento->tipo_doc = 1;
        $documento->download = '<ESTA PENDIENTE>';
        $documento->save();
    }   
}



    // UPDATE
    public function updateRequermiento(Request $request){
        $autenticacion = $this->ValidarConexion($request->rfcempresa, $request->usuario, $request->pwd, 0, 4, 10,12);
        $array["error"] = $autenticacion[0]["error"];
    }



    
    // NUEVO ESTADO
    public function newState(Request $request){


    }


    // ELIMINAR REQ
    public function eliminarRequerimiento(Request $request){       
        ConnectDatabase($request->idempresa);
        $id = $request->idperfil;
        DB::table('mc_requerimiento')->where("id_req", $id)->update(["status"=>"0"]);
        return response($id, 200);
    }
}