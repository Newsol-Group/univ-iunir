<?php
/**
 *
 * 
 * @package 	Reconocimiento de Actividades
 * @subpackage 	Aprobación por Resolución
 * 
 */
class form_datos_generales_actividad_doc extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('nombre', '140px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('carga_horaria', '105px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('creditos', '75px');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
					$this->generar_html_ef('tipo_actividad', '140px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('fecha_desde', '105px');
					echo '</td>';
					echo '<td>';
					$this->generar_html_ef('fecha_hasta', '75px');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		
		//---- Procesamiento de EFs --------------------------------

		{$this->objeto_js}.evt__tipo_actividad__validar = function(es_inicial)
		{
			this.submit();
			return true;
		}
		";
	}
}
?>