<?php
/**
 *
 *
 * @package Cursadas
 * @subpackage Comisiones
 * 
 */
class ci_edi_carga_notas_nsg extends ci_edi_carga_notas
{

	function ini()
	{
		toba::memoria()->desactivar_reciclado();
		parent::ini();
		$this->s__datos = $this->controlador()->get_datos_comision();
	}


	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_td_etiqueta_width('110px');
			$this->ev_info->set_columnas_a_mostrar(array('nombre', 'nombre_turno', 'nombre_modalidad', 'anio_academico_periodo', 'nombre_periodo', 'codigo_y_nombre_actividad', 'nombre_ubicacion', 'porc_asistencia', 'icono_info_escalas_notas'));
			$this->ev_info->set_columnas_fila(array('nombre', 'nombre_turno', 'nombre_modalidad'), 1);
			$this->ev_info->set_columnas_fila(array('anio_academico_periodo', 'nombre_periodo'), 2);
			$this->ev_info->set_columnas_fila(array('codigo_y_nombre_actividad', 'nombre_ubicacion'), 3);
			$this->ev_info->set_columnas_fila(array('icono_info_escalas_notas','porc_asistencia'), 4);
			$this->ev_info->set_max_columnas_por_fila(3);
			$this->ev_info->set_etiqueta('nombre', 'Comisión');
			$this->ev_info->set_etiqueta('nombre_turno', 'Turno');
			$this->ev_info->set_etiqueta('nombre_modalidad', 'Modalidad');
			$this->ev_info->set_etiqueta('anio_academico_periodo', 'Año Académico');
			$this->ev_info->set_etiqueta('nombre_periodo', 'Período Lectivo');
			$this->ev_info->set_etiqueta('codigo_y_nombre_actividad', 'Actividad');
			$this->ev_info->set_etiqueta('nombre_ubicacion', 'Ubicación');
			$this->ev_info->set_etiqueta('icono_info_escalas_notas', 'Escalas de Notas');
			$this->ev_info->set_etiqueta('porc_asistencia', 'Porcentaje Asistencia');

		}
		return $this->ev_info;
	}

	function get_porcentaje_asistencia()
	{
		return $this->controlador()->get_datos_comision()['porc_asistencia'];
	}

	function conf_fila__form_ml_notas($fila)
	{
		$datos_ml = $this->dep('form_ml_notas')->get_datos(true);

		// Si el alumno está inscripto a la instancia cursar y está incluido en un acta de regulares cerrada,
		// se deberán deshabilitar los campos de la instancia cursar.
		if ($datos_ml[$fila]['instancia_cursada'] && $datos_ml[$fila]['acta_cursada_cerrada']) {
			$this->dep('form_ml_notas')->set_solo_lectura(array('fecha_regular', 'cond_regularidad', 'resultado_cursada', 'observacion_cursada'));
		} else {
			$this->dep('form_ml_notas')->set_solo_lectura(array('fecha_regular', 'cond_regularidad', 'resultado_cursada', 'observacion_cursada'), false);
		}

		// Si el alumno está inscripto a la instancia promoción y está incluido en un acta de promoción cerrada,
		// se deberán deshabilitar los campos de la instancia promocionar.
		if ($datos_ml[$fila]['instancia_promocion'] && $datos_ml[$fila]['acta_promocion_cerrada']) {
			$this->dep('form_ml_notas')->set_solo_lectura(array('fecha_promocion', 'resultado_promocion', 'observacion_promocion'));
		} else {
			$this->dep('form_ml_notas')->set_solo_lectura(array('fecha_promocion', 'resultado_promocion', 'observacion_promocion'), false);
		}

		// Si el alumno está inscripto a la instancia cursar y está incluido en un acta de regulares cerrada o cur_asentar_notas_actas_regulares = 'S',
		// se deberá deshabilitar el campo Nota de Cursada.
		if ($datos_ml[$fila]['instancia_cursada'] && ($datos_ml[$fila]['acta_cursada_cerrada'] || guarani::parametros()->get_valor('cur_asentar_notas_actas_regulares') == acta::ingreso_notas_sin_notas)) {
			$this->dep('form_ml_notas')->set_solo_lectura(array('nota_cursada'));
		} else {
			$this->dep('form_ml_notas')->set_solo_lectura(array('nota_cursada'), false);
		}

		// Si el alumno está inscripto a la instancia promoción y está incluido en un acta de promoción cerrada o cur_asentar_notas_actas_promocion = 'S',
		// se deberá deshabilitar el campo Nota de Promoción.
		if ($datos_ml[$fila]['instancia_promocion'] && ($datos_ml[$fila]['acta_promocion_cerrada'] || guarani::parametros()->get_valor('cur_asentar_notas_actas_promocion') == acta::ingreso_notas_sin_notas)) {
			$this->dep('form_ml_notas')->set_solo_lectura(array('nota_promocion'));
		} else {
			$this->dep('form_ml_notas')->set_solo_lectura(array('nota_promocion'), false);
		}
	}
}
?>