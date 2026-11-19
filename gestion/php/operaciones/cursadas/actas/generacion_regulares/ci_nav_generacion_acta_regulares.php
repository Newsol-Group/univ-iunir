<?php
/**
 *
 *
 * @package Cursadas
 * @subpackage Actas
 * 
 */
class ci_nav_generacion_acta_regulares extends ci_navegacion
{
	// Mensajes
	protected $msg_sin_evaluaciones = '800_sin_evaluacion';
	
	// Comisión seleccionada y su evaluación asociada.
	protected $s__comision_seleccionada;
	protected $s__datos_comision;
	protected $s__evaluacion;
	protected $s__acta;
	
	// Componente Visual de Información.
	protected $ev_info;

	function ini()
	{
		if (toba::zona()->cargada() && !$this->cn()->entidad_cargada()) {
			$filtro = "sga_comisiones.entidad = " . toba::db()->quote(toba::zona()->get_editable());
			$datos_comision = current(toba::consulta_php('co_comisiones')->get_listado_con_cant_actas_no_anuladas(comision::inst_cursar, $filtro));
			
			// El valor del parámetro 'cur_multiples_actas_comision', obtenido en el contexto de usarlo para determinar si deben o no mostrarse 
			// los eventos a nivel fila, es obtenido por fuera del esquema de atributos en caché porque todavía no hay selección.
			$operacion = guarani::get_id_operacion();
			$comision = $datos_comision['comision'];
			$atributos = aplanar_matriz(toba::consulta_php('co_comisiones')->get_propuestas_comision($comision), 'propuesta');
			$cur_multiples_actas_comision = guarani::parametros()->get_valor('cur_multiples_actas_comision', $atributos);

			if (!(($datos_comision['cant_actas_no_anuladas'] > 0) && ($cur_multiples_actas_comision == comunes::no))) {
				$this->seleccionar(array('entidad' => toba::zona()->get_editable()));
				// Si sólo puede haber un acta por comisión, se preseleccionan todos los alumnos de la comisión.
				if (guarani::parametros()->get_valor('cur_multiples_actas_comision') == comunes::no) {
					$this->dep('editor')->agregar_alumnos_comision();
				}
			}
		}
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('nombre', 'nombre_turno', 'nombre_modalidad', 'anio_academico_periodo', 'nombre_periodo', 'codigo_y_nombre_actividad', 'nombre_ubicacion'));
			$this->ev_info->set_columnas_fila(array('nombre', 'nombre_turno', 'nombre_modalidad'), 1);
			$this->ev_info->set_columnas_fila(array('anio_academico_periodo', 'nombre_periodo'), 2);
			$this->ev_info->set_columnas_fila(array('codigo_y_nombre_actividad', 'nombre_ubicacion'), 3);
			$this->ev_info->set_max_columnas_por_fila(3);
			$this->ev_info->set_etiqueta('nombre', 'Comisión');
			$this->ev_info->set_etiqueta('nombre_turno', 'Turno');
			$this->ev_info->set_etiqueta('nombre_modalidad', 'Modalidad');
			$this->ev_info->set_etiqueta('anio_academico_periodo', 'Año Académico');
			$this->ev_info->set_etiqueta('nombre_periodo', 'Período Lectivo');
			$this->ev_info->set_etiqueta('codigo_y_nombre_actividad', 'Actividad');
			$this->ev_info->set_etiqueta('nombre_ubicacion', 'Ubicación');
		}
		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- operaciones auxiliares -------------------------------------------------------
	//-----------------------------------------------------------------------------------

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

	function get_acta()
	{
		return $this->s__acta;
	}

	function seleccionar($seleccion)
	{
		$this->s__comision_seleccionada = $seleccion;

		// Se obtienen los datos de la comisión seleccionada a partir de su código de entidad.
		$filtro = "sga_comisiones.entidad = {$this->get_comision_seleccionada()}";
		$this->s__datos_comision = current(toba::consulta_php('co_comisiones')->get_listado($filtro));

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
			$this->set_pantalla('edicion');
		}

		$this->cn()->cargar_atributos_parametros($this->s__datos_comision['comision']);
	}

	function get_datos_resumen()
	{
		$datos_acta = toba::consulta_php('co_actas')->get_datos_acta($this->get_acta());

		$datos = array();
		$datos['nro_acta'] = array('etiq' => 'Acta Nro.', 'valor' => $datos_acta['nro_acta']);
		$datos['anio_academico_periodo'] = array('etiq' => 'Año Académico', 'valor' => $datos_acta['anio_academico_periodo']);
		$datos['comision_nombre'] = array('etiq' => 'Comisión', 'valor' => $datos_acta['comision_nombre']);
		$datos['nombre_ubicacion'] = array('etiq' => 'Ubicación', 'valor' => $datos_acta['nombre_ubicacion']);
		$datos['codigo_y_nombre_actividad'] = array('etiq' => 'Actividad', 'valor' => $datos_acta['codigo_y_nombre_actividad']);
		$datos['periodo_nombre'] = array('etiq' => 'Período Lectivo', 'valor' => $datos_acta['periodo_nombre']);

		return $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: seleccion 
	//-----------------------------------------------------------------------------------
	
	function conf__seleccion(toba_ei_pantalla $pantalla)
	{
		$this->cn()->resetear_atributos_parametros();
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		if (!isset($this->s__filtro)) {
			$msg_eof = guarani_mensajes::get($this->indx_msg_eof . '_sinfiltro');
		} else {
			$msg_eof = $this->get_msg_eof('Comisiones');

			// Se muestran sólo comisiones que tienen asociada la instancia CURSAR.
			$instancia_cursar = comision::inst_cursar;
			$this->s__filtro .= " AND EXISTS (SELECT * FROM sga_comisiones_instancias WHERE sga_comisiones_instancias.comision = sga_comisiones.comision AND sga_comisiones_instancias.instancia = $instancia_cursar)";
			// Comisiones en estado Activas.
			$this->s__filtro .= " AND sga_comisiones.estado = '" . comunes::estado_activo . "'";

			$datos = toba::consulta_php('co_comisiones')->get_listado_con_cant_actas_no_anuladas($instancia_cursar, $this->s__filtro);
			$cuadro->set_datos($datos);
		}
		$cuadro->set_eof_mensaje($msg_eof);
	}

	function evt__cuadro__seleccion($seleccion)
	{
		$this->seleccionar($seleccion);

		// Si sólo puede haber un acta por comisión, se preseleccionan todos los alumnos de la comisión.
		if (guarani::parametros()->get_valor('cur_multiples_actas_comision') == comunes::no) {
			$this->dep('editor')->agregar_alumnos_comision();
		}
		
		toba::zona()->cargar($this->get_comision_seleccionada());
	}

	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		// Si ya hay un acta generada para la comisión y el parámetro cur_multiples_actas_comision = N, no se deberá mostrar este botón.
		$datos = $this->dep('cuadro')->get_datos();
		
		// El valor del parámetro 'cur_multiples_actas_comision', obtenido en el contexto de usarlo para determinar si deben o no mostrarse 
		// los eventos a nivel fila, es obtenido por fuera del esquema de atributos en caché porque todavía no hay selección.
		$operacion = guarani::get_id_operacion();
		$comision = $datos[$fila]['comision'];
		$atributos = aplanar_matriz(toba::consulta_php('co_comisiones')->get_propuestas_comision($comision), 'propuesta');
		$cur_multiples_actas_comision = guarani::parametros()->get_valor('cur_multiples_actas_comision', $atributos);
		
		if (($datos[$fila]['cant_actas_no_anuladas'] > 0) && ($cur_multiples_actas_comision == comunes::no)) {
			$evento->anular();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		if (empty($this->s__datos['con_acta_generada'])) {
			$filtro->columna('con_acta_generada')->set_estado(array('valor' => comunes::no));
		}
		$filtro->columna('anio_academico')->set_condicion_fija('es_igual_a');
		$filtro->columna('periodos_nombre')->set_condicion_fija('es_igual_a');
		$filtro->columna('con_acta_generada')->set_condicion_fija('es_igual_a');
		$filtro->columna('actividad')->set_condicion_fija('contiene');
	}

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$filtro_con_acta = '';
			if ((isset($datos['con_acta_generada']))) {
				$filtro_con_acta = ($datos['con_acta_generada']['valor'] == comunes::si) ? ' AND EXISTS ' : ' AND NOT EXISTS ';
				$origen_regulares = toba::db()->quote(acta::origen_regulares);
				$filtro_con_acta .= "(SELECT * FROM sga_actas WHERE sga_actas.comision = sga_comisiones.comision AND sga_actas.origen = $origen_regulares AND sga_actas.estado <> '" . acta::estado_anulada . "')";
				$this->dep('filtro')->columna('con_acta_generada')->resetear_estado();
			}
			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro_con_acta";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: edicion
	//-----------------------------------------------------------------------------------

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		$this->ev_info()->set_datos($this->s__datos_comision);
		$this->hay_cambios = $this->cn()->hay_cambios();
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA: informacion
	//-----------------------------------------------------------------------------------

	function conf__informacion(toba_ei_pantalla $pantalla)
	{
		$this->evento('cancelar')->set_etiqueta('Ir al inicio');
		$this->evento('cancelar')->set_imagen('/home.png');
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__guardar()
	{
		// Se obtienen los datos del acta.
		$nro_acta = $this->dep('editor')->get_nro_acta();
		$observaciones = $this->dep('editor')->get_observaciones();

		// Se crea el acta.
		$this->s__acta = $this->cn()->generar_acta($nro_acta, $observaciones, $this->s__datos_comision['comision'], comision::inst_cursar);

		// Se muestra el resumen de los datos del nuevo acta.
		$this->set_pantalla('informacion');
	}

	function evt__cancelar()
	{
		parent::evt__cancelar();
		$this->dep('editor')->resetear_vars();
		toba::zona()->resetear();
		unset($this->s__comision_seleccionada);
		unset($this->s__evaluacion);
		unset($this->s__acta);
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