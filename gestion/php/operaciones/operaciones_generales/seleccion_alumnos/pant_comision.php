<?php
class pant_comision extends toba_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('filtro_comision')->generar_html();
		echo '<hr />';
		$this->dep('comision_disponibles')->generar_botones();
		$this->dep('comision_disponibles')->evento('agregar')->set_en_botonera(false);
		$this->dep('comision_disponibles')->generar_html();
	}
}
?>