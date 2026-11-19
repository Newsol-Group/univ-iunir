<?php
/**
 *
 *
 * @package Cursadas
 * @subpackage Actas
 * 
 */
class ci_nav_anular extends ci_navegacion
{
	// Mensajes.
	protected $msg_anular_ok = '800_anular_acta_ok';
	
	// Comisión y acta seleccionadas.
	protected $s__comision_seleccionada;
	protected $s__acta_seleccionada;
	
	// Datos del acta y comisión seleccionadas.
	protected $s__datos_acta;
	protected $s__datos_comision;
	
	// Datos de los alumnos asociados al acta seleccionada.
	protected $s__datos_alumnos;
	
	// Componentes Visuales de Información de Datos de Comisiones y Actas.
	protected $ev_info_comision;
	protected $ev_info_acta;

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
	 * Retorna el id de acta seleccionada.
	 */
	function get_acta_seleccionada()
	{
		return $this->s__acta_seleccionada['id_acta'];
	}

	function seleccionar()
	{
		$entidad_comision = $this->get_comision_seleccionada();

		// Se busca la evaluación asociada a la comisión seleccionada.
		$where = "	sga_evaluaciones.entidad = $entidad_comision AND
					sga_evaluaciones_tipos.automatica = '" . comunes::si . "' AND
					sga_evaluaciones_tipos.aplica_a = '" . acta::evaluacion_tipo_aplica_comision . "'";
		$datos = current(toba::consulta_php('co_evaluaciones')->get_listado($where));

		if (!empty($datos)) {
			$this->cn()->cargar(array('evaluacion' => $datos['evaluacion']));
		}
		
		$this->cn()->cargar_atributos_parametros($this->s__datos_comision['comision']);
		
		$datos_acta = toba::consulta_php('co_actas')->get_datos_acta($this->get_acta_seleccionada());
		$this->cn()->cargar_dependencias_anulacion($datos_acta['origen']);
	}

	function get_datos_resumen()
	{
		$datos_acta = $this->s__datos_acta;

		$datos = array();
		$datos[] = array('etiq' => 'Acta Nro.', 'valor' => $datos_acta['nro_acta']);
		$datos[] = array('etiq' => 'Año Académico', 'valor' => $datos_acta['anio_academico_periodo']);
		$datos[] = array('etiq' => 'Comisión', 'valor' => $datos_acta['comision_nombre']);
		$datos[] = array('etiq' => 'Libro', 'valor' => $datos_acta['libro_nombre']);
		$datos[] = array('etiq' => 'Ubicación', 'valor' => $datos_acta['nombre_ubicacion']);
		$datos[] = array('etiq' => 'Actividad', 'valor' => $datos_acta['codigo_y_nombre_actividad']);
		$datos[] = array('etiq' => 'Período Lectivo', 'valor' => $datos_acta['periodo_nombre']);

		return $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: seleccion_comision
	//-----------------------------------------------------------------------------------
	
	function conf__seleccion_comision(toba_ei_pantalla $pantalla)
	{
		$this->cn()->resetear_atributos_parametros();
		$this->cn()->resetear_dependencias_parametros();
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
		$this->s__comision_seleccionada = $seleccion;
		
		// Se obtienen los datos de la comisión seleccionada a partir de su código de entidad.
		$filtro = "sga_comisiones.entidad = {$this->get_comision_seleccionada()}";
		$this->s__datos_comision = current(toba::consulta_php('co_comisiones')->get_listado($filtro));
		
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
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: seleccion_acta
	//-----------------------------------------------------------------------------------

	function conf__seleccion_acta(toba_ei_pantalla $pantalla)
	{
		$filtro = "sga_comisiones.entidad = {$this->get_comision_seleccionada()}";
		$comision = current(toba::consulta_php('co_comisiones')->get_listado($filtro));
		$this->ev_info_comision()->set_datos($comision);
		
		$this->cn()->resetear_atributos_parametros();
		$this->cn()->resetear_dependencias_parametros();
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

		// Se muestran sólo actas abiertas.
		$estado_abierta = toba::db()->quote(acta::estado_abierta);
		$filtro .= " AND sga_actas.estado = $estado_abierta";

		$datos_actas = toba::consulta_php('co_actas')->get_listado($filtro);

		foreach ($datos_actas as $clave => $valor) {
			$datos_actas[$clave]['estado_semaforo'] = toba_recurso::imagen_proyecto('iconos_estados/verde.png', true, null, null, 'Acta Abierta', null);
		}

		$cuadro->set_datos($datos_actas);

		$msj_eof = $this->get_msg_eof('Actas');
		$cuadro->set_eof_mensaje($msj_eof);
	}

	function evt__cuadro_actas__seleccion($seleccion)
	{
		$this->s__acta_seleccionada = $seleccion;
		$this->seleccionar();
		$this->set_pantalla('previsualizacion');
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: previsualizacion
	//-----------------------------------------------------------------------------------

	function conf__previsualizacion()
	{
		$this->s__datos_acta = toba::consulta_php('co_actas')->get_datos_acta($this->get_acta_seleccionada());

		$datos = $this->s__datos_acta;
		$datos['icono_info_escala_notas'] = $this->get_html_icono_info_escala_notas();
		$this->ev_info_acta()->set_datos($datos);
	}

	function ev_info_acta()
	{
		if (!isset($this->ev_info_acta)) {
			$this->ev_info_acta = guarani::ev_info_datos();

			$this->ev_info_acta->set_columnas_a_mostrar(array('nro_acta', 'libro_nombre', 'anio_academico_periodo', 'periodo_nombre', 'comision_nombre', 'modalidad_nombre', 'codigo_y_nombre_actividad', 'nombre_ubicacion', 'icono_info_escala_notas', 'nombre_turno'));

			$this->ev_info_acta->set_columnas_fila(array('nro_acta', 'comision_nombre', 'libro_nombre'), 1);
			$this->ev_info_acta->set_columnas_fila(array('anio_academico_periodo', 'periodo_nombre', 'modalidad_nombre'), 2);
			$this->ev_info_acta->set_columnas_fila(array('codigo_y_nombre_actividad', 'nombre_ubicacion', 'icono_info_escala_notas'), 3);
			$this->ev_info_acta->set_columnas_fila(array('nombre_turno'), 4);

			$this->ev_info_acta->set_max_columnas_por_fila(3);

			$this->ev_info_acta->set_etiqueta('nro_acta', 'Acta');
			$this->ev_info_acta->set_etiqueta('comision_nombre', 'Comisión');
			$this->ev_info_acta->set_etiqueta('libro_nombre', 'Libro');

			$this->ev_info_acta->set_etiqueta('anio_academico_periodo', 'Año Académico');
			$this->ev_info_acta->set_etiqueta('periodo_nombre', 'P. Lectivo');
			$this->ev_info_acta->set_etiqueta('modalidad_nombre', 'Modalidad');

			$this->ev_info_acta->set_etiqueta('codigo_y_nombre_actividad', 'Actividad');
			$this->ev_info_acta->set_etiqueta('nombre_ubicacion', 'Ubicación');
			$this->ev_info_acta->set_etiqueta('icono_info_escala_notas', 'Escala de Notas');

			$this->ev_info_acta->set_etiqueta('nombre_turno', 'Turno');
		}
		return $this->ev_info_acta;
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_alumnos ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_alumnos(toba_ei_cuadro $cuadro)
	{
		$params_sistema = array('cur_orden_detalle_actas' => guarani::parametros()->get_valor('cur_orden_detalle_actas'));
		
		if ($this->s__datos_acta['origen'] == acta::origen_regulares) {
			$instancia = comision::inst_cursar;
		} else {
			$instancia = comision::inst_promocionar;
			$params_sistema['cur_inserta_alumnos_no_promocionales_en_acta'] = guarani::parametros()->get_valor('cur_inserta_alumnos_no_promocionales_en_acta');
		}
		
		$cuadro->set_tamanio_pagina($this->s__datos_acta['renglones_folio']);
		
		$this->s__datos_alumnos = toba::consulta_php('co_actas')->get_alumnos_acta_cursadas($this->get_acta_seleccionada(), $instancia, '', '', '', false, $params_sistema);
		$cuadro->set_datos($this->s__datos_alumnos);
		
		$msj_eof = $this->get_msg_eof('Alumnos');
		$cuadro->set_eof_mensaje($msj_eof);
	}

	//-----------------------------------------------------------------------------------
	//---- form_acta --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__form_acta__modificacion($datos)
	{
		if (isset($datos['observaciones'])) {
			$this->s__datos_acta['observaciones'] = $datos['observaciones'];
		} else {
			$this->s__datos_acta['observaciones'] = '';
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__anular()
	{
		$alumnos = $this->s__datos_alumnos;
		
		// Si el Acta es de Promoción y el parámetro de 'cur_inserta_alumnos_no_promocionales_en_acta'
		// es 'N' los alumnos no promocionables no se habrán mostrado en el listado de Alumnos
		// de la pantalla, pero deben considerarse para anular sus detalles, por lo que se vuelve 
		// a recuperar dicho listado, sin considerar el valor del parámetro.		 
		if (($this->s__datos_acta['origen'] == acta::origen_promociones) && (guarani::parametros()->get_valor('cur_inserta_alumnos_no_promocionales_en_acta') == comunes::no)) {
			$instancia = comision::inst_promocionar;
			$where = '';
			$limit = '';
			$offset = '';
			$para_anular = true;
			$params_sistema = array('cur_inserta_alumnos_no_promocionales_en_acta' => guarani::parametros()->get_valor('cur_inserta_alumnos_no_promocionales_en_acta'),
									'cur_orden_detalle_actas' => guarani::parametros()->get_valor('cur_orden_detalle_actas')
									);
			$alumnos = toba::consulta_php('co_actas')->get_alumnos_acta_cursadas($this->get_acta_seleccionada(), $instancia, $where, $limit, $offset, $para_anular, $params_sistema);
		}

		$this->cn()->anular_acta($this->s__datos_acta, $alumnos);

		$mensaje = guarani_mensajes::get($this->msg_anular_ok);
		toba::notificacion()->agregar($mensaje, 'info');

		$this->set_pantalla('informacion');
	}

	function evt__cancelar_preseleccion()
	{
		unset($this->s__comision_seleccionada);
		unset($this->s__datos_comision);
		$this->set_pantalla('seleccion_comision');
	}

	function evt__cancelar_sel_acta()
	{
		unset($this->s__acta_seleccionada);
		$this->set_pantalla('seleccion_acta');
	}

	function evt__cancelar()
	{
		if (guarani::parametros()->get_valor('cur_imprimir_acta_anulada') == comunes::si) {
			$mensaje = 'Se debe imprimir el acta recientemente anulada.';
			throw guarani::error($mensaje);
		}

		$this->cn()->resetear();
		unset($this->s__comision_seleccionada);
		unset($this->s__datos_comision);
		unset($this->s__acta_seleccionada);

		$this->set_pantalla('seleccion_comision');
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	/**
	 * Retorna el HTML de un vínculo (con imagen) a la operación que muestra el detalle de una escala de notas.
	 */
	function get_html_icono_info_escala_notas()
	{
		$vinculo = toba::vinculador()->get_url(toba_proyecto::get_id(), '37000054', array('escala' => $this->s__datos_acta['escala_nota']), array('celda_memoria' => 'escala_notas'));
		$javascript = "abrir_popup('info','$vinculo',{'width':'450','height':'420'})";

		$salida = "<a class='icono-utileria' href='#' onclick=\"$javascript\">";
		$salida .= toba_recurso::imagen_toba('info_chico.gif', true, null, null, 'Ver detalle de escala');
		$salida .= '</a>';

		return $salida;
	}

}
?>