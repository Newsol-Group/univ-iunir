<?php
/**
 *
 *
 * @package		Matrícula
 * @subpackage 	Alumnos
 * 
 */
class ci_nav_pasar_a_calidad_pasivo extends ci_nav_procesos_bk_wizzard
{
	protected $datos_ubicacion_temp;

	// Alumnos a pasar a calidad pasivo
	protected $datos_alumnos_temp;
	protected $s__datos_alumnos;
	protected $s__alumnos;

	// Componente Visual de información.
	protected $ev_info;

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		parent::conf();
		$this->indx_msg_eof = '800_eof_cuadro';
	}

	//-----------------------------------------------------------------------------------
	//---- Pantalla Propuestas ----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__propuestas(toba_ei_pantalla $pantalla)
	{
		if (isset($this->s__datos_alumnos)) {
			unset($this->s__datos_alumnos);
		}
	}

	//---- form_ubicacion ---------------------------------------------------------------

	function conf__form_ubicacion(toba_ei_formulario $form)
	{
		if (isset($this->datos_ubicacion_temp)) {
			$form->set_datos($this->datos_ubicacion_temp);
		} elseif ($this->existe_seleccion('ubicacion')) {
			$datos = $this->get_seleccion_valor('ubicacion');
			$form->set_datos($datos);
		}
	}

	function evt__form_ubicacion__modificacion($datos)
	{
		$this->datos_ubicacion_temp = $datos;

		$datos_ubicacion = toba::consulta_php('co_ubicaciones')->get_ubicacion_info($datos['ubicacion']);
		$datos['ubicacion_nombre'] = $datos_ubicacion['ubicacion_nombre'];

		$this->set_seleccion($datos);
	}

	//---- filtro_propuestas ------------------------------------------------------------

	function conf__filtro_propuestas(guarani_ei_filtro $filtro)
	{
		$this->filtro_configurar('filtro_propuestas', false);
	}

	function evt__filtro_propuestas__filtrar($datos)
	{
		$nombre = 'filtro_propuestas';
		if (isset($datos) && !empty($datos)) {
			$this->s__datos_filtros[$nombre] = $datos;

			$filtro_ra = ' AND sga_propuestas.propuesta';
			$filtro_ra .= ($datos['responsable_academica']['condicion'] == 'es_igual_a') ? ' IN ' : ' NOT IN ';
			$filtro_ra .= "(SELECT sga_propuestas_ra.propuesta FROM sga_propuestas_ra WHERE sga_propuestas_ra.responsable_academica = {$datos['responsable_academica']['valor']})";
			$this->dep($nombre)->columna('responsable_academica')->resetear_estado();

			$this->s__filtros[$nombre] = "{$this->dep($nombre)->get_sql_where()} $filtro_ra";
		} else {
			$this->filtro_limpiar($nombre);
			$this->s__filtros[$nombre] = '';
		}
	}

	function evt__filtro_propuestas__cancelar()
	{
		$this->filtro_limpiar('filtro_propuestas');
	}

	//---- cuadro_propuestas ------------------------------------------------------------

	function conf__cuadro_propuestas(cuadro_seleccion_multiple $cuadro)
	{
		$filtro = $this->get_filtro('filtro_propuestas');
		
		if (empty($filtro)) {
			$this->indx_msg_eof = '800_eof_cuadro_sinfiltro';
			$cuadro->set_eof_mensaje($this->get_msg_eof());	
		} else {
			$cuadro->set_eof_mensaje($this->get_filtro_msg_eof('filtro_propuestas', 'sga_propuestas'));
			$filtro .= "AND EXISTS (SELECT	sga_alumnos.alumno
									FROM	sga_alumnos
									WHERE	sga_alumnos.plan_version = sga_planes_versiones.plan_version AND
											sga_alumnos.calidad = '".alumno::calidad_activo."')";
			$datos = toba::consulta_php('co_propuestas')->get_propuestas_planes_versiones_activos($filtro);
			$cuadro->set_datos($datos);
		}
	}

	function evt__cuadro_propuestas__seleccion($seleccion)
	{
		if ($seleccion) {
			$this->set_seleccion($seleccion);
		} else {
			$this->eliminar_seleccion_previa();
		}
	}

	function conf_evt__cuadro_propuestas__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_fila_seleccionada('cuadro_propuestas', $fila));
	}

	function validar_propuestas()
	{
		if ($this->falta_seleccionar('plan')) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('una propuesta'));
			throw guarani::error($mje);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Pantalla Alumnos -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__alumnos(toba_ei_pantalla $pantalla)
	{
		$this->ev_info()->set_datos($this->get_seleccion());
	}

	//---- form_alumnos -----------------------------------------------------------------

	function conf__form_alumnos(toba_ei_formulario $form)
	{
		if (isset($this->datos_alumnos_temp)) {
			$form->set_datos($this->datos_alumnos_temp);
		}
	}

	function evt__form_alumnos__modificacion($datos)
	{
		$this->datos_alumnos_temp = $datos;
		$this->set_seleccion($datos);
	}

	//---- ev_info ----------------------------------------------------------------------
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_max_datos_por_columna(10);
			$this->ev_info->set_max_chars_por_columna(110);
			$this->ev_info->set_td_etiqueta_width('80px');
   			
			$this->ev_info->set_columnas_a_mostrar(array('ubicacion_nombre', 'propuesta_nombre', 'tipo_alumnos'));
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
			$this->ev_info->set_etiqueta('propuesta_nombre', 'Propuestas');
			$this->ev_info->set_etiqueta('tipos_alumnos', 'Tipo Alumnos');
		}
		return $this->ev_info;
	}

	function validar_alumnos()
	{
		$datos = $this->get_seleccion();

		if ($datos['considerar_perdida_reg']) {
			if (is_null($datos['condicion'])) {
				$mensaje = guarani_mensajes::get('dato_faltante', array('Condición Regularidad'));
				throw guarani::error($mensaje);
			} else {
				if (is_null($datos['desde'])) {
					$mensaje = guarani_mensajes::get('dato_faltante', array('Desde'));
					throw guarani::error($mensaje);
				}
				if ($datos['condicion'] == 'E') {
					if (is_null($datos['hasta'])) {
						$mensaje = guarani_mensajes::get('dato_faltante', array('Hasta'));
						throw guarani::error($mensaje);
					}
					if ($datos['hasta'] < $datos['desde']) {
						$mensaje = guarani_mensajes::get('800_control_anio', array('Hasta', 'menor', 'Desde para la Condición de Regularidad'));
						throw guarani::error($mensaje);
					}
				}
			}
		}

		if ($datos['considerar_reinscriptos'] && is_null($datos['anio_reinscripcion'])) {
			$mensaje = guarani_mensajes::get('dato_faltante', array('Año Reinscripción'));
			throw guarani::error($mensaje);
		}

		$this->s__alumnos = $this->cn()->get_listado_alumnos($datos);
		
		if (guarani_arreglos::count($this->s__alumnos) == 0) {
			$mje_sin_alumnos = guarani_mensajes::get('800MAT_no_hay_alumnos');
			throw guarani::error($mje_sin_alumnos);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Pantalla Resultados ----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__resultados(toba_ei_pantalla $pantalla)
	{
		$datos = $this->get_seleccion();
		$tipo_alumnos = array();
		if ($datos['regulares']) {
			$tipo_alumnos[] = 'Regulares';
		}
		if ($datos['no_regulares']) {
			$tipo_alumnos[] = 'No Regulares';
		}
		$datos['tipo_alumnos'] = guarani_texto::armar_lista($tipo_alumnos);
		$this->ev_info()->set_datos($datos);
		
		// Se oculta el botón "< Anterior".
		$this->evento('cambiar_tab__anterior')->ocultar();
	}
	
	//---- form_totales -----------------------------------------------------------------
	
	function conf__form_totales(toba_ei_formulario $form)
	{
		$form->set_solo_lectura();
	}
	
	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cambiar_tab__siguiente()
	{
		parent::evt__cambiar_tab__siguiente();

		if ($this->s__pantalla_actual == 'alumnos') { // Si clickeó el boton 'Siguiente' desde la pantalla de alumnos...
			$datos = $this->get_seleccion();
			$parametros = array_merge(	array('alumnos' => $this->s__alumnos),
										array('motivo' => $datos['motivo_calidad']),
										array('documento' => $datos['documento']),
										array('observaciones' => $datos['observaciones']));
			// Se ejecuta el proceso background.
			$this->cn()->ejecutar_proceso($parametros);
		}
	}

	function evt__finalizar()
	{
		$this->limpiar_memoria();
		$this->set_pantalla('propuestas');
	}

	//-----------------------------------------------------------------------------------
	//---- AJAX -------------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ajax__estado_proceso($parametros, toba_ajax_respuesta $respuesta)
	{
		$contenido = $this->cn()->get_estado_proceso();
		if (!empty($contenido)) {
			$respuesta->set($contenido);
		}
	}
	
	function ajax__resultados_proceso($parametros, toba_ajax_respuesta $respuesta)
	{
		$resultados = $this->cn()->get_resultado_proceso(pro_pasar_a_calidad_pasivo::reporte_resultados);
		
		if (!empty($resultados['mensajes'])) {
			$respuesta->set(current($resultados['mensajes']));
		}
	}
	
	function ajax__reporte($params, toba_ajax_respuesta $respuesta)
	{
		$reporte		 = pro_pasar_a_calidad_pasivo::reporte_alumnos_pasados_a_pasivo;
		$operacion		 = '38000121';
		$parametro_popup = 'alumnos';
		
        if ($this->modo_visualizacion()) {
            $datos = $this->controlador()->get_resultado_reporte($reporte);
        } else {
            //Obtengo el Proceso para pedirle el log de resultados
            $datos = $this->cn()->get_resultado_proceso($reporte);
        }
		
		//Almaceno los datos en la instancia de impresion para ser consumida luego del ci del popup
		guarani_datos_impresion::instancia()->set_datos(array($parametro_popup => $datos['mensajes']));
		guarani_datos_impresion::instancia()->guardar_datos_imp();

		//Creo el vinculo para que se abra en el popup
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), $operacion, null, array('celda_memoria' => 'popup'));
		$respuesta->set($vinculo);
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$msj_confirmar = guarani_mensajes::get('800MAT_confirmar_pase_regulares_a_pasivo');

		if ($this->s__pantalla_actual == 'alumnos') {
				echo "
				//---- Eventos ---------------------------------------------
				{$this->objeto_js}.evt__cambiar_tab__siguiente = function()
				{
					var form_alumnos = this.dep('form_alumnos');
					var no_selecciono_opciones = !form_alumnos.ef('regulares').chequeado() && !form_alumnos.ef('no_regulares').chequeado() && !form_alumnos.ef('considerar_reinscriptos').chequeado();
					var selecciono_opcion_reg = form_alumnos.ef('regulares').chequeado();
		
					if (no_selecciono_opciones || selecciono_opcion_reg) {
						return confirm('$msj_confirmar');
					}
				}
			";
		}
	}
}
?>