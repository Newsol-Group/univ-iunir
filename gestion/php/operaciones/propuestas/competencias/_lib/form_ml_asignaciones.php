<?php
class form_ml_asignaciones extends guarani_pers_ei_formulario_ml
{
	protected function generar_layout($ancho='auto')
	{
		$this->generar_botones();
		//-- Botonera excel y pdf
		echo "<div style='overflow: auto; height: 300px; border: 1px solid black; margin-bottom:2px'>";
		$this->generar_botonera_exportacion();
		//Botonera de agregar y ordenar
		$this->generar_botonera_manejo_filas();
		echo "<table class='ei-ml-grilla' style='width: $ancho' >\n";
		$this->generar_formulario_encabezado();
		$this->generar_formulario_pie();
		$this->generar_formulario_cuerpo();
		echo "\n</table>";
		echo "</div>";
		$this->generar_botones();
	}

}
?>