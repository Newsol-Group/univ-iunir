<?php

namespace siu\modelo\transacciones;

use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;
use siu\modelo\_comun\abm;
use siu\extension_kernel\formularios\guarani_form_elemento;
use \persona;

class ficha_alumno extends transaccion
{
	protected $operacion = 'matXYZ';
	protected $evento = '2';
	
	const NO_SELECCION = 'null';
	
	//--------------------------------------------------------------------------
	//----	INFO ---------------------------------------------------------------
	//--------------------------------------------------------------------------
	
	function info__comisiones_docente($parametros)
	{
		$datos = catalogo::consultar('docente', 'lista_comisiones_activas', $parametros);
		return $datos;
	}
	
	function info__alum_inscriptos_comision($comision)
	{
		$parametros = array('comision' => $comision);
		return catalogo::consultar('docente', 'alum_inscriptos_comision', $parametros);
	}
	
	
	function get_ids_periodos_lectivos_vigentes()
    {
        $periodos = catalogo::consultar('unidad_academica', 'periodos_lectivos_vigentes_ids');
        return \guarani_arreglos::aplanar_matriz_sin_nulos($periodos,'periodo_lectivo');
    }
	
	function info__mesas_docente($parametros)
	{
		$datos = catalogo::consultar('docente', 'lista_mesas_examen_docente_dentro_o_fuera_calendario', $parametros);
		return $datos;
	}
	
	function info__alum_inscriptos_examen($llamado_mesa)
	{
                $parametros = [
                    'llamado_mesa' => $llamado_mesa,
                    'incluir_otros_alumnos' => null,
                    'evaluacion' => null
                ];
		return catalogo::consultar('docente', 'alum_inscriptos_examen', $parametros);
	}
	
	function info__actas_docente_fc($parametros)
	{
		$datos = catalogo::consultar('docente', 'lista_actas_fc_docente', $parametros);
		return $datos;
	}
	
	
	function info__alum_inscriptos_acta_fc($id_acta)
	{
		$parametros = array('id_acta' => $id_acta);
		return catalogo::consultar('docente', 'alum_inscriptos_acta_fc', $parametros);
	}
	
	function info__datos_cabecera_persona($persona)
	{
		$parametros = array('persona' => $persona);
		return catalogo::consultar('persona', 'datos_persona_header', $parametros);
	}
	
	//--------------------------------------------------------------------------
	//---- Cabeceras -----------------------------------------------------------
	//--------------------------------------------------------------------------
	
	function info__cabecera_comision($comision)
	{
		$parametros = array(
			'id' => $comision,
			'docente' => kernel::persona()->get_id_docente()
		);

		$datos = catalogo::consultar('docente', 'subcomision_cabecera', $parametros);
		if (!empty($datos)) {
			return current($datos);
		}
	}
	
	function info__cabecera_mesa($llamado_mesa)
	{
		$parametros = array('llamado_mesa' => $llamado_mesa);
		$datos = catalogo::consultar('carga_notas_mesa_examen', 'get_datos_encabezado_mesa', $parametros);
		return $datos;
	}
	
	function info__cabecera_acta_fc($id_acta)
	{
		$parametros = array(
			'docente' => kernel::persona()->get_id_docente(), 
			'id_acta' => $id_acta,
			'actividad' => null, 
			'ubicacion' => null,
			'turnos_examen_no_finalizados' => true, // fecha fin de turno de examen >= fecha actual
		);
		$datos = catalogo::consultar('docente', 'lista_actas_fc_docente', $parametros);
		return current($datos);
	}
	
	//--------------------------------------------------------------------------
	//---- Reportes ------------------------------------------------------------
	//--------------------------------------------------------------------------
	
	function info__rep_datos_personales($persona)
	{
		return \toba::consulta_php('co_personas')->get_datos_persona($persona);
	}
	
	function info__rep_datos_personales_propuestas($persona)
	{
		// Lista las propuestas en las que esta el alumno con inscripción aceptada o pendiente.
		$where = " sga_situacion_aspirante.resultado_asp IN ('A','P') AND sga_propuestas_aspira.persona = " . kernel::db()->quote($persona);
		return \toba::consulta_php('co_alumnos')->get_propuestas_aspira($where);
	}
	
	function info__registros_bitacora($persona)
	{
		$persona = kernel::db()->quote($persona);
		$where = "sga_bitacora.persona = $persona";
		$where .= " AND sga_bitacora.visibilidad IN ('".\persona::bitacora_visibilidad_docente."', '".persona::bitacora_visibilidad_todos."')";
		$where .= " AND sga_bitacora.id_seguimiento <> 3 ";  // El docente no ve los seguimientos "Actividades"
		$datos = \toba::consulta_php('co_personas')->get_comentarios_bitacora($where);
		$nuevo = array();
		foreach ($datos as $dato) {
			$id = catalogo::generar_id($dato['bitacora']);
			$dato['fecha'] = convertir_fecha_formato_frontend($dato['fecha']);
			$nuevo[$id] = $dato;
			$nuevo[$id][catalogo::id] = $id;
		}
		return $nuevo;
	}
	
	function info__origen_alumno($persona)
	{
		$parametros = array(
			'persona' => $persona,
			'comisiones_activas' => true, // fecha de inactivación comisión >= fecha actual
			'turnos_examen_no_finalizados' => true, // fecha fin de turno de examen >= fecha actual
		);
		return catalogo::consultar('docente', 'buscar_procedencia_alumno', $parametros);
	}
	
	function info__llamado_mesa_de_acta($id_acta)
	{
		$parametros = array(
			'id_acta' => $id_acta,
		);
		$datos = catalogo::consultar('acta', 'get_datos_acta', $parametros);
		return $datos['llamado_mesa'];
	}
	
	//--------------------------------------------------------------------------
	//---- Sincronización con la BD (bitácora) ---------------------------------
	//--------------------------------------------------------------------------
	
	function evt__guardar_bitacora($datos) 
	{
		if (empty($datos[catalogo::id])) {
			// Alta
			unset($datos[catalogo::id]);
			$datos['usuario_alta'] = kernel::persona()->get_id();
			$datos['docente'] = kernel::persona()->get_id_docente();
			$datos['fecha'] = convertir_fecha_formato_base($datos['fecha']);
			abm::alta('sga_bitacora', $datos);
		} else {
			// Modificación
			$id = $datos[catalogo::id];
			$registros = $this->info__registros_bitacora($datos['persona']);
			$registro = $registros[$id];
			$clave = array('bitacora' => $registro['bitacora']);
			$datos['fecha'] = convertir_fecha_formato_base($datos['fecha']);
			unset($datos[catalogo::id]);
			abm::modificacion('sga_bitacora', $datos, $clave);
		}
	}
	
	function evt__eliminar_bitacora($id_bitacora) 
	{
		$clave = array('bitacora' => $id_bitacora);
		abm::baja('sga_bitacora', $clave);
	}
	
	//--------------------------------------------------------------------------
	//---- Opciones ------------------------------------------------------------
	//--------------------------------------------------------------------------
	
	function armar_combo_opciones($datos, $campo_clave, $campo_valor, $con_no_seleccion=true, $omitir_no_seleccion_opcion_unica=true)
	{
		$combo = array();
		if (!empty($datos)) {
			if (($con_no_seleccion) && ((\guarani_arreglos::count($datos) > 1) || ((\guarani_arreglos::count($datos) <= 1) && !$omitir_no_seleccion_opcion_unica) )) {
				$combo[self::NO_SELECCION] = kernel::traductor()->trans('descr_no_seleccion');
			}
			foreach ($datos as $dato) {
				$combo[$dato[$campo_clave]] = $dato[$campo_valor];
			}
		}
		return $combo;
	}
	
	function opciones__seguimientos()
	{
		$datos = \toba::consulta_php('co_personas')->get_tipos_seguimientos_bitacora();
		$datos = array_filter($datos, function($fila) {
			return ($fila['id_seguimiento'] != persona::bitacora_seguimiento_actividades);
		});
		
		return $this->armar_combo_opciones($datos, 'id_seguimiento', 'nombre');
	}
	
	function opciones__visibilidad()
	{
		$datos = \toba::consulta_php('co_personas')->get_niveles_visibilidad_bitacora_autogestion();
		return $this->armar_combo_opciones($datos, 'valor', 'descr', false);
	}
	
	function opciones__ubicaciones()
	{
		$datos = catalogo::consultar('docente', 'get_lista_ubicaciones');
		return guarani_form_elemento::armar_combo_opciones($datos, 'ubicacion', 'ubicacion_nombre', true, true, 'Todos');
	}
}