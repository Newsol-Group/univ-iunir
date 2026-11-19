<?php
class pant_seleccion_certificado extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		echo "<div align='center'>";
		echo "<table width='60%'><tr><td>";
		$this->dep('plan_certificados')->generar_html();
		echo '<hr />';
		$this->dep('filtro_certificados')->generar_html();
		$this->dep('certificados')->generar_html();
		echo "</td></tr></table>";
		echo "</div>";
	}

}
?>