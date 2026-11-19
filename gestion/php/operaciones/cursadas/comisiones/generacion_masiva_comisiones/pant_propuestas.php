<?php
class pant_propuestas extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info_destino()->generar_html();
		$this->controlador()->ev_info()->generar_html();
		echo '<hr/>';
		echo '<b>Seleccionar Propuestas</b> (se listarán las propuestas pertenecientes a la ubicación y las responsables académicas seleccionadas con anterioridad)';
		parent::generar_layout();
	}

}
?>