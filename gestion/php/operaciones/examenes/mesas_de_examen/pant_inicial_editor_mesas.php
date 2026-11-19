<?php
/**
 *
 *
 * @package		Examenes
 * @subpackage	Mesas de Examen
 * 
 */
class pant_inicial_editor_mesas extends guarani_pers_ei_pantalla
{
	/**
	 * Se extiende el layout de la pantalla de edición de Mesas para poder generar el árbol de selección a izquierda 
	 * y las pantallas de edición de una Mesa o de un Llamado según corresponda a la derecha del árbol.
	 */
	function generar_layout()
	{
		$generar_td = true;

		echo "<table border='0' style='BORDER-COLLAPSE: collapse;'><tr>";
		echo '<tr>';
		if ($this->existe_dependencia('seleccion_mesa_llamado')) {
			echo "<td style='padding: 0px; empty-cells: hide; BORDER-COLLAPSE: colapse; text-align: left; vertical-align: top; overflow: hidden;'>";
			echo "<div style='text-align: left; vertical-align: top; width:320px; overflow: hidden;'>";
			$this->dependencia('seleccion_mesa_llamado')->generar_html();
			echo "</div>";
			echo "</td>";
		}
		if ($this->existe_dependencia('editor_mesa')) {
			$generar_td = false;
			echo "<td style='padding: 0px; text-align: left;vertical-align: top; empty-cells: hide; BORDER-COLLAPSE: collapse;'>";
			echo "<div  style='padding: 5px; text-align: left; vertical-align: top;'>";
			$this->dependencia('editor_mesa')->generar_html();
			echo "</div>";
			echo '</td>';
		}
		if ($this->existe_dependencia('editor_llamado')) {
			$generar_td = false;
			echo "<td style='padding: 0px; text-align: left;vertical-align: top; empty-cells: hide; BORDER-COLLAPSE: collapse;'>";
			echo "<div  style='padding: 5px; text-align: left; vertical-align: top;'>";
			$this->dependencia('editor_llamado')->generar_html();
			echo "</div>";
			echo '</td>';
		}
		if ($generar_td) {
			echo "<td width='500px'>";
			echo "</td>";
		}
		echo '</tr>';
		echo '</table>';
	}

}
?>