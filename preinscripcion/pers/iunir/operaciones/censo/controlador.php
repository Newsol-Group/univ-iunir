<?php

namespace iunir\operaciones\censo;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use SIU\Chulupi\util\mail;
use SIU\Chulupi\nucleo\FinalizadorRequest;
use SIU\Chulupi\ErrorKernel;
use RDIExcepcion;
use siu\extension_kernel\controlador_preinscripcion;
use siu\modelo\util;
use siu\operaciones\censo\_comun\pagelet_con_edicion;
use siu\errores\error_preinscripcion;
use siu\modelo\g3\g3;
use SIU\Chulupi\util\ManejadorArchivos;
use siu\modelo\comunes;
use iunir\modelo\g3\consultas_bd\nsg_preinsc_facturacion;

class controlador extends controlador_preinscripcion
{
	protected $seleccion;
	protected $error_mail;
	protected $tramite_finalizado;

	/**
	 * @return null
	 */
	function modelo()
	{
		return null;
	}

	function es_tramite_finalizado()
	{
		if (!isset($this->tramite_finalizado)) {
			$estado = kernel::persona()->datos()->get_estado_cuenta_preinscripcion();
			$this->tramite_finalizado = ($estado == \siu\preinscripcion::estado_preparado);
		}
		return $this->tramite_finalizado;
	}

	function get_clase_vista()
	{
		if ($this->es_tramite_finalizado()) {
			return 'vista_finalizado';
		} else {
			return 'vista';
		}
	}

	function ini()
	{
		$this->controlar_csrf_accion('guardar');
	}

	function accion__index()
	{

		if (!$this->es_tramite_finalizado()) {
			$formulario = $this->load_form_activo();
			kernel::renderer()->add($formulario);
		}
	}
	
/*
	function get_mcp_trans_id($codfor, $nrofor, $tipo_documento, $nro_documento)
	{
		return
			$codfor . '_' .
			$nrofor . '_' .
			date("Y-m-d H:i:s") . '_' .
			$tipo_documento . '_' .
			$nro_documento;
	}
*/

	function accion__pagar_mcp() {
		$codfor = $this->get_param('codfor', 'post');
		$preinscripcion_propuesta = $this->get_param('id', 'post');
		$nrofor = $this->get_param('nrofor', 'post');

		//$tipo_documento = kernel::persona()->datos()->get_tipo_doc(); //96,
		//$nro_documento = kernel::persona()->datos()->get_nro_documento(); //31116453
		//$trans_id = $this->get_mcp_trans_id($codfor, $nrofor, $tipo_documento, $nro_documento);
		
		$facturacion = new nsg_preinsc_facturacion(true);
		$trans_id = $facturacion->get_mcpTransId(['codfor' => $codfor, 'nrofor' => $nrofor]);
		$ret_value = [
			'trans_id' => $trans_id,
			'preinscripcion_propuesta' => $preinscripcion_propuesta
		];
		$this->render_raw_json($ret_value);
	}

	function accion_pagar_mp() {
	}

	function accion__finalizar()
	{
		$hay_faltantes = kernel::persona()->datos()->hay_datos_faltantes();

		if ($hay_faltantes) {
			$this->vista()->activar_control_faltantes();
		} else if (!$this->puede_finalizar()) {
			$msj = kernel::traductor()->trans('censo.error_pago_incompleto');
			$this->render_raw_json([  
				'estado' => 'error',
				'mensaje' => $msj
			]);
			return;
		} else {
			$this->vista()->desactivar_control_faltantes();

			/**
			 * Se actualiza el estado de la preinscripci�n
			 *  - C: Formulario impreso. Listo para ingreso en Guarani
			 */
			
			try {
				kernel::db()->abrir_transaccion();
				kernel::persona()->datos()->actualizar_estado(\siu\preinscripcion::estado_preparado);
				kernel::persona()->datos()->eliminar_observaciones();
				// Se actualiza la versi�n impresa.
				kernel::persona()->datos()->actualizar_version_impresa();
				kernel::db()->cerrar_transaccion();
			} catch (\Exception $e) {
				kernel::db()->abortar_transaccion();
				kernel::log()->add_error($e);
				throw new error_preinscripcion(kernel::traductor()->trans('prop_finalizada.error'));
			}

			$this->nuevo_request(kernel::ruteador()->get_id_operacion(), 'index');
		}
	}

	function puede_finalizar()
	{
		$facturacion = new nsg_preinsc_facturacion(true); // o FALSE
		$propuestas_aux = [21, 22, 23, 24];
		
		$datos_preinscripciones = kernel::persona()->datos()->get_datos_inscripciones();
		$datos_matriculas = $facturacion->get_datosFacturacion
		(
			[
				'datos_preinscripciones' => $datos_preinscripciones,
				//'forzar_propuesta' => 1,
				//'forzar_ubicacion' => 1
			]
		);

		foreach ($datos_preinscripciones as $preinscripcion) {
			if (in_array($preinscripcion['propuesta'], $propuestas_aux)) {
				continue;
			}
			$preinsc_propuesta = $preinscripcion['preinscripcion_propuesta'];
			$matricula = $datos_matriculas[$preinsc_propuesta] ?? null;
			if (is_null($matricula)) return false;
			if ($matricula['estado_pago'] != 3) return false;
		}

		return true;
	}

	function accion__reabrir()
	{
		/**
		 * Se actualiza el estado de la preinscripci�n
		 *  - A: Activado. La persona respondi� el mail enviado
		 */
		kernel::persona()->datos()->actualizar_estado(\siu\preinscripcion::estado_activado);
		$this->nuevo_request(kernel::ruteador()->get_id_operacion(), 'propuesta');
	}

	function accion__imprimir()
	{
		//mensaje mail confirmando la recepcion de datos
		if ($this->es_la_primera_impresion()) {
			try {
				$dir_mail = kernel::persona()->get_mail();
				$nombre_institucion = kernel::proyecto()->get_parametro('rep_nombre_institucion');

				$asunto = kernel::traductor()->trans('mail_asunto_fin', array('%1%' => $nombre_institucion));
				$cuerpo = kernel::traductor()->trans('mail_cuerpo_fin', array('%1%' => $nombre_institucion));

				$mail = new mail($dir_mail, $asunto, $cuerpo, null, '', \siu\preinscripcion::charset);
				$mail->set_html(true);
				$mail->enviar();
			} catch (\Exception $e) {
				kernel::sesion()->set('error_mail', kernel::traductor()->trans('notificacion_email_puede_continuar'));
				kernel::log()->add_debug('==mensaje===', $e);
			}
		}
		$pdf = kernel::localizador()->instanciar('operaciones\\_comun\\generador_pdf');
		$pdf->get_comprobante();
	}

	function accion__ocultar_msg_impr()
	{
		$this->vista()->desactivar_control_faltantes();

		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO, array('default' => 'propuesta'));
		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);

		kernel::renderer()->add($pagelet);
	}

	function hay_cambios_desde_impresion()
	{
		return kernel::persona()->datos()->hay_cambios_desde_impresion();
	}
	function es_la_primera_impresion()
	{
		return kernel::persona()->datos()->es_la_primera_impresion();
	}
	protected function load_form_activo()
	{
		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO, array(
			'default' => 'propuesta'
		));
		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);
		return $pagelet;
	}

	function accion__guardar()
	{
		if (!kernel::request()->isPost()) {
			return;
		}

		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO, array('default' => 'propuesta'));
		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);

		$form = $pagelet->get_form();
		if ($form->procesar()) {
			$datos = $form->get_datos();

			// Si es la solapa "Datos principales"
			if ($formulario == "datos_principales") {
				// Si en "Identidad de G�nero y Diversidad" se elige distinto a "Ninguna de las anteriores. Especifique"
				if ($datos['identidad_genero'] != comunes::GENERO_DIVERSIDAD_ESPECIFIQUE) {
					$datos['identidad_genero_otro'] = '';
				}
			}

			kernel::persona()->datos()->set_datos_preinscripcion($datos);
			if ($this->hay_cambios_desde_impresion()) {
				$pagelet->set_estado_info(pagelet_datos_principales::FORM_CAMBIOS_DESDE_IMPRESION);
			} else {
				$pagelet->set_estado_info(pagelet_datos_principales::FORM_GUARDADO);
			}
		} else {
			$pagelet->set_estado_info(pagelet_datos_principales::FORM_ERROR_VALIDACION);
		}

		kernel::renderer()->add($pagelet);
	}

	function accion__guardar_propuesta()
	{
		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO, array('default' => 'propuesta'));
		$pagelet = $this->vista()->pagelet($formulario);
		$form = $pagelet->get_form();
		if ($form->procesar()) {
			$datos = $form->get_datos();
			kernel::persona()->datos()->set_propuesta_insc($datos);
		}
		$this->redirigir();
	}

	function accion__eliminar_preinscripcion()
	{
		if (kernel::request()->isPost()) {
			$datos = kernel::request()->getPost();
			kernel::persona()->datos()->eliminar_insc($datos['id']);
		}
	}

	protected function redirigir()
	{
		$this->nuevo_request(kernel::persona()->perfil()->get_parametro('index'));
	}

	//--------------------------------------------------------------------------
	//---- Edici�n de pagelets con edici�n
	//--------------------------------------------------------------------------

	function accion__edicion()
	{
		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO);
		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);

		$pagelet->set_pantalla(pagelet_con_edicion::ESTADO_EDICION);

		kernel::renderer()->add($pagelet);
	}

	function accion__volver_edicion()
	{
		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO);
		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);

		$pagelet->set_pantalla(pagelet_con_edicion::ESTADO_LISTADO);

		kernel::renderer()->add($pagelet);
	}

	//--------------------------------------------------------------------------
	//---- ABM's
	//--------------------------------------------------------------------------

	function accion__editar()
	{
		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO);
		$this->seleccion = $this->validate_param(1, 'get', validador::TIPO_ALPHANUM);
		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);

		$pagelet->set_pantalla(pagelet_con_edicion::ESTADO_EDICION);
		kernel::renderer()->add($pagelet);
	}

	//--------------------------------------------------------------------------
	//---- Actuaci�n Docente
	//--------------------------------------------------------------------------

	function accion__guardar_actuacion_docente()
	{
		if (!kernel::request()->isPost()) {
			return;
		}

		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO, array('default' => 'formacion_acad'));
		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);
		$form = $pagelet->get_form();

		if ($form->procesar()) {
			$datos = $form->get_datos();
			kernel::persona()->datos()->set_actuacion_docente($datos);
			$pagelet->set_estado_info(pagelet_datos_principales::FORM_GUARDADO);
			$pagelet->set_pantalla(pagelet_con_edicion::ESTADO_LISTADO);
		} else {
			$pagelet->set_estado_info(pagelet_datos_principales::FORM_ERROR_VALIDACION);
			$pagelet->set_pantalla(pagelet_con_edicion::ESTADO_EDICION);
		}

		kernel::renderer()->add($pagelet);
	}

	function accion__eliminar_actuacion_docente()
	{
		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO);

		$id = $this->validate_param(1, 'get', validador::TIPO_ALPHANUM);
		kernel::persona()->datos()->eliminar_actuacion_docente($id);

		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);
	}

	function get_actuacion_docente()
	{
		if (isset($this->seleccion)) {
			$datos = kernel::persona()->datos()->get_datos_actuacion_docente();
			$datos_act = $datos[$this->seleccion];
		} else {
			$datos_act = array();
		}
		return $datos_act;
	}

	//--------------------------------------------------------------------------
	//---- Turno
	//--------------------------------------------------------------------------

	function accion__devolver_turno()
	{
		$ra = $this->decodificar_ra_turno();
		kernel::persona()->datos()->devolver_turno($ra);

		$pagelet = $this->vista()->pagelet('turno_presentacion');
		$this->vista()->set_form_activo($pagelet);
	}

	function accion__seleccionar_turno()
	{
		$pagelet = $this->vista()->pagelet('turno_presentacion');
		$this->vista()->set_form_activo($pagelet);
		$pagelet->set_pantalla(pagelet_con_edicion::ESTADO_EDICION);
	}

	function accion__volver_seleccionar_turno()
	{
		$pagelet = $this->vista()->pagelet('turno_presentacion');
		$this->vista()->set_form_activo($pagelet);
		$pagelet->set_pantalla(pagelet_con_edicion::ESTADO_LISTADO);
	}

	function get_fecha_minima_turno()
	{
		$ra = $this->decodificar_ra_turno();
		return g3::turnos()->get_fecha_minima($ra);
	}

	function get_fecha_maxima_turno()
	{
		$ra = $this->decodificar_ra_turno();
		return g3::turnos()->get_fecha_maxima($ra);
	}

	function get_fechas_turnos_disponibles()
	{
		$ra = $this->decodificar_ra_turno();
		return g3::turnos()->get_fechas_turnos_disponibles($ra);
	}

	function accion__cascada_franjas_turnos()
	{
		$fecha = $this->get_param('fecha', 'post');
		$ra = $this->decodificar_ra_turno($this->get_param('ra', 'post'));
		$datos = g3::turnos()->get_franjas_horarias_disponibles($fecha, $ra);
		$this->render_raw_json($datos);
	}

	function accion__cascada_tipo_documento()
	{
		$pais = $this->get_param('pais', 'post');
		$condicion = "mdp_tipo_documento_pais.pais = '" . $pais . "'";
		$entidad_pais_tipo_doc = g3::entidad('tipos_documentos_pais')->get_opciones_pais($condicion, true);
		$this->render_raw_json($entidad_pais_tipo_doc);
	}

	function accion__cascada_nacionalidad()
	{
		$nacionalidad = $this->get_param('nacionalidad', 'post');
		if ($nacionalidad == '2') {
			$datos = util::opciones()->get_paises();
		} else {
			$datos = array('54' => 'Argentina');
		}
		$this->render_raw_json($datos);
	}

	function accion__datos_tipo_documento_validar()
	{
		$tipo_documento = $this->get_param('tipo_documento', 'post');
		$pais = $this->get_param('pais', 'post');
		$nro_documento = $this->get_param('nro_documento', 'post');

		$entidad_tipo_doc = g3::entidad('tipos_documentos_pais')->get_entidad($pais . "_" . $tipo_documento);

		$entidad_tipo_doc['mensaje_validacion_cuil'] = '';
		// Si el tipo de documento es CUIL/CUIT y falla la validaci�n 
		if (($tipo_documento == comunes::TIPO_DOCUMENTO_CUIL_CUIT) && (!comunes::validarCUIT($nro_documento))) {
			$entidad_tipo_doc['mensaje_validacion_cuil'] = kernel::traductor()->trans('nro_documento.cuil.error');
		}

		$this->render_raw_json($entidad_tipo_doc);
	}

	function get_ra_turno()
	{
		$ra = $this->validate_param(0, 'get', validador::TIPO_ALPHANUM);
		return $ra;
	}

	protected function decodificar_ra_turno($ra = null)
	{
		if (is_null($ra)) {
			$ra = $this->validate_param(0, 'get', validador::TIPO_ALPHANUM);
		}
		$datos = g3::responsables_academicas()->get_opciones();
		foreach ($datos as $key => $dato) {
			if ($ra == util::catalogo()->generar_id($key)) {
				return $key;
			}
		}
		return null;
	}

	function accion__guardar_turno()
	{
		if (!kernel::request()->isPost()) {
			return;
		}

		$formulario = 'turno_presentacion';
		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);
		$form = $pagelet->get_form();

		if ($form->procesar()) {
			$ra = $this->decodificar_ra_turno();
			$datos = $form->get_datos();
			try {
				kernel::persona()->datos()->set_turno($ra, $datos);
				// Invalido cache de turno de presentaci�n
				kernel::persona()->datos()->invalidar_cache_turno_presentacion($ra);
				$pagelet->set_estado_info(pagelet_datos_principales::FORM_GUARDADO);
			} catch (error_preinscripcion $e) {
				$pagelet->set_mensaje_error($e->get_mensaje());
				$pagelet->set_estado_info(pagelet_datos_principales::FORM_ERROR_VALIDACION);
			}
		} else {
			$pagelet->set_estado_info(pagelet_datos_principales::FORM_ERROR_VALIDACION);
		}

		kernel::renderer()->add($pagelet);
	}

	//--------------------------------------------------------------------------
	//---- Actuaci�n Profesional
	//--------------------------------------------------------------------------

	function accion__guardar_actuacion_profesional()
	{
		if (!kernel::request()->isPost()) {
			return;
		}

		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO, array('default' => 'formacion_acad'));
		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);
		$form = $pagelet->get_form();

		if ($form->procesar()) {
			$datos = $form->get_datos();
			kernel::persona()->datos()->set_actuacion_profesional($datos);
			$pagelet->set_estado_info(pagelet_datos_principales::FORM_GUARDADO);
			$pagelet->set_pantalla(pagelet_con_edicion::ESTADO_LISTADO);
		} else {
			$pagelet->set_estado_info(pagelet_datos_principales::FORM_ERROR_VALIDACION);
			$pagelet->set_pantalla(pagelet_con_edicion::ESTADO_EDICION);
		}

		kernel::renderer()->add($pagelet);
	}

	function accion__eliminar_actuacion_profesional()
	{
		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO);

		$id = $this->validate_param(1, 'get', validador::TIPO_ALPHANUM);
		kernel::persona()->datos()->eliminar_actuacion_profesional($id);

		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);
	}

	function get_actuacion_profesional()
	{
		if (isset($this->seleccion)) {
			$datos = kernel::persona()->datos()->get_datos_actuacion_profesional();
			$datos_act = $datos[$this->seleccion];
		} else {
			$datos_act = array();
		}
		return $datos_act;
	}

	//--------------------------------------------------------------------------
	//---- Formaci�n acad�mica
	//--------------------------------------------------------------------------

	function accion__guardar_formacion_acad()
	{
		if (!kernel::request()->isPost()) {
			return;
		}

		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO, array('default' => 'formacion_acad'));
		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);
		$form = $pagelet->get_form();

		if ($form->procesar()) {
			$datos = $form->get_datos();
			kernel::persona()->datos()->set_formacion_acad($datos);
			$pagelet->set_estado_info(pagelet_datos_principales::FORM_GUARDADO);
			$pagelet->set_pantalla(pagelet_con_edicion::ESTADO_LISTADO);
		} else {
			$pagelet->set_estado_info(pagelet_datos_principales::FORM_ERROR_VALIDACION);
			$pagelet->set_pantalla(pagelet_con_edicion::ESTADO_EDICION);
		}

		kernel::renderer()->add($pagelet);
	}

	function accion__eliminar_formacion_acad()
	{
		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO);

		$id = $this->validate_param(1, 'get', validador::TIPO_ALPHANUM);
		kernel::persona()->datos()->eliminar_formacion_acad($id);

		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);

		kernel::renderer()->add($pagelet);
	}

	function get_formacion_acad()
	{
		if (isset($this->seleccion)) {
			$datos = kernel::persona()->datos()->get_datos_formacion_acad();
			$datos_estudios = $datos[$this->seleccion];
		} else {
			$datos_estudios = array();
		}
		return $datos_estudios;
	}

	//--------------------------------------------------------------------------
	//---- CASCADAS: Selector de propuestas ------------------------------------
	//--------------------------------------------------------------------------

	function accion__cascada_propuestas()
	{
		$ra = $this->get_param('ra', 'post');
		$inscripciones = kernel::persona()->datos()->get_datos_inscripciones();
		if (!empty($inscripciones)) {
			$excluir = aplanar_matriz($inscripciones, 'propuesta');
		} else {
			$excluir = array();
		}
		$datos = g3::propuestas()->get_propuestas_disponibles_preinscripcion($ra, $excluir);
		$this->render_raw_json($datos);
	}

	function accion__cascada_ubicaciones()
	{
		$propuesta = $this->get_param('propuesta', 'post');
		$datos = g3::propuestas()->get_ubicaciones_propuesta($propuesta);
		$this->render_raw_json($datos);
	}

	function accion__cascada_modalidades()
	{
		$propuesta = $this->get_param('propuesta', 'post');
		$ubicacion = $this->get_param('ubicacion', 'post');
		$datos = g3::propuestas()->get_modalidades_propuesta($propuesta, $ubicacion);
		$this->render_raw_json($datos);
	}

	//--------------------------------------------------------------------------
	//---- CASCADAS: Selector de localidades -----------------------------------
	//--------------------------------------------------------------------------

	function accion__get_descr_localidad()
	{
		$localidad = $this->get_param('localidad', 'post');
		$this->render_raw_json(array('localidad_descr' => g3::localidades()->get_nombre($localidad)));
	}

	function accion__lista_localidades()
	{
		$criterio = utf8_decode($this->get_param('term', 'get'));
		$pais = utf8_decode($this->get_param('pais', 'get'));
		if (empty($criterio)) {
			return;
		}
		if (empty($pais)) {
			$pais = null;
		}
		$datos = g3::localidades()->get_lista_localidades($criterio, $pais);
		$this->render_autocomplete($datos, 'localidad', 'localidad_descr');
	}

	function render_autocomplete($datos, $campo_valor, $campo_descr)
	{
		$paquete = [];
		foreach ($datos as $key => $dato) {
			$valor = $dato[$campo_valor];
			$descripcion = formatear($dato[$campo_descr]);
			$paquete[] = ["label" => $descripcion, "value" => [$campo_valor => $valor, $campo_descr => $descripcion]];
		}
		echo kernel::renderer()->json_encode($paquete);
		throw new FinalizadorRequest();
	}

	protected function to_autocomplete($values)
	{
		$lista_autocomplete = array();
		foreach ($values as $value) {
			$lista_autocomplete[] = array(
				'localidad' => $value['localidad'],
				'localidad_descr' => $value['localidad_descr'],
				'label' => '[' . $value['localidad_descr'] . '] ',
				'value' => '[' . $value['localidad_descr'] . '] '
			);
		}
		return $lista_autocomplete;
	}

	function accion__cascada_provincias()
	{
		$pais = $this->get_param('pais', 'post');
		$datos = util::opciones()->get_provincias($pais);
		$this->render_raw_json($datos);
	}

	function accion__cascada_departamentos()
	{
		$provincia = $this->get_param('provincia', 'post');
		$datos = util::opciones()->get_departamentos($provincia);
		$this->render_raw_json($datos);
	}

	function accion__cascada_localidades()
	{
		$departamento = $this->get_param('departamento', 'post');
		$datos = util::opciones()->get_localidades($departamento);
		$this->render_raw_json($datos);
	}

	function accion__cascada_cod_postales()
	{
		$localidad = $this->get_param('localidad', 'post');
		$datos = util::opciones()->get_cod_postales($localidad);
		$this->render_raw_json($datos);
	}

	function accion__cp_unico()
	{
		$localidad = $this->get_param('localidad', 'post');
		$datos = util::opciones()->get_cod_postales($localidad);
		if (count($datos) == 1) {
			$this->render_raw_json(array('cp' => current($datos)));
		} else {
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//---- Selector de colegio secundario --------------------------------------
	//--------------------------------------------------------------------------

	function accion__buscar_colegios()
	{
		$colegio_nombre = $this->get_param('colegio', 'post');
		$localidad_nombre = $this->get_param('localidad', 'post');

		$datos = g3::colegios()->get_colegios($colegio_nombre, $localidad_nombre);
		if (empty($datos)) {
			$html = '<div class="alert alert-error mensaje strong">';
			$html .= kernel::traductor()->trans('busqueda_sin_resultados');
			$html .= '</div>';
		} else {
			$html = $this->armar_tabla_resultado_colegios($datos);
		}

		$this->render_raw_json(array('html' => $html));
	}

	protected function armar_tabla_resultado_colegios($datos)
	{
		$html = '<table id="tabla_resultado_colegios" class="table table-stripped table-hover">
						<thead>
							<tr>
								<th sort="colSecundario">' . kernel::traductor()->trans('colegio_secundario') . '</th>
								<th sort="localidad">' . kernel::traductor()->trans('localidad') . '</th>
								<th>&nbsp;</th>
							</tr>
						</thead>
						<tbody>';
		foreach ($datos as $dato) {
			$html .= '		<tr>
								<td>' . $dato['nombre'] . '</td>
								<td>' . $dato['localidad_nombre'] . '</td>
								<td>
									<button class="btn btn-info btn-small pull-right" title="' . kernel::traductor()->trans('seleccionar') . '" onclick="seleccionar_colegio(\'' . $dato['colegio'] . '\')">
										<i class="icon-white icon-ok"></i> 
									</button>
								</td>
							</tr>';
		}
		$html .= '<tfoot class="nav">
					<tr>
						<td colspan=3>
							<div class="paginationTitle"></div>
							<div class="pagination"></div>
							<div class="status"></div>
						</td>
					</tr>
					</tfoot>';
		$html .= '</table>';

		return $html;
	}

	function accion__buscar_colegio_por_id()
	{
		$id_colegio = $this->get_param('id_colegio', 'post');
		$this->render_raw_json(array('colegio_descr' => g3::colegios()->get_nombre($id_colegio)));
	}

	//--------------------------------------------------------------------------
	//---- Selector de t�tulo secundario ---------------------------------------
	//--------------------------------------------------------------------------

	function accion__buscar_titulo_sec()
	{
		$titulo_nombre = $this->get_param('titulo', 'post');
		$datos = util::opciones()->get_titulos_sec_por_nombre($titulo_nombre);
		if (empty($datos)) {
			$html = '<div class="alert alert-error mensaje strong">';
			$html .= kernel::traductor()->trans('busqueda_sin_resultados');
			$html .= '</div>';
		} else {
			$html = $this->armar_tabla_resultado_titulos_sec($datos);
		}

		$this->render_raw_json(array('html' => $html));
	}

	protected function armar_tabla_resultado_titulos_sec($datos)
	{
		$html = '<table id="tabla_resultado_titulos" class="table table-stripped table-hover">
						<thead>
							<tr>
								<th sort="tituloSec">' . kernel::traductor()->trans('titulo_secundario') . '</th>
								<th>&nbsp;</th>
							</tr>
						</thead>
						<tbody>';
		foreach ($datos as $dato) {
			$html .= '		<tr>
								<td>' . $dato['nombre'] . '</td>
								<td>
									<button class="btn btn-info btn-small pull-right" title="' . kernel::traductor()->trans('seleccionar') . '" onclick="seleccionar_titulo_sec(\'' . $dato['titulo'] . '\')">
										<i class="icon-white icon-ok"></i> 
									</button>
								</td>
							</tr>';
		}
		$html .= '<tfoot class="nav">
					<tr>
						<td colspan=3>
							<div class="paginationTitle"></div>
							<div class="pagination"></div>
							<div class="status"></div>
						</td>
					</tr>
					</tfoot>';
		$html .= '</table>';

		return $html;
	}

	function accion__buscar_titulo_por_id()
	{
		$id_titulo = $this->get_param('id_titulo', 'post');
		$this->render_raw_json(array('titulo_descr' => g3::titulos_secundario()->get_nombre($id_titulo)));
		//		$this->render_raw_json(array('titulo_descr' => g3::colegios()->get_nombre($id_titulo)));
	}

	//--------------------------------------------------------------------------
	//---- RDI - Documentaci�n -------------------------------------------------
	//--------------------------------------------------------------------------

	function accion__guardar_documentacion()
	{
		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO);
		$clave_encriptada = $this->validate_param(1, 'get', validador::TIPO_TEXTO);

		$pagelet = $this->vista()->pagelet($formulario);

		$requisitos_a_presentar = kernel::persona()->datos()->get_requisitos_a_presentar();
		$requisito = $requisitos_a_presentar[$clave_encriptada];

		$temp_path = kernel::proyecto()->get_www_temp();
		$indice_form = 'archivo';

		if ($_FILES['archivo']['error'] == UPLOAD_ERR_INI_SIZE || $_FILES['archivo']['error'] == UPLOAD_ERR_FORM_SIZE) {
			kernel::sesion()->set('error_tamanio', true);
		}

		try {
			$nombre = $this->guardar_archivos_subidos($indice_form, $temp_path);

			if (!empty($nombre)) {
				$path_file = $temp_path . '/' . $nombre['nombre_fs'];
				$tipo_documento_digital = $requisito['tipo_documento_digital'];

				$requisitos_aspirante = kernel::persona()->datos()->get_requisitos_aspirante();
				if (empty($requisitos_aspirante[$clave_encriptada]['id_documento'])) {
					// Alta de recurso
					$id_recurso = $this->enviar_repositorio($path_file, $tipo_documento_digital);
					$datos = array(
						'requisito' => $requisito['requisito'],
						'id_documento' => $id_recurso
					);
					kernel::persona()->datos()->set_requisito($datos);
				} else {
					// Actualizaci�n de recurso
					$id_recurso = $requisitos_aspirante[$clave_encriptada]['id_documento'];
					$this->enviar_repositorio($path_file, $tipo_documento_digital, $id_recurso);
				}

				// Se elimina el recurso temporal
				unlink($path_file);
			}
		} catch (ErrorKernel $e) {
			kernel::log()->add_error($e);
		} catch (RDIExcepcion $e) {
			kernel::log()->add_error($e);
			$pagelet->set_mensaje_error(kernel::traductor()->trans('doc.msj_error_rdi'));
		}

		$this->vista()->set_form_activo($pagelet);

		kernel::renderer()->add($pagelet);
	}

	function accion__modificar_documentacion()
	{
		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO);
		$clave_encriptada = $this->validate_param(1, 'get', validador::TIPO_TEXTO);

		$requisitos_aspirante = kernel::persona()->datos()->get_requisitos_aspirante();

		if (!empty($requisitos_aspirante[$clave_encriptada]['id_documento'])) {
			$requisito = $requisitos_aspirante[$clave_encriptada]['requisito'];
			kernel::sesion()->set('requisito_' . $requisito, 1);
		}

		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);

		kernel::renderer()->add($pagelet);
	}

	function accion__descargar_documentacion()
	{
		try {
			$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO);
			$clave_encriptada = $this->validate_param(1, 'get', validador::TIPO_TEXTO);

			$pagelet = $this->vista()->pagelet($formulario);

			$requisitos_aspirante = kernel::persona()->datos()->get_requisitos_aspirante();
			$requisito = $requisitos_aspirante[$clave_encriptada];

			$srv = kernel::proyecto()->rdi()->servicio(g3::requisitos()->tipo_documento_digital($requisito['requisito']));
			$attr = $srv->recuperarAtributos($requisito['id_documento']);
			$content = $srv->recuperarContenido($requisito['id_documento']);

			$nombre_descarga = $attr['tipoIdentificacion'] . $attr['numeroIdentificacion'] . $attr['id'];
			$archivo_temp = md5($nombre_descarga);
			$nombre = kernel::proyecto()->get_www_temp() . '/' . $archivo_temp;
			file_put_contents($nombre, $content);

			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mime_type = finfo_file($finfo, $nombre);
			finfo_close($finfo);
			$ext = ManejadorArchivos::mime2ext($mime_type);
			$newname = "{$nombre}.{$ext}";
			rename($nombre, $newname);

			$this->descargar_archivo($newname);

			unlink($newname);
		} catch (RDIExcepcion $e) {
			kernel::log()->add_error($e);
			$pagelet->set_mensaje_error(kernel::traductor()->trans('doc.msj_error_rdi'));
		}
	}

	function accion__eliminar_documentacion()
	{
		try {
			$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO);
			$clave_encriptada = $this->validate_param(1, 'get', validador::TIPO_TEXTO);
			$requisitos_aspirante = kernel::persona()->datos()->get_requisitos_aspirante();

			$pagelet = $this->vista()->pagelet($formulario);

			if (!empty($requisitos_aspirante[$clave_encriptada]['id_documento'])) {
				$id_recurso = $requisitos_aspirante[$clave_encriptada]['id_documento'];

				$tipo_documento_digital = g3::requisitos()->tipo_documento_digital($requisitos_aspirante[$clave_encriptada]['requisito']);
				$this->eliminar_repositorio($id_recurso, $tipo_documento_digital);

				$requisito = $requisitos_aspirante[$clave_encriptada]['requisito'];
				$datos = array('id_documento' => $id_recurso, 'requisito' => $requisito);

				kernel::persona()->datos()->eliminar_requisito($datos);
			}
		} catch (RDIExcepcion $e) {
			kernel::log()->add_error($e);
			$pagelet->set_mensaje_error(kernel::traductor()->trans('doc.msj_error_rdi'));
		}

		$this->vista()->set_form_activo($pagelet);

		kernel::renderer()->add($pagelet);
	}

	protected function enviar_repositorio($path, $tipo_documento_digital, $id_recurso = null)
	{
		if (file_exists($path)) {
			// Validaci�n de extensi�n permitida.
			$extension_partes = explode(".", $path);
			$cant_partes = count($extension_partes);
			$extension = a_minusculas($extension_partes[$cant_partes - 1]);
			$extensiones_validas = aplanar_matriz(g3::entidad('extensiones')->get_extensiones_permitidas(), 'extension');
			if (!in_array($extension, $extensiones_validas)) {
				kernel::sesion()->set('error_extension', true);
				unlink($path);
				return;
			}

			$srv = kernel::proyecto()->rdi()->servicio($tipo_documento_digital);

			$datos_archivo = getimagesize($path);
			$datos_persona = kernel::persona()->datos()->get_datos_preinscripcion();
			$attr = $this->get_atributos_documento_digital_requisito_documental($tipo_documento_digital, $datos_persona, $datos_archivo);

			$cont = file_get_contents($path);
			if (is_null($id_recurso)) {
				// Se crea un nuevo recurso en el RDI y se retorna el ID
				$ecm_resource_id = $srv->crear($attr, $cont, mime_content_type($path));
				return $ecm_resource_id;
			} else {
				// Se actualiza el contenido
				$srv->modificarContenido($id_recurso, $cont);
			}
		} else {
			return null;
		}
	}

	protected function eliminar_repositorio($id_recurso, $tipo_documento_digital)
	{
		$srv = kernel::proyecto()->rdi()->servicio($tipo_documento_digital);
		$srv->eliminar($id_recurso);
	}

	protected function get_atributos_documento_digital_requisito_documental($tipo_documento_digital, $datos_persona, $datos_archivo)
	{
		$atributos = [];

		// Atributos de los recursos personales
		$atributos['tipoIdentificacion'] = $datos_persona['tipo_documento'];
		$atributos['numeroIdentificacion'] = $datos_persona['nro_documento'];
		if (isset($datos_persona['apellido'])) {
			$atributos['apellido'] = $datos_persona['apellido'];
		}
		if (isset($datos_persona['nombres'])) {
			$atributos['nombres'] = $datos_persona['nombres'];
		}
		switch ($tipo_documento_digital) {
			// Si es de tipo Foto de la persona (tipo 'RecursoPersonalFoto' en NUXEO)
			case \RDITipos::FOTO:
				if (isset($datos_archivo[0])) {
					$atributos['ancho'] = $datos_archivo[0];
				}
				if (isset($datos_archivo[0])) {
					$atributos['alto'] = $datos_archivo[1];
				}
				break;
			// Si es de tipo Gen�rico (tipo 'Recurso' en NUXEO)
			case \RDITipos::RECURSO:
				// Elimino dichos atributos ya que no los necesita, sino da el siguiente error:
				// Parametros invalidos: tipoIdentificacion,numeroIdentificacion,apellido,nombres
				unset($atributos['tipoIdentificacion']);
				unset($atributos['numeroIdentificacion']);
				unset($atributos['apellido']);
				unset($atributos['nombres']);
				break;
		}
		return $atributos;
	}
}
