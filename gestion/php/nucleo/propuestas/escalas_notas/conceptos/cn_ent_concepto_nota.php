<?php
/**
 *
 *
 * @package		Propuestas Formativas
 * @subpackage 	Escalas de Notas
 * 
 */
class cn_ent_concepto_nota extends cn_entidad
{
	function get_datos($tabla=null)
	{
		return $this->tabla('escalas_notas_concepto')->get();
	}

	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$datos['estado'] = ($datos['activo'] == comunes::si) ? comunes::estado_activo : comunes::estado_baja;
		$this->tabla('escalas_notas_concepto')->set($datos);
	}
	
	//-------------------------------------------------------------------

	function validar_datos($datos)
	{
		$datos['concepto'] = $this->tabla('escalas_notas_concepto')->get_columna('concepto');
		$this->validar_nombre_escala_nota_concepto($datos['nombre'], $datos['concepto']);
		
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	function validar_nombre_escala_nota_concepto($nombre, $concepto)
	{
		if (! toba::consulta_php('co_conceptos_notas')->validar_nombre_concepto_notas($nombre, $concepto)) {
			$this->set_error('dato_existente', array('nombre', 'Concepto de Nota'));
		}
	}
}
?>