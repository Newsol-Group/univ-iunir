<?php
/**
 *
 *
 * @package Examenes
 * @subpackage Copiado Masivo de Mesas de Examen
 */
class pant_turno_origen extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		echo '<br>';
		$this->dependencia('form_turno_origen')->generar_html();
		echo '<br> <br>';
		echo '<b>Selecciones la/s Responsables Académicas para las actividades:</b>';
		echo '<br> <br>';
	
		$this->dependencia('filtro_ras')->generar_html();
		$this->dependencia('cuadro_ras')->generar_html();
	}
}
?>