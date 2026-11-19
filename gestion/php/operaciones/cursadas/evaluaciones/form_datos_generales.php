<?php
class form_datos_generales extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$mje_horas = guarani_mensajes::get('800_error_rango_horas');

		echo "
		//---- Validacion general ----------------------------------
		
		{$this->objeto_js}.evt__validar_datos = function()
		{
			if (this.ef('hora_inicio').tiene_estado() && this.ef('hora_fin').tiene_estado()) {
				var hora_inicio = this.ef('hora_inicio').hora();
				var hora_fin = this.ef('hora_fin').hora();
 
				if (hora_inicio > hora_fin) {
					notificacion.agregar('$mje_horas');
					return false;
				}
			}

			return true;
		}
		";
	}

}
?>