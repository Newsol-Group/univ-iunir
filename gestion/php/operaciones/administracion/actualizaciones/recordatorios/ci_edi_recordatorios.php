<?php
class ci_edi_recordatorios extends ci_edicion_simple
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		if ($this->cn()->controlador->entidad_es_nueva()) {
			parent::extender_objeto_js();		
	        $id_form = $this->dependencia('formulario')->get_id_objeto_js();               	
			$mensaje_fecha_hasta = guarani_mensajes::get('800_control_fechas', array('Desde', 'mayor', 'Hasta.'));			
			echo "
			//---- Procesamiento de EFs --------------------------------
			
			{$id_form}.evt__fecha_desde__procesar = function(es_inicial)
			{
				if (! es_inicial) {
					var fecha_desde = this.ef('fecha_desde').get_estado();
					this.ef('fecha_hasta').cambiar_valor(fecha_desde);
				}
			}
	
			{$id_form}.evt__fecha_hasta__validar = function()
			{
				var ok = true;
				if (this.ef('fecha_desde').tiene_estado() && this.ef('fecha_hasta').tiene_estado()) {
				
					var fecha_desde = this.ef('fecha_desde').fecha();
					var fecha_hasta = this.ef('fecha_hasta').fecha();
					
					if (fecha_desde > fecha_hasta) {
						this.ef('fecha_hasta').set_error('$mensaje_fecha_hasta');
						ok = false;		
					}
				}
				return ok;
			}	
					
			";
		}
	}
}

?>