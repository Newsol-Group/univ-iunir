<?php
class ci_edi_modificacion_prioridad_insc_actividades extends guarani_pers_ci
{
	// Componente visual de información
	protected $ev_info;
	protected $datos_temp;
	protected $s__datos;
	protected $hubo_cambios = false;

	//--------------------------------------------------------------------------
	//---- ev_info -------------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__pant_edicion()
	{
		// Se establecen los datos al elemento visual de información
		$datos = guarani::co('co_alumnos')->get_datos_basicos_alumno($this->controlador()->get_alumno_preseleccionado());

		$elemento = toba::db()->quote($this->controlador()->get_actividad_seleccionada());
		$where = "sga_elementos.elemento = $elemento";
		$datos_elem = guarani::co('co_elementos')->get_actividades($where);
		$datos['actividad_codigo_y_nombre'] = $datos_elem[0]['descr'];
		$datos['actividad_tipo'] = $datos_elem[0]['subtipo_nombre'];

		$this->s__datos = $datos;

		$this->ev_info()->set_datos($datos);
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();

			$this->ev_info->set_columnas_a_mostrar(array('apellido_y_nombre', 'documento_principal', 'legajo', 'propuesta_nombre', 'plan_nombre', 'plan_version_nombre', 'ubicacion_nombre', 'actividad_codigo_y_nombre', 'actividad_tipo'));

			$this->ev_info->set_columnas_fila(array('apellido_y_nombre', 'documento_principal', 'legajo'), 1);
			$this->ev_info->set_columnas_fila(array('propuesta_nombre', 'plan_nombre', 'plan_version_nombre', 'ubicacion_nombre'), 2);
			$this->ev_info->set_columnas_fila(array('actividad_codigo_y_nombre', 'actividad_tipo'), 3);

			$this->ev_info->set_max_columnas_por_fila(4);

			$this->ev_info->set_etiqueta('apellido_y_nombre', 'Alumno');
			$this->ev_info->set_etiqueta('documento_principal', 'Identificación');
			$this->ev_info->set_etiqueta('legajo', 'Legajo');

			$this->ev_info->set_etiqueta('propuesta_nombre', 'Propuesta');
			$this->ev_info->set_etiqueta('plan_nombre', 'Plan');
			$this->ev_info->set_etiqueta('plan_version_nombre', 'Versión');
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');

			$this->ev_info->set_etiqueta('actividad_codigo_y_nombre', 'Actividad');
			$this->ev_info->set_etiqueta('actividad_tipo', 'Tipo');
		}
		return $this->ev_info;
	}

	//--------------------------------------------------------------------------
	//---- form_ml_prioridades_insc --------------------------------------------
	//--------------------------------------------------------------------------

	function conf__form_ml_prioridades_insc(toba_ei_formulario_ml $form_ml)
	{
		if (isset($this->datos_temp)) {
			$this->controlador->set_hubo_cambios();
			$form_ml->set_datos($this->datos_temp);
		} else {
			$form_ml->set_datos($this->cn()->entidad()->get_datos());
		}
	}

	function evt__form_ml_prioridades_insc__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos($datos);
		} catch (guarani_error $e) {
			$this->datos_temp = $datos;
			throw $e;
		}
	}

	function conf_evt__form_ml_prioridades_insc__ver_docentes($evento, $fila)
	{
		$datos = $this->dep('form_ml_prioridades_insc')->get_datos(true);
		$datos_fila = $datos[$fila];

		$info['comision'] = $datos_fila['comision'];
		$info['comision_nombre'] = $datos_fila['comision_nombre'];
		$info['titulo'] = $this->get_titulo();

		$info['propuesta_descr'] = $this->s__datos['propuesta_nombre'];
		$info['plan_nombre'] = $this->s__datos['plan_nombre'];
		$info['plan_version_nombre'] = $this->s__datos['plan_version_nombre'];
		$info['ubicacion_nombre'] = $this->s__datos['ubicacion_nombre'];
		$info['descr'] = $this->s__datos['actividad_codigo_y_nombre'];

		foreach ($info as $dato => $valor) {
			$evento->vinculo()->agregar_parametro(utf8_encode($dato), utf8_encode($valor));
		}
	}

	function get_comisiones_alternativas()
	{
		$cant_comisiones_disponibles = guarani_arreglos::count($this->cn()->entidad()->get_datos());
		$res = array();

		for ($i = 1; $i <= $cant_comisiones_disponibles; $i++) {
			$res[] = array('valor' => $i, 'descr' => $i);
		}

		return $res;
	}

	function unset_vars()
	{
		unset($this->s__datos);
	}

}
?>