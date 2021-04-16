<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class PublicacionesController extends Controller
{
    function getPublicaciones(Request $request)
    { 
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            $publicaciones = DB::select("SELECT * FROM mc_publicaciones WHERE status = 1 ORDER BY fechaPublicacion DESC");
            for($x=0 ; $x<count($publicaciones) ; $x++) {
                $documentos = DB::select("SELECT * FROM mc_publicaciones_docs WHERE idPublicacion = ? ORDER BY id ASC", [$publicaciones[$x]->id]);
                $publicaciones[$x]->documentos = $documentos;
                $nombreUsuario = DB::connection("General")->select("SELECT CONCAT(nombre, ' ', apellidop, ' ', apellidom) AS nombreUsuario FROM mc1001 WHERE idusuario = ?", [$publicaciones[$x]->idUsuario]);
                $publicaciones[$x]->nombreUsuario = $nombreUsuario[0]->nombreUsuario;
            }
            $array["publicaciones"] = $publicaciones;
        }
                
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function eliminarPublicacion(Request $request)
    {
        $idPublicacion = $request->idPublicacion;
        /* $tipoPublicacion = $request->tipoPublicacion; */
        $fechaEliminacion = $request->fechaEliminacion;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            DB::table('mc_publicaciones')->where("id", $idPublicacion)->update(["fechaEliminado" => $fechaEliminacion, "status" => 0]);
        }
                
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function getCatalogosPublicaciones(Request $request)
    { 
        $idTipoPublicacion = $request->idTipoPublicacion;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            $catalogos = DB::select("SELECT * FROM mc_publicaciones_catalogos WHERE tipo = ?", [$idTipoPublicacion]);
            $array["catalogos"] = $catalogos;
        }
                
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function eliminarCatalogoPublicacion(Request $request)
    {
        $idCatalogo = $request->idCatalogo;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            DB::table('mc_publicaciones_catalogos')->where("id", $idCatalogo)->delete();

            $publicaciones = DB::select("SELECT * FROM mc_publicaciones WHERE tipoCatalogo = ?", [$idCatalogo]);

            if(count($publicaciones) > 0) {
                for($x=0 ; $x<count($publicaciones) ; $x++) {
                    DB::table('mc_publicaciones')->where("id", $publicaciones[$x]->id)->update(['tipoCatalogo' => 1]);
                }
            }
        }
                
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function agregarPublicacion(Request $request)
    { 
        $idmenu = $request->idmenu;
        $idmodulo = $request->idmodulo;
        $titulo = $request->titulo;
        $descripcion = $request->descripcion;
        $tipoPublicacion = $request->tipoPublicacion;
        $tipoCatalogo = $request->tipoCatalogo;
        $idUsuario = $request->idUsuario;
        $fechaPublicacion = $request->fechaPublicacion;
        $codigoArchivo = $request->codigoArchivo;
        $documentos = $request->file();
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            $idPublicacion = DB::table('mc_publicaciones')->insertGetId(['titulo' => $titulo, 'descripcion' => $descripcion, 'tipoPublicacion' => $tipoPublicacion, 'tipoCatalogo' => $tipoCatalogo, 'idUsuario' => $idUsuario, 'fechaPublicacion' => $fechaPublicacion]);

            $validaCarpetas = getExisteCarpeta($idmodulo, $idmenu, $request->idsubmenu);
            $array["error"] = $validaCarpetas[0]["error"];
            if ($validaCarpetas[0]['error'] == 0) {
                $carpetamodulo = $validaCarpetas[0]['carpetamodulo'];
                $carpetamenu = $validaCarpetas[0]['carpetamenu'];
                $carpetasubmenu = $validaCarpetas[0]['carpetasubmenu'];
                $x=0;
                $y=0;
                $servidor = getServidorNextcloud();
                $datosempresa = DB::connection("General")->select("SELECT usuario_storage, password_storage FROM mc1000 WHERE RFC = '$request->rfc'");
                $u_storage = $datosempresa[0]->usuario_storage;
                $p_storage = $datosempresa[0]->password_storage;
                foreach ($documentos as $key => $file) {
                    $archivo = $file->getClientOriginalName();

                    $codigoarchivo = $request->rfc . "_" . $codigoArchivo . "_" . $idUsuario . "_";

                    $resultado = subirArchivoNextcloud($archivo, $file, $request->rfc, $servidor, $u_storage, $p_storage, $carpetamodulo, $carpetamenu, $carpetasubmenu, $codigoarchivo, $x);
                    if ($resultado["archivo"]["error"] == 0) {
                        $codigodocumento = $codigoarchivo . $x;
                        $type = explode(".", $archivo);
                        $directorio = $request->rfc . '/' . $carpetamodulo . '/' . $carpetamenu . '/' . $carpetasubmenu;
                        $target_path = $directorio . '/' . $codigodocumento . "." . $type[count($type) - 1];
                        $link = GetLinkArchivo($target_path, $servidor, $u_storage, $p_storage);
                        /* $array["directorio"][$x] = $directorio;
                        $array["target_path"][$x] = $target_path;
                        $array["link"][$x] = $link; */
                        DB::table('mc_publicaciones_docs')->insert(['idPublicacion' => $idPublicacion, 'nombre' => $codigodocumento . "." . $type[count($type) - 1], 'ruta' => $resultado["archivo"]["directorio"], 'link' => $link]);
                        $array["archivo"][$y] = $archivo;
                        $array["statusDocumentos"][$y] = $link != "" ? 1 : 0;
                        $y++;
                    }
                    $x++;
                }
            }
        }
                
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function agregarDocumentosPublicacion(Request $request) {
        $idPublicacion = $request->idPublicacion;
        $idmenu = $request->idmenu;
        $idmodulo = $request->idmodulo;
        $idUsuario = $request->idUsuario;
        $codigoArchivo = $request->codigoArchivo;
        $documentos = $request->file();
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {

            $validaCarpetas = getExisteCarpeta($idmodulo, $idmenu, $request->idsubmenu);
            $array["error"] = $validaCarpetas[0]["error"];
            if ($validaCarpetas[0]['error'] == 0) {
                $carpetamodulo = $validaCarpetas[0]['carpetamodulo'];
                $carpetamenu = $validaCarpetas[0]['carpetamenu'];
                $carpetasubmenu = $validaCarpetas[0]['carpetasubmenu'];
                $x=0;
                $y=0;
                $servidor = getServidorNextcloud();
                $datosempresa = DB::connection("General")->select("SELECT usuario_storage, password_storage FROM mc1000 WHERE RFC = '$request->rfc'");
                $u_storage = $datosempresa[0]->usuario_storage;
                $p_storage = $datosempresa[0]->password_storage;
                foreach ($documentos as $key => $file) {
                    $archivo = $file->getClientOriginalName();

                    $codigoarchivo = $request->rfc . "_" . $codigoArchivo . "_" . $idUsuario . "_";

                    $resultado = subirArchivoNextcloud($archivo, $file, $request->rfc, $servidor, $u_storage, $p_storage, $carpetamodulo, $carpetamenu, $carpetasubmenu, $codigoarchivo, $x);
                    if ($resultado["archivo"]["error"] == 0) {
                        $codigodocumento = $codigoarchivo . $x;
                        $type = explode(".", $archivo);
                        $directorio = $request->rfc . '/' . $carpetamodulo . '/' . $carpetamenu . '/' . $carpetasubmenu;
                        $target_path = $directorio . '/' . $codigodocumento . "." . $type[count($type) - 1];
                        $link = GetLinkArchivo($target_path, $servidor, $u_storage, $p_storage);
                        DB::table('mc_publicaciones_docs')->insert(['idPublicacion' => $idPublicacion, 'nombre' => $codigodocumento . "." . $type[count($type) - 1], 'ruta' => $resultado["archivo"]["directorio"], 'link' => $link]);
                        $array["archivo"][$y] = $archivo;
                        $array["statusDocumentos"][$y] = $link != "" ? 1 : 0;
                        $y++;
                    }
                    $x++;
                }
            }
        }
                
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function eliminarDocumentoPublicacion(Request $request) {
        $idDocumento = $request->idDocumento;
        $rutaDocumento = $request->rutaDocumento;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            $servidor = getServidorNextcloud();
            $DatosEmpresa = DB::connection("General")->select("SELECT usuario_storage, password_storage FROM mc1000 WHERE RFC = '$request->rfc'");
            $usuariostorage = $DatosEmpresa[0]->usuario_storage;
            $passwordstorage = $DatosEmpresa[0]->password_storage;
            DB::table('mc_publicaciones_docs')->where("id", $idDocumento)->delete();
            $datosEliminacionDocumentoPublicacion = eliminaArchivoNextcloud($servidor, $usuariostorage, $passwordstorage, $rutaDocumento);
            $array["datosEliminacionDocumentoPublicacion"] = $datosEliminacionDocumentoPublicacion;
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function editarPublicacion(Request $request) {
        $idPublicacion = $request->idPublicacion;
        $titulo = $request->titulo;
        $descripcion = $request->descripcion;
        $tipoCatalogo = $request->tipoCatalogo;
        $fechaEditado = $request->fechaEditado;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            DB::table('mc_publicaciones')->where("id", $idPublicacion)->update(['titulo' => $titulo, 'descripcion' => $descripcion , 'tipoCatalogo' => $tipoCatalogo, 'fechaEditado' => $fechaEditado]);
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    function guardarCatalogoPublicacion(Request $request)
    {
        $idCatalogo = $request->idCatalogo;
        $nombre = $request->nombre;
        $tipo = $request->tipo;
        $accion = $request->accion;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            if($accion == 1) {
                DB::table('mc_publicaciones_catalogos')->insert(['nombre' => $nombre, 'tipo' => $tipo]);
            }
            else {
                DB::table('mc_publicaciones_catalogos')->where("id", $idCatalogo)->update(['nombre' => $nombre, 'tipo' => $tipo]);
            }
        }
                
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
}