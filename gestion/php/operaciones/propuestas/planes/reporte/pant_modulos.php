<?php 
class pant_modulos extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		echo "<div style='float:left; width: 30%; padding: 5px; border: 1px solid black'>";
		$this->dep('arbol_modulos')->generar_html();
		echo '</div>';
		if ($this->existe_dependencia('formulario_modulos')) {
			echo '<div style="float:right;padding-left: 5px; width: 65%">';
			$this->dep('formulario_modulos')->generar_html();
			echo "</div>";
		}
		if ($this->existe_dependencia('cuadro_modulos')) {
			echo "<div style='clear:both'>";
			$this->dep('cuadro_modulos')->generar_html();
			echo "</div>";
		}
	}
}

?>