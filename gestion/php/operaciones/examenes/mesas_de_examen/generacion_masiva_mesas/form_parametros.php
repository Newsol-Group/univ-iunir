<?php
class form_parametros extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="100%">';
		echo '<tbody>';
			echo '<tr>';
				echo '<td width="40%">';
				echo '<fieldset style="height: 85px" title="Cantidad de mesas por cátedra"><legend>Cantidad de mesas por cátedra</legend>';
						echo '<table cellspacing="1" cellpadding="1" border="0">';
							echo'<tbody>';
								echo '<tr>';
									echo '<td colspan=3>';
									$this->generar_html_ef('mesa_por_catedra');
									$this->generar_html_ef('crear_mesas', '300px');
									echo '</td>';
								echo '</tr>';
							echo '</tbody>';
						echo '</table>';
					echo '</fieldset>';
				echo '</td>';
				echo '<td width="60%">';
				echo '<fieldset style="height: 85px" title="Criterio para la generación"><legend>Criterio para la generación</legend>';
					echo '<table cellspacing="1" cellpadding="1" border="0">';
						echo'<tbody>';
							echo '<tr>';
								echo '<td colspan=3>';
								$this->generar_html_ef('criterio_generacion');
								echo '</td>';
							echo '</tr>';
						echo '</tbody>';
					echo '</table>';
				echo '</fieldset>';
				echo '</td>';
			echo '</tr>';
			echo '<tr><td>';
			$this->generar_html_ef('versiones_no_vigentes', '410px');
			echo '</td></tr>';
		echo '</tbody>';
		echo '</table>';
	}

}
?>