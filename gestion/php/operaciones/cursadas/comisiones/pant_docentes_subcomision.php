<?php
/**
 *
 *
 * @package		Cursadas
 * @subpackage	Comisiones
 * 
 */
class pant_docentes_subcomision extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('docentes_asociados')->generar_html();
		echo '<hr />';
		$this->dep('seleccion_docentes')->generar_botones();
		$this->dep('seleccion_docentes')->evento('agregar')->set_en_botonera(false);
		$this->dep('seleccion_docentes')->generar_html();
		echo '<hr />';
	}

}
?>