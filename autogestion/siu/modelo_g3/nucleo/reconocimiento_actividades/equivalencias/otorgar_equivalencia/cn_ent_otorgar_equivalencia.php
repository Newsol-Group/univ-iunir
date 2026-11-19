<?php
/**
 *
 *
 * @package 	Reconocimiento de Actividades
 * @subpackage 	Otorgar Equivalencia
 * 
 */
class cn_ent_otorgar_equivalencia extends cn_entidad
{
	function cargar_atributos_parametros($propuesta, $plan)
	{
		$this->resetear_atributos_parametros();
		$operacion = guarani::get_id_operacion();
		
		$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras(array($propuesta));
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_ra, $ras);
		
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, array($propuesta));
		
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_plan, array($plan));
	}
	
	//--------------------------------------------------------------------------
	//---- sga_equiv_tramites
	//--------------------------------------------------------------------------

	function get_datos($tabla = null)
	{
		return $this->tabla('equiv_tramite')->get();
	}

	function set_datos($datos, $tabla = null, $validar = true)
	{
		$this->tabla('equiv_tramite')->set($datos);
		if (!empty($datos['alumno'])) {
			$datos_alumno = toba::consulta_php('co_alumnos')->get_datos_basicos_alumno($datos['alumno']);
			$this->cargar_atributos_parametros($datos_alumno['propuesta'], $datos_alumno['plan']);
		} else {
			$this->resetear_atributos_parametros();
		}
	}

	//--------------------------------------------------------------------------
	//---- sga_equiv_otorgadas
	//--------------------------------------------------------------------------

	function get_cursor_equiv_otorgada()
	{
		return $this->tabla('equiv_otorgada')->get_cursor();
	}

	function set_cursor_equiv_otorgada($id_fila)
	{
		$this->tabla('equiv_otorgada')->set_cursor($id_fila);
	}

	function hay_equivalencia_seleccionada()
	{
		return $this->tabla('equiv_otorgada')->hay_cursor();
	}

	function unset_cursor_equiv_otorgada()
	{
		$this->tabla('equiv_otorgada')->resetear_cursor();
	}

	function set_datos_equiv_otorgada($datos)
	{
		$this->validar_datos_otorgadas($datos);
		$this->tabla('equiv_otorgada')->set($datos);
	}

	function agregar_equiv_otorgada($datos)
	{
		$this->validar_datos_otorgadas($datos);
		return $this->tabla('equiv_otorgada')->nueva_fila($datos);
	}

	function eliminar_equiv_otorgada($id_fila)
	{
		return $this->tabla('equiv_otorgada')->eliminar_fila($id_fila);
	}

	function get_dato_equiv_otorgada()
	{
		return $this->tabla('equiv_otorgada')->get();
	}

	function get_datos_equiv_otorgada()
	{
		return $this->tabla('equiv_otorgada')->get_filas();
	}

	function eliminar_otorgadas()
	{
		return $this->tabla('equiv_otorgada')->eliminar_filas();
	}

	//--------------------------------------------------------------------------
	//---- sga_equiv_internas
	//--------------------------------------------------------------------------

	function set_datos_interna($datos)
	{
		$this->tabla('equiv_internas')->procesar_filas($datos);
	}

	function get_datos_interna()
	{
		return $this->tabla('equiv_internas')->get_filas();
	}

	function agregar_interna($datos)
	{
		$this->tabla('equiv_internas')->nueva_fila($datos);
	}

	function eliminar_interna($id_fila)
	{
		$this->tabla('equiv_internas')->eliminar_fila($id_fila);
	}

	function eliminar_todo_internas()
	{
		$this->tabla('equiv_internas')->eliminar_filas();
	}

	//--------------------------------------------------------------------------
	//---- sga_equiv_externas
	//--------------------------------------------------------------------------

	function set_datos_externa($datos)
	{
		$this->tabla('equiv_externas')->procesar_filas($datos);
	}

	function get_datos_externa()
	{
		return $this->tabla('equiv_externas')->get_filas();
	}

	function agregar_externa($datos)
	{
		$this->tabla('equiv_externas')->nueva_fila($datos);
	}

	function eliminar_externa($id_fila)
	{
		$this->tabla('equiv_externas')->eliminar_fila($id_fila);
	}

	function eliminar_todo_externas()
	{
		$this->tabla('equiv_externas')->eliminar_filas();
	}

	//--------------------------------------------------------------------------
	//---- Validaciones 
	//--------------------------------------------------------------------------

	function validar_datos_otorgadas($datos)
	{
		$this->validar_instancias($datos['elemento'], $datos['instancia']);

		if (!empty($datos['resultado'])) {
			if ($datos['resultado'] == escalas_notas::resultado_reprobado) {
				// Si la instancia es "Parcial" el resultado NO puede ser "Desaprobado".
				if ($datos['instancia'] == equivalencia::equiv_instancia_parcial) {
					$msj = guarani_mensajes::get('800EQU_alcance_parcial_desaprobado');
					$this->set_error($msj);
				}
			} elseif ($datos['resultado'] == escalas_notas::resultado_aprobado) {
				$this->validar_equivalencias_aprobadas($datos['elemento'], $datos['instancia']);
			}
		}

		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	/**
	 * Valida que no exista una equivalencia de instancia "Parcial" si existe otra de instancia "Total" para la misma actividad (y viceversa).
	 *
	 * @param integer $elemento
	 * @param integer $instancia
	 */
	function validar_instancias($elemento, $instancia)
	{
		if ($instancia == equivalencia::equiv_instancia_total || $instancia == equivalencia::equiv_instancia_parcial) {
			$otra_instancia = ($instancia == equivalencia::equiv_instancia_total) ? equivalencia::equiv_instancia_parcial : equivalencia::equiv_instancia_total;
			$parametros_msj = ($instancia == equivalencia::equiv_instancia_total) ? array('Total', 'Parcial') : array('Parcial', 'Total');
			$msj = guarani_mensajes::get('800EQU_total_parcial_error', $parametros_msj);

			$id_fila = $this->get_cursor_equiv_otorgada();
			$equivalencias = $this->get_datos_equiv_otorgada();
			foreach ($equivalencias as $e) {
				if ($e['elemento'] == $elemento && $e['instancia'] == $otra_instancia && (is_null($id_fila) || $id_fila != $e[apex_datos_clave_fila])) {
					$this->set_error($msj);
					break;
				}
			}
		}
	}

	/**
	 * Valida que no existan dos equivalencias con resultado "Aprobado" para la misma Instancia y la misma actividad.
	 *
	 * @param integer $elemento
	 * @param char $instancia
	 */
	function validar_equivalencias_aprobadas($elemento, $instancia)
	{
		$msj = guarani_mensajes::get('800EQU_alcance_aprobado');
		$id_fila = $this->get_cursor_equiv_otorgada();
		$equivalencias = $this->get_datos_equiv_otorgada();
		foreach ($equivalencias as $e) {
			if ($e['elemento'] == $elemento && $e['instancia'] == $instancia && !empty($e['resultado']) && $e['resultado'] == escalas_notas::resultado_aprobado && (is_null($id_fila) || $id_fila != $e[apex_datos_clave_fila])) {
				$this->set_error($msj);
				break;
			}
		}
	}

}
?>