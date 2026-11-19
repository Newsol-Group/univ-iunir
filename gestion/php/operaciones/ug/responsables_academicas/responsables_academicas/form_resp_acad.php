<?php
class form_resp_acad extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------
		{$this->objeto_js}.evt__localidad__procesar = function(es_inicial)
		{
			if (! es_inicial) {
				var localidad = this.ef('localidad').get_estado().toString();
				var index = localidad.indexOf(':');
				if (index != -1 && index < localidad.length) {
					var cp = localidad.substring(localidad.indexOf(':') + 1, localidad.length);
					this.ef('codigo_postal').set_estado(cp);
				} else {
					this.ef('codigo_postal').resetear_estado('');
				}
			}
		}
		";
	}
}
?>