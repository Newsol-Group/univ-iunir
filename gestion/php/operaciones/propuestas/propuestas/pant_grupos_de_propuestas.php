<?php
class pant_grupos_de_propuestas extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('formulario')->generar_html();
		echo '<hr />';
		$this->dep('propuestas_asociadas')->generar_html();
		echo '<hr />';
		$this->dep('prop_no_asociadas')->generar_botones();
		$this->dep('prop_no_asociadas')->evento('agregar')->set_en_botonera(false);
		$this->dep('prop_no_asociadas')->generar_html();
	}
}

?>