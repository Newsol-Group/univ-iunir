<?php
class form_ras extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo "<table>";
		echo "	<tr>";
		echo "		<td>";
		echo "			<fieldset>";
		echo "				<legend>Responsables Académicas</legend>";
							$this->generar_html_ef('responsables_academicas');
		echo "			</fieldset>";
		echo "		</td>";
		echo "	</tr>";
		echo "</table>";
	}

}
?>