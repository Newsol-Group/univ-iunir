<?php
/**
 *
 *
 * @package Cursadas
 * @subpackage Actas
 * 
 */
class ci_edi_rectificativa_regulares extends guarani_pers_ci
{
	// Comisión y acta seleccionadas.
	protected $s__evaluacion;
	protected $s__acta_original;
	protected $s__datos_acta_original;
	
	// Datos del nuevo acta (rectificativa de la original).
	protected $s__documento;
	protected $s__nro_acta;
	protected $s__observaciones;
	
	// Alumnos insertados en 'sga_eval_detalle_cursadas' durante la operación.
	protected $s__alumnos_agregados;
	
	// Código de acta ficticio, para filtrar los detalles a mostrar!
	protected $id_acta_ficticia = acta::id_acta_ficticia;
	
	// Componente Visual de Información de Datos de Actas
	protected $ev_info_acta;

	function ini()
	{
		if (!isset($this->s__evaluacion)) {
			$this->s__evaluacion = $this->controlador()->get_evaluacion();
		}

		if (!isset($this->s__acta_original)) {
			$this->s__acta_original = $this->controlador()->get_acta_seleccionada();
		}

		if (!isset($this->s__datos_acta_original)) {
			$this->s__datos_acta_original = $this->controlador()->get_datos_acta_original();
		}

		$this->dep('alumnos')->set_deshabilitar_eliminar_alumnos_disponibles();

		// Se setean los filtros que necesita el selector de alumnos.
		$this->dep('alumnos')->set_filtros($this->s__evaluacion, $this->id_acta_ficticia, comision::inst_cursar, $this->s__acta_original);
		$this->set_alumnos_asociados();
	}

	function resetear_vars()
	{
		unset($this->s__evaluacion);
		unset($this->s__acta_original);
		unset($this->s__datos_acta_original);

		unset($this->s__documento);
		unset($this->s__nro_acta);
		unset($this->s__observaciones);

		unset($this->s__alumnos_agregados);

		$this->dep('alumnos')->evt__cancelar();
	}

	//-----------------------------------------------------------------------------------
	//---- datos de actas ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_documento()
	{
		if (isset($this->s__documento)) {
			return $this->s__documento;
		}
	}

	function get_nro_acta()
	{
		if (guarani::parametros()->get_valor('cur_carga_manual_nro_acta_regulares') == comunes::no) {
			$this->generar_nro_acta();
		}
		return $this->s__nro_acta;
	}

	function generar_nro_acta()
	{
		$parametros = array();
		$parametros['origen'] = acta::origen_regulares;
		$parametros['tipo_acta'] = acta::tipo_rectificativa;
		$parametros['comision'] = $this->s__datos_acta_original['comision'];

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

	//--------------------------------------------------------------------------------------------------------------------
	//---- PANTALLA INICIAL ----------------------------------------------------------------------------------------------
	//--------------------------------------------------------------------------------------------------------------------

	function conf__pant_inicial()
	{
		// Configuración del ev del acta.
		$this->ev_info_acta()->set_datos($this->s__datos_acta_original);
	}

	function ev_info_acta()
	{
		if (!isset($this->ev_info_acta)) {
			$this->ev_info_acta = guarani::ev_info_datos();

			$this->ev_info_acta->set_columnas_a_mostrar(array('nro_acta', 'libro_nombre', 'anio_academico_periodo', 'periodo_nombre', 'comision_nombre', 'modalidad_nombre', 'codigo_y_nombre_actividad', 'nombre_ubicacion', 'escala_nota_nombre', 'nombre_turno', 'observaciones'));

			$this->ev_info_acta->set_columnas_fila(array('nro_acta', 'comision_nombre', 'libro_nombre'), 1);
			$this->ev_info_acta->set_columnas_fila(array('anio_academico_periodo', 'periodo_nombre', 'modalidad_nombre'), 2);
			$this->ev_info_acta->set_columnas_fila(array('codigo_y_nombre_actividad', 'nombre_ubicacion', 'escala_nota_nombre'), 3);
			$this->ev_info_acta->set_columnas_fila(array('nombre_turno'), 4);
			$this->ev_info_acta->set_columnas_fila(array('observaciones'), 5);

			$this->ev_info_acta->set_max_columnas_por_fila(3);

			$this->ev_info_acta->set_etiqueta('nro_acta', 'Acta Original');
			$this->ev_info_acta->set_etiqueta('comision_nombre', 'Comisión');
			$this->ev_info_acta->set_etiqueta('libro_nombre', 'Libro');

			$this->ev_info_acta->set_etiqueta('anio_academico_periodo', 'Año Académico');
			$this->ev_info_acta->set_etiqueta('periodo_nombre', 'P. Lectivo');
			$this->ev_info_acta->set_etiqueta('modalidad_nombre', 'Modalidad');

			$this->ev_info_acta->set_etiqueta('codigo_y_nombre_actividad', 'Actividad');
			$this->ev_info_acta->set_etiqueta('nombre_ubicacion', 'Ubicación');
			$this->ev_info_acta->set_etiqueta('escala_nota_nombre', 'Escala de Notas');

			$this->ev_info_acta->set_etiqueta('nombre_turno', 'Turno');
			$this->ev_info_acta->set_etiqueta('observaciones', 'Observaciones');
		}
		return $this->ev_info_acta;
	}

	//-----------------------------------------------------------------------------------
	//---- alumnos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function set_alumnos_asociados()
	{
		$condiciones = array('id_acta_cursada' => $this->id_acta_ficticia);
		$this->dep('alumnos')->set_alumnos_asociados($this->cn()->get_detalles($condiciones));
	}

	function agregar_alumno_comision($alumno)
	{
		// Se completa el acta de cursada con el valor ficticio.
		$alumno['id_acta_cursada'] = $this->id_acta_ficticia;
		$alumno['instancia_cursada'] = 1;
		$alumno['acta_cursada_cerrada'] = 0;

		$condiciones = array('alumno' => $alumno['alumno']);
		if ($this->cn()->existe_detalle($condiciones)) {
			$id_detalle = $this->cn()->get_id_detalle($condiciones);
			$this->cn()->modificar_detalle($id_detalle, $alumno);
		} else {
			// Completar los campos nota y resultado para la instancia cursada y promoción (si existe) obteniendo los datos del detalle del último acta válida.
			$detalle_cursada = toba::consulta_php('co_actas')->get_detalle_alumno_comision($alumno['alumno'], $this->s__datos_acta_original['comision'], acta::origen_regulares);
			$alumno['escala_nota_cursada'] = $detalle_cursada['escala_nota'];
			$alumno['nota_cursada'] = $detalle_cursada['nota'];
			$alumno['resultado_cursada'] = $detalle_cursada['resultado'];
			$alumno['plan_version'] = $detalle_cursada['plan_version'];
			$alumno['inscripto'] = toba::consulta_php('co_alumnos')->tiene_inscripciones_actividad($alumno['alumno'], "vw_insc_cursada.comision = {$this->s__datos_acta_original['comision']}") ? comunes::si : comunes::no;

			$detalle_promocion = toba::consulta_php('co_actas')->get_detalle_alumno_comision($alumno['alumno'], $this->s__datos_acta_original['comision'], acta::origen_promociones);
			if (!empty($detalle_promocion)) {
				$alumno['id_acta_promocion'] = $detalle_promocion['id_acta'];
				$alumno['instancia_promocion'] = 1;
				$alumno['acta_promocion_cerrada'] = 1;

				$alumno['escala_nota_promocion'] = $detalle_promocion['escala_nota'];
				$alumno['nota_promocion'] = $detalle_promocion['nota'];
				$alumno['resultado_promocion'] = $detalle_promocion['resultado'];
			}

			$this->cn()->agregar_detalle($alumno);

			// Se indica que el detalle para el alumno se insertó durante la operación. Al desasociarlo del acta se eliminará.
			$this->s__alumnos_agregados[$alumno['alumno']] = true;
		}
		$this->set_alumnos_asociados();
	}

	function agregar_alumno($alumno)
	{
		$this->cn()->validar_alumno($alumno['alumno'], $this->s__datos_acta_original['comision'], comision::inst_cursar);

		// Se completa el acta de cursada con el valor ficticio.
		$alumno['id_acta_cursada'] = $this->id_acta_ficticia;

		// Se completan los datos del detalle.
		$alumno['instancia_cursada'] = 1;
		$alumno['escala_nota_cursada'] = $this->cn()->get_escala_notas_comision_instancia($this->s__datos_acta_original['comision'], comision::inst_cursar);

		$condiciones = array('alumno' => $alumno['alumno']);
		if ($this->cn()->existe_detalle($condiciones)) {
			$id_detalle = $this->cn()->get_id_detalle($condiciones);
			$this->cn()->modificar_detalle($id_detalle, $alumno);
		} else {
			$alumno['inscripto'] = comunes::no;
			// Se obtiene el plan-versión del alumno a la fecha de inicio del período lectivo de la comisión.
			$alumno['plan_version'] = toba::consulta_php('co_alumnos')->get_plan_version($alumno['alumno'], $this->s__datos_acta_original['fecha_inicio']);
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
			$this->cn()->modificar_columna_detalle($alumno, 'id_acta_cursada', null);
		}

		$this->set_alumnos_asociados();
	}

	//-----------------------------------------------------------------------------------
	//---- form_acta --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_acta(form_acta $form)
	{
		$datos_form = array();

		// Número de Acta: deberá estar visible sólo si cur_carga_manual_nro_acta_regulares = S.
		if (guarani::parametros()->get_valor('cur_carga_manual_nro_acta_regulares') == comunes::no) {
			$form->desactivar_efs(array('nro_acta'));
		} else {
			$datos_form['nro_acta'] = $this->s__nro_acta;
		}

		if (isset($this->s__documento)) {
			$datos_form['documento'] = $this->s__documento;
		}
		$datos_form['observaciones'] = $this->s__observaciones;

		$form->set_datos($datos_form);
	}

	function evt__form_acta__modificacion($datos)
	{
		if (isset($datos['documento'])) {
			$this->s__documento = $datos['documento'];
		}

		if (isset($datos['nro_acta'])) {
			$this->s__nro_acta = $datos['nro_acta'];
		}

		$this->s__observaciones = $datos['observaciones'];
	}

}
?>