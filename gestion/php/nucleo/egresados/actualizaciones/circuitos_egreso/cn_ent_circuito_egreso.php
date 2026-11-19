<?php
/**
 *
 * 
 * @package 	Egresados
 * @subpackage 	Actualizaciones
 * 
 */
class cn_ent_circuito_egreso extends cn_entidad
{
	//------------------------------------------------------------------------
	//-- Circuito de Egreso
	//------------------------------------------------------------------------

	function get_datos_generales()
	{
		return $this->tabla('circuitos')->get();
	}

	function set_datos_generales($datos)
	{
		$this->tabla('circuitos')->set($datos);
	}

	//------------------------------------------------------------------------
	//-- Caminos de Circuito de Egreso
	//------------------------------------------------------------------------

	function get_datos_caminos()
	{
		return $this->tabla('caminos')->get_filas();
	}

	function set_datos_camino($datos)
	{
		if ($this->hay_camino_seleccionado()) {
			$this->validar_datos_camino($datos);
			$this->tabla('caminos')->set($datos);
		}
	}

	function seleccionar_camino($id_fila_camino)
	{
		$this->tabla('caminos')->set_cursor($id_fila_camino);
	}

	function deseleccionar_camino()
	{
		$this->tabla('caminos')->resetear_cursor();
	}

	function get_datos_camino_seleccionado()
	{
		if ($this->hay_camino_seleccionado()) {
			return $this->tabla('caminos')->get();
		}
	}

	function hay_camino_seleccionado()
	{
		return $this->tabla('caminos')->hay_cursor();
	}

	function eliminar_camino_seleccionado()
	{
		if ($this->hay_camino_seleccionado()) {
			$id_fila = $this->tabla('caminos')->get_cursor();
			$this->tabla('caminos')->eliminar_fila($id_fila);
		}
	}

	function nuevo_camino($datos)
	{
		$this->validar_datos_camino($datos);
		$id = $this->tabla('caminos')->nueva_fila($datos);
		$this->seleccionar_camino($id);
	}

	function validar_datos_camino($datos)
	{
		$this->validar_camino_repetido($datos);
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	/**
	 * Valida que no se ingrese más de una vez la combinación <Estado Inicial, Acción>.
	 *
	 * @param array $datos Datos del camino a validar.
	 */
	function validar_camino_repetido($datos)
	{
		$id_fila = $this->tabla('caminos')->get_cursor();
		$caminos = $this->tabla('caminos')->get_filas();
		foreach ($caminos as $camino) {
			if ($camino['estado_inicial'] == $datos['estado_inicial'] && $camino['accion'] == $datos['accion'] && (is_null($id_fila) || $id_fila != $camino[apex_datos_clave_fila])) {
				$estado_inicial = empty($camino['estado_inicial']) ? htmlspecialchars('<Sin estado inicial>') : $camino['estado_inicial_nombre'];
				$this->set_error('800EGR_transicion_repetida', array($estado_inicial, $camino['accion_nombre']));
			}
		}
	}

	/**
	 * Valida que el circuito tenga al menos una transición cuyo estado inicial sea vacío.
	 *
	 */
	function validar_existencia_camino()
	{
		$caminos = $this->tabla('caminos')->get_filas();
		foreach ($caminos as $camino) {
			if (empty($camino['estado_inicial'])) {
				return;
			}
		}
		$this->set_error('800EGR_falta_transicion_inicial');
	}

	/**
	 * Valida que el circuito tenga un único estado final (sin transiciones salientes hacia otro estado).
	 */
	function validar_estado_final_unico()
	{
		$caminos = $this->tabla('caminos')->get_filas();
		$estados = array_unique(aplanar_matriz($caminos, 'estado_final'));
		$cant_estados_finales = 0;
		foreach ($estados as $estado) {
			$condiciones = array(array('estado_inicial', '==', $estado), array('estado_final', '!=', $estado));
			if (!$this->tabla('caminos')->existe_fila_condicion($condiciones, false)) {
				$cant_estados_finales++;
			}
			if ($cant_estados_finales > 1) {
				$this->set_error('800EGR_unico_estado_final');
				break;
			}
		}
	}

	//------------------------------------------------------------------------
	//-- Formularios de Camino de Circuito de Egreso
	//------------------------------------------------------------------------

	function get_datos_forms()
	{
		return $this->tabla('caminos_form')->get_filas();
	}

	function set_datos_forms($datos)
	{
		return $this->tabla('caminos_form')->procesar_filas($datos);
	}
	
	//------------------------------------------------------------------------
	//-- Campos de Camino de Circuito de Egreso
	//------------------------------------------------------------------------

	function get_datos_campos()
	{
		return $this->tabla('caminos_campos')->get_filas();
	}

	function set_datos_campos($datos)
	{
		$this->tabla('caminos_campos')->procesar_filas($datos);
	}

	//------------------------------------------------------------------------
	//-- Validaciones generales
	//------------------------------------------------------------------------

	function pre_sincronizar()
	{
		$this->validar_existencia_camino();
		$this->validar_estado_final_unico();
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}
}
?>