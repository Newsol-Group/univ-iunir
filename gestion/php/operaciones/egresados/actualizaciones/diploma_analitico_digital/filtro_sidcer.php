<?php
class filtro_sidcer extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
        echo "<table>";
        echo "<tr>";
        echo "<td>";
            $this->generar_html_ef('solicitud');
        echo "</td>";
        echo "<td>";
            $this->generar_boton('buscar');
        echo "</td>";
        echo "</tr>";
        echo "</table>";
	}

}
?>