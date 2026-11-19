<?php
/**
 *
 *
 * @package Cursadas
 * @subpackage Actas
 * 
 */
class ci_edi_generacion_acta_promocionales extends guarani_pers_ci
{
	protected $s__evaluacion;
	protected $s__comision;
	protected $s__fecha_inicio_periodo_comision;

	protected $s__nro_acta;
	protected $s__observaciones;

	// Alumnos insertados en 'sga_eval_detalle_cursadas' durante la operación.
	protected $s__alumnos_agregados;

	// Código de acta ficticio, para filtrar los detalles a mostrar
	protected $id_acta_ficticia = acta::id_acta_ficticia;

	function ini()
	{
		if (!isset($this->s__evaluacion)) {
			$this->s__evaluacion = $this->controlador()->get_evaluacion();
		}

		if (guarani::parametros()->get_valor('cur_multiples_actas_comision') == comunes::no) {
			$this->dep('alumnos')->set_eliminar_inscriptos(false);
		}

		$this->dep('alumnos')->set_deshabilitar_eliminar_alumnos_disponibles();

		if (!isset($this->s__comision)) {
			$datos_comision = $this->controlador()->get_datos_comision();
			$this->s__comision = $datos_comision['comision'];
			$this->s__fecha_inicio_periodo_comision = $datos_comision['fecha_inicio_periodo'];
		}

		// Se setean los filtros que necesita el selector de alumnos.
		$this->dep('alumnos')->set_filtros($this->s__evaluacion, $this->id_acta_ficticia, comision::inst_promocionar);
		$this->set_alumnos_asociados();
	}

	function resetear_vars()
	{
		unset($this->s__evaluacion);
		unset($this->s__comision);

		unset($this->s__nro_acta);
		unset($this->s__observaciones);

		unset($this->s__alumnos_agregados);

		$this->dep('alumnos')->evt__cancelar();
	}

	//-----------------------------------------------------------------------------------
	//---- datos de actas ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_nro_acta()
	{
		if (guarani::parametros()->get_valor('cur_carga_manual_nro_acta_promocion') == comunes::no) {
			$this->generar_nro_acta();
		}
		return $this->s__nro_acta;
	}

	function generar_nro_acta()
	{
		$parametros = array();
		$parametros['origen'] = acta::origen_promociones;
		$parametros['tipo_acta'] = acta::tipo_normal;
		$parametros['comision']	= $this->s__comision;

		try {
			$this->s__nro_acta = toba::consulta_php('co_actas')->generar_nro_acta($parametros);
		} catch (toba_error $e) {
			throw guarani::error(guarani_mensajes::get('800_error_generar_nro_acta'));
		}
	}

	function get_observaciones()
	{
		return $this->s__observaciones;
	}

	//-----------------------------------------------------------------------------------
	//---- alumnos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function set_alumnos_asociados()
	{
		$condiciones = array('id_acta_promocion' => $this->id_acta_ficticia);
		$this->dep('alumnos')->set_alumnos_asociados($this->cn()->get_detalles($condiciones));
	}

	function agregar_alumnos_comision()
	{
		$detalles = toba::consulta_php('co_evaluaciones')->get_detalles_acta($this->s__evaluacion, $this->id_acta_ficticia, comision::inst_promocionar);
		foreach ($detalles as $detalle) {
			$id_detalle = $this->cn()->get_id_detalle(array('alumno' => $detalle['alumno']));
			$this->cn()->modificar_columna_detalle($id_detalle, 'id_acta_promocion', $this->id_acta_ficticia);
		}
		$this->set_alumnos_asociados();
	}

	function agregar_alumno_comision($alumno)
	{
		$id_detalle = $this->cn()->get_id_detalle($alumno);

		// Se completa el acta de promoción con el valor ficticio.
		$alumno['id_acta_promocion'] = $this->id_acta_ficticia;

		$this->cn()->modificar_detalle($id_detalle, $alumno);
		$this->set_alumnos_asociados();
	}

	function agregar_alumno($alumno)
	{
		$this->cn()->validar_alumno($alumno['alumno'], $this->s__comision, comision::inst_promocionar);

		// Se completa el acta de promoción con el valor ficticio.
		$alumno['id_acta_promocion'] = $this->id_acta_ficticia;

		// Se completan los datos del detalle.
		$alumno['instancia_promocion'] = 1;
		$alumno['escala_nota_promocion'] = $this->cn()->get_escala_notas_comision_instancia($this->s__comision, comision::inst_promocionar);

		$condiciones = array('alumno' => $alumno['alumno']);
		if ($this->cn()->existe_detalle($condiciones)) {
			$id_detalle = $this->cn()->get_id_detalle($condiciones);
			$this->cn()->modificar_detalle($id_detalle, $alumno);
		} else {
			$alumno['inscripto'] = comunes::no;
			// Se obtiene el plan-versión del alumno a la fecha de inicio del período lectivo de la comisión.
			$alumno['plan_version'] = toba::consulta_php('co_alumnos')->get_plan_version($alumno['alumno'], $this->s__fecha_inicio_periodo_comision);
			$this->cn()->agregar_detalle($alumno);
			// Se indica que el detalle para el alumno se insertó durante la operación. Al desasociarlo del acta se eliminará.
			$this->s__alumnos_agregados[$alumno['alumno']] = true; 
		}

		$this->set_alumnos_asociados();
	}

	function eliminar_alumno($alumno)
	{
		$detalle = $this->cn()->get_detalle($alumno);

		if (isset($this->s__alumnos_agregados[$detalle['alumno']])) { // Si es un alumno insertado durante la operación, se elimina el detalle.
			$this->cn()->eliminar_detalle($alumno);
			unset($this->s__alumnos_agregados[$detalle['alumno']]);
		} else { // Sino, se desasocia del acta.
			$this->cn()->modificar_columna_detalle($alumno, 'id_acta_promocion', null);
		}

		$this->set_alumnos_asociados();
	}

	//-----------------------------------------------------------------------------------
	//---- form_acta --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_acta(toba_ei_formulario $form)
	{
		$datos_form = array();

		// Número de Acta: deberá estar visible sólo si cur_carga_manual_nro_acta_promocion = S.
		if (guarani::parametros()->get_valor('cur_carga_manual_nro_acta_promocion') == comunes::no) {
			$form->desactivar_efs(array('nro_acta'));
		} else {
			$datos_form['nro_acta'] = $this->s__nro_acta;
		}

		$datos_form['observaciones'] = $this->s__observaciones;
		$form->set_datos($datos_form);	
	}

	function evt__form_acta__modificacion($datos)
	{
		if (isset($datos['nro_acta'])) {
			$this->s__nro_acta = $datos['nro_acta'];
		}

		$this->s__observaciones = $datos['observaciones'];
	}
}

?>