<?php
/**
 *
 *
 * @package Cursadas
 * @subpackage Actas
 * 
 */
class ci_nav_cierre_promocionales extends ci_navegacion
{
	// Comisión y acta seleccionadas.
	protected $s__comision_seleccionada;
	protected $s__acta_seleccionada;
	protected $s__datos_acta;
	
	// Selección manual de libro / tomo.
	protected $s__libro_tomo;
	
	// Datos de los alumnos asociados al acta seleccionada.
	protected $s__datos_alumnos;
	
	// Componentes Visuales de Información de Datos de Comisiones y Actas.
	protected $ev_info_comision;
	protected $ev_info_acta;

	function ini()
	{
		$this->cn()->desactivar_lock_optimista();
		$volvio_imp = toba::memoria()->get_parametro('volvio_imp');
		$volvio_edit = toba::memoria()->get_parametro('volvio_edit');
		if (isset($volvio_imp) || isset($volvio_edit)) {
			$seleccion = array('id_acta' => toba::memoria()->get_parametro('id_acta'));
			$this->s__comision_seleccionada['entidad'] = toba::zona()->get_editable();
			$this->evt__cuadro_actas__seleccion($seleccion);
		} elseif (toba::zona()->cargada() && !$this->cn()->entidad_cargada()) {
			$this->s__comision_seleccionada['entidad'] = toba::zona()->get_editable();
			$this->set_pantalla('seleccion_acta');
		}
	}

	function get_libros_tomos_actas()
	{
		return toba::consulta_php('co_actas')->get_libros_tomos_actas($this->s__datos_acta);
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
	}
	
	function cargar_atributos_parametros()
	{
		$filtro = "sga_comisiones.entidad = {$this->get_comision_seleccionada()}";
		$datos_comision = current(toba::consulta_php('co_comisiones')->get_listado($filtro));
		$this->cn()->cargar_atributos_parametros($datos_comision['comision']);
		$this->cn()->cargar_dependencias_cierre_promo($this->s__datos_acta['digital']);
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

			// Se muestran sólo comisiones que tienen asociada la instancia PROMOCIONAR.
			$instancia_promocionar = comision::inst_promocionar;
			$this->s__filtro .= " AND EXISTS (SELECT * FROM sga_comisiones_instancias WHERE sga_comisiones_instancias.comision = sga_comisiones.comision AND sga_comisiones_instancias.instancia = $instancia_promocionar)";

			$datos = toba::consulta_php('co_comisiones')->get_listado_con_cant_actas_no_anuladas($instancia_promocionar, $this->s__filtro);
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
															sga_actas.origen = '" . acta::origen_promociones . "' AND
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

		// Se muestran sólo actas de promoción.
		$origen_promocionales = toba::db()->quote(acta::origen_promociones);
		$filtro .= " AND sga_actas.origen = $origen_promocionales";

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

		$msj_eof = $this->get_msg_eof('Actas');
		$cuadro->set_eof_mensaje($msj_eof);
	}

	function evt__cuadro_actas__seleccion($seleccion)
	{
		$this->s__acta_seleccionada = $seleccion;
		$this->s__datos_acta = toba::consulta_php('co_actas')->get_datos_acta($this->get_acta_seleccionada());
		$this->seleccionar();
		$this->set_pantalla('previsualizacion');
		$this->cargar_atributos_parametros();
	}

	function conf_evt__cuadro_actas__seleccion(toba_evento_usuario $evento, $fila)
	{
		// Si el acta está cerrada no se deberá mostrar el botón de selección.
		$datos = $this->dep('cuadro_actas')->get_datos();
		if ($datos[$fila]['estado'] == acta::estado_cerrada) {
			$evento->anular();
		}
	}

	//--------------------------------------------------------------------------------------------------------------------
	//---- PANTALLA PREVISUALIZACIÓN -------------------------------------------------------------------------------------
	//--------------------------------------------------------------------------------------------------------------------

	function conf__previsualizacion()
	{
		$datos = $this->s__datos_acta;
		$datos['icono_info_escala_notas'] = $this->get_html_icono_info_escala_notas();
		$this->ev_info_acta()->set_datos($datos);

		$operacion = guarani::get_id_operacion_toba();
		$celda_actual = toba::memoria()->get_celda_memoria_actual_id();
		$id_acta = $this->get_acta_seleccionada();

		// Configuración del vínculo para la impresión del acta.
		$this->evento('imprimir')->vinculo()->set_parametros(array('operacion_a_volver' => $operacion, 'celda_a_restaurar' => $celda_actual, 'id_acta' => $id_acta));
		$this->evento('imprimir')->vinculo()->set_celda_memoria('celda_imp');

		// Configuración del vínculo para la edición del acta.
		$this->evento('editar')->vinculo()->set_parametros(array('operacion_a_volver' => $operacion, 'celda_a_restaurar' => $celda_actual, 'id_acta' => $id_acta));
		$this->evento('editar')->vinculo()->set_celda_memoria('celda_edit');
	}

	function ev_info_acta()
	{
		if (!isset($this->ev_info_acta)) {
			$this->ev_info_acta = guarani::ev_info_datos();

			$this->ev_info_acta->set_columnas_a_mostrar(array('nro_acta', 'libro_nombre', 'anio_academico_periodo', 'periodo_nombre', 'comision_nombre', 'modalidad_nombre', 'codigo_y_nombre_actividad', 'nombre_ubicacion', 'icono_info_escala_notas', 'nombre_turno', 'observaciones'));

			$this->ev_info_acta->set_columnas_fila(array('nro_acta', 'comision_nombre', 'libro_nombre'), 1);
			$this->ev_info_acta->set_columnas_fila(array('anio_academico_periodo', 'periodo_nombre', 'modalidad_nombre'), 2);
			$this->ev_info_acta->set_columnas_fila(array('codigo_y_nombre_actividad', 'nombre_ubicacion', 'icono_info_escala_notas'), 3);
			$this->ev_info_acta->set_columnas_fila(array('nombre_turno'), 4);
			$this->ev_info_acta->set_columnas_fila(array('observaciones'), 5);

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
			$this->ev_info_acta->set_etiqueta('observaciones', 'Observaciones');
		}
		return $this->ev_info_acta;
	}

	//-----------------------------------------------------------------------------------
	//---- form_libro -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_libro(toba_ei_formulario $form)
	{
		if (!empty($this->s__libro_tomo)) {
			$form->set_datos(array('libro_tomo' => $this->s__libro_tomo));
		}
	}

	function evt__form_libro__modificacion($datos)
	{
		if (!empty($datos['libro_tomo'])) {
			$this->s__libro_tomo = $datos['libro_tomo'];
		}
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_alumnos ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_alumnos(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_tamanio_pagina($this->s__datos_acta['renglones_folio']);

		$params_sistema = array(
			'cur_inserta_alumnos_no_promocionales_en_acta' => guarani::parametros()->get_valor('cur_inserta_alumnos_no_promocionales_en_acta'),
			'cur_orden_detalle_actas' => guarani::parametros()->get_valor('cur_orden_detalle_actas'),
		);		
		$this->s__datos_alumnos = toba::consulta_php('co_actas')->get_alumnos_acta_cursadas($this->get_acta_seleccionada(), comision::inst_promocionar, '', '', '', false, $params_sistema);
		$cuadro->set_datos($this->s__datos_alumnos);

		$msj_eof = $this->get_msg_eof('Alumnos');
		$cuadro->set_eof_mensaje($msj_eof);
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cerrar()
	{
		$this->cn()->cerrar_acta($this->get_acta_seleccionada(), $this->s__datos_alumnos, $this->s__libro_tomo);

		unset($this->s__comision_seleccionada);
		unset($this->s__acta_seleccionada);
		toba::zona()->resetear();
		$this->set_pantalla('seleccion_comision');

		toba::notificacion()->agregar('Se ha cerrado el acta con éxito.', 'info');
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
		unset($this->s__acta_seleccionada);
		$this->set_pantalla('seleccion_acta');
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