<?php
class form_asignacion extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" align="left" width="60%">';
		echo '<tbody>';
			echo '<tr>';
				echo '<td>';
				echo '<fieldset title="Fecha y hora de la mesa (*)"><legend>Fecha y hora de la mesa (*)</legend>';
					echo '<table cellspacing="1" cellpadding="1" border="0">';
						echo'<tbody>';
							echo '<tr>';
								echo '<td>';
								$this->generar_html_ef('fecha');
								echo '</td>';
							echo '</tr>';
						echo '</tbody>';
					echo '</table>';
					echo '<hr width=950px></hr>';
					echo '<b>(*)</b> Se indicará el criterio a utilizar. En el caso de cátedra y cctividad, de no estar definido que día crear la mesa, se utilizará la establecida por default.';
				echo '</fieldset>';
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td>';
				echo '<fieldset title="Docentes"><legend>Docentes</legend>';
					echo '<table cellspacing="1" cellpadding="1" border="0">';
						echo'<tbody>';
							echo '<tr>';
								echo '<td>';
								$this->generar_html_ef('docentes');
								echo '</td>';
							echo '</tr>';
						echo '</tbody>';
					echo '</table>';
				echo '</fieldset>';
				echo '</td>';
			echo '</tr>';
		echo '</tbody>';
		echo '</table>';
	}

}

?>