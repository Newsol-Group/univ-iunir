<?php
/**
 *
 *
 * @package 	Cursadas
 * @subpackage 	Comisiones
 * 
 */
class cn_ent_regla_calculo_notas extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('regla_calculo')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('regla_calculo')->get();
	}

	//-------------------------------------------------------------------
	
	function validar_datos($datos)
	{
		$regla_calculo = $this->tabla('regla_calculo')->get_columna('regla_calculo');
		
		if (! toba::consulta_php('co_reglas_calculo')->validar_nombre_regla_calculo_notas($datos['nombre'], $regla_calculo)) {
			$this->set_error('dato_duplicado', array('nombre', 'Regla para el Cálculo de Notas'));
			throw guarani::error($this->get_mensaje_error());
		}
	}
}
?>