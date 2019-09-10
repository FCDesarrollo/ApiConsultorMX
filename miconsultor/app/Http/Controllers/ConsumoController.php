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

            $Pwd = $Password; //password_hash($Password, PASSWORD_BCRYPT); //md5($Password);

            $conexion[0]['idempresa'] = $idempresa[0]->idempresa;

            $idusuario = DB::connection("General")->select("SELECT idusuario, password FROM mc1001 WHERE correo = '$Usuario'");

            if(!empty($idusuario)){                 

                $conexion[0]['idusuario'] = $idusuario[0]->idusuario;

                $ID = $idusuario[0]->idusuario;

                //if(password_verify($request->contra, $hash_BD)) {
                if(password_verify($Pwd, $idusuario[0]->password)) {
                //if($Pwd == $idusuario[0]->password){

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

   

    public function LoteConsumo(Request $request){ 
        
        $RFCEmpresa = $request->rfcempresa;
        $Usuario = $request->usuario;
        $Pwd = $request->pwd;
        $FecI = $request->fechai;
        $FecF = $request->fechaf;
        $TipoDocumento = $request->tipodocto;

        $autenticacion = $this->ValidarConexion($RFCEmpresa, $Usuario, $Pwd, $TipoDocumento, 2, 6, 17);  

        $array["error"] = $autenticacion[0]["error"];
        
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

            $array["documentos"] = $doctos;
            $array["movimientos"] = $movtos;

        }
        
        //echo $array[0]['error'];

        //return json_encode($array);
        return $array;

    }

    function LoteMarcado(Request $request){

        $RFCEmpresa = $request->rfcempresa;
        $Usuario = $request->usuario;
        $Pwd = $request->pwd;        
        $TipoDocumento = $request->tipodocto;
        $registros = $request->registros;

        //$a  = isset($request->conexion) ? $request->conexion : 0;

        $autenticacion = $this->ValidarConexion($RFCEmpresa, $Usuario, $Pwd, $TipoDocumento, 2, 6, 17);  

        $array["error"] = $autenticacion[0]["error"];

        if($autenticacion[0]['error'] == 0){
            
            ConnectDatabase($autenticacion[0]['idempresa']);
            
            for ($i=0; $i < count($registros); $i++) {               
                
                $id = $registros[$i]['iddocto'];
                $idadw = $registros[$i]['iddoctoadw'];                
                
                DB::table('mc_lotesdocto')->where("id", $id)->update(['idadw' => $idadw, 'idsupervisor' => $autenticacion[0]["idusuario"], 'fechaprocesado' => now(), 'estatus' => "1"]);                

            }

        }

        //return json_encode($array, JSON_UNESCAPED_UNICODE);
        return $array;
        
    }    

    public function LoteCargadoExt($RFCEmpresa, $Usuario, $Pwd, $TipoDocto, $movimientos){        
        
        $autenticacion = $this->ValidarConexion($RFCEmpresa, $Usuario, $Pwd, $TipoDocto, 2, 6, 17);
        
        if($autenticacion[0]['error'] == 0){          
            
            $validaciones = $this->Validaciones($movimientos, $TipoDocto, count($movimientos));
            
            if($validaciones[1]['errorJSON'] == 0){

                $catalogos =  $this->VerificarCatalogos($movimientos, $autenticacion[0]['idempresa'], $TipoDocto);

                if($catalogos[1]['errorCATALOGOS'] == 0){

                    ConnectDatabase($autenticacion[0]['idempresa']);   

                    $registros = $this->CargaJSON($movimientos, $TipoDocto, $autenticacion[0]['idempresa'], $autenticacion[0]['idusuario']);
                    
                    $array["error"] = $autenticacion[0]["error"];
                    $array["documentos"] = $registros; //Documentos
                    //$array[2]  = $documentos[1];    //Movimientos

                }else{
                    $array["error"] = 21; // Error en catalogos
                    $array["catalogos"] = $catalogos[0]; //Movimientos con elementos pendientes por cargar
                }

            }else{
                $array["error"] = 11; // Error en el JSON
                $array["validaciones"] = $validaciones[0]; //Movimientos con errores
            }

        }else{
            $array["error"] = $autenticacion[0]["error"];
        }

        return json_encode($array);        

    }  

    function GeneraDocumentos($movtos){

        $num_movtos = count($movtos);

        $k = 0;
        for ($i=0; $i < $num_movtos; $i++) { 
            
            $documentos[$k] = array('fecha' => $movtos[$i]['fecha'], 'codigoconcepto' => $movtos[$i]['codigoconcepto'], 'nombreconcepto' => $movtos[$i]['nombreconcepto'], 'codigocliprov' => $movtos[$i]['codigocliprov'], 'rfc' => $movtos[$i]['rfc'], 'razonsocial' => $movtos[$i]['razonsocial'], 'codigoproducto' => $movtos[$i]['codigoproducto'], 'nombreproducto' => $movtos[$i]['nombreproducto'], 'folio' => $movtos[$i]['folio'], 'serie' => $movtos[$i]['serie'], 'subtotal' => $movtos[$i]['subtotal'], 'descuento' => $movtos[$i]['descuento'], 'iva' => $movtos[$i]['iva'], 'total' => $movtos[$i]['total'], 'almacen' => $movtos[$i]['almacen'], 'cantidad' => $movtos[$i]['cantidad'], 'unidad' => $movtos[$i]['unidad'], 'horometro' => $movtos[$i]['horometro'], 'kilometro' => $movtos[$i]['kilometro'], 'sucursal' => $movtos[$i]['sucursal']);

            $movtos[$i]['marca'] = 1;
            $folio = $movtos[$i]['folio'];
            $fecha = $movtos[$i]['fecha'];
            $sucursal = $movtos[$i]['sucursal'];

            for ($j=0; $j < $num_movtos; $j++) {
                
                if(!isset($movtos[$j]['marca']) && $movtos[$j]['fecha'] == $fecha && $movtos[$j]['folio'] == $folio){                    
                    $documentos[$k]['cantidad'] = $documentos[$k]['cantidad'] + $movtos[$j]['cantidad'];
                    $documentos[$k]['subtotal'] = $documentos[$k]['subtotal'] + $movtos[$j]['subtotal'];
                    $documentos[$k]['descuento'] = $documentos[$k]['descuento'] + $movtos[$j]['descuento'];
                    $documentos[$k]['iva'] = $documentos[$k]['iva'] + $movtos[$j]['iva'];
                    $documentos[$k]['total'] = $documentos[$k]['total'] + $movtos[$j]['total'];
                    $movtos[$j]['marca'] = 1;
                }
            }

            $k = $k + 1;

        }

        return $documentos;
    }    


    public function CargaJSON($movimientos, $tipodocto, $idempresa, $idusuario){
        
        if($tipodocto == 3){
            $documentos = $this->GeneraDocumentos($movimientos);            
        }else{
            $documentos = $movimientos;
        }
        $num_doctos = count($documentos);
        $num_movtos = count($movimientos);
    
        $flag = 0;
        $val4 = "";$val5 = "";$val6 = "";$val7 = "";$val8 = "";$val9 = "";$val10 = "";$val11 = "";$val12 = "";$val13 = "";$val14 = "";
        
        $k = 0;        
        for ($i=0; $i < $num_doctos; $i++) { 

            $fecha = $documentos[$i]["fecha"];
            $codigoconcepto = $documentos[$i]["codigoconcepto"];
            $concepto = $documentos[$i]["nombreconcepto"];
            $rfc = $documentos[$i]["rfc"];
            $razonsocial = $documentos[$i]["razonsocial"];
            $codigoproducto = $documentos[$i]["codigoproducto"]; 
            $producto = $documentos[$i]["nombreproducto"];
            $suc = $documentos[$i]["sucursal"];

            $idlote = $this->RegistrarLote($idempresa, $idusuario, $tipodocto, $suc);

            if($tipodocto == 3){                
                $val4 = $documentos[$i]["folio"];
                $val5 = $documentos[$i]["serie"];
                $val6 = $documentos[$i]["subtotal"];
                $val7 = $documentos[$i]["descuento"];
                $val8 = $documentos[$i]["iva"];
                $val9 = $documentos[$i]["total"];
                $val11 = $documentos[$i]["cantidad"];
                $codigolote = str_replace("-", "", $fecha).$tipodocto.$val4;                           
            }else if($tipodocto == 2){
                $val9 = $documentos[$i]["total"];
                $val10 = $documentos[$i]["almacen"];
                $val11 = $documentos[$i]["cantidad"];                
                $val12 = $documentos[$i]["unidad"];                
                $val13 = $documentos[$i]["horometro"];
                $val14 = $documentos[$i]["kilometro"];
                $codigolote = str_replace("-", "", $fecha).$tipodocto.$val11.$val12;
            }else if($tipodocto == 4 || $tipodocto == 5){
                $val10 = $documentos[$i]["almacen"];
                $val11 = $documentos[$i]["cantidad"];              
                $val12 = $documentos[$i]["unidad"];
                if($tipodocto == 4){
                    $val9 = $documentos[$i]["total"];
                    $codigolote = str_replace("-", "", $fecha).$tipodocto.$val11.$val12.$val9;
                }else{
                    $codigolote = str_replace("-", "", $fecha).$tipodocto.$val11.$val12;
                }                
            }
           
            $documentos[$i]["estatus"] = "";
                
            $lote = DB::select("SELECT * FROM mc_lotesdocto WHERE codigo = '$codigolote'");                
                            
            if(empty($lote)){
                
                DB::table('mc_lotesdocto')->insertGetId(['idlote' => $idlote, 'codigo' => $codigolote, 'sucursal' => $suc, 'concepto' => $codigoconcepto, 'proveedor' => $rfc, 'fecha' => $fecha, 'folio' => $val4, 'serie' => $val5, 'subtotal' => $val6, 'descuento' => $val7, 'iva' => $val8, 'total' => $val9,'campoextra1' => $val11, 'campoextra2' => $val10]);

                $lote = DB::select("SELECT * FROM mc_lotesdocto WHERE codigo = '$codigolote'");

                $documentos[$i]["estatus"] = "Registrado cargado correctamente."; //Nuevo Registro

                $this->RegistrarMovtos($idempresa, $idusuario, $idlote, $lote[0]->id, $tipodocto, $codigolote, $movimientos, $num_movtos);                       
            }else{
                $documentos[$i]["estatus"] = "El registro que intenta registrar ya existe.";
            }

            $this->UpdateLote($idempresa, $tipodocto, $idlote);
        
        }        

        return $documentos;
    }   

    function RegistrarMovtos($idempresa, $idusuario, $idlote, $iddocto, $tipodocto, $codigo, $movtos, $num_movtos){

        ConnectDatabase($idempresa);   
        
        $cont = 0;
        

        for ($i=0; $i < $num_movtos; $i++) {

            $fecha = $movtos[$i]["fecha"];            
            $codigoproducto = $movtos[$i]["codigoproducto"];

            if($tipodocto == 3){
                $folio = $movtos[$i]["folio"];
                $cantidad = ($movtos[$i]["cantidad"] != "" ? $movtos[$i]["cantidad"] : 1);
                $subtotal = floatval($movtos[$i]["subtotal"]);
                $descuento = ($movtos[$i]["descuento"] != "" ? floatval($movtos[$i]["descuento"]): 0);
                $iva = ($movtos[$i]["iva"] != "" ? floatval($movtos[$i]["iva"]) : 0);
                $total = floatval($movtos[$i]["total"]);
                $codigotemp = str_replace("-", "", $fecha).$tipodocto.$folio;    

                if($codigo == $codigotemp){ // tipo 3
                    DB::table('mc_lotesmovtos')->insertGetId(['idlote' => $idlote, 'iddocto' => $iddocto, 'fechamov' => $fecha, 'producto' => $codigoproducto, 'cantidad' => $cantidad, 'subtotal' => $subtotal, 'descuento' => $descuento, 'iva' => $iva, 'total' => $total]);
                }                        
            }elseif($tipodocto == 2){

                $cantidad = ($movtos[$i]["cantidad"] != "" ? $movtos[$i]["cantidad"] : 1);
                $almacen = $movtos[$i]['almacen'];
                $kilometros = ($movtos[$i]['kilometro'] != "" ? $movtos[$i]['kilometro'] : 0);
                $horometros = ($movtos[$i]['horometro'] != "" ? $movtos[$i]['horometro'] : 0);
                $unidad = $movtos[$i]['unidad'];
                $total = floatval($movtos[$i]['total']);
                $codigotemp = str_replace("-", "", $fecha).$tipodocto.$cantidad.$unidad;
                
                if($codigo == $codigotemp){ // tipo 2

                    
                    DB::table('mc_lotesmovtos')->insertGetId(['idlote' => $idlote, 'iddocto' => $iddocto, 'fechamov' => $fecha, 'producto' => $codigoproducto, 'cantidad' => $cantidad, 'almacen' => $almacen, 'kilometros' => $kilometros, 'horometro' => $horometros, 'unidad' => $unidad, 'total' => $total]);
                    

                }                
            }elseif($tipodocto == 4 || $tipodocto == 5){
                $cantidad = ($movtos[$i]["cantidad"] != "" ? $movtos[$i]["cantidad"] : 1);
                $almacen = $movtos[$i]['almacen'];
                $unidad = $movtos[$i]['unidad'];

                if($tipodocto == 4){
                    $precio = $movtos[$i]['total'];
                    $codigotemp = str_replace("-", "", $fecha).$tipodocto.$cantidad.$unidad.$precio;
                    if($codigo == $codigotemp){
                        DB::table('mc_lotesmovtos')->insertGetId(['idlote' => $idlote, 'iddocto' => $iddocto, 'fechamov' => $fecha, 'producto' => $codigoproducto, 'almacen' => $almacen, 'cantidad' => $cantidad, 'unidad' => $unidad, 'total' => $precio]);        
                    }                    
                }else{                    
                    $codigotemp = str_replace("-", "", $fecha).$tipodocto.$cantidad.$unidad; 

                    if($codigo == $codigotemp){ 
                        DB::table('mc_lotesmovtos')->insertGetId(['idlote' => $idlote, 'iddocto' => $iddocto, 'fechamov' => $fecha, 'producto' => $codigoproducto, 'almacen' => $almacen, 'cantidad' => $cantidad, 'unidad' => $unidad]);
                         
                    }                    
                }
                

            }

        }       
        
    }        

    function VerificarCatalogos($movtos, $idempresa, $tipodocto){
        //$datos = $request->array;
        ConnectDatabase($idempresa);

        $count = count($movtos);
        
        $dato[1]['errorCATALOGOS'] = 0;

        $RFCGenerico = "XAXX010101000";


        for($i=0; $i < $count; $i++){
            $codprod = $movtos[$i]['codigoproducto'];
            $codigocliprov = $movtos[$i]['codigocliprov'];
            $rfc = $movtos[$i]['rfc'];
            $codconcepto = $movtos[$i]['codigoconcepto'];
            $razonsocial = $movtos[$i]['razonsocial'];


            $suc = $movtos[$i]['sucursal'];
            //$tipodocto = $movtos[$i]['idconce'];

            $movtos[$i]['productoreg'] = 0;
            $movtos[$i]['clienprovreg'] = 0;
            $movtos[$i]['conceptoreg'] = 0;

            switch ($tipodocto) { //Tipo de Cliente/Proveedor
                case 2: //DIESEL
                case 4: 
                case 5:
                    $tipocli = 2; //Proveedor
                    break;            
                case 3: //REMISION
                    $tipocli = 1; //Cliente
                    break;
            }


            $producto = DB::select("SELECT * FROM mc_catproductos WHERE codigoprod = '$codprod'");
            if(empty($producto)){
                $dato[1]['errorCATALOGOS'] = 1;
                $movtos[$i]['productoreg'] = 1;  
            }else{
                if(is_null($movtos[$i]['nombreproducto'])){
                    $movtos[$i]['nombreproducto'] = $producto[0]->nombreprod;
                }
            }

            if($rfc == $RFCGenerico){                
                $proveedor = DB::select("SELECT * FROM mc_catclienprov WHERE codigoc = '$codigocliprov' And (tipocli = '$tipocli' OR tipocli = 3)");    
            }else{
                $proveedor = DB::select("SELECT * FROM mc_catclienprov WHERE rfc = '$rfc' And (tipocli = '$tipocli' OR tipocli = 3)");    
            }            
            if(empty($proveedor)){
                $dato[1]['errorCATALOGOS'] = 1;
                $movtos[$i]['clienprovreg'] = 1;
            }else{

            }
            
            $concepto = DB::select("SELECT * FROM mc_catconceptos WHERE codigoconcepto = '$codconcepto'");
            if(empty($concepto)){
                $dato[1]['errorCATALOGOS'] = 1;
                $movtos[$i]['conceptoreg'] = 1;
            }else{
                if(is_null($movtos[$i]['nombreconcepto'])){
                    $movtos[$i]['nombreconcepto'] = $concepto[0]->nombreconcepto;
                }
            }
            
            $sucursal = DB::select("SELECT * FROM mc_catsucursales WHERE sucursal = '$suc'");
            if(empty($sucursal)){
                $dato[1]['errorCATALOGOS'] = 1;
                $movtos[$i]['sucursalreg'] = 1;
            }else{

            }    

        }

        $dato[0] = $movtos;

        return $dato;

    }    



    public function UpdateLote($idempresa, $tipodocto, $idlote){     

        ConnectDatabase($idempresa);

        $n = DB::select("SELECT count(id) AS reg FROM mc_lotesdocto WHERE idlote = '$idlote' And error <> 1");
        $totalregistros = DB::select("SELECT count(id) AS totalreg FROM mc_lotesdocto WHERE idlote = '$idlote'");
        
        DB::table('mc_lotes')->where("id", $idlote)->update(['tipo' => $tipodocto, 'totalregistros' => $totalregistros[0]->totalreg, 'totalcargados' => $n[0]->reg]);
        
    }    



    public function RegistrarLote($idempresa, $idusuario, $tipodocto, $sucursal){
              
        ConnectDatabase($idempresa);
        $fechac = date("Ymd"); 
        $codigolote = $fechac.$idusuario.$tipodocto.$sucursal;

        //$idlote = DB::select("SELECT id FROM mc_lotes WHERE tipo = 0 LIMIT 1");
        $idlote = DB::select("SELECT id FROM mc_lotes WHERE codigolote = '$codigolote'");

        if(empty($idlote)){
            $lote = DB::table('mc_lotes')->insertGetId(['fechadecarga' => $fechac, 'codigolote' => $codigolote, 'usuario' => $idusuario, 'tipo' => 0]);
            $idlote = DB::select("SELECT id FROM mc_lotes WHERE codigolote = '$codigolote'");
        }
        return $idlote[0]->id; 
    }


    function CheckValor($variable){
        $flag = true;
        $var = $variable;
        if(is_numeric($var)) {
            $var = floatval($var);
        }
        return $var;
    }

    function Validaciones($movtos, $tipodocto, $num_movtos){
        $val1 = "";$val2 = "";$val3 = "";$val4 = "";$val5 = "";
        $val6 = "";$val7 = "";$val8 = "";$val9 = "";$val10 = "";
        $val11 = "";$val12 = "";$val13 = "";$val14 = "";

        $validaciones[1]['errorJSON'] = 0; //Si permacene en 0, es por que no hay errores

        for ($i=0; $i < $num_movtos; $i++) {             
            $fecha = $movtos[$i]["fecha"];
            $codigoconcepto = $movtos[$i]["codigoconcepto"];
            $rfc = $movtos[$i]["rfc"];
            $razonsocial = $movtos[$i]["razonsocial"];
            $codigoproducto = $movtos[$i]["codigoproducto"]; 
            $suc = $movtos[$i]["sucursal"]; 

            if($tipodocto == 3){                
                $val1 = $this->CheckValor($movtos[$i]["folio"]);
                $val2 = $movtos[$i]["serie"];
                $val3 = $this->CheckValor($movtos[$i]["subtotal"]);
                $val4 = $this->CheckValor($movtos[$i]["descuento"]);
                $val5 = $this->CheckValor($movtos[$i]["iva"]);
                $val6 = $this->CheckValor($movtos[$i]["total"]);
                $val13 = ($movtos[$i]["cantidad"] != "" ? $this->CheckValor($movtos[$i]["cantidad"]) : 1);
            }else if($tipodocto == 2){
                $val7 = $this->CheckValor($movtos[$i]["total"]);
                $val8 = $this->CheckValor($movtos[$i]["almacen"]);
                //$val9 = $this->CheckValor($movtos[$i]["litros"]);   
                $val10 = $movtos[$i]["unidad"];
                $val11 = ($movtos[$i]["horometro"] != "" ? $this->CheckValor($movtos[$i]["horometro"]) : 0);
                $val12 = ($movtos[$i]["kilometro"] != "" ? $this->CheckValor($movtos[$i]["kilometro"]) : 0);
                $val13 = ($movtos[$i]["cantidad"] != "" ? $this->CheckValor($movtos[$i]["cantidad"]) : 1);
            }else if($tipodocto == 4){                
                $val8 = $this->CheckValor($movtos[$i]["almacen"]);
                $val10 = $movtos[$i]["unidad"];
                $val13 = ($movtos[$i]["cantidad"] != "" ? $this->CheckValor($movtos[$i]["cantidad"]) : 1);
                $val14 = $this->CheckValor($movtos[$i]["total"]);
            }else if($tipodocto == 5){
                $val8 = $this->CheckValor($movtos[$i]["almacen"]);
                $val10 = $movtos[$i]["unidad"];
                $val13 = $this->CheckValor($movtos[$i]["cantidad"]);                
            } 



            $valores = explode('-', $fecha);
            if(count($valores) != 3 || checkdate($valores[1], $valores[2], $valores[0]) == false){ 
                $movtos[$i]['e_fecha'] = "Fecha Incorrecta."; 
                $validaciones[1]['errorJSON'] = 1;
            }   
            if($rfc == "" || (strlen($rfc) < 12 || strlen($rfc) > 13)){ 
                $movtos[$i]['e_rfc'] = "RFC vacio o estructura incorrecta.";
                $validaciones[1]['errorJSON'] = 1;
            }
            if($razonsocial == ""){
                $movtos[$i]['e_razonsocial'] = "La razon social no puede estar vacia.";
                $validaciones[1]['errorJSON'] = 1;
            }
            if($codigoproducto == ""){
                $movtos[$i]['e_codigoprod'] = "El codigo del producto no puede estar vacio.";
                $validaciones[1]['errorJSON'] = 1;
            }  
            if($codigoconcepto == ""){ 
                $movtos[$i]['e_codigocon'] = "El codigo del concepto no puede estar vacio.";
                $validaciones[1]['errorJSON'] = 1;
            }  
            if($suc == ""){ 
                //$documentos[0][$i]['sucursal'] = "S/S";
                $movtos[$i]['sucursal'] = "S/S";                
            }

            if($tipodocto == 2){ //CONSUMO DIESEL
                if(!is_numeric($val7)){
                    $movtos[$i]['e_importe'] = "Error en el importe.";
                    $validaciones[1]['errorJSON'] = 1;
                }
                if($val8 == "" || !is_numeric($val8)){
                    $movtos[$i]['e_almacen'] = "Error con el almacen.";
                    $validaciones[1]['errorJSON'] = 1;
                }
                /*if($val9 == "" || !is_numeric($val9)){
                    $movtos[$i]['e_litros'] = "Error en los litros.";
                    $validaciones[1]['errorJSON'] = 1;
                }*/
                if($val10 == ""){
                    $movtos[$i]['e_unidad'] = "Error, unidad vacia.";
                    $validaciones[1]['errorJSON'] = 1;
                }
                if(!is_numeric($val11)){
                    $movtos[$i]['e_kilometro'] = "Error con los kilometros.";
                    $validaciones[1]['errorJSON'] = 1;
                }
                if(!is_numeric($val12)){
                    $movtos[$i]['e_horometro'] = "Error con los horometros.";
                    $validaciones[1]['errorJSON'] = 1;
                }
                if($val13 == "" || !is_numeric($val13)){
                    $movtos[$i]['e_cantidad'] = "Error con la cantidad.";
                    $validaciones[1]['errorJSON'] = 1;
                }                
            }else if($tipodocto == 3){ //REMISIONES                
                if($val1 == "" || !is_numeric($val1)){
                    $movtos[$i]['e_folio'] = "Error con el folio.";
                    $validaciones[1]['errorJSON'] = 1;
                }
                if($val13 == "" || !is_numeric($val13)){
                    $movtos[$i]['e_cantidad'] = "Error con la cantidad.";
                    $validaciones[1]['errorJSON'] = 1;
                }
                if($val3 == "" || !is_numeric($val3)){
                    $movtos[$i]['e_subtotal'] = "Error en el subtotal.";
                    $validaciones[1]['errorJSON'] = 1;
                }
                if(!is_numeric($val4)){
                    $movtos[$i]['e_descuento'] = "Error con el descuento.";
                    $validaciones[1]['errorJSON'] = 1;
                }
                if(!is_numeric($val5)){
                    $movtos[$i]['e_iva'] = "Error con el iva.";
                    $validaciones[1]['errorJSON'] = 1;
                }
                if($val6 == "" || !is_numeric($val6)){
                    $movtos[$i]['e_total'] = "Error con el total.";
                    $validaciones[1]['errorJSON'] = 1;
                }
            }else if($tipodocto == 4 || $tipodocto == 5){ //ENTRADAS Y SALIDAS DE MATERIA PRIMA
                if(!is_numeric($val8)){
                    $movtos[$i]['e_almacen'] = "Error con el almacen.";
                    $validaciones[1]['errorJSON'] = 1;
                }
                if($val10 == ""){
                    $movtos[$i]['e_unidad'] = "Error, unidad vacia.";
                    $validaciones[1]['errorJSON'] = 1;
                }
                if($val13 == "" || !is_numeric($val13)){
                    $movtos[$i]['e_cantidad'] = "Error en la cantidad.";
                    $validaciones[1]['errorJSON'] = 1;
                }
                if($tipodocto == 5){
                    if($val14 == "" || is_numeric($val14)){
                        $movtos[$i]['e_precio'] = "Error con el precio.";
                        $validaciones[1]['errorJSON'] = 1;
                    }
                }                
            }
        } //FIN FOR

        $validaciones[0] = $movtos;

        return $validaciones;
    }    


}
