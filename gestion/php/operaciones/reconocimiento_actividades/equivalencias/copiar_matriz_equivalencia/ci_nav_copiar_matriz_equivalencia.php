<?php
/**
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Equivalencias
 */
class ci_nav_copiar_matriz_equivalencia extends ci_navegacion
{
	// Selección de la Propuesta-Plan-Versión desde y hacia.
	protected $s__prop_plan_version_desde;
	protected $s__datos_prop_plan_version_desde;
	protected $s__prop_plan_version_hacia;
	protected $s__datos_prop_plan_version_hacia;

	// Matrices a copiar y no copiadas.
	protected $matrices_a_copiar;
	protected $s__matrices_no_copiadas;
	
	// Elementos Visuales de Información.
	protected $ev_info_plan_desde;
	protected $ev_info_matrices_desde;
	protected $ev_info_matrices_hacia;
	protected $ev_info_matrices_no_copiadas;
	
	// Mensajes.
	protected $msj_no_seleccion 		 = '800_no_seleccion';
	protected $msj_copiar_matrices_ok 	 = '800EQU_copiar_matrices_ok';
	protected $msj_copiar_matrices_error = '800EQU_copiar_matrices_error';
	
	// Id pantalla anterior
	protected $s__pantalla_anterior;

	function conf()
	{
		parent::conf();
		$this->s__pantalla_anterior = $this->get_id_pantalla_anterior();
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA DESDE ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//---- cuadro_desde -----------------------------------------------------------------
	
	function conf__cuadro_desde(toba_ei_cuadro $cuadro)
	{
		$indx_msj = $this->indx_msg_eof;
		if (!isset($this->s__filtro)) {
			$indx_msj .= '_sinfiltro';
			$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj));
		} else {
			$indx_msj = $this->indx_msg_eof . '_filtrado';
			$etiqueta = guarani_etiquetas::get_tabla('sga_planes');
			$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj, array($etiqueta->get_enf('plural'))));

			$datos = $this->cn()->get_propuestas_planes_versiones_desde($this->s__filtro);
			$cuadro->set_datos($datos);
		}
	}

	function evt__cuadro_desde__seleccion($seleccion)
	{
		$this->s__prop_plan_version_desde = $seleccion;
		$this->set_pantalla('pant_sel_plan_hacia');
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA HACIA ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//---- Configuraciones --------------------------------------------------------------
	
	function conf__pant_sel_plan_hacia(toba_ei_pantalla $pantalla)
	{
		$datos_prop_plan_version_desde = $this->get_datos_prop_plan_version_desde();
		$this->ev_info_plan_desde()->set_datos($datos_prop_plan_version_desde);
	}
	
	//---- cuadro_hacia -----------------------------------------------------------------

	/**
	 * @todo Mensaje de EOF?
	 */
	function conf__cuadro_hacia(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('sga_planes');
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof, array($etiqueta->get_enf('plural'))));

		$datos = $this->cn()->get_propuestas_planes_versiones_hacia($this->s__prop_plan_version_desde['propuesta'], $this->s__prop_plan_version_desde['plan_version']);
		$cuadro->set_datos($datos);
	}

	function evt__cuadro_hacia__seleccion($seleccion)
	{
		$this->s__prop_plan_version_hacia = $seleccion;
		$this->set_pantalla('pant_sel_matrices');
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA MATRICES ------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//---- Configuraciones --------------------------------------------------------------
	
	function conf__pant_sel_matrices(toba_ei_pantalla $pantalla)
	{
		$datos_prop_plan_version_desde = $this->get_datos_prop_plan_version_desde();
		$datos_prop_plan_version_hacia = $this->get_datos_prop_plan_version_hacia();
		$this->ev_info_matrices_desde()->set_datos($datos_prop_plan_version_desde);
		$this->ev_info_matrices_hacia()->set_datos($datos_prop_plan_version_hacia);
	}
	
	//---- cuadro_matrices_hacia --------------------------------------------------------
	
	function conf__cuadro_matrices_hacia(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('sga_equiv_matrices');
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof, array($etiqueta->get_enf('plural'))));

		$datos = $this->cn()->get_matrices_equivalencia($this->s__prop_plan_version_hacia['plan_version']);
		foreach ($datos as $clave => $valor) {
			$datos[$clave]['icono_info_grupos'] = $this->get_html_icono_info_grupos_equivalencia($datos[$clave]['matriz']);
		}
		$cuadro->set_datos($datos);
	}
	
	//---- cuadro_matrices_desde --------------------------------------------------------
	
	function conf__cuadro_matrices_desde(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->get_matrices_equivalencia($this->s__prop_plan_version_desde['plan_version']);
		foreach ($datos as $clave => $valor) {
			$datos[$clave]['icono_info_grupos'] = $this->get_html_icono_info_grupos_equivalencia($datos[$clave]['matriz']);
		}
		$cuadro->set_datos($datos);
	}

	function evt__cuadro_matrices_desde__seleccion($seleccion)
	{
		$this->matrices_a_copiar = $seleccion;
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA RESULTADOS ----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//---- Configuraciones --------------------------------------------------------------
	
	function conf__pant_resultados(toba_ei_pantalla $pantalla)
	{
		$datos_prop_plan_version_hacia = $this->get_datos_prop_plan_version_hacia();
		$this->ev_info_matrices_hacia()->set_datos($datos_prop_plan_version_hacia);

		if (!empty($this->s__matrices_no_copiadas)) {
			$datos_prop_plan_version_desde = $this->get_datos_prop_plan_version_desde();
			$this->ev_info_matrices_no_copiadas()->set_datos($datos_prop_plan_version_desde);
		}

		// Configuración del vínculo para la visualización de matrices.
		$this->evento('ver_matrices')->vinculo()->set_parametros($this->s__prop_plan_version_hacia);
	}
	
	//---- cuadro_matrices_no_copiadas --------------------------------------------------
	
	function conf__cuadro_matrices_no_copiadas(toba_ei_cuadro $cuadro)
	{
		if (!empty($this->s__matrices_no_copiadas)) {
			$filtro = guarani_sql::armar_condicion_in($this->s__matrices_no_copiadas, 'sga_equiv_matrices', 'matriz');
			$datos = $this->cn()->get_matrices_equivalencia(null, $filtro);
			$cuadro->set_datos($datos);
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- Configuraciones Generales ----------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ev_info_plan_desde()
	{
		if (!isset($this->ev_info_plan_desde)) {
			$this->ev_info_plan_desde = guarani::ev_info_datos();
			$this->ev_info_plan_desde->set_controlador($this);
			$this->ev_info_plan_desde->set_style('width:100%;');
			$this->ev_info_plan_desde->set_columnas_a_mostrar(array('propuesta_descripcion', 'plan_nombre', 'plan_estado_descripcion', 'fecha_entrada_vigencia_format', 'version_nombre', 'version_estado_descripcion'));
			$this->ev_info_plan_desde->set_columnas_fila(array('propuesta_descripcion'), 1);
			$this->ev_info_plan_desde->set_columnas_fila(array('plan_nombre'), 2);
			$this->ev_info_plan_desde->set_columnas_fila(array('plan_estado_descripcion'), 3);			
			$this->ev_info_plan_desde->set_columnas_fila(array('version_nombre'), 4);
			$this->ev_info_plan_desde->set_columnas_fila(array('version_estado_descripcion', 'fecha_entrada_vigencia_format'), 5);
			$this->ev_info_plan_desde->set_etiqueta('propuesta_descripcion', 'Propuesta:');
			$this->ev_info_plan_desde->set_etiqueta('plan_nombre', 'Plan:');
			$this->ev_info_plan_desde->set_etiqueta('plan_estado_descripcion', 'Estado Plan:');
			$this->ev_info_plan_desde->set_etiqueta('version_nombre', 'Versión:');
			$this->ev_info_plan_desde->set_etiqueta('version_estado_descripcion', 'Estado Versión:');
			$this->ev_info_plan_desde->set_etiqueta('fecha_entrada_vigencia_format', 'Fecha Vigencia:');
			
		}
		return $this->ev_info_plan_desde;
	}
	
	function ev_info_matrices_desde()
	{
		if (!isset($this->ev_info_matrices_desde)) {
			$this->ev_info_matrices_desde = guarani::ev_info_datos();
			$this->ev_info_matrices_desde->set_controlador($this);
			$this->ev_info_matrices_desde->set_style('width:100%;');
			$this->ev_info_matrices_desde->set_columnas_a_mostrar(array('plan_nombre', 'version_nombre'));
  		$this->ev_info_matrices_desde->set_columnas_fila(array('plan_nombre'), 1);
			$this->ev_info_matrices_desde->set_columnas_fila(array('version_nombre'), 2);			
			$this->ev_info_matrices_desde->set_etiqueta('plan_nombre', 'Plan:');
			$this->ev_info_matrices_desde->set_etiqueta('version_nombre', 'Versión:');
		}
		return $this->ev_info_matrices_desde;
	}
	
	function ev_info_matrices_hacia()
	{
		if (!isset($this->ev_info_matrices_hacia)) {
			$this->ev_info_matrices_hacia = guarani::ev_info_datos();
			$this->ev_info_matrices_hacia->set_controlador($this);
			$this->ev_info_matrices_hacia->set_style('width:100%;');
			$this->ev_info_matrices_hacia->set_columnas_a_mostrar(array('propuesta_descripcion', 'plan_nombre', 'version_nombre'));
			$this->ev_info_matrices_hacia->set_columnas_fila(array('propuesta_descripcion'), 1);
			$this->ev_info_matrices_hacia->set_columnas_fila(array('plan_nombre'), 2);
			$this->ev_info_matrices_hacia->set_columnas_fila(array('version_nombre'), 3);
			$this->ev_info_matrices_hacia->set_etiqueta('propuesta_descripcion', 'Propuesta:');
			$this->ev_info_matrices_hacia->set_etiqueta('plan_nombre', 'Plan:');
			$this->ev_info_matrices_hacia->set_etiqueta('version_nombre', 'Versión:');
		}
		return $this->ev_info_matrices_hacia;
	}
	
	function ev_info_matrices_no_copiadas()
	{
		if (!isset($this->ev_info_matrices_no_copiadas)) {
			$this->ev_info_matrices_no_copiadas = guarani::ev_info_datos();
			$this->ev_info_matrices_no_copiadas->set_controlador($this);
			$this->ev_info_matrices_no_copiadas->set_columnas_a_mostrar(array('propuesta_descripcion', 'plan_nombre', 'version_nombre'));
			$this->ev_info_matrices_no_copiadas->set_columnas_fila(array('propuesta_descripcion'), 1);
			$this->ev_info_matrices_no_copiadas->set_columnas_fila(array('plan_nombre'), 2);
			$this->ev_info_matrices_no_copiadas->set_columnas_fila(array('version_nombre'), 3);
			$this->ev_info_matrices_no_copiadas->set_etiqueta('propuesta_descripcion', 'Matrices de:');
			$this->ev_info_matrices_no_copiadas->set_etiqueta('plan_nombre', 'Plan:');
			$this->ev_info_matrices_no_copiadas->set_etiqueta('version_nombre', 'Versión:');
		}
		return $this->ev_info_matrices_no_copiadas;
	}
	
	//-----------------------------------------------------------------------------------
	//---- operaciones auxiliares -------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Retorna el HTML de un vínculo (con imagen) a la operación que muestra el detalle de
	 * las Actividades de los Grupos Origen y Destino de la Matriz de Equivalencia.
	 */
	function get_html_icono_info_grupos_equivalencia($matriz_equivalencia)
	{
		$vinculo = toba::vinculador()->get_url(toba_proyecto::get_id(), '38000104', array('matriz_equivalencia'=>$matriz_equivalencia), array('celda_memoria'=>'grupos_equivalencia'));
		$javascript = "abrir_popup('info','$vinculo',{'width':'850','height':'630'})";

		$salida	 = "<a class='icono-utileria' href='#' onclick=\"$javascript\">";
		$salida .= toba_recurso::imagen_toba('info_chico.gif', true, null, null, 'Ver detalles de grupos');
		$salida .= '</a>';

		return $salida;
	}

	function get_datos_prop_plan_version_desde()
	{
		if (!isset($this->s__datos_prop_plan_version_desde)) {
			$this->s__datos_prop_plan_version_desde = current(toba::consulta_php('co_planes')->get_datos_plan_version($this->s__prop_plan_version_desde['plan_version']));
		}
		return $this->s__datos_prop_plan_version_desde;
	}

	function get_datos_prop_plan_version_hacia()
	{
		if (!isset($this->s__datos_prop_plan_version_hacia)) {
			$this->s__datos_prop_plan_version_hacia = current(toba::consulta_php('co_planes')->get_datos_plan_version($this->s__prop_plan_version_hacia['plan_version']));
		}
		return $this->s__datos_prop_plan_version_hacia;
	}

	function get_matrices_no_copiadas()
	{
		return $this->s__matrices_no_copiadas;
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__volver()
	{
		if ($this->s__pantalla_anterior == 'pant_sel_plan_hacia') {
			unset($this->s__prop_plan_version_hacia);
			unset($this->s__datos_prop_plan_version_hacia);
			unset($this->s__matrices_no_copiadas);
		} elseif ($this->s__pantalla_anterior == 'pant_sel_plan_desde') {
			unset($this->s__prop_plan_version_desde);
			unset($this->s__datos_prop_plan_version_desde);
		}
		$this->set_pantalla($this->s__pantalla_anterior);
	}

	function evt__copiar()
	{
		if (empty($this->matrices_a_copiar)) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_equiv_matrices');
			$mensaje_no_seleccion = guarani_mensajes::get($this->msj_no_seleccion, array($etiqueta->get('una')));
			throw guarani::error($mensaje_no_seleccion);
		}

		$this->s__matrices_no_copiadas = $this->cn()->copiar_matrices_equivalencia('null', $this->s__prop_plan_version_hacia['plan_version'], $this->matrices_a_copiar);
		if (empty($this->s__matrices_no_copiadas)) {
			$mensaje = guarani_mensajes::get($this->msj_copiar_matrices_ok);
			$tipo_mensaje = comunes::error_informacion;
		} else {
			$mensaje = guarani_mensajes::get($this->msj_copiar_matrices_error);
			$tipo_mensaje = comunes::error_critico;
		}

		toba::notificacion()->agregar($mensaje, $tipo_mensaje);
		$this->set_pantalla('pant_resultados');
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		if ($this->get_id_pantalla() == 'pant_sel_matrices') {
			$etiqueta = guarani_etiquetas::get_tabla('sga_equiv_matrices');
			$mensaje_no_seleccion = guarani_mensajes::get($this->msj_no_seleccion, array($etiqueta->get('una')));
			$id_js_cuadro_desde = $this->dependencia('cuadro_matrices_desde')->get_id_objeto_js();
			
			echo "
			//---- Eventos ---------------------------------------------
			
			{$this->objeto_js}.evt__copiar = function()
			{
				var seleccion = {$id_js_cuadro_desde}.get_ids_seleccionados('seleccion');
				if (seleccion.length == 0) {
					alert('$mensaje_no_seleccion');
					return false;
				}
				return true;
			}
			";
		}
	}
}
?>