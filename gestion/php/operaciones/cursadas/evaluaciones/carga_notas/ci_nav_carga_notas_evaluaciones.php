<?php
/**
	*
	* @package     Cursadas
	* @subpackage     Evaluaciones
	* 
	*/
class ci_nav_carga_notas_evaluaciones extends ci_navegacion
{
	// Comisión y evaluación seleccionadas.
	protected $s__comision_seleccionada;
	protected $s__evaluacion_seleccionada;
	protected $s__datos_evaluacion;
	
	// Componente Visual de Información de datos de la comisión.
	protected $ev_info_comision;

	//-----------------------------------------------------------------------------------
	//---- operaciones auxiliares -------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function seleccionar_comision($seleccion)
	{
		$this->cn()->cargar_atributos_parametros($seleccion['comision']);
		$this->s__comision_seleccionada = $seleccion;
		$this->set_pantalla('seleccion_evaluacion');
	}
	
	/**
	 * Retorna la entidad de la comisión seleccionada. 
	 */
	function get_comision_seleccionada()
	{
		return $this->s__comision_seleccionada['entidad'];
	}

	/**
	 * Retorna el código de la evaluación seleccionada.
	 */
	function get_evaluacion_seleccionada()
	{
		return $this->s__evaluacion_seleccionada['evaluacion'];
	}

	function get_datos_evaluacion()
	{
		return $this->s__datos_evaluacion;
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCIÓN DE COMISIÓN 
	//-----------------------------------------------------------------------------------

	function conf__seleccion_comision(toba_ei_pantalla $pantalla)
	{
		$this->cn()->resetear_atributos_parametros();
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_comision --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_comision(toba_ei_cuadro $cuadro)
	{
		if (!isset($this->s__filtro)) {
			$msg_eof = guarani_mensajes::get($this->indx_msg_eof . '_sinfiltro');
		} else {
			$msg_eof = $this->get_msg_eof('Comisiones');
			$filtro_activas = "sga_comisiones.estado = '" . comunes::estado_activo . "'";
			$filtro = empty($this->s__filtro) ? $filtro_activas : $this->s__filtro . ' AND ' . $filtro_activas;
			$datos = toba::consulta_php('co_comisiones')->get_listado($filtro);
			$cuadro->set_datos($datos);
		}
		$cuadro->set_eof_mensaje($msg_eof);
	}

	function conf_evt__cuadro_comision__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dependencia('cuadro_comision')->get_datos();
		$filtro = "sga_evaluaciones_tipos.automatica = '" . comunes::no . "'";
		$cant_eval_comision = toba::consulta_php('co_evaluaciones')->get_cant_evaluaciones_para_comision($datos[$fila]['comision'], $filtro);
		if ($cant_eval_comision == 0) {
			$evento->anular();
		} else {
			$evento->activar();
		}
	}

	function evt__cuadro_comision__seleccion($seleccion)
	{
		$this->seleccionar_comision($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('anio_academico')->set_condicion_fija('es_igual_a');
		$filtro->columna('periodos_nombre')->set_condicion_fija('es_igual_a');
		$filtro->columna('actividad_codigo')->set_condicion_fija('contiene');
		$filtro->columna('actividad_nombre')->set_condicion_fija('contiene');
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCIÓN DE EVALUACIÓN 
	//-----------------------------------------------------------------------------------

	function conf__seleccion_evaluacion(toba_ei_pantalla $pantalla)
	{
		// Se obtienen los datos de la comisión seleccionada a partir de su código de entidad.
		$filtro = "sga_comisiones.entidad = {$this->get_comision_seleccionada()}";
		$comision = current(toba::consulta_php('co_comisiones')->get_listado($filtro));
		$this->ev_info_comision()->set_datos($comision);
	}

	function ev_info_comision()
	{
		if (!isset($this->ev_info_comision)) {
			$this->ev_info_comision = guarani::ev_info_datos();
			$this->ev_info_comision->set_columnas_a_mostrar(array('nombre', 'nombre_turno', 'nombre_modalidad', 'anio_academico_periodo', 'nombre_periodo', 'codigo_y_nombre_actividad', 'nombre_ubicacion'));
			$this->ev_info_comision->set_columnas_fila(array('nombre', 'nombre_turno', 'nombre_modalidad'), 1);
			$this->ev_info_comision->set_columnas_fila(array('anio_academico_periodo', 'nombre_periodo'), 2);
			$this->ev_info_comision->set_columnas_fila(array('codigo_y_nombre_actividad', 'nombre_ubicacion'), 3);
			$this->ev_info_comision->set_max_columnas_por_fila(3);
			$this->ev_info_comision->set_etiqueta('nombre', 'Comisión');
			$this->ev_info_comision->set_etiqueta('nombre_turno', 'Turno');
			$this->ev_info_comision->set_etiqueta('nombre_modalidad', 'Modalidad');
			$this->ev_info_comision->set_etiqueta('anio_academico_periodo', 'Año Académico');
			$this->ev_info_comision->set_etiqueta('nombre_periodo', 'Período Lectivo');
			$this->ev_info_comision->set_etiqueta('codigo_y_nombre_actividad', 'Actividad');
			$this->ev_info_comision->set_etiqueta('nombre_ubicacion', 'Ubicación');
		}
		return $this->ev_info_comision;
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCIÓN DE ALUMNOS 
	//-----------------------------------------------------------------------------------

	function conf__seleccion_alumnos()
	{
		$this->hay_cambios = $this->cn()->hay_cambios_seleccion_alumnos();
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA INGRESO DE NOTAS 
	//-----------------------------------------------------------------------------------

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		$this->hay_cambios = $this->cn()->hay_cambios();
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_evaluacion ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_evaluacion(toba_ei_cuadro $cuadro)
	{
		$datos_evaluaciones = toba::consulta_php('co_evaluaciones')->get_evaluaciones_por_entidad($this->get_comision_seleccionada());

		foreach ($datos_evaluaciones as $clave => $valor) {
			// Formateo del estado como semáforo.
			if ($valor['estado'] == comunes::estado_abierto) {
				$datos_evaluaciones[$clave]['estado_semaforo'] = toba_recurso::imagen_proyecto('iconos_estados/verde.png', true, null, null, 'Evaluación Abierta', null);
			} elseif ($valor['estado'] == comunes::estado_cerrado) {
				$datos_evaluaciones[$clave]['estado_semaforo'] = toba_recurso::imagen_proyecto('iconos_estados/rojo.png', true, null, null, 'Evaluación Cerrada', null);
			}
			// Formateo de la hora de inicio sin segundos.
			if (isset($valor['hora_inicio'])) {
				$datos_evaluaciones[$clave]['hora_inicio'] = substr($valor['hora_inicio'], 0, 5);
			}
		}

		$cuadro->set_datos($datos_evaluaciones);

		$msj_eof = $this->get_msg_eof('Evaluaciones');
		$cuadro->set_eof_mensaje($msj_eof);
	}

	function evt__cuadro_evaluacion__seleccion($seleccion)
	{
		$this->s__evaluacion_seleccionada = $seleccion;
		$this->s__datos_evaluacion = toba::consulta_php('co_evaluaciones')->get_datos_evaluacion_comision($this->get_evaluacion_seleccionada());
		$this->cn()->cargar($seleccion);
		$this->set_pantalla('seleccion_alumnos');
	}

	function conf_evt__cuadro_evaluacion__seleccion(toba_evento_usuario $evento, $fila)
	{
		// Si la evaluación está cerrada no se deberá mostrar el botón de selección.
		$datos = $this->dep('cuadro_evaluacion')->get_datos();
		if ($datos[$fila]['estado'] == comunes::estado_cerrado) {
			$evento->anular();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cancelar_evaluacion()
	{
		$this->resetear_vars();
		$this->set_pantalla('seleccion_comision');
	}

	function evt__cancelar_alumnos()
	{
		$this->dep('selector_alumnos')->resetear_vars();
		$this->cn()->resetear();
		$this->set_pantalla('seleccion_evaluacion');
	}

	function evt__cancelar()
	{
		$this->set_pantalla('seleccion_alumnos');
	}

	/**
	 * Resetea las variables de sesión del CI.
	 */
	function resetear_vars()
	{
		unset($this->s__comision_seleccionada);
		unset($this->s__evaluacion_seleccionada);
		unset($this->s__datos_evaluacion);
	}

	function evt__notas()
	{
		$this->set_pantalla('edicion');
	}

	function evt__guardar()
	{
		$this->cn()->sincronizar();
		toba::notificacion()->info('Se guardó correctamente.');
	}
	
	function evt__finalizar()
	{
		$this->resetear_vars();
		$this->dep('selector_alumnos')->resetear_vars();
		$this->set_pantalla('seleccion_comision');
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$msj_confirmacion = guarani_mensajes::get('800_abandono_edicion');
		$msj_sin_cambios = guarani_mensajes::get('800_no_hubo_cambios');
		$msj_sin_seleccion_alumnos = guarani_mensajes::get('800_sin_seleccion_alumnos');

		$pantalla = $this->get_id_pantalla();
		switch ($pantalla) {
			case 'seleccion_alumnos':
				if ($this->hay_cambios) {
					$confirmar = 'var confirmar = true;';
				} else {
					$confirmar = 'var confirmar = false;';
				}
				echo "    {$this->objeto_js}.evt__notas = function() {
							$confirmar
							if (! confirmar) {
								alert('$msj_sin_seleccion_alumnos');
								return false;
							}
							return true;
						}
					";
				break;
			case 'edicion':
				if ($this->hay_cambios) {
					$confirmar = 'var confirmar = true;';
				} else {
					$confirmar = 'var confirmar = toba.hay_cambios();';
				}
				echo "    {$this->objeto_js}.evt__guardar = function() {
							$confirmar
							if (! confirmar) {
								alert('$msj_sin_cambios');
								return false;
							}
							return true;
						}
						{$this->objeto_js}.evt__cancelar = function() {
							$confirmar
							if (confirmar) {
								resp = confirm('$msj_confirmacion');
								if (! resp) {
									return false;
								}
							}
                            return true;
                        }
                        toba.set_callback_menu({$this->objeto_js}.evt__cancelar);
                    ";
                break;
        }
    }

}
?>