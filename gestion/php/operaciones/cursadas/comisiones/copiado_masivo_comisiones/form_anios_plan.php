<?php
class form_anios_plan extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$msj = guarani_mensajes::get('800_campos_sin_seleccion', array('Año según Plan'));
		
		echo "
		//---- Validacion general ----------------------------------
		
		{$this->objeto_js}.evt__anio_segun_plan__validar = function()
		{
			if (!this.ef('anio_segun_plan').tiene_estado()) {
				this.ef('anio_segun_plan').set_error('$msj');
				this.ef('anio_segun_plan').seleccionar_todo(true);
				return false;
			}
			return true;
		}
		
		{$this->objeto_js}.evt__anio_segun_plan__procesar = function(es_inicial)
		{
			//Si accedo a la pantalla por primera vez setea todos los valores por default.
			if (!this.ef('anio_segun_plan').tiene_estado() && es_inicial) {
				this.ef('anio_segun_plan').seleccionar_todo(true);
			}
		}
		";
	}

}
?>