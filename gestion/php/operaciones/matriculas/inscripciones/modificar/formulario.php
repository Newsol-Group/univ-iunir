<?php
class formulario extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__dato_estudio_anterior__procesar = function(es_inicial)
		{
			if (this.ef('dato_estudio_anterior').tiene_estado()) {
				var params = [];
				params['dato_estudio_anterior'] = this.ef('dato_estudio_anterior').get_estado();
				this.controlador.ajax('datos_estudio_anterior', params, this, this.respuesta_datos_estudio_anterior);
			}
			this.ef('institucion_anterior').desactivar();
		}

        {$this->objeto_js}.respuesta_datos_estudio_anterior = function(respuesta)
        {
            if (typeof respuesta == 'object') {
				this.ef('institucion_anterior').set_estado(respuesta['institucion_anterior']);
			} else {
				this.ef('institucion_anterior').set_estado('');
			}
		}
		";
	}
}
?>