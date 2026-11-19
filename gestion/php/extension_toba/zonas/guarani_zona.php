<?php
class guarani_zona extends toba_zona
{
	function generar_html_barra_superior()
	{
		echo "<div class='zona-barra-sup' id='zona_{$this->id}'>";
		echo "<div class='zona-items'>";
		$this->generar_html_barra_vinculos();
		echo "</div>";
		$this->generar_html_barra_especifico();
		echo "</div>\n";
	}
}
?>