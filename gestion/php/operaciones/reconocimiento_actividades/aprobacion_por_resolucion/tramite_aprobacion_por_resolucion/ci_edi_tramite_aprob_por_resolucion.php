<?php
/**
 *
 * 
 * @package 	Reconocimiento de Actividades
 * @subpackage 	Aprobación por Resolución
 * 
 */
class ci_edi_tramite_aprob_por_resolucion extends guarani_pers_ci
{
	// Msj para cuadros vacíos
	protected $indx_msj_eof = '800_eof_cuadro';
	
	// Datos temporales para formularios
	protected $datos_generales_temp;
	protected $datos_cred_globales_temp;
	protected $datos_activ_reconocidas_temp;
	
	// Filtro y Cuadro de Selección de Actividades a Reconocer
	protected $seleccion_actividades;
	protected $s__datos_filtro_activ;
	protected $s__filtro_activ;

	//-----------------------------------------------------------------------------------
	//---- PANTALLA DATOS PRINCIPALES
	//-----------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- form_datos_generales ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_datos_generales(form_datos_generales $form)
	{
		if (isset($this->datos_generales_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_generales_temp);
		} else {
			$datos = $this->cn()->entidad()->get_datos_generales();
			$form->set_datos($datos);
		}
	}

	function evt__form_datos_generales__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_generales($datos);
		} catch (guarani_error $e) {
			$this->datos_generales_temp = $datos;
			throw $e;
		}
	}

	function evt__form_datos_generales__cambio_tipo_reconocimiento($datos)
	{
		// Eliminar las actividades/módulos o los créditos globales.
		if ($datos['tipo_reconocimiento'] == aprobacion_por_resolucion::activ_modulos) {
			$this->cn()->entidad()->set_datos_generales(array('total_creditos' => 0));
		} else {
			$this->cn()->entidad()->eliminar_actividades_reconocidas();
		}

		$this->evt__form_datos_generales__modificacion($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA ACTIVIDADES REALIZADAS 
	//-----------------------------------------------------------------------------------

	function evt__pant_actividades_realizadas__salida()
	{
		$this->dep('editor_actividades')->evt__cancelar();
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA RECONOCIMIENTO 
	//-----------------------------------------------------------------------------------

	function conf__pant_reconocimiento(toba_ei_pantalla $pant)
	{
		// Se agregan las dependencias correspondientes a Actividades/Módulos o Créditos Globales, según lo 
		// que se haya seleccionado como Tipo de Reconocimiento en la primera solapa.
		$datos_tramite = $this->cn()->entidad()->get_datos_generales();
		if ($pant->existe_dependencia('form_creditos_globales')) {
			$pant->eliminar_dep('form_creditos_globales');
		}
		if ($pant->existe_dependencia('cuadro_activ_a_reconocer')) {
			$pant->eliminar_dep('cuadro_activ_a_reconocer');
		}
		if ($pant->existe_dependencia('filtro_activ_a_reconocer')) {
			$pant->eliminar_dep('filtro_activ_a_reconocer');
		}
		if ($pant->existe_dependencia('form_ml_activ_reconocidas')) {
			$pant->eliminar_dep('form_ml_activ_reconocidas');
		}
		$tipo_reconoc = $datos_tramite['tipo_reconocimiento'];
		if ($tipo_reconoc == aprobacion_por_resolucion::activ_modulos) {
			$pant->agregar_dep('form_ml_activ_reconocidas');
			$pant->agregar_dep('filtro_activ_a_reconocer');
			$pant->agregar_dep('cuadro_activ_a_reconocer');
		} elseif ($tipo_reconoc == aprobacion_por_resolucion::cred_globales) {
			$pant->agregar_dep('form_creditos_globales');
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_ml_activ_reconocidas ----------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf_fila__form_ml_activ_reconocidas($fila)
	{
		$datos_ml = $this->dep('form_ml_activ_reconocidas')->get_datos(true);
		/**
		 * Si el tipo de elemento que se está reconociendo es una actividad, el reconocimiento se setea como total
		 * y no será editable. En caso de ser una módulo el reconocimiento será editable y no tendrá valor default.
		 */
		if (isset($datos_ml[$fila]['elemento_tipo']) && $datos_ml[$fila]['elemento_tipo'] == elemento::tipo_actividad) {
			$this->dependencia('form_ml_activ_reconocidas')->ef('reconocimiento_total')->set_estado(comunes::si);
			$this->dependencia('form_ml_activ_reconocidas')->ef('reconocimiento_total')->set_solo_lectura(true);
		} else {
			$this->dependencia('form_ml_activ_reconocidas')->ef('reconocimiento_total')->set_solo_lectura(false);
		}
	}

	function conf__form_ml_activ_reconocidas(toba_ei_formulario_ml $form_ml)
	{
		if (isset($this->datos_activ_reconocidas_temp)) {
			$this->controlador()->set_hubo_cambios();
			$datos = $this->datos_activ_reconocidas_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos_actividades_reconocidas();
		}

		$icono_informacion = new icono_info_escala_nota();
		$form_ml->ef('escala_nota')->agregar_icono_utileria($icono_informacion);
		$form_ml->set_datos($datos);
	}

	function evt__form_ml_activ_reconocidas__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_actividades_reconocidas($datos);
		} catch (guarani_error $e) {
			$this->datos_activ_reconocidas_temp = $datos;
			throw $e;
		}
	}

	function evt__form_ml_activ_reconocidas__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_actividad_reconocida($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- filtro_activ_a_reconocer -----------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_activ_a_reconocer(guarani_ei_filtro $filtro)
	{
		if (isset($this->s__datos_filtro_activ)) {
			$filtro->set_datos($this->s__datos_filtro_activ);
		}
	}

	function evt__filtro_activ_a_reconocer__filtrar($datos)
	{
		if (isset($datos) && !empty($datos)) {
			$this->s__datos_filtro_activ = $datos;
			$this->s__filtro_activ = $this->dep('filtro_activ_a_reconocer')->get_sql_where();
		} else {
			unset($this->s__datos_filtro_activ);
			$this->s__filtro_activ = '';
		}
	}

	function evt__filtro_activ_a_reconocer__cancelar()
	{
		unset($this->s__datos_filtro_activ);
		unset($this->s__filtro_activ);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_activ_a_reconocer -----------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_activ_a_reconocer(cuadro_seleccion_multiple $cuadro)
	{
		// Setea el msj eof del cuadro.
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msj_eof, array('Actividades/Módulos posibles para Reconocer')));
		$activ_reconocidas = $this->cn()->entidad()->get_datos_actividades_reconocidas();
		if (!empty($activ_reconocidas)) {
			$cond_not_in_activ = guarani_sql::armar_condicion_in($activ_reconocidas, 'vw_actividades_plan', 'elemento', 'elemento', true);
			$cond_not_in_mod = guarani_sql::armar_condicion_in($activ_reconocidas, 'vw_modulos_plan', 'elemento', 'elemento', true);
			if (isset($this->s__filtro_activ) && trim($this->s__filtro_activ) != '') {
				$cond_not_in_activ .= ' AND ' . $this->s__filtro_activ;
				$cond_not_in_mod .= ' AND ' . $this->s__filtro_activ;
			}
		} else {
			$cond_not_in_activ = $this->s__filtro_activ;
			$cond_not_in_mod = $this->s__filtro_activ;
		}
		$datos_tramite = $this->cn()->entidad()->get_datos_generales();
		$actividades = toba::consulta_php('co_aprobacion_por_resolucion')->get_elementos_disponibles_tramite_aprobacion($datos_tramite['alumno'], $datos_tramite['plan_version'], $cond_not_in_activ, $cond_not_in_mod);
		$cuadro->set_datos($actividades);
	}

	function evt__cuadro_activ_a_reconocer__seleccion($seleccion)
	{
		$this->seleccion_actividades = $seleccion;
	}

	function evt__cuadro_activ_a_reconocer__agregar($datos)
	{
		$datos_tramite = $this->cn()->entidad()->get_datos_generales();
		if (!empty($this->seleccion_actividades)) {
			foreach ($this->seleccion_actividades as $fila => $actividad) {
				$this->cn()->entidad()->agregar_actividad_reconocida($actividad, $datos_tramite['plan_version']);
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_creditos_globales -------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_creditos_globales(toba_ei_formulario $form)
	{
		if (isset($this->datos_cred_globales_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_cred_globales_temp);
		} else {
			if ($this->cn()->entidad_es_nueva()) {
				// Para el caso del Alta se setea como dato defecto del total de créditos, la sumatoria de los
				// créditos asignados a los distintos documentos presentados por actividades realizadas.
				$suma_creditos = $this->cn()->entidad()->get_sumatoria_creditos_activ_realizadas_docs();
				$form->ef('total_creditos')->set_estado($suma_creditos);
			} else {
				// En el caso de edición el dato del total de créditos se recupera de la tabla de datos generales.
				$datos_generales = $this->cn()->entidad()->get_datos_generales();
				$form->set_datos($datos_generales);
			}
		}
	}

	function evt__form_creditos_globales__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_generales($datos);
		} catch (guarani_error $e) {
			$this->datos_cred_globales_temp = $datos;
			throw $e;
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Consultas AJAX ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Cuando el usuario asigna un Documento respaldatorio al trámite si la Fecha del Trámite no tiene estado
	 * se asigna como valor default la fecha asociada al Documento.
	 */
	function ajax__fecha_tramite_default($params, toba_ajax_respuesta $respuesta)
	{
		// En el primer parámetro se tiene el id del Documento respaldatorio del Trámite.
		$id_doc = current($params);
		$datos_doc = toba::consulta_php('co_documentos')->get_datos_documentos($id_doc);
		if (!empty($datos_doc)) {
			$salida = array('fecha' => $datos_doc['fecha_formato_visual']);
			$respuesta->set($salida);
		} else {
			$respuesta->set(false);
		}
	}

	function ajax__validar_cambio_tipo($params, toba_ajax_respuesta $respuesta)
	{
		$tipo_reconocimiento = current($params);

		$actividades = $this->cn()->entidad()->get_datos_actividades_reconocidas();
		$creditos_globales = $this->cn()->entidad()->get_columna_reconocimiento('total_creditos');

		$salida = false;
		if ($tipo_reconocimiento == aprobacion_por_resolucion::activ_modulos && $creditos_globales != 0 ||
				$tipo_reconocimiento == aprobacion_por_resolucion::cred_globales && !empty($actividades)) {
			$salida = array('mensaje' => guarani_mensajes::get('800EQU_confirmar_cambio_tipo'));
		}
		$respuesta->set($salida);
	}

	//-----------------------------------------------------------------------------------
	//---- JavaScript -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Retorna las funciones js para validar las notas de las distintas escalas de notas activas.
	 *
	 */
	function get_js_validar_nota_escala()
	{
		$escalas = toba::consulta_php('co_escalas_notas')->get_ids_escalas_notas_activas();
		$escalas = rs_convertir_asociativo_matriz($escalas, array('escala_nota'), array('escala_nota'));
		$validador = escalas_notas::validador_escalas_notas();
		return $validador->get_js_validar_nota_escalas($escalas);
	}

	function get_alumno_nombre($id)
	{
		return toba::consulta_php('co_alumnos')->get_alumno_nombre($id);
	}

}
?>