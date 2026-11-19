<?php

namespace siu\modelo\transacciones;

use \SIU\Chulupi\kernel;
use \siu\modelo\datos\catalogo;
use \siu\errores\error_guarani;
use siu\modelo\entidades\rs_arreglo;


class carga_evaluaciones_parciales extends transaccion
{
	protected $operacion = 'cur00021';
	protected $evento = '14';
	protected $opciones_enviadas;

	//--------------------------------------------------------------------------
	//---- LISTA EVALUACIONES
	//--------------------------------------------------------------------------

	function info__lista_comisiones()
	{
		$parametros = array(
			'anio_academico' => null,
			'docente' => kernel::persona()->get_id_docente(),
			'periodo_lectivo' => null,
			'actividad' => null,
			'ubicacion' => null,
		);
		return catalogo::consultar('docente', 'lista_comisiones', $parametros);
	}

	/**
	 * Uso: generar el parametro comisión de la operación ver_comision
	 */
	function info__comision($seleccion)
	{
		$parametros = array('comision' => $seleccion['comision'], 'subcomision' => $seleccion['subcomision']);
		$datos = catalogo::consultar('carga_evaluaciones_parciales', 'comision_cabecera', $parametros);
		$rs = new rs_arreglo($datos);
		$rs->set_columna_id('comision');
		$rs->agregar_entidad('comision');
		return $rs;
	}

	function info__lista_evaluaciones($seleccion)
	{
		// Observación: no recupera sólo las evaluaciones, si es vacío obtiene la comisión de todas maneras
		$parametros = array(
			'docente' => kernel::persona()->get_id_docente(),
			'comision' => $seleccion['comision'],
			'subcomision' => $seleccion['subcomision'],
		);
		$datos = catalogo::consultar('carga_evaluaciones_parciales', 'listado_evaluaciones_parciales', $parametros);
		$rs = new rs_arreglo($datos);
		$rs->set_columna_id('id_fila');
		$rs->agregar_entidad('comision');
		return $rs;
	}

	function info__arbol_evaluaciones($seleccion = null)
	{
		$comision = (isset($seleccion)) ? $seleccion['comision'] : null;
		$parametros = array(
			'docente' => kernel::persona()->get_id_docente(),
			'comision' => $comision,
			'subcomision' => $seleccion['subcomision']
		);
		$datos = catalogo::consultar('carga_evaluaciones_parciales', 'listado_evaluaciones_parciales', $parametros);
		$rs = new rs_arreglo($datos);
		$rs->agregar_entidad('comision');
		$rs->set_columna_id('id_fila'); //comision + evaluacion
		return $rs;
	}

	function info__evaluaciones_relacionadas($entidad, $evaluacion = null)
	{
		$where = "	AND sga_evaluaciones_tipos.esencial = '" . \comunes::si . "'
					AND sga_evaluaciones.evaluacion NOT IN
					(	SELECT 	sga_evaluaciones_relacion.evaluacion_hijo AS evaluacion
						FROM 	sga_evaluaciones_relacion 
					)
				";
		return \toba::consulta_php('co_evaluaciones')->get_evaluaciones_relacionadas($entidad, $evaluacion, $where);
	}
	
	function info__evaluaciones_relacionadas_para_checklist($evaluacion)
	{
		$relacionadas = array();
		$eval_relacionadas = catalogo::consultar('carga_evaluaciones_parciales', 'lista_relacionadas', array('evaluacion' => $evaluacion));
		foreach ($eval_relacionadas as $relacionada) {
			$relacionadas[] = $relacionada['evaluacion_padre'];
		}
		return $relacionadas;
	}

	//--------------------------------------------------------------------------
	//---- ABM EVALUACION
	//--------------------------------------------------------------------------	

	function info__lista_escala_notas($parametros)
	{
		return catalogo::consultar('carga_evaluaciones_parciales', 'listado_escala_notas', $parametros);
	}

	function info__tipo_evaluacion()
	{
		return catalogo::consultar('carga_evaluaciones_parciales', 'listado_tipo_evaluacion', null);
	}

	function alta_evaluacion_parcial($seleccion, $datos)
	{
		$datos = $this->validar_evaluacion($datos, $seleccion);
		try {
			kernel::db()->abrir_transaccion();
			$resultado = catalogo::consultar('carga_evaluaciones_parciales', 'alta_evaluacion_parcial', $datos);
			if ($resultado[0] != 1) {
				throw new error_guarani(kernel::traductor()->trans($resultado[1]));
			}
			$this->invalidar_listado_evaluaciones($seleccion);
			kernel::db()->cerrar_transaccion();
			return kernel::traductor()->trans($resultado[1]);
		} catch (error_guarani $e) {
			kernel::db()->abortar_transaccion();
			kernel::log()->add_error($e);
			throw $e;
		}
	}

	protected function validar_evaluacion($datos, $seleccion)
	{
		$datos['comision'] = $seleccion['comision'];
		$datos['nombre'] = substr($datos['nombre'], 0, 100);
		$datos['descripcion'] = substr($datos['descripcion'], 0, 255);
		$datos['eval_relacionadas'] = !empty($datos['eval_relacionadas']) ? array_values($datos['eval_relacionadas']) : NULL;
		$datos['hora_fin'] = $datos['hora_fin'] ? : NULL;

		$where = "AND sga_evaluaciones.nombre = " . kernel::db()->quote($datos['nombre']);
		$eval_comision = \toba::consulta_php('co_evaluaciones')->get_evaluaciones_relacionadas($seleccion['entidad'], $seleccion['evaluacion'], $where);
		if (!empty($eval_comision)) {
			$mensaje = kernel::traductor()->trans('eval_comision.error_nombre_repetido');
			throw new error_guarani($mensaje);
		}
		
		return $datos;
	}

	function modificar_evaluacion_parcial($seleccion, $datos)
	{
		$datos = $this->validar_evaluacion($datos, $seleccion);
		try {
			kernel::db()->abrir_transaccion();
			$instancias = $datos['instancias'];
			// Control de instancias
			$flag_instancias = false;
			$eval_instancias = $this->info__evaluacion_instancias($seleccion);
			if (\guarani_arreglos::count($instancias) != \guarani_arreglos::count($eval_instancias)) {
				$flag_instancias = true;
			} else {
				foreach ($instancias as $instancia) {
					//todo: ver que quiere hacer aca!
					if (!in_array($instancia, $eval_instancias)) {
						$flag_instancias = true;
						break;
					}
				}
			}
			// Control evaluaciones relacionadas
			$flag_relacionadas = false;
			$eval_relacionadas = $this->info__evaluaciones_relacionadas($seleccion['entidad'], $seleccion['evaluacion']);
			$relacionadas = $datos['eval_relacionadas'];
			if (\guarani_arreglos::count($relacionadas) != \guarani_arreglos::count($eval_relacionadas)) {
				$flag_relacionadas = true;
			} else {
				foreach ($relacionadas as $relacion) {
					if (!in_array($relacion, $eval_relacionadas)) {
						$flag_relacionadas = true;
						break;
					}
				}
			}
			$datos['evaluacion'] = $seleccion['evaluacion'];
			$datos['flag_instancias'] = $flag_instancias ? 1 : 0;
			$datos['flag_relacionadas'] = $flag_relacionadas ? 1 : 0;
			$resultado = catalogo::consultar('carga_evaluaciones_parciales', 'modificar_evaluacion_parcial', $datos);
			if ($resultado[0] != 1) {
				throw new error_guarani(kernel::traductor()->trans($resultado[1]));
			}
			$this->invalidar_listado_evaluaciones($seleccion);
			$this->invalidar_evaluacion_cabecera($seleccion);
			kernel::db()->cerrar_transaccion();
			return kernel::traductor()->trans($resultado[1]);
		} catch (error_guarani $e) {
			kernel::db()->abortar_transaccion();
			kernel::log()->add_error($e);
			throw $e;
		}
	}

	function baja_evaluacion_parcial($seleccion)
	{
		//@todo: Control derechos!
		try {
			kernel::db()->abrir_transaccion();
			$parametros = array('evaluacion' => $seleccion['evaluacion']);
			$resultado = catalogo::consultar('carga_evaluaciones_parciales', 'baja_evaluacion_parcial', $parametros);
			if ($resultado['resultado'] != 1) {
				throw new error_guarani(kernel::traductor()->trans($resultado['mensaje_indice']));
			}
			$this->invalidar_listado_evaluaciones($seleccion);
			kernel::db()->cerrar_transaccion();
			return kernel::traductor()->trans($resultado['mensaje_indice']);
		} catch (error_guarani $e) {
			kernel::db()->abortar_transaccion();
			kernel::log()->add_error($e);
			throw $e;
		}
	}

	function invalidar_listado_evaluaciones($seleccion)
	{
		$parametros = array(
			'docente' => kernel::persona()->get_id_docente(),
			'comision' => $seleccion['comision'],
			'subcomision' => $seleccion['subcomision']
		);
		catalogo::limpiar_cache('carga_evaluaciones_parciales', 'listado_evaluaciones_parciales', $parametros);
	}

	function invalidar_evaluacion_cabecera($seleccion)
	{
		catalogo::limpiar_cache('carga_evaluaciones_parciales', 'evaluacion_cabecera', array('evaluacion' => $seleccion['evaluacion']));
	}

	//--------------------------------------------------------------------------
	//---- CARGA NOTAS
	//--------------------------------------------------------------------------	

	function info__escala_notas($escala_notas)
	{
		return catalogo::consultar('sistema', 'escala_notas', array('escala_notas' => $escala_notas));
	}

	function info__evaluacion_cabecera($seleccion)
	{
		return catalogo::consultar('carga_evaluaciones_parciales', 'evaluacion_cabecera', array('evaluacion' => $seleccion['evaluacion']));
	}

	function info__evaluacion_detalle($seleccion)
	{
		$parametros = [
			'comision' => $seleccion['comision'],
			'evaluacion' => $seleccion['evaluacion'],
			'subcomision' => $seleccion['subcomision']
		];
		return catalogo::consultar('carga_evaluaciones_parciales', 'evaluacion_detalle', $parametros);
	}

	function info__evaluacion_alumnos($seleccion)
	{
		$alumnos = [];
		$parametros = [
			'comision' => $seleccion['comision'],
			'evaluacion' => $seleccion['evaluacion'],
                        'subcomision' => $seleccion['subcomision']
		];
		$datos = catalogo::consultar('carga_evaluaciones_parciales', 'evaluacion_detalle', $parametros);
		foreach ($datos as $alumno) {
			$alumnos[$alumno['alumno']] = $alumno;
		}
		return $alumnos;
	}

	function info__evaluacion_instancias($seleccion)
	{
		$instancias = array();
		$datos = catalogo::consultar('carga_evaluaciones_parciales', 'lista_instancias', array('evaluacion' => $seleccion['evaluacion']));
		foreach ($datos as $instancia) {
			$instancias[] = $instancia['instancia'];
		}
		return $instancias;
	}

	function info__resultados_posibles_escala_nota($escala_nota)
	{
		return catalogo::consultar('sistema', 'get_resultados_posibles_escala_nota', ['escala_nota' => $escala_nota]);
	}

	function evt__procesar_evaluaciones($seleccion, $alumnos)
	{
		try {
			kernel::db()->abrir_transaccion();
			$mensajes = array();
			$evaluacion = $this->info__evaluacion_cabecera($seleccion);
			foreach ($alumnos as $alumno) {
				$renglon = array(
					'evaluacion' => $evaluacion['evaluacion'],
					'alumno'     => $alumno['alumno'],
					'fecha'      => $evaluacion['evaluacion_fecha'],
					'nota'       => $alumno['nota'],
					'resultado'  => $alumno['resultado'],
					'corregido_por' => isset($alumno['corregido_por']) ? substr($alumno['corregido_por'], 0, 50) : null,
					'observaciones' => isset($alumno['observaciones']) ? substr($alumno['observaciones'], 0, 100) : null,
					'ultimo_cambio' => $alumno['ultimo_cambio'],
				);
				$respuesta = catalogo::consultar('carga_evaluaciones_parciales', 'guardar_renglon', $renglon);
				$datos_alumno = catalogo::consultar('alumno', 'get_datos', array('alumno' => $alumno['alumno']));
				$mensajes[$alumno['alumno']] = array(
					'result' => $respuesta['resultado'] == 1,
					'message' => kernel::traductor()->trans($respuesta['mensaje_indice'], array('%1%' => $datos_alumno['nombre_alumno'])),
				);
			}
			kernel::db()->cerrar_transaccion();
			return $mensajes;
		} catch (error_guarani $e) {
			kernel::db()->abortar_transaccion();
			kernel::log()->add_error($e);
			throw $e;
		}
	}

	//--------------------------------------------------------------------------
	//---- AGREGAR/ELIMINAR ALUMNO(S)
	//--------------------------------------------------------------------------

	function agregar_alumno($seleccion, $alumno)
	{
		foreach ($this->info__evaluacion_detalle($seleccion) as $alum_eval) {
			if ($alum_eval['alumno'] == $alumno) {
				return false;
			}
		}
		$datos_eval = $this->info__evaluacion_cabecera($seleccion);
		$datos = array(
			'evaluacion' => $datos_eval['evaluacion'],
			'escala_nota' => $datos_eval['escala_nota'],
			'param_alumnos' => 'ALUMNO',
			'alumno' => $alumno,
			'subcomision' => null,
			'resultado' => null,
			'evaluacion_origen' => null,
		);
		catalogo::consultar('carga_evaluaciones_parciales', 'agregar_alumnos_a_evaluacion', $datos);
		return true;
	}

	function agregar_comision($seleccion)
	{
		$datos_eval = $this->info__evaluacion_cabecera($seleccion);
		$datos = array(
			'evaluacion' => $datos_eval['evaluacion'],
			'escala_nota' => $datos_eval['escala_nota'],
			'param_alumnos' => 'COMISION',
			'alumno' => null,
			'subcomision' => null,
			'resultado' => null,
			'evaluacion_origen' => null,
		);
		return catalogo::consultar('carga_evaluaciones_parciales', 'agregar_alumnos_a_evaluacion', $datos);
	}

	function agregar_subcomision($seleccion, $subcomision)
	{
		$datos_eval = $this->info__evaluacion_cabecera($seleccion);
		$datos = array(
			'evaluacion' => $datos_eval['evaluacion'],
			'escala_nota' => $datos_eval['escala_nota'],
			'param_alumnos' => 'SUBCOMISION',
			'alumno' => null,
			'subcomision' => $subcomision,
			'resultado' => null,
			'evaluacion_origen' => null,
		);
		return catalogo::consultar('carga_evaluaciones_parciales', 'agregar_alumnos_a_evaluacion', $datos);
	}

	function agregar_resultado($seleccion, $resultado, $origen)
	{
		$datos_eval = $this->info__evaluacion_cabecera($seleccion);
		$datos_origen = $this->info__evaluacion_cabecera($origen);
		$datos = array(
			'evaluacion' => $datos_eval['evaluacion'],
			'escala_nota' => $datos_eval['escala_nota'],
			'param_alumnos' => 'EVALUACION',
			'alumno' => null,
			'subcomision' => null,
			'resultado' => $resultado,
			'evaluacion_origen' => $datos_origen['evaluacion'],
		);
		return catalogo::consultar('carga_evaluaciones_parciales', 'agregar_alumnos_a_evaluacion', $datos);
	}

	function eliminar_alumno($seleccion, $alumno, $ultimo_cambio)
	{
		$datos_eval = $this->info__evaluacion_cabecera($seleccion);
		$datos = array(
			'evaluacion' => $datos_eval['evaluacion'],
			'alumno' => $alumno,
			'ultimo_cambio' => $ultimo_cambio,
		);
		return catalogo::consultar('carga_evaluaciones_parciales', 'sacar_alumno_de_evaluacion', $datos);
	}
	
	function evt__cambio_estado_evaluacion($parametros)
	{
		\guarani::act('act_evaluaciones_nucleo')->cambio_estado_evaluacion($parametros);
		
	}


}