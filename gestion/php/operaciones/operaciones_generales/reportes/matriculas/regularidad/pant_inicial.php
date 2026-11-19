<?php
/**
 *
 *
 * @package     Matrícula
 * @subpackage  Regularidad
 */
class pant_inicial extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$titulo = $this->controlador()->get_titulo();
		echo '<br>';
		echo "<u><b>$titulo</b></u>";
		echo '<br></br>';
		parent::generar_layout();
	}

}

?>