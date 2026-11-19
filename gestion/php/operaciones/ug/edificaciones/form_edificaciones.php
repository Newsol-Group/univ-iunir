<?php
/**
 *
 *
 * @package Unidades de Gestion
 * @subpackage Edificaciones
 */

class form_edificaciones extends guarani_pers_ei_formulario
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
			//	Separa el id de localidad que viene como localidad:codigo_postal del buscador, 
			//	para mostrar el valor correspondiente en el ef de codigo_postal.
			
			if (! es_inicial) {
				if (this.ef('localidad').tiene_estado()) {
					var localidad = this.ef('localidad').get_estado().toString();
					var index = localidad.indexOf(':');
					if (index != -1 && index < localidad.length) {	//Si esta el separador : en el string de localidad y el string continua despues del mismo
						var cp = localidad.substring(localidad.indexOf(':') + 1, localidad.length);
						this.ef('codigo_postal').set_estado(cp);
					} else {										//La localidad no tiene cp, entonces dejo el estado de cp en vacio
						this.ef('codigo_postal').resetear_estado('');	
					}
				} else {
					//cubre el caso en que se limpia el valor del popup de localidad.
					this.ef('codigo_postal').resetear_estado();
				}
			}
		}
		
		";
	}

}