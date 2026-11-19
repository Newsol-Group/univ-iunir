<?php
/**
 *
 *
 * @package 	Cursadas
 * @subpackage 	Actas
 * 
 */
//Newsol - Se agregó el % de asistencia de la comisión a la cabecera del acta
class ci_nav_cargar_notas_nsg extends ci_nav_cargar_notas
{
	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('nombre', 'nombre_turno', 'nombre_modalidad', 'anio_academico_periodo', 'nombre_periodo', 'codigo_y_nombre_actividad', 'nombre_ubicacion', 'porc_asistencia'));

			$this->ev_info->set_columnas_fila(array('anio_academico_periodo', 'nombre_periodo', 'nombre'), 1);
			$this->ev_info->set_columnas_fila(array('nombre_ubicacion', 'nombre_turno', 'nombre_modalidad'), 2);
			$this->ev_info->set_columnas_fila(array('codigo_y_nombre_actividad'), 3);
			$this->ev_info->set_columnas_fila(array('porc_asistencia'), 4);

			$this->ev_info->set_max_columnas_por_fila(3);

			$this->ev_info->set_etiqueta('anio_academico_periodo', 'Año Académico');
			$this->ev_info->set_etiqueta('nombre_periodo', 'Período Lectivo');
			$this->ev_info->set_etiqueta('nombre', 'Comisión');

			$this->ev_info->set_etiqueta('nombre_ubicacion', 'Ubicación');
			$this->ev_info->set_etiqueta('nombre_turno', 'Turno');
			$this->ev_info->set_etiqueta('nombre_modalidad', 'Modalidad');

			$this->ev_info->set_etiqueta('codigo_y_nombre_actividad', 'Actividad');
			$this->ev_info->set_etiqueta('porc_asistencia', 'Porcentaje Asistencia');
		}
		return $this->ev_info;
	}

	function get_porcentaje_asistencia()
	{
		return $this->controlador()->get_datos_comision()['porc_asistencia'];
	}


}
?>