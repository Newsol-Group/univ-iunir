<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos
 * 
 */
class cn_ent_requisitos extends cn_entidad
{

	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('requisitos_generales')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('requisitos_generales')->get();
	}

	function get_columna($columna)
	{
		return $this->tabla('requisitos_generales')->get_columna($columna);
	}

	function set_columna_valor($columna, $valor)
	{
		return $this->tabla('requisitos_generales')->set_columna_valor($columna, $valor, true);
	}

	//-------------------------------------------------------------------
	
	function nueva_fila_req_validos($datos)
	{
		$this->tabla('requisitos_validos')->nueva_fila($datos);
	}
	
	function eliminar_fila_req_validos($id_fila)
	{
		$requisito_valido = $this->tabla('requisitos_validos')->get_fila($id_fila);
		$this->validar_accion_a_eliminar($requisito_valido);
		$this->tabla('requisitos_validos')->eliminar_fila($id_fila);
	}

	function eliminar_req_validos()
	{
		$this->tabla('requisitos_validos')->eliminar_filas();
	}
	
	function get_datos_req_validos()
	{
		return $this->tabla('requisitos_validos')->get_filas();
	}
	
	//-------------------------------------------------------------------
	
	function nueva_fila_ptos_control_req($datos)
	{
		$this->tabla('ptos_control_req')->nueva_fila($datos);
	}
	
	function eliminar_fila_ptos_control_req($id_fila)
	{
		$this->tabla('ptos_control_req')->eliminar_fila($id_fila);
	}
	
	function eliminar_ptos_control_req()
	{
		$this->tabla('ptos_control_req')->eliminar_filas();
	}

	function get_datos_ptos_control_req()
	{
		return $this->tabla('ptos_control_req')->get_filas();
	}
	
	//-------------------------------------------------------------------------------------------
	
	function validar_datos($datos)
	{
		$datos['requisito'] = $this->tabla('requisitos_generales')->get_columna('requisito');
		if ($this->controlador->entidad_es_nueva()) {
			$this->validar_nombre_requisito($datos['nombre'], $datos['requisito']);
		}		
		//$this->validar_requisito_subordinado_de($datos['requisito_subordinado_de'],$datos['requisito']);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	function validar_nombre_requisito($nombre, $requisito)
	{
		if (!toba::consulta_php('co_requisitos')->validar_nombre_requisito($nombre, $requisito)) {
			$this->set_error('dato_duplicado', array('nombre','requisito general'));
		}
	}
	
	function validar_requisito_subordinado_de($requisito_subordinado_de, $requisito)
	{
		if (!$this->validar_subordinado_de($requisito_subordinado_de, $requisito)) {
			$this->set_error('800_dato_invalido', array('campo Subordinado de','el Requisito General padre debe ser distinto del Requisito General actual'));
		}
	}
	
	/**
        Valida si un requisito subordinado para un requisito dado es correcto o no
    */
	function validar_subordinado_de($requisito_subordinado_de, $requisito)
	{
		if (!isset($requisito_subordinado_de)) {
			return true;
		}
		if (!isset($requisito)) {
			return true;
		}
		
		return ($requisito_subordinado_de != $requisito);
	}

	//-------------------------------------------------------------------------------------------
	
	function validar_accion_a_eliminar($datos)
	{
		if (!$this->controlador->entidad_es_nueva()) {
			$this->validar_accion_requisito($datos['accion'], $datos['requisito']);
		}
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	function validar_accion_requisito($accion, $requisito)
	{
		$req_grupo_acciones = toba::consulta_php('co_requisitos')->get_requisito_grupo_accion($requisito);
		foreach ($req_grupo_acciones as $clave => $valor) {
			if ($valor['accion'] == $accion) {
				$this->set_error('800_dato_no_eliminable', array('Acción','esta en uso'));
			}
		}
	}

	//-------------------------------------------------------------------------------------------

	function pre_sincronizar()
	{
		if ($this->tabla('requisitos_generales')->get_columna('requisito_tipo') != requisito::tipo_documentales) {
			$this->validar_definicion_regla($this->tabla('requisitos_generales')->get_columna('regla'));
		}		
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	} 
	
	function validar_definicion_regla($regla)
	{
		if (is_null($regla)) {
			$this->set_error('800_falta_regla');
		}
	}
	
}

?>