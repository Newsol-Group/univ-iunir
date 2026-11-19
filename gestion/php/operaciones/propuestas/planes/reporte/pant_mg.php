<?php 
class pant_mg extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		echo "<div style='float:left; width: 30%; padding: 5px; border: 1px solid black'>";
		$this->dep('cuadro_sel_mg')->generar_html();
		echo '</div>';
		if($this->existe_dependencia('formulario_mg')) {
			echo '<div style="float:right;padding-left: 5px; width: 65%">';
			$this->dep('formulario_mg')->generar_html();
			echo "</div>";
		}
		if($this->existe_dependencia('cuadro_activ_mg')) {
			echo "<div style='clear:both'>";
			$this->dep('cuadro_activ_mg')->generar_html();
			echo "</div>";
		}		
	}
}

?>