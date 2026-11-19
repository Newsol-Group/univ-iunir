<?php
class form_anios_academicos extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	function extender_objeto_js()
	{
		$mensaje_fecha_invalida = guarani_mensajes::get('800_control_fechas', array(' Hasta', 'menor o igual', ' Desde'));
		
		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__anio_academico__procesar = function(es_inicial)
		{
			if (! es_inicial) {
				var anio = this.ef('anio_academico').get_estado();
				var fecha_i = '01/04/' + anio;
				var fecha_f = '31/03/' + (anio + 1);
				this.ef('fecha_inicio').cambiar_valor(fecha_i);
				this.ef('fecha_fin').cambiar_valor(fecha_f);
			}
		}
		
		
		//---- Validacion general ----------------------------------

		{$this->objeto_js}.evt__validar_datos = function()
		{
			var ok = true;
			
			if (this.ef('fecha_fin').fecha() < this.ef('fecha_inicio').fecha()) {
			
				notificacion.agregar('$mensaje_fecha_invalida');
				ok = false;
			}
			return ok;
		}

		";
	}

}
?>