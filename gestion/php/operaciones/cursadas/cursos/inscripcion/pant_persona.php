<?php
/**
 * 
 * 
 * @package		Cursadas
 * @subpackage	Cursos
 * 
 */
class pant_persona extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('filtro_persona')->generar_html();
		echo '<hr />';
		$this->dep('cuadro_persona')->generar_botones();
		$this->dep('cuadro_persona')->evento('agregar_persona')->set_en_botonera(false);
		$this->dep('cuadro_persona')->generar_html();
	}
}
?>