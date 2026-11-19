<?php
class pant_actividades extends guarani_pers_ei_pantalla
{
	
	function generar_layout()
	{
		$this->controlador()->ev_info_origen()->generar_html();
		$this->controlador()->ev_info_filtros()->generar_html();
		echo '<hr/>';
		echo '<table width="100%">';
			echo '<tbody>';
				echo '<tr valign="top">';
					echo '<td width="80%">';
						echo '<fieldset><legend>Actividades cursadas en:</legend>';
							$this->dep('cuadro_actividades')->generar_html();
						echo '</fieldset>';
					echo '</td>';
					echo '<td width="20%" align="center">';
						echo '<fieldset><legend>Año según Plan</legend>';
							$this->dep('form_anios_plan')->generar_html();
						echo '</fieldset>';
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
	}

}
?>