<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ConsumoController extends Controller
{

    public function ValidarConexion($RFCEmpresa, $Usuario, $Password, $TipoDocumento, $Modulo, $Menu, $SubMenu){
        //Se puede omitir la validacion de tipo de usuario, y la de permisos de modulo, menu y submenu
        //mandando como parametro el valor 0 en cada uno.

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

                    if($Modulo != 0 && $Menu != 0 && $SubMenu != 0){

                        ConnectDatabase($idempresa[0]->idempresa);                    

                        $permisos = DB::select("SELECT modulo.tipopermiso AS modulo, menu.tipopermiso AS menu, submenu.tipopermiso AS submenu FROM mc_usermod modulo, mc_usermenu menu, mc_usersubmenu submenu WHERE modulo.idusuario = $ID And menu.idusuario = $ID And submenu.idusuario = $ID And modulo.idmodulo = $Modulo AND menu.idmenu = $Menu AND submenu.idsubmenu = $SubMenu;");

                        if(!empty($permisos)){
                            //if($permisos[0]->modulo != 0 And $permisos[0]->menu != 0 And $permisos[0]->submenu != 0){

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
                //echo count($registros);
                //echo $registros[$i]['iddocto'];
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
                    $array["movimientos"] = $registros; //Documentos
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
                
            $lote = DB::select("SELECT * FROM mc_lotesdocto WHERE codigo = '$codigolote'");                
                            
            if(empty($lote)){
                
                DB::table('mc_lotesdocto')->insertGetId(['idlote' => $idlote, 'codigo' => $codigolote, 'sucursal' => $suc, 'concepto' => $codigoconcepto, 'proveedor' => $rfc, 'fecha' => $fecha, 'folio' => $val4, 'serie' => $val5, 'subtotal' => $val6, 'descuento' => $val7, 'iva' => $val8, 'total' => $val9,'campoextra1' => $val11, 'campoextra2' => $val10]);

                $lote = DB::select("SELECT * FROM mc_lotesdocto WHERE codigo = '$codigolote'");

                $documentos[$i]["registro"] = 0;
                $documentos[$i]["registro_detalle"] = "Registro cargado correctamente."; //Nuevo Registro

                $this->RegistrarMovtos($idempresa, $idusuario, $idlote, $lote[0]->id, $tipodocto, $codigolote, $movimientos, $num_movtos);                       
            }else{
                $documentos[$i]["registro"] = 1;
                $documentos[$i]["registro_detalle"] = "El registro que intento cargar ya existe.";
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

        //$arreglo['productos'] = "";
        //$arreglo['clientesproveedores'] = "";
        //$arreglo['conceptos'] = "";
        //$arreglo['sucursales'] = "";
        
        $catalogos['productos'][0] = "";
        $catalogos['clientesproveedores'][0] = "";
        $catalogos['conceptos'][0] = "";
        $catalogos['sucursales'][0] = "";

        $p = 0;
        $c = 0;
        $cp = 0;
        $s = 0;

        for($i=0; $i < $count; $i++){
            $codprod = $movtos[$i]['codigoproducto'];
            $codigocliprov = $movtos[$i]['codigocliprov'];
            $rfc = $movtos[$i]['rfc'];
            $codconcepto = $movtos[$i]['codigoconcepto'];
            $razonsocial = $movtos[$i]['razonsocial'];
            $suc = $movtos[$i]['sucursal'];
            //$tipodocto = $movtos[$i]['idconce'];

            

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
                //$movtos[$i]['productoreg'] = 1;     
                if($p == 0){
                    $catalogos['productos'][$p] = $codprod;
                    //$arreglo['productos'][$p] = $codprod;
                    $p = $p + 1;                    
                }else{
                    if(!in_array($codprod, $catalogos['productos'])){
                        $catalogos['productos'][$p] = $codprod;
                        //$arreglo['productos'][$p]['codigoproducto'] = $codprod;
                        $p = $p + 1;                    
                    }                    
                }
                
            }

            if($rfc == $RFCGenerico){                
                $proveedor = DB::select("SELECT * FROM mc_catclienprov WHERE codigoc = '$codigocliprov' And (tipocli = '$tipocli' OR tipocli = 3)");    
                $ClienteProveedor = $codigocliprov;
            }else{
                $proveedor = DB::select("SELECT * FROM mc_catclienprov WHERE rfc = '$rfc' And (tipocli = '$tipocli' OR tipocli = 3)");
                $ClienteProveedor = $rfc;    
            }            
            if(empty($proveedor)){
                $dato[1]['errorCATALOGOS'] = 1;
                //$movtos[$i]['clienprovreg'] = 1;     
                if($cp == 0){
                    $catalogos['clientesproveedores'][$cp] = $ClienteProveedor;
                    //$arreglo['clientesproveedores'][$cp] = $ClienteProveedor;
                    $cp = $cp + 1;
                }else{
                    if(!in_array($ClienteProveedor, $catalogos['clientesproveedores'])){           
                        $catalogos['clientesproveedores'][$cp] = $ClienteProveedor;
                        //$arreglo['clientesproveedores'][$cp]['clienteproveedor'] = $ClienteProveedor;
                        $cp = $cp + 1;
                    }                    
                }

            }
            
            $concepto = DB::select("SELECT * FROM mc_catconceptos WHERE codigoconcepto = '$codconcepto'");
            if(empty($concepto)){
                $dato[1]['errorCATALOGOS'] = 1;
                //$movtos[$i]['conceptoreg'] = 1;    
                if($c == 0){
                    $catalogos['conceptos'][$c] = $codconcepto;
                    //$arreglo['conceptos'][$c]['codigoconcepto'] = $codconcepto;
                    $c = $c + 1;
                }else{
                    if(!in_array($codconcepto, $catalogos['conceptos'])){               
                        $catalogos['conceptos'][$c] = $codconcepto;
                        //$arreglo['conceptos'][$c]['codigoconcepto'] = $codconcepto;
                        $c = $c + 1;
                    }
                }                
            }
            
            $sucursal = DB::select("SELECT * FROM mc_catsucursales WHERE sucursal = '$suc'");
            if(empty($sucursal)){
                $dato[1]['errorCATALOGOS'] = 1;
                //$movtos[$i]['sucursalreg'] = 1;
                if($s == 0){
                    $catalogos['sucursales'][$s] = $suc;
                    //$arreglo['sucursales'][$s]['sucursal'] = $suc;
                    $s = $s + 1;
                }else{
                    if(!in_array($suc, $catalogos['sucursales'])){  
                        $catalogos['sucursales'][$s] = $suc;
                        //$arreglo['sucursales'][$s]['sucursal'] = $suc;
                        $s = $s + 1;
                    }                    
                }

            }  

        }

        $dato[0] = $catalogos;

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
        $movtos2 = "";
        $validaciones[1]['errorJSON'] = 0; //Si permacene en 0, es por que no hay errores
        $n = 0;
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
                $movtos2[$n]['fecha'] = $fecha;
                $movtos2[$n]['fecha_detalle'] = "Fecha Incorrecta.";
                $n = $n + 1;                      
                $validaciones[1]['errorJSON'] = 1;
            }   
            if($rfc == "" || (strlen($rfc) < 12 || strlen($rfc) > 13)){ 
                $movtos[$i]['e_rfc'] = "RFC vacio o estructura incorrecta.";
                $movtos2[$n]['rfc'] = $rfc;
                $movtos2[$n]['rfc_detalle'] = "RFC vacio o estructura incorrecta.";
                $n = $n + 1;                      
                $validaciones[1]['errorJSON'] = 1;
            }
            if($razonsocial == ""){
                $movtos[$i]['e_razonsocial'] = "La razon social no puede estar vacia.";
                $movtos2[$n]['razonsocial'] = $razonsocial;
                $movtos2[$n]['razonsocial_detalle'] = "La razon social no puede estar vacia.";
                $n = $n + 1;                                      
                $validaciones[1]['errorJSON'] = 1;
            }
            if($codigoproducto == ""){
                $movtos[$i]['e_codigoprod'] = "El codigo del producto no puede estar vacio.";
                $movtos2[$n]['codigoproducto'] = $codigoproducto;
                $movtos2[$n]['codigoproducto_detalle'] = "El codigo del producto no puede estar vacio.";
                $n = $n + 1;                                      
                $validaciones[1]['errorJSON'] = 1;
            }  
            if($codigoconcepto == ""){ 
                $movtos[$i]['e_codigocon'] = "El codigo del concepto no puede estar vacio.";
                $movtos2[$n]['codigoconcepto'] = $codigoconcepto;
                $movtos2[$n]['codigoconcepto_detalle'] = "El codigo del concepto no puede estar vacio.";
                $n = $n + 1;                                      
                $validaciones[1]['errorJSON'] = 1;
            }  
            if($suc == ""){ 
                //$documentos[0][$i]['sucursal'] = "S/S";
                $movtos[$i]['sucursal'] = "S/S";                
            }

            if($tipodocto == 2){ //CONSUMO DIESEL
                if(!is_numeric($val7)){
                    $movtos[$i]['e_importe'] = "Error en el importe.";
                    $movtos2[$n]['importe'] = $val7;
                    $movtos2[$n]['importe_detalle'] = "Error en el importe.";
                    $n = $n + 1;                                        
                    $validaciones[1]['errorJSON'] = 1;
                }
                if($val8 == "" || !is_numeric($val8)){
                    $movtos[$i]['e_almacen'] = "Error con el almacen.";
                    $movtos2[$n]['almacen'] = $val8;
                    $movtos2[$n]['almacen_detalle'] = "Error con el almacen, vacio o no es numerico.";
                    $n = $n + 1;                    
                    $validaciones[1]['errorJSON'] = 1;
                }
                /*if($val9 == "" || !is_numeric($val9)){
                    $movtos[$i]['e_litros'] = "Error en los litros.";
                    $validaciones[1]['errorJSON'] = 1;
                }*/
                if($val10 == ""){
                    $movtos[$i]['e_unidad'] = "Error, unidad vacia.";
                    $movtos2[$n]['unidad'] = $val10;
                    $movtos2[$n]['unidad_detalle'] = "Error, unidad vacia.";
                    $n = $n + 1;                                        
                    $validaciones[1]['errorJSON'] = 1;
                }
                if(!is_numeric($val11)){
                    $movtos[$i]['e_kilometro'] = "Error con los kilometros.";
                    $movtos2[$n]['kilometros'] = $val11;
                    $movtos2[$n]['kilometros_detalle'] = "Error con los kilometros.";
                    $n = $n + 1;                    
                    $validaciones[1]['errorJSON'] = 1;
                }
                if(!is_numeric($val12)){
                    $movtos[$i]['e_horometro'] = "Error con los horometros.";
                    $movtos2[$n]['horometro'] = $val12;
                    $movtos2[$n]['horometro_detalle'] = "Error con los horometros.";
                    $n = $n + 1;                    
                    $validaciones[1]['errorJSON'] = 1;
                }
                if($val13 == "" || !is_numeric($val13)){
                    $movtos[$i]['e_cantidad'] = "Error con la cantidad.";
                    $movtos2[$n]['cantidad'] = $val13;
                    $movtos2[$n]['cantidad_detalle'] = "Error con la cantidad, vacio o no es numerico.";
                    $n = $n + 1;
                    $validaciones[1]['errorJSON'] = 1;
                }                
            }else if($tipodocto == 3){ //REMISIONES                
                if($val1 == "" || !is_numeric($val1)){
                    $movtos[$i]['e_folio'] = "Error con el folio.";
                    $movtos2[$n]['folio'] = $val1;
                    $movtos2[$n]['folio_detalle'] = "Error con el folio.";
                    $n = $n + 1;                                        
                    $validaciones[1]['errorJSON'] = 1;
                }
                if($val13 == "" || !is_numeric($val13)){
                    $movtos[$i]['e_cantidad'] = "Error con la cantidad.";
                    $movtos2[$n]['cantidad'] = $val13;
                    $movtos2[$n]['cantidad_detalle'] = "Error con la cantidad, vacio o no es numerico.";
                    $validaciones[1]['errorJSON'] = 1;
                }
                if($val3 == "" || !is_numeric($val3)){
                    $movtos[$i]['e_subtotal'] = "Error en el subtotal.";
                    $movtos2[$n]['subtotal'] = $val3;
                    $movtos2[$n]['subtotal_detalle'] = "Error en el subtotal, vacio o no es numerico.";
                    $n = $n + 1;                                        
                    $validaciones[1]['errorJSON'] = 1;
                }
                if(!is_numeric($val4)){
                    $movtos[$i]['e_descuento'] = "Error con el descuento.";
                    $movtos2[$n]['descuento'] = $val4;
                    $movtos2[$n]['descuento_detalle'] = "Error con el descuento.";
                    $n = $n + 1;                                        
                    $validaciones[1]['errorJSON'] = 1;
                }
                if(!is_numeric($val5)){
                    $movtos[$i]['e_iva'] = "Error con el iva.";
                    $movtos2[$n]['iva'] = $val5;
                    $movtos2[$n]['iva_detalle'] = "Error con el iva.";
                    $n = $n + 1;                                        
                    $validaciones[1]['errorJSON'] = 1;
                }
                if($val6 == "" || !is_numeric($val6)){
                    $movtos[$i]['e_total'] = "Error con el total.";
                    $movtos2[$n]['total'] = $val6;
                    $movtos2[$n]['total_detalle'] = "Error con el total.";
                    $n = $n + 1;                                        
                    $validaciones[1]['errorJSON'] = 1;
                }
            }else if($tipodocto == 4 || $tipodocto == 5){ //ENTRADAS Y SALIDAS DE MATERIA PRIMA
                if(!is_numeric($val8)){
                    $movtos[$i]['e_almacen'] = "Error con el almacen.";
                    $movtos2[$n]['almacen'] = $val8;
                    $movtos2[$n]['almacen_detalle'] = "Error con el almacen.";
                    $n = $n + 1;                                        
                    $validaciones[1]['errorJSON'] = 1;
                }
                if($val10 == ""){
                    $movtos[$i]['e_unidad'] = "Error, unidad vacia.";
                    $movtos2[$n]['unidad'] = $val10;
                    $movtos2[$n]['unidad_detalle'] = "Error, unidad vacia.";
                    $n = $n + 1;                                        
                    $validaciones[1]['errorJSON'] = 1;
                }
                if($val13 == "" || !is_numeric($val13)){
                    $movtos[$i]['e_cantidad'] = "Error en la cantidad.";
                    $movtos2[$n]['cantidad'] = $val13;
                    $movtos2[$n]['cantidad_detalle'] = "Error con la cantidad, vacio o no es numerico.";
                    $n = $n + 1;                                        
                    $validaciones[1]['errorJSON'] = 1;
                }
                if($tipodocto == 5){
                    if($val14 == "" || is_numeric($val14)){
                        $movtos[$i]['e_precio'] = "Error con el precio.";
                        $movtos2[$n]['precio'] = $val14;
                        $movtos2[$n]['precio_detalle'] = "Error con el precio.";
                        $n = $n + 1;                                            
                        $validaciones[1]['errorJSON'] = 1;
                    }
                }                
            }
        } //FIN FOR

        $validaciones[0] = $movtos2;

        return $validaciones;
    }

    function LoteCatalogos(Request $request){
        $RFCEmpresa = $request->rfcempresa;
        $Usuario = $request->usuario;
        $Pwd = $request->pwd;

        $n_catalogos = 0;

        $catalogos = DB::connection("General")->select("SELECT clave FROM mc1012");

        $autenticacion = $this->ValidarConexion($RFCEmpresa, $Usuario, $Pwd, 0, 2, 6, 17);

        $array["error"] = $autenticacion[0]["error"];     


        if($autenticacion[0]['error'] == 0){  

            ConnectDatabase($autenticacion[0]['idempresa']);        
            
            
            for ($cat=0; $cat < count($catalogos); $cat++) { 
                $val = $catalogos[$cat]->clave;                
                if(isset($request->$val)){

                    $datos = $request->$val;

                    //return $datos;
                    //$array[$val] = $datos;
                    //return $datos;
                    //echo count($datos);

                    for ($i=0; $i < count($datos); $i++) { 
                                                

                        if($catalogos[$cat]->clave == "productos"){            
                            $campo1 = strtoupper($datos[$i]['codigoproducto']);
                            $campo2 = strtoupper($datos[$i]['nombreproducto']);                            
                            $ele = DB::select("SELECT * FROM mc_catproductos WHERE codigoprod = '$campo1' OR codigoadw = '$campo1'");
                            if(empty($ele)){
                                DB::table('mc_catproductos')->insertGetId(['codigoprod' => $campo1, 'nombreprod' => $campo2, 'codigoadw' => $campo1, 'nombreadw' => $campo2, 'fechaalta' => now()]);
                                
                                $array[$val][$i]['registrado'] = 0;
                                $datos[$i]['registrado'] = 0;
                            }else{
                                $array[$val][$i]['registrado'] = 1;
                                $datos[$i]['registrado'] = 1;
                            }        

                        }else if($catalogos[$cat]->clave == "conceptos"){
                            $campo1 = strtoupper($datos[$i]['codigoconcepto']);
                            $campo2 = strtoupper($datos[$i]['nombreconcepto']);                            
                            $ele = DB::select("SELECT * FROM mc_catconceptos WHERE codigoconcepto = '$campo1' OR codigoadw = '$campo1'");
                            if(empty($ele)){
                                DB::table('mc_catconceptos')->insertGetId(['codigoconcepto' => $campo1, 'nombreconcepto' => $campo2, 'codigoadw' => $campo1, 'nombreadw' => $campo2]);
                                
                                $array[$val][$i]['registrado'] = 0;
                                $datos[$i]['registrado'] = 0;
                            }else{
                                $datos[$i]['registrado'] = 1;
                                $array[$val][$i]['registrado'] = 1;
                            }

                        }else if($catalogos[$cat]->clave == "clientesproveedores"){   
                            $campo1 = strtoupper($datos[$i]['codigo']);
                            $campo2 = strtoupper($datos[$i]['rfc']);
                            $campo3 = strtoupper($datos[$i]['razonsocial']); //Razon Social
                            $campo4 = $datos[$i]['tipo']; //Tipo 
                            if($campo2 == "XAXX010101000"){                    
                                $codigoclienteproveedor = strtoupper($datos[$i][5]);
                                //$codigoclienteproveedor = strtoupper($campo1);
                                $ele = DB::select("SELECT * FROM mc_catclienprov WHERE codigoc = '$campo1'");
                            }else{
                                $codigoclienteproveedor = ($datos[$i][5] == "" ? $campo1 : strtoupper($datos[$i][5]));
                                $ele = DB::select("SELECT * FROM mc_catclienprov WHERE rfc = '$campo3'");
                            }
                            
                            if(empty($ele)){
                                DB::table('mc_catclienprov')->insertGetId(['codigoc' => $campo1, 'rfc' => $campo2, 'razonsocial' => $campo3, 'tipocli' => $campo4]);
                                
                                $array[$val][$i]['registrado'] = 0;
                                $datos[$i]['registrado'] = 0;
                            }else{
                                if($ele[0]->tipocli == $campo3){                        
                                    $array[$val][$i]['registrado'] = 1;
                                    $datos[$i]['registrado'] = 1;
                                }else{
                                    if($ele[0]->tipocli != 3){                            
                                        if($ele[0]->razonsocial == $campo2 || $ele[0]->codigoc == $codigoclienteproveedor){
                                            DB::table('mc_catclienprov')->where("id", $ele[0]->id)->update(['tipocli' => 3, 'razonsocial' => $campo2]);     
                                            $array[$val][$i]['registrado'] = 0;
                                            $datos[$i]['registrado'] = 0;
                                        }else{
                                            $array[$val][$i]['registrado'] = 1;
                                            $datos[$i]['registrado'] = 1;
                                        }                            
                                    }else{                            
                                        $array[$val][$i]['registrado'] = 1;
                                        $datos[$i]['registrado'] = 1;
                                    }           
                                }                
                            }            
                        }else if($catalogos[$cat]->clave == "sucursales"){
                            $campo1 = $datos[$i]["sucursal"];          
                                                        
                            //$campo1 = $datos[$i][0];          
                            //$datos[$val][$i]['registrado'] = 0;
                            $ele = DB::select("SELECT * FROM mc_catsucursales WHERE sucursal = '$campo1'");
                            if(empty($ele)){
                                DB::table('mc_catsucursales')->insertGetId(['sucursal' => $campo1]);
                                
                                $datos[$i]['registrado'] = 0;
                                //$datos[$i]['registrado'] = 0;
                                //$datos[$val][$i]['registrado'] = 0;
                            }else{
                                $datos[$i]['registrado'] = 1;
                                //$datos[$i]['registrado'] = 1;
                                //$array[$val][$i]['registrado'] = 1;
                                //$datos[$val][$i]['registrado'] = 0;
                            }            
                        }


                    }

                    $array[$val] = $datos; 

                }


            }

        }

        return $array;       

    }        


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////---------------------->ALMACEN DIGITAL<-------------------------///////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function RubrosGen(Request $request){
        //$datos = $request->datos;

        //$rfc = $request->rfcempresa;

        $autenticacion = $this->ValidarConexion($request->rfcempresa, $request->usuario, $request->pwd, 0, Mod_Contabilidad, Menu_AlmacenDigital, SubM_ExpedientesDigitales);

        $array["error"] = $autenticacion[0]["error"];

        if($autenticacion[0]['error'] == 0){  

            ConnectDatabase($autenticacion[0]["idempresa"]);
            $rubros = DB::select("SELECT * FROM mc_rubros");
            
            $array["rubros"] = $rubros;
    

        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function AlmacenConsumo(Request $request){

        $autenticacion = $this->ValidarConexion($request->rfcempresa, $request->usuario, $request->pwd, 0, Mod_Contabilidad, Menu_AlmacenDigital, SubM_ExpedientesDigitales);

        $FecI = $request->fechai;
        $FecF = $request->fechaf;
        $ClaveR = $request->claverubro;
        $array["error"] = $autenticacion[0]["error"];

        if($autenticacion[0]['error'] == 0){  

            ConnectDatabase($autenticacion[0]["idempresa"]);

            $datos = DB::select("SELECT det.id, a.fechadocto, a.idusuario, a.rubro, a.idsucursal, det.codigodocumento, det.documento, det.idagente, det.fechaprocesado, det.estatus FROM mc_almdigital a INNER JOIN mc_almdigital_det det ON a.id = det.idalmdigital WHERE a.fechadocto >= '$FecI' AND a.fechadocto <= '$FecF' AND a.rubro = '$ClaveR';");

            if(!empty($datos)){

                for ($i=0; $i < count($datos); $i++) { 
                 
                    $idusuario = $datos[$i]->idusuario;            

                    $datosuser = DB::connection("General")->select("SELECT nombre FROM mc1001 WHERE idusuario = $idusuario");

                    $datos[$i]->usuario = $datosuser[0]->nombre;

                    $claverubro = $datos[$i]->rubro;

                    $rubro = DB::select("SELECT nombre FROM mc_rubros WHERE clave = '$claverubro'");

                    $datos[$i]->nombrerubro = $rubro[0]->nombre;

                    $idsucursal = $datos[$i]->idsucursal;

                    $suc = DB::select("SELECT sucursal FROM mc_catsucursales WHERE idsucursal = $idsucursal");

                    $datos[$i]->sucursal = $suc[0]->sucursal;

                }

                
            }

            $array["almacen"] = $datos;
    

        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);

    }

    function AlmacenMarcado(Request $request){

        $autenticacion = $this->ValidarConexion($request->rfcempresa, $request->usuario, $request->pwd, 0, Mod_Contabilidad, Menu_AlmacenDigital, SubM_ExpedientesDigitales);

        $array["error"] = $autenticacion[0]["error"];

        if(isset($request->registros)){
            $registros = $request->registros;
        }else{
            //$registros[0]["idalmacen"] = $request->idalmacen;
            $registros[0]["id"] = $request->id;
        }
        
        if($autenticacion[0]['error'] == 0){  

            ConnectDatabase($autenticacion[0]["idempresa"]);

            for ($i=0; $i < count($registros); $i++) {               

                //$idalmacen = $registros[$i]['idalmacen'];
                $idalmacen_det = $registros[$i]['id'];                
                
                $resp = DB::table('mc_almdigital_det')->where("id", $idalmacen_det)->where("estatus", 0)->update(['idagente' => $autenticacion[0]["idusuario"], 'fechaprocesado' => now(), 'estatus' => "1"]);                
                if(!empty($resp)){
                    $registros[$i]["estatus"] = true;                    
                }else{
                    $registros[$i]["estatus"] = false;
                }

            }

            $array["registros"] = $registros;

        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }


    function CatSucursales(Request $request){

        $autenticacion = $this->ValidarConexion($request->rfcempresa, $request->usuario, $request->pwd, 0, Mod_Contabilidad, Menu_AlmacenDigital, SubM_ExpedientesDigitales); 

        $array["error"] = $autenticacion[0]["error"];

        if($autenticacion[0]['error'] == 0){  
            ConnectDatabase($autenticacion[0]["idempresa"]);

            $sucursales = DB::select("SELECT idsucursal, sucursal FROM mc_catsucursales");

            $array["sucursales"] = $sucursales;
        }
     
        return json_encode($array, JSON_UNESCAPED_UNICODE);   
    }

    function DatosAlmacen(Request $request){
        $rfc = $request->rfcempresa;
        $idempresa = DB::connection("General")->select("SELECT idempresa FROM mc1000 WHERE rfc ='$rfc'");        

        if(!empty($idempresa)){
            ConnectDatabase($idempresa[0]->idempresa);
            $reg = DB::select("SELECT * FROM mc_almdigital ORDER BY fechadocto DESC");

            for ($i=0; $i < count($reg); $i++) { 

                $idalm = $reg[$i]->id;
                           
                $procesados = DB::select("SELECT id FROM mc_almdigital_det WHERE idalmdigital = $idalm And estatus = 1");

                $reg[$i]->procesados = count($procesados);

                $idusuario = $reg[$i]->idusuario;            

                $datosuser = DB::connection("General")->select("SELECT nombre FROM mc1001 WHERE idusuario = $idusuario");

                $reg[$i]->usuario = $datosuser[0]->nombre;

                $claverubro = $reg[$i]->rubro;

                $rubro = DB::select("SELECT nombre FROM mc_rubros WHERE clave = '$claverubro'");

                $reg[$i]->rubro = $rubro[0]->nombre;

                $idsucursal = $reg[$i]->idsucursal;

                $suc = DB::select("SELECT sucursal FROM mc_catsucursales WHERE idsucursal = $idsucursal");

                $reg[$i]->sucursal = $suc[0]->sucursal;

            }
        }else{
            $reg = array(
                "datos" => "",
            );        
        }

        return json_encode($reg, JSON_UNESCAPED_UNICODE);
    }

    function ArchivosAlmacen(Request $request){
        $idempresa = $request->idempresa;
        $idalmacen = $request->idalmacen;
        ConnectDatabase($idempresa);
        $archivos = DB::select("SELECT * FROM mc_almdigital_det WHERE idalmdigital = $idalmacen");

        for ($i=0; $i < count($archivos); $i++) { 
            $idagente = ($archivos[$i]->idagente != null ? $archivos[$i]->idagente : 0);
            $datosagente = DB::connection("General")->select("SELECT nombre FROM mc1001 WHERE idusuario = $idagente");
            if(!empty($datosagente)){
                $archivos[$i]->agente = $datosagente[0]->nombre;
            }else{
                $archivos[$i]->agente = "¡No ha sido procesado!";
            }
        }


        return json_encode($archivos, JSON_UNESCAPED_UNICODE);
    }

    function EliminaArchivoAlmacen(Request $request){
        $datos = $request->objeto;

        $idarchivo = $datos["idarchivo"];
        $idalmacen = $datos["idalmacen"];

        $autenticacion = $this->ValidarConexion($datos["rfcempresa"], $datos["usuario"], $datos["pwd"], 0, 2, 5, 16); 

        $array["error"] = $autenticacion[0]["error"];

        if($autenticacion[0]['error'] == 0){  

            ConnectDatabase($autenticacion[0]["idempresa"]);

            $archivo = DB::select("SELECT * FROM mc_almdigital_det WHERE idalmdigital = $idalmacen And id = $idarchivo And estatus != 1");

            if(!empty($archivo)){
                //DB::table('mc_almdigital')->where("id", $idlote)->delete();
                $val = $archivo[0]->documento;
                $documento = explode(".", $val);                
                $codigo = $archivo[0]->codigodocumento;
                $namecloud = $codigo.".".$documento[1];
                DB::table('mc_almdigital_det')->where("idalmdigital", $idalmacen)->where("id", $idarchivo)->delete();

                $totalr = DB::select("SELECT totalregistros FROM mc_almdigital WHERE id = $idalmacen");
                $totalc = DB::select("SELECT count(id) as tc FROM mc_almdigital_det WHERE idalmdigital = $idalmacen");

                if($totalc[0]->tc > 0){
                    $totalregistros = $totalr[0]->totalregistros - 1;
                    DB::table('mc_almdigital')->where("id", $idalmacen)->update(['totalregistros' => $totalregistros, 'totalcargados' => $totalc[0]->tc]);
                    $array["totalregistros"] = $totalregistros;
                    $array["archivo"] = $namecloud;
                }else{
                    DB::table('mc_almdigital')->where("id", $idalmacen)->delete();                    
                    $array["totalregistros"] = 0;
                }
                $array["eliminado"] = 0;
            }else{
                $array["eliminado"] = 1;
            }            
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);


    }

    function DatosStorage(Request $request){
        $rfc = $request->rfcempresa;
        $server = DB::connection("General")->select("SELECT servidor_storage FROM mc0000");
        $storage = DB::connection("General")->select("SELECT usuario_storage, password_storage FROM mc1000 WHERE RFC = '$rfc'");
        $storage[0]->server = $server[0]->servidor_storage;
        return json_encode($storage, JSON_UNESCAPED_UNICODE);
    }



   public function AlmCargaArchivos(Request $request){   

        //$idlote = (new ConsumoController)->RegistrarLote($idempresa, $idusuario, $tipodocto, $suc);
        $datos = $request->datos;
        //$archivos = $request->archivos;        

        $autenticacion = $this->ValidarConexion($datos["rfcempresa"], $datos["usuario"], $datos["pwd"], 0, 2, 5, 16);
        
        $array["error"] = $autenticacion[0]["error"];

        if($autenticacion[0]['error'] == 0){  
            
            $archivos = $datos["archivos"];
            $numarchivos = count($archivos);        
            $rfc = $datos["rfcempresa"];
            $now = date('Y-m-d h:i:s A');
            $idUsuario = $autenticacion[0]["idusuario"]; 
            $Rubro = $datos["rubro"];
            $sucursal = $datos["sucursal"];
            $observaciones = $datos["observaciones"];        
            
            $fechadocto = $datos["fechadocto"];
            $string = explode("-", $fechadocto);
            $codfec = substr($string[0], 2).$string[1];

            $codigogral = date("Ymd").$idUsuario.$Rubro.$sucursal;
            $codarchivo = $datos["rfcempresa"]."_".$codfec."_".$Rubro."_";

            $ArchivosVerificados = $this->VerificaArchivos($autenticacion[0]["idempresa"], $codarchivo, $archivos);

            $contador = 0;           

            $suc = DB::select("SELECT * FROM mc_catsucursales WHERE sucursal = '$sucursal'");
            if(!empty($suc)){

                ConnectDatabase($autenticacion[0]["idempresa"]);
                //ConnectaEmpresaDatabase($empresa);     
                
                //$codigoalm = date("Ymd").$idUsuario.$Rubro.$sucursal;
                $codigoalm = $codfec.$idUsuario.$Rubro.$sucursal;

                $reg = DB::select("SELECT * FROM mc_almdigital WHERE codigoalm = '$codigoalm'");

                if(empty($reg)){
                    $idalm = DB::table('mc_almdigital')->insertGetId(['fechadecarga' => $now, 'fechadocto' => $fechadocto, 'codigoalm' => $codigoalm, 'idusuario' => $idUsuario, 'rubro' => $Rubro, 'idsucursal' => $suc[0]->idsucursal, 'observaciones' => $observaciones]); 

                    while (isset($ArchivosVerificados["archivos"][$contador])) {

                        $nomDoc = $ArchivosVerificados["archivos"][$contador]["archivo"];
                        //$codigodocumento = $datos["rfcempresa"]."_".$codfec."_".$Rubro."_".$consecutivo;
                        $codigodocumento = $ArchivosVerificados["archivos"][$contador]["codigo"];
                        
                        if($ArchivosVerificados["archivos"][$contador]["status"] == 0){
                        
                            DB::table('mc_almdigital_det')->insertGetId(['idalmdigital' => $idalm, 'idsucursal' => $suc[0]->idsucursal, 'documento' => $nomDoc, 'codigodocumento' => $codigodocumento]);
                            
                        }
                        $contador++;            
                    } 
                    DB::table('mc_almdigital')->where("id", $idalm)->update(['totalregistros' => $numarchivos, 'totalcargados' => $contador]);

                }else{
                    $cont = 0;
                    while (isset($ArchivosVerificados["archivos"][$contador])) {
                        $nomDoc = $ArchivosVerificados["archivos"][$contador]["archivo"]; 
                        $codigodocumento = $ArchivosVerificados["archivos"][$contador]["codigo"];
                        if($ArchivosVerificados["archivos"][$contador]["status"] == 0){
                            DB::table('mc_almdigital_det')->insertGetId(['idalmdigital' => $reg[0]->id, 'idsucursal' => $reg[0]->idsucursal, 'documento' => $nomDoc, 'codigodocumento' => $codigodocumento]);
                           $cont = $cont + 1; 
                        }
                        $contador++;
                    } 

                    if($observaciones == ""){
                        $observaciones = $reg[0]->observaciones;
                    }

                    $idalm = $reg[0]->id;
                    $totalcargados = DB::select("SELECT COUNT(id) As tc FROM mc_almdigital_det WHERE idalmdigital = $idalm");
                    $totalregistros = $reg[0]->totalregistros + $cont;
                    DB::table('mc_almdigital')->where("id", $idalm)->update(['totalregistros' => $totalregistros, 'totalcargados' => $totalcargados[0]->tc, 'observaciones' => $observaciones]);
                }
                                                          

                $array["error"] = $autenticacion[0]["error"]; //SIN ERROR
                $array["archivos"] = $ArchivosVerificados["archivos"];
            }else{
                $array["error"] = 21; //ERROR EN LA SUCURSAL, NO REGISTRADA
            }

        }else{
            $array["error"] = $autenticacion[0]["error"]; //ERROR DE AUTENTICACION
        }    

        return json_encode($array, JSON_UNESCAPED_UNICODE);
        
        
    }

    function SaveStorage($filename, $source, $target_path){

    }


    function VerificaArchivos($idempresa, $codigo, $archivos){                   

        ConnectDatabase($idempresa);
        $regexp = "/^[a-zA-Z0-9\-_]*$/";
        $array["error"] = 1;      

        $countreg = DB::select("SELECT COUNT(id) as n FROM mc_almdigital_det");
        if(empty($countreg)){
            $tamNumero = 1;    
            $countreg = 0;
        }else{
            $tamNumero = strlen($countreg[0]->n);    
            $countreg = $countreg[0]->n;
        }         

        for ($i=0; $i < count($archivos); $i++) { 
            $archivo = $archivos[$i];

            $resultado = preg_match($regexp, $archivo);

            $countreg = $countreg + 1;
            if(strlen($countreg) == 1){
                $consecutivo = "000".$countreg;
            }elseif (strlen($countreg) == 2){
                $consecutivo = "00".$countreg;
            }elseif (strlen($countreg) == 3){
                $consecutivo = "0".$countreg;
            }else{
                $consecutivo = $countreg;
            }            

            $codigodocumento = $codigo.$consecutivo;

            if($resultado){
                $array["archivos"][$i]["archivo"] = $archivo;
                $array["archivos"][$i]["codigo"] = $codigodocumento;
                $array["archivos"][$i]["status"] = 2; //Nombre Invalido                
            }else{
                //$ele = DB::select("SELECT * FROM mc_almdigital_det WHERE documento = '$archivo'");
                $ele = DB::select("SELECT * FROM mc_almdigital_det WHERE codigodocumento like '$codigo%' AND documento = '$archivo'");       
                if(empty($ele)){
                    //$archivos[$i]["status"] = 0;
                    $array["error"] = 0;
                    $array["archivos"][$i]["archivo"] = $archivo;
                    $array["archivos"][$i]["codigo"] = $codigodocumento;                    
                    $array["archivos"][$i]["consecutivo"] = $consecutivo;
                    /*$array["archivos"][$i]["filename"] =  $archivos[$i]["nombre"];
                    $array["archivos"][$i]["source"] =  $archivos[$i]["fuente"];
                    $array["archivos"][$i]["target_path"] = $archivos[$i]["ruta"];*/
                    $array["archivos"][$i]["status"] = 0; //Nuevo  
                }else{
                    //$archivos[$i]["status"] = 1;    
                    $array["archivos"][$i]["archivo"] = $archivo;
                    $array["archivos"][$i]["codigo"] = $codigodocumento;
                    $array["archivos"][$i]["status"] = 1; //Duplicado            
                    
                }
                
            }

        }

        return $array;

    }

    function DatosFiltroAvanzado(Request $request){
        $datos = $request->datos;

        $autenticacion = $this->ValidarConexion($datos["rfcempresa"], $datos["usuario"], $datos["pwd"], 0, 0, 0, 0);

        $array["error"] = $autenticacion[0]["error"];

        if($autenticacion[0]['error'] == 0){
             $idempresa = $autenticacion[0]['idempresa'];

             $usuarios = DB::connection("General")->select("SELECT m1.idusuario, m1.nombre FROM mc1001 m1 INNER JOIN mc1002 m2 ON m1.idusuario = m2.idusuario WHERE m2.idempresa = $idempresa");
             if(!empty($usuarios)){
                $array["usuarios"] = $usuarios;
             }else{
                $array["usuarios"] = "";
             }

             ConnectDatabase($autenticacion[0]["idempresa"]);

             $rubros = DB::select("SELECT * FROM mc_rubros");  
             if(!empty($rubros)){
                $array["rubros"] = $rubros;
             }else{
                $array["rubros"] = "";
             }

             $sucursales = DB::select("SELECT * FROM mc_catsucursales");  
             if(!empty($sucursales)){
                $array["sucursales"] = $sucursales;
             }else{
                $array["sucursales"] = "";
             }


        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }    

    function FiltrarDatos(Request $request){
        $datos = $request->datos;

        $autenticacion = $this->ValidarConexion($datos["rfcempresa"], $datos["usuario"], $datos["pwd"], 0, 0, 0, 0);

        $array["error"] = $autenticacion[0]["error"];

        $j = 0;

        if($autenticacion[0]['error'] == 0){
        
            $idempresa = $autenticacion[0]['idempresa'];

            ConnectDatabase($idempresa);

            $fechaini = $datos["fechaini"];
            $fechafin = $datos["fechafin"];
            $iduser = $datos["idusuario"];
            $claverubro = $datos["claverubro"];
            $sucursal = $datos["idsucursal"];
            $orden = $datos["orden"];
            
            $flag = 0;

            if($orden == "DESC"){
                
                $reg = DB::select("SELECT * FROM mc_almdigital WHERE fechadocto >= '$fechaini' And fechadocto <= '$fechafin' ORDER BY fechadocto DESC");
            }else{
                
                $reg = DB::select("SELECT * FROM mc_almdigital WHERE fechadocto >= '$fechaini' And fechadocto <= '$fechafin' ORDER BY fechadocto ASC");
            }


            //$reg = DB::select("SELECT * FROM mc_almdigital WHERE fechadocto >= '2019-10-01' AND fechadocto <= '2019-10-10' AND idsucursal = 97 AND rubro = 'REM1' ORDER BY id DESC");
            if(!empty($reg)){

                for ($i=0; $i < count($reg); $i++) { 
                    

                    $idalm = $reg[$i]->id;
                               
                    $procesados = DB::select("SELECT id FROM mc_almdigital_det WHERE idalmdigital = $idalm And estatus = 1");

                    $reg[$i]->procesados = count($procesados);

                    $idusuario = $reg[$i]->idusuario;            

                    $datosuser = DB::connection("General")->select("SELECT nombre FROM mc1001 WHERE idusuario = $idusuario");

                    $reg[$i]->usuario = $datosuser[0]->nombre;

                    $idsucursal = $reg[$i]->idsucursal;

                    $suc = DB::select("SELECT sucursal FROM mc_catsucursales WHERE idsucursal = $idsucursal");

                    $reg[$i]->sucursal = $suc[0]->sucursal;

                    if($iduser == 0 && $claverubro == 0 && $idsucursal == 0){
                        $array["datos"][$j] = $reg[$i];
                        $j = $j + 1;
                        $flag = 1;
                    }                

                }

                if($flag == 0){  
                    
                    for ($x=0; $x < count($reg); $x++) {                    
                        if($iduser == $reg[$x]->idusuario OR $iduser == 0){
                            if($claverubro == $reg[$x]->rubro OR $claverubro == 0){
                                if($sucursal == $reg[$x]->idsucursal OR $sucursal == 0){
                                    $array["datos"][$j] = $reg[$x];
                                    $j = $j + 1;
                                }
                            }
                        }
                    }
                }
            }else{
                $array["datos"][0] = NULL;
            }

        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);

    }

}
