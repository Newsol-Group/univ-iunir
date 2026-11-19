<?php
/**
 *
 *
 * @package		Asistencias
 * @subpackage	-
 * 
 */
class ci_edi_registrar_asistencia_alumnos extends guarani_pers_ci
{
	protected $datos_asistencia_temp;
	protected $datos_presentes_temp;

	//-----------------------------------------------------------------------------------
	//---- PANTALLA Inicial 
	//-----------------------------------------------------------------------------------

	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		// Se verifica si se registran o no cantidad de inasistencias para determinar 
		// qué dependencia debe mostrarse en la pantalla de edición.
		if ($this->pantalla()->existe_dependencia('form_ml_asistencias')) {
			$this->pantalla()->eliminar_dep('form_ml_asistencias');
		}
		if ($this->pantalla()->existe_dependencia('form_ml_presentes')) {
			$this->pantalla()->eliminar_dep('form_ml_presentes');
		}
		if ($this->controlador()->registra_cantidad_inasistencias()) {
			$this->pantalla()->agregar_dep('form_ml_asistencias');
			// Se setean las expresiones regulares correspondientes al control del nro. ingresado para cantidad
			// de inasistencias, dependiendo del parámetro que indica si se carga cantidad de horas o no.
			// Si se cargan cantidad de horas, entonces no pueden ingresarse decimales.
			// De lo contrario se muestra el combo y se elige la cantidad con el mismo.
			if ($this->controlador()->registra_cantidad_horas()) {
				$this->dep('form_ml_asistencias')->ef('cant_inasistencias')->set_expreg('/^[0-9]{1,3}$/');
				$this->dep('form_ml_asistencias')->desactivar_efs('cant_inasistencias_combo');
			} else {
				$this->dep('form_ml_asistencias')->desactivar_efs('cant_inasistencias');
			}
		} else {
			$this->pantalla()->agregar_dep('form_ml_presentes');
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_ml_inasistencias --------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_ml_asistencias(toba_ei_formulario_ml $form)
	{
		if (isset($this->datos_asistencia_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_asistencia_temp);
		} else {
			$comision = $this->controlador()->get_preseleccion();
			$subcomision = $this->controlador()->get_seleccion_subcomision();
			$clase = $this->controlador()->get_seleccion_clase();
			$form->set_datos($this->controlador()->cn()->get_listado_alumnos_asistencias($comision, $subcomision, $clase));
		}
	}

	function conf_fila__form_ml_asistencias($fila)
	{
		$datos_ml = $this->dep('form_ml_asistencias')->get_datos(true);
		// Si la inasistencia está justificada no se puede poner al alumno como presente.
		if ($datos_ml[$fila]['cant_justificadas'] > 0) {
			$this->dep('form_ml_asistencias')->set_solo_lectura(array('cant_inasistencias', 'cant_inasistencias_combo'));
		} else {
			$this->dep('form_ml_asistencias')->set_solo_lectura(array('cant_inasistencias', 'cant_inasistencias_combo'), false);
		}
	}

	function evt__form_ml_asistencias__modificacion($datos)
	{		
		try {
			$this->cn()->set_asistencia($datos);
		} catch (guarani_error $e) {
			$this->datos_asistencia_temp = $datos;
			throw $e;
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_ml_presentes ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_ml_presentes(toba_ei_formulario_ml $form)
	{
		if (isset($this->datos_presentes_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_presentes_temp);
		} else {
			$comision = $this->controlador()->get_preseleccion();
			$subcomision = $this->controlador()->get_seleccion_subcomision();
			$clase = $this->controlador()->get_seleccion_clase();
			$form->set_datos($this->controlador()->cn()->get_listado_alumnos_asistencias($comision, $subcomision, $clase));
		}
	}

	function conf_fila__form_ml_presentes($fila)
	{
		$datos_ml = $this->dep('form_ml_presentes')->get_datos(true);
		// Si la inasistencia está justificada no se puede poner al alumno como presente.
		if ($datos_ml[$fila]['cant_justificadas'] > 0) {
			$this->dep('form_ml_presentes')->set_solo_lectura(array('presente'));
		} else {
			$this->dep('form_ml_presentes')->set_solo_lectura(array('presente'), false);
		}
	}

	function evt__form_ml_presentes__modificacion($datos)
	{
		try {
			$this->cn()->set_asistencia($datos);			
		} catch (guarani_error $e) {
			$this->datos_presentes_temp = $datos;
			throw $e;
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- form_libres ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__form_libres(form_libres $form)
	{		
		$datos['mostrar_libres'] = $this->controlador()->cn()->get_mostrar_libres();
		$form->set_datos($datos);
	}
	
	function evt__form_libres__mostrar_libres($datos)
	{
		$this->controlador()->cn()->set_mostrar_libres($datos['mostrar_libres']);
	}
	
}
?>