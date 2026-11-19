<?php
class cn_ent_vigencia_regularidad extends cn_entidad
{
	function set_datos_excepcion($datos)
	{		
		$this->tabla('vigencia_regularidad')->set($datos);
	}
	
	function get_datos_excepcion()
	{
		return $this->tabla('vigencia_regularidad')->get();
	}	
	
	function set_planes($datos)
	{
		$this->validar_planes($datos);
		$this->tabla('plan')->eliminar_filas();
		$this->tabla('plan')->procesar_filas($datos);
	}
	
	function get_planes()
	{
		return $this->tabla('plan')->get_filas();
	}
	
	function validar_planes($datos)
	{		
		if (empty($datos)) {
			throw new guarani_error('Debe seleccionar al menos un plan');
		}	
	}
}

?>