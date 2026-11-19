<?php
/**
 *
 *
 * @package 	Cursadas
 * @subpackage 	Actas
 * 
 */
class ci_nav_cargar_notas extends ci_navegacion
{
	// Mensajes
	protected $msg_sin_evaluaciones = '800_sin_evaluacion';
	
	// Comisión seleccionada y su evaluación asociada.
	protected $s__comision_seleccionada;
	protected $s__datos_comision;
	protected $s__evaluacion;
	
	// Acta seleccionada y datos de la misma
	protected $s__acta_seleccionada;
	protected $s__datos_acta;
	
	// Parámetros enviados en la invocación desde otra operación.
	protected $s__operacion_a_volver;
	protected $s__celda_a_restaurar;
	
	// Componente Visual de Información.
	protected $ev_info;

	function ini()
	{
		if (toba::zona()->cargada() && !$this->cn()->entidad_cargada()) {
			$this->seleccionar_comision(array('entidad' => toba::zona()->get_editable()));
		}

		if (!isset($this->s__acta_seleccionada)) {
			// Obtengo los parámetros si la operación es invocada desde otra operación.		
			$this->s__operacion_a_volver = toba::memoria()->get_parametro('operacion_a_volver');
			if (isset($this->s__operacion_a_volver)) {
				$this->s__acta_seleccionada = array('id_acta' => toba::memoria()->get_parametro('id_acta'));
				$this->s__celda_a_restaurar = toba::memoria()->get_parametro('celda_a_restaurar');

				$this->s__datos_acta = toba::consulta_php('co_actas')->get_datos_acta($this->get_acta_seleccionada());
				$this->seleccionar_comision(array('entidad' => $this->s__datos_acta['comision_entidad']), false);
				$this->seleccionar_acta($this->s__acta_seleccionada);
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Operaciones auxiliares -------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Retorna la entidad de la comisión seleccionada. 
	 */
	function get_comision_seleccionada()
	{
		return $this->s__comision_seleccionada['entidad'];
	}

	function get_datos_comision()
	{
		return $this->s__datos_comision;
	}

	function get_evaluacion()
	{
		return $this->s__evaluacion;
	}

	/**
	 * Retorna el id de acta seleccionada.
	 */
	function get_acta_seleccionada()
	{
		return $this->s__acta_seleccionada['id_acta'];
	}

	function get_datos_acta()
	{
		return $this->s__datos_acta;
	}

	function seleccionar_comision($seleccion, $navegar_seleccion_acta = true)
	{
		$this->s__comision_seleccionada = $seleccion;

		// Se obtienen los datos de la comisión seleccionada a partir de su código de entidad.
		$filtro = "sga_comisiones.entidad = {$this->get_comision_seleccionada()}";
		$select = 'sga_periodos_lectivos.fecha_inicio_dictado, sga_periodos_lectivos.fecha_fin_dictado';
		$this->s__datos_comision = current(toba::consulta_php('co_comisiones')->get_listado($filtro, $select));

		// Se busca la evaluación asociada a la comisión seleccionada.
		$where = "	sga_evaluaciones.entidad = {$this->get_comision_seleccionada()} AND
					sga_evaluaciones_tipos.automatica = '" . comunes::si . "' AND
					sga_evaluaciones_tipos.aplica_a = '" . acta::evaluacion_tipo_aplica_comision . "'";
		$datos = current(toba::consulta_php('co_evaluaciones')->get_listado($where));

		if (empty($datos)) {
			// Si no existe la evaluación => mensaje de error.
			$mensaje = guarani_mensajes::get($this->msg_sin_evaluaciones);
			throw guarani::error($mensaje);
		} else {
			$this->s__evaluacion = $datos['evaluacion'];
			$this->cn()->cargar(array('evaluacion' => $this->s__evaluacion));
			if ($navegar_seleccion_acta) {
				$this->set_pantalla('seleccion_acta');
			}
		}
	}

	function seleccionar_acta($seleccion)
	{
		$this->s__acta_seleccionada = $seleccion;
		$this->s__datos_acta = toba::consulta_php('co_actas')->get_datos_acta($this->get_acta_seleccionada());
		$this->dep('editor')->resetear_vars();
		$this->cn()->cargar_atributos_parametros($this->s__datos_acta['comision']);
		$this->set_pantalla('edicion');
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: seleccion
	//-----------------------------------------------------------------------------------

	function conf__seleccion(toba_ei_pantalla $pantalla)
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
			$datos = toba::consulta_php('co_comisiones')->get_listado_con_cant_actas_no_anuladas(null, $this->s__filtro);
			$cuadro->set_datos($datos);
		}
		$cuadro->set_eof_mensaje($msg_eof);
	}

	function conf_evt__cuadro_comision__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dependencia('cuadro_comision')->get_datos();
		if ($datos[$fila]['cant_actas_no_anuladas'] == 0) {
			$evento->anular();
		}
	}

	function evt__cuadro_comision__seleccion($seleccion)
	{
		$this->seleccionar_comision($seleccion);
		toba::zona()->cargar($this->get_comision_seleccionada());
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('anio_academico')->set_condicion_fija('es_igual_a');
		$filtro->columna('periodos_nombre')->set_condicion_fija('es_igual_a');
		$filtro->columna('modalidad')->set_condicion_fija('es_igual_a');
		$filtro->columna('actividad')->set_condicion_fija('contiene');
	}

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$filtro_modalidad = '';
			if (isset($datos['modalidad'])) {
				$filtro_modalidad .= " AND EXISTS (SELECT * FROM sga_comisiones_modalidad WHERE sga_comisiones_modalidad.comision = sga_comisiones.comision AND sga_comisiones_modalidad.modalidad = '{$datos['modalidad']['valor']}')";
				$this->dep('filtro')->columna('modalidad')->resetear_estado();
			}
			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro_modalidad";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: seleccion_acta
	//-----------------------------------------------------------------------------------

	function conf__seleccion_acta()
	{
		$this->ev_info()->set_datos($this->s__datos_comision);
		$this->cn()->resetear_atributos_parametros();
	}

	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('nombre', 'nombre_turno', 'nombre_modalidad', 'anio_academico_periodo', 'nombre_periodo', 'codigo_y_nombre_actividad', 'nombre_ubicacion'));

			$this->ev_info->set_columnas_fila(array('anio_academico_periodo', 'nombre_periodo', 'nombre'), 1);
			$this->ev_info->set_columnas_fila(array('nombre_ubicacion', 'nombre_turno', 'nombre_modalidad'), 2);
			$this->ev_info->set_columnas_fila(array('codigo_y_nombre_actividad'), 3);

			$this->ev_info->set_max_columnas_por_fila(3);

			$this->ev_info->set_etiqueta('anio_academico_periodo', 'Año Académico');
			$this->ev_info->set_etiqueta('nombre_periodo', 'Período Lectivo');
			$this->ev_info->set_etiqueta('nombre', 'Comisión');

			$this->ev_info->set_etiqueta('nombre_ubicacion', 'Ubicación');
			$this->ev_info->set_etiqueta('nombre_turno', 'Turno');
			$this->ev_info->set_etiqueta('nombre_modalidad', 'Modalidad');

			$this->ev_info->set_etiqueta('codigo_y_nombre_actividad', 'Actividad');
		}
		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_actas -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_actas(toba_ei_cuadro $cuadro)
	{
		$filtro = "sga_comisiones.entidad = {$this->get_comision_seleccionada()}";

		// NO se muestran las actas anuladas.
		$estado_anulada = toba::db()->quote(acta::estado_anulada);
		$filtro .= " AND sga_actas.estado <> $estado_anulada";

		$datos_actas = toba::consulta_php('co_actas')->get_listado($filtro);

		foreach ($datos_actas as $clave => $valor) {
			if ($datos_actas[$clave]['estado'] == acta::estado_abierta) {
				$datos_actas[$clave]['estado_semaforo'] = toba_recurso::imagen_proyecto('iconos_estados/verde.png', true, null, null, 'Acta Abierta', null);
			} elseif ($datos_actas[$clave]['estado'] == acta::estado_cerrada) {
				$datos_actas[$clave]['estado_semaforo'] = toba_recurso::imagen_proyecto('iconos_estados/rojo.png', true, null, null, 'Acta Cerrada', null);
			}
		}

		$cuadro->set_datos($datos_actas);
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof, array('Actas generadas para la Comisión seleccionada')));
	}

	function evt__cuadro_actas__seleccion($seleccion)
	{
		$this->seleccionar_acta($seleccion);
	}

	function conf_evt__cuadro_actas__seleccion(toba_evento_usuario $evento, $fila)
	{
		// Si el acta está cerrada no se deberá mostrar el botón de selección.
		$datos = $this->dep('cuadro_actas')->get_datos();
		if ($datos[$fila]['estado'] == acta::estado_cerrada) {
			$evento->anular();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: edicion
	//-----------------------------------------------------------------------------------

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		$this->hay_cambios = $this->cn()->hay_cambios();
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cancelar_actas()
	{
		if (!empty($this->s__operacion_a_volver) && !empty($this->s__celda_a_restaurar)) {
			echo toba_js::abrir();
			echo "document.location.href='" .
			toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), $this->s__operacion_a_volver, array('volvio_edit' => true, 'id_acta' => $this->s__acta_seleccionada['id_acta']), array('celda_memoria' => $this->s__celda_a_restaurar)) . "'\n";
			echo toba_js::cerrar();
		} else {
			toba::zona()->resetear();
			$this->resetear_vars();
			$this->set_pantalla('seleccion');
		}
	}

	function evt__cancelar()
	{
		if (!empty($this->s__operacion_a_volver) && !empty($this->s__celda_a_restaurar)) {
			echo toba_js::abrir();
			echo "document.location.href='" .
			toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), $this->s__operacion_a_volver, array('volvio_edit' => true, 'id_acta' => $this->s__acta_seleccionada['id_acta']), array('celda_memoria' => $this->s__celda_a_restaurar)) . "'\n";
			echo toba_js::cerrar();
		} else {
			toba::zona()->resetear();
			$this->resetear_vars();
			$this->dep('editor')->resetear_vars();
			$this->set_pantalla('seleccion');
		}
	}

	function evt__guardar()
	{
		// Se desactivan las transacciones para genererar una única que contenga los dos pasos: actualizar observaciones y actualizar notas cargadas.
		$this->cn()->desactivar_transacciones_anidadas();
		try {
			toba::db()->abrir_transaccion();

			// Actualización de las observaciones del acta.
			$observaciones = $this->dep('editor')->get_observaciones();
			if ($observaciones) {
				$id_acta = $this->get_acta_seleccionada();
				guarani::act('act_actas')->actualizar_observaciones_acta($id_acta, $observaciones);
			}

			// Sincronización de las notas cargadas, actualización de la versión de actas (si existen).
			$campo_acta = ($this->s__datos_acta['origen'] == acta::origen_regulares) ? 'id_acta_cursada' : 'id_acta_promocion';
			$this->cn()->sincronizar_carga_notas(array($campo_acta => $this->get_acta_seleccionada()));
			
			toba::notificacion()->info('Se guardó correctamente.');

			$this->hay_cambios = false;

			toba::db()->cerrar_transaccion();
		} catch (guarani_error $e) {
			toba::db()->abortar_transaccion();
			toba::notificacion()->agregar($e->getMessage(), 'error');
			toba::logger()->error($e->getMessage());
		}
	}

	/**
	 * Resetea las variables de sesión de este CI
	 */
	function resetear_vars()
	{
		unset($this->s__comision_seleccionada);
		unset($this->s__evaluacion);
	}

}
?>