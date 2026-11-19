<?php
class pant_datos_salud extends toba_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('form_salud')->generar_html();
	}
}