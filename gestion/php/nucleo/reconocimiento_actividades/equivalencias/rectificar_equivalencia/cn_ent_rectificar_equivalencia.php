<?php
/**
 * Representa una Rectificación de un Tramite de Equivalencia.
 *
 *
 * @package		Reconocimiento de Actividades
 * @subpackage	Equivalencias
 */
class cn_ent_rectificar_equivalencia extends cn_entidad
{
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

	function unset_cursor_equiv_otorgada()
	{
		$this->tabla('equiv_otorgada')->resetear_cursor();
	}

	function set_datos_equiv_otorgada($datos)
	{
		$this->validar_datos_actividades($datos);
		$this->validar_parcial_resultado($datos);
		$this->tabla('equiv_otorgada')->set($datos);
	}

	function agregar_equiv_otorgada($datos)
	{
		$this->validar_nueva_equivalencia($datos);
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

	function validar_nueva_equivalencia($datos)
	{
		$this->validar_datos_actividades($datos);
		$this->validar_instancia_parcial_total($datos, true);
		$this->validar_parcial_resultado($datos);
	}

	function validar_parcial_resultado($datos)
	{
		// Si la instancia es parcial 
		if ($datos['instancia'] == equivalencia::equiv_instancia_parcial) {
			// Se verifica que el resultado no sea 'Desaprobado'. No pueden existir equivalencias con esta combinación
			if (!is_null($datos['resultado'])) {
				if ($datos['resultado'] == escalas_notas::resultado_reprobado) {
					$msj = guarani_mensajes::get('800EQU_alcance_parcial_desaprobado');
					$this->set_error($msj);
				}
			}
		}
		// Se verifica que se hayan completado los campos correspondientes al alcance definido para la matriz.
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	function validar_instancia_parcial_total($datos, $controlar_total)
	{
		if ($controlar_total) {
			$instancia = ($controlar_total) ? equivalencia::equiv_instancia_total : equivalencia::equiv_instancia_parcial;
			$msj = guarani_mensajes::get('800EQU_total_parcial_error', array('Total', 'Parcial/Total'));
		} else {
			$instancia = ($controlar_total) ? equivalencia::equiv_instancia_total : equivalencia::equiv_instancia_parcial;
			$msj = guarani_mensajes::get('800EQU_total_parcial_error', array('Parcial', 'Total/Parcial'));
		}
		$filas = $this->get_datos_equiv_otorgada();
		if (!empty($filas)) {
			foreach ($filas as $fila) {
				if (($fila['elemento'] == $datos['elemento']) && ($fila['instancia'] == $instancia)) {
					$this->set_error($msj);
					break;
				}
			}
		}
	}

	function validar_datos_actividades($datos)
	{
		// Validación de datos obligatorios
		if (is_null($datos['elemento'])) {
			$msj = guarani_mensajes::get('dato_faltante', array('Actividad'));
			$this->set_error($msj);
		}
		if (is_null($datos['resultado'])) {
			$msj = guarani_mensajes::get('dato_faltante', array('Resultado'));
			$this->set_error($msj);
		}
	}

}
?>