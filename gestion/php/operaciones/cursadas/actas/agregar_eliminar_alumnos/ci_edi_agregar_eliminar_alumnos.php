<?php
/**
 *
 *
 * @package Cursadas
 * @subpackage Actas
 * 
 */
class ci_edi_agregar_eliminar_alumnos extends guarani_pers_ci
{
	// Evaluación y acta seleccionadas.
	protected $s__evaluacion;
	protected $s__acta;
	protected $s__datos_acta;
	protected $s__instancia;
	
	// Nombres de los campos a modificar en los detalles.
	protected $s__campo_acta;
	protected $s__campo_instancia;
	protected $s__campo_escala_nota;
	
	// Alumnos insertados en 'sga_eval_detalle_cursadas' durante la operación.
	protected $s__alumnos_agregados;
	
	// Mensaje de confirmación para la eliminación de alumnos asociados al acta.
	protected $indx_msg_confirmacion = '800_eliminar_alumno_acta';
	
	// Componente Visual de Información de Datos de Actas.
	protected $ev_info_acta;

	function ini()
	{
		$this->s__evaluacion = $this->controlador()->get_evaluacion();
		$this->s__acta = $this->controlador()->get_acta_seleccionada();
		$this->s__datos_acta = $this->controlador()->get_datos_acta();

		// El conjunto de campos a modificar en los detalles depende de la instancia a la cual pertenece el acta.
		if ($this->s__datos_acta['origen'] == acta::origen_regulares) {
			$this->s__instancia = comision::inst_cursar;
			$this->s__campo_acta = 'id_acta_cursada';
			$this->s__campo_instancia = 'instancia_cursada';
			$this->s__campo_escala_nota = 'escala_nota_cursada';
		} elseif ($this->s__datos_acta['origen'] == acta::origen_promociones) {
			$this->s__instancia = comision::inst_promocionar;
			$this->s__campo_acta = 'id_acta_promocion';
			$this->s__campo_instancia = 'instancia_promocion';
			$this->s__campo_escala_nota = 'escala_nota_promocion';
		}
		
		if (guarani::parametros()->get_valor('cur_multiples_actas_comision') == comunes::no) {
			$this->dep('alumnos')->set_eliminar_inscriptos(false);
		}

		$msj = guarani_mensajes::get($this->indx_msg_confirmacion, array($this->s__datos_acta['nro_acta']));
		$this->dep('alumnos')->set_msg_confirmacion_eliminar_alumnos($msj);

		// Se setean los filtros que necesita el selector de alumnos.
		$this->dep('alumnos')->set_filtros($this->s__evaluacion, $this->s__acta, $this->s__instancia);
		$this->set_alumnos_asociados();
	}

	function resetear_vars()
	{
		unset($this->s__evaluacion);
		unset($this->s__acta);
		unset($this->s__datos_acta);
		unset($this->s__instancia);

		unset($this->s__campo_acta);
		unset($this->s__campo_instancia);
		unset($this->s__campo_escala_nota);

		unset($this->s__alumnos_agregados);

		$this->dep('alumnos')->evt__cancelar();
	}

	//--------------------------------------------------------------------------------------------------------------------
	//---- PANTALLA INICIAL ----------------------------------------------------------------------------------------------
	//--------------------------------------------------------------------------------------------------------------------

	function conf__pant_inicial()
	{
		// Configuración del ev del Acta
		$this->ev_info_acta()->set_datos($this->s__datos_acta);
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

			$this->ev_info_acta->set_etiqueta('nro_acta', 'Acta');
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
		$condiciones = array($this->s__campo_acta => $this->s__acta);
		$this->dep('alumnos')->set_alumnos_asociados($this->cn()->get_detalles($condiciones));
	}

	function agregar_alumno_comision($alumno)
	{
		$id_detalle = $this->cn()->get_id_detalle($alumno);

		// Se completa el acta (de cursada o promoción) con el valor ficticio.
		$alumno[$this->s__campo_acta] = $this->s__acta;

		$this->cn()->modificar_detalle($id_detalle, $alumno);
		$this->set_alumnos_asociados();
	}

	function agregar_alumno($alumno)
	{
		$this->cn()->validar_alumno($alumno['alumno'], $this->s__datos_acta['comision'], $this->s__instancia);

		// Se completan los campos del detalle.
		$alumno[$this->s__campo_acta] = $this->s__acta;
		$alumno[$this->s__campo_instancia] = 1;
		$alumno[$this->s__campo_escala_nota] = $this->cn()->get_escala_notas_comision_instancia($this->s__datos_acta['comision'], $this->s__instancia);

		$condiciones = array('alumno' => $alumno['alumno']);
		if ($this->cn()->existe_detalle($condiciones)) {
			$id_detalle = $this->cn()->get_id_detalle($condiciones);
			$this->cn()->modificar_detalle($id_detalle, $alumno);
		} else {
			$alumno['inscripto'] = comunes::no;
			// Se obtiene el plan-versión del alumno a la fecha de inicio del período lectivo de la comisión.
			$alumno['plan_version'] = toba::consulta_php('co_alumnos')->get_plan_version($alumno['alumno'], $this->s__datos_acta['fecha_inicio']);
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
			$this->cn()->modificar_columna_detalle($alumno, $this->s__campo_acta, null);
		}

		$this->set_alumnos_asociados();
	}

	function eliminar_alumno_disponible($alumno)
	{
		// Se debe marcar como eliminado porque sino no detecta cambios al guardar cuando sólo se eliminó un alumno disponible.
		$this->cn()->set_eliminado();
		// No es posible sólo borrar el detalle sobre el DT porque el cuadro de disponibles se carga de la BD (no tendría impacto).
		// Si sólo se elimina el detalle en la BD no se pueden luego guardar otros cambios porque se produce un error de sincronización.
		$this->cn()->modificar_acta($this->s__datos_acta);
		guarani::act('act_actas')->eliminar_eval_detalle_cursada($this->s__evaluacion, $alumno);
		$this->cn()->unset_eliminado();
		$this->controlador()->set_pantalla('informacion');
	}
}
?>