<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class GestionEmpresarialController extends Controller
{
    public function getPryProyectos(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            $proyectos = DB::select('SELECT mc_pry_proyectos.*, mc_pry_agentes.Agente FROM mc_pry_proyectos INNER JOIN mc_pry_agentes ON mc_pry_proyectos.idAgente = mc_pry_agentes.id ORDER BY mc_pry_proyectos.FecUltAccion DESC');
            $array["proyectos"] = $proyectos;
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function guardarPryProyecto(Request $request)
    {
        $idProyecto = $request->idProyecto;
        $Proyecto = $request->Proyecto;
        $Codigo = $request->Codigo;
        $IdClas1 = $request->IdClas1;
        $IdClas2 = $request->IdClas2;
        $IdClas3 = $request->IdClas3;
        $IdClas4 = $request->IdClas4;
        $idAgente = $request->idAgente;
        $Estatus = $request->Estatus;
        $FecIni = $request->FecIni;
        $FecFin = $request->FecFin;
        $Avance = $request->Avance;
        $FecUltAccion = $request->FecUltAccion;
        $accion = $request->accion;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            if($accion === 1) {
                DB::table('mc_pry_proyectos')->insert(['Proyecto' => $Proyecto, 'Codigo' => $Codigo, 'IdClas1' => $IdClas1, 'IdClas2' => $IdClas2, 'IdClas3' => $IdClas3, 'IdClas4' => $IdClas4, 'idAgente' => $idAgente, 'Estatus' => $Estatus, 'FecIni' => $FecIni, 'FecFin' => $FecFin, 'Avance' => $Avance, 'FecUltAccion' => $FecUltAccion]);
            }
            else {
                DB::table('mc_pry_proyectos')->where("id", $idProyecto)->update(['Proyecto' => $Proyecto, 'Codigo' => $Codigo, 'IdClas1' => $IdClas1, 'IdClas2' => $IdClas2, 'IdClas3' => $IdClas3, 'IdClas4' => $IdClas4, 'idAgente' => $idAgente, 'Estatus' => $Estatus, 'FecIni' => $FecIni, 'FecFin' => $FecFin, 'Avance' => $Avance, 'FecUltAccion' => $FecUltAccion]);
            }
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function borrarPryProyecto(Request $request)
    {
        $idProyecto = $request->idProyecto;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            DB::table('mc_pry_proyectos')->where("id", $idProyecto)->delete();
            DB::table('mc_pry_proyactividades')->where("IdProyecto", $idProyecto)->delete();
            DB::table('mc_pry_proyacciones')->where("idproyecto", $idProyecto)->delete();
            DB::table('mc_pry_proycatagentes')->where("IDProyecto", $idProyecto)->delete();
            DB::table('mc_pry_proypersonas')->where("idproyecto", $idProyecto)->delete();
            DB::table('mc_pry_proyplanes')->where("idproyecto", $idProyecto)->delete();
            DB::table('mc_pry_proydocumentos')->where("idproyecto", $idProyecto)->delete(); //aqui falta eliminar los documentos en NextCloud.
            //Definir que pasara despues de eliminar un proyecto con los agentes, personas, actividades y acciones que esten relacionados con el proyecto eliminado.
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function getPryAgentesPersonas(Request $request)
    {
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            $agentesPersonas = DB::select('SELECT * FROM mc_pry_agentes');
            $array["agentesPersonas"] = $agentesPersonas;
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function guardarPryAgentePersona(Request $request)
    {
        $idAgentePersona = $request->idAgentePersona;
        $Agente = $request->Agente;
        $Estatus = $request->Estatus;
        $tipo = $request->tipo;
        $accion = $request->accion;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            if($accion === 1) {
                DB::table('mc_pry_agentes')->insert(['Agente' => $Agente, 'Estatus' => $Estatus, 'tipo' => $tipo]);
            }
            else {
                DB::table('mc_pry_agentes')->where("id", $idAgentePersona)->update(['Agente' => $Agente, 'Estatus' => $Estatus, 'tipo' => $tipo]);
            }
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function cambiarEstatusPryAgentePersona(Request $request)
    {
        $idAgentePersona = $request->idAgentePersona;
        $Estatus = $request->Estatus;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            DB::table('mc_pry_agentes')->where("id", $idAgentePersona)->update(['Estatus' => $Estatus]);
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function borrarPryAgentePersona(Request $request)
    {
        $idAgentePersona = $request->idAgentePersona;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            DB::table('mc_pry_agentes')->where("id", $idAgentePersona)->delete();
            //Definir que pasara despues de eliminar un agente o persona con los proyectos, actividades o acciones en los que este involucrado.
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function getPryProyCatAgentes(Request $request)
    {
        $IDProyecto = $request->IDProyecto;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            $proycatagentes = DB::select('SELECT * FROM mc_pry_proycatagentes WHERE IDProyecto = ?', [$IDProyecto]);
            $array["proycatagentes"] = $proycatagentes;
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function guardarPryProyCatAgentes(Request $request)
    {
        $idProyCatAgente = $request->idProyCatAgente;
        $IDPersona = $request->IDPersona;
        $IDProyecto = $request->IDProyecto;
        $accion = $request->accion;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            if($accion === 1) {
                DB::table('mc_pry_proycatagentes')->insert(['IDPersona' => $IDPersona, 'IDProyecto' => $IDProyecto]);
            }
            else {
                DB::table('mc_pry_proycatagentes')->where("id", $idProyCatAgente)->update(['IDPersona' => $IDPersona, 'IDProyecto' => $IDProyecto]);
            }
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function borrarPryProyCatAgente(Request $request)
    {
        $idProyCatAgente = $request->idProyCatAgente;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            DB::table('mc_pry_proycatagentes')->where("id", $idProyCatAgente)->delete();
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function getPryProyActividadesInfo(Request $request)
    {
        $IdProyecto = $request->IdProyecto;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            $proyectos = DB::select('SELECT mc_pry_proyectos.*, mc_pry_agentes.Agente FROM mc_pry_proyectos INNER JOIN mc_pry_agentes ON mc_pry_proyectos.idAgente = mc_pry_agentes.id WHERE mc_pry_proyectos.id = ? ORDER BY mc_pry_proyectos.FecUltAccion DESC', [$IdProyecto]);
            for($x=0 ; $x<count($proyectos) ; $x++) {
                $proyactividades = DB::select('SELECT * FROM mc_pry_proyactividades WHERE IdProyecto = ?', [$proyectos[$x]->id]);
                $proyectos[$x]->Actividades = $proyactividades;
                $proyectos[$x]->Acciones = DB::select('SELECT mc_pry_proyacciones.*, mc_pry_proyactividades.Actividad FROM mc_pry_proyacciones
                LEFT JOIN mc_pry_proyactividades ON mc_pry_proyacciones.idactividad = mc_pry_proyactividades.id WHERE mc_pry_proyacciones.idproyecto = ? ORDER BY mc_pry_proyacciones.fecha DESC', [$proyectos[$x]->id]);
                $proyectos[$x]->Documentos = DB::select('SELECT * FROM mc_pry_proydocumentos WHERE idproyecto = ?', [$proyectos[$x]->id]);
                $proyectos[$x]->Planes = DB::select('SELECT * FROM mc_pry_proyplanes WHERE idproyecto = ?', [$proyectos[$x]->id]);
                $proyectos[$x]->AgentesPersonas = DB::select('SELECT mc_pry_agentes.* FROM mc_pry_agentes INNER JOIN mc_pry_proyectos
                ON mc_pry_agentes.id = mc_pry_proyectos.idAgente
                WHERE mc_pry_proyectos.id = ?
                UNION 
                SELECT mc_pry_agentes.* FROM mc_pry_agentes 
                INNER JOIN mc_pry_proyactividades ON mc_pry_agentes.id = mc_pry_proyactividades.idAgente 
                WHERE mc_pry_proyactividades.IdProyecto = ?
                UNION 
                SELECT mc_pry_agentes.* FROM mc_pry_agentes 
                INNER JOIN mc_pry_proypersonas ON mc_pry_agentes.id = mc_pry_proypersonas.idpersona 
                WHERE mc_pry_proypersonas.Idproyecto = ?', [$proyectos[$x]->id, $proyectos[$x]->id, $proyectos[$x]->id]);
            }

            $array["actividadesInfo"] = $proyectos;
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function guardarPryProyActividad(Request $request)
    {
        $idProyActividad = $request->idProyActividad;
        $IdProyecto = $request->IdProyecto;
        $Pos = $request->Pos;
        $Nivel = $request->Nivel;
        $Actividad = $request->Actividad;
        $FecIni = $request->FecIni;
        $FecFin = $request->FecFin;
        $idAgente = $request->idAgente;
        $Avance = $request->Avance;
        $Estatus = $request->Estatus;
        $FecUltAccion = $request->FecUltAccion;
        $accion = $request->accion;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            if($accion === 1) {
                DB::table('mc_pry_proyactividades')->insert(['IdProyecto' => $IdProyecto, 'Pos' => $Pos, 'Nivel' => $Nivel, 'Actividad' => $Actividad, 'FecIni' => $FecIni, 'FecFin' => $FecFin, 'idAgente' => $idAgente, 'Avance' => $Avance, 'Estatus' => $Estatus, 'FecUltAccion' => $FecUltAccion]);
            }
            else {
                DB::table('mc_pry_proyactividades')->where("id", $idProyActividad)->update(['IdProyecto' => $IdProyecto, 'Pos' => $Pos, 'Nivel' => $Nivel, 'Actividad' => $Actividad, 'FecIni' => $FecIni, 'FecFin' => $FecFin, 'idAgente' => $idAgente, 'Avance' => $Avance, 'Estatus' => $Estatus, 'FecUltAccion' => $FecUltAccion]);
            }
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function borrarPryProyActividad(Request $request)
    {
        $idProyActividad = $request->idProyActividad;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            DB::table('mc_pry_proyactividades')->where("id", $idProyActividad)->delete();
            DB::table('mc_pry_proyacciones')->where("idactividad", $idProyActividad)->delete();
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function guardarPryProyAccion(Request $request)
    {
        $idProyAccion = $request->idProyAccion;
        $idproyecto = $request->idproyecto;
        $idactividad = $request->idactividad;
        $fecha = $request->fecha;
        $Avance = $request->Avance;
        $estatus = $request->estatus;
        $accion = $request->accion;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            if($accion === 1) {
                DB::table('mc_pry_proyacciones')->insert(['idproyecto' => $idproyecto, 'idactividad' => $idactividad, 'fecha' => $fecha, 'Avance' => $Avance, 'estatus' => $estatus]);
            }
            else {
                DB::table('mc_pry_proyacciones')->where("id", $idProyAccion)->update(['idproyecto' => $idproyecto, 'idactividad' => $idactividad, 'fecha' => $fecha, 'Avance' => $Avance, 'estatus' => $estatus]);
            }
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function borrarPryProyAccion(Request $request)
    {
        $idProyAccion = $request->idProyAccion;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            DB::table('mc_pry_proyacciones')->where("id", $idProyAccion)->delete();
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function guardarPryProyPersonas(Request $request)
    {
        $idproyecto = $request->idproyecto;
        $idactividad = $request->idactividad;
        $idaccion = $request->idaccion;
        $idsAgentespersonas = $request->idsAgentespersonas;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            for($x=0 ; $x<count($idsAgentespersonas) ; $x++) {
                DB::table('mc_pry_proypersonas')->insert(['idproyecto' => $idproyecto, 'idactividad' => $idactividad, 'idaccion' => $idaccion, 'idpersona' => $idsAgentespersonas[$x]]);
            }
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function guardarPryProyDocumento(Request $request)
    {
        $idmenu = $request->idmenu;
        $idmodulo = $request->idmodulo;
        $idUsuario = $request->idUsuario;
        $codigoArchivo = $request->codigoArchivo;
        $idproyecto = $request->idproyecto;
        $idactividad = $request->idactividad;
        $idaccion = $request->idaccion;
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

                    $codigoarchivo = /* $request->rfc . "_" .  */$codigoArchivo . "_" . $idUsuario . "_";

                    $resultado = subirArchivoNextcloud($archivo, $file, $request->rfc, $servidor, $u_storage, $p_storage, $carpetamodulo, $carpetamenu, $carpetasubmenu, $codigoarchivo, $x);
                    if ($resultado["archivo"]["error"] == 0) {
                        $codigodocumento = $codigoarchivo . $x;
                        $type = explode(".", $archivo);
                        $directorio = $request->rfc . '/' . $carpetamodulo . '/' . $carpetamenu . '/' . $carpetasubmenu;
                        $target_path = $directorio . '/' . $codigodocumento . "." . $type[count($type) - 1];
                        $link = GetLinkArchivo($target_path, $servidor, $u_storage, $p_storage);
                        DB::table('mc_pry_proydocumentos')->insert(['idproyecto' => $idproyecto, 'idactividad' => $idactividad, 'idaccion' => $idaccion, 'NombreDocumento' => $codigodocumento, 'ExtencionDocumento' => ".".$type[count($type) - 1], 'rutaDocumento' => $directorio, 'LinkDocumento' => $link]);
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

    function borrarPryProyDocumento(Request $request) {
        $idsPryProyDocumento = $request->idsPryProyDocumento;
        $rutasDocumentos = $request->rutasDocumentos;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];
        if ($valida[0]['error'] === 0) {
            $servidor = getServidorNextcloud();
            $DatosEmpresa = DB::connection("General")->select("SELECT usuario_storage, password_storage FROM mc1000 WHERE RFC = '$request->rfc'");
            $usuariostorage = $DatosEmpresa[0]->usuario_storage;
            $passwordstorage = $DatosEmpresa[0]->password_storage;
            for($x=0 ; $x<count($idsPryProyDocumento) ; $x++) {
                $datosEliminacionDocumentoPublicacion = eliminaArchivoNextcloud($servidor, $usuariostorage, $passwordstorage, $rutasDocumentos[$x]);
                $array["datosEliminacionDocumentoPublicacion"][$x] = $datosEliminacionDocumentoPublicacion;
                DB::table('mc_pry_proydocumentos')->where("id", $idsPryProyDocumento[$x])->delete();
            }
        }
        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }

    public function guardarPryProyPlan(Request $request)
    {
        $idProyPlan = $request->idProyPlan;
        $idproyecto = $request->idproyecto;
        $idactividades = $request->idactividades;
        $fecini = $request->fecini;
        $fecfin = $request->fecfin;
        $idagente = $request->idagente;
        $accion = $request->accion;
        $valida = verificaPermisos($request->usuario, $request->pwd, $request->rfc, $request->idsubmenu);
        $array["error"] = $valida[0]["error"];

        if ($valida[0]['error'] === 0) {
            if($accion === 1) {
                DB::table('mc_pry_proyplanes')->insert(['idproyecto' => $idproyecto, 'idactividades' => $idactividades, 'fecini' => $fecini, 'fecfin' => $fecfin, 'idagente' => $idagente]);
            }
            else {
                DB::table('mc_pry_proyplanes')->where("id", $idProyPlan)->delete();
            }
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
}