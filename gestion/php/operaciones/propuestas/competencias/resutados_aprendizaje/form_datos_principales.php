<?php
class form_datos_principales extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$tipo_competencias = comunes::resultado_tipo_competencias;
		
		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__resultado_tipo__procesar = function(es_inicial)
		{
			var resultado_tipo = this.ef('resultado_tipo').get_estado();
			
			if (resultado_tipo == '$tipo_competencias') {
				this.controlador.activar_tab('componentes');
			} else {
				this.controlador.desactivar_tab('componentes');
			}
		}
		";
	}


}
?>