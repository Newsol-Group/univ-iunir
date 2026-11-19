<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class ci_edi_requisitos_documentales extends guarani_pers_ci
{
	// Datos Temporales
	protected $datos_temp;
	protected $datos_temp_excepcion;
	protected $datos_temp_prorroga;
	protected $requisitos_seleccionados;
	protected $s__requisito_seleccionado;
	protected $s__requisitos_pantalla;
	protected $s__msg_ayuda_documento_digital_draft;

	/**
	 * Configuración parametrizada que determina si se utiliza Repositorio Digital 
	 * Integrado (RDI) para almacenar los archivos asociados a requisitos documentales.
	 * 
	 * @return boolean
	 */
	function usa_repositorio_digital()
	{
		return $this->cn()->entidad()->usa_repositorio_digital();
	}

	function get_requisito_seleccionado()
	{
		$salida = isset($this->s__requisito_seleccionado) ? $this->s__requisito_seleccionado : null;
		return $salida;
	}
	
	function get_msg_ayuda_documento_digital_draft()
	{
		if (!isset($this->s__msg_ayuda_documento_digital_draft)) {
			$this->s__msg_ayuda_documento_digital_draft = guarani_mensajes::get('800_msg_ayuda_documento_digital_draft');
		}
		return $this->s__msg_ayuda_documento_digital_draft;
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: pant_edicion
	//-----------------------------------------------------------------------------------

	function conf__pant_edicion()
	{
		unset($this->s__requisito_seleccionado);
	}

	//-----------------------------------------------------------------------------------
	//---- requisitos -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__requisitos($form_ml)
	{
		// Manejo condicional de campos en función del uso de Repositorio Digital
		if (!$this->usa_repositorio_digital()) {
			$form_ml->desactivar_efs(array('estado_doc_digital'));
		}

		$datos = $this->cn()->entidad()->get_datos();
		
		// Si hubo errores de validación, se restauran las selecciones
		if (isset($this->datos_temp)) {
			foreach ($datos as $key => $dato) {
				$temp = $this->buscar_temp($dato['requisito']);
				$datos[$key]['presentado'] = $temp['presentado'];
				$datos[$key]['fecha_presentacion'] = $temp['fecha_presentacion'];
				$datos[$key]['fecha_vencimiento'] = $temp['fecha_vencimiento'];
			}
		}

		$form_ml->set_datos($datos);
	}
	
	protected function buscar_temp($requisito)
	{
		foreach ($this->datos_temp as $dato) {
			if ($dato['requisito'] == $requisito) {
				return $dato;
			}
		}
	}

	function conf_fila__requisitos($fila)
	{
		$this->dep('requisitos')->set_solo_lectura(array('presentado'), false);
		$datos_ml = $this->dep('requisitos')->get_datos(true);
		if ($datos_ml[$fila]['presentado']) {
			// Si está presentado, se bloquea el check "Presentado".
			// Si hay que revertir, hay un botón a tal efecto.
			$this->dep('requisitos')->set_solo_lectura(array('presentado'));
		} else {
			if ((isset($datos_ml[$fila]['agregado'])) && ($datos_ml[$fila]['agregado'] == 1)) {
				// El requisito agregado se considera presentado SIEMPRE
				$this->dep('requisitos')->set_solo_lectura(array('presentado'));
			} else {
				if ($datos_ml[$fila]['exceptuado_todas']) {
					$this->dep('requisitos')->set_solo_lectura(array('presentado'));
				}
			}
		}
	}

	function evt__requisitos__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos($datos);
		} catch (guarani_error $e) {
			$this->datos_temp = $datos;
			throw $e;
		}
	}

	function evt__requisitos__exceptuar($seleccion)
	{
		$datos = $this->dep('requisitos')->get_datos(true);
		$this->s__requisito_seleccionado = $datos[$seleccion]['requisito'];
		$this->set_pantalla('pant_exceptuar');
	}

	function conf_evt__requisitos__exceptuar(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('requisitos')->get_datos(true);
		if (($datos[$fila]['estado'] == requisito::estado_vigente) && (!$datos[$fila]['exceptuado_algunas'])) {
			$evento->anular();
		}
		if ($datos[$fila]['solicitado_para'] == requisito::requisito_para_egreso_descr || $datos[$fila]['solicitado_para'] == requisito::requisito_para_ninguno_descr) { // Los requisitos solicitados para egreso no se pueden exceptuar.
			$evento->anular();
		}
	}

	function evt__requisitos__prorrogar($seleccion)
	{
		$datos = $this->dep('requisitos')->get_datos(true);
		$this->s__requisito_seleccionado = $datos[$seleccion]['requisito'];
		$this->set_pantalla('pant_prorogar');
	}

	function conf_evt__requisitos__prorrogar(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('requisitos')->get_datos(true);
		if ($datos[$fila]['solicitado_para'] == requisito::requisito_para_ingreso_descr || $datos[$fila]['solicitado_para'] == requisito::requisito_para_ingreso_egreso_descr) {
			if ($datos[$fila]['estado'] == requisito::estado_vigente) {
				$evento->anular();
			} elseif ($datos[$fila]['exceptuado_todas']) {
				$evento->anular();
			}
		} else { // Los requisitos solicitados para egreso no se pueden prorrogar.
			$evento->anular();
		}
	}

	function evt__requisitos__ver($seleccion)
	{
		$datos = $this->dep('requisitos')->get_datos(true);
		$this->s__requisito_seleccionado = $datos[$seleccion]['requisito'];
		$this->set_pantalla($this->s__requisitos_pantalla[$seleccion]);
	}

	/**
	 * Se unifican los botones "Doc. Digital" y "Doc. Draft" en el botón Ver
	 * 
	 * @param toba_evento_usuario $evento
	 * @param int $fila
	 */
	function conf_evt__requisitos__ver(toba_evento_usuario $evento, $fila)
	{
		$doc_digital = false;
		$doc_draft = false;
		
		$cn = $this->cn()->entidad();
		// Si usa RDI
		if ($this->usa_repositorio_digital()) {
			// Si usa Repositorio Digital, la carga de Documentos Digitales está
			// disponible sólo si el requisito tiene asociado Tipo de Documento
			$datos = $this->dep('requisitos')->get_datos(true);
			if (!empty($datos[$fila]['tipo_documento_digital'])) {
				$doc_digital = true;
			}
			
			/*	Se anula el evento para recuperar un documento cargado en Draft en los siguientes casos:
				*  + Si no se usa Repositorio Digital
				*  + Si se ha rechazado en Draft
				*  + Si ya hay un documento cargado
				*  + Si el requisito no tiene un Tipo de Documento Digital asociado
				*  + Si no hay un documento cargado en Draft para ese requisito
			 */
			$requisito = $datos[$fila]['requisito'];
			if ($this->cn()->entidad()->rechazado_draft($requisito)) {
				// Ha sido marcado para rechazar en Draft
			} elseif (($datos[$fila]['estado_doc_digital_id'] != $cn::estado_rdi_PEND_VAL) || (empty($datos[$fila]['tipo_documento_digital']))) {
				// Hay un manejo desde Gestión, o no hay un tipo digital asociado
			} elseif ($this->cn()->entidad()->hay_doc_en_draft($requisito)) {
				// Hay documento digital cargado en Draft para el requisito
				$doc_draft = true;
			}
		}
		
		// Asigno la pantalla correspondiente a cada requisito
		$this->s__requisitos_pantalla[$fila] = ($doc_draft)? 'pant_doc_draft' : 'pant_req_digital';
		
		if ($doc_draft) {// Si es draft muestro mensaje de ayuda en el botón Ver
			$evento->set_msg_ayuda($this->get_msg_ayuda_documento_digital_draft());	
		}
		
		// Si no hay Doc. Digital ni Doc.Draft anulo el evento
		if (!$doc_digital && !$doc_draft) {
			$evento->anular();
		}
	}
	
	function evt__requisitos__eliminar($seleccion)
	{
		$datos = $this->dep('requisitos')->get_datos(true);
		$this->cn()->entidad()->eliminar_requisito($datos[$seleccion]['requisito']);
	}

	function conf_evt__requisitos__eliminar(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('requisitos')->get_datos(true);

		if (isset($datos[$fila]['hay_doc_temp_cargado']) && ($datos[$fila]['hay_doc_temp_cargado'])) {
			$mensaje = "Eliminar esta presentación implicará también perder la asociación con el documento digital. ¿Desea continuar?";
			$evento->set_msg_confirmacion($mensaje);
		}

		if ((empty($datos[$fila]['agregado'])) && (!$datos[$fila]['presentado'])) {
			$evento->anular();
		}
	}

	function conf_evt__requisitos__presentaciones_ant(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('requisitos')->get_datos(true);
		$requisito = $datos[$fila]['requisito'];
		$persona = $this->cn()->entidad()->get_persona_seleccionada();
		if ($this->cn()->entidad()->hay_presentaciones_vencidas($requisito)) {
			$evento->vinculo()->agregar_parametro(utf8_encode('requisito'), utf8_encode($requisito));
			$evento->vinculo()->agregar_parametro(utf8_encode('persona'), utf8_encode($persona));
		} else {
			$evento->anular();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: pant_agregar
	//-----------------------------------------------------------------------------------

	function conf__pant_agregar()
	{
		$ci_navegacion = $this->controlador()->controlador();
		$ci_navegacion->evento('guardar')->desactivar();
		if ($ci_navegacion->pantalla()->existe_evento('cancelar')) {
			$ci_navegacion->evento('cancelar')->desactivar();
		}
		if ($ci_navegacion->pantalla()->existe_evento('volver_navegacion')) {
			$ci_navegacion->evento('volver_navegacion')->desactivar();
		}
		$this->controlador()->pantalla()->tab('pant_procesos')->desactivar();
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_req_disponibles -------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_req_disponibles(toba_ei_cuadro $cuadro)
	{
		$datos = $this->cn()->entidad()->get_datos_requisitos_disponibles_agregar();
		$cuadro->set_datos($datos);
	}

	function evt__cuadro_req_disponibles__seleccion($seleccion)
	{
		$this->requisitos_seleccionados = $seleccion;
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: pant_exceptuar
	//-----------------------------------------------------------------------------------

	function conf__pant_exceptuar()
	{
		$ci_navegacion = $this->controlador()->controlador();
		$ci_navegacion->evento('guardar')->desactivar();
		if ($ci_navegacion->pantalla()->existe_evento('cancelar')) {
			$ci_navegacion->evento('cancelar')->desactivar();
		}
		if ($ci_navegacion->pantalla()->existe_evento('volver_navegacion')) {
			$ci_navegacion->evento('volver_navegacion')->desactivar();
		}
		$this->controlador()->pantalla()->tab('pant_procesos')->desactivar();
	}

	//-----------------------------------------------------------------------------------
	//---- form_ml_exceptuar ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_ml_exceptuar(toba_ei_formulario_ml $form_ml)
	{
		if (isset($this->datos_temp_excepcion)) {
			$datos = $this->datos_temp_excepcion;
		} else {
			$datos = $this->cn()->get_datos_requisitos_aspirante_excepcion($this->s__requisito_seleccionado);
		}
		$form_ml->set_datos($datos);
	}

	function evt__form_ml_exceptuar__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->procesar_datos_requisitos_aspirante($datos);
		} catch (guarani_error $e) {
			$this->datos_temp_excepcion = $datos;
			throw $e;
		}
	}

	function conf_fila__form_ml_exceptuar($fila)
	{
        /*
		// Si la propuesta está aceptada, y el requisito está exceptuado, no se puede revertir la excepción.
		$datos_ml = $this->dep('form_ml_exceptuar')->get_datos(true);
		$propuesta_aspira = $datos_ml[$fila]['propuesta_aspira'];

		$datos_per_insc = toba::consulta_php('co_periodos_de_inscripcion')->get_datos_periodo_insc($propuesta_aspira);
		if (guarani_fecha::es_menor_a_fecha_actual($datos_per_insc['fecha_cierre_requisitos'])) {
			// Ya pasó la fecha cierre de requisitos -> no se puede revertir la excepción
			$this->dep('form_ml_exceptuar')->set_solo_lectura(array('exceptuado'));
			$this->dep('form_ml_exceptuar')->set_solo_lectura(array('fecha_excepcion', 'motivo_excepcion', 'documento_excepcion'), false);
		}*/
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: pant_prorogar
	//-----------------------------------------------------------------------------------

	function conf__pant_prorogar()
	{
		$ci_navegacion = $this->controlador()->controlador();
		$ci_navegacion->evento('guardar')->desactivar();
		if ($ci_navegacion->pantalla()->existe_evento('cancelar')) {
			$ci_navegacion->evento('cancelar')->desactivar();
		}
		if ($ci_navegacion->pantalla()->existe_evento('volver_navegacion')) {
			$ci_navegacion->evento('volver_navegacion')->desactivar();
		}
		$this->controlador()->pantalla()->tab('pant_procesos')->desactivar();
	}

	//-----------------------------------------------------------------------------------
	//---- form_ml_prorrogar ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_ml_prorrogar($form_ml)
	{
		if (isset($this->datos_temp_prorroga)) {
			$datos = $this->datos_temp_prorroga;
		} else {
			$datos = $this->cn()->get_datos_requisitos_aspirante_prorroga($this->s__requisito_seleccionado);
		}
		$form_ml->set_datos($datos);
	}

	function conf_fila__form_ml_prorrogar($fila)
	{
		$datos_ml = $this->dep('form_ml_prorrogar')->get_datos(true);
		if (isset($datos_ml[$fila]['exceptuado']) && ($datos_ml[$fila]['exceptuado'] == comunes::si)) {
			$this->dep('form_ml_prorrogar')->set_solo_lectura(array('prorrogado'));
		} else {
			$this->dep('form_ml_prorrogar')->set_solo_lectura(array('prorrogado'), false);
		}
	}

	function evt__form_ml_prorrogar__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->procesar_datos_requisitos_aspirante($datos);
		} catch (guarani_error $e) {
			$this->datos_temp_prorroga = $datos;
			throw $e;
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: pant_req_digital
	//-----------------------------------------------------------------------------------

	function conf__pant_req_digital()
	{
		$ci_navegacion = $this->controlador()->controlador();
		$ci_navegacion->evento('guardar')->desactivar();
		if ($ci_navegacion->pantalla()->existe_evento('cancelar')) {
			$ci_navegacion->evento('cancelar')->desactivar();
		}
		if ($ci_navegacion->pantalla()->existe_evento('volver_navegacion')) {
			$ci_navegacion->evento('volver_navegacion')->desactivar();
		}
		$this->controlador()->pantalla()->tab('pant_procesos')->desactivar();
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__agregar()
	{
		$this->set_pantalla('pant_agregar');
	}

	function evt__cancelar()
	{
		unset($this->s__requisito_seleccionado);
		$this->set_pantalla('pant_edicion');
	}

	function evt__aceptar()
	{
		unset($this->s__requisito_seleccionado);
		$this->set_pantalla('pant_edicion');
	}

	function evt__aceptar_seleccion()
	{
		if (!empty($this->requisitos_seleccionados)) {
			foreach ($this->requisitos_seleccionados as $req) {
				$this->cn()->entidad()->agregar_requisito($req['requisito']);
			}
		}
		$this->set_pantalla('pant_edicion');
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: pant_doc_draft
	//-----------------------------------------------------------------------------------

	function conf__pant_doc_draft()
	{
		$ci_navegacion = $this->controlador()->controlador();
		$ci_navegacion->evento('guardar')->desactivar();
		if ($ci_navegacion->pantalla()->existe_evento('cancelar')) {
			$ci_navegacion->evento('cancelar')->desactivar();
		}
		if ($ci_navegacion->pantalla()->existe_evento('volver_navegacion')) {
			$ci_navegacion->evento('volver_navegacion')->desactivar();
		}
		$this->controlador()->pantalla()->tab('pant_procesos')->desactivar();
	}

}