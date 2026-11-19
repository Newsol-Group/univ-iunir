<?php
class pantalla_plan_elementos extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		echo "<table style='BORDER-COLLAPSE: collapse;'>";
		echo "<tr>";

		echo "<td style='padding: 0px; empty-cells: hide; BORDER-COLLAPSE: collapse; text-align: left; vertical-align: top'>";
		echo "<div style='text-align: left; vertical-align: top; width:500px;'>";
		$this->dep('arbol')->generar_html();
		echo "</div>";
		echo '</td>';

		if ($this->existe_dependencia('propiedades')) {
			echo "<td style='background-color: #e7e7e7; text-align: left;vertical-align: top; '>";
			echo "<div >";
			$this->dep('propiedades')->generar_html();
			echo "</div>";
			echo '</td>';
		}

		if ($this->existe_dependencia('disponibles')) {
			echo "<td style=' background-color: #e7e7e7;  padding: 0px; vertical-align: top; empty-cells: hide; BORDER-COLLAPSE: collapse; '>";
			echo "<div  style='padding: 5px; text-align: left; vertical-align: top;'>";
			$this->dep('disponibles')->generar_html();
			echo "</div>";
			echo '</td>';
		}

		echo "</tr>";
		echo "</table>";
	}

}
?>