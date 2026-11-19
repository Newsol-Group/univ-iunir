<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos por Operación
 * 
 */
class filtro_req_a_configurar extends guarani_pers_ei_filtro
{
	function ini()
	{
		$this->set_sin_sensitividad_tildes();
		$this->set_sin_sensitividad_mayusculas();
		parent::ini();
	}
}
?>