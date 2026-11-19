<?php
/**
 *
 *
 * @package Cursadas
 * @subpackage Actas
 * 
 */
class ci_nav_agregar_eliminar_alumnos_rectificativa extends ci_navegacion
{
	// Comisión seleccionada y su evaluación asociada.
	protected $s__comision_seleccionada;
	protected $s__evaluacion;
	protected $s__datos_comision;
	
	// Acta seleccionada.
	protected $s__acta_seleccionada;
	protected $s__datos_acta;
	
	// Componente Visual de Información.
	protected $ev_info_comision;

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

	/**
	 * Retorna el código de la evaluación (automática y que aplica a comisión) asociada a la comisión seleccionada.
	 */
	function get_evaluacion()
	{
		return $this->s__evaluacion;
	}

	/**
	 * Retorna el código del acta seleccionada.
	 */
	function get_acta_seleccionada()
	{
		return $this->s__acta_seleccionada['id_acta'];
	}

	function get_datos_acta()
	{
		return $this->s__datos_acta;
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
		$datos = array();
		$datos[] = array('etiq' => 'Acta Nro.', 'valor' => $this->s__datos_acta['nro_acta']);
		$datos[] = array('etiq' => 'Año Académico', 'valor' => $this->s__datos_acta['anio_academico_periodo']);
		$datos[] = array('etiq' => 'Comisión', 'valor' => $this->s__datos_acta['comision_nombre']);
		$datos[] = array('etiq' => 'Libro', 'valor' => $this->s__datos_acta['libro_nombre']);
		$datos[] = array('etiq' => 'Ubicación', 'valor' => $this->s__datos_acta['nombre_ubicacion']);
		$datos[] = array('etiq' => 'Actividad', 'valor' => $this->s__datos_acta['codigo_y_nombre_actividad']);
		$datos[] = array('etiq' => 'Período Lectivo', 'valor' => $this->s__datos_acta['periodo_nombre']);
		$datos[] = array('etiq' => 'Instancia', 'valor' => $this->s__datos_acta['instancia_nombre']);

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
			$datos = toba::consulta_php('co_comisiones')->get_listado_con_cant_actas_rectificativas_no_anuladas(null, $this->s__filtro);
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
		$this->s__comision_seleccionada = $seleccion;

		// Se obtienen los datos de la comisión seleccionada a partir de su código de entidad.
		$filtro = "sga_comisiones.entidad = {$this->get_comision_seleccionada()}";
		$this->s__datos_comision = current(toba::consulta_php('co_comisiones')->get_listado($filtro));
		$this->cn()->cargar_atributos_parametros($this->s__datos_comision['comision']);

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
															sga_actas.tipo_acta = '" . acta::tipo_rectificativa . "' AND
															sga_actas.estado <> '" . acta::estado_anulada . "' AND
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
			$this->ev_info_comision->set_columnas_fila(array('nombre_ubicacion', 'codigo_y_nombre_actividad'), 3);
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

		// Sólo se muestran las actas de tipo RECTIFICATIVA, en estado NO ANULADA.
		$estado_anulada = toba::db()->quote(acta::estado_anulada);
		$filtro .= " AND sga_actas.estado <> $estado_anulada";

		$datos_actas = toba::consulta_php('co_actas')->get_listado_actas_rectificativas($filtro);

		foreach ($datos_actas as $clave => $valor) {
			if ($datos_actas[$clave]['estado'] == acta::estado_abierta) {
				$datos_actas[$clave]['estado_semaforo'] = toba_recurso::imagen_proyecto('iconos_estados/verde.png', true, null, null, 'Acta Abierta', null);
			} elseif ($datos_actas[$clave]['estado'] == acta::estado_cerrada) {
				$datos_actas[$clave]['estado_semaforo'] = toba_recurso::imagen_proyecto('iconos_estados/rojo.png', true, null, null, 'Acta Cerrada', null);
			}
		}

		$cuadro->set_datos($datos_actas);

		$msj_eof = $this->get_msg_eof('Actas Rectificativas');
		$cuadro->set_eof_mensaje($msj_eof);
	}

	function evt__cuadro_actas__seleccion($seleccion)
	{
		$this->s__acta_seleccionada = $seleccion;
		$this->s__datos_acta = toba::consulta_php('co_actas')->get_datos_acta($this->get_acta_seleccionada());
		$this->seleccionar();
		$this->set_pantalla('edicion');
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
		$this->cn()->modificar_acta($this->s__datos_acta);
		$this->set_pantalla('informacion');
	}

	function evt__cancelar_preseleccion()
	{
		unset($this->s__comision_seleccionada);
		unset($this->s__datos_comision);
		$this->set_pantalla('seleccion_comision');
	}

	function evt__cancelar()
	{
		$this->cn()->resetear();
		$this->dep('editor')->resetear_vars();
		unset($this->s__acta_seleccionada);
		$this->set_pantalla('seleccion_acta');
	}

}
?>