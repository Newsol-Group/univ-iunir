<?php
/**
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Generar Matriz de Equivalencia
 */
class pant_inicial_editor_grupo extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		echo "<table border='0' style='BORDER-COLLAPSE: collapse;'>";
			echo '<tr>';
			echo '	<td>';
				$this->dep('form_datos_grupo')->generar_html();
			echo '	</td>';
			echo '</tr>';
		echo '</table>';
		echo '<hr />';
		echo "<table border='0' style='BORDER-COLLAPSE: collapse;'>";
			echo '<tr>';
				echo '<td>';
					$this->dep('actividades_origen')->generar_html();
				echo '</td>';
				echo '<td>';
					$this->dep('actividades_destino')->generar_html();
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<td colspan=2>';
				echo '<hr />';
				$this->dep('seleccion_actividades')->generar_html();
				echo '</td>';
			echo '</tr>';
		echo '</table>';
	}
	
}

?>