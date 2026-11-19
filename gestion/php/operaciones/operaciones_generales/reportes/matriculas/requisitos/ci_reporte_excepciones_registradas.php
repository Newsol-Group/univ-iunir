<?php
class ci_reporte_excepciones_registradas extends ci_impresion_popup
{
	//Elemento Visual de Información
	protected $ev_info;
	
	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		//Seteo de datos en ev
		$this->ev_info()->set_datos($this->s__datos_conf);
	}
	
	//---- Componente visual para mostrar Información -----------------------------------
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_style('width: 40%;');
			$this->ev_info->set_max_chars_por_columna(200);
			$this->ev_info->set_columnas_a_mostrar(array('requisito'));
			$this->ev_info->set_etiqueta('requisito', 'Requisito');
		}
		return $this->ev_info;
	}
	
	//---- cuadro -----------------------------------------------------------------------
	
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_datos($this->s__datos);
	}
	
}
?>