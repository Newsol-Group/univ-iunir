<?php
/**
 *
 *
 * @package     Matrícula
 * @subpackage  Regularidad
 * 
 */
class ci_nav_verificar_regularidad_alumno extends ci_nav_procesos_bk_wizzard
{
	//Componente Visual de Información.
	protected $ev_info;

	/*
	 * Se maneja el avance de pantalla.
	 */
	function evt__cambiar_tab__siguiente()
	{
		parent::evt__cambiar_tab__siguiente();

		if ($this->s__pantalla_actual == 'pant_ubicaciones') {
			// Parámetros
			$propuestas = $this->get_seleccion_valor('propuesta');
			$ubicaciones = $this->get_seleccion_valor('ubicacion');
			$anio_academico = $this->get_seleccion_valor('anio_academico');
			$fecha_control_desde = $this->get_seleccion_valor('fecha_control_desde');
			$fecha_control_hasta = $this->get_seleccion_valor('fecha_control_hasta');
			$alumnos_a_verificar = $this->get_seleccion_valor('alumnos_a_verificar');
			$fecha_venc_readmision = $this->get_seleccion_valor('fecha_venc_readmision');
			$nro_resolucion = $this->get_seleccion_valor('nro_resolucion');

			// Parámetros del sistema
			$mat_regularidad_alumno = guarani::parametros()->get_valor('mat_regularidad_alumno', $propuestas);
			
			// Se obtienen los Alumnos a los que se les verificará la regularidad
			$filtro_alumnos_a_verificar = '(false';
			if (in_array(vencimiento_regularidad::alumnos_regulares_sin_readmision, $alumnos_a_verificar)) {
				$filtro_alumnos_a_verificar .= " OR (	sga_alumnos.regular = '".comunes::si."' AND
														NOT EXISTS (SELECT	sga_readmisiones.readmision
																	FROM	sga_perdida_regularidad
																			JOIN sga_readmisiones ON sga_perdida_regularidad.perdida_regularidad = sga_readmisiones.perdida_regularidad
																	WHERE	sga_perdida_regularidad.alumno = sga_alumnos.alumno AND
																			sga_readmisiones.anio_academico = $anio_academico))";
			}
			if (in_array(vencimiento_regularidad::alumnos_regulares_con_readmision_vencida, $alumnos_a_verificar)) {
				$filtro_alumnos_a_verificar .= " OR (	sga_alumnos.regular = '".comunes::si."' AND
														CURRENT_DATE > (SELECT		COALESCE(sga_readmisiones.fecha_vencimiento, CURRENT_DATE)
																		FROM		sga_perdida_regularidad
																					JOIN sga_readmisiones ON sga_perdida_regularidad.perdida_regularidad = sga_readmisiones.perdida_regularidad
																		WHERE		sga_perdida_regularidad.alumno = sga_alumnos.alumno AND
																					sga_readmisiones.anio_academico = $anio_academico
																		ORDER BY	sga_readmisiones.fecha_vencimiento
																		LIMIT		1))";
			}
			if (in_array(vencimiento_regularidad::alumnos_no_regulares, $alumnos_a_verificar)) {
				$filtro_alumnos_a_verificar .= " OR sga_alumnos.regular = '".comunes::no."'";
			}
			$filtro_alumnos_a_verificar .= ')';
			$alumnos = toba::consulta_php('co_alumnos')->get_alumnos_planes_versiones_ubicaciones($propuestas, $ubicaciones, $anio_academico, $mat_regularidad_alumno, true, $filtro_alumnos_a_verificar);

			// Se ejecuta el proceso en background
			$this->cn()->ejecutar_proceso(array_merge(array('alumnos' => $alumnos), array('anio_academico' => $anio_academico), array('fecha_control_desde' => $fecha_control_desde), array('fecha_control_hasta' => $fecha_control_hasta), array('nro_resolucion' => $nro_resolucion), array('fecha_venc_readmision' => $fecha_venc_readmision)));
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PROCESO ----------------------------------------------------------------------
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
		// Se retorna el array de resultados con las cantidades totales como respuesta
		$resultados = $this->cn()->get_resultado_proceso(pro_verificar_regularidad_alumno::reporte_cantidades);
		if (!empty($resultados['mensajes'])) {
			$respuesta->set(current($resultados['mensajes']));
		}
	}

	function ajax__reporte($params, toba_ajax_respuesta $respuesta)
	{
		if ($params['evento'] == 'rep_perdieron_regularidad') {
			$reporte = pro_verificar_regularidad_alumno::reporte_perdieron_regularidad;
			guarani_datos_impresion::instancia()->set_titulo('Alumnos que han perdido la regularidad');
			$ocultar_columnas = false;
		} else {
			$reporte = pro_verificar_regularidad_alumno::reporte_recuperaron_regularidad;
			guarani_datos_impresion::instancia()->set_titulo('Alumnos que han recuperado la regularidad');
			$ocultar_columnas = true;
		}

		// Se obtiene el Proceso para pedirle el log de resultados
		if ($this->modo_visualizacion()) {
			$datos = $this->controlador()->get_resultado_reporte($reporte);
		} else {
			$datos = $this->cn()->get_resultado_proceso($reporte);
		}

		// Se almacenan los datos en la instancia de impresión para ser consumida luego del ci del popup
		guarani_datos_impresion::instancia()->set_datos(array('alumnos' => $datos['mensajes'], 'ocultar_columnas' => $ocultar_columnas));
		guarani_datos_impresion::instancia()->guardar_datos_imp();

		// Se crea el vínculo para que se abra en el popup
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '38000138', null, array('celda_memoria' => 'popup'));
		$respuesta->set($vinculo);
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA PROPUESTAS 
	//-----------------------------------------------------------------------------------
	
	function conf__pant_propuestas(toba_ei_pantalla $pantalla)
	{
		$this->cn()->resetear_atributos_parametros();
	}
	
	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
		$datos_defecto = array('alumnos_a_verificar' => array(vencimiento_regularidad::alumnos_regulares_sin_readmision, vencimiento_regularidad::alumnos_regulares_con_readmision_vencida, vencimiento_regularidad::alumnos_no_regulares));
		$form->set_datos_defecto($datos_defecto);

		$datos = array();
		$datos['anio_academico'] = $this->get_seleccion_valor('anio_academico');
		$datos['fecha_control_desde'] = $this->get_seleccion_valor('fecha_control_desde');
		$datos['fecha_control_hasta'] = $this->get_seleccion_valor('fecha_control_hasta');
		$datos['nro_resolucion'] = $this->get_seleccion_valor('nro_resolucion');
		$datos['alumnos_a_verificar'] = $this->get_seleccion_valor('alumnos_a_verificar');
		$datos['fecha_venc_readmision'] = $this->get_seleccion_valor('fecha_venc_readmision');
		$form->set_datos($datos);
	}

	function evt__formulario__modificacion($datos)
	{
		$datos['nro_resolucion_descr'] = toba::consulta_php('co_documentos')->get_documento($datos['nro_resolucion']);
		$this->set_seleccion($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro__seleccion($seleccion)
	{
		$this->set_seleccion($seleccion);
		$propuestas = aplanar_matriz($seleccion, 'propuesta');
		$this->cn()->cargar_atributos_parametros($propuestas);
	}

	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_fila_seleccionada('cuadro', $fila));
	}
	
	//-----------------------------------------------------------------------------------
	//---- Validación de salida ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function validar_pant_propuestas()
	{
		// Se valida que se haya seleccionado algún Plan
		if ($this->falta_seleccionar('propuesta')) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_propuestas');
			$mje = guarani_mensajes::get('800_no_seleccion', array($etiqueta->get('una')));
			throw guarani::error($mje);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA UBICACIONES 
	//-----------------------------------------------------------------------------------

	function conf__pant_ubicaciones(toba_ei_pantalla $pantalla)
	{
		$datos = $this->get_seleccion();
		$datos['fecha_control_desde'] = guarani_fecha::convertir_fecha_formato_frontend($datos['fecha_control_desde']);
		$datos['fecha_control_hasta'] = guarani_fecha::convertir_fecha_formato_frontend($datos['fecha_control_hasta']);
		$datos['alumnos_a_verificar_nombres'] = toba::consulta_php('co_vencimientos_regularidad')->get_alumnos_a_verificar_nombres($datos['alumnos_a_verificar']);
		$this->ev_info()->set_datos($datos);
		$this->evento('cambiar_tab__siguiente')->set_etiqueta('Procesar');
		$this->evento('cambiar_tab__siguiente')->set_imagen('/generar.png');
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_ubicaciones -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_ubicaciones(toba_ei_cuadro $cuadro)
	{
		$filtro = guarani_sql::armar_condicion_in($this->get_seleccion_valor('propuesta'), 'sga_propuestas_oferta', 'propuesta');
		$propuestas_oferta = toba::consulta_php('co_propuestas')->get_ubicaciones_propuestas($filtro);
		$ubicaciones = array();
		$ubicacion_anterior = 0;
		foreach ($propuestas_oferta as $propuesta_oferta) {
			if ($propuesta_oferta['ubicacion'] != $ubicacion_anterior) {
				if ($ubicacion_anterior != 0) {
					$ubicaciones[$ubicacion_anterior]['tiene_reglas'] = empty($ubicaciones[$ubicacion_anterior]['propuestas_sin_reglas']) ? true : false;
					$ubicaciones[$ubicacion_anterior]['info_reglas'] = $this->get_html_icono_info_reglas($ubicacion_anterior, $ubicaciones[$ubicacion_anterior]['propuestas'], $ubicaciones[$ubicacion_anterior]['propuestas_sin_reglas']);
				}
				$ubicaciones[$propuesta_oferta['ubicacion']] = array('ubicacion' => $propuesta_oferta['ubicacion'], 'ubicacion_nombre' => $propuesta_oferta['ubicacion_nombre'], 'propuestas' => array(), 'propuestas_sin_reglas' => array());
				$ubicacion_anterior = $propuesta_oferta['ubicacion'];
			}
			$ubicaciones[$propuesta_oferta['ubicacion']]['propuestas'][] = $propuesta_oferta['propuesta'];
			$cant_reglas = toba::consulta_php('co_readmisiones')->get_cant_reglas_calculo_regularidad_propuesta_ubicacion($propuesta_oferta['propuesta'], $propuesta_oferta['ubicacion']);
			if ($cant_reglas == 0) {
				$ubicaciones[$propuesta_oferta['ubicacion']]['propuestas_sin_reglas'][] = $propuesta_oferta['propuesta'];
			}
		}
		if ($ubicacion_anterior != 0) {
			$ubicaciones[$ubicacion_anterior]['tiene_reglas'] = empty($ubicaciones[$ubicacion_anterior]['propuestas_sin_reglas']) ? true : false;
			$ubicaciones[$ubicacion_anterior]['info_reglas'] = $this->get_html_icono_info_reglas($ubicacion_anterior, $ubicaciones[$ubicacion_anterior]['propuestas'], $ubicaciones[$ubicacion_anterior]['propuestas_sin_reglas']);
		}
		$cuadro->set_datos($ubicaciones);
	}

	function evt__cuadro_ubicaciones__seleccion($seleccion)
	{
		$this->set_seleccion($seleccion);	
		if (empty($seleccion)) {
			$this->eliminar_seleccion_previa();
		}
	}
	
	function conf_evt__cuadro_ubicaciones__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_fila_seleccionada('cuadro_ubicaciones', $fila));
	}
	
	/**
	 * Retorna el HTML de un vínculo (con imagen) a la operación que muestra el 
	 * detalle reglas utilizadas por propuesta para la ubicacion en cuestion.
	 */
	function get_html_icono_info_reglas($ubicacion, $propuestas, $propuestas_sin_reglas)
	{
		if (empty($propuestas_sin_reglas)) {
			$propuestas_sin_reglas = '';
			$mensaje = '';
		} else {
			$propuestas_sin_reglas = implode(',', $propuestas_sin_reglas);
			$mensaje = 'Hay propuestas sin reglas definidas para la ubicación';
		}
		$vinculo = toba::vinculador()->get_url(toba_proyecto::get_id(), '45000010', array('ubicacion' => $ubicacion, 'propuestas' => implode(',', $propuestas), 'propuestas_sin_reglas' => $propuestas_sin_reglas), array('celda_memoria'=>'ubicaciones'));
		$javascript = "abrir_popup('info','$vinculo',{'width':'650','height':'420'})";
		$imagen = empty($propuestas_sin_reglas) ? 'info_chico.gif' : 'warning.gif';
		$salida = "<a class='icono-utileria' href='#' onclick=\"$javascript\">";
		$salida .= toba_recurso::imagen_toba($imagen, true, null, null, $mensaje);
		$salida .= '</a>';
		return $salida;
	}

	//-----------------------------------------------------------------------------------
	//---- Validación de salida ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function validar_pant_ubicaciones()
	{	
		// Se valida que existan Ubicaciones seleccionadas
		if ($this->falta_seleccionar('ubicacion')) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_ubicaciones');
			$mje = guarani_mensajes::get('800_no_seleccion', array($etiqueta->get('una')));
			throw guarani::error($mje);
		} else {
			$ubicaciones = $this->get_seleccion_valor('tiene_reglas');
                        if (!is_null($ubicaciones)) {
                            foreach ($ubicaciones as $ubicacion) {
                                    if (!$ubicacion) {
                                            throw guarani::error(guarani_mensajes::get('800MAT_propuestas_sin_regla'));
                                    }
                            }
                        }
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA FINALIZAR 
	//-----------------------------------------------------------------------------------

	function conf__pant_resultados(toba_ei_pantalla $pantalla)
	{
		$datos = $this->get_seleccion();
		$datos['fecha_control_desde'] = guarani_fecha::convertir_fecha_formato_frontend($datos['fecha_control_desde']);
		$datos['fecha_control_hasta'] = guarani_fecha::convertir_fecha_formato_frontend($datos['fecha_control_hasta']);
		$datos['alumnos_a_verificar_nombres'] = toba::consulta_php('co_vencimientos_regularidad')->get_alumnos_a_verificar_nombres($datos['alumnos_a_verificar']);
		$this->ev_info()->set_datos($datos);
		$this->evento('cambiar_tab__anterior')->ocultar();
	}

	//-----------------------------------------------------------------------------------
	//---- form_totales -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_totales(toba_ei_formulario $form)
	{
		$form->set_solo_lectura();
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__volver()
	{
		$this->limpiar_seleccion();
		$this->set_pantalla('pant_propuestas');
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_td_etiqueta_width('100px');
			$this->ev_info->set_max_datos_por_columna(10);
			$this->ev_info->set_max_chars_por_columna(100);
			
			$this->ev_info->set_columnas_a_mostrar(array('anio_academico', 'nro_resolucion_descr', 'fecha_control_desde', 'fecha_control_hasta', 'alumnos_a_verificar_nombres', 'nombre'));
			$this->ev_info->set_columnas_fila(array('anio_academico', 'fecha_control_desde', 'fecha_control_hasta'), 1);
			$this->ev_info->set_columnas_fila(array('nro_resolucion_descr', 'alumnos_a_verificar_nombres'), 2);
			$this->ev_info->set_columnas_fila(array('nombre'), 3);
			$this->ev_info->set_max_columnas_por_fila(3);
			
			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('fecha_control_desde', 'Desde');
			$this->ev_info->set_etiqueta('fecha_control_hasta', 'Hasta');
			$this->ev_info->set_etiqueta('nro_resolucion_descr', 'Nº Resolución');
			$this->ev_info->set_etiqueta('alumnos_a_verificar_nombres', 'Alumnos a Verificar');
			$this->ev_info->set_etiqueta('nombre', 'Propuestas');
		}
		return $this->ev_info;
	}
}