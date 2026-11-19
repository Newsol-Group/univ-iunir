<?php
class form_ubicacion extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo "<table>";
		echo "	<tr>";
		echo "		<td>";
		echo "			<fieldset>";
		echo "				<legend>Ubicaciones</legend>";
							$this->generar_html_ef('ubicacion');
		echo "			</fieldset>";
		echo "		</td>";
		echo "	</tr>";
		echo "</table>";
	}

}
?>