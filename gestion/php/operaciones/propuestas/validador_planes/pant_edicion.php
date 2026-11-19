<?php 
class pant_edicion extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		echo $this->controlador()->cn()->get_cabecera();
		echo $this->controlador()->cn()->get_observaciones();
	}
}
?>