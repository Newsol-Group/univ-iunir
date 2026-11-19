<?php

namespace siu\modelo\transacciones;

use siu\modelo\datos\catalogo;
use SIU\Chulupi\kernel;
use siu\modelo_g3\gestion_rest;
use siu\errores\error_guarani_usuario;
use SIU\Chulupi\util\ManejadorArchivos;
use siu\modelo\_comun\clienteRDI;
use siu\modelo\transacciones\solicitar_certificacion;

class solicitar_certificacion extends transaccion_alumno
{
	
	const egr_pasar_a_pasivo = "egr_pasar_a_pasivo";

	const estado_pendiente_validacion = "pendiente_validacion";
	const estado_no_presentado = "no_presentado";
	const estado_presentado = "presentado";
	const estado_vencido = "vencido";

	protected $operacion = '800SIUEGR005';
	
	function info__certificados_tramitados()
	{
		$datos = catalogo::consultar('certificacion', 'get_certificaciones_tramitadas_por_alumno', [
			'alumno' => $this->alumno
		]);
		
		return $datos;
	}

	function limpiar_cache_certificados_tramitados()
	{
		catalogo::limpiar_cache('certificacion', 'get_certificaciones_tramitadas_por_alumno', [
			'alumno' => $this->alumno
		]);
	}

	function info__cuil_persona()
	{
		$cuil = \persona::doc_CUIL;
		$datos = catalogo::consultar('persona', 'get_documentos_persona', array(
			'persona' => $this->persona,
			'tipo_documento' => $cuil
		));
		return current($datos);
	}
	
	function info__certificaciones_no_tramitadas()
	{
		
		$datos = catalogo::consultar('certificacion', 'get_certificaciones_no_tramitadas_por_alumno', array(
			'alumno' => $this->alumno,
			'plan_version' => $this->plan_version
		));
		
		return $datos;
	}
	
	function info__orientaciones()
	{
		
		$datos = catalogo::consultar('certificacion', 'get_orientaciones_plan_version', array(
			'plan_version' => $this->plan_version
		));
		
		return $datos;
	}
	
	function info__colaciones()
	{
		
		$datos = catalogo::consultar('certificacion', 'get_colaciones_por_propuesta', array(
			'propuesta' => $this->propuesta
		));
		
		return $datos;
	}
	
	function info__juramentos()
	{
		
		$datos = catalogo::consultar('certificacion', 'get_juramentos');
		
		return $datos;
	}
	
	function info__datos_contactos()
	{
		
		$datos = catalogo::consultar('datos_censales', 'get_datos_contactos', array(
			'persona' => $this->persona
		));
		
		return $datos;
	}
	
	function evt__confirmar_solicitud_titulo($cuil, $certificacion, $colacion, $juramento){
		
		try{
			kernel::db()->abrir_transaccion();

			$this->evt__guardar_cuil($cuil);
			
			$cumple_condiciones = false;
			// Que la inscripción a Propuesta esté en estado Aceptada.
			$propuesta_aceptada = \toba::consulta_php('co_alumnos')->inscripcion_propuesta_aceptada($this->alumno);
		
			if($propuesta_aceptada){
			// Si el alumno cumple las condiciones para obtener la certificación
			if($this->info__punto_de_control($this->alumno, $certificacion) != \guarani_punto_de_control::rechazado ){
				if($this->info__cumple_condiciones_certificacion($this->alumno, $certificacion)){
			
					$cumple_condiciones = true;
					$this->evt__guardar_solicitud($certificacion, $colacion, $juramento);

			}
			}
			}
			kernel::db()->cerrar_transaccion();
			return $cumple_condiciones;
		}
		catch (\Exception $e){
			kernel::db()->abortar_transaccion();
			kernel::log()->add_error($e);
			throw $e;
		}
	}
	
	function info__cumple_condiciones_certificacion($alumno, $certificado)
	{
		
		$resultado = gestion_rest::cumple_condiciones_certificacion($alumno, $certificado);
		$cumple_condiciones = $resultado['cumple_condiciones'];
		kernel::log()->add_debug("=== Cumple condiciones ===", $cumple_condiciones);
		return $cumple_condiciones;
		
	}

	function info__punto_de_control($alumno, $certificado){

		$puntos_de_control = [\guarani_punto_de_control::egreso];

		$parametros = [
			'fecha' => \guarani_fecha::get_hoy(),
			'alumno' => $alumno,
			'plan_version' => $this->plan_version,
			'certificado' => $certificado
		];

		$proceso = $this->nuevo_proceso_control($puntos_de_control, $parametros);
		$resultado_controles = $proceso->ejecutar();
		$mensajes_controles = $proceso->get_mensajes();

		if ($resultado_controles != \guarani_punto_de_control::autorizado) {

			$e = new error_guarani(kernel::traductor()->trans('error_ejecutando_controles'));
			$e->add_mensajes_usuario($mensajes_controles);
			throw $e;
		}
	}

	function evt__guardar_cuil($cuil){
		
		$cuil_persona = $this->info__cuil_persona();
		
		// Si la persona tiene CUIL
		if($cuil_persona){
			
			// Si el CUIL viene vacío es porque lo quiere borrar
			if(is_null($cuil)){
				
				catalogo::consultar('certificacion', 'borrar_cuil', array(
					'persona' => $this->persona
				));
				
			} // Si el CUIL cambio lo actualizo
			else if($cuil_persona['nro_documento'] != $cuil){
				catalogo::consultar('certificacion', 'actualizar_cuil', array(
					'persona' => $this->persona,
					'cuil' => $cuil
				));
			}
			
		}
		else{//si no tiene
			
			// Si ingresa un CUIL lo guardo
			if(!is_null($cuil)){
				catalogo::consultar('certificacion', 'insertar_cuil', array(
					'persona' => $this->persona,
					'cuil' => $cuil
				));
			} // sino salgo
			else{
				return true;
			}
			
		}
		
		// Limpio la cache
		$this->limpiar_cuil();
	}
	
	protected function limpiar_cuil(){
		$cuil = \persona::doc_CUIL;
		catalogo::limpiar_cache('persona', 'get_documentos_persona', array(
			'persona' => $this->persona,
			'tipo_documento' => $cuil
		));
	}

        // Ver como se hace en la función "generar_solicitud" de "cn_man_solicitar_certificaciones"
	function evt__guardar_solicitud($certificacion, $colacion = null, $juramento = null){
		
		$this->evt__pasar_alumno_pasivo($certificacion);
		
		$this->evt__baja_plan_personalizado();
		
		$nro_solicitud = $this->evt__registrar_solicitud($certificacion, $colacion, $juramento);
		kernel::log()->add_debug("=== Nro solicitud ===", $nro_solicitud);
		
		$this->evt__registrar_orientacion($nro_solicitud, $certificacion);
		
		$this->evt__congelar_historia_academica($nro_solicitud);

		$this->evt__insertar_requisitos_certificado($nro_solicitud);

		$this->evt__actualizar_estado_requisitos($nro_solicitud);
					
		// Limpio la cache
		$this->limpiar_solicitud();
		
	}
	
	protected function limpiar_solicitud(){
		catalogo::limpiar_cache('certificacion', 'get_certificaciones_no_tramitadas_por_alumno', [
			'alumno' => $this->alumno,
			'plan_version' => $this->plan_version
		]);
		
		catalogo::limpiar_cache('certificacion', 'get_certificaciones_tramitadas_por_alumno', [
			'alumno' => $this->alumno
		]);
		
		/**
		 * @see https://redmine.siu.edu.ar/redmine/issues/32865 Ticket #32865
		 */
		catalogo::limpiar_cache('certificacion', 'get_tramites_certificacion_by_persona_propuesta', [
			'persona' => $this->persona,
			'propuesta' => $this->propuesta
		]);
	}
	
	function evt__pasar_alumno_pasivo($certificacion){
		
		// Si el parámetro 'egr_pasar_a_pasivo' es SI ...
		if($this->se_debe_cambiar_calidad_alumno()){
			
			// ... y el título es final paso al alumno a pasivo
			if($this->es_titulo_final($certificacion)){
				
				// Se actualiza la calidad del alumno se inserta el registro histórico de cambio de calidad.
				\guarani::act('act_alumnos')->actualizar_calidad_alumno($this->alumno, \alumno::calidad_pasivo);
				\guarani::act('act_alumnos')->insertar_cambio_calidad($this->alumno, \alumno::calidad_pasivo, \alumno::motivo_calidad_egreso);
				
			}
			
		}
	}
	
	function evt__baja_plan_personalizado(){
		
		// Si el alumno al que se está dando de baja pertenece a un plan personalizado, también se debe dar de baja el plan
		if($this->es_plan_personalizado()){
			
			\guarani::act('act_planes')->baja_de_plan($this->plan);
			\guarani::act('act_planes')->baja_de_version($this->plan_version);
			
		}
	}
	
	function se_debe_cambiar_calidad_alumno(){
		
		$propuestas = array($this->propuesta);
        $egr_pasar_a_pasivo = \guarani::parametros()->get_valor(static::egr_pasar_a_pasivo, $propuestas, false);
		kernel::log()->add_debug("=== egr_pasar_a_pasivo ===", $egr_pasar_a_pasivo);
		return ($egr_pasar_a_pasivo == \comunes::si);
		
	}
	
	function es_titulo_final($certificacion){
		$datos_certificado = \toba::consulta_php('co_certificados')->get_certificado($certificacion);
		$titulo_final = false;
		
		if($datos_certificado['titulo_nivel'] == \tramite_certificacion::nivel_titulo_final){
			$titulo_final = true;
		}
		
		kernel::log()->add_debug("=== titulo final ===", $titulo_final);
		return $titulo_final;
	}
	
	function es_plan_personalizado(){
		$tipo_y_estado_plan_de_plan_version = \toba::consulta_php('co_planes')->get_tipo_y_estado_plan_de_plan_version($this->plan_version);
		$plan_personalizado = false;
		
		if($tipo_y_estado_plan_de_plan_version['tipo_plan'] == \planes::tipo_personalizado){
			$plan_personalizado = true;
		}
		
		kernel::log()->add_debug("=== plan personalizado ===", $plan_personalizado);
		return $plan_personalizado;
	}
	
	function evt__registrar_solicitud($certificacion, $colacion, $juramento){
		
		return catalogo::consultar('certificacion', 'registrar_solicitud', array(
					'persona' => $this->persona,
					'certificado' => $certificacion,
					'alumno' => $this->alumno,
					'plan_version' => $this->plan_version,
					'juramento' => $juramento,
					'colacion' => $colacion,
				));
		
	}
	
	function evt__congelar_historia_academica($nro_solicitud){
		
		// Se registra una "foto" de la historia académica del alumno.
		\guarani::act('act_tramites_certificacion')->registrar_historia_academica($this->alumno, $nro_solicitud);
		
	}
	
	function evt__registrar_orientacion($nro_solicitud, $certificado){
		
		// Se registran las orientaciones que el alumno cumplió.
		$orientaciones_cumplidas = $this->info__orientaciones_cumplidas($certificado);
		\guarani::act('act_tramites_certificacion')->registrar_orientaciones_cumplidas($nro_solicitud, $orientaciones_cumplidas);
		
	}

        // Ver como se hace en la función "insertar_requisitos_certificado" de "cn_man_solicitar_certificaciones"
        function evt__insertar_requisitos_certificado($nro_solicitud)
        {
            $requisitos = \toba::consulta_php('co_requisitos')->get_requisitos_egreso_persona_plan_version($this->persona, $this->plan_version);
            if (!empty($requisitos)) {
                foreach ($requisitos as $requisito) {
                    \guarani::act('act_requisitos_ingreso')->insertar_requisito_certificado($nro_solicitud, $requisito['requisito']);
                }
            }

	}

	// Ver como se hace en la función "actualizar_estado_requisitos" de "cn_man_solicitar_certificaciones"
	function evt__actualizar_estado_requisitos($nro_solicitud)
	{
            // Si la solicitud tiene requisitos de egreso
            if (\toba::consulta_php('co_tramites_certificacion')->solicitud_tiene_requisitos_egreso($nro_solicitud)) {

                // Si usa RDI entonces 'sga_certificados_otorg.estado_requisitos' va 'Abierto'
                if (kernel::proyecto()->usa_rdi()) {
                    $estado_requisitos = \tramite_certificacion::estado_requisitos_abierto;
                // Si no usa RDI entonces 'sga_certificados_otorg.estado_requisitos' va 'Pendiente'
                } else {
                    $estado_requisitos = \tramite_certificacion::estado_requisitos_pendiente;
                }

            // Si la solicitud no tiene requisitos de egreso entonces 'sga_certificados_otorg.estado_requisitos' va 'Aceptado'
            } else {
                $estado_requisitos = \tramite_certificacion::estado_requisitos_aceptado;
            }
            
            \guarani::act('act_tramites_certificacion')->actualizar_estado_requisitos($nro_solicitud, $estado_requisitos);
	}

	function info__orientaciones_cumplidas($certificado)
	{
		$orientaciones = \toba::consulta_php('co_certificados')->get_orientaciones_certificado($certificado, $this->plan_version);
		$orientaciones_cumplidas = array();
		foreach ($orientaciones as $orientacion) {
			$regla = \guarani::regla($orientacion['regla']);
			$regla->set_parametros($orientacion['parametros']);
			$regla->set_parametros_contexto(array('elemento' => $orientacion['elemento'], 'alumno' => $this->alumno, 'plan_version' => $this->plan_version));
			if ($regla->validar()) {
				$orientaciones_cumplidas[] = $orientacion['elemento'];
			}
		}
		return $orientaciones_cumplidas;
	}

	function get_requisitos_by_solicitud($nro_solicitud)
	{
		return catalogo::consultar('certificacion', 'get_requisitos_by_solicitud', [
			'nro_solicitud' => $nro_solicitud,
			'estado_req_digital' => null
		]);
	}

	function limpiar_cache_requisitos_by_solicitud($nro_solicitud)
	{
		catalogo::limpiar_cache('certificacion', 'get_requisitos_by_solicitud', [
			'nro_solicitud' => $nro_solicitud,
			'estado_req_digital' => null
		]);

		catalogo::limpiar_cache('certificacion', 'get_requisitos_by_solicitud', [
			'nro_solicitud' => $nro_solicitud,
			'estado_req_digital' => [solicitar_certificacion::estado_pendiente_validacion, solicitar_certificacion::estado_presentado]
		]);
	}

	function get_cant_requisitos_cargados_by_solicitud($nro_solicitud)
	{
		return \guarani_arreglos::count(catalogo::consultar('certificacion', 'get_requisitos_by_solicitud', [
			'nro_solicitud' => $nro_solicitud,
			'estado_req_digital' => [solicitar_certificacion::estado_pendiente_validacion, solicitar_certificacion::estado_presentado]
		]));
	}
	
	function evt__reabrir_solicitud($nro_solicitud)
	{
		$hubo_error = false;
		$msg_error = '';

		try {

			kernel::db()->abrir_transaccion();

			$certificados_tramitados = $this->info__certificados_tramitados();

			$certificado_tramitado = $certificados_tramitados[$nro_solicitud] ?? null;

			// Si el certificado no existe
			if (is_null($certificado_tramitado)) {
				throw new error_guarani_usuario(kernel::traductor()->trans('solicitar_certificacion.error_existe_certificado_tramitado'));
			}

			// Actualizo el 'estado_requisitos' de la solicitud a "Abierto" en la DB
			\guarani::act('act_tramites_certificacion')->actualizar_estado_requisitos($nro_solicitud, \tramite_certificacion::estado_requisitos_abierto);

			// Limpio cache
			$this->limpiar_cache_certificados_tramitados();

			kernel::db()->cerrar_transaccion();

		// Error a mostrar al usuario
		} catch (error_guarani_usuario $e) {
			kernel::log()->add_error($e);
			kernel::db()->abortar_transaccion();
			$hubo_error = true;
			$msg_error = $e->getMessage();
		// Error genérico
		} catch (\Exception $e) {
			kernel::log()->add_error($e);
			kernel::db()->abortar_transaccion();
			$hubo_error = true;
			$msg_error = kernel::traductor()->trans('requisitos.msj_error_generico');
		}

		return [$hubo_error, $msg_error];
	}
	
	function evt__finalizar_solicitud($nro_solicitud)
	{
		$hubo_error = false;
		$msg_error = '';

		try {

			kernel::db()->abrir_transaccion();

			$certificados_tramitados = $this->info__certificados_tramitados();

			$certificado_tramitado = $certificados_tramitados[$nro_solicitud] ?? null;

			// Si el certificado no existe
			if (is_null($certificado_tramitado)) {
				throw new error_guarani_usuario(kernel::traductor()->trans('solicitar_certificacion.error_existe_certificado_tramitado'));
			}

			// Actualizo el 'estado_requisitos' de la solicitud a "Pendiente" en la DB
			\guarani::act('act_tramites_certificacion')->actualizar_estado_requisitos($nro_solicitud, \tramite_certificacion::estado_requisitos_pendiente);

			// Limpio cache
			$this->limpiar_cache_certificados_tramitados();

			kernel::db()->cerrar_transaccion();
			
		// Error a mostrar al usuario
		} catch (error_guarani_usuario $e) {
			kernel::log()->add_error($e);
			kernel::db()->abortar_transaccion();
			$hubo_error = true;
			$msg_error = $e->getMessage();
		// Error genérico
		} catch (\Exception $e) {
			kernel::log()->add_error($e);
			kernel::db()->abortar_transaccion();
			$hubo_error = true;
			$msg_error = kernel::traductor()->trans('requisitos.msj_error_generico');
		}

		return [$hubo_error, $msg_error];
	}

	function evt__descargar_requisito_egreso($nro_solicitud, $requisito_id)
	{

		$hubo_error = false;
		$msg_error = '';
		$path = '';
		$nombre_descarga = '';

		try {

			$certificados_tramitados = $this->info__certificados_tramitados();
			$certificado_tramitado = $certificados_tramitados[$nro_solicitud] ?? null;
			// Si el certificado no existe
			if (is_null($certificado_tramitado)) {
				throw new error_guarani_usuario(kernel::traductor()->trans('solicitar_certificacion.error_existe_certificado_tramitado'));
			}

			$requisitos = $this->get_requisitos_by_solicitud($nro_solicitud);
			$requisito = $requisitos[$requisito_id] ?? null;
			// Si el requisito no existe
			if (is_null($requisito)) {
				throw new \Exception(kernel::traductor()->trans('requisitos.error_existe_requisto'));
			}

			// Si el requisito no tiene documento en el RDI
			if (is_null($requisito['id_documento'])) {
				throw new error_guarani_usuario(kernel::traductor()->trans('requisitos.error_requisito_sin_documento'));
			}

			// Descargo el documento desde RDI
			$srv = kernel::proyecto()->rdi()->servicio($requisito['tipo_documento_digital']);
			$content = $srv->recuperarContenido($requisito['id_documento']);

			// Path de descarga
			$path = kernel::proyecto()->get_www_temp() . '/' . $requisito['id_documento'];
			file_put_contents($path, $content);

			// Nombre de descarga
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mime_type = finfo_file($finfo, $path);
			finfo_close($finfo);
			$ext = ManejadorArchivos::mime2ext($mime_type);
			$nombre_descarga = "{$requisito['requisito_nombre']}.{$ext}";

		// Error RDI
		} catch (\RDIExcepcion $e) {
			kernel::log()->add_error($e);
			kernel::db()->abortar_transaccion();
			$hubo_error = true;
			$msg_error = kernel::traductor()->trans('requisitos.msj_error_rdi');
		// Error a mostrar al usuario
		} catch (error_guarani_usuario $e) {
			kernel::log()->add_error($e);
			kernel::db()->abortar_transaccion();
			$hubo_error = true;
			$msg_error = $e->getMessage();
		// Error genérico
		} catch (\Exception $e) {
			kernel::log()->add_error($e);
			kernel::db()->abortar_transaccion();
			$hubo_error = true;
			$msg_error = kernel::traductor()->trans('requisitos.msj_error_generico');
		}

		return [$hubo_error, $msg_error, $path, $nombre_descarga];
	}

	function evt__eliminar_requisito_egreso($nro_solicitud, $requisito_id)
	{

		$hubo_error = false;
		$msg_error = '';

		try {

			kernel::db()->abrir_transaccion();

			$certificados_tramitados = $this->info__certificados_tramitados();
			$certificado_tramitado = $certificados_tramitados[$nro_solicitud] ?? null;
			// Si el certificado no existe
			if (is_null($certificado_tramitado)) {
				throw new error_guarani_usuario(kernel::traductor()->trans('solicitar_certificacion.error_existe_certificado_tramitado'));
			}

			$requisitos = $this->get_requisitos_by_solicitud($nro_solicitud);
			$requisito = $requisitos[$requisito_id] ?? null;
			// Si el requisito no existe
			if (is_null($requisito)) {
				throw new \Exception(kernel::traductor()->trans('requisitos.error_existe_requisto'));
			}

			// Si el requisito no tiene documento en el RDI
			if (is_null($requisito['id_documento'])) {
				throw new error_guarani_usuario(kernel::traductor()->trans('requisitos.error_requisito_sin_documento'));
			}

			// Eliminar registro en BD
			$parametros = [
				'persona' => $this->persona,
				'requisito' => $requisito['requisito'],
				'id_documento' => $requisito['id_documento']
			];
			catalogo::consultar('alumno', 'eliminar_requisito_draft', $parametros);
			unset($parametros['id_documento']);
			// Elimino las observaciones del draft
			catalogo::consultar('alumno', 'eliminar_sga_requisitos_digitales_draft_detalle', $parametros);

			// Elimino documento del RDI
			clienteRDI::eliminar_repositorio($requisito['tipo_documento_digital'], $requisito['id_documento']);

			// Limpio cache
			$this->limpiar_cache_requisitos_by_solicitud($nro_solicitud);

			kernel::db()->cerrar_transaccion();

		// Error RDI
		} catch (\RDIExcepcion $e) {
			kernel::log()->add_error($e);
			kernel::db()->abortar_transaccion();
			$hubo_error = true;
			$msg_error = kernel::traductor()->trans('requisitos.msj_error_rdi');
		// Error a mostrar al usuario
		} catch (error_guarani_usuario $e) {
			kernel::log()->add_error($e);
			kernel::db()->abortar_transaccion();
			$hubo_error = true;
			$msg_error = $e->getMessage();
		// Error genérico
		} catch (\Exception $e) {
			kernel::log()->add_error($e);
			kernel::db()->abortar_transaccion();
			$hubo_error = true;
			$msg_error = kernel::traductor()->trans('requisitos.msj_error_generico');
		}

		return [$hubo_error, $msg_error];
	}
	
	function evt__subir_requisito_egreso($nro_solicitud, $requisito_id, $path_archivo_subido, $vencido = false)
	{

		$hubo_error = false;
		$msg_error = '';

		try {

			kernel::db()->abrir_transaccion();

			// Si el archivo no existe
			if (!file_exists($path_archivo_subido)) {
			   throw new error_guarani_usuario(kernel::traductor()->trans('upload_err_generico'));
			}

			$certificados_tramitados = $this->info__certificados_tramitados();
			$certificado_tramitado = $certificados_tramitados[$nro_solicitud] ?? null;
			// Si el certificado no existe
			if (is_null($certificado_tramitado)) {
				throw new error_guarani_usuario(kernel::traductor()->trans('solicitar_certificacion.error_existe_certificado_tramitado'));
			}

			$requisitos = $this->get_requisitos_by_solicitud($nro_solicitud);
			$requisito = $requisitos[$requisito_id] ?? null;
			// Si el requisito no existe
			if (is_null($requisito)) {
				throw new \Exception(kernel::traductor()->trans('requisitos.error_existe_requisto'));
			}

			// Si el requisito está vencido se carga como "-- Pendiente de validación --"
			if ($vencido) {
				$requisito['id_documento'] = null;
			}

			// Crea o modifica el recurso en el RDI
			$id_recurso = clienteRDI::enviar_repositorio($path_archivo_subido, $requisito['tipo_documento_digital'], $requisito['id_documento']);

			// Si se crea nuevo guardo en la DB
			if (is_null($requisito['id_documento'])) {
				$parametros = [
					'persona' => $this->persona,
					'requisito' => $requisito['requisito'],
					'id_documento' => $id_recurso
				];
				catalogo::consultar('alumno', 'guardar_requisito_draft', $parametros);
			} else {
				$parametros = [
					'claves' => ['requisito_digital' => $requisito['requisito_digital']],
					'datos' => ['fecha_carga' => 'CURRENT_TIMESTAMP']
				];

				// Pongo 'fecha_carga' a la fecha actual
				catalogo::consultar('alumno', 'actualizar_requisito_draft', $parametros);
			}

			$parametros = [
				'persona' => $this->persona,
				'requisito' => $requisito['requisito']
			];
			// Elimino las observaciones del draft
			catalogo::consultar('alumno', 'eliminar_sga_requisitos_digitales_draft_detalle', $parametros);

			// Limpio cache
			$this->limpiar_cache_requisitos_by_solicitud($nro_solicitud);

			kernel::db()->cerrar_transaccion();

		// Error RDI
		} catch (\RDIExcepcion $e) {
			kernel::log()->add_error($e);
			kernel::db()->abortar_transaccion();
			$hubo_error = true;
			$msg_error = kernel::traductor()->trans('requisitos.msj_error_rdi');
		// Error a mostrar al usuario
		} catch (error_guarani_usuario $e) {
			kernel::log()->add_error($e);
			kernel::db()->abortar_transaccion();
			$hubo_error = true;
			$msg_error = $e->getMessage();
		// Error genérico
		} catch (\Exception $e) {
			kernel::log()->add_error($e);
			kernel::db()->abortar_transaccion();
			$hubo_error = true;
			$msg_error = kernel::traductor()->trans('requisitos.msj_error_generico');
		// Entra ya sea que salga todo bien o falle
		} finally {
			// Elimino el archivo temporal
			if (file_exists($path_archivo_subido)) {
				unlink($path_archivo_subido);
			}
		}

		return [$hubo_error, $msg_error];
	}

}
