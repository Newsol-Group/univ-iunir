<?php
/**
 *
 * @package Exámenes
 * @subpackage Actas
 */
class cn_ent_cargar_nota_acta_examen extends cn_ent_acta_examen
{
	function get_listado($filtro)
	{
		return toba::consulta_php('co_actas')->get_listado_actas_examen_con_sin_mesas($filtro);
	}

	function get_alumnos_acta_examen($id_acta = null, $estado = null)
	{
		$params_sistema = array('exa_orden_detalle_actas' => guarani::parametros()->get_valor('exa_orden_detalle_actas'));
		return toba::consulta_php('co_actas')->get_alumnos_acta_examen($id_acta, $estado, $params_sistema);
	}

	function set_datos_detalles($datos)
	{
		// El usuario puede haber modificado la Instancia asociada al Alumno (para Actas Rectificativas) y
		// debe modificarse correspondientemente la escala de notas en el detalle del mismo.
		$escalas = toba::consulta_php('co_mesas_de_examen')->get_instancias_mesa_examen($this->get_mesa_examen());
		$escalas_instancias_mesa = rs_convertir_asociativo($escalas, array('instancia'), 'escala_nota');
		foreach ($datos as $id => $detalle_alumno) {
			if ($detalle_alumno['escala_nota'] != $escalas_instancias_mesa[$detalle_alumno['instancia']]) {
				$datos[$id]['escala_nota'] = $escalas_instancias_mesa[$detalle_alumno['instancia']];
			}
		}
		$this->validar_carga_notas_detalles($datos);
		$this->procesar_detalles($datos);
	}

	function set_carga_notas_eval_manual($datos)
	{
		$this->validar_carga_nota_eval_manual($datos);
		$this->procesar_detalles_eval_manual($datos);
	}

	function set_carga_nota_final($alumno, $datos)
	{
		$this->validar_carga_nota_final($datos);
		$this->modificar_detalle($alumno[apex_datos_clave_fila], $datos);
	}

	function guardar($datos_acta = null)
	{
		try {
			$this->desactivar_transacciones_anidadas();
			toba::db()->abrir_transaccion();
			$this->sincronizar();
			$this->actualizar_version($datos_acta);
			toba::db()->cerrar_transaccion();
			$this->activar_transacciones_anidadas();
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			$this->activar_transacciones_anidadas();
			throw $e;
		}
	}

	function actualizar_version($datos_acta)
	{
		$id = $datos_acta['id_acta'];
		$version = $datos_acta['version'];
		$version_impresa = $datos_acta['version_impresa'];
		if ($version == $version_impresa) {
			guarani::act('act_actas')->actualizar_version_acta($id, $version);
		}
	}

	//--------------------------------------------------------------------------
	//---- Validaciones Carga Notas --------------------------------------------
	//--------------------------------------------------------------------------

	function validar_carga_notas_detalles($datos)
	{
		foreach ($datos as $detalle) {

			// Se validan la nota y el resultado de cursada.
			$this->validar_nota($detalle['nota'], $detalle['escala_nota'], $detalle['apellido_y_nombre']);
			$this->validar_resultado($detalle['nota'], $detalle['escala_nota'], $detalle['resultado'], $detalle['apellido_y_nombre']);
			$this->validar_fecha($detalle['fecha'], $detalle['apellido_y_nombre']);
		}

		if ($this->hay_errores) {
			throw guarani::error($this->get_mensaje_error());
		}
	}

	/**
	 * Verifica que la nota ingresada sea válida de acuerdo a la escala de notas de la instancia correspondiente al alumno
	 * Retorna true si la nota es valida / false si es invalida, esto es para luego saber si se debe validar el resultado
	 */
	function validar_nota($nota, $escala_nota, $apellido_nombre_alumno)
	{
		$validador = escalas_notas::validador_escalas_notas();
		if (!$validador->es_nota_valida($nota, $escala_nota)) {
			$this->set_error('800_nota_invalida', array($apellido_nombre_alumno));
			return false;
		}
		return true;
	}

	/**
	 * Verifica que el resultado seteado para el alumno sea el que se corresponde con la escala de notas asignada al mismo
	 * (según su instancia) y la nota que se seteó para él, o en su defecto que el resultado sea Ausente y al alumno no se
	 * le haya seteado nota. Además se verifica que si hay cargado un resultado debe haberse cargado una nota y viceversa.
	 */
	function validar_resultado($nota, $escala_nota, $resultado, $apellido_nombre_alumno)
	{
		$validador = escalas_notas::validador_escalas_notas();
		if (!empty($resultado)) {
			if ($resultado == escalas_notas::resultado_ausente && isset($nota)) {
				$this->set_error('800EXA_error_nota_ausente', array($apellido_nombre_alumno));
			} elseif (isset($nota) && !$validador->es_resultado_valido($nota, $escala_nota, $resultado)) {
				$this->set_error('800EXA_error_nota_resultado', array($apellido_nombre_alumno));
			} elseif (!isset($nota) && ($resultado != escalas_notas::resultado_ausente)) {
				$this->set_error('800EXA_error_nota_faltante', array($apellido_nombre_alumno));
			}
		} elseif (isset($nota)) {
			$this->set_error('800EXA_error_nota_resultado', array($apellido_nombre_alumno));
		}
	}

	/**
	 * Verifica que la fecha de la carga de nota para el alumno sea válida, es decir, mayor o igual a la fecha del 
	 * llamado-mesa y menor o igual a la actual.
	 */
	function validar_fecha($fecha_nota, $apellido_nombre_alumno)
	{
		if (!empty($fecha_nota) && !toba::consulta_php('co_mesas_de_examen')->es_fecha_valida_de_carga_nota_examen($fecha_nota, $this->get_llamado_mesa())) {
			$this->set_error('800_control_fecha_entre', array('cargada para el alumno ' . $apellido_nombre_alumno, 'fecha del llamado de la mesa', 'fecha actual'));
		}
	}

	function validar_carga_nota_eval_manual($datos)
	{
		foreach ($datos as $detalle) {
			//Se valida la nota de la evaluación
			if ($this->validar_nota($detalle['nota'], $detalle['escala_nota'], "la evaluación de tipo " . $detalle['tipo_eval_nombre'])) {
				$this->validar_resultado($detalle['nota'], $detalle['escala_nota'], $detalle['resultado'], "la evaluación de tipo " . $detalle['tipo_eval_nombre']);
			}
		}

		if ($this->hay_errores) {
			throw guarani::error($this->get_mensaje_error());
		}
	}

	function validar_carga_nota_final($datos)
	{
		//Se valida la nota de la evaluación
		$this->validar_nota($datos['nota'], $datos['escala_nota'], 'la Nota Final');
		if ($this->hay_errores) {
			throw guarani::error($this->get_mensaje_error());
		}
	}

	/**
	 * Retorna las escalas de la mesa de examen actual
	 *
	 * @return <type>
	 */
	function get_escalas_notas_mesa($mesa_examen = null)
	{
		return guarani::co('co_mesas_de_examen')->get_escala_notas_mesa($mesa_examen);
	}

}
?>