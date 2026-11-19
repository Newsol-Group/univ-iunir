<?php
class pant_tomos extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		echo "<div style='margin-left: auto; margin-right: auto; width: 400px;'>";
		$this->dep('cuadro_tomos')->generar_html();
		echo "</div>";
	}

}
?>