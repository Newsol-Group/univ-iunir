<?php
class cuadro_salida_fop extends guarani_pers_ei_cuadro
{
	function inicializar($parametros=array())
	{
		parent::inicializar($parametros);
		$this->set_manejador_salida('xml', 'guarani_ei_cuadro_salida_fop_xml');		
	}
}
?>
