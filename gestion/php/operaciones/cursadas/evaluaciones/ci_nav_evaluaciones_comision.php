<?php
/**
 *
 * 
 * @package		Cursadas
 * @subpackage	Evaluaciones
 * 
 */
class ci_nav_evaluaciones_comision extends ci_nav_con_preseleccion
{
	protected $s__preseleccion;
	protected $s__evaluacion;
	protected $s__comision;
	
	// Mensajes
	protected $msg_eof_cuadro = '800_eof_cuadro_sinfiltro';
	protected $msg_eval_cerrada = '800CUR_evaluacion_cerrada';
	protected $msg_no_eliminar_padre = '800CUR_no_eliminar_eval_padre';
	protected $msg_eliminar_eval_con_alumnos = '800CUR_eliminar_eval_con_alumnos';
	protected $msg_reabrir_eval_ok = '800CUR_evaluacion_reabrir_ok';
	protected $msg_reabrir_eval_error = '800CUR_evaluacion_reabrir_error';
	
	// Datos de las evaluaciones creadas
	protected $datos;
	
	/**
	 * Componente Visual de Información.
	 * @var ev_info_datos
	 */
	protected $ev_info;
	protected $max_chars_por_col = 100;

	function seleccionar_preseleccion($seleccion)
	{
		$this->set_preseleccion($seleccion);
		$this->set_pantalla('seleccion');
	}

	function set_preseleccion($seleccion)
	{
		$this->s__preseleccion = $seleccion;
	}

	function get_entidad_preseleccionada()
	{
		$preseleccion = $this->s__preseleccion;
		return $preseleccion['entidad'];
	}

	function get_evaluacion_seleccionada()
	{
		$seleccion = $this->s__evaluacion;
		return $seleccion['evaluacion'];
	}

	function get_comision_seleccionada()
	{
		return $this->s__comision;
	}
	
	function ini__operacion()
	{
		$this->set_concepto_preseleccion('Comisiones');
	}

	//-----------------------------------------------------------------------------------
	//---- filtro_preseleccion ----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_preseleccion(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos_preseleccion)) {
			$filtro->set_datos($this->s__datos_preseleccion);
		}

		$filtro->columna('anio_academico')->set_condicion_fija('es_igual_a');
		$filtro->columna('periodos_nombre')->set_condicion_fija('es_igual_a');
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_preseleccion ----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_preseleccion(toba_ei_cuadro $cuadro)
	{
		if (isset($this->s__datos_preseleccion)) {
			$datos = $this->cn()->get_listado_comisiones($this->s__filtro_preseleccion);
			$cuadro->set_datos($datos);
			$msj = $this->get_msg_eof_preseleccion(isset($this->s__filtro_preseleccion));
			$cuadro->set_eof_mensaje($msj);
		} else {
			// Debe filtrar para obtener datos
			$cuadro->set_eof_mensaje(guarani_mensajes::get($this->msg_eof_cuadro));
		}
	}

	///-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__seleccion(toba_ei_pantalla $pantalla)
	{
		$entidad = $this->get_entidad_preseleccionada();
		$datos = $this->cn()->get_listado_comisiones_por_entidad($entidad);
		$this->s__comision = $datos[0]['comision'];
		$this->ev_info()->set_datos(current($datos));
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
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
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$this->datos = $this->cn()->get_listado_evaluaciones_por_entidad($this->get_entidad_preseleccionada());

		foreach ($this->datos as $key => $estado) {
			if ($this->datos[$key]['estado'] == comunes::estado_abierto) {
				$this->datos[$key]['estado_semaforo'] = toba_recurso::imagen_proyecto('iconos_estados/verde.png', true, null, null, 'Evaluación Abierta', null);
			} elseif ($this->datos[$key]['estado'] == comunes::estado_cerrado) {
				$this->datos[$key]['estado_semaforo'] = toba_recurso::imagen_proyecto('iconos_estados/rojo.png', true, null, null, 'Evaluación Cerrada', null);
			}
		}

		$cuadro->set_datos($this->datos);
		$cuadro->set_eof_mensaje(guarani_mensajes::get('800_eof_cuadro', array('Evaluaciones')));
	}

	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__evaluacion = $seleccion;
		$this->cn()->cargar($seleccion);
		$this->set_pantalla('edicion');
	}

	function conf_evt__cuadro__eliminar(toba_evento_usuario $evento, $fila)
	{
		if (isset($this->datos)) {
			if (toba::consulta_php('co_evaluaciones')->tiene_notas_cargadas($this->datos[$fila]['evaluacion'])) {
				// Tiene notas cargadas
				$mensaje = guarani_mensajes::get($this->msg_eliminar_eval_con_alumnos, array($this->datos[$fila]['nombre']));
				$evento->set_msg_confirmacion($mensaje);
			} else {
				$mensaje = guarani_mensajes::get($this->indx_msg_confirmar_eliminar, array('la Evaluación'));
				$evento->set_msg_confirmacion($mensaje);
			}
		}
	}

	function evt__cuadro__eliminar($seleccion)
	{
		$this->cn()->cargar($seleccion, true);

		if (toba::consulta_php('co_evaluaciones')->es_padre_de_evaluacion($seleccion['evaluacion'])) {
			// ES PADRE DE OTRA EVALUACIÓN -> NO SE PUEDE ELIMINAR
			$this->cn()->resetear();
			$mje = guarani_mensajes::get($this->msg_no_eliminar_padre);
			throw guarani::error($mje);
		} else {
			$this->cn()->entidad()->eliminar();
			$this->cn()->resetear();
		}
	}

	function evt__cuadro__reabrir($seleccion)
	{
		$this->cn()->cargar($seleccion, true);

		if (toba::consulta_php('co_comisiones')->tiene_acta_cursada_promocion_cerrada($this->s__comision) == false) {
			$datos = array('estado' => comunes::estado_abierto,'evaluacion'=>$seleccion['evaluacion'], 'fecha_cierre' => NULL);
			$this->cn()->reabrir_evaluacion($datos);
			$this->cn()->resetear();
			$mje = guarani_mensajes::get($this->msg_reabrir_eval_ok, array($seleccion['nombre']));
			$this->informar_msg($mje, 'info');
			
			
		} else {
			//mensaje tiene_acta_cursada_promocion_cerrada
			$this->cn()->resetear();
			$mje = guarani_mensajes::get($this->msg_reabrir_eval_error, array($seleccion['nombre']));
			throw guarani::error($mje);
		}
	}

	function conf_evt__cuadro__reabrir(toba_evento_usuario $evento, $fila)
	{
		if ($this->datos[$fila]['estado'] == comunes::estado_cerrado) {
			$evento->mostrar();
		} else {
			$evento->ocultar();
		}
	}

}
?>