<?php
class form_acercade extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo "<table border=0>";
	    echo "<tr>";
	    echo "<td>";
	    echo "<img SRC='img/logo_medio.png' WIDTH=341 HEIGHT=74 BORDER=0 ALT='SIU-Guarani 3'>";
	    echo "</td>";
	    echo "</tr>";
	    echo "<tr>";
	    echo "<td>";
	    $this->generar_html_ef('nombre');
		$this->generar_html_ef('version_app');
		$this->generar_html_ef('version_bd');
		$this->generar_html_ef('revision_svn');
		echo "</td>";
	    echo "</tr>";
	    echo "<tr>";
	    echo "<td>";
		echo "<strong><a href='https://administracion.siu.edu.ar/?ai=administracion||37000001&tcm=previsualizacion&tm=1&cod_proyecto=448' style='text-decoration: none; padding: 0 10px 5px 8px' target='_blank'>Cambios por Versión</a></strong>";			
	    echo "</td>";
	    echo "</tr>";
	    echo "</table>";
	}

}
?>