<?php
/**
 * 
 *
 * @package 	Matriculas
 * @subpackage 	Reportes
 *
 */
class form_opciones_equiv_parciales extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<div style="float:left;">';
		$this->generar_html_ef('propuesta');
		echo '</div>';
		echo '<div style="float:left;">';
		$this->generar_html_ef('actividad');
		echo '</div>';
		echo '<div style="float:left;">';
		$this->generar_html_ef('resultado');
		echo '</div>';
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------

		{$this->objeto_js}.evt__propuesta__procesar = function(es_inicial)
		{
			if (!es_inicial) {
				var estado = this.ef('propuesta').get_estado();
				var parametros = [];
				parametros['opcion'] = 'propuesta';
				parametros['valor'] = estado;
				
				this.ef('propuesta').set_solo_lectura(true);
				this.ef('resultado').set_solo_lectura(true);
				{$this->objeto_js}.controlador.ajax('set_opciones', parametros, this, atender_respuesta);
				return false;
			}
		}

		{$this->objeto_js}.evt__resultado__procesar = function(es_inicial)
		{
			if (!es_inicial) {
				var estado = this.ef('resultado').get_estado();
				var parametros = [];
				parametros['opcion'] = 'resultado';
				parametros['valor'] = estado;

				this.ef('propuesta').set_solo_lectura(true);
				this.ef('resultado').set_solo_lectura(true);
				{$this->objeto_js}.controlador.ajax('set_opciones', parametros, this, atender_respuesta);
				return false;
			}
		}

		function atender_respuesta(response)
		{
			this.submit();
			this.ef('propuesta').set_solo_lectura(false);
			this.ef('resultado').set_solo_lectura(false);
		}
		";
	}

}
?>