<?php
/**
 *
 *
 * @package Examenes
 * @subpackage Copiado Masivo de Mesas de Examen
 */
class pant_turno_destino extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		echo '<br>';
		$this->controlador()->ev_info_origen()->generar_html();
		echo '<br> <br>';
		$this->controlador()->ev_info_filtros()->generar_html();
		echo '<hr />';
		
		echo '<br>';
		echo '<b>Destino</b>';
		echo '<br> <br>';
	
		$this->dependencia('form_turno_destino')->generar_html();
	}

}
?>