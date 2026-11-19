<?php
/**
 *
 *
 * @package Examenes
 * @subpackage Copiado Masivo de Mesas de Examen
 */
class pant_propuestas extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		echo '<br>';
		$this->controlador()->ev_info_origen()->generar_html();
		echo '<br> <br>';
		$this->controlador()->ev_info_filtros()->generar_html();
		echo '<hr />';
		
		echo '<br>';
		echo '<b>Seleccionar Propuestas:</b> (Se listarán las propuestas correspondientes a la ubicación y responsables académicas seleccionadas con anterioridad)';
		echo '<br> <br>';
	
		$this->dependencia('filtro_propuestas')->generar_html();
		$this->dependencia('cuadro_propuestas')->generar_html();
	}

}
?>