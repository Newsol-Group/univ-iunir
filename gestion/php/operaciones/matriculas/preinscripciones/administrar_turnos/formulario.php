<?php
class formulario extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo "<table>";
		echo "	<tr>";
		echo "		<td>";
		echo "			<fieldset>";
		echo "				<legend>Configuración</legend>";
								$this->generar_html_ef('turno_config');
								$this->generar_html_ef('fecha');
								$this->generar_html_ef('hora');
								$this->generar_html_ef('operador');
                                $this->generar_html_ef('descripcion');
		echo "			</fieldset>";
		echo "		</td>";
		echo "	</tr>";
		echo "</table>";
	}
}