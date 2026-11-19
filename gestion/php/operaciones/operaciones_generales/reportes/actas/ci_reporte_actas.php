<?php
class ci_reporte_actas extends ci_impresion_popup
{
	function ini()
	{
		parent::ini();
		$this->s__titulo = 'Reporte de Actas Generadas';
	}

	function conf__pant_reporte(toba_ei_pantalla $pantalla)
	{
		$this->ev_info()->set_datos($this->s__datos_conf);
	}

	//--------------------------------------------------------------------------
	//---- cuadro --------------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_datos($this->s__datos);
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_style('width: 70%;');
			$this->ev_info->set_columnas_a_mostrar(array('anio_academico', 'nombre_turno_examen'));
			$this->ev_info->set_columnas_fila(array('anio_academico', 'nombre_turno_examen'));
			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('nombre_turno_examen', 'Turno');
		}

		return $this->ev_info;
	}

}
?>