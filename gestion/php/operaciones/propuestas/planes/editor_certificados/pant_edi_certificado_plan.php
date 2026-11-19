<?php 
class pant_edi_certificado_plan extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		if ($this->controlador()->get_modo() == 'estructura') {
			$ancho_disponibles = '450px';
			$ancho_arbol = '650px';
		} else {
			$ancho_disponibles = '550px';
			$ancho_arbol = '500px';
		}
		
		echo "<div align='center' style='text-align: left; vertical-align: top;'>";
		echo "<table style='border-collapse: collapse; width: 100%;' border='0' align='center'>";
		echo "<tr align='center'>";
		echo "<td colspan=3>";
		$this->dep('datos_certificado')->generar_html();
		echo "</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td  style='BORDER-COLLAPSE: colapse; text-align: left; vertical-align: top; overflow: hidden;'>";
		echo "<div style='text-align: left; vertical-align: top; width:$ancho_arbol; overflow: hidden;'>";
		$this->dep('arbol_certificado')->generar_html();
		echo "</div>";
		echo '</td>';
		if ($this->existe_dependencia('disponibles')) {
			echo "<td style=' background-color: #e7e7e7;  padding: 0px; vertical-align: top; empty-cells: hide; BORDER-COLLAPSE: collapse; '>";
			echo "<div style='padding: 5px; text-align: left; vertical-align: top; width:$ancho_disponibles;'>";
			$this->dep('disponibles')->generar_html();
			echo "</div>";
			echo '</td>';
		} 
		if ($this->existe_dependencia('propiedades')) {
			echo "<td style='background-color: #e7e7e7; text-align: left;vertical-align: top; '>";
			echo "<div >";
			$this->dep('propiedades')->generar_html();
			echo "</div>";
			echo '</td>';
		}
		echo "</tr>";
		echo "</table>";
	}
}
?>