<?php
/**
 *
 * 
 *
 * @package 	Matrícula
 * @subpackage 	Inscripciones
 * 
 */
class form_cambio_plan extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
			echo '<tbody>';
				echo '<tr>';
					echo '<td colspan=2>';
						echo "<h3>Datos Generales</h3>";
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
						$this->generar_html_ef('tipo_cambio');
					echo '</td>';
					echo '<td>';
						$this->generar_html_ef('documento');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=2>';
					$this->generar_html_ef('observaciones');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=2>';
						echo "<h3>Datos del Cambio de Plan</h3>";
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
						$this->generar_html_ef('motivo_plan');
					echo '</td>';
					echo '<td>';
						$this->generar_html_ef('destino', '80px');
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

		{$this->objeto_js}.evt__destino__procesar = function(es_inicial)
		{
			if (!es_inicial) {
				if (this.ef('destino').tiene_estado() && this.ef('motivo_plan').tiene_estado())  {
					this.submit();
				}
			}
		}

		{$this->objeto_js}.evt__motivo_plan__procesar = function(es_inicial)
		{
			if (!es_inicial) {
				if (this.ef('destino').tiene_estado() && this.ef('motivo_plan').tiene_estado())  {
					this.submit();
				}
			}
		}
		";
	}
}
?>