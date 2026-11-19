<?php
class ci_edi_docentes extends guarani_pers_ci
{
	protected $datos_docente_temp;
	protected $datos_docente_resp_temp;
	protected $datos_docente_ra_temp;

	function ini()
	{
		$this->dep('form_docente_ra')->set_columna_ordenamiento('nombre');
	}

	//-----------------------------------------------------------------------------------
	//---- form_datos_docente -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_datos_docente(toba_ei_formulario $form)
	{
		if (!$this->cn()->entidad_es_nueva()) {
			$form->ef('persona')->set_solo_lectura();
		}
		if (isset($this->datos_docente_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_docente_temp);
		} else {
			$form->set_datos($this->cn()->entidad()->get_datos_docente());
		}
	}

	function evt__form_datos_docente__modificacion($datos)
	{
		try {
			if ($datos['legajo'] || $datos['estado']) {
				$this->cn()->entidad()->set_datos_docente($datos);
			}
		} catch (guarani_error $e) {
			$this->datos_docente_temp = $datos;
			throw $e;
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_docente_ra -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_docente_ra(form_ml_requerido $form_ml)
	{
		if (isset($this->datos_docente_resp_temp)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->datos_docente_resp_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos_docente_ra();
		}

		$filtro = guarani_sql::armar_condicion_in($datos, 'sga_responsables_academicas', 'responsable_academica', 'responsable_academica', true);
		$institucion_instalacion = toba::db()->quote(guarani_configuracion::instancia()->get_valor_config('institucion_instalacion'));
		$filtro .= " AND sga_responsables_academicas.institucion = $institucion_instalacion";
		$datos_posibles = toba::consulta_php('co_responsables_academicas')->get_listado($filtro);

		$form_ml->set_datos($datos);
		$form_ml->set_datos_posibles($datos_posibles);
	}

	function conf_fila__form_docente_ra($fila)
	{
		$datos_ml = $this->dep('form_docente_ra')->get_datos();
		if (!empty($datos_ml[$fila]['requerido']) && $datos_ml[$fila]['requerido'] == 1) {
			$datos_responsable_academica = toba::consulta_php('co_responsables_academicas')->get_datos_responsable_academica($datos_ml[$fila]['responsable_academica']);
			if (empty($datos_responsable_academica)) {
				$this->dep('form_docente_ra')->ef('requerido')->set_solo_lectura(true);
			} else {
				$this->dep('form_docente_ra')->ef('requerido')->set_solo_lectura(false);
			}
		} else {
			$this->dep('form_docente_ra')->ef('requerido')->set_solo_lectura(false);
		}
	}

	function evt__form_docente_ra__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_docente_ra($datos);
		} catch (guarani_error $e) {
			$this->datos_docente_ra_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_docente_resp ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_docente_resp(toba_ei_formulario $form)
	{
		if (isset($this->datos_docente_resp_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_docente_resp_temp);
		} else {
			$datos = aplanar_matriz($this->cn()->entidad()->get_datos_docente_resp(), 'responsabilidad');
			$form->set_datos(array('responsabilidad' => $datos));
		}
	}

	function evt__form_docente_resp__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_docente_resp($datos['responsabilidad']);
		} catch (guarani_error $e) {
			$this->datos_docente_resp_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}
}
?>