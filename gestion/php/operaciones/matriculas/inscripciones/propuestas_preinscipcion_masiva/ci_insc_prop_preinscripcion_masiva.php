<?php
/**
 * 
 * 
 * @package 	Matrícula
 * @subpackage 	Inscripciones
 *
 */
class ci_insc_prop_preinscripcion_masiva extends ci_nav_procesos_bk_wizzard
{
	// Componente Visual de Información.
	protected $ev_info;
	protected $s__datos_ev_info;
	
	protected $s__fuente_de_datos;
	protected $s__datos_propuestas;
	protected $s__propuestas_procesar;
	protected $s__anio_academico;
	protected $s__periodo_inscripcion;
	protected $s__tipo_ingreso;

	function ini__operacion()
	{
		preinscripcion::actualizar_fuentes_datos();
	}

	function set_nombre_proceso()
	{
		$this->s__nombre_proceso = 'pro_prop_preinscripcion_masiva';
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_columnas_a_mostrar(array('fuente', 'anio_academico', 'periodo_inscripcion', 'tipo_ingreso', 'propuestas'));
			$this->ev_info->set_max_columnas_por_fila(1);

			$this->ev_info->set_etiqueta('fuente', 'Fuente de datos:');
			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico:');
			$this->ev_info->set_etiqueta('periodo_inscripcion', 'Período de Inscripción:');
			$this->ev_info->set_etiqueta('tipo_ingreso', 'Tipo de Ingreso a completar:');
			$this->ev_info->set_etiqueta('propuestas', 'Propuestas:');
			
			$this->ev_info->set_style('width: 100%');
			$this->ev_info->set_td_etiqueta_width(130);
		}
		return $this->ev_info;
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA: fuente
	//-----------------------------------------------------------------------------------

	function conf__fuente(toba_ei_pantalla $pantalla)
	{
		$this->eliminar_seleccion_previa('fuente', 'fuente_de_datos');
				
		unset($this->s__anio_academico);
		unset($this->s__periodo_inscripcion);
		unset($this->s__tipo_ingreso);
		
		// Se limpian selecciones del ev_info
		$this->s__datos_ev_info = array();
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro__seleccion($seleccion)
	{
		if ($seleccion) {
			// Se chequea que esté bien configurada la conexión a la base de preinscripción.
			$this->validar_conexion_preinscripcion($seleccion['fuente_de_datos']);

			$this->s__fuente_de_datos = $seleccion['fuente_de_datos'];
			toba::memoria()->set_dato('fuente_preinscripcion', $this->s__fuente_de_datos);
			
			// Datos para el ev_info
			$this->s__datos_ev_info['fuente'] = $seleccion['nombre'];

			// Se verifica que existan propuestas habilitadas para inscripción en la base de preinscripción.
			$this->validar_propuestas_disponibles();
		
			$this->set_seleccion($seleccion);
		} else {
			$this->eliminar_seleccion_previa();
		}
	}
	
	function validar_fuente()
	{
		if (guarani_arreglos::count($this->get_seleccion('fuente_de_datos')) == 0) {
			$etiqueta = guarani_etiquetas::get_tabla('adm_bases_preinscripcion');
			$mje = guarani_mensajes::get('800_no_seleccion_unica', array($etiqueta->get('una')));
			throw guarani::error($mje);
		}
	}

	function validar_conexion_preinscripcion($fuente_de_datos)
	{
		$instancia = toba::instancia()->get_id();
		$proyecto = toba_proyecto::get_id();

		$res = toba_dba::get_bases_definidas();
		$nombre = $instancia . ' ' . $proyecto . ' ' . $fuente_de_datos;

		if (empty($res[$nombre])) {
			unset($this->s__fuente_de_datos);
			$mje = guarani_mensajes::get('800MAT_error_conexion_preinscripcion');
			throw guarani::error($mje);
		}
	}

	function validar_propuestas_disponibles()
	{
		$this->s__datos_propuestas = $this->cn()->get_listado_propuestas();
		if (empty($this->s__datos_propuestas)) {
			$mje = guarani_mensajes::get('800MAT_preinscripcion_sin_propuestas');
			throw guarani::error($mje);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: periodos
	//-----------------------------------------------------------------------------------

	function conf__periodos(toba_ei_pantalla $pantalla)
	{
		$this->s__datos_propuestas = $this->cn()->get_listado_propuestas();
		
		unset($this->s__datos_ev_info['anio_academico']);
		unset($this->s__datos_ev_info['periodo_inscripcion']);
		unset($this->s__datos_ev_info['tipo_ingreso']);
		
		$this->ev_info()->set_datos($this->s__datos_ev_info);
	}
	
	//-----------------------------------------------------------------------------------
	//---- form_tipo_ingreso ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_tipo_ingreso(toba_ei_formulario $form)
	{
		if (isset($this->s__anio_academico)) {
			$form->set_datos(array('anio_academico' => $this->s__anio_academico));
		}
		if (isset($this->s__periodo_inscripcion)) {
			$form->set_datos(array('periodo_inscripcion' => $this->s__periodo_inscripcion));
		}
		if (isset($this->s__tipo_ingreso)) {
			$form->set_datos(array('tipo_ingreso' => $this->s__tipo_ingreso));
		}
	}

	function evt__form_tipo_ingreso__modificacion($datos)
	{
		$this->s__anio_academico = $datos['anio_academico'];
		$this->s__periodo_inscripcion = $datos['periodo_inscripcion'];
		$this->s__tipo_ingreso = $datos['tipo_ingreso'];
		
		// Datos para el ev_info: año académico
		$this->s__datos_ev_info['anio_academico'] = $datos['anio_academico'];
		// Datos para el ev_info: período de inscripción
		$datos_periodo = toba::consulta_php('co_periodos_de_inscripcion')->get_datos_periodo_inscripcion_propuesta($datos['periodo_inscripcion']);
		$this->s__datos_ev_info['periodo_inscripcion'] = $datos_periodo['periodo_nombre'];
		// Datos para el ev_info: tipo de ingreso
		if (!empty($datos['tipo_ingreso'])) {
			$this->s__datos_ev_info['tipo_ingreso'] = toba::consulta_php('co_ingresos')->get_nombre_tipo_ingreso($datos['tipo_ingreso']);
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA: propuestas
	//-----------------------------------------------------------------------------------

	function conf__propuestas(toba_ei_pantalla $pantalla)
	{
		$pantalla->eliminar_evento('cambiar_tab__siguiente');
		unset($this->s__datos_ev_info['propuestas']);
		$this->ev_info()->set_datos($this->s__datos_ev_info);
	}
	
	//-----------------------------------------------------------------------------------
	//---- form_ml_propuestas -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_ml_propuestas(toba_ei_formulario_ml $form_ml)
	{
		$form_ml->set_datos($this->s__datos_propuestas);
	}

	function evt__form_ml_propuestas__modificacion($datos)
	{
		$this->s__datos_propuestas = $datos;
		$this->validar_seleccion_propuestas();
	}
	
	function conf_fila__form_ml_propuestas($fila)
	{
		$propuesta = $this->s__datos_propuestas[$fila]['propuesta'];
		if (!toba::consulta_php('co_periodos_de_inscripcion')->propuesta_habilitada_en_periodo($propuesta, $this->s__periodo_inscripcion)) {
			$this->dep('form_ml_propuestas')->ef('procesar')->set_solo_lectura(true);
			$this->dep('form_ml_propuestas')->ef('plan_version')->set_solo_lectura(true);
		} else {
			$this->dep('form_ml_propuestas')->ef('procesar')->set_solo_lectura(false);
			$this->dep('form_ml_propuestas')->ef('plan_version')->set_solo_lectura(false);
		}
	}
	
	function validar_seleccion_propuestas()
	{
		$this->s__propuestas_procesar = array();
		
		$error_planes = array();
		$nombres_propuestas = array();
		foreach ($this->s__datos_propuestas as $propuesta) {
			if ($propuesta['procesar']) {
				if (empty($propuesta['plan_version'])) {
					$error_planes[] = $propuesta['propuesta_nombre'];
				} else {
					$nombres_propuestas[] = $propuesta['propuesta_nombre'];
					$this->s__propuestas_procesar[] = $propuesta;
				}
			}
		}
		
		// Si hubo propuestas marcadas para procesar y no se seleccionó plan, se
		// notifica el error
		if (!empty($error_planes)) {
			$mje = 'Debe seleccionar el <strong>Plan y Versión</strong> para ';
			if (guarani_arreglos::count($error_planes) == 1) {
				$mje .= 'la propuesta <strong>' . $error_planes[0] . '</strong>';
			} else {
				$mje .= 'las propuestas <strong>' . implode(', ', $error_planes) . '</strong>';
			}
			throw guarani::error($mje);
		}
		
		// Se controla que se haya seleccionado al menos una propuesta
		if (empty($this->s__propuestas_procesar)) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_propuestas');
			$mje = guarani_mensajes::get('800_no_seleccion', array($etiqueta->get('una')));
			throw guarani::error($mje);
		}
		
		$this->s__datos_ev_info['propuestas'] = implode(', ', $nombres_propuestas);
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: procesar
	//-----------------------------------------------------------------------------------
	
	function conf__procesar(toba_ei_pantalla $pantalla)
	{
		$pantalla->eliminar_evento('cambiar_tab__anterior');
		$this->ev_info()->set_datos($this->s__datos_ev_info);
	}
	
	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__procesar()
	{
		$this->procesar();
		$this->set_pantalla('procesar');
	}

	function evt__volver()
	{
		$this->set_pantalla('fuente');
	}

	function unset_vars()
	{
		unset($this->s__datos_propuestas);
		unset($this->s__anio_academico);
		unset($this->s__periodo_inscripcion);
		unset($this->s__tipo_ingreso);
		unset($this->s__fuente_de_datos);
	}

	//-----------------------------------------------------------------------------------
	//---- Procesamiento
	//-----------------------------------------------------------------------------------

	function procesar()
	{
		$datos = array();
		$datos['fuente'] = $this->s__fuente_de_datos;
		$datos['datos_propuestas'] = $this->s__propuestas_procesar;
		$datos['anio_academico'] = $this->s__anio_academico;
		$datos['periodo_inscripcion'] = $this->s__periodo_inscripcion;
		$datos['tipo_ingreso'] = $this->s__tipo_ingreso;
		
		$this->cn()->ejecutar_proceso($datos);
	}

	function ajax__estado_proceso($parametros, toba_ajax_respuesta $respuesta)
	{
		$contenido = $this->cn()->get_estado_proceso();
		if (!empty($contenido)) {
			$respuesta->set($contenido);
		}
	}

	function ajax__resultados_proceso($parametros, toba_ajax_respuesta $respuesta)
	{
		$contenido = $this->cn()->get_resultado_proceso(pro_prop_preinscripcion_masiva::reporte_resultados);
		$resultados = current($contenido['mensajes']);
		if (!empty($resultados)) {
			$respuesta->set($resultados);
		}
	}

	function ajax__reporte($params, toba_ajax_respuesta $respuesta)
	{
		switch ($params['evento']) {
			case 'rep_errores':
				$reporte = pro_prop_preinscripcion_masiva::reporte_errores;
				$operacion = '41000120';
				$titulo = 'Inscripciones Procesadas con Error';
				break;
		}
		// Se obtiene el Proceso para pedirle el log de resultados
		if ($this->modo_visualizacion()) {
			$datos = $this->controlador()->get_resultado_reporte($reporte);
			$ev_info_datos = array();
		} else {
			$datos = $this->cn()->get_resultado_proceso($reporte);
			$ev_info_datos = $this->get_info_reporte();
		}

		// Se almacenan los datos en la instancia de impresión para ser consumidos luego en el ci del popup
		guarani_datos_impresion::instancia()->set_datos(array(
			'datos' => $datos['mensajes'],
			'ev_info_datos' => $ev_info_datos,
			'reporte' => $reporte,
			'titulo' => $titulo
		));

		guarani_datos_impresion::instancia()->guardar_datos_imp();

		// Se crea el vínculo para que se abra en el popup
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), $operacion, null, array('celda_memoria' => 'popup'));
		$respuesta->set($vinculo);
	}

	function get_info_reporte()
	{
		$datos = array();

		$datos['fuente_de_datos'] = toba::consulta_php('co_preinscripciones')->get_nombre_fuente_datos($this->s__fuente_de_datos);

		$datos_periodo_inscripcion = toba::consulta_php('co_periodos_de_inscripcion')->get_datos_periodo_inscripcion($this->s__periodo_inscripcion);
		$datos['periodo'] = $datos_periodo_inscripcion['nombre'];

		$datos['propuestas'] = implode(', ', aplanar_matriz($this->s__datos_propuestas, 'propuesta_nombre'));

		if (!empty($this->s__tipo_ingreso)) {
			$tipo_ingreso = toba::db()->quote($this->s__tipo_ingreso);
			$where = "sga_tipos_ingreso.tipo_ingreso = $tipo_ingreso";
			$datos_tipo_ingreso = current(toba::consulta_php('co_ingresos')->get_listado($where));
			$datos['tipo_ingreso'] = $datos_tipo_ingreso['nombre'];
		}

		return $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_anios_academicos_mayores_o_iguales_calendario()
	{
		return toba::consulta_php('co_anios_academicos')->get_anios_academicos_mayores_o_iguales(guarani_fecha::get_hoy_parte('anio')-1);
	}
	
	function get_planes_para_inscripcion($propuesta) 
	{
		$params_sistema = array(
			'inscripcion_propuesta_plan' => guarani::parametros()->get_valor('inscripcion_propuesta_plan', $propuesta),
			'inscripcion_propuesta_version' => guarani::parametros()->get_valor('inscripcion_propuesta_version', $propuesta)
		);
		return toba::consulta_php('co_propuestas')->get_planes_para_inscripcion($propuesta, false, '', $params_sistema);
	}

}
?>