<?php 
/**
 *
 *
 * @package 	Propuestas Formativas
 * @subpackage 	-
 * 
 */
class form_propuesta extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		$this->generar_html_ef('codigo');
		$this->generar_html_ef('nombre');
		$this->generar_html_ef('nombre_abreviado');
		$this->generar_html_ef('propuesta_tipo');
		$this->generar_html_ef('publica');
		
		if (!$this->controlador()->cn()->entidad_es_nueva()) {
			echo '<hr class="ci-pant-sep-ei"/>';
			$this->generar_html_ef('estado');
		}
		
		echo '<hr class="ci-pant-sep-ei"/>';				
		$this->generar_html_ef('fecha_alta');
		$this->generar_html_ef('documento_alta');
		
		if (!$this->controlador()->cn()->entidad_es_nueva()) {
			echo '<div id="sep_baja"> <hr class="ci-pant-sep-ei"/> </div>';
			$this->generar_html_ef('fecha_baja');
			$this->generar_html_ef('documento_baja');
		}

		echo '<hr class="ci-pant-sep-ei"/>';
		$this->generar_html_ef('campo_disciplinar');
		$this->generar_html_ef('escala_cumplimiento');
		
		echo '<hr class="ci-pant-sep-ei"/>';
		$this->generar_html_ef('areas_tematicas');

		echo '<hr class="ci-pant-sep-ei"/>';
		$this->generar_html_ef('ubicaciones');
	}
	
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function extender_objeto_js()
	{
		echo "
			//---- Procesamiento de EFs --------------------------------
			
			{$this->objeto_js}.evt__estado__procesar = function(es_inicial)
			{
				var estado = this.ef('estado').get_estado();
				var div = document.getElementById('sep_baja');
				
				if (estado != 'B') {
					this.ef('fecha_baja').ocultar(false);
					this.ef('documento_baja').ocultar(false);
					div.style.display='none';
				} else {
					this.ef('fecha_baja').mostrar(true, false);
					this.ef('documento_baja').mostrar(true, false);
					div.style.display='';
				}

			}
			";
	}

}
?>