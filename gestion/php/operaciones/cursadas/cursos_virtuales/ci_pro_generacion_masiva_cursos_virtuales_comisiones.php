<?php
class ci_pro_generacion_masiva_cursos_virtuales_comisiones extends ci_nav_procesos_bk_sin_listado_inicial
{
	protected $s__datos_formulario;

	protected $s__datos_plataforma;
	protected $s__MoodleCategoriasCursos;

	protected $s__datos_ev_info;
	protected $ev_info_filtro;

	//-----------------------------------------------------------------------------------
	//---- Configuración ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__seleccion(guarani_ei_pantalla $pantalla)
	{
		if (!isset($this->s__filtro)) {
			$pantalla->eliminar_dep('formulario');
		}
	}

	function conf__procesamiento(\toba_ei_pantalla $pantalla)
	{
		parent::conf__procesar($pantalla);
		$this->ev_info_filtro()->set_datos($this->get_datos_ev_info_filtro());
	}

	//-----------------------------------------------------------------------------------
	//---- Procesamiento ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	public function set_nombre_proceso()
	{
		$this->s__nombre_proceso = 'pro_proc_generar_cursos_virtuales_comisiones_masivamente';
	}

	function procesar()
	{
		$parametros = array('datos' => $this->s__seleccion, 'datos_formulario' => $this->s__datos_formulario, 'datos_plataforma' => $this->s__datos_plataforma, 'operacion' => guarani::get_id_operacion());
		$this->cn()->ejecutar_proceso($parametros);
		$this->set_pantalla('procesamiento');
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
		$contenido = $this->cn()->get_resultado_proceso(pro_proc_generar_cursos_virtuales_comisiones_masivamente::reporte_resultados);
		$resultados = current($contenido['mensajes']);
		if (!empty($resultados)) {
			$respuesta->set($resultados);
		}
	}

	function ajax__reporte($params, toba_ajax_respuesta $respuesta)
	{
		switch ($params['evento']) {
			case 'rep_docentes':
				$reporte = pro_proc_generar_cursos_virtuales_comisiones_masivamente::rep_docentes;
				$operacion = '22000005';
				$titulo = 'Reporte docentes';
				$parametro_popup = 'rep_docentes';
				break;
			case 'rep_alumnos':
				$reporte = pro_proc_generar_cursos_virtuales_comisiones_masivamente::rep_alumnos;
				$operacion = '22000005';
				$titulo = 'Reporte alumnos';
				$parametro_popup = 'rep_alumnos';
				break;
			case 'rep_cursos':
				$reporte = pro_proc_generar_cursos_virtuales_comisiones_masivamente::rep_curso;
				$operacion = '22000005';
				$titulo = 'Reporte cursos';
				$parametro_popup = 'rep_curso';
				break;
		}

		// Se obtiene el Proceso para pedirle el log de resultados
		if ($this->modo_visualizacion()) {
			$datos = $this->controlador()->get_resultado_reporte($reporte);
		} else {
			$datos = $this->cn()->get_resultado_proceso($reporte);
		}

		// Se almacenan los datos en la instancia de impresión para ser consumida luego del ci del popup
		guarani_datos_impresion::instancia()->set_datos($datos['mensajes']);
		guarani_datos_impresion::instancia()->set_titulo($titulo);
		guarani_datos_impresion::instancia()->guardar_datos_imp();

		// Se crea el vinculo para que se abra en el popup
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), $operacion, null, array('celda_memoria' => 'popup'));
		$respuesta->set($vinculo);
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$filtro_responsable_academica = '';
			if (isset($datos['responsable_academica'])) {
				$filtro_responsable_academica .= " AND EXISTS (SELECT * FROM sga_elementos_ra WHERE sga_elementos_ra.elemento = vw_comisiones.elemento AND sga_elementos_ra.responsable_academica = '{$datos['responsable_academica']['valor']}')";
				$this->dep('filtro')->columna('responsable_academica')->resetear_estado();
			}
 			$filtro_propuesta = '';
			if (isset($datos['propuesta'])) {
				$filtro_propuesta .= " AND EXISTS (SELECT * FROM sga_comisiones_propuestas WHERE sga_comisiones_propuestas.comision = vw_comisiones.comision AND sga_comisiones_propuestas.propuesta = '{$datos['propuesta']['valor']}')";
				$this->dep('filtro')->columna('propuesta')->resetear_estado();
			}
			$filtro_modalidad = '';
			if (isset($datos['modalidad'])) {
				$filtro_modalidad .= " AND EXISTS (SELECT * FROM sga_comisiones_modalidad WHERE sga_comisiones_modalidad.comision = vw_comisiones.comision AND sga_comisiones_modalidad.modalidad = '{$datos['modalidad']['valor']}')";
				$this->dep('filtro')->columna('modalidad')->resetear_estado();
			}
			$filtro_recupera_comision = '';
			if (isset($datos['recupera_comision'])) {
				if ($datos['recupera_comision']['valor'] == comunes::no){
					$filtro_recupera_comision .= " AND NOT EXISTS (SELECT 1 FROM int_pv_cursos_comisiones  WHERE int_pv_cursos_comisiones.comision = vw_comisiones.comision) ";
				}
				$this->dep('filtro')->columna('recupera_comision')->resetear_estado();
			}

			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro_responsable_academica $filtro_propuesta $filtro_modalidad $filtro_recupera_comision";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

	function conf__filtro(toba_ei_filtro $filtro)
	{
		$ras_disponibles = toba::consulta_php('co_configuracion')->get_ras_disponibles_en_plataformas();
		if (empty($ras_disponibles)){
			$mje = 'No se encontraron plataformas de educación virtual activas';
			throw guarani::error($mje);
		}
		$filtro->columna('responsable_academica')->set_condicion_fija('es_igual_a');
		$filtro->columna('propuesta')->set_condicion_fija('es_igual_a');
		$filtro->columna('recupera_comision')->set_condicion_fija('es_igual_a');
		if (isset($this->s__datos)) {
			$filtro->set_datos($this->s__datos);
		}		
	}

	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__formulario__modificacion($datos)
	{
		if (!empty($datos)) {
			$this->s__datos_formulario = $datos;
		}
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro__seleccion($datos)
	{
		$this->s__seleccion = $datos;
	}

	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro')->get_datos();
		if ($datos[$fila]['curso_creado'] == comunes::si_desc && $datos[$fila]['alumnos_sincronizados'] == comunes::si_desc && $datos[$fila]['docentes_sincronizados'] == comunes::si_desc) {
			$evento->anular();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__procesar()
	{
		if (guarani_arreglos::count($this->s__seleccion) == 0) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_comisiones');
			$mje = guarani_mensajes::get('800_no_seleccion', array($etiqueta->get('una')));
			throw guarani::error($mje);
		}
		$this->procesar();
		$this->set_pantalla('procesamiento');
	}

	function evt__volver()
	{
		unset($this->s__seleccion);
		unset($this->s__filtro);
		unset($this->s__datos_formulario);
		$this->cn()->limpiar_memoria();
		$this->set_pantalla('seleccion');
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_categorias_cursos_moodle()
	{
		$this->s__MoodleCategoriasCursos = $this->get_moodle_obj($this->s__datos['responsable_academica']['valor'])->getCategoriasCursos();
		return $this->s__MoodleCategoriasCursos;
	}

	function get_moodle_obj($responsable_academica)
	{
		if (!isset($this->moodle_obj)) {
			$this->s__datos_plataforma = toba::consulta_php('co_configuracion')->get_plataforma_responsable_academica($responsable_academica);
			$this->moodle_obj = new moodle($this->s__datos_plataforma['host'], $this->s__datos_plataforma['url'], $this->s__datos_plataforma['token']);
		}
		return $this->moodle_obj;
	}

	function ev_info_filtro()
	{
		if (!isset($this->ev_info_filtro)) {
			$this->ev_info_filtro = guarani::ev_info_datos();
			$this->ev_info_filtro->set_style('width: 100%;');
			$this->ev_info_filtro->set_titulo('Filtro para Comisiones');
			$this->ev_info_filtro->set_columnas_a_mostrar(array('anio_academico', 'periodo_nombre', 'comisiones', 'opciones'));
			$this->ev_info_filtro->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info_filtro->set_etiqueta('periodo_nombre', 'Período Lectivo');
			$this->ev_info_filtro->set_etiqueta('comisiones', 'Comisiones');
			$this->ev_info_filtro->set_etiqueta('opciones', 'Opciones');
		}
		return $this->ev_info_filtro;
	}

	function get_datos_ev_info_filtro()
	{
		$datos = current($this->s__seleccion);
		$datos['comisiones'] = array_column($this->s__seleccion, 'comision_nombre');
		$datos['opciones'] = array();
		if ($this->s__datos_formulario['act_matricula_alumnos'] == 1) {
			$datos['opciones'][] = 'Actualizar matrícula de alumnos';
		}
		if ($this->s__datos_formulario['act_matricula_docentes'] == 1) {
			$datos['opciones'][] = 'Actualizar matrícula de docentes';
		}
		if ($this->s__datos_formulario['agrupar_comisiones'] == 1) {
			$datos['opciones'][] = 'Agrupar comisiones de la misma actividad y crear un único curso virtual';
		}
		return $datos;
	}
}
?>