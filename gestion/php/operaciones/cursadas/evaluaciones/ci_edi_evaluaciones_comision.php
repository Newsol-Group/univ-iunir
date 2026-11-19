<?php
/**
 *
 * 
 * @package		Cursadas
 * @subpackage	Evaluaciones
 * 
 */
class ci_edi_evaluaciones_comision extends guarani_pers_ci
{
	protected $datos_temp;
	protected $datos_temp_instancias;
	protected $datos_temp_eval_rel;
	
	// Componente Visual de Información.
	protected $ev_info;
	protected $max_chars_por_col = 100;

	function ini()
	{
		$datos = $this->cn()->entidad()->get_datos_evaluacion();

		if (isset($datos['estado']) && ($datos['estado'] == comunes::estado_cerrado)) {
			$this->dep('datos_generales')->set_solo_lectura();
			$this->dep('evaluaciones_relacionadas')->set_solo_lectura();
			$this->dep('instancias')->set_solo_lectura();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		$entidad = $this->controlador()->get_entidad_preseleccionada();
		$datos = $this->cn()->get_listado_comisiones_por_entidad($entidad);
		$this->ev_info()->set_datos(current($datos));
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_max_chars_por_columna($this->max_chars_por_col);
			$this->ev_info->set_columnas_a_mostrar(array('nombre', 'nombre_turno', 'nombre_modalidad', 'anio_academico_periodo', 'nombre_periodo', 'codigo_y_nombre_actividad', 'nombre_ubicacion'));
			$this->ev_info->set_columnas_fila(array('anio_academico_periodo'), 1);
			$this->ev_info->set_columnas_fila(array('nombre_periodo'), 2);
			$this->ev_info->set_columnas_fila(array('nombre'), 3);
			$this->ev_info->set_columnas_fila(array('nombre_turno'), 4);
			$this->ev_info->set_columnas_fila(array('nombre_modalidad'), 5);
			$this->ev_info->set_columnas_fila(array('codigo_y_nombre_actividad'), 6);
			$this->ev_info->set_columnas_fila(array('nombre_ubicacion'), 7);
			$this->ev_info->set_max_columnas_por_fila(1);
			$this->ev_info->set_etiqueta('nombre', 'Comisión');
			$this->ev_info->set_etiqueta('nombre_turno', 'Turno');
			$this->ev_info->set_etiqueta('nombre_modalidad', 'Modalidad');
			$this->ev_info->set_etiqueta('anio_academico_periodo', 'Año Académico');
			$this->ev_info->set_etiqueta('nombre_periodo', 'Período Lectivo');
			$this->ev_info->set_etiqueta('codigo_y_nombre_actividad', 'Actividad');
			$this->ev_info->set_etiqueta('nombre_ubicacion', 'Ubicación');
			$this->ev_info->set_td_etiqueta_width('100px');
		}
		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- datos_generales --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__datos_generales(toba_ei_formulario $form)
	{
		// Agrego ícono de utilería (pop-up notas)
		$icono_informacion = new icono_info_escala_nota();
		$form->ef('escala_nota')->agregar_icono_utileria($icono_informacion);

		$comision = $this->controlador()->get_comision_seleccionada();
		$propuestas = \toba::consulta_php('co_comisiones')->get_propuestas_comision($comision);
		$ids_propuestas = array_column($propuestas, "propuesta");
		
		$form->set_datos(['propuestas' => $ids_propuestas, 'escala_asignada' => 0]);
		
		if (isset($this->datos_temp)) {
			$this->datos_temp['propuestas'] = $ids_propuestas;
			$this->datos_temp['escala_asignada'] = $this->datos_temp['escala_nota'];
			$form->set_datos($this->datos_temp);
		} else {
			if (!$this->cn()->entidad()->es_nueva()) {
				$datos = $this->cn()->entidad()->get_datos_evaluacion();
				// Formateo adecuado de hora
				if (isset($datos['hora_inicio'])) {
					$datos['hora_inicio'] = substr($datos['hora_inicio'], 0, 5);
				}
				if (isset($datos['hora_fin'])) {
					$datos['hora_fin'] = substr($datos['hora_fin'], 0, 5);
				}
				
				$datos['propuestas'] = $ids_propuestas;
				$datos['escala_asignada'] = $datos['escala_nota'];
				$form->set_datos($datos);

				/* En caso de haber alumnos en la evaluación -> Sólo permito editar Nombre, Descripción y visible_al_alumno 
				por ticket #41864 */
				if ($this->cn()->entidad()->tiene_alumnos($datos['evaluacion'])) {
					$form->set_solo_lectura(array('evaluacion_tipo', 'escala_nota'));
				}
			}
		}
	}

	function evt__datos_generales__modificacion($datos)
	{
		$datos['estado'] = comunes::estado_abierto;
		$datos['entidad'] = $this->controlador()->get_entidad_preseleccionada();

		if (!$this->cn()->entidad()->es_nueva()) {
			$datos['evaluacion'] = $this->controlador()->get_evaluacion_seleccionada();
		}

		$this->datos_temp = $datos;
		$this->cn()->entidad()->set_datos_evaluacion($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- evaluaciones_relacionadas ----------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__evaluaciones_relacionadas(toba_ei_formulario $form)
	{
		if (isset($this->datos_temp_eval_rel)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp_eval_rel);
		} else {
			$datos = aplanar_matriz($this->cn()->entidad()->get_datos_evaluaciones_relacionadas(), 'evaluacion_padre');
			$form->set_datos(array('evaluacion_padre' => $datos));
		}
	}

	function evt__evaluaciones_relacionadas__modificacion($datos)
	{
		$this->datos_temp_eval_rel = $datos;
		$this->cn()->entidad()->set_datos_evaluaciones_relacionadas($datos['evaluacion_padre']);
	}

	//-----------------------------------------------------------------------------------
	//---- instancias -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__instancias(toba_ei_formulario $form)
	{
		if (isset($this->datos_temp_instancias)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp_instancias);
		} else {
			if ($this->cn()->entidad()->es_nueva()) {
				// Por defecto en el alta se mostrarán como seleccionadas aquellas que 
				// estén seleccionadas para la comisión de la evaluación
				$entidad = $this->controlador()->get_entidad_preseleccionada();
				$datos = $this->cn()->get_listado_comisiones_por_entidad($entidad);
				$comision = $datos[0]['comision'];

				$datos_instancias = $this->cn()->get_instancias_comision($comision);
				$instancias = aplanar_matriz($datos_instancias, 'instancia');
				$form->set_datos(array('instancia' => $instancias));
			} else {
				$datos = aplanar_matriz($this->cn()->entidad()->get_datos_instancias(), 'instancia');
				$form->set_datos(array('instancia' => $datos));
			}
		}
	}

	function evt__instancias__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_instancias($datos['instancia']);
		} catch (guarani_error $e) {
			$this->datos_temp_instancias = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	//-----------------------------------------------------------------------------------
	//---- combos -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_evaluaciones_relacionadas()
	{
		$entidad = $this->controlador()->get_entidad_preseleccionada();
		$evaluacion = $this->cn()->entidad()->get_codigo_evaluacion();
		$where = "	AND sga_evaluaciones_tipos.esencial = '" . comunes::si . "'
					AND sga_evaluaciones.evaluacion NOT IN
					(	SELECT 	sga_evaluaciones_relacion.evaluacion_hijo AS evaluacion
						FROM 	sga_evaluaciones_relacion 
					)
				";
		return toba::consulta_php('co_evaluaciones')->get_evaluaciones_relacionadas($entidad, $evaluacion, $where);
	}

}