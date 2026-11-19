<?php
class pant_datos_principales extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('elemento')->generar_html();
		echo '<hr />';
		echo '<div class="ei-barra-subtit">';
		echo "<span>Forma de Cumplimiento</span>";
		echo "</div>";		
		$this->dep('regla')->generar_html();
		echo '<hr />';
		$this->dep('revision')->generar_html();
		echo '<hr />';
		$this->dep('ua')->generar_html();
	}

}
?>