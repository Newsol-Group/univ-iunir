<?php

class form_contrasenia extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$mje_clave_confirmar = guarani_mensajes::get('800_clave_confirmar_incorrecta');

		echo "
		//---- Validacion de EFs -----------------------------------
		
		{$this->objeto_js}.evt__clave_confirmar__validar = function()
		{
			if (this.ef('clave').tiene_estado() &&
				this.ef('clave').get_estado() != this.ef('clave_confirmar').get_estado()) {
				this.ef('clave_confirmar').set_error('$mje_clave_confirmar');
				return false;
			}
			return true;
		}
		";
	}
}