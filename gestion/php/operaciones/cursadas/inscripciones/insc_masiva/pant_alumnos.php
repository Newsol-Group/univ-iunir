<?php
class pant_alumnos extends guarani_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('asignados')->generar_html();
		echo '<hr />';
		echo '<h3>Alumnos disponibles</h3>';
		echo "<p style='font-style: italic;'>(de la propuesta y plan / versión seleccionados anteriormente)</p>";
		$this->dep('filtro')->generar_html();
		$this->dep('disponibles')->generar_botones();
		$this->dep('disponibles')->evento('agregar')->set_en_botonera(false);
		$this->dep('disponibles')->generar_html();
	}
}
?>