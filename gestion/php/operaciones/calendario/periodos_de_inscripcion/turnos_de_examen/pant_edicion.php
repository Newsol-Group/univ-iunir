<?php
class pant_edicion extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$generar_td = true;
		echo "<table border='0' style='BORDER-COLLAPSE: collapse;'><tr>";
		echo "<tr>";
		if ($this->existe_dependencia('seleccion')) {
			echo "<td style='padding: 0px; empty-cells: hide; BORDER-COLLAPSE: collapse; text-align: left; vertical-align: top; overflow: hidden;'>";
			echo "<div style='text-align: left; vertical-align: top; width:320px; overflow: hidden;'>";
			$this->dep('seleccion')->generar_html();
			echo "</div>";
			echo "</td>";
		}
		if ($this->existe_dependencia('edicion_turno')) {
			$generar_td = false;
			echo "<td style='padding: 0px; text-align: left;vertical-align: top; empty-cells: hide; BORDER-COLLAPSE: collapse;'>";
			echo "<div  style='padding: 5px; padding-top: 0px; text-align: left; vertical-align: top;'>";
			$this->dep('edicion_turno')->generar_html();
			echo "</div>";
			echo '</td>';
		}
		if ($this->existe_dependencia('edicion_llamado')) {
			$generar_td = false;
			echo "<td style='padding: 0px; text-align: left;vertical-align: top; empty-cells: hide; BORDER-COLLAPSE: collapse;'>";
			echo "<div  style='padding: 5px; padding-top: 0px; text-align: left; vertical-align: top;'>";
			$this->controlador()->ev_info()->set_titulo('Datos del Llamado');
			$this->controlador()->ev_info()->generar_html();
			$this->dep('edicion_llamado')->generar_html();
			echo "</div>";
			echo '</td>';
		}
		if ($this->existe_dependencia('edicion_periodo')) {
			$generar_td = false;
			echo "<td style='padding: 0px; text-align: left;vertical-align: top; empty-cells: hide; BORDER-COLLAPSE: collapse;'>";
			echo "<div  style='padding: 5px; padding-top: 0px; text-align: left; vertical-align: top;'>";
			$this->controlador()->ev_info()->set_titulo('Datos del Período de Inscripción');
			$this->controlador()->ev_info()->generar_html();
			$this->dep('edicion_periodo')->generar_html();
			echo "</div>";
			echo '</td>';
		}
		if ($generar_td) {
			echo "<td width='730px'>";
			echo "</td>";
		}
		echo '</tr>';
		echo '</table>';
	}

}
?>