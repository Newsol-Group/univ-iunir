<?php
class pant_edicion extends toba_ei_pantalla
{
	function generar_layout()
	{
		
		echo "<h3>IMPORTANTE:</h3>";
		echo "<ul>";
		if (!$this->controlador()->controlador()->es_edicion()) {
			echo "<li>A partir de la configuración aquí ingresada se generarán turnos para la presentación de documentación de preinscripción en ventanilla.</li>";
		}
		echo "<li>Una vez generados los turnos, la configuración no podrá ser modificada. En caso de algún error de carga, se deberá eliminar la configuración (en consecuencia se eliminarán los turnos) e ingresarla nuevamente.</li>";
		echo "<li>Una configuración puede ser eliminada siempre que no haya turnos tomados.</li>";
		echo "<li>Los turnos podrán ser tomados por aspirantes a propuestas de las Responsables Académicas alcanzadas por esta configuración.</li>";
		echo "<li>Los turnos estarán disponibles para los aspirantes que se preinscriban tanto por el módulo de Preinscripción como de Autogestión.</li>";
		echo "</ul>";
		
		echo "<table>";
		echo "	<tr>";
		echo "		<td>";
						$this->dep('formulario')->generar_html();
		echo "		</td>";
		echo "		<td style='padding-top: 6px;'>";
		echo "			<fieldset>";
		echo "				<legend>Intervalos de atención</legend>";
							$this->dep('intervalos')->generar_html();
		echo "			</fieldset>";
		echo "		</td>";
        echo "		<td>";
                            $this->dep('form_modalidad')->generar_html();
        echo "		</td>";
		echo "	</tr>";
		echo "	<tr>";
		
        echo "		<td>";
						$this->dep('form_ras')->generar_html();
		echo "		</td>";
		echo "		<td>";
						$this->dep('form_ubicacion')->generar_html();
		echo "		</td>";
		echo "	</tr>";
		echo "</table>";
		
	}

}
?>