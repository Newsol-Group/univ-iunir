<?php
/**
 *
 *
 * @package Calendario
 * @subpackage Planificacion
 */
class ci_nav_clases extends ci_nav_con_preseleccion
{
	protected $s__comision;
	protected $s__subcomision;
	protected $s__clase;
	protected $s__datos_comision;
	protected $s__datos_subcomision;
	protected $s__datos_clase;
	
	//Componentes Visuales de Información.
	protected $ev_info_comision;
	protected $ev__info_subcomision;
	
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		parent::conf();
		$this->set_sin_listado_inicial_preseleccion();
	}

	function ini()
	{
		parent::set_concepto_preseleccion('Comisión');
	}

	function set_preseleccion($seleccion)
	{
		$this->s__comision = $seleccion['comision'];
	}

	function get_preseleccion()
	{
		if (isset($this->s__comision)) {
			return $this->s__comision;
		} else {
			return null;
		}
	}

	function set_seleccion_subcomision($seleccion)
	{
		$this->s__subcomision = $seleccion['subcomision'];
	}
	
	function get_seleccion_subcomision()
	{
		if (isset($this->s__subcomision)) {
			return $this->s__subcomision;
		} else {
			return null;
		}
	}
	
	function set_seleccion_clase($seleccion)
	{
		$this->s__clase = $seleccion['clase'];
	}
	
	function get_seleccion_clase()
	{
		return $this->s__clase;
	}

	function get_datos_comision()
	{
		return $this->s__datos_comision;
	}
	
	function get_datos_subcomision()
	{
		return $this->s__datos_subcomision;
	}
	
	function get_datos_clase()
	{
		return $this->s__datos_clase;
	}
	
	function hay_clase_seleccionada()
	{
		if (isset($this->s__clase)) {
			return true;
		} else {
			return false;
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- Pantalla Selección Comisión --------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//---- filtro_preselección ----------------------------------------------------------

	function conf__filtro_preseleccion(toba_ei_filtro $filtro)
	{
		parent::conf__filtro_preseleccion($filtro);
		$filtro->columna('anio_academico')->set_condicion_fija('es_igual_a');
		$filtro->columna('periodo_lectivo')->set_condicion_fija('es_igual_a');
		$filtro->columna('actividad')->set_condicion_fija('contiene');
		$filtro->columna('modalidad')->set_condicion_fija('es_igual_a');
	}

	function evt__filtro_preseleccion__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_preseleccion = $datos;
			$filtro_modalidad = '';
			if (isset($datos['modalidad'])) {
				$filtro_modalidad .= " AND EXISTS (SELECT * FROM sga_comisiones_modalidad WHERE sga_comisiones_modalidad.comision = sga_comisiones.comision AND sga_comisiones_modalidad.modalidad = '{$datos['modalidad']['valor']}')";
				$this->dep('filtro_preseleccion')->columna('modalidad')->resetear_estado();
			}
			$this->s__filtro_preseleccion = "{$this->dep('filtro_preseleccion')->get_sql_where()} $filtro_modalidad";
		} else {
			unset($this->s__datos_preseleccion);
			$this->s__filtro_preseleccion = '';
		}
	}

	//---- cuadro_preselección ----------------------------------------------------------
	
	// Se redefine la selección del primer cuadro para pasar a la segunda pantalla (ya que existen 4 pant de selección).
	function evt__cuadro_preseleccion__seleccion($seleccion)
	{
		parent::evt__cuadro_preseleccion__seleccion($seleccion);
		/**
		 * Se verifica si la comisión tiene subcomisiones para ver a qué pantalla se debe pasar.
		 */
		if (toba::consulta_php('co_comisiones')->comision_tiene_subcomisiones($this->get_preseleccion())) {
			$this->set_pantalla('seleccion_subcomision');	
		} else {
			$this->set_pantalla('seleccion');
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Pantalla Selección Subcomisión --------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__seleccion_subcomision(toba_ei_pantalla $pantalla)
	{
		$this->s__datos_comision = current(toba::consulta_php('co_comisiones')->get_datos_comision($this->get_preseleccion()));
		$this->ev_info_comision()->set_datos($this->s__datos_comision);
	}
	
	//---- cuadro -----------------------------------------------------------------------

	function conf__cuadro_subcomisiones(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->get_listado_subcomisiones($this->s__comision);
		$cuadro->set_datos($datos);
	}
	
	// Se extiende el método de selección del ci nav con preselección porque existe una tercer pantalla de Selección.
	function evt__cuadro_subcomisiones__seleccion($seleccion)
	{
		$this->set_seleccion_subcomision($seleccion);
		$this->set_pantalla('seleccion');
	}

	//-----------------------------------------------------------------------------------
	//---- Pantalla Selección Clase -----------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__seleccion(toba_ei_pantalla $pantalla)
	{
		if (!isset($this->s__subcomision)) {
			$this->s__datos_comision = current(toba::consulta_php('co_comisiones')->get_datos_comision($this->get_preseleccion()));
		} else {
			$this->s__datos_subcomision = current(toba::consulta_php('co_comisiones')->get_datos_subcomision($this->s__subcomision));
			$this->ev_info_subcomision()->set_datos($this->s__datos_subcomision);
		}
		$this->ev_info_comision()->set_datos($this->s__datos_comision);
	}
	
	//---- cuadro clases ----------------------------------------------------------------
	
	function conf__cuadro_clases(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof, array('Clases asociadas a la Comisión/Subcomisión seleccionada')));
		$datos = $this->cn()->get_listado_clases($this->s__comision, $this->s__subcomision);
		$cuadro->set_datos($datos);
	}

	function evt__cuadro_clases__seleccion($seleccion)
	{
		$this->set_seleccion_clase($seleccion);
		$this->s__datos_clase = toba::consulta_php('co_clases')->get_datos_clase_asignacion($this->s__clase);
		$this->set_pantalla('edicion');
	}

	//-----------------------------------------------------------------------------------
	//---- Pantalla Edición -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		// Se redefine el ocultamiento del evt Eliminar porque no existe una entidad asociada.
		if ($this->pantalla('edicion')->existe_evento('eliminar')) {
			if (!isset($this->s__clase)) {
				$pantalla->eliminar_evento('eliminar');
			} else {
				$this->evento('eliminar')->set_msg_confirmacion($this->get_msg_confirmar_eliminar());
			}
		}
		// Elementos de visualización.
		$this->ev_info_comision()->set_datos($this->s__datos_comision);
		if (isset($this->s__subcomision)) {
			$this->ev_info_subcomision()->set_datos($this->s__datos_subcomision);
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function evt__cancelar_seleccion_subcomision_comision()
	{
		// En la pantalla de Clases, se retorna a la selección de subcomisión o de comisión según corresponda.
		if (isset($this->s__subcomision)) {
			unset($this->s__subcomision);
			$this->set_pantalla('seleccion_subcomision');	
		} else {
			unset($this->s__comision);
			$this->set_pantalla('preseleccion');
		}
	}
		
	function evt__agregar_clase()
	{
		unset($this->s__clase);
		$this->set_pantalla('edicion');
	}
	
	function evt__eliminar()
	{	
		try {
			if (!is_null($this->s__clase)) {
				$this->cn()->eliminar_clase($this->s__clase);
				toba::notificacion()->agregar($this->get_mensaje('eliminar_ok'), 'info');
				$this->set_pantalla('seleccion');
			}
		} catch (guarani_error $e)	{
			$e->notificar();
		} catch (toba_error $e) {
			toba::notificacion()->agregar($e->getMessage(), 'error');
			toba::logger()->error($e->getMessage());
		}
	}

	function evt__guardar()
	{
		try {
			/**
			 * Obtiene los datos del formulario de edición, y verifica si existe una clase seleccionada para editar sus 
			 * datos o dar de alta una nueva.
			 */
			$datos = $this->dep('editor')->get_datos();
			if (!is_null($this->s__clase)) {
				$this->cn()->modificar_clase($this->s__clase, $datos);
			} else {
				$this->cn()->agregar_clase($this->s__comision, $this->s__subcomision, $datos);
			}
			if ($this->volver_al_guardar) {
				$this->cn()->resetear();
				$this->set_pantalla('seleccion');
			}
			toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');
		} catch (guarani_error $e)	{
			throw $e;
		} 
	}

	//-----------------------------------------------------------------------------------
	//---- evs_info ---------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function ev_info_comision()
	{
		if (!isset($this->ev_info_comision)) {
			$this->ev_info_comision = guarani::ev_info_datos();
			$this->ev_info_comision->set_controlador($this);
			$this->ev_info_comision->set_titulo('Datos de la Comisión');
			
			$this->ev_info_comision->set_columnas_a_mostrar(array('anio_academico', 'periodo_lectivo_nombre', 'actividad_desc', 'comision_nombre', 'ubicacion_nombre', 'turno_nombre', 'modalidad_nombre'));
			$this->ev_info_comision->set_columnas_fila(array('anio_academico', 'periodo_lectivo_nombre'), 1);
			$this->ev_info_comision->set_columnas_fila(array('actividad_desc', 'comision_nombre'), 2);
			$this->ev_info_comision->set_columnas_fila(array('turno_nombre', 'modalidad_nombre', 'ubicacion_nombre'), 3);
			
			$this->ev_info_comision->set_max_columnas_por_fila(3);
			
			$this->ev_info_comision->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info_comision->set_etiqueta('periodo_lectivo_nombre', 'P. Lectivo');
			$this->ev_info_comision->set_etiqueta('actividad_desc', 'Actividad');
			$this->ev_info_comision->set_etiqueta('comision_nombre', 'Comisión');
			$this->ev_info_comision->set_etiqueta('turno_nombre', 'Turno');
			$this->ev_info_comision->set_etiqueta('modalidad_nombre', 'Modalidad');
			$this->ev_info_comision->set_etiqueta('ubicacion_nombre', 'Ubicación');
			
			$this->ev_info_comision->set_td_etiqueta_width('100px');
			$this->ev_info_comision->set_td_valores_width('200px');
		}
		return $this->ev_info_comision;
	}
	
	function ev_info_subcomision()
	{
		if (!isset($this->ev_info_subcomision)) {
			$this->ev_info_subcomision = guarani::ev_info_datos();
			$this->ev_info_subcomision->set_controlador($this);
			$this->ev_info_subcomision->set_titulo('Datos de la Subcomisión');
			
			$this->ev_info_subcomision->set_columnas_a_mostrar(array('subcomision_nombre', 'tipo_clase_nombre'));
			$this->ev_info_subcomision->set_columnas_fila(array('subcomision_nombre', 'tipo_clase_nombre'), 1);
			
			$this->ev_info_subcomision->set_max_columnas_por_fila(2);
			
			$this->ev_info_subcomision->set_etiqueta('subcomision_nombre', 'Subcomisión');
			$this->ev_info_subcomision->set_etiqueta('tipo_clase_nombre', 'Tipo');
			
			$this->ev_info_subcomision->set_td_etiqueta_width('80px');
		}
		return $this->ev_info_subcomision;
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	// Se personaliza el evento js guardar para poder personalizar el comportamiento al invalidar una Clase.
	function js_evt_guardar() 
	{
		$msj_alerta_invalidacion_clase = guarani_mensajes::get('800CAL_baja_clase_asistencia_temas');
		$id_form_js = $this->dep('editor')->dep('formulario')->get_id_objeto_js();
		
		return "
			function confirmar_invalidacion_clase(proyecto, operacion, url, es_popup) {
				if (! es_popup || typeof(es_popup) == 'undefined') {
					var valido = {$id_form_js}.ef('valido').get_estado();
					if (valido == 0) {
						resp = confirm('$msj_alerta_invalidacion_clase');
						if (! resp) {
							return false;
						}
					}
				}
				return true;
			}
			
			{$this->objeto_js}.evt__guardar = function() {
				if (confirmar_no_cambios()) {
					return confirmar_invalidacion_clase();
				} else {
					return false;
				}
			}
		";
	}
}
?>