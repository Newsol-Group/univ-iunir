<?php
/**
 *
 *
 * @package Cursadas
 * @subpackage Actas
 * 
 */
class ci_nav_rectificativa_regulares extends ci_navegacion
{
	// Comisión seleccionada y su evaluación asociada.
	protected $s__comision_seleccionada;
	protected $s__datos_comision;
	protected $s__evaluacion;
	
	// Acta seleccionada.
	protected $s__acta_seleccionada;
	protected $s__datos_acta_original;
	
	// Código del acta rectificativa generada.
	protected $s__acta;
	
	// Componente Visual de Información.
	protected $ev_info_comision;

	function ini()
	{
		if (toba::zona()->cargada() && !$this->cn()->entidad_cargada()) {
			$this->set_datos_comision(array('entidad' => toba::zona()->get_editable()));
			$this->set_pantalla('seleccion_acta');
		}
	}

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		$this->hay_cambios = $this->cn()->hay_cambios();
	}

	//-----------------------------------------------------------------------------------
	//---- operaciones auxiliares -------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Retorna la entidad de la comisión seleccionada. 
	 */
	function get_comision_seleccionada()
	{
		return $this->s__comision_seleccionada['entidad'];
	}

	function set_datos_comision($seleccion)
	{
		$this->s__comision_seleccionada = $seleccion;
		$filtro = "sga_comisiones.entidad = {$this->get_comision_seleccionada()}";
		$this->s__datos_comision = current(toba::consulta_php('co_comisiones')->get_listado($filtro));
	}

	/**
	 * Retorna el código de la evaluación (automática y que aplica a comisión) asociada a la comisión seleccionada.
	 */
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

	function get_id_acta_rectificativa()
	{
		return $this->s__acta;
	}

	function get_datos_acta_original()
	{
		return $this->s__datos_acta_original;
	}

	function seleccionar()
	{
		// Se busca la evaluación asociada a la comisión seleccionada.
		$where = "	sga_evaluaciones.entidad = {$this->get_comision_seleccionada()} AND
					sga_evaluaciones_tipos.automatica = '" . comunes::si . "' AND
					sga_evaluaciones_tipos.aplica_a = '" . acta::evaluacion_tipo_aplica_comision . "'";
		$datos = current(toba::consulta_php('co_evaluaciones')->get_listado($where));

		if (!empty($datos)) {
			$this->s__evaluacion = $datos['evaluacion'];
			$this->cn()->cargar(array('evaluacion' => $this->s__evaluacion));
		}
	}

	function get_datos_resumen()
	{
		$datos_acta = toba::consulta_php('co_actas')->get_datos_acta($this->s__acta);

		$datos = array();
		$datos[0] = array('etiq' => 'Acta Rectificativa Nro.', 'valor' => $datos_acta['nro_acta']);
		$datos[1] = array('etiq' => 'Año Académico', 'valor' => $datos_acta['anio_academico_periodo']);
		$datos[2] = array('etiq' => 'Comisión', 'valor' => $datos_acta['comision_nombre']);
		$datos[3] = array('etiq' => 'Ubicación', 'valor' => $datos_acta['nombre_ubicacion']);
		$datos[4] = array('etiq' => 'Actividad', 'valor' => $datos_acta['codigo_y_nombre_actividad']);
		$datos[5] = array('etiq' => 'Período Lectivo', 'valor' => $datos_acta['periodo_nombre']);

		$datos[6] = array('etiq' => 'Acta Original', 'valor' => $this->s__datos_acta_original['nro_acta']);

		return $datos;
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA: seleccion_comision
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

			// Se muestran sólo comisiones que tienen asociada la instancia CURSAR.
			$instancia_cursar = comision::inst_cursar;
			$this->s__filtro .= " AND EXISTS (SELECT * FROM sga_comisiones_instancias WHERE sga_comisiones_instancias.comision = sga_comisiones.comision AND sga_comisiones_instancias.instancia = $instancia_cursar)";

			$datos = toba::consulta_php('co_comisiones')->get_listado_con_cant_actas_no_anuladas($instancia_cursar, $this->s__filtro);
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
		$this->set_datos_comision($seleccion);
		$this->cn()->cargar_atributos_parametros($this->s__datos_comision['comision']);
		toba::zona()->cargar($this->get_comision_seleccionada());
		$this->set_pantalla('seleccion_acta');
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('anio_academico')->set_condicion_fija('es_igual_a');
		$filtro->columna('periodos_nombre')->set_condicion_fija('es_igual_a');
		$filtro->columna('actividad')->set_condicion_fija('contiene');
		$filtro->columna('nro_acta')->set_condicion_fija('es_igual_a');
	}

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$filtro_nro_acta = '';
			if (isset($datos['nro_acta'])) {
				$filtro_nro_acta = " AND EXISTS (	SELECT	*
													FROM	sga_actas
													WHERE	sga_actas.comision = sga_comisiones.comision AND
															sga_actas.origen = '" . acta::origen_regulares . "' AND
															sga_actas.tipo_acta = '" . acta::tipo_normal . "' AND
															sga_actas.estado = '" . acta::estado_cerrada . "' AND
															sga_actas.nro_acta = '{$datos['nro_acta']['valor']}')";
				$this->dep('filtro')->columna('nro_acta')->resetear_estado();
			}
			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro_nro_acta";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: seleccion_acta
	//-----------------------------------------------------------------------------------

	function conf__seleccion_acta(toba_ei_pantalla $pantalla)
	{
		$this->ev_info_comision()->set_datos($this->s__datos_comision);
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
	//---- cuadro_actas -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_actas(toba_ei_cuadro $cuadro)
	{
		$filtro = "sga_comisiones.entidad = {$this->get_comision_seleccionada()}";

		// Sólo se muestran actas de regulares.
		$origen_regulares = toba::db()->quote(acta::origen_regulares);
		$filtro .= " AND sga_actas.origen = $origen_regulares";

		// Sólo se muestran las actas de tipo NORMAL en estado CERRADA.
		$tipo_normal = toba::db()->quote(acta::tipo_normal);
		$filtro .= " AND sga_actas.tipo_acta = $tipo_normal";
		$estado_cerrada = toba::db()->quote(acta::estado_cerrada);
		$filtro .= " AND sga_actas.estado = $estado_cerrada";

		$datos_actas = toba::consulta_php('co_actas')->get_listado($filtro);

		foreach ($datos_actas as $clave => $valor) {
			if ($datos_actas[$clave]['tiene_rectificativas_abiertas'] == 0) {
				$datos_actas[$clave]['estado_semaforo'] = toba_recurso::imagen_proyecto('iconos_estados/verde.png', true, null, null, 'Acta OK', null);
			} elseif ($datos_actas[$clave]['tiene_rectificativas_abiertas'] == 1) {
				$datos_actas[$clave]['estado_semaforo'] = toba_recurso::imagen_proyecto('iconos_estados/amarillo.png', true, null, null, 'Acta con rectificativa abierta', null);
			}
		}

		$cuadro->set_datos($datos_actas);

		$msj_eof = $this->get_msg_eof('Actas');
		$cuadro->set_eof_mensaje($msj_eof);
	}

	function evt__cuadro_actas__seleccion($seleccion)
	{
		$this->s__acta_seleccionada = $seleccion;
		$this->s__datos_acta_original = toba::consulta_php('co_actas')->get_datos_acta($this->get_acta_seleccionada());
		$this->seleccionar();
		$this->set_pantalla('edicion');
	}

	function conf_evt__cuadro_actas__seleccion(toba_evento_usuario $evento, $fila)
	{
		// Si el acta tiene rectificativas abiertas no se deberá mostrar el botón de selección.
		$datos = $this->dep('cuadro_actas')->get_datos();
		if ($datos[$fila]['tiene_rectificativas_abiertas']) {
			$evento->anular();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: informacion
	//-----------------------------------------------------------------------------------

	function conf__informacion(toba_ei_pantalla $pantalla)
	{
		$this->evento('cancelar_preseleccion')->set_etiqueta('Ir al inicio');
		$this->evento('cancelar_preseleccion')->set_imagen('/home.png');
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__guardar()
	{
		// Se obtienen los datos del acta.
		$documento = $this->dep('editor')->get_documento();
		$nro_acta = $this->dep('editor')->get_nro_acta();
		$observaciones = $this->dep('editor')->get_observaciones();
		$id_acta_original = $this->get_acta_seleccionada();

		$this->s__acta = $this->cn()->generar_acta_rectificativa($nro_acta, $documento, $observaciones, $id_acta_original, $this->s__datos_comision['comision'], comision::inst_cursar);

		// Se muestra el resumen de los datos del nuevo acta.
		$this->set_pantalla('informacion');
	}

	function evt__cancelar_preseleccion()
	{
		unset($this->s__comision_seleccionada);
		toba::zona()->resetear();
		$this->set_pantalla('seleccion_comision');
	}

	function evt__cancelar()
	{
		$this->cn()->resetear();
		$this->dep('editor')->resetear_vars();
		unset($this->s__acta_seleccionada);
		$this->set_pantalla('seleccion_acta');
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$msj_confirmacion = guarani_mensajes::get('800_abandono_edicion');
		$msj_sin_seleccion_alumnos = guarani_mensajes::get('800_sin_seleccion_alumnos');

		if ($this->hay_cambios) {
			$confirmar = 'var hay_cambios_alumnos = true; var hay_cambios_formulario = toba.hay_cambios();';
		} else {
			$confirmar = 'var hay_cambios_alumnos = false; var hay_cambios_formulario = toba.hay_cambios();';
		}

		echo "
			{$this->objeto_js}.evt__cancelar = function() {
				$confirmar
				if (hay_cambios_alumnos || hay_cambios_formulario) {
					resp = confirm('$msj_confirmacion');
					if (! resp) {
						return false;
					}
				}
				return true;
			}

			{$this->objeto_js}.evt__guardar = function() {
				$confirmar
				if (! hay_cambios_alumnos) {
					alert('$msj_sin_seleccion_alumnos');
					return false;
				}
				return true;
			}

            toba.set_callback_menu({$this->objeto_js}.evt__cancelar);
		";
	}

}
?>