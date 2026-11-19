<?php
class ci_pro_inscripcion_cursada_masiva extends ci_nav_procesos_bk
{
	function set_nombre_proceso()
	{
		$this->s__nombre_proceso = pro_inscripcion_cursadas_masiva::nombre;
	}
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_columnas_a_mostrar(array('propuesta_descr', 'ubicacion_descr', 'plan_descr', 'plan_version_descr', 
				'descr', 'comision_nombre', 'anio_academico', 'periodo_lectivo_nombre'));

			$this->ev_info->set_max_columnas_por_fila(2);
			$this->ev_info->set_td_etiqueta_width('100px');
			$this->ev_info->set_td_valores_width('375px');

			$this->ev_info->set_etiqueta('propuesta_descr', 'Propuesta');
			$this->ev_info->set_etiqueta('plan_descr', 'Plan');
			$this->ev_info->set_etiqueta('plan_version_descr', 'Plan Versión');
			$this->ev_info->set_etiqueta('ubicacion_descr', 'Ubicación');
			$this->ev_info->set_etiqueta('descr', 'Actividad');
			$this->ev_info->set_etiqueta('comision_nombre', 'Comisión');
			$this->ev_info->set_etiqueta('anio_academico', 'Año académico');
			$this->ev_info->set_etiqueta('periodo_lectivo_nombre', 'Período lectivo');
		}
		return $this->ev_info;
	}

}
?>