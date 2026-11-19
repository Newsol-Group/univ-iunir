<?php
class ci_rep_alumnos_comision_sin_acta extends ci_impresion_popup
{
	protected $ev_info;	
	protected $s__comision;
	protected $s__instancia;	
	
	function ini()
	{
		parent::ini();
		$parametros = guarani_datos_impresion::instancia()->get_datos();
		$this->ev_info()->set_datos($parametros);
		$this->s__comision = $parametros['comision'];
		$this->s__instancia = $parametros['instancia'];
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_columnas_a_mostrar(array('anio_academico', 'periodo_nombre', 'codigo_y_nombre_actividad', 'comision_nombre', 'instancia_nombre' ));
			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('periodo_nombre', 'Periodo Lectivo');
			$this->ev_info->set_etiqueta('codigo_y_nombre_actividad', 'Actividad');
			$this->ev_info->set_etiqueta('comision_nombre', 'Comisión');
			$this->ev_info->set_etiqueta('instancia_nombre', 'Instancia');
		}
		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		return toba::consulta_php('co_comisiones')->get_alumnos_comision_no_estan_en_acta($this->s__comision, $this->s__instancia);
	}
}

?>