<?php
/**
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Generar Matriz de Equivalencia
 */
class pant_inicial_editor_matrices extends guarani_pers_ei_pantalla
{
	/**
	 * Se extiende el layout de la pantalla de edición de Matrices para poder generar el árbol de selección a izquierda 
	 * y las pantallas de edición de una Matriz o de un Grupo según corresponda a la derecha del árbol.
	 */
	function generar_layout()
	{
		echo '<br>';
		$this->controlador()->ev_info_prop_destino()->generar_html();
		echo '<br>';
		
		$generar_td = true;

		echo "<table border='0' style='BORDER-COLLAPSE: collapse;'><tr>";
			echo '<tr>';
				if ($this->existe_dependencia('seleccion_matriz_grupo')) {
					echo "<td style='padding: 0px; empty-cells: hide; BORDER-COLLAPSE: colapse; text-align: left; vertical-align: top; overflow: hidden;'>";
					echo "<div style='text-align: left; vertical-align: top; width:300px; overflow: hidden;'>";
					$this->dependencia('seleccion_matriz_grupo')->generar_html();
					echo "</div>";
					echo "</td>";
				}
				if ($this->existe_dependencia('editor_matriz')) {
					$generar_td = false;
					echo "<td style='padding: 0px; text-align: left;vertical-align: top; empty-cells: hide; BORDER-COLLAPSE: collapse;'>";
					echo "<div  style='padding: 5px; text-align: left; vertical-align: top;'>";
					$this->dependencia('editor_matriz')->generar_html();
					echo "</div>";
					echo '</td>';
				}
				if ($this->existe_dependencia('editor_grupo')) {
					$generar_td = false;
					echo "<td style='padding: 0px; text-align: left;vertical-align: top; empty-cells: hide; BORDER-COLLAPSE: collapse;'>";
					echo "<div  style='padding: 5px; text-align: left; vertical-align: top;'>";
					$this->dependencia('editor_grupo')->generar_html();
					echo "</div>";
					echo '</td>';
				}
				if ($this->existe_dependencia('editor_orden')) {
					$generar_td = false;
					echo "<td style='padding: 0px; text-align: left;vertical-align: top; empty-cells: hide; BORDER-COLLAPSE: collapse;' width:650px>";
					echo "<div  style='padding: 5px; text-align: left;  width:650px; vertical-align: top;'>";
					$this->dependencia('editor_orden')->generar_html();
					echo "</div>";
					echo '</td>';
				}
				if ($generar_td) {
					echo "<td width='650px'>";
					echo "</td>";
				}
			echo '</tr>';
		echo '</table>';
	}
}
?>