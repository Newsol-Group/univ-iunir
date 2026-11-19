<?php
/**
 *
 *
 * @package 	Cursadas
 * @subpackage 	Actas
 * 
 */
//Newsol - Se agregó el % de asistencia de la comisión a la cabecera del acta
class ci_edi_cargar_notas_nsg extends ci_edi_cargar_notas
{
	// Nombre de las instancias.
	protected $s__pct_asistencia;

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('nro_acta', 'instancia_nombre', 'tipo_acta_descr', 'comision_nombre',
				'nombre_turno', 'modalidad_nombre', 'anio_academico_periodo', 'periodo_lectivo_nombre',
				'nombre_ubicacion', 'codigo_y_nombre_actividad', 'icono_info_escalas_notas', 'porc_asistencia'));

			$this->ev_info->set_columnas_fila(array('nro_acta', 'instancia_nombre', 'tipo_acta_descr', 'comision_nombre'), 1);
			$this->ev_info->set_columnas_fila(array('nombre_turno', 'modalidad_nombre', 'anio_academico_periodo', 'periodo_lectivo_nombre'), 2);
			$this->ev_info->set_columnas_fila(array('nombre_ubicacion', 'codigo_y_nombre_actividad', 'icono_info_escalas_notas'), 3);
			$this->ev_info->set_columnas_fila(array('icono_info_escalas_notas','porc_asistencia'), 4);

			$this->ev_info->set_max_columnas_por_fila(4);

			$this->ev_info->set_etiqueta('nro_acta', 'Acta');
			$this->ev_info->set_etiqueta('instancia_nombre', 'Instancia');
			$this->ev_info->set_etiqueta('tipo_acta_descr', 'Tipo');
			$this->ev_info->set_etiqueta('comision_nombre', 'Comisión');

			$this->ev_info->set_etiqueta('nombre_turno', 'Turno');
			$this->ev_info->set_etiqueta('modalidad_nombre', 'Modalidad');
			$this->ev_info->set_etiqueta('anio_academico_periodo', 'Año Académico');
			$this->ev_info->set_etiqueta('periodo_lectivo_nombre', 'Período Lectivo');

			$this->ev_info->set_etiqueta('nombre_ubicacion', 'Ubicación');
			$this->ev_info->set_etiqueta('codigo_y_nombre_actividad', 'Actividad');
			$this->ev_info->set_etiqueta('icono_info_escalas_notas', 'Escala de notas');
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