<?php
class ci_rep_alumnos_examenes_sin_acta extends ci_impresion_popup
{
	protected $ev_info;	
	protected $s__llamado_mesa;

	function ini()
	{
		parent::ini();
		$parametros = guarani_datos_impresion::instancia()->get_datos();
		$this->ev_info()->set_datos($parametros);
		$this->s__llamado_mesa = $parametros['llamado_mesa'];
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_columnas_a_mostrar(array('anio_academico', 'turno_nombre', 'llamado_nombre',  'codigo_nombre_actividad', 'nombre_mesa', 'fecha_examen'));
			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('turno_nombre', 'Turno de Examen');
			$this->ev_info->set_etiqueta('llamado_nombre', 'Llamado');
			$this->ev_info->set_etiqueta('codigo_nombre_actividad', 'Actividad');
			$this->ev_info->set_etiqueta('nombre_mesa', 'Mesa de Examen');
			$this->ev_info->set_etiqueta('fecha_examen', 'Fecha');
		}
		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		return toba::consulta_php('co_actas')->get_alumnos_una_mesa_examen_no_estan_en_acta($this->s__llamado_mesa);
	}

}
?>