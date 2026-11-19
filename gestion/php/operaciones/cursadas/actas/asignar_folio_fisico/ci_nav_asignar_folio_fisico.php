<?php
/**
 *
 *
 * @package 	Cursadas
 * @subpackage 	Actas
 * 
 */
class ci_nav_asignar_folio_fisico extends ci_navegacion
{
	protected $s__comision_seleccionada;
	protected $s__acta_seleccionada;
	protected $s__datos_acta;
	
	// Componente Visual de Información
	protected $ev_info_comision;

	//-----------------------------------------------------------------------------------
	//---- Operaciones auxiliares -------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function es_acta_regulares()
	{
		return ($this->s__datos_acta['origen'] == acta::origen_regulares);
	}

	function get_datos_acta()
	{
		return $this->s__datos_acta;
	}

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

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: SELECCIÓN DE COMISIÓN 
	//-----------------------------------------------------------------------------------
	
	function conf__seleccion_comision(toba_ei_pantalla $pantalla)
	{
		$this->cn()->resetear_atributos_parametros();
		$this->cn()->resetear_dependencias_parametros();
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
	//---- cuadro_comision --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_comision(toba_ei_cuadro $cuadro)
	{
		if (!isset($this->s__filtro)) {
			$msg_eof = guarani_mensajes::get($this->indx_msg_eof . '_sinfiltro');
			$cuadro->set_eof_mensaje($msg_eof);
		} else {            
			$datos = toba::consulta_php('co_comisiones')->get_listado_con_cant_actas_impresas_no_anuladas($this->s__filtro);
			$cuadro->set_datos($datos);

			if (empty($datos)) {
				$indx_msg = $this->indx_msg_eof;
				if (isset($this->s__filtro) && !empty($this->s__filtro)) {
					$indx_msg = $this->indx_msg_eof . '_filtrado';
				}
				$etiquetas = guarani_etiquetas::get_tabla('sga_comisiones');
				$etiqueta = $etiquetas->get_enf('plural');
				$msg_eof = guarani_mensajes::get($indx_msg, array($etiqueta));
				$cuadro->set_eof_mensaje($msg_eof);
			}
		}
	}

	function conf_evt__cuadro_comision__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dependencia('cuadro_comision')->get_datos();
		if ($datos[$fila]['cant_actas_impresas_no_anuladas'] == 0) {
			$evento->anular();
		}
	}

	function evt__cuadro_comision__seleccion($seleccion)
	{
		$this->s__comision_seleccionada = $seleccion;
		$this->set_pantalla('seleccion_acta');
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: SELECCIÓN DE ACTA 
	//-----------------------------------------------------------------------------------

	function conf__seleccion_acta()
	{
		$filtro = "sga_comisiones.entidad = {$this->get_comision_seleccionada()}";
		$comision = current(toba::consulta_php('co_comisiones')->get_listado($filtro));
		$this->ev_info_comision()->set_datos($comision);
		// Reset de atributos de parámetros
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

		// NO se muestran las actas anuladas.
		$estado_anulada = toba::db()->quote(acta::estado_anulada);
		$filtro .= " AND sga_actas.estado <> $estado_anulada";

		// NO se muestran las actas digitales.
		$filtro .= ' AND sga_actas.estado_documento_digital IS NULL';

		$datos_actas = toba::consulta_php('co_actas')->get_listado($filtro);

		foreach ($datos_actas as $clave => $valor) {
			if ($datos_actas[$clave]['estado'] == acta::estado_abierta) {
				$datos_actas[$clave]['estado_semaforo'] = toba_recurso::imagen_proyecto('iconos_estados/verde.png', true, null, null, 'Acta Abierta', null);
			} elseif ($datos_actas[$clave]['estado'] == acta::estado_cerrada) {
				$datos_actas[$clave]['estado_semaforo'] = toba_recurso::imagen_proyecto('iconos_estados/rojo.png', true, null, null, 'Acta Cerrada', null);
			}
		}

		$cuadro->set_datos($datos_actas);

		if (empty($datos_actas)) {
			$etiquetas = guarani_etiquetas::get_tabla('sga_actas');
			$etiqueta = $etiquetas->get_enf('plural');
			$msg_eof = guarani_mensajes::get($this->indx_msg_eof, array($etiqueta));
			$cuadro->set_eof_mensaje($msg_eof);
		}
	}

	function evt__cuadro_actas__seleccion($seleccion)
	{
		$this->s__acta_seleccionada = $seleccion;
		$this->s__datos_acta = toba::consulta_php('co_actas')->get_datos_acta($this->get_acta_seleccionada());
		
		// Carga de atributos de parámetros
		$this->cn()->cargar_atributos_parametros($this->s__datos_acta['comision']);
		$this->cn()->cargar_dependencias_parametros($this->s__datos_acta['origen'], $this->s__datos_acta['digital']);

		$this->set_pantalla('edicion');
	}

	function conf_evt__cuadro_actas__seleccion(toba_evento_usuario $evento, $fila)
	{
		// El botón de selección sólo estará disponible cuando el acta esté CERRADA.
		$datos = $this->dep('cuadro_actas')->get_datos();
		if ($datos[$fila]['estado'] != acta::estado_cerrada) {
			$evento->anular();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: EDICIÓN
	//-----------------------------------------------------------------------------------

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cancelar_preseleccion()
	{
		unset($this->s__comision_seleccionada);
		$this->set_pantalla('seleccion_comision');
	}

	function evt__cancelar()
	{
		unset($this->s__acta_seleccionada);
		unset($this->s__datos_acta);
		$this->dep('editor')->resetear_variables();
		$this->set_pantalla('seleccion_acta');
	}

	function evt__guardar()
	{
		$id_acta = $this->get_acta_seleccionada();
		$libro_tomo = $this->dep('editor')->get_libro_tomo();
		$folios = $this->dep('editor')->get_folios();

		$this->cn()->actualizar_asignaciones_ff($id_acta, $libro_tomo, $folios);

		$this->resetear();
		$this->set_pantalla('seleccion_comision');
		toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');
	}

	function resetear()
	{
		unset($this->s__acta_seleccionada);
		unset($this->s__datos_acta);
		unset($this->s__comision_seleccionada);
		$this->dep('editor')->resetear_variables();
	}

}