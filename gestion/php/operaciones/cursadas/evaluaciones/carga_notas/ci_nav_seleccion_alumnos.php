<?php
/**
 *
 * @package 	Cursadas
 * @subpackage 	Evaluaciones
 * 
 */
class ci_nav_seleccion_alumnos extends guarani_pers_ci
{
	protected $s__datos_evaluacion;
	
	// Alumnos insertados en 'sga_eval_detalle' durante la operación.
	protected $s__alumnos_agregados;
	
	/**
	 * Componente Visual de Información de datos de la evaluación.
	 * @var ev_info_datos
	 */
	protected $ev_info_evaluacion;

	function ini()
	{
		if (!isset($this->s__datos_evaluacion)) {
			$this->s__datos_evaluacion = $this->controlador()->get_datos_evaluacion();
		}

		$this->dep('alumnos')->set_deshabilitar_eliminar_alumnos_disponibles();

		// Se setean los filtros que necesita el selector de alumnos.
		$this->dep('alumnos')->set_filtros($this->s__datos_evaluacion['evaluacion'], null, null, null, $this->s__datos_evaluacion['comision']);
		$this->set_alumnos_asociados();
	}

	function resetear_vars()
	{
		unset($this->s__datos_evaluacion);
		unset($this->s__alumnos_agregados);
		$this->dep('alumnos')->evt__cancelar();
	}

	//--------------------------------------------------------------------------------------------------------------------
	//---- PANTALLA INICIAL ----------------------------------------------------------------------------------------------
	//--------------------------------------------------------------------------------------------------------------------

	function conf__pant_inicial()
	{
		// Configuración del Componente Visual de Información de Datos de Evaluaciones.
		$datos = $this->s__datos_evaluacion;

		$nombre_escala = (!empty($datos['nombre_escala_nota'])) ? $datos['nombre_escala_nota'] : '';
		$datos['icono_info_escala_notas'] = $nombre_escala . ' ' . $this->get_html_icono_info_escala_notas();

		if (guarani::co('co_evaluaciones')->es_hijo_de_evaluacion($datos['evaluacion'])) {
			$datos['icono_info_eval_relacionadas'] = $this->get_html_icono_info_eval_relacionadas();
		}

		$this->ev_info_evaluacion()->set_datos($datos);
	}

	function ev_info_evaluacion()
	{
		if (!isset($this->ev_info_evaluacion)) {
			$this->ev_info_evaluacion = guarani::ev_info_datos();
			$this->ev_info_evaluacion->set_columnas_a_mostrar(array('nombre_comision', 'nombre_turno', 'nombre_modalidad', 'anio_academico_periodo', 'nombre_periodo', 'codigo_y_nombre_actividad', 'nombre_ubicacion', 'nombre_evaluacion', 'fecha_visual', 'icono_info_escala_notas', 'icono_info_eval_relacionadas'));

			$this->ev_info_evaluacion->set_columnas_fila(array('nombre_comision', 'nombre_turno', 'nombre_modalidad'), 1);
			$this->ev_info_evaluacion->set_columnas_fila(array('anio_academico_periodo', 'nombre_periodo'), 2);
			$this->ev_info_evaluacion->set_columnas_fila(array('codigo_y_nombre_actividad', 'nombre_ubicacion'), 3);
			$this->ev_info_evaluacion->set_columnas_fila(array('nombre_evaluacion', 'fecha_visual'), 4);
			$this->ev_info_evaluacion->set_columnas_fila(array('icono_info_escala_notas', 'icono_info_eval_relacionadas'), 5);

			$this->ev_info_evaluacion->set_max_columnas_por_fila(3);
			$this->ev_info_evaluacion->set_etiqueta('nombre_comision', 'Comisión');
			$this->ev_info_evaluacion->set_etiqueta('nombre_turno', 'Turno');
			$this->ev_info_evaluacion->set_etiqueta('nombre_modalidad', 'Modalidad');
			$this->ev_info_evaluacion->set_etiqueta('anio_academico_periodo', 'Año Académico');
			$this->ev_info_evaluacion->set_etiqueta('nombre_periodo', 'Período Lectivo');
			$this->ev_info_evaluacion->set_etiqueta('codigo_y_nombre_actividad', 'Actividad');
			$this->ev_info_evaluacion->set_etiqueta('nombre_ubicacion', 'Ubicación');
			$this->ev_info_evaluacion->set_etiqueta('nombre_evaluacion', 'Nombre');
			$this->ev_info_evaluacion->set_etiqueta('fecha_visual', 'Fecha');
			$this->ev_info_evaluacion->set_etiqueta('icono_info_escala_notas', 'Escala de Notas');
			$this->ev_info_evaluacion->set_etiqueta('icono_info_eval_relacionadas', 'Evaluaciones Relacionadas');
		}
		return $this->ev_info_evaluacion;
	}

	//-----------------------------------------------------------------------------------
	//---- alumnos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function set_alumnos_asociados()
	{
		$condiciones = array('agregado' => true);
		$ordenamiento = guarani::parametros()->get_valor('cur_orden_detalle_actas');
		if ($ordenamiento == 1) {
			$columna = 'legajo';
		} elseif ($ordenamiento == 2) {
			$columna = 'apellido_y_nombre';
		} elseif ($ordenamiento == 3) {
			$columna = 'documento_principal';
		}
		$this->dep('alumnos')->set_alumnos_asociados($this->cn()->get_detalles($condiciones, $columna));
	}

	function agregar_alumno_comision($alumno)
	{
		// Se indica que el alumno ha sido agregado.
		$alumno['agregado'] = true;

		$condiciones = array('alumno' => $alumno['alumno']);
		if ($this->cn()->existe_detalle($condiciones)) {
			$id_detalle = $this->cn()->get_id_detalle($condiciones);
			$this->cn()->modificar_detalle($id_detalle, $alumno);
		} else {
			$alumno['escala_nota'] = $this->s__datos_evaluacion['escala_nota'];
			$alumno['fecha'] = $this->s__datos_evaluacion['fecha'];
			$this->cn()->agregar_detalle($alumno);
			// Se indica que el detalle para el alumno se insertó durante la operación. Al desasociarlo se eliminará.
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
		} else { // Sino, se desasocia.
			$this->cn()->modificar_columna_detalle($alumno, 'agregado', false);
		}

		$this->set_alumnos_asociados();
	}

	//-----------------------------------------------------------------------------------
	//---- operaciones auxiliares -------------------------------------------------------
	//-----------------------------------------------------------------------------------
	/**
	 * Retorna el HTML de un vínculo (con imagen) a la operación que muestra el detalle de una escala de notas.
	 */
	function get_html_icono_info_escala_notas()
	{
		$vinculo = toba::vinculador()->get_url(toba_proyecto::get_id(), '37000054', array('escala' => $this->s__datos_evaluacion['escala_nota']), array('celda_memoria' => 'escala_notas'));
		$javascript = "abrir_popup('info','$vinculo',{'width':'450','height':'420'})";

		$salida = "<a class='icono-utileria' href='#' onclick=\"$javascript\">";
		$salida .= toba_recurso::imagen_toba('info_chico.gif', true, null, null, 'Ver detalle de escala');
		$salida .= '</a>';

		return $salida;
	}

	/**
	 * Retorna el HTML de un vínculo (con imagen) a la operación que muestra el detalle de resultados en evaluaciones relacionadas.
	 */
	function get_html_icono_info_eval_relacionadas()
	{
		$vinculo = toba::vinculador()->get_url(toba_proyecto::get_id(), '41000126', array('evaluacion' => $this->s__datos_evaluacion['evaluacion']), array('celda_memoria' => 'evaluacion'));
		$javascript = "abrir_popup('info','$vinculo',{'width':'1000','height':'600'})";

		$salida = "<a class='icono-utileria' href='#' onclick=\"$javascript\">";
		$salida .= toba_recurso::imagen_toba('info_chico.gif', true, null, null, 'Ver evaluaciones relacionadas');
		$salida .= '</a>';

		return $salida;
	}

}
?>