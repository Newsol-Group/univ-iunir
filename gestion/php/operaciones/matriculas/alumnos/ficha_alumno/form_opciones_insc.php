<?php
/**
 * 
 *
 * @package 	Matriculas
 * @subpackage 	Reportes
 *
 */
class form_opciones_insc extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<div style="float:left;">';
		$this->generar_html_ef('inscripciones');
		echo '</div>';
		echo '<div style="float:left;">';
		$this->generar_html_ef('vigencia');
		echo '</div>';
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{		

		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__vigencia__procesar = function(es_inicial)
		{
			if (!es_inicial) {
				var estado = this.ef('vigencia').get_estado();
				var parametros = [];
				parametros['opcion'] = 'vigencia';
				parametros['valor'] = estado;
				
				this.ef('inscripciones').set_solo_lectura(true);
				this.ef('vigencia').set_solo_lectura(true);
				{$this->objeto_js}.controlador.ajax('set_opciones', parametros, this, atender_respuesta);
				return false;
			}
		}
		
		{$this->objeto_js}.evt__inscripciones__procesar = function(es_inicial)
		{
			if (!es_inicial) {
				var estado = this.ef('inscripciones').get_estado();
				var parametros = [];
				parametros['opcion'] = 'inscripciones';
				parametros['valor'] = estado;
				
				this.ef('inscripciones').set_solo_lectura(true);
				this.ef('vigencia').set_solo_lectura(true);
				{$this->objeto_js}.controlador.ajax('set_opciones', parametros, this, atender_respuesta);
				return false;
			}
		}
		
		function atender_respuesta(response)
		{
			this.submit();
			
			this.ef('inscripciones').set_solo_lectura(true);
			this.ef('vigencia').set_solo_lectura(true);
		}
		";
	}

}

?>