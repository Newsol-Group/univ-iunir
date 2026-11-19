<?php
/**
 *
 *
 * @package		Cursadas
 * @subpackage	Comisiones
 * 
 */
class pant_bandas_horarias_subcomision extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('bandas_horarias_asociadas')->generar_html();
		echo '<hr />';
		$this->dep('seleccion_bandas_horarias')->generar_botones();
		$this->dep('seleccion_bandas_horarias')->evento('agregar')->set_en_botonera(false);
		$this->dep('seleccion_bandas_horarias')->generar_html();
	}

}
?>