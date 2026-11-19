<?php
/**
 *
 *
 * @package     Reconocimiento de Actividades
 * @subpackage  Equivalencias
 */
class ci_nav_cargar_equiv_masiva extends ci_nav_procesos_bk_wizzard
{
	//Componente Visual de Información.
	protected $ev_info;
	protected $s__seleccion_form;
	protected $s__datos_ev;
	protected $s__equivs;
	protected $s__alumnos = array();
	protected $datos_activs = array();

	function conf() {
		parent::conf();
		$datos = $this->get_seleccion();
		$datos = (is_null($datos)) ? array() : $datos;
		if (isset($datos['actividades'])) unset($datos['actividades']);
		$this->s__datos_ev = $datos;
		$this->ev_info()->set_datos($this->s__datos_ev);
	}
	
	/*
	 * Se maneja el avance de pantalla.
	 */
	function evt__cambiar_tab__siguiente()
	{
		parent::evt__cambiar_tab__siguiente();
		if ($this->s__pantalla_actual == 'pant_equivalencias') {
			//Parámetros
			$datos_equivs = $this->dependencia('editor_notas')->get_datos();
			if (!empty($datos_equivs)) {
				//Validar datos de quivalencias registradas
				$valido = true;
				foreach ($datos_equivs as $equiv) {
					$valido = $valido && (!is_null($equiv['fecha'])) && (!is_null($equiv['resultado']));
				}
				if (!$valido) {
					$mje = guarani_mensajes::get('800_sin_dato', array('completado', 'todos los campos Fecha y Resultado'));
					throw guarani::error($mje);
				}

				$datos = $this->get_seleccion();
				$datos['equivs'] = $datos_equivs;
				$datos['operacion'] = guarani::get_id_operacion();
				$datos['usuario'] = toba::usuario()->get_id();
				//Ejecuto el proceso en background
				//ei_arbol($datos);die;
				$this->cn()->ejecutar_proceso($datos);
			} else {
				$mje = guarani_mensajes::get('800_sin_dato', array('completado', 'datos a procesar'));
				throw guarani::error($mje);				
			}
		}
	}

	function evt__cambiar_tab__anterior()
	{
		parent::evt__cambiar_tab__anterior();
		if ($this->s__pantalla_actual == 'pant_equivalencias') {
			$this->dependencia('editor_notas')->limpiar_datos();
		}
	}
	
	function evt__pant_equivalencias__entrada()
	{
		// Genero los datos para la asignacion de equivalencias
		$datos_act = $this->get_seleccion();
		$i = 0;
		$this->s__equivs = array();
		foreach ($this->s__alumnos as $alu) {
			foreach ($datos_act['actividades'] as $elemento) {
				 $this->s__equivs[$i]['codigo'] = $alu['alumno'] . '_' . $elemento['elemento'];
				 $this->s__equivs[$i]['elemento'] = $elemento['elemento'];
				 $this->s__equivs[$i]['identificacion'] = $alu['documento_principal'];
				 $this->s__equivs[$i]['apellido_y_nombre'] = $alu['apellido_y_nombre'];
				 $this->s__equivs[$i]['actividad'] = $elemento['actividad_descr'];
				 $this->s__equivs[$i]['escala_nota'] = $elemento['escala_nota'];
				 $i++;
			}
		}
	}
	
	function get_alumnos_asignados()
	{ 
		return $this->s__alumnos;
	}

	function set_alumnos_asignados($sel)
	{ 
		$this->s__alumnos = array_merge($this->s__alumnos, $sel);
	}

	function eliminar_alumno($sel)
	{ 
		$datos = array();
		foreach ($this->s__alumnos as $alu) {
			if ($alu['alumno'] != $sel['alumno']) 
			{
				$datos[] = $alu;
			}
		}
		$this->s__alumnos = $datos;
	}
	
	function get_equivalencias()
	{
		return $this->s__equivs;		
	}

	function get_plan()
	{
		$plan = $this->get_seleccion('plan_version');
		return $plan['plan_version'];
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
		//Se retorna el array de resultados con las cantidades totales como respuesta
		$resultados = $this->cn()->get_resultado_proceso(pro_cargar_equiv_masiva::reporte_cantidades);
		if (!empty($resultados['mensajes'])) {
			$respuesta->set(current($resultados['mensajes']));
		}
	}
	
	function ajax__reporte($params, toba_ajax_respuesta $respuesta)
	{
		if ($params['evento'] == 'rep_cant_equiv_otorgada') {
			$reporte = pro_cargar_equiv_masiva::reporte_equiv_otorgadas_x_act;
			$titulo = 'Reporte de Equivalencias Otorgadas por Actividad';
			$operacion = '34000041';
		} elseif ($params['evento'] == 'rep_cant_alumnos_equiv_otorgada') {
			$reporte = pro_cargar_equiv_masiva::reporte_equiv_otorgadas_x_alu;
			$titulo = 'Reporte de Equivalencias Otorgadas por Alumno';
			$operacion = '34000042';
		} else {
			$reporte = pro_cargar_equiv_masiva::reporte_errores;
			$titulo = 'Reporte de Errores';
			$operacion = '34000043';
		}
		
		//Obtengo el Proceso para pedirle el log de resultados
		$datos = $this->cn()->get_resultado_proceso($reporte);
		
		//Almaceno los datos en la instancia de impresion para ser consumida luego por los reportes
		guarani_datos_impresion::instancia()->set_titulo($titulo);
		guarani_datos_impresion::instancia()->set_conf($this->s__datos_ev);
		guarani_datos_impresion::instancia()->set_datos($datos['mensajes']);
		guarani_datos_impresion::instancia()->guardar_datos_imp();

		//Creo el vinculo para que se abra en el popup
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), $operacion, null, array('celda_memoria' => 'popup'));
		$respuesta->set($vinculo);	
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA PROPUESTAS ----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//---- formulario -------------------------------------------------------------------
	
	function conf__formulario(toba_ei_formulario $form)
	{
		$datos = array();
		if (isset($this->s__seleccion_form)) {
			$datos = $this->s__seleccion_form;
		} else {
			$datos['fecha'] = guarani_fecha::get_hoy();			
		}
		$form->set_datos($datos);
	}
	
	function evt__formulario__modificacion($datos)
	{
		$this->s__seleccion_form = $datos;
		$this->set_seleccion($datos);
	}

	//---- cuadro -----------------------------------------------------------------------
	
	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		$evento->set_check_activo($this->es_fila_seleccionada('cuadro', $fila));
	}

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$filtro = $this->get_filtro_condicion();
		$etiquetas = guarani_etiquetas::get_tabla('sga_propuestas');
		$etiqueta = $etiquetas->get_enf('plural');
		
		if (!isset($filtro)) {
			$indx_msj = $this->indx_msg_eof . '_sinfiltro';
		} else {
			$indx_msj = $this->indx_msg_eof . '_filtrado';
			$filtro_vigentes = ' AND ' . "	sga_planes.estado IN ('". planes::estado_activo . "', '" . planes::estado_activo_vigente . "') AND
											sga_planes_versiones.estado IN ('". planes::estado_activo . "', '" . planes::estado_activo_vigente . "')";
			$ras = $this->get_seleccion('responsable_academica');
			$filtro_ras = ' sga_responsables_academicas.responsable_academica = ' . $ras['responsable_academica'];
			$filtro = empty($filtro) ? $filtro_ras . $filtro_vigentes : $filtro . ' AND ' . $filtro_ras . $filtro_vigentes;
			$datos = toba::consulta_php('co_planes')->get_datos_plan_version(null, $filtro);
			
			$cuadro->set_datos($datos);
		}
		$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj, array($etiqueta)));
	}

	function evt__cuadro__seleccion($seleccion)
	{
		if ($seleccion) {
			$this->set_seleccion($seleccion);
		} else {
			$this->eliminar_seleccion_previa();
		}
	}

	function validar_pant_propuestas()
	{
		if ($this->falta_seleccionar('plan_version')) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('un plan'));
			throw guarani::error($mje);
		}
	}
	
	function validar_pant_alumnos()
	{
		if (empty($this->s__alumnos)) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('un alumno'));
			throw guarani::error($mje);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA ACTIVIDADES ---------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//---- form_ml_actividades -----------------------------------------------------------
	
	function conf__form_ml_actividades(toba_ei_formulario_ml $form_ml)
	{
		$id_plan_version = $this->get_seleccion('plan_version');
		$this->datos_activs = toba::consulta_php('co_elementos')->get_actividades_plan_version($id_plan_version['plan_version'],'','actividad_descr');
		$form_ml->set_datos($this->datos_activs);
	}
	
	function evt__form_ml_actividades__modificacion($seleccion)
	{
		$datos = array();
		foreach ($seleccion as $sel) {
			if (($sel['apex_ei_analisis_fila'] == 'M') && ($sel['requerido'])) {
				$dato = array();
				$dato['elemento'] = $sel['elemento'];
				$dato['actividad_descr'] = $sel['actividad_descr'];
				$dato['escala_nota'] = $sel['escala_nota'];
				$datos[] = $dato;
			}
		}
		if (!empty($datos)) {
			$this->set_seleccion(array('actividades' => $datos));
		}
	}
	
	//---- Validación de salida ---------------------------------------------------------
	
	function validar_pant_actividades()
	{ 
		//Valido que existan Ubicaciones seleccionadas
		if ($this->falta_seleccionar('actividades')) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('una actividad'));
			throw guarani::error($mje);
		}
	}

	function conf__pant_actividades(toba_ei_pantalla $pantalla)
	{
		$this->eliminar_seleccion_previa($this->s__pantalla_actual,'actividades');
		$this->dep('form_ml_actividades')->set_columna_ordenamiento('actividad_descr');
	}
		
	function conf__pant_equivalencias(toba_ei_pantalla $pantalla)
	{
		$this->evento('cambiar_tab__siguiente')->set_etiqueta('Procesar');
		$this->evento('cambiar_tab__siguiente')->set_imagen('/generar.png');
		$mje = guarani_mensajes::get('800EQU_confirmar_proc_equiv');
		$this->evento('cambiar_tab__siguiente')->set_msg_confirmacion($mje);
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA FINALIZAR -----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__pant_resultados(toba_ei_pantalla $pantalla)
	{
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

	function evt__volver()
	{
		$this->limpiar_seleccion();
		$this->evt__filtro__cancelar();
		$this->s__alumnos = array();
		$this->s__equivs = array();
		$this->dependencia('editor_notas')->limpiar_datos();
		$this->set_pantalla('pant_propuestas');
	}
	
	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__pant_alumnos()
	{
		$datos = $this->get_seleccion();
		$temp = new toba_formateo('html');
		$this->set_seleccion(array('fecha_visual' => $temp->formato_fecha($datos['fecha'])));
		$temp = current(toba::consulta_php('co_instituciones')->get_institucion_nombre($datos['institucion']));
		$this->set_seleccion(array('institucion_nombre' => $temp['nombre_abreviado']));
		$temp = current(toba::consulta_php('co_responsables_academicas')->get_datos_responsable_academica($datos['responsable_academica']));
		$this->set_seleccion(array('resp_acad_nombre' => $temp['resp_acad_nombre']));		
		$this->ev_info()->set_datos($this->get_seleccion());
	}
	
	function ev_info()		
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_style('width: 70%;');
			$this->ev_info->set_columnas_a_mostrar(array('fecha_visual', 'institucion_nombre', 'propuesta_nombre', 'resp_acad_nombre'));
			$this->ev_info->set_columnas_fila(array('fecha_visual', 'institucion_nombre'), 1);
			$this->ev_info->set_columnas_fila(array('propuesta_nombre', 'resp_acad_nombre'), 2);
			$this->ev_info->set_etiqueta('fecha_visual', 'Fecha de Otorgamiento');
			$this->ev_info->set_etiqueta('institucion_nombre', 'Institución Externa');
			$this->ev_info->set_etiqueta('propuesta_nombre', 'Propuesta');
			$this->ev_info->set_etiqueta('resp_acad_nombre', 'Responsable Académica');
		}
		
		return $this->ev_info;
	}
	
	function get_js_escalas_defecto() 
	{
		$escalas = "var escalas_defecto = new Array();";
		$i=0;
		foreach($this->datos_activs as $dato) {
			$filtro = 'sga_elementos.elemento = ' . $dato['elemento'];
			$dato_escala = current(toba::consulta_php('co_elementos')->get_escala_nota_equivalencia_elementos_plan_version($this->get_plan(), $filtro));
			$escalas .= "escalas_defecto[$i] = ";
			$escalas .= (!is_null($dato_escala['escala_nota'])) ? $dato_escala['escala_nota'] : '0';
			$escalas .= ";\n";
			$i++;
		}
		
		return $escalas;
	}	
}
?>