<?php

class ci_edi_espacio extends ci_edicion_simple
{
	function conf()
	{
		if (! $this->cn()->entidad()->puede_cambiar_tipo_ubicacion()) {
			$this->dep('formulario')->set_solo_lectura(array('espacio_tipo','ubicacion'));
		} 
	}
	
}
?>