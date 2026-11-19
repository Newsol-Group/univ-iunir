<?php
/**
 *
 *
 * @package     Matrícula
 * @subpackage  Regularidad
 * 
 */
class pant_propuestas extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$this->dep('formulario')->generar_html();
		echo '<br>';
		echo '<b>Seleccionar Propuestas</b>';
		echo '<br></br>';
		$this->dep('filtro')->generar_html();
		$this->dep('cuadro')->generar_html();
	}

}
?>