<?php
class pant_edicion extends toba_ei_pantalla
{
	function generar_layout()
	{
		
		echo "<h3>IMPORTANTE:</h3>";
		echo "<ul>";
		if (!$this->controlador()->controlador()->es_edicion()) {
			echo "<li>A partir de la configuración aquí ingresada se editará el turno para la presentación de documentación de preinscripción en ventanilla.</li>";
		}
		echo "<li>Una configuración puede ser eliminada siempre que no haya turnos tomados.</li>";
		echo "<li>Los turnos podrán ser tomados por aspirantes a propuestas de las Responsables Académicas alcanzadas por esta configuración.</li>";
		echo "<li>Los turnos estarán disponibles para los aspirantes que se preinscriban tanto por el módulo de Preinscripción como de Autogestión.</li>";
		echo "</ul>";
		
		echo "<table>";
		echo "	<tr>";
		echo "		<td>";
						$this->dep('formulario')->generar_html();
		echo "		</td>";
		echo "</table>";
		
	}

}