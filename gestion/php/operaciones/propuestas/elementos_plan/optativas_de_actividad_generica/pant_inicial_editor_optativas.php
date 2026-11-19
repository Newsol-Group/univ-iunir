<?php
/**
 *
 *
 * @package Propuestas Formativas
 * @subpackage Actividades
 */
class pant_inicial_editor_optativas extends guarani_pers_ei_pantalla 
{
	/**
	 * Se extiende el layout de la pantalla de edición de comisiones para poder generar el árbol de selección a izquierda 
	 * y las pantallas de edición de una comisión o de una subcomisión según corresponda a la derecha del árbol.
	 */
	function generar_layout()
	{
		$this->controlador()->ev_info_generica_optativa()->generar_html();
		echo '<hr />';
		if ($this->existe_dependencia('form_puntaje')) {
			$this->controlador()->dep('form_puntaje')->generar_html();
		}
		
		$generar_td = true;

		echo "<table border='0' style='BORDER-COLLAPSE: collapse;'><tr>";
			echo '<tr>';
				if ($this->existe_dependencia('seleccion_plan_version')) {
					echo "<td style='padding: 0px; empty-cells: hide; BORDER-COLLAPSE: colapse; text-align: left; vertical-align: top; overflow: hidden;'>";
					echo "<div style='text-align: left; vertical-align: top; width:420px; overflow: hidden;'>";
					$this->dependencia('seleccion_plan_version')->generar_html();
					echo "</div>";
					echo "</td>";
				}
				if ($this->existe_dependencia('editor_optativa')) {
					$generar_td = false;
					echo "<td style='padding: 0px; text-align: left;vertical-align: top; empty-cells: hide; BORDER-COLLAPSE: collapse;'>";
					echo "<div  style='padding: 5px; text-align: left; vertical-align: top;'>";
					$this->dependencia('editor_optativa')->generar_html();
					echo "</div>";
					echo '</td>';
				}
				if ($generar_td) {
					echo "<td width='600px'>";
					echo "</td>";
				}
			echo '</tr>';
		echo '</table>';
	}
}
?>