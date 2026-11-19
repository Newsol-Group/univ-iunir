<?php
class pant_grafo extends guarani_pers_ei_pantalla
{
	/**
	 * Permite modificar la forma en que se grafica la pantalla, por defecto un componente sobre el otro
	 */
	function generar_layout()
	{
		echo $this->controlador()->cn()->get_cabecera();
		$this->dep('esquema')->generar_html();
	}

}

?>