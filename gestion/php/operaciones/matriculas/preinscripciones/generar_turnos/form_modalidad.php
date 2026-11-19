<?php

class form_modalidad extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo "<table>";
		echo "    <tr>";
		echo "        <td>";
		echo "            <fieldset>";
		echo "                <legend>Modalidades</legend>";
								$this->generar_html_ef('modalidad');
		echo "            </fieldset>";
		echo "        </td>";
		echo "    </tr>";
		echo "</table>";
	}
}
