<?php
/**
 *	Representa un Período Genérico
 *
 * @package Calendario
 * @subpackage Períodos Genéricos
 */
class cn_ent_periodo_generico extends cn_entidad
{
	
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('periodos_genericos')->set($datos);
	}

	function get_datos($tabla=null)
	{
		return $this->tabla('periodos_genericos')->get();
	}	

	function validar_datos($datos)
	{
		$datos['periodo_generico'] = $this->tabla('periodos_genericos')->get_columna('periodo_generico');
		$this->validar_nombre_periodo_generico($datos['nombre'], $datos['periodo_generico']);
		$this->validar_orden($datos['orden_en_su_tipo']);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	function validar_orden($orden)
	{
		$es_numero_entero = $orden == round($orden);
		$esta_en_rango = ($orden >= 1) && ($orden <= 999);
		if (!$es_numero_entero || !$esta_en_rango) {
			$this->set_error('800_dato_invalido', array('Órden','debe ser un entero entre 1 y 999'));
		}
	}
	
	function validar_nombre_periodo_generico($nombre, $periodo_generico)
	{
		if (! toba::consulta_php('co_periodos_genericos')->validar_nombre_periodo_generico($nombre, $periodo_generico)) {
			$this->set_error('dato_duplicado', array('Nombre', 'período genérico'));
		}
	}
	
	function hay_registros()
	{
		return ($this->tabla('periodos_genericos')->get_cantidad_filas() > 0);
	}
	
}

?>