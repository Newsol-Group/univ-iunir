<?php
/**
 * 
 *
 * @package 	Matriculas
 * @subpackage 	Reportes
 *
 */
class form_opciones_hist_acad_nsg extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<div style="float:left;">';
		$this->generar_html_ef('planes');
		echo '</div>';

	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{		

		echo "
		//---- Procesamiento de EFs --------------------------------
		{$this->objeto_js}.evt__planes__procesar = function(es_inicial)
		{
			if (!es_inicial) {
				var estado = this.ef('planes').get_estado();
				var parametros = [];
				parametros['opcion'] = 'planes';
				parametros['valor'] = estado;				
				this.ef('planes').set_solo_lectura(true);
				{$this->objeto_js}.controlador.ajax('set_opciones', parametros, this, atender_respuesta);

				return false;
			}
		}

		
		function atender_respuesta(response)
		{
			this.submit();
			this.ef('planes').set_solo_lectura(false);

		}
		";
	}

}

?>