<?php
/**
 *
 * @package		Cursadas
 * @subpackage	Comisiones
 */
class pant_seleccion_plataforma extends toba_ei_pantalla
{
	function generar_layout()
	{
		echo '<p style="border-bottom: 2px solid black; padding-bottom: 10px;">';
		echo $this->controlador()->get_mensaje_multiples_pev();
		echo '</p>';
		parent::generar_layout();
	}
}
?>