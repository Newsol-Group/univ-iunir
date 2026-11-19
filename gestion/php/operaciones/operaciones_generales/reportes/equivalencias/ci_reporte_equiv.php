<?php
class ci_reporte_equiv extends ci_impresion_popup
{
	//Elemento Visual de Información
	protected $ev_info;
	
	function ini()
	{
		parent::ini();
		$this->s__datos	= guarani_datos_impresion::instancia()->get_datos();	
	}
	
	function conf__pant_reporte(toba_ei_pantalla $pantalla)
	{
		//Seteo de datos en ev
		$this->ev_info()->set_datos($this->s__datos_conf);
	}
	
	//---- Componente visual para mostrar Información ------------------------------------
	
	function ev_info()		
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_style('border-style: solid; border-width: 1px; border-color: #DDD;');
			$this->ev_info->set_columnas_a_mostrar(array('fecha_visual', 'institucion_nombre', 'propuesta_nombre', 'resp_acad_nombre'));
			$this->ev_info->set_columnas_fila(array('fecha_visual', 'institucion_nombre'), 1);
			$this->ev_info->set_columnas_fila(array('propuesta_nombre', 'resp_acad_nombre'), 2);
			$this->ev_info->set_etiqueta('fecha_visual', 'Fecha de Otorgamiento');
			$this->ev_info->set_etiqueta('institucion_nombre', 'Institución Externa');
			$this->ev_info->set_etiqueta('propuesta_nombre', 'Propuesta');
			$this->ev_info->set_etiqueta('resp_acad_nombre', 'Responsable Académica');
		}
		
		return $this->ev_info;
	}
	
	//---- cuadro -----------------------------------------------------------------------
	
	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_datos($this->s__datos);
	}
	
	function ancho_etiquetas_encabezado()
	{
		return 150;
	}
	
}
?>