<?php
/**
 * 
 * @package Sincronizar Novedades con SQ
 */
class cn_man_sincronizar_novedades_sq extends cn_manejador_entidad
{

	function get_listado_propuestas_sincronizar($filtro)
	{
		return toba::consulta_php('co_cobros')->get_listado_propuestas_sincronizar($filtro);
	}	
	
	/**
     * @see https://repositorio.siu.edu.ar/trac/guarani/wiki/Casos_de_Uso/Sincronizar_SQ Caso de Uso.
     */
	function sincronizar_propuesta_cobrable($seleccion)
	{
		$novedad = toba::consulta_php('co_cobros')->get_propuesta_sincronizar($seleccion);
		
		toba::logger()->debug("Sincronizando plan '$seleccion':");
		toba::logger()->var_dump($novedad);
		
		try{
			
			toba::db()->abrir_transaccion();
			
			// Marco el plan como sincronizado dentro de una transacción, si falla la llamada a SQ aborto y deshago la marca
			guarani::act('act_planes')->cambiar_sincronizacion_plan_sq($seleccion, true);
			
			toba::db()->cerrar_transaccion();
			
			// Si el plan es cobrable
			if($novedad['plan_cobrable'] == comunes::si){

				$tipo_propuesta = ($novedad['propuesta_tipo'] == propuesta::tipo_posgrado) ? cobro::TIPO_POSGRADO : cobro::TIPO_GRADO;
				
				$body = array(
					'id'			=> $novedad['plan_id'],
					'codigo'		=> $novedad['plan_codigo'],
					'descripcion'	=> $novedad['propuesta_nombre'],
					'tipo'			=> $tipo_propuesta,
					'estado'		=> cobro::ESTADO_SERVICIO_ACTIVO
				);
				
				$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras_descripcion($novedad['propuesta_id']);
				// Si existen Responsables Académicas para la propuesta las paso como 'contextos'
				if (!empty($ras)) {

					$contextos = array_map(function($ra) {
						return [
							"codigo"		=> $ra["responsable_academica"],
							"descripcion"	=> $ra["nombre"]
						];
					}, $ras);
					
					$body['contextos'] = $contextos;
				}

				$areas_tematicas = toba::consulta_php('co_propuestas')->get_areas_tematicas_propuesta($novedad['propuesta_id']);
				// Si existen Áreas Temáticas para la propuesta las paso como 'tags'
				if (!empty($areas_tematicas)) {

					$tags = array_map(function($area_tematica) {
						return [
							"descripcion"	=> $area_tematica["nombre"],
						];
					}, $areas_tematicas);

					$body['tags'] = $tags;
				}

				// Se logea el Payload
				toba::logger()->debug("Payload:");
				toba::logger()->var_dump($body);
				
				$respuesta = cobro::crear_servicio($body);
				toba::logger()->debug("Se creo un servicio en SQ con el id '{$respuesta['id']}'.");
				
			}
			else{// Si el plan NO es cobrable

				$id_externo = $novedad['plan_id'];
				$respuesta = cobro::eliminar_servicio($id_externo);
				toba::logger()->debug("Se elimino un servicio en SQ con el id '{$respuesta['id']}'.");
				
			}
			
			$mje_ok = guarani_mensajes::get('800ADM_sincro_ok');
			toba::notificacion()->agregar($mje_ok, comunes::mensaje_informacion);
		}
		catch (Exception $e){
			toba::db()->abortar_transaccion();
			
			// Se logea el error
			toba::logger()->debug("Error:");
			toba::logger()->debug($e->getMessage());
			
			// Marco el plan como NO sincronizado con SQ
			guarani::act('act_planes')->cambiar_sincronizacion_plan_sq($seleccion, false);
			toba::logger()->debug("Se marca el plan como NO sincronizado con SQ, plan '$seleccion'.");
			
			$mje_error = guarani_mensajes::get('800ADM_sincro_error');
			toba::notificacion()->agregar($mje_error, comunes::mensaje_critico);
		}
		
	}
	
	
	function get_listado_inscripciones_sincronizar($filtro)
	{
		return toba::consulta_php('co_cobros')->get_listado_inscripciones_sincronizar($filtro);
	}	
	
	/**
     * @see https://repositorio.siu.edu.ar/trac/guarani/ticket/3188 Ticket.
     * @see https://repositorio.siu.edu.ar/trac/guarani/ticket/3235 Ticket.
     */
	function sincronizar_inscripcion($propuesta_aspira, $tipo_operacion)
	{
		
		try{
			
			// Se se trata de una baja busco en la tabla 'sga_propuestas_aspira_log'...
			if ($tipo_operacion == cobro::SQ_INSCRIPCION_FALLO_BAJA) {
				$detalle = guarani::co('co_inscripciones')->get_detalle_propuesta_aspira_log($propuesta_aspira);
			} else { // ...de lo contratio busco en 'sga_propuestas_aspira'
				$detalle = guarani::co('co_inscripciones')->get_detalle_propuesta_aspira($propuesta_aspira);
			}
			
			toba::logger()->debug("Tipo de operación '$tipo_operacion'.");
			toba::logger()->debug("Sincronizando inscripción '$propuesta_aspira':");
			toba::logger()->var_dump($detalle);

			$ras = guarani::co('co_responsables_academicas')->get_propuestas_ras($detalle['propuesta']);
			$usa_sq = guarani::parametros()->get_valor('sq_usa_sanaviron', $ras);
			$es_plan_cobrable = ($detalle['plan_cobrable'] == comunes::si);
			
			/**
			 * Si se trata de un plan cobrable y que utiliza SQ
			 */
			if (($usa_sq == comunes::si) && $es_plan_cobrable) {

				// Si se necesita sincronizar un alta encolada o resincronizar un alta fallida
				if (in_array($tipo_operacion, [cobro::SQ_INSCRIPCION_ENCOLADA, cobro::SQ_INSCRIPCION_FALLO_ALTA])) {
					
					$datos_persona = guarani::co('co_personas')->get_datos_persona($detalle['persona']);

					$body = array(
						"cliente" => array(
							"id_externo" => $datos_persona['persona'],
							"tipo_documento" => $datos_persona['tipo_doc'],
							"numero_documento" => $datos_persona['nro_documento'],
							"apellido" => $datos_persona['apellido'],
							"nombres" => $datos_persona['nombres']
						),
						"tipo_consumo" => cobro::TIPO_CONSUMO_SUSCRIPCION_ABREV,
						"id_consumo" => $detalle['plan'],
						"id_tramite_ref" => $detalle['nro_transaccion'],
						"callback_url" => guarani::get_url_operacion(2)
					);

					// Se logea el Payload
					toba::logger()->debug("Payload:");
					toba::logger()->var_dump($body);

					$respuesta = cobro::generar_solicitud_consumo_externo($body);
				
					toba::db()->abrir_transaccion();
			
					// Guardo el token en la tabla 'sga_propuestas_aspira'
					guarani::act('act_inscripciones')->actualizar_sq_token_propuesta_aspira($detalle['nro_transaccion'], $respuesta['token']);

					// Actualizo el estado de sincronización con SQ en la tabla 'sga_propuestas_aspira'
					guarani::act('act_inscripciones')->actualizar_sq_sincronizado_propuesta_aspira($propuesta_aspira, cobro::SQ_INSCRIPCION_SINCRONIZADA);

					toba::db()->cerrar_transaccion();


					toba::logger()->debug("Se creo una solicitud de consumo externo en SQ con el token '{$respuesta['token']}'.");
			
				} else { // Se se trata de una baja, rechazo o cambio de calidad
					
					$operacion_descr = "";
					switch ($tipo_operacion) {
						case cobro::SQ_INSCRIPCION_FALLO_BAJA:
							$tipo_novedad = cobro::TIPO_NOVEDAD_BAJA;
							$operacion_descr = guarani_mensajes::get('800_sq_sincronizar_baja');
							break;
						case cobro::SQ_INSCRIPCION_FALLO_CAMBIO_CALIDAD_ACTIVO:
							$tipo_novedad = cobro::TIPO_NOVEDAD_ACTIVA;
							$operacion_descr = guarani_mensajes::get('800_sq_sincronizar_cambio_calidad_activo');
							break;
						case cobro::SQ_INSCRIPCION_FALLO_CAMBIO_CALIDAD_PASIVO:
							$tipo_novedad = cobro::TIPO_NOVEDAD_INACTIVA;
							$operacion_descr = guarani_mensajes::get('800_sq_sincronizar_cambio_calidad_pasivo');
							break;
						case cobro::SQ_INSCRIPCION_FALLO_RECHAZO:
							$tipo_novedad = cobro::TIPO_NOVEDAD_INACTIVA;
							$operacion_descr = guarani_mensajes::get('800_sq_sincronizar_rechazo');
							break;
						case cobro::SQ_INSCRIPCION_FALLO_REVERTIR_RECHAZO:
							$tipo_novedad = cobro::TIPO_NOVEDAD_ACTIVA;
							$operacion_descr = guarani_mensajes::get('800_sq_sincronizar_revertir_rechazo');
							break;
					}
				
					$body = array(
						"id" => $detalle['nro_transaccion'],
						"tipo_novedad" => $tipo_novedad,
						"observaciones" => guarani_mensajes::get('800_observacion_sq_suscripciones_novedades', array($detalle['nro_transaccion'], $operacion_descr))
					);
					
					// Solo cuando se trata de una baja (tipo_novedad = 'B') se informa el modo de devolución.
					if ($tipo_novedad == cobro::TIPO_NOVEDAD_BAJA) $body["modo_devolucion"] = cobro::SUSCRIPCIONES_NOVEDADES_MODO_DEVOLUCION_NO_CORRESPONDE;
			
					// Se logea el Payload
					toba::logger()->debug("Payload:");
					toba::logger()->var_dump($body);
					
					$respuesta = cobro::informar_suscripcion_novedad($body);
					
					toba::db()->abrir_transaccion();
					
					// Actualizo el estado de sincronización con SQ en la tabla 'sga_propuestas_aspira' o 'sga_propuestas_aspira_logs' según corresponda
					guarani::act('act_inscripciones')->actualizar_sq_sincronizado_propuesta_aspira($propuesta_aspira, cobro::SQ_INSCRIPCION_SINCRONIZADA, ($tipo_operacion == cobro::SQ_INSCRIPCION_FALLO_BAJA));

					toba::db()->cerrar_transaccion();
					
					toba::logger()->debug("Se creo una suscripción novedad en SQ con el id '{$respuesta['id']}'.");
				}
				
				
			}
			else{
				
				$mje_error = guarani_mensajes::get('800ADM_sincro_inscripcion_no_aplica');
				toba::notificacion()->agregar($mje_error, comunes::mensaje_critico);
				return false;
			}
				
			
			$mje_ok = guarani_mensajes::get('800ADM_sincro_ok');
			toba::notificacion()->agregar($mje_ok, comunes::mensaje_informacion);
		}
		catch (Exception $e){
			toba::db()->abortar_transaccion();
			
			// Se logea el error
			toba::logger()->debug("Error:");
			toba::logger()->debug($e->getMessage());
			
			$mje_error = guarani_mensajes::get('800ADM_sincro_inscripcion_error');
			toba::notificacion()->agregar($mje_error, comunes::mensaje_critico);
		}
		
	}
	
}