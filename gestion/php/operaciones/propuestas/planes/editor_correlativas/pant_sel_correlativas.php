<?php
class pant_sel_correlativas extends guarani_pers_ei_pantalla
{
	
	function generar_layout()
	{
		echo "<table align='center' width='60%'>";
		echo "<tr>";
		echo "<td style='align: left;vertical-align: top; padding: 10px; width:100px'>";
		$this->dep('arbol_plan')->generar_html();	
		echo '</td>';
		echo "</tr>";
		echo "</table>";
	
	}
	
}

?>