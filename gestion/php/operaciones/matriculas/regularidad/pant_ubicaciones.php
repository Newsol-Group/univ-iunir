<?php
/**
 *
 *
 * @package     Matrícula
 * @subpackage  Regularidad
 * 
 */
class pant_ubicaciones extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->controlador()->ev_info()->generar_html();
		echo '<br>';
		echo '<b>Seleccionar Ubicaciones</b>';
		echo '<br></br>';
		$this->dep('cuadro_ubicaciones')->generar_html();
	}

}
?>