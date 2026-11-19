<?php
/**
 *
 *
 * @package Egresados
 * @subpackage Actualizaciones
 */
class cn_ent_actualizar_tramites_certificacion extends cn_entidad
{
	// Datos del camino tomado y el cambio de estado.
	protected $s__camino;
	protected $s__datos_cambio_estado;

	// Formularios insertados en 'sga_certificados_otorg_form' durante la operación.
	protected $s__formularios_agregados;

	// Volver a registrar la HA del alumno (hubo cambios en fecha de egreso o promedios).
	protected $s__registrar_historia_academica = false;

	function resetear()
	{
		parent::resetear();
		$this->limpiar_memoria();
	}

	//------------------------------------------------------------------------
	//-- Trámite de Certificado
	//------------------------------------------------------------------------
 
	function set_datos_tramite($datos)
	{
		$this->validar_datos_tramite($datos);
		
		$datos['estado'] = $datos['estado_nuevo'];
		$datos['fecha_cambio_estado'] = guarani_fecha::get_hoy();
		$this->tabla('certificado_otorg')->set($datos);
	}
	
	function get_datos_tramite()
	{
		return $this->tabla('certificado_otorg')->get();
	}

	//------------------------------------------------------------------------
	//-- Formularios
	//------------------------------------------------------------------------

	function set_formularios($datos)
	{
		$datos_tramite = $this->get_datos_tramite();
		$formularios = toba::consulta_php('co_circuitos_tramite')->get_formularios_por_circuito_estado_accion($datos_tramite['circuito'], $datos['estado'], $datos['accion']);
		foreach ($formularios as $form) {
			$condiciones = array('formulario' => $form['formulario']);
			if ($this->existe_formulario($condiciones)) {
				if ($form['admite_reimpresion'] == comunes::si) {
					// Si el formulario ya está asociado al trámite y admite reimpresión se incrementa la cantidad de generaciones (cant_veces_generado++).
					$id = $this->get_id_formulario($condiciones);
					$datos = $this->get_formulario($id);
					$this->modificar_columna_formulario($id, 'cant_veces_generado', $datos['cant_veces_generado'] + 1);
				}
			} else {
				// Si el formulario aún no está asociado al trámite, se agrega la relación (cant_veces_generado = 1).
				$datos = array('formulario' => $form['formulario'], 'cant_veces_generado' => 1);
				$this->agregar_formulario($datos);
			}
		}
	}

	function existe_formulario($condiciones)
	{
		return $this->tabla('formularios')->existe_fila_condicion($condiciones);
	}

	function get_id_formulario($condiciones)
	{
		return current($this->tabla('formularios')->get_id_fila_condicion($condiciones));
	}

	function get_formulario($id)
	{
		return $this->tabla('formularios')->get_fila($id);
	}

	function agregar_formulario($datos)
	{
		$this->s__formularios_agregados[$datos['formulario']] = true;
		$this->tabla('formularios')->nueva_fila($datos);
	}

	function modificar_columna_formulario($id, $columna, $valor)
	{
		$this->tabla('formularios')->set_fila_columna_valor($id, $columna, $valor);
	}
	
	function get_datos_formularios_a_imprimir()
	{
		$datos_tramite = $this->get_datos_tramite();
		return toba::consulta_php('co_circuitos_tramite')->get_formularios_por_circuito_estado_accion($datos_tramite['circuito'], $this->s__datos_cambio_estado['estado_anterior'], $this->s__datos_cambio_estado['accion']);
	}

	function es_formulario_agregado($formulario)
	{
		return isset($this->s__formularios_agregados[$formulario]);
	}

	//------------------------------------------------------------------------
	//-- Cambios de Estado
	//------------------------------------------------------------------------

	function agregar_cambio_estado($datos)
	{
		$this->s__datos_cambio_estado = array(	'accion' => $datos['accion'],
												'estado_anterior' => $datos['estado'],
												'estado_nuevo' => $datos['estado_nuevo'],
												'observaciones' => $datos['observaciones_cambio_estado']);
		if (isset($datos['fecha_egreso'])) {
			$this->s__datos_cambio_estado['fecha_egreso'] = $datos['fecha_egreso'];
		}
		if (isset($datos['promedio'])) {
			$this->s__datos_cambio_estado['promedio'] = $datos['promedio'];
		}
		if (isset($datos['promedio_sin_aplazos'])) {
			$this->s__datos_cambio_estado['promedio_sin_aplazos'] = $datos['promedio_sin_aplazos'];
		}
		$this->tabla('cambios_estado')->nueva_fila($this->s__datos_cambio_estado);
	}
	
	function get_datos_cambio_estado()
	{
		$datos_accion = current(toba::consulta_php('co_circuitos_tramite')->get_datos_accion($this->s__datos_cambio_estado['accion']));
		$this->s__datos_cambio_estado['accion_nombre'] = $datos_accion['accion_nombre'];
		$datos_estado = current(toba::consulta_php('co_circuitos_tramite')->get_datos_estado($this->s__datos_cambio_estado['estado_nuevo']));
		$this->s__datos_cambio_estado['estado_nuevo_nombre'] = $datos_estado['estado_nombre'];
		return $this->s__datos_cambio_estado;
	}

	//------------------------------------------------------------------------
	//-- Validaciones
	//------------------------------------------------------------------------

	function validar_datos_tramite($datos)
	{
		$this->validar_pago_cuota($datos);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	/**
	 * Si el camino exige chequear pago, se ejecuta el control fijo "Verificar Pago".
	 *
	 */
	function validar_pago_cuota($datos)
	{
		if ($this->s__camino['controlar_pago'] == comunes::si && ! tramite_certificacion::verificar_pago()) {
			$this->set_error('800EGR_no_pago');	
		}
	}
	
	//------------------------------------------------------------------------
	//-- Operaciones Auxiliares
	//------------------------------------------------------------------------
	
	function enviar_aviso_alumno()
	{
		return $this->s__camino['enviar_aviso_alumno'];
	}

	function recalcular_historia_academica($datos)
	{
		$datos_tramite = $this->get_datos_tramite();
		$this->s__camino = current(toba::consulta_php('co_circuitos_tramite')->get_camino_por_circuito_estado_accion($datos_tramite['circuito'], $datos['estado'], $datos['accion']));

		if ($this->s__camino['recalcular_histacad'] == comunes::si) {
			$nueva_fecha_egreso = toba::consulta_php('co_alumnos')->get_fecha_egreso($datos_tramite['alumno'], null, $datos_tramite['certificado']);
			$nueva_duracion_real_en_meses = null;
			// La fecha de egreso puede ser vacía cuando el certificado se obtiene sólo con requisitos (sin actividades/módulos).
			if (!empty($nueva_fecha_egreso)) {
				$nueva_duracion_real_en_meses = toba::consulta_php('co_alumnos')->get_duracion_real_en_meses($datos_tramite['alumno'], $nueva_fecha_egreso);
			}
			$nuevo_promedio = toba::consulta_php('co_alumnos')->get_promedio($datos_tramite['alumno'], null, $datos_tramite['certificado']);
			$nuevo_promedio_sin_aplazos = toba::consulta_php('co_alumnos')->get_promedio($datos_tramite['alumno'], null, $datos_tramite['certificado'], comunes::no);

			if ($datos_tramite['fecha_egreso'] != $nueva_fecha_egreso || $datos_tramite['duracion_real_en_meses'] != $nueva_duracion_real_en_meses || $datos_tramite['promedio'] != $nuevo_promedio || $datos_tramite['promedio_sin_aplazos'] != $nuevo_promedio_sin_aplazos) {
				$this->s__registrar_historia_academica = true;
				$datos['fecha_egreso'] = $nueva_fecha_egreso;
				$datos['duracion_real_en_meses'] = $nueva_duracion_real_en_meses;
				$datos['promedio'] = $nuevo_promedio;
				$datos['promedio_sin_aplazos'] = $nuevo_promedio_sin_aplazos;
			}
		}

		return $datos;
	}

	function registrar_historia_academica()
	{
		return $this->s__registrar_historia_academica;
	}
}
?>