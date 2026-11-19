<?php
class form extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__localidad_cp__procesar = function(es_inicial)
		{
			if (! es_inicial) {
				var localidad = this.ef('localidad_cp').get_estado().toString();
				var index = localidad.indexOf(':');
				if (index != -1 && index < localidad.length) {
					var cp = localidad.substring(localidad.indexOf(':') + 1, localidad.length);
					this.ef('cp').set_estado(cp);
				} else {
					this.ef('cp').set_estado('La localidad no posee CP asignado');
				}
			}
		}
		";
	}

}
?>