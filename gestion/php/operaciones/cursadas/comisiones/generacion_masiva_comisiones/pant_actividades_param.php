<?php
class pant_actividades_param extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info_destino()->generar_html();
		$this->controlador()->ev_info()->generar_html();
		
		echo '<hr/>';
		echo '<table width="100%">';
			echo '<tbody>';
				
				echo '<tr valign="top">';
					echo '<td width="80%">';
						echo '<fieldset style="height: 290px"><legend>Actividades cursadas en:</legend>';
							$this->dep('cuadro_actividades_param')->generar_html();
						echo '</fieldset>';
					echo '</td>';
					echo '<td width="20%" align="center">';
						echo '<fieldset style="height: 290px"><legend>Año según Plan</legend>';
							$this->dep('form_anios_plan')->generar_html();
						echo '</fieldset>';
					echo '</td>';
				echo '</tr>';
				
				echo '<tr>';
					echo '<td colspan="2">';
						echo '<fieldset><legend>Cátedras</legend>';
							$this->dep('form_comisiones')->generar_html();
						echo '</fieldset>';
					echo '</td>';
				echo '</tr>';
				
				echo '<tr>';
					echo '<td colspan="2">';
						echo '<fieldset><legend>Inscripción</legend>';
							$this->dep('form_inscripcion')->generar_html();
						echo '</fieldset>';
					echo '</td>';
				echo '</tr>';
		
			echo '</tbody>';
		echo '</table>';
	}

}
?>