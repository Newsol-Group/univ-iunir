<?php
/**
 *
 *
 * @package Cursadas
 * @subpackage Comisiones
 */
class form_filtro_comisiones_actividades extends guarani_pers_ei_formulario  
{
	function generar_layout()
	{
		echo '<fieldset><legend>Comisiones de:</legend>';
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
			echo'<tbody>';
				echo '<tr>';
					echo '<td>';
						$this->generar_html_ef('anio_academico');
					echo '</td>';
					echo '<td>';
						$this->generar_html_ef('periodo_lectivo');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=2>';
						$this->generar_html_ef('ubicacion');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
		echo '</fieldset>';
		echo '<hr/>';
		echo '<fieldset><legend>Actividades de:</legend>';
			$this->generar_html_ef('responsable_academica');
			$this->generar_html_ef('propuesta');
			$this->generar_html_ef('plan');
			$this->generar_html_ef('plan_version');
			$this->generar_html_ef('anio_de_cursada');
			$this->generar_html_ef('actividad');
		echo '</fieldset>';
	}
	
	function extender_objeto_js()
	{
		echo "
			
			{$this->objeto_js}.hay_cambios = function(ef_actual)
			{
				return false;
			}
			
		";
	}
}

?>