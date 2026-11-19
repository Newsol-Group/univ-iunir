<?php
/**
 *
 *
 * @package		Examenes
 * @subpackage	Mesas de Examen
 * 
 */
class pant_docentes_llamado extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('form_tribunal')->generar_html();
		echo '<hr />';
		$this->dep('docentes_asociados')->generar_html();
		echo '<hr />';
		$this->dep('filtro_docentes')->generar_html();
		echo '<hr />';
		$this->dep('seleccion_docentes')->generar_botones();
		$this->dep('seleccion_docentes')->evento('agregar')->set_en_botonera(false);
		$this->dep('seleccion_docentes')->generar_html();
		echo '<hr />';
	}

}
?>