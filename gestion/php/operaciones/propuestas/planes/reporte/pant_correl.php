<?php
class pant_correl extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		echo "<div style='float:left; width: 50%; padding: 5px; border: 1px solid black; overflow: auto'>";
		$this->dep('arbol_plan')->generar_html();
		echo '</div>';
		echo '<div style="float:left;padding-left: 5px; width: 47%">';
		$this->dep('formulario_correl')->generar_html();
		echo '<hr/>';
		$this->dep('arbol_requisitos')->generar_html();
		echo "</div>";
		$this->dep('esquema_correl')->generar_html();
	}
}
?>