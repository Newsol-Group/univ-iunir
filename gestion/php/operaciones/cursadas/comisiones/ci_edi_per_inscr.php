<?php
/**
 *
 *
 * @package 	Cursadas
 * @subpackage 	Comisiones
 * 
 */
class ci_edi_per_inscr extends guarani_pers_ci
{
	protected $indx_msg_eof = '800_eof_cuadro';
	protected $indx_msg_confirmar_eliminar = '800_confirmar_elimin';
	
	protected $s__seleccion_periodo_insc;
	protected $datos_periodos_insc_temp;
	protected $datos_excepcion_perlect;
	
	protected $s__es_edicion;

	//-----------------------------------------------------------------------------------
	//---- Métodos auxiliares -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__edicion_per_inscr(toba_ei_pantalla $pantalla)
	{
		$ci_navegacion = $this->controlador()->controlador()->controlador();
		$ci_navegacion->evento('guardar')->desactivar();
		$ci_navegacion->evento('cancelar')->desactivar();
		$ci_navegacion->evento('limpiar')->desactivar();
	}

	function conf__edicion_excep_perlect(toba_ei_pantalla $pantalla)
	{
		$ci_navegacion = $this->controlador()->controlador()->controlador();
		$ci_navegacion->evento('guardar')->desactivar();
		$ci_navegacion->evento('cancelar')->desactivar();
		$ci_navegacion->evento('limpiar')->desactivar();
	}

	/**
	 * Verifica si actualmente se está agregando o editando una excepción para 
	 * un período de inscripción fecha asociado.
	 */
	function hay_excepcion_en_edicion()
	{
		if (isset($this->s__seleccion_periodo_insc)) {
			return true;
		} else {
			return false;
		}
	}

	function resetear_vars()
	{
		unset($this->s__seleccion_periodo_insc);
	}

	//-----------------------------------------------------------------------------------
	//---- seleccion_periodos -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__seleccion_periodos(toba_ei_cuadro $cuadro)
	{
		// Setea el mensaje eof del cuadro de fechas de períodos de inscripción.
		// Recupera el índice del mensaje de eof del cuadro del ci de navegación.
		$indx_msj = $this->indx_msg_eof;
		// Recupera la etiqueta enfatizada y plural del concepto asociado a la tabla de fechas de períodos de inscripción.
		$etiqueta = guarani_etiquetas::get_tabla('sga_periodos_inscripcion_fechas');
		$etiqueta = $etiqueta->get_enf('plural');
		$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj, array($etiqueta)));

		$periodos = $this->cn()->entidad()->get_datos_periodos_inscripcion();
		$cuadro->set_datos($periodos);
	}

	function evt__seleccion_periodos__seleccion($seleccion)
	{
		$this->s__seleccion_periodo_insc = current($seleccion);

		$excep = $this->cn()->entidad()->hay_per_insc_excep($this->s__seleccion_periodo_insc);
		$this->s__es_edicion = !is_null($excep);

		$this->set_pantalla('edicion_per_inscr');
	}

	//-----------------------------------------------------------------------------------
	//---- datos_excepcion_periodo ------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__datos_excepcion_periodo(toba_ei_formulario $form)
	{
            
		if (isset($this->datos_periodos_insc_temp)) {
			$this->datos_periodos_insc_temp['fecha_inicio'] = guarani_fecha::separar_fecha_hora($this->datos_periodos_insc_temp['fecha_inicio']);
			$this->datos_periodos_insc_temp['fecha_fin'] = guarani_fecha::separar_fecha_hora($this->datos_periodos_insc_temp['fecha_fin']);
			$this->datos_periodos_insc_temp['fecha_tope_bajas'] = guarani_fecha::separar_fecha_hora($this->datos_periodos_insc_temp['fecha_tope_bajas']);
			$this->controlador()->controlador()->set_hubo_cambios();
			$form->set_datos($this->datos_periodos_insc_temp);
		} else {
                   	$excep = $this->cn()->entidad()->hay_per_insc_excep($this->s__seleccion_periodo_insc);

			if ($this->s__es_edicion) {
				$this->cn()->entidad()->set_cursor_per_insc_excep($excep);
				$datos_per_excep = $this->cn()->entidad()->get_datos_periodo_insc_excep_seleccionado();

				// Se formatea la fecha y hora para el ef_editable_fecha_hora.
				if (!empty($datos_per_excep['fecha_inicio'])) {
					$datos_per_excep['fecha_inicio'] = guarani_fecha::separar_fecha_hora($datos_per_excep['fecha_inicio']);
				}
				if (!empty($datos_per_excep['fecha_fin'])) {
					$datos_per_excep['fecha_fin'] = guarani_fecha::separar_fecha_hora($datos_per_excep['fecha_fin']);
				}
				if (!empty($datos_per_excep['fecha_tope_bajas'])) {
					$datos_per_excep['fecha_tope_bajas'] = guarani_fecha::separar_fecha_hora($datos_per_excep['fecha_tope_bajas']);
				}
                               
				$form->set_datos($datos_per_excep);
			} else {
				// Por default traigo los datos de periodo. 
				 $datos_periodo_insc =toba::consulta_php('co_periodos_de_inscripcion')->get_datos_periodo_insc_fechas($this->s__seleccion_periodo_insc);
                                 
				$datos_per_excep['fecha_inicio'] =guarani_fecha::separar_fecha_hora($datos_periodo_insc['fecha_inicio']);
				$datos_per_excep['fecha_fin'] = guarani_fecha::separar_fecha_hora($datos_periodo_insc['fecha_fin']);
				$datos_per_excep['fecha_tope_bajas'] = guarani_fecha::separar_fecha_hora($datos_periodo_insc['fecha_tope_bajas']);
				$datos_per_excep['tipo_alumno'] = $datos_periodo_insc['tipo_alumno'];
				$datos_per_excep['tipo_cursado'] = $datos_periodo_insc['tipo_cursado'];
				$datos_per_excep['habilitado_interfaz'] = $datos_periodo_insc['habilitado_interfaz'];
				                                 
                                $form->set_datos($datos_per_excep);
			}
		}

		if (!$this->s__es_edicion) {
			$form->evento('eliminar')->anular();
		}

		if ($form->existe_evento('eliminar')) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_comisiones_excep_perinsc');
			$form->evento('eliminar')->set_msg_confirmacion(guarani_mensajes::get($this->indx_msg_confirmar_eliminar, array($etiqueta->get('la'))));
		}
	}

	function evt__datos_excepcion_periodo__confirmar($datos)
	{
		// Se formatea la fecha y hora recibida en el formato del ef_editable_fecha_hora.
		$datos['fecha_inicio'] = "{$datos['fecha_inicio'][0]} {$datos['fecha_inicio'][1]}";
		$datos['fecha_fin'] = "{$datos['fecha_fin'][0]} {$datos['fecha_fin'][1]}";
		$datos['fecha_tope_bajas'] = "{$datos['fecha_tope_bajas'][0]} {$datos['fecha_tope_bajas'][1]}";
		try {
			// Se verifica si se está en el caso de agregar para crear una nueva fila que con los datos.
			// En otro caso solo se setean los datos en la fila donde se encuentra posicionado actualmente el cursor.
			$cursor = $this->cn()->entidad()->get_cursor_per_insc_excep();
			if (!isset($cursor)) {
				// Agrega la fila correspondiente a la nueva excepción y setea el cursor en la misma.
				$this->cn()->entidad()->agregar_per_insc_excep($this->s__seleccion_periodo_insc);
			}
			$this->cn()->entidad()->set_datos_periodo_insc_excep($datos);
			$this->cn()->entidad()->unset_cursor_per_insc_excep();
			unset($this->s__seleccion_periodo_insc);
		} catch (guarani_error $e) {
			$this->datos_periodos_insc_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
		$this->set_pantalla('seleccion_per_inscr');
	}

	function evt__datos_excepcion_periodo__cancelar()
	{
		// Se canceló la edición de una excepción.
		$this->cn()->entidad()->unset_cursor_per_insc_excep();
		unset($this->s__seleccion_periodo_insc);
		$this->set_pantalla('seleccion_per_inscr');
	}

	function evt__datos_excepcion_periodo__eliminar()
	{
		$this->cn()->entidad()->eliminar_per_inscr_excep();
		unset($this->s__seleccion_periodo_insc);
		$this->set_pantalla('seleccion_per_inscr');
	}

	//-----------------------------------------------------------------------------------
	//---- seleccion_excep_perlect ------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__seleccion_excep_perlect(guarani_pers_ei_cuadro $cuadro)
	{
		$datos_comision = $this->cn()->entidad()->get_datos_generales();
		if (!empty($datos_comision['periodo_lectivo'])) {
			// Datos originales del período
			$datos = toba::consulta_php('co_periodos_lectivos')->get_datos_periodo_lectivo($datos_comision['periodo_lectivo']);
			// Datos de la excepción
			$datos_excep_perlect = $this->cn()->entidad()->get_datos_excep_perlect();
			$datos['fecha_inicio_dictado_excep'] = !empty($datos_excep_perlect) ? $datos_excep_perlect['fecha_inicio_dictado'] : '';
			$datos['fecha_fin_dictado_excep'] = !empty($datos_excep_perlect) ? $datos_excep_perlect['fecha_fin_dictado'] : '';
			// Datos para el cuadro
			$datos_cuadro = array();
			$datos_cuadro[] = $datos;
			$cuadro->set_datos($datos_cuadro);
		}
	}

	function evt__seleccion_excep_perlect__seleccion()
	{
		$this->set_pantalla('edicion_excep_perlect');
	}

	//-----------------------------------------------------------------------------------
	//---- datos_excepcion_perlect ------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__datos_excepcion_perlect(guarani_pers_ei_formulario $form)
	{
		// Se obtienen datos (si existen)
		if (isset($this->datos_excepcion_perlect)) {
			$datos_excep_perlect = $this->datos_excepcion_perlect;
		} else {
			$datos_excep_perlect = $this->cn()->entidad()->get_datos_excep_perlect();
		}

		if (!empty($datos_excep_perlect)) {
			// Si hay datos, se establece mensaje de confirmación al evento eliminar
			$etiqueta = guarani_etiquetas::get_tabla('sga_comisiones_excep_perlect');
			$form->evento('eliminar')->set_msg_confirmacion(guarani_mensajes::get($this->indx_msg_confirmar_eliminar, array($etiqueta->get('la'))));

			$form->set_datos($datos_excep_perlect);
		} else {
			// Si no hay datos, se quita el evento eliminar
			$form->eliminar_evento('eliminar');
		}

		$datos_comision = $this->cn()->entidad()->get_datos_generales();
		if (!empty($datos_comision['comision'])) {
			$fechas_excep_bd = toba::consulta_php('co_comisiones')->get_fechas_excep_perlect($datos_comision['comision']);

			$solo_lectura = array();
			// Las nuevas fechas de inicio y fin de dictado no pueden editarse una vez que ya transcurrieron las mismas.
			if (isset($fechas_excep_bd['fecha_inicio_dictado']) && guarani_fecha::es_menor_a_fecha_actual($fechas_excep_bd['fecha_inicio_dictado'])) {
				$form->ef('fecha_inicio_dictado')->set_descripcion('La fecha no puede ser modificada porque ya ha transcurrido');
				$solo_lectura[] = 'fecha_inicio_dictado';
			}
			if (isset($fechas_excep_bd['fecha_fin_dictado']) && guarani_fecha::es_menor_a_fecha_actual($fechas_excep_bd['fecha_fin_dictado'])) {
				$form->ef('fecha_fin_dictado')->set_descripcion('La fecha no puede ser modificada porque ya ha transcurrido');
				$solo_lectura[] = 'fecha_fin_dictado';
			}
			if (!empty($solo_lectura)) {
				$form->set_solo_lectura($solo_lectura);
				$form->eliminar_evento('eliminar');
			}
		}
	}

	function evt__datos_excepcion_perlect__cancelar()
	{
		$this->set_pantalla('seleccion_per_inscr');
	}

	function evt__datos_excepcion_perlect__eliminar()
	{
		$this->cn()->entidad()->eliminar_datos_excep_perlect();
		$this->set_pantalla('seleccion_per_inscr');
	}

	function evt__datos_excepcion_perlect__confirmar($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_excep_perlect($datos);
			$this->set_pantalla('seleccion_per_inscr');
		} catch (guarani_error $e) {
			$this->datos_excepcion_perlect = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

}