<?php
/**
 *
 *
 * @package Examenes
 * @subpackage Inscripciones Automáticas
 */
class ci_nav_inscribir_automaticamente_a_examen extends ci_nav_procesos_bk
{
	//Filtros
	protected $s__filtro;
	//Datos Filtros
	protected $s__datos_filtro;
	//Flags para eliminar la selección al filtrar
	protected $seleccion_filtrada = false;
	//Selección en el cuadro de Mesas asociadas a Comisiones.
	protected $s__seleccion_mesas_comisiones;
	//Indices de mensajes.
	protected $indx_msj_eof = '800_eof_cuadro';
	protected $indx_msj_confirm_insc = '800EXA_confirmar_insc_exa_auto';
	protected $indx_msj_no_seleccion = '800_no_seleccion';
	
	//Componentes Visuales de información de selecciones previas.
	protected $s__info_filtros;
	protected $ev_info_filtros;
	
	protected $s__proceso;

	function resetear_variables()
	{
		unset($this->s__filtro);
		unset($this->s__datos_filtro);
		unset($this->s__info_filtros);
		unset($this->s__seleccion_mesas_comisiones);
		unset($this->s__proceso);
	}

	function get_info_visual_filtros()
	{
		if (!isset($this->s__info_filtros)) {
			//Se obtienen los datos de los filtros seleccionados para configurar el ev de información.
			$this->s__info_filtros = array();
			if (isset($this->s__datos_filtro['anio_academico'])) {
				$this->s__info_filtros['anio_academico'] = $this->s__datos_filtro['anio_academico']['valor'];
			}
			if (isset($this->s__datos_filtro['periodo_lectivo'])) {
				$datos_per_lect = toba::consulta_php('co_periodos_lectivos')->get_datos_periodo_lectivo($this->s__datos_filtro['periodo_lectivo']['valor']);
				$this->s__info_filtros['periodo_lectivo_nombre'] = $datos_per_lect['periodo_nombre'];
			}
			if (isset($this->s__datos_filtro['turno_examen'])) {
				$datos_turno = toba::consulta_php('co_turnos_de_examen')->get_datos_turno($this->s__datos_filtro['turno_examen']['valor']);
				$this->s__info_filtros['turno_examen_nombre'] = $datos_turno['nombre'];
			}
			if (isset($this->s__datos_filtro['llamado_mesa'])) {
				$datos_llamado = toba::consulta_php('co_turnos_de_examen')->get_datos_llamado($this->s__datos_filtro['llamado_mesa']['valor']);
				$this->s__info_filtros['llamado_mesa_nombre'] = $datos_llamado['nombre'];
			}
		}
		return $this->s__info_filtros;
	}

	function set_nombre_proceso()
	{
		$this->s__nombre_proceso = 'pro_inscribir_automaticamente_a_examen';
	}

	//-----------------------------------------------------------------------------------
	//---- CONFIGURACIONES
	//-----------------------------------------------------------------------------------
	
	//---- Pantallas --------------------------------------------------------------------

	function conf__listado_comisiones_mesas(toba_ei_pantalla $pantalla)
	{
		//Se setea el msj de confirmación adecuado en el botón generar para confirmar las inscripciones automáticas.
		$msj_confirm_insc = guarani_mensajes::get($this->indx_msj_confirm_insc);
		$this->evento('generar')->set_msg_confirmacion($msj_confirm_insc);

		//El botón generar se desactiva si el cuadro de relaciones de comisiones mesas no tiene datos cargados.
		$datos_comisiones_mesas = $this->dep('cuadro_comisiones_mesas')->get_datos();
		if (empty($datos_comisiones_mesas)) {
			$this->evento('generar')->desactivar();
		} else {
			$this->evento('generar')->activar();
		}

		//Si el usuario presionó filtrar se elimina la selección actual del cuadro.
		if ($this->seleccion_filtrada) {
			unset($this->s__seleccion_mesas_comisiones);
		}
	}

	function conf__inscripcion(toba_ei_pantalla $pantalla)
	{
		$info_filtros = $this->get_info_visual_filtros();
		$this->ev_info_filtros()->set_datos($info_filtros);
	}

	//-----------------------------------------------------------------------------------
	//---- Pantalla inicial de listado de asociaciones entre Comisiones y Mesas ---------
	//-----------------------------------------------------------------------------------
	
	//-----------------------------------------------------------------------------------
	//---- filtro_comisiones_mesas ------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_comisiones_mesas(guarani_ei_filtro $filtro)
	{
		// Si el perfil de datos del usuario pertenece al dominio "Propuestas" es obligatorio filtrar los conceptos por ubicación.
		if (usuario::tiene_perfil_por_propuesta()) {
			$filtro->columna('ubicacion_mesa')->set_visible(true);
			$filtro->columna('ubicacion_mesa')->get_ef()->set_obligatorio(true);
		}
		if (isset($this->s__datos_filtro)) {
			$filtro->set_datos($this->s__datos_filtro);
		}
		$filtro->columna('anio_academico')->set_condicion_fija('es_igual_a');
		$filtro->columna('periodo_lectivo')->set_condicion_fija('es_igual_a');
		$filtro->columna('comision_procesada')->set_condicion_fija('es_igual_a');
		$filtro->columna('actividad')->set_condicion_fija('contiene');
	}

	function evt__filtro_comisiones_mesas__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_filtro = $datos;
			$filtro_comision_procesada = '';
			//El filtro por año académico es obligatorio por lo tanto se sabe que al menos este campo estará seteado.
			//Se construye el where para el filtro de comision procesada a partir del campo cantidad de la tabla de insc automáticas.
			if (isset($datos['comision_procesada'])) {
				if ($datos['comision_procesada']['valor'] == comunes::si) {
					$filtro_comision_procesada = ' AND sga_insc_auto_examen.cantidad > 0';
				} elseif ($datos['comision_procesada']['valor'] == comunes::no) {
					$filtro_comision_procesada = ' AND sga_insc_auto_examen.cantidad = 0';
				}
				$this->dep('filtro_comisiones_mesas')->columna('comision_procesada')->resetear_estado();
			}
			$this->s__filtro = "{$this->dep('filtro_comisiones_mesas')->get_sql_where()} $filtro_comision_procesada";
			//Se setea el flag para eliminar la selección del cuadro al filtrar.
			$this->seleccion_filtrada = true;
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

	function evt__filtro_comisiones_mesas__cancelar()
	{
		unset($this->s__datos_filtro);
		$this->s__filtro = '';
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_comisiones_mesas ------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_msj_eof($etiqueta)
	{
		$indx_msj = $this->indx_msj_eof;
		if (isset($this->s__filtro) && !empty($this->s__filtro)) {
			$indx_msj = $this->indx_msg_eof . '_filtrado';
		} else {
			$indx_msj = $this->indx_msg_eof . '_sinfiltro';
		}
		return guarani_mensajes::get($indx_msj, array($etiqueta));
	}

	function conf__cuadro_comisiones_mesas(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('sga_insc_auto_examen');
		$nombre = $etiqueta->get_enf('plural');
		$msj = $this->get_msj_eof($nombre);
		$cuadro->set_eof_mensaje($msj);
		/**
		 * Se obtienen las asociaciones de mesas a comisiones según el filtro y se setea la propiedad de 
		 * ordenamiento con cortes de control.
		 */
		if (isset($this->s__filtro) && !empty($this->s__filtro)) {
			$datos = toba::consulta_php('co_inscripciones_examenes')->get_datos_asociacion_comisiones_mesas_para_inscripcion($this->s__filtro);
			$cuadro->set_datos($datos);
		}
		$cuadro->set_usar_ordenamiento_con_cortes();
	}

	function evt__cuadro_comisiones_mesas__seleccion($datos)
	{
		$this->s__seleccion_mesas_comisiones = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- Pantalla de generación automática de Inscripciones a Exámenes ----------------
	//-----------------------------------------------------------------------------------
	
	//-----------------------------------------------------------------------------------
	//---- form_resultados --------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * El formulario donde se muestran los resultados finales del proceso no se permite editar. Los datos de sus
	 * campos se completan en la extensión js de la pantalla al finalizar el proceso de generación de inscripciones.
	 */
	function conf__form_resultados(toba_ei_formulario $form)
	{
		$form->set_solo_lectura();
		if (isset($this->s__datos_resultados)) {
			$form->set_datos($this->s__datos_resultados);
			if ($this->modo_visualizacion()) {
				foreach (array_keys($this->s__datos_resultados) as $ef) {
					if ($form->existe_ef($ef) && $form->existe_evento('rep_' . $ef) && $form->ef($ef)->get_estado() == 0)
						$form->evento('rep_' . $ef)->desactivar();
				}
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__generar()
	{
		/**
		 * Si el usuario confirmó la generación de inscripciones, se controla que se haya seleccionado al menos una 
		 * de las relaciones de mesas y comisiones. En caso contrario se emite el msj de error correspondiente.
		 */
		if (empty($this->s__seleccion_mesas_comisiones)) {
			$msj_no_seleccion = guarani_mensajes::get($this->indx_msj_no_seleccion, array('una asociación entre Mesa y Comisión'));
			throw guarani::error($msj_no_seleccion);
		} else {
			$this->set_pantalla('inscripcion');
			// Se dispara la ejecución del proceso de generación de inscripciones automáticas en background.
			// Se arma el array con los parámetros a mandar en la creación del proceso
			$parametros = array_merge(array('comisiones_llamados_mesa' => $this->s__seleccion_mesas_comisiones,
				'usuario' => toba::usuario()->get_id(),
				'operacion' => guarani::get_id_operacion()));

			// Se crea y ejecuta el proceso background
			$this->s__proceso = guarani::proceso_bk('pro_inscribir_automaticamente_a_examen');
			$this->s__proceso->set_datos($parametros);
			guarani::ejecutar_proceso_bk($this->s__proceso);
		}
	}

	function evt__volver()
	{
		$this->resetear_variables();
		$this->set_pantalla('listado_comisiones_mesas');
	}

	function evt__volver_inicio()
	{
		$this->set_pantalla('listado_comisiones_mesas');
	}

	function evt__reporte_insc()
	{
		
	}

	function evt__abortar()
	{
		if (!$this->cn() instanceof cn_procesos_bk) {
			$this->s__proceso->detener_procesamiento();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares para la visualización de las selecciones previas ------------------
	//-----------------------------------------------------------------------------------

	function ev_info_filtros()
	{
		if (!isset($this->ev_info_filtros)) {
			$this->ev_info_filtros = guarani::ev_info_datos();
			$this->ev_info_filtros->set_controlador($this);
			$this->ev_info_filtros->set_style('border-style: solid; border-width: 1px; border-color: #DDD;');
			$this->ev_info_filtros->set_style_fila('height: 20px;');
			$this->ev_info_filtros->set_columnas_a_mostrar(array('anio_academico', 'periodo_lectivo_nombre', 'turno_examen_nombre', 'llamado_mesa_nombre'));
			$this->ev_info_filtros->set_columnas_fila(array('anio_academico', 'periodo_lectivo_nombre'), 1);
			$this->ev_info_filtros->set_columnas_fila(array('turno_examen_nombre', 'llamado_mesa_nombre'), 2);
			$this->ev_info_filtros->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info_filtros->set_etiqueta('periodo_lectivo_nombre', 'Período Lectivo');
			$this->ev_info_filtros->set_etiqueta('turno_examen_nombre', 'Turno de Examen');
			$this->ev_info_filtros->set_etiqueta('llamado_mesa_nombre', 'Llamado');
		}
		return $this->ev_info_filtros;
	}

	//-----------------------------------------------------------------------------------
	//---- Funciones ajax utilizadas en el proceso de generación de inscripciones -------
	//-----------------------------------------------------------------------------------

	function ajax__estado_proceso($parametros, toba_ajax_respuesta $respuesta)
	{
		$contenido = $this->s__proceso->get_estado();
		if (!empty($contenido)) {
			$respuesta->set($contenido);
		}
	}

	function ajax__resultados_proceso($parametros, toba_ajax_respuesta $respuesta)
	{
		// Se recuperan los resultados del proceso (el arreglo con las comisiones y sus datos asociados, y las cantidades totales de inscripciones).
		$com_procesadas = $this->s__proceso->get_resultado(pro_inscribir_automaticamente_a_examen::reporte_comisiones_procesadas);
		$totales_insc = $this->s__proceso->get_resultado(pro_inscribir_automaticamente_a_examen::reporte_resultados);

		// Título y encabezado del reporte
		$datos = array('comisiones_inscripciones' => $com_procesadas['mensajes']);
		$conf = $this->get_info_visual_filtros();

		// Se almacenan los datos en la instancia para que sean recuperados luego en la impresión
		guarani_datos_impresion::instancia()->set_conf($conf);
		guarani_datos_impresion::instancia()->set_datos($datos);
		guarani_datos_impresion::instancia()->guardar_datos_imp();

		// Se retorna el array de resultados con las cantidades totales como respuesta.
		$resultados = current($totales_insc['mensajes']);
		if (!empty($resultados)) {
			$respuesta->set($resultados);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		parent::extender_objeto_js();
		$msj_confirm_filtrar = guarani_mensajes::get('800_confirmar_filtro_pierde_datos');

		// Advertencia de pérdida de datos en el cuadro multiselección de asociaciones de mesas a comisiones al filtrar.
		if ($this->get_id_pantalla() == 'listado_comisiones_mesas') {
			$id_js_filtro = $this->dependencia('filtro_comisiones_mesas')->get_id_objeto_js();
			$id_js_cuadro = $this->dependencia('cuadro_comisiones_mesas')->get_id_objeto_js();

			echo "
				{$id_js_filtro}.evt__filtrar = function() {
					// La advertencia sólo debe emitirse en caso de que el cuadro tenga filas seleccionadas.
					filas_seleccionadas = {$id_js_cuadro}.get_ids_seleccionados('seleccion');
					if (filas_seleccionadas.length > 0) {
						return confirmar_filtrado();
					}
				}

				function confirmar_filtrado(proyecto, operacion, url, es_popup) {
					if (! es_popup || typeof(es_popup) == 'undefined') {
						resp = confirm('$msj_confirm_filtrar');
						if (! resp) {
							return false;
						}
					}
					return true;
				}
			";
		}
	}

}
?>