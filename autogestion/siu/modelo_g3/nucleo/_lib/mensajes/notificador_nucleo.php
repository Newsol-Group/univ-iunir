<?php
/**
 * Notificador para el envío de emails y mensajes
 *
 * 	Atención: esta clase se instancia con: guarani::notificador()
 *
 * @package Mensajes
 * @subpackage Notificador
 */
class notificador_nucleo
{
	//Circuitos
	const cerrar_acta_regulares = 1;
	const cerrar_acta_promociones = 2;
	const cerrar_acta_examenes = 3;
	const actualizar_tramite_certificacion = 4;
	const modificar_mesas_masivamente = 5;
	const circuito_tesis = 6;
	const alumnos_extranjeros = 7;
	const ingresantes_adeuden_requisito_ingreso = 8;
	const administrar_solicitudes = 9;
	const emision_certificados_cursos = 10;
	const enviar_mensaje = 11;
	const inscripciones_pendientes_a_examenes = 12;
	const inscribir_a_cursadas_3w = 13;
	const inscribir_a_examenes_3w = 14;
	const inscribir_a_actividad = 15;
	const inscribir_a_examenes = 16;
	const distribuir_comisiones = 17;
	const eliminar_inscripcion_cursada = 18;
	const eliminar_inscripcion_examen = 19;
	const adeudan_requisitos = 20;
	const autorizar_acta_huarpe = 21;
    const inscripcion_propuesta = 22;
    const cursar_rendir = 23;//Ticket #22860
	const notificar_sancion_alumno = 31;//Ticket #44963
    const notificar_sancion_docente = 32;//Ticket #44963

	// Ticket #46244 (https://redmine.siu.edu.ar/redmine/issues/46244)
	const notificar_reapertura_convocatoria_beca = 33;

	const notificar_reapertura_tramite_egreso = 34;

	//Tipos de mensajes
	const tipo_mensaje_general = 1;
	const tipo_mensaje_urgente = 2;
	const tipo_mensaje_calendario = 3;
	const tipo_mensaje_inscripciones = 4;
	const tipo_mensaje_docentes = 5;
	const tipo_mensaje_notas = 6;
	const tipo_mensaje_tramite_egreso = 7;
	const tipo_mensaje_tramite_tesis = 8;
	const tipo_mensaje_constancias_y_certificados = 9;
	const tipo_mensaje_encuestas = 10;
	
	//Parametro para el envío de emails
	const notificaciones_envia_mails = 'notificaciones_envia_mails';

	static protected $instancia;
	protected $indx_msj_error = '800_error_envio_mensajes';
	protected $habilitar_transacciones = true;

	protected function __construct()
	{
		
	}

	/**
	 * Retorna una instancia de la clase notificador siguiendo el patrón Singleton
	 * @return notificador
	 */
	static function instancia()
	{
		if (!isset(static::$instancia)) {
			static::$instancia = new notificador();
		}
		return static::$instancia;
	}

        protected function abrir_transaccion()
	{
            if ($this->habilitar_transacciones) {
                toba::db()->abrir_transaccion();
            }
	}
        
        protected function cerrar_transaccion()
	{
            if ($this->habilitar_transacciones) {
		toba::db()->cerrar_transaccion();
            }
	}
        
        protected function abortar_transaccion()
	{
            if ($this->habilitar_transacciones) {
		toba::db()->abortar_transaccion();
            }
	}
        
	/**
	 * Envía las notificaciones de un alcance dado.
	 *
	 * @param int $alcance Alcance de la notificación.
	 * @param mixed[] $parametros Parámetros de la notificación.
	 * @param bool $habilitar_transacciones Si está en FALSE deshabilita las transacciones y le da el control al quien esta llamando.
	 *
	 * @throws guarani_error Si ocurre algún error durante el proceso.
	 */
	public function enviar($alcance, $parametros, $habilitar_transacciones = true)
	{
                $this->habilitar_transacciones = $habilitar_transacciones;
                
		switch ($alcance) {
			case static::cerrar_acta_regulares:
				$this->cerrar_acta_regulares($parametros['id_acta']);
				break;
			case static::cerrar_acta_promociones:
				$this->cerrar_acta_promociones($parametros['id_acta']);
				break;
			case static::cerrar_acta_examenes:
				$this->cerrar_acta_examenes($parametros['id_acta']);
				break;
			case static::actualizar_tramite_certificacion:
				$this->actualizar_tramite_certificacion($parametros['nro_tramite']);
				break;
			case static::modificar_mesas_masivamente:
				$this->modificar_mesas_masivamente($parametros['llamado_mesa'], $parametros['personas'], $parametros['tipo_modificacion']);
				break;
			case static::circuito_tesis:
				$this->circuito_tesis($parametros['id_tesis']);
				break;
			case static::alumnos_extranjeros:

				$enviarEmail = (isset($parametros['enviarEmail'])) ? $parametros['enviarEmail'] : true;
				$adjuntos = (isset($parametros['adjuntos'])) ? $parametros['adjuntos'] : [];
				$responderA = (isset($parametros['responderA'])) ? $parametros['responderA'] : null;
				$enviarPorTwitter = (isset($parametros['enviarPorTwitter'])) ? $parametros['enviarPorTwitter'] : 0;
				$mensajeTwitter = (isset($parametros['mensajeTwitter'])) ? $parametros['mensajeTwitter'] : '';
				$enviarPorFacebook = (isset($parametros['enviarPorFacebook'])) ? $parametros['enviarPorFacebook'] : 0;
				$dominios = (isset($parametros['dominios'])) ? $parametros['dominios'] : [];
				$visibleGestion = (isset($parametros['visibleGestion'])) ? $parametros['visibleGestion'] : comunes::no;
				$remitente = (isset($parametros['remitente'])) ? $parametros['remitente'] : null;

				$this->enviar_mensaje(
						$parametros['titulo'], $parametros['cuerpo'], $parametros['alcance'], static::tipo_mensaje_general, $parametros['fechaDesde'], $parametros['fechaHasta'], $parametros['personas'], $enviarEmail, $adjuntos, $responderA, $enviarPorTwitter, $mensajeTwitter, $enviarPorFacebook, $dominios, $visibleGestion, comunes::interfaz_gestion, $remitente
				);
				
				break;
			case static::ingresantes_adeuden_requisito_ingreso:
				$this->ingresantes_adeuden_requisito_ingreso($parametros['personas']);
				break;
			case static::administrar_solicitudes:
				$this->administrar_solicitudes($parametros['id_solicitud']);
				break;
			case static::emision_certificados_cursos:
				$this->emision_certificados_cursos($parametros['ids_solicitudes']);
				break;
			case static::enviar_mensaje:

				$enviarEmail = (isset($parametros['enviarEmail'])) ? $parametros['enviarEmail'] : true;
				$adjuntos = (isset($parametros['adjuntos'])) ? $parametros['adjuntos'] : [];
				$responderA = (isset($parametros['responderA'])) ? $parametros['responderA'] : null;
				$enviarPorTwitter = (isset($parametros['enviarPorTwitter'])) ? $parametros['enviarPorTwitter'] : 0;
				$mensajeTwitter = (isset($parametros['mensajeTwitter'])) ? $parametros['mensajeTwitter'] : '';
				$enviarPorFacebook = (isset($parametros['enviarPorFacebook'])) ? $parametros['enviarPorFacebook'] : 0;
				$dominios = (isset($parametros['dominios'])) ? $parametros['dominios'] : [];
				$visibleGestion = (isset($parametros['visibleGestion'])) ? $parametros['visibleGestion'] : comunes::no;
				$interfaz = (isset($parametros['interfaz'])) ? $parametros['interfaz'] : comunes::interfaz_gestion;
				$remitente = (isset($parametros['remitente'])) ? $parametros['remitente'] : null;
				
				$this->enviar_mensaje(
						$parametros['titulo'], $parametros['cuerpo'], $parametros['alcance'], $parametros['tipoMensaje'], $parametros['fechaDesde'], $parametros['fechaHasta'], $parametros['personas'], $enviarEmail, $adjuntos, $responderA, $enviarPorTwitter, $mensajeTwitter, $enviarPorFacebook, $dominios, $visibleGestion, $interfaz, $remitente
				);
				
				break;
			
			case static::inscripciones_pendientes_a_examenes:
				$this->inscripciones_pendientes_a_examenes($parametros);
				break;
			
			case static::inscribir_a_cursadas_3w:
				$this->inscribir_a_cursadas_3w($parametros);
				break;

			case static::inscribir_a_examenes_3w:
				$this->inscribir_a_examenes_3w($parametros);
				break;
                            
			case static::inscribir_a_actividad:
				$this->inscribir_a_actividad($parametros);
				break;
                        
			case static::inscribir_a_examenes:
				$this->inscribir_a_examenes($parametros);
				break;
			
			case static::distribuir_comisiones:
				$this->distribuir_comisiones($parametros);
				break;

			case static::eliminar_inscripcion_cursada:
				$this->eliminar_inscripcion_cursada($parametros);
				break;
			
			case static::eliminar_inscripcion_examen:
				$this->eliminar_inscripcion_examen($parametros);
				break;
			
			case static::adeudan_requisitos:
				$this->enviar_adeudan_requisitos($parametros['dominios']);
				break;
                            
                        case static::autorizar_acta_huarpe:
                                $this->autorizar_acta_huarpe($parametros['id_acta']);
                                break;
            case static::inscripcion_propuesta:
                $this->enviar_inscripcion_propuesta($parametros);
                break;
            
            case static::cursar_rendir:
                $this->enviar_alumnos_para_cursar_o_rendir($parametros);
                break;
			
            case static::notificar_sancion_alumno:				
                $this->enviar_notificacion_sancion_alumno($parametros);
                break;
			
            case static::notificar_sancion_docente:
                $this->enviar_notificacion_sancion_docente($parametros);
                break;

            case static::notificar_reapertura_convocatoria_beca:
                $this->notificar_reapertura_convocatoria_beca($parametros);
                break;

            case static::notificar_reapertura_tramite_egreso:
                $this->notificar_reapertura_tramite_egreso($parametros);
                break;
            }
	}

	/**
	 * Notifica a los alumnos que se ha cerrado un acta de regulares en la cual forma parte.
	 *
	 * @see https://repositorio.siu.edu.ar/trac/guarani/wiki/Casos_de_Uso/Cerra_Acta_Reg Caso de Uso.
	 *
	 * @param int $id_acta Id del acta de regulares a cerrar.
	 *
	 * @throws guarani_error Si ocurre algún error durante el proceso.
	 */
	protected function cerrar_acta_regulares($id_acta)
	{

		$parametros['id_acta'] = $id_acta;
		$obligatorios = ['id_acta'];
		if (!$this->validar_parametros_faltantes($parametros, $obligatorios)) {
			return false;
		}
		
		try {

			// Atributos para resolver valor de parámetros
			$propuestas = aplanar_matriz(toba::consulta_php('co_actas')->get_propuestas_acta($id_acta), 'propuesta');
			$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($propuestas);

			$cur_notificar_cierre_acta_regulares = guarani::parametros()->get_valor(acta::cur_notificar_cierre_acta_regulares, $ras, false);
			// Si no se debe enviar notificaciones salgo
			if ($cur_notificar_cierre_acta_regulares == comunes::no)
				return true;

			// Datos del acta
			$datos_acta = toba::consulta_php('co_actas')->get_datos_acta($id_acta);

			// Alumnos del acta
			$alumnos = toba::consulta_php('co_actas')->get_alumnos_acta_cursadas($id_acta);

			// Si no hay alumnos para notificar salgo
			if (empty($alumnos)) {
				return true;
			}
			$personasIds = array_column($alumnos, 'persona');
			$emailsPersonas = toba::consulta_php('co_personas')->get_emails_personas($personasIds);
			$nombresPersonas = toba::consulta_php('co_personas')->get_apellido_nombres_personas($personasIds);

			if ($datos_acta['tipo_acta'] == acta::tipo_normal) {
				$plantilla = toba::consulta_php('co_mensajes')->get_plantilla(1);
			} elseif ($datos_acta['tipo_acta'] == acta::tipo_rectificativa) {
				$plantilla = toba::consulta_php('co_mensajes')->get_plantilla(2);
			}

			$plantillaTitulo = str_replace("%nombre actividad%", $datos_acta['actividad_nombre'], $plantilla['titulo']);
			$plantillaTexto = $plantilla['texto'];

			// Abro una transacción
			$this->abrir_transaccion();

			// Se inserta el mensaje en la tabla 'men_mensajes'.
			$mensaje = guarani::act('act_mensajes')->set_mensajes_men_mensajes_personalizado($plantillaTitulo, static::tipo_mensaje_notas);

			$buscar = array("%nombre del alumno%", "%nombre actividad%", "%nota%");
			foreach ($alumnos as $alumno) {
				$persona = $alumno['persona'];

                                if($alumno['nota_nombre'] != NULL) {
                                    $reemplazo = array($nombresPersonas[$persona]['nombres'], $datos_acta['actividad_nombre'], $alumno['nota_nombre']);
                                }
                                else {
                                    $reemplazo = array($nombresPersonas[$persona]['nombres'], $datos_acta['actividad_nombre'], $alumno["resultado_nombre"]);
                                }
                                
				$cuerpo = str_replace($buscar, $reemplazo, $plantillaTexto);
				// Se inserta el destinatario en la tabla 'men_destinatarios'.
				guarani::act('act_mensajes')->set_mensajes_men_destinatarios($mensaje, $persona, $cuerpo);

				$this->encolar_email($plantillaTitulo, $cuerpo, $persona, $emailsPersonas);
			}
			// Si sale bien cierro la transacción
			$this->cerrar_transaccion();
		} catch (Exception $e) {
			// Si falla aborto la transacción
			$this->abortar_transaccion();
			$this->manejar_excepcion($e);
			$msj_error = guarani_mensajes::get($this->indx_msj_error);
			throw new guarani_error($msj_error);
		}
	}

	/**
	 * Notifica a los alumnos que se ha cerrado un acta de promociones en la cual forma parte.
	 *
	 * @see https://repositorio.siu.edu.ar/trac/guarani/wiki/Casos_de_Uso/Cerra_Acta_Promo Caso de Uso.
	 *
	 * @param int $id_acta Id del acta de promociones a cerrar.
	 *
	 * @throws guarani_error Si ocurre algún error durante el proceso.
	 */
	protected function cerrar_acta_promociones($id_acta)
	{

		$parametros['id_acta'] = $id_acta;
		$obligatorios = ['id_acta'];
		if (!$this->validar_parametros_faltantes($parametros, $obligatorios)) {
			return false;
		}
		
		try {

			// Atributos para resolver valor de parámetros
			$propuestas = aplanar_matriz(toba::consulta_php('co_actas')->get_propuestas_acta($id_acta), 'propuesta');
			$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($propuestas);

			$cur_notificar_cierre_acta_promocion = guarani::parametros()->get_valor(acta::cur_notificar_cierre_acta_promocion, $ras, false);
			// Si no se debe enviar notificaciones salgo
			if ($cur_notificar_cierre_acta_promocion == comunes::no) {
				return true;
			}
			// Datos del acta
			$datos_acta = toba::consulta_php('co_actas')->get_datos_acta($id_acta);

			// Alumnos del acta
			$alumnos = toba::consulta_php('co_actas')->get_alumnos_acta_cursadas($id_acta);

			// Si no hay alumnos para notificar salgo
			if (empty($alumnos)) {
				return true;
			}
			$personasIds = array_column($alumnos, 'persona');
			$emailsPersonas = toba::consulta_php('co_personas')->get_emails_personas($personasIds);
			$nombresPersonas = toba::consulta_php('co_personas')->get_apellido_nombres_personas($personasIds);

			if ($datos_acta['tipo_acta'] == acta::tipo_normal) {
				$plantilla = toba::consulta_php('co_mensajes')->get_plantilla(3);
			} else if ($datos_acta['tipo_acta'] == acta::tipo_rectificativa) {
				$plantilla = toba::consulta_php('co_mensajes')->get_plantilla(4);
			}

			$plantillaTitulo = str_replace("%nombre actividad%", $datos_acta['actividad_nombre'], $plantilla['titulo']);
			$plantillaTexto = $plantilla['texto'];

			// Abro una transacción
			$this->abrir_transaccion();;

			// Se inserta el mensaje en la tabla 'men_mensajes'.
			$mensaje = guarani::act('act_mensajes')->set_mensajes_men_mensajes_personalizado($plantillaTitulo, static::tipo_mensaje_notas);

			$buscar = array("%nombre del alumno%", "%nombre actividad%", "%nota%");
			foreach ($alumnos as $alumno) {
				$persona = $alumno['persona'];

                                if($alumno['nota_nombre'] != NULL) {
                                    $reemplazo = array($nombresPersonas[$persona]['nombres'], $datos_acta['actividad_nombre'], $alumno['nota_nombre']);
                                }
                                else {
                                    $reemplazo = array($nombresPersonas[$persona]['nombres'], $datos_acta['actividad_nombre'], $alumno["resultado_nombre"]);
                                }
                                
				$cuerpo = str_replace($buscar, $reemplazo, $plantillaTexto);
				// Se inserta el destinatario en la tabla 'men_destinatarios'.
				guarani::act('act_mensajes')->set_mensajes_men_destinatarios($mensaje, $persona, $cuerpo);

				$this->encolar_email($plantillaTitulo, $cuerpo, $persona, $emailsPersonas);
			}
			// Si sale bien cierro la transacción
			$this->cerrar_transaccion();
		} catch (Exception $e) {
			// Si falla aborto la transacción
			$this->abortar_transaccion();
			$this->manejar_excepcion($e);
			$msj_error = guarani_mensajes::get($this->indx_msj_error);
			throw new guarani_error($msj_error);
		}
	}

	/**
	 * Notifica a los alumnos que se ha cerrado un acta de exámenes en la cual forma parte.
	 *
	 * @see https://repositorio.siu.edu.ar/trac/guarani/wiki/Casos_de_Uso/Cerrar_Actas_Examen Caso de Uso.
	 *
	 * @param int $id_acta Id del acta de exámenes a cerrar.
	 *
	 * @throws guarani_error Si ocurre algún error durante el proceso.
	 */
	protected function cerrar_acta_examenes($id_acta)
	{

		$parametros['id_acta'] = $id_acta;
		$obligatorios = ['id_acta'];
		if (!$this->validar_parametros_faltantes($parametros, $obligatorios)) {
			return false;
		}
		
		try {

			// Atributos para resolver valor de parámetros
			$propuestas = aplanar_matriz(toba::consulta_php('co_actas')->get_propuestas_acta($id_acta), 'propuesta');
			$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($propuestas);

			$exa_notificar_cierre_acta_examen = guarani::parametros()->get_valor(acta::exa_notificar_cierre_acta_examen, $ras, false);
			// Si no se debe enviar notificaciones salgo
			if ($exa_notificar_cierre_acta_examen == comunes::no) {
				return true;
			}

			$detalles = toba::consulta_php('co_actas')->get_detalles_para_envio_mail($id_acta);

			// Si no hay alumnos para notificar salgo
			if (empty($detalles)) {
				return true;
			}
			$personasIds = array_column($detalles, 'persona');
			$emailsPersonas = toba::consulta_php('co_personas')->get_emails_personas($personasIds);
			$nombresPersonas = toba::consulta_php('co_personas')->get_apellido_nombres_personas($personasIds);

			if ($detalles[0]['rectificado'] == 0) {
				$plantilla = toba::consulta_php('co_mensajes')->get_plantilla(5);
			} else {
				$plantilla = toba::consulta_php('co_mensajes')->get_plantilla(6);
			}

			$fecha_examen = $detalles[0]['fecha_examen'];
			$plantillaTitulo = str_replace("%fecha mesa%", $fecha_examen, $plantilla['titulo']);
			$plantillaTexto = $plantilla['texto'];

			// Abro una transacción
			$this->abrir_transaccion();

			// Se inserta el mensaje en la tabla 'men_mensajes'.
			$mensaje = guarani::act('act_mensajes')->set_mensajes_men_mensajes_personalizado($plantillaTitulo, static::tipo_mensaje_notas);

			$buscar = array("%nombre del alumno%", "%nombre actividad%", "%fecha mesa%", "%nota%");
			foreach ($detalles as $detalle) {
				$persona = $detalle['persona'];
                                
                                if($detalle['nota_descr'] != NULL) {
                                    $reemplazo = array($nombresPersonas[$persona]['nombres'], $detalle['actividad'], $detalle['fecha_examen'], $detalle['nota_descr']);
                                }
                                else {
                                    $reemplazo = array($nombresPersonas[$persona]['nombres'], $detalle['actividad'], $detalle['fecha_examen'], $detalle["resultado_descripcion"]);
                                }


				$cuerpo = str_replace($buscar, $reemplazo, $plantillaTexto);
				// Se inserta el destinatario en la tabla 'men_destinatarios'.
				guarani::act('act_mensajes')->set_mensajes_men_destinatarios($mensaje, $persona, $cuerpo);

				$this->encolar_email($plantillaTitulo, $cuerpo, $persona, $emailsPersonas);
			}
			// Si sale bien cierro la transacción
			$this->cerrar_transaccion();
		} catch (Exception $e) {
			// Si falla aborto la transacción
			$this->abortar_transaccion();
			$this->manejar_excepcion($e);
			$msj_error = guarani_mensajes::get($this->indx_msj_error);
			throw new guarani_error($msj_error);
		}
	}
        
        /**
	 * Notifica a los docentes de un acta que tienen un documento para autorizar en SIU-Huarpe.
	 *
	 * @see https://redmine.siu.edu.ar/redmine/projects/guarani3/wiki/Casos_de_UsoCerrar_Actas_Examen Caso de Uso.
	 *
	 * @param int $id_acta Id del acta.
	 *
	 * @throws guarani_error Si ocurre algún error durante el proceso.
	 */
	protected function autorizar_acta_huarpe($id_acta)
	{

		$parametros['id_acta'] = $id_acta;
		$obligatorios = ['id_acta'];
		if (!$this->validar_parametros_faltantes($parametros, $obligatorios)) {
			return false;
		}
		
		try {

			// Atributos para resolver valor de parámetros
			$propuestas = aplanar_matriz(toba::consulta_php('co_actas')->get_propuestas_acta($id_acta), 'propuesta');
			$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($propuestas);

                        $rs = toba::consulta_php('co_actas')->get_origen_acta($id_acta);
                        $origen_acta = $rs[0]['origen'];
                
                        if ($origen_acta == acta::origen_examen) {
                            $utiliza_acta_digital = guarani::parametros()->get_valor(acta::exa_notificar_cierre_acta_examen, $ras, false);
                            $docentes_autorizantes = toba::consulta_php('co_actas')->get_docentes_autorizantes_acta_examen($id_acta);
                        } elseif ($origen_acta == acta::origen_regulares) {
                            $utiliza_acta_digital = guarani::parametros()->get_valor(acta::cur_notificar_cierre_acta_regulares, $ras, false);
                            $docentes_autorizantes = toba::consulta_php('co_actas')->get_docentes_autorizantes_acta_comision($id_acta);
                        } elseif ($origen_acta == acta::origen_promociones) {
                            $utiliza_acta_digital = guarani::parametros()->get_valor(acta::cur_notificar_cierre_acta_promocion, $ras, false);
                            $docentes_autorizantes = toba::consulta_php('co_actas')->get_docentes_autorizantes_acta_comision($id_acta);
                        }
                        
			// Si no se debe enviar notificaciones salgo
			if ($utiliza_acta_digital == comunes::no) {
                            return true;
			}
                        
			// Si no hay docentes para notificar salgo
			if (empty($docentes_autorizantes)) {
                            return true;
			}
                        
			$personasIds = array_column($docentes_autorizantes, 'persona');
			$emailsPersonas = toba::consulta_php('co_personas')->get_emails_personas($personasIds);
			$nombresPersonas = toba::consulta_php('co_personas')->get_apellido_nombres_personas($personasIds);
                        
			$plantilla = toba::consulta_php('co_mensajes')->get_plantilla(26);
			$cuerpo = $plantilla['texto'];

			// Abro una transacción
			$this->abrir_transaccion();
                        
			foreach ($docentes_autorizantes as $docente) {
				
                            $persona = $docente['persona'];

                            $plantillaTitulo = str_replace("%nombre docente%", $nombresPersonas[$persona]['nombres'], $plantilla['titulo']);
                            // Se inserta el mensaje en la tabla 'men_mensajes'.
                            $mensaje = guarani::act('act_mensajes')->set_mensajes_men_mensajes_personalizado($plantillaTitulo, static::tipo_mensaje_general);
                            
                            // Se inserta el destinatario en la tabla 'men_destinatarios'.
                            guarani::act('act_mensajes')->set_mensajes_men_destinatarios($mensaje, $persona, $cuerpo);

                            $this->encolar_email($plantillaTitulo, $cuerpo, $persona, $emailsPersonas);
			}
                        
			// Si sale bien cierro la transacción
			$this->cerrar_transaccion();
		} catch (Exception $e) {
			// Si falla aborto la transacción
			$this->abortar_transaccion();
			$this->manejar_excepcion($e);
			$msj_error = guarani_mensajes::get($this->indx_msj_error);
			throw new guarani_error($msj_error);
		}
	}

	/**
	 * Notifica al alumno que su trámite de titulo se actualizó.
	 *
	 * @see https://repositorio.siu.edu.ar/trac/guarani/wiki/Casos_de_Uso/Cambio_Estado_Solicitud Caso de Uso.
	 *
	 * @param int $nro_tramite Número de solicitud del certificado.
	 *
	 * @throws guarani_error Si ocurre algún error durante el proceso.
	 */
	protected function actualizar_tramite_certificacion($nro_tramite)
	{
		
		$parametros['nro_tramite'] = $nro_tramite;
		$obligatorios = ['nro_tramite'];
		if (!$this->validar_parametros_faltantes($parametros, $obligatorios)) {
			return false;
		}

		try {

			$persona = toba::consulta_php('co_circuitos_tramite')->get_persona_a_notificar_cambio_estado_certificacion($nro_tramite);

			// Si no hay datos salgo
			if (empty($persona)) {
				return true;
			}
			// Me fijo en la transición dentro de la operación 'Administrar Circuitos de Egreso'
			if ($persona['enviar_aviso_alumno'] == comunes::no) {
				return true;
			}
			$personaId = $persona['persona'];
			$personasIds = array($personaId);
			$emailsPersonas = toba::consulta_php('co_personas')->get_emails_personas($personasIds);

			$plantilla = toba::consulta_php('co_mensajes')->get_plantilla(8);

			$plantillaTitulo = $plantilla['titulo'];
			$plantillaTexto = $plantilla['texto'];

			$buscar = array("%nombre del alumno%", "%nombre titulo%", "%estado tramite%");
			$reemplazo = array($persona['persona_nombres'], $persona['certificado_nombre'], $persona['estado_nombre']);
			$cuerpo = str_replace($buscar, $reemplazo, $plantillaTexto);

			// Abro una transacción
			$this->abrir_transaccion();

			// Se inserta el mensaje en la tabla 'men_mensajes'.
			$mensaje = guarani::act('act_mensajes')->set_mensajes_men_mensajes_personalizado($plantillaTitulo, static::tipo_mensaje_tramite_egreso);

			// Se inserta el destinatario en la tabla 'men_destinatarios'.
			guarani::act('act_mensajes')->set_mensajes_men_destinatarios($mensaje, $personaId, $cuerpo);

			$this->encolar_email($plantillaTitulo, $cuerpo, $personaId, $emailsPersonas);

			// Si sale bien cierro la transacción
			$this->cerrar_transaccion();
		} catch (Exception $e) {
			// Si falla aborto la transacción
			$this->abortar_transaccion();
			$this->manejar_excepcion($e);
			$msj_error = guarani_mensajes::get($this->indx_msj_error);
			throw new guarani_error($msj_error);
		}
	}

	/**
	 * Notifica a los alumnos y docentes que se modifico una mesa de examen de la cual forma parte.
	 *
	 * @see https://repositorio.siu.edu.ar/trac/guarani/wiki/Casos_de_Uso/Suspender_Mesas_Examen Caso de Uso.
	 *
	 * @param int[] $personas array de alumnos y docentes correspondientes a la mesa de examen.
	 * @param string $tipo_modificacion Tipo de modificación ('S' <=> Suspensión, 'F' <=> Cambio de fecha, 'H' <=> Cambio de horario).
	 *
	 * @throws guarani_error Si ocurre algún error durante el proceso.
	 */
	protected function modificar_mesas_masivamente($llamado_mesa, $personas, $tipo_modificacion)
	{
		
		$parametros['llamado_mesa'] = $llamado_mesa;
		$parametros['personas'] = $personas;
		$parametros['tipo_modificacion'] = $tipo_modificacion;
		$obligatorios = ['llamado_mesa', 'personas', 'tipo_modificacion'];
		if (!$this->validar_parametros_faltantes($parametros, $obligatorios)) {
			return false;
		}
		
		try {
			switch ($tipo_modificacion) {
				case mesa_de_examen::cambio_suspension:
					$plantilla = toba::consulta_php('co_mensajes')->get_plantilla(9);
					$buscar = array("%nombre actividad%", "%fecha mesa%");
					break;
				case mesa_de_examen::cambio_fecha:
					$plantilla = toba::consulta_php('co_mensajes')->get_plantilla(10);
					$buscar = array("%nombre actividad%", "%nueva fecha%");
					break;
				case mesa_de_examen::cambio_horario:
					$plantilla = toba::consulta_php('co_mensajes')->get_plantilla(11);
					$buscar = array("%nombre actividad%", "%fecha mesa%", "%nueva hora%");
					break;
			}

			$plantillaTitulo = $plantilla['titulo'];
			$plantillaTexto = $plantilla['texto'];

			// Abro una transacción
			$this->abrir_transaccion();

			// Se inserta el mensaje en la tabla 'men_mensajes'.
			$mensaje = guarani::act('act_mensajes')->set_mensajes_men_mensajes_personalizado($plantillaTitulo, static::tipo_mensaje_calendario);
				
			// Los datos de la mesa que se recuperan ya fueron modificados por el proceso (fecha/hora/estado suspendido).
			$datos_mesa = toba::consulta_php('co_mesas_de_examen')->get_datos_llamado_mesa($llamado_mesa);
				
			switch ($tipo_modificacion) {
				case mesa_de_examen::cambio_suspension:
					$reemplazo = array($datos_mesa['actividad_nombre'], $datos_mesa['fecha_formato_visual']);
					break;
				case mesa_de_examen::cambio_fecha:
					$reemplazo = array($datos_mesa['actividad_nombre'], $datos_mesa['fecha_formato_visual']);
					break;
				case mesa_de_examen::cambio_horario:
					$reemplazo = array($datos_mesa['actividad_nombre'], $datos_mesa['fecha_formato_visual'], $datos_mesa['mesa_examen_hora_inicio_visual']);
					break;
			}

			$plantillaTextoMesa = str_replace($buscar, $reemplazo, $plantillaTexto);
				
			$personasIds = array_column($personas, 'persona');
			$emailsPersonas = toba::consulta_php('co_personas')->get_emails_personas($personasIds);
			$buscarPersona = array("%nombre del alumno%");
			foreach ($personas as $persona) {
				$reemplazoPersona = array($persona['nombres']);
				$cuerpo = str_replace($buscarPersona, $reemplazoPersona, $plantillaTextoMesa);
				$personaId = $persona['persona'];
				// Se inserta el destinatario en la tabla 'men_destinatarios'.
				guarani::act('act_mensajes')->set_mensajes_men_destinatarios($mensaje, $personaId, $cuerpo);

				$this->encolar_email($plantillaTitulo, $cuerpo, $personaId, $emailsPersonas);
			}

			// Si sale bien cierro la transacción
			$this->cerrar_transaccion();
		} catch (Exception $e) {
			// Si falla aborto la transacción
			$this->abortar_transaccion();
			$this->manejar_excepcion($e);
			$msj_error = guarani_mensajes::get($this->indx_msj_error);
			throw new guarani_error($msj_error);
		}
	}

	/**
	 * Notifica a los alumnos que se actualizó su trámite de tesis.
	 *
	 * @see https://repositorio.siu.edu.ar/trac/guarani/wiki/Casos_de_Uso/Administrar_Tesis Caso de Uso.
	 *
	 * @param int $id_tesis Id de la tesis.
	 *
	 * @throws guarani_error Si ocurre algún error durante el proceso.
	 */
	protected function circuito_tesis($id_tesis)
	{
		
		$parametros['id_tesis'] = $id_tesis;
		$obligatorios = ['id_tesis'];
		if (!$this->validar_parametros_faltantes($parametros, $obligatorios)) {
			return false;
		}
		
		try {
			$notificar_personas_tesis = toba::consulta_php('co_tesis')->se_debe_notificar_personas_tesis($id_tesis);
			// Me fijo en la transición dentro de la operación 'Administrar Circuitos de Tesis'
			if (!$notificar_personas_tesis) {
				return true;
			}
			$personas = toba::consulta_php('co_tesis')->get_personas_a_notificar_cambio_estado_tesis($id_tesis);

			$personasIds = array_column($personas, 'persona');
			$emailsPersonas = toba::consulta_php('co_personas')->get_emails_personas($personasIds);

			$plantilla = toba::consulta_php('co_mensajes')->get_plantilla(12);

			$plantillaTitulo = $plantilla['titulo'];
			$plantillaTexto = $plantilla['texto'];

			// Abro una transacción
			$this->abrir_transaccion();

			// Se inserta el mensaje en la tabla 'men_mensajes'.
			$mensaje = guarani::act('act_mensajes')->set_mensajes_men_mensajes_personalizado($plantillaTitulo, static::tipo_mensaje_tramite_tesis);

			$buscar = array("%nombre del alumno%", "%nombre tesis%", "%estado tramite%");
			foreach ($personas as $persona) {
				$reemplazo = array($persona['persona_nombres'], $persona['tesis_titulo'], $persona['tesis_estado']);
				$cuerpo = str_replace($buscar, $reemplazo, $plantillaTexto);
				$personaId = $persona['persona'];
				// Se inserta el destinatario en la tabla 'men_destinatarios'.
				guarani::act('act_mensajes')->set_mensajes_men_destinatarios($mensaje, $personaId, $cuerpo);

				$this->encolar_email($plantillaTitulo, $cuerpo, $personaId, $emailsPersonas);
			}
			// Si sale bien cierro la transacción
			$this->cerrar_transaccion();
		} catch (Exception $e) {
			// Si falla aborto la transacción
			$this->abortar_transaccion();
			$this->manejar_excepcion($e);
			$msj_error = guarani_mensajes::get($this->indx_msj_error);
			throw new guarani_error($msj_error);
		}
	}

	/**
	 * Notifica a todos los alumnos extranjeros de una o varias propuestas.
	 *
	 * @see https://repositorio.siu.edu.ar/trac/guarani/wiki/Casos_de_Uso/Enviar_Mensajes Caso de Uso.
	 *
	 * @param string $titulo Título del mensaje.
	 * @param string $cuerpo Cuerpo del mensaje.
	 * @param array  $adjuntos Archivos adjuntos.
	 * @param string $fechaDesde Fecha a partir de la cual se enviará email/mostrará mensaje (formato: yyyy-mm-dd).
	 *
	 * @throws guarani_error Si ocurre algún error durante el proceso.
	 */
	/* function alumnos_extranjeros($titulo, $cuerpo, $adjuntos = array(), $fechaDesde = null)
	  {

	  try{

	  $nacExtranjero = persona::nac_extranjero;
	  $where = "mdp_personas.nacionalidad = $nacExtranjero";
	  $personas = toba::consulta_php('co_personas')->get_listado($where);

	  $personasIds = array_column($personas, 'persona');
	  $emailsPersonas = toba::consulta_php('co_personas')->get_emails_personas($personasIds);

	  // Abro una transacción
	  $this->abrir_transaccion();

	  // Se inserta el mensaje en la tabla 'men_mensajes'.
	  $mensaje = guarani::act('act_mensajes')->set_mensajes_men_mensajes_personalizado($titulo, static::tipo_mensaje_general);

	  foreach ($personas as $persona) {
	  $personaId = $persona['persona'];
	  // Se inserta el destinatario en la tabla 'men_destinatarios'.
	  guarani::act('act_mensajes')->set_mensajes_men_destinatarios($mensaje, $personaId, $cuerpo);

	  $this->encolar_email($titulo, $cuerpo, $personaId, $emailsPersonas, $adjuntos, $fechaDesde);

	  }
	  // Si sale bien cierro la transacción
	  $this->cerrar_transaccion();

	  }
	  catch (Exception $e){
	  // Si falla aborto la transacción
	  $this->abortar_transaccion();
	  $this->manejar_excepcion($e);
	  $msj_error = guarani_mensajes::get($this->indx_msj_error);
	  throw new guarani_error($msj_error);
	  }

	  } */
	/**
	 * Notifica a ingresantes que adeuden algún requisito de ingreso documental.
	 *
	 * @see https://repositorio.siu.edu.ar/trac/guarani/wiki/Casos_de_Uso/Enviar_Mensajes Caso de Uso.
	 *
	 * @param array  $personas Array de personas a notificar.
	 *
	 * @throws guarani_error Si ocurre algún error durante el proceso.
	 */
	protected function ingresantes_adeuden_requisito_ingreso($personas)
	{

		$parametros['personas'] = $personas;
		$obligatorios = ['personas'];
		if (!$this->validar_parametros_faltantes($parametros, $obligatorios)) {
			return false;
		}
		
		try {

			$personasIn = implode(", ", $personas);
			$where = "vw_personas.persona IN ($personasIn)";
			$personasRequisitos = toba::consulta_php('co_requisitos_ingreso')->get_listado_reporte($where);

			// Si no hay alumnos que adeuden requisitos salgo
			if (empty($personasRequisitos)) {
				return false;
			}
			$personasIds = array_column($personasRequisitos, 'persona');
			$personasIds = array_unique($personasIds, SORT_NUMERIC);
			$emailsPersonas = toba::consulta_php('co_personas')->get_emails_personas($personasIds);

			$plantilla = toba::consulta_php('co_mensajes')->get_plantilla(7);
			$plantillaTitulo = $plantilla['titulo'];
			$plantillaTexto = $plantilla['texto'];

			// Abro una transacción
			$this->abrir_transaccion();

			// Se inserta el mensaje en la tabla 'men_mensajes'.
			$mensaje = guarani::act('act_mensajes')->set_mensajes_men_mensajes_personalizado($plantillaTitulo, static::tipo_mensaje_general);

			$personas = array();
			// Genero una estructura más manejable
			foreach ($personasRequisitos as $personaRequisito) {

				$personas[$personaRequisito['persona']]['nombres_elegido'] = $personaRequisito['nombres_elegido'];
				$personas[$personaRequisito['persona']]['requisitos'][] = $personaRequisito['requisito_nombre'];
			}

			$buscar = array("%nombre del alumno%", "%Lista de requisitos%");
			foreach ($personas as $id => $persona) {

				$reemplazo = array($persona['nombres_elegido'], implode(", ", $persona['requisitos']));
				$cuerpo = str_replace($buscar, $reemplazo, $plantillaTexto);
				// Se inserta el destinatario en la tabla 'men_destinatarios'.
				guarani::act('act_mensajes')->set_mensajes_men_destinatarios($mensaje, $id, $cuerpo);

				$this->encolar_email($plantillaTitulo, $cuerpo, $id, $emailsPersonas);
			}
			// Si sale bien cierro la transacción
			$this->cerrar_transaccion();
		} catch (Exception $e) {
			// Si falla aborto la transacción
			$this->abortar_transaccion();
			$this->manejar_excepcion($e);
			$msj_error = guarani_mensajes::get($this->indx_msj_error);
			throw new guarani_error($msj_error);
		}
	}

	/**
	 * Notifica al alumno que realizo la solicitud al momento que la misma es impresa o rechazada.
	 *
	 * @see https://repositorio.siu.edu.ar/trac/guarani/wiki/Casos_de_Uso/Administrar_Solicitudes Caso de Uso.
	 *
	 * @param int $id_solicitud Número de solicitud de constancia.
	 *
	 * @throws guarani_error Si ocurre algún error durante el proceso.
	 */
	protected function administrar_solicitudes($id_solicitud)
	{
		
		$parametros['id_solicitud'] = $id_solicitud;
		$obligatorios = ['id_solicitud'];
		if (!$this->validar_parametros_faltantes($parametros, $obligatorios)) {
			return false;
		}

		try {

			$solicitud = toba::consulta_php('co_constancias')->get_datos_solicitud($id_solicitud);

			// Atributos para resolver valor de parámetros
			$propuestas = array($solicitud['propuesta']);
			$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($propuestas);

			$con_notifica_estado_solicitud = guarani::parametros()->get_valor(constancias::con_notifica_estado_solicitud, $ras, false);
			// Si no se debe enviar notificaciones salgo
			if ($con_notifica_estado_solicitud == comunes::no) {
				return true;
			}
			// Si la solicitud fue impresa
			if ($solicitud['estado'] == constancias::estado_solicitud_impreso) {
				$plantilla = toba::consulta_php('co_mensajes')->get_plantilla(13);
			} elseif ($solicitud['estado'] == constancias::estado_solicitud_rechazado) {
				// Si la solicitud fue rechazada
				$plantilla = toba::consulta_php('co_mensajes')->get_plantilla(14);
			} else {
				// Si el estado es otro salgo
				return true;
			}

			$plantillaTitulo = $plantilla['titulo'];
			$plantillaTexto = $plantilla['texto'];

			$personaId = $solicitud['persona'];

			$personasIds = array($personaId);
			$emailsPersonas = toba::consulta_php('co_personas')->get_emails_personas($personasIds);

			$titulo = str_replace("%constancia%", $solicitud['constancia_nombre'], $plantillaTitulo);

			$buscar = array("%nombre del alumno%", "%constancia%");
			$reemplazo = array($solicitud['nombres_elegido'], $solicitud['constancia_nombre']);
			$cuerpo = str_replace($buscar, $reemplazo, $plantillaTexto);

			// Abro una transacción
			$this->abrir_transaccion();

			// Se inserta el mensaje en la tabla 'men_mensajes'.
			$mensaje = guarani::act('act_mensajes')->set_mensajes_men_mensajes_personalizado($titulo, static::tipo_mensaje_constancias_y_certificados);

			// Se inserta el destinatario en la tabla 'men_destinatarios'.
			guarani::act('act_mensajes')->set_mensajes_men_destinatarios($mensaje, $personaId, $cuerpo);

			$this->encolar_email($titulo, $cuerpo, $personaId, $emailsPersonas);

			// Si sale bien cierro la transacción
			$this->cerrar_transaccion();
		} catch (Exception $e) {
			// Si falla aborto la transacción
			$this->abortar_transaccion();
			$this->manejar_excepcion($e);
			$msj_error = guarani_mensajes::get($this->indx_msj_error);
			throw new guarani_error($msj_error);
		}
	}

	/**
	 * Notifica a los alumnos que se emitió una constancia de curso.
	 *
	 * @see https://repositorio.siu.edu.ar/trac/guarani/wiki/Casos_de_Uso/Emitir_Certificados_Cursos Caso de Uso.
	 *
	 * @param int[] $ids_solicitudes Números de solicitudes de constancia.
	 *
	 * @throws guarani_error Si ocurre algún error durante el proceso.
	 */
	protected function emision_certificados_cursos($ids_solicitudes)
	{

		$parametros['ids_solicitudes'] = $ids_solicitudes;
		$obligatorios = ['ids_solicitudes'];
		if (!$this->validar_parametros_faltantes($parametros, $obligatorios)) {
			return false;
		}
		
		try {
			// Si no hay solicitudes salgo
			if (empty($ids_solicitudes)) {
				return true;
			}
			$ids_solicitudes_in = implode(", ", $ids_solicitudes);
			$where = "sga_constancias_solicitud.solicitud IN ($ids_solicitudes_in)";
			$solicitudes = toba::consulta_php('co_constancias')->get_listado_const_solicitadas($where);

			$primerSolicitud = $solicitudes[0];
			// Son todas solicitudes de la misma actividad y comisión
			$actividadPrimerSolicitud = toba::consulta_php('co_constancias')->get_datos_actividad_solicitud_constancia_curso($primerSolicitud['solicitud']);

			$personasIds = array_column($solicitudes, 'persona');
			$emailsPersonas = toba::consulta_php('co_personas')->get_emails_personas($personasIds);

			$plantillaConDescarga = toba::consulta_php('co_mensajes')->get_plantilla(15);
			$plantillaTituloConDescarga = $plantillaConDescarga['titulo'];
			$tituloConDescarga = str_replace("%nombre materia%", $actividadPrimerSolicitud['nombre'], $plantillaTituloConDescarga);
			$plantillaTextoConDescarga = $plantillaConDescarga['texto'];
			$cuerpoConDescarga = str_replace("%nombre de la actividad%", $actividadPrimerSolicitud['nombre'], $plantillaTextoConDescarga);

			$plantillaSinDescarga = toba::consulta_php('co_mensajes')->get_plantilla(16);
			$plantillaTituloSinDescarga = $plantillaSinDescarga['titulo'];
			$tituloSinDescarga = str_replace("%nombre materia%", $actividadPrimerSolicitud['nombre'], $plantillaTituloSinDescarga);
			$plantillaTextoSinDescarga = $plantillaSinDescarga['texto'];
			$cuerpoSinDescarga = str_replace("%nombre de la actividad%", $actividadPrimerSolicitud['nombre'], $plantillaTextoSinDescarga);

			$fecha_hoy = new DateTime('today');

			// Abro una transacción
			$this->abrir_transaccion();

			foreach ($solicitudes as $solicitud) {

				$propuestas = array($solicitud['propuesta']);
				$con_notifica_certificado_curso = guarani::parametros()->get_valor(constancias::con_notifica_certificado_curso, $propuestas, false);
				// Si no se debe enviar notificaciones sigo con la siguiente solicitud
				if ($con_notifica_certificado_curso == comunes::no) {
					continue;
				}
				$certificado_vencido = false;

				if ($solicitud['fecha_fin_vigencia']) {
					$fecha_fin_vigencia = DateTime::createFromFormat('!Y-m-d', $solicitud['fecha_fin_vigencia']);

					if ($fecha_fin_vigencia < $fecha_hoy) {
						$certificado_vencido = true;
					}
				}

				// Si permite descarga y la interfaz es autogestion y el estado es Online y no esta vencido se puede descargar...
				$permiteDescarga = ($solicitud['permite_descargar'] && ($solicitud['interfaz'] == comunes::interfaz_autogestion) && ($solicitud['estado'] == constancias::estado_solicitud_online) && (!$certificado_vencido));

				// Si permite descarga
				if ($permiteDescarga) {
					$tituloSolicitud = $tituloConDescarga;
					$cuerpoSolicitud = str_replace("%nombre del alumno%", $solicitud['nombres_elegido'], $cuerpoConDescarga);
				} else {
					// Si NO permite descarga
					$tituloSolicitud = $tituloSinDescarga;
					$cuerpoSolicitud = str_replace("%nombre del alumno%", $solicitud['nombres_elegido'], $cuerpoSinDescarga);
				}

				// Se inserta el mensaje en la tabla 'men_mensajes'.
				$mensaje = guarani::act('act_mensajes')->set_mensajes_men_mensajes_personalizado($tituloSolicitud, static::tipo_mensaje_constancias_y_certificados);

				// Se inserta el destinatario en la tabla 'men_destinatarios'.
				guarani::act('act_mensajes')->set_mensajes_men_destinatarios($mensaje, $solicitud['persona'], $cuerpoSolicitud);

				$this->encolar_email($tituloSolicitud, $cuerpoSolicitud, $solicitud['persona'], $emailsPersonas);
			}

			// Si sale bien cierro la transacción
			$this->cerrar_transaccion();
		} catch (Exception $e) {
			// Si falla aborto la transacción
			$this->abortar_transaccion();
			$this->manejar_excepcion($e);
			$msj_error = guarani_mensajes::get($this->indx_msj_error);
			throw new guarani_error($msj_error);
		}
	}

	protected function enviar_adeudan_requisitos($dominios)
	{
		try {
			// Alumnos que adeudan requisitos de ingreso
			$alumnos = toba::consulta_php('co_mensajes')->get_alumnos_adeudan_requisitos($dominios);

			// Si no hay alumnos para notificar salgo
			if (empty($alumnos)) {
				return true;
			}
			$personasIds = array_column($alumnos, 'persona');
			$emailsPersonas = toba::consulta_php('co_personas')->get_emails_personas($personasIds);
			$nombresPersonas = toba::consulta_php('co_personas')->get_apellido_nombres_personas($personasIds);

			$plantilla = toba::consulta_php('co_mensajes')->get_plantilla(7);

			$plantillaTitulo = $plantilla['titulo'];
			$plantillaTexto = $plantilla['texto'];

			// Abro una transacción
			$this->abrir_transaccion();

			// Se inserta el mensaje en la tabla 'men_mensajes'.
			$mensaje = guarani::act('act_mensajes')->set_mensajes_men_mensajes_personalizado($plantillaTitulo, static::tipo_mensaje_general, mensajes::alumnos_adeudan_requisitos, comunes::si);

			foreach ($dominios as $dominio) {
				//Se desetea la columna que usa toba porque no forma parte de la tabla men_dominio
				unset($dominio['x_dbr_clave']);
				$columnas = implode(',', array_keys($dominio));
				$valores = implode(', ', array_map(function($n) {
							return toba::db()->quote($n);
						}, array_values($dominio)));
				guarani::act('act_mensajes')->insertar_men_dominio($mensaje, $columnas, $valores);
			}

			$buscar = array("%nombre del alumno%", "%Lista de requisitos%");
			foreach ($alumnos as $alumno) {
				$persona = $alumno['persona'];
				$requisitos = aplanar_matriz(toba::consulta_php('co_requisitos_ingreso')->get_requisitos_persona_adeudados($persona), 'requisito');
				$lista_requisitos = implode(', ', $requisitos);

				$reemplazo = array($nombresPersonas[$persona]['nombres'], $lista_requisitos);
				$cuerpo = str_replace($buscar, $reemplazo, $plantillaTexto);
				// Se inserta el destinatario en la tabla 'men_destinatarios'.
				guarani::act('act_mensajes')->set_mensajes_men_destinatarios($mensaje, $persona, $cuerpo);

				$this->encolar_email($plantillaTitulo, $cuerpo, $persona, $emailsPersonas);
			}
			// Si sale bien cierro la transacción
			$this->cerrar_transaccion();
		} catch (Exception $e) {
			// Si falla aborto la transacción
			$this->abortar_transaccion();
			$this->manejar_excepcion($e);
			$msj_error = guarani_mensajes::get($this->indx_msj_error);
			throw new guarani_error($msj_error);
		}
	}

	
	protected function inscripciones_pendientes_a_examenes($parametros) {
		
		$obligatorios = ['aceptada', 'actividad', 'alumno', 'fecha'];
		if (!$this->validar_parametros_faltantes($parametros, $obligatorios)) {
			return false;
		}
		
		if($parametros['aceptada']) {
			$plantilla = toba::consulta_php('co_mensajes')->get_plantilla(19);
		} else {
			$plantilla = toba::consulta_php('co_mensajes')->get_plantilla(20);
		}
		$titulo = str_replace("%nombre actividad%", $parametros['actividad'], $plantilla['titulo']);
		$datos_alumno = toba::consulta_php('co_alumnos')->get_datos_alumno($parametros['alumno']);
		$fecha = explode( ' ', $parametros['fecha']);
		$buscar = array("%nombre del alumno%", "%fecha mesa%", "%nombre actividad%");
		$reemplazo = array($datos_alumno[0]['nombres'], $fecha[0], $parametros['actividad']);
		$cuerpo = str_replace($buscar, $reemplazo, $plantilla['texto']);
		$mensaje = guarani::act('act_mensajes')->set_mensajes_men_mensajes_personalizado($titulo, static::tipo_mensaje_inscripciones);
		guarani::act('act_mensajes')->set_mensajes_men_destinatarios($mensaje, $datos_alumno[0]['persona'], $cuerpo);
		$emailsPersonas = toba::consulta_php('co_personas')->get_emails_personas( Array($datos_alumno[0]['persona']) );
		$this->encolar_email($titulo, $cuerpo, $datos_alumno[0]['persona'], $emailsPersonas);
	}
	
	protected function inscribir_a_cursadas_3w($parametros) {
		
		$obligatorios = ['estado', 'nombre_materia', 'nombre_alumno', 'nombre_comision', 'persona', 'email'];
		if (!$this->validar_parametros_faltantes($parametros, $obligatorios)) {
			return false;
		}
		
		if($parametros['estado'] == comunes::estado_aceptado) {
			$plantilla = toba::consulta_php('co_mensajes')->get_plantilla(17);
		}
		else if($parametros['estado'] == comunes::estado_pendiente) {
			$plantilla = toba::consulta_php('co_mensajes')->get_plantilla(23);
		}
		else if($parametros['estado'] == comunes::estado_baja) {
			$plantilla = toba::consulta_php('co_mensajes')->get_plantilla(21);
		}
			
		$titulo = str_replace("%nombre actividad%", $parametros['nombre_materia'], $plantilla['titulo']);
		
		$buscar = array("%nombre del alumno%", "%nombre comision%", "%nombre actividad%");
		$reemplazo = array($parametros['nombre_alumno'], $parametros['nombre_comision'], $parametros['nombre_materia']);
		$cuerpo = str_replace($buscar, $reemplazo, $plantilla['texto']);
		
		$mensaje = guarani::act('act_mensajes')->set_mensajes_men_mensajes_personalizado($titulo, static::tipo_mensaje_inscripciones);
		guarani::act('act_mensajes')->set_mensajes_men_destinatarios($mensaje, $parametros['persona'], $cuerpo);
		$this->encolar_email($titulo, $cuerpo, $parametros['persona'], $parametros['email']);
	}
	
	protected function inscribir_a_examenes_3w($parametros) {
		
		$obligatorios = ['estado', 'fecha_mesa', 'nombre_actividad', 'nombre_alumno', 'persona', 'email'];
		if (!$this->validar_parametros_faltantes($parametros, $obligatorios)) {
			return false;
		}
		
		if($parametros['estado'] == comunes::estado_aceptado) {
			$plantilla = toba::consulta_php('co_mensajes')->get_plantilla(19);
		}
		else if($parametros['estado'] == comunes::estado_pendiente) {
			$plantilla = toba::consulta_php('co_mensajes')->get_plantilla(24);
		}
		else if($parametros['estado'] == comunes::estado_baja) {
			$plantilla = toba::consulta_php('co_mensajes')->get_plantilla(22);
		}
			
		$titulo = str_replace("%nombre actividad%", $parametros['nombre_actividad'], $plantilla['titulo']);
		
		$buscar = array("%nombre del alumno%", "%fecha mesa%", "%nombre actividad%");
		$reemplazo = array($parametros['nombre_alumno'], $parametros['fecha_mesa'], $parametros['nombre_actividad']);
		$cuerpo = str_replace($buscar, $reemplazo, $plantilla['texto']);

		$mensaje = guarani::act('act_mensajes')->set_mensajes_men_mensajes_personalizado($titulo, static::tipo_mensaje_inscripciones);
		guarani::act('act_mensajes')->set_mensajes_men_destinatarios($mensaje, $parametros['persona'], $cuerpo);
		$this->encolar_email($titulo, $cuerpo, $parametros['persona'], $parametros['email']);
	}
        
	protected function inscribir_a_actividad($parametros) {
		
		$obligatorios = ['estado', 'nombre_actividad', 'nombre_alumno', 'nombre_comision', 'nombre_actividad', 'persona', 'email'];
		if (!$this->validar_parametros_faltantes($parametros, $obligatorios)) {
			return false;
		}
		
		switch($parametros['estado']) {
			case comunes::resultado_inscripcion_aceptada: $plantilla = toba::consulta_php('co_mensajes')->get_plantilla(17); break;
			case comunes::resultado_inscripcion_rechazada: $plantilla = toba::consulta_php('co_mensajes')->get_plantilla(18); break;
			case comunes::resultado_inscripcion_pendiente: $plantilla = toba::consulta_php('co_mensajes')->get_plantilla(23); break;
		}
		$titulo = str_replace("%nombre actividad%", $parametros['nombre_actividad'], $plantilla['titulo']);
		$buscar = array("%nombre del alumno%", "%nombre comision%", "%nombre actividad%");
		$reemplazo = array($parametros['nombre_alumno'], $parametros['nombre_comision'], $parametros['nombre_actividad']);
		$cuerpo = str_replace($buscar, $reemplazo, $plantilla['texto']);
		$mensaje = guarani::act('act_mensajes')->set_mensajes_men_mensajes_personalizado($titulo, static::tipo_mensaje_inscripciones);
		guarani::act('act_mensajes')->set_mensajes_men_destinatarios($mensaje, $parametros['persona'], $cuerpo);
		$this->encolar_email($titulo, $cuerpo, $parametros['persona'], $parametros['email']);
	}
        
	protected function inscribir_a_examenes($parametros) {
		
		$obligatorios = ['estado', 'nombre_actividad', 'nombre_alumno', 'fecha_mesa', 'nombre_actividad', 'persona', 'email'];
		if (!$this->validar_parametros_faltantes($parametros, $obligatorios)) {
			return false;
		}
		
		
		if($parametros['estado'] == comunes::estado_aceptado) {
			$plantilla = toba::consulta_php('co_mensajes')->get_plantilla(19);
		}
		else if($parametros['estado'] == comunes::estado_pendiente) {
			$plantilla = toba::consulta_php('co_mensajes')->get_plantilla(24);
		}
		$titulo = str_replace("%nombre actividad%", $parametros['nombre_actividad'], $plantilla['titulo']);
		$buscar = array("%nombre del alumno%", "%fecha mesa%", "%nombre actividad%");
		$reemplazo = array($parametros['nombre_alumno'], $parametros['fecha_mesa'], $parametros['nombre_actividad']);
		$cuerpo = str_replace($buscar, $reemplazo, $plantilla['texto']);
		$mensaje = guarani::act('act_mensajes')->set_mensajes_men_mensajes_personalizado($titulo, static::tipo_mensaje_inscripciones);
		guarani::act('act_mensajes')->set_mensajes_men_destinatarios($mensaje, $parametros['persona'], $cuerpo);
		$this->encolar_email($titulo, $cuerpo, $parametros['persona'], $parametros['email']);
	}
	
	protected function distribuir_comisiones($parametros) {
		
		$obligatorios = ['nombre_actividad', 'nombre_alumno', 'nombre_comision_origen', 'nombre_actividad', 'nombre_comision_destino', 'persona', 'email'];
		if (!$this->validar_parametros_faltantes($parametros, $obligatorios)) {
			return false;
		}
		
		$plantilla = toba::consulta_php('co_mensajes')->get_plantilla(25);
		$titulo = str_replace("%nombre actividad%", $parametros['nombre_actividad'], $plantilla['titulo']);
		$buscar = array("%nombre del alumno%", "%nombre comision origen%", "%nombre actividad%", "%nombre comision destino%");
		$reemplazo = array($parametros['nombre_alumno'], $parametros['nombre_comision_origen'], $parametros['nombre_actividad'], $parametros['nombre_comision_destino']);
		$cuerpo = str_replace($buscar, $reemplazo, $plantilla['texto']);
		$mensaje = guarani::act('act_mensajes')->set_mensajes_men_mensajes_personalizado($titulo, static::tipo_mensaje_inscripciones);
		guarani::act('act_mensajes')->set_mensajes_men_destinatarios($mensaje, $parametros['persona'], $cuerpo);
		$this->encolar_email($titulo, $cuerpo, $parametros['persona'], $parametros['email']);
	}
	
	protected function eliminar_inscripcion_cursada($parametros) {
		
		$obligatorios = ['nombre_actividad', 'nombre_alumno', 'nombre_comision', 'nombre_actividad', 'persona', 'email'];
		if (!$this->validar_parametros_faltantes($parametros, $obligatorios)) {
			return false;
		}
		
		$plantilla = toba::consulta_php('co_mensajes')->get_plantilla(21);
		$titulo = str_replace("%nombre actividad%", $parametros['nombre_actividad'], $plantilla['titulo']);
		$buscar = array("%nombre del alumno%", "%nombre comision%", "%nombre actividad%");
		$reemplazo = array($parametros['nombre_alumno'], $parametros['nombre_comision'], $parametros['nombre_actividad']);
		$cuerpo = str_replace($buscar, $reemplazo, $plantilla['texto']);
		$mensaje = guarani::act('act_mensajes')->set_mensajes_men_mensajes_personalizado($titulo, static::tipo_mensaje_inscripciones);
		guarani::act('act_mensajes')->set_mensajes_men_destinatarios($mensaje, $parametros['persona'], $cuerpo);
		$this->encolar_email($titulo, $cuerpo, $parametros['persona'], $parametros['email']);
	}

	protected function eliminar_inscripcion_examen($parametros) {
		
		$obligatorios = ['nombre_actividad', 'nombre_alumno', 'fecha_mesa', 'nombre_actividad', 'persona', 'email'];
		if (!$this->validar_parametros_faltantes($parametros, $obligatorios)) {
			return false;
		}
		
		$plantilla = toba::consulta_php('co_mensajes')->get_plantilla(22);
		$titulo = str_replace("%nombre actividad%", $parametros['nombre_actividad'], $plantilla['titulo']);
		$buscar = array("%nombre del alumno%", "%fecha mesa%", "%nombre actividad%");
		$reemplazo = array($parametros['nombre_alumno'], $parametros['fecha_mesa'], $parametros['nombre_actividad']);
		$cuerpo = str_replace($buscar, $reemplazo, $plantilla['texto']);
		$mensaje = guarani::act('act_mensajes')->set_mensajes_men_mensajes_personalizado($titulo, static::tipo_mensaje_inscripciones);
		guarani::act('act_mensajes')->set_mensajes_men_destinatarios($mensaje, $parametros['persona'], $cuerpo);
		$this->encolar_email($titulo, $cuerpo, $parametros['persona'], $parametros['email']);
	}

    protected function enviar_inscripcion_propuesta($parametros)
    {
        $obligatorios = ['email', 'nombre_propuesta', 'nombre_alumno', 'usuario', 'link_autogestion', 'persona'];
		if (!$this->validar_parametros_faltantes($parametros, $obligatorios)) {
			return false;
		}        
        $plantilla = toba::consulta_php('co_mensajes')->get_plantilla(27);
		$buscar = array("%nombre alumno%", "%nombre propuesta%", "%usuario%", "%link autogestion%");
		$reemplazo = array($parametros['nombre_alumno'], $parametros['nombre_propuesta'], $parametros['usuario'], $parametros['link_autogestion']);
		$cuerpo = str_replace($buscar, $reemplazo, $plantilla['texto']);                
		$mensaje = guarani::act('act_mensajes')->set_mensajes_men_mensajes_personalizado($plantilla['titulo'], static::tipo_mensaje_inscripciones);
		guarani::act('act_mensajes')->set_mensajes_men_destinatarios($mensaje, $parametros['persona'], $cuerpo);
		$this->encolar_email($plantilla['titulo'], $cuerpo, $parametros['persona'], $parametros['email']);
    }
    
    /**
     * La siguiente operacion se utiliza para enviar mail masivos a los alumnos que se encuentran
     * en condiciones de cursar o rendir, el ticket asociado a esta operacion es #22860
     * @param type $parametros
     * @return boolean
     */
    protected function enviar_alumnos_para_cursar_o_rendir($parametros)
    {
        $obligatorios = ['nombre_actividad', 'estado', 'email','nombre_alumno','persona','nombre_propuesta'];
		if (!$this->validar_parametros_faltantes($parametros, $obligatorios)) {
			return false;
		}
        
        $plantilla = toba::consulta_php('co_mensajes')->get_plantilla(28);
		$buscar = array("%nombre alumno%", "%nombre propuesta%", "%estado%","%nombre actividad%");
		$reemplazo = array($parametros['nombre_alumno'], $parametros['nombre_propuesta'], $parametros['estado'], $parametros['nombre_actividad']);
		$cuerpo = str_replace($buscar, $reemplazo, $plantilla['texto']);                
		$asuntoEmail = str_replace($buscar, $reemplazo, $plantilla['titulo']);                
		$mensaje = guarani::act('act_mensajes')->set_mensajes_men_mensajes_personalizado($plantilla['titulo'], static::tipo_mensaje_general);
		guarani::act('act_mensajes')->set_mensajes_men_destinatarios($mensaje, $parametros['persona'], $cuerpo);
        $this->encolar_email_masivo($asuntoEmail, $cuerpo, $parametros['persona']);
    }
	
    /**
     * La siguiente operacion se utiliza para enviar mail al alumno notificandole de una sanción
     * @param type $parametros
     * @return boolean
     */
    protected function enviar_notificacion_sancion_alumno($parametros)
    {
        $obligatorios = ['nombres_elegido', 'fecha_desde', 'fecha_hasta','email'];
		if (!$this->validar_parametros_faltantes($parametros, $obligatorios)) {
			return false;
		}
        
        $plantilla = toba::consulta_php('co_mensajes')->get_plantilla(static::notificar_sancion_alumno);
		$buscar = array("%nombre alumno%", "%fecha desde%", "%al fecha hasta%");
		$reemplazo = array($parametros['nombres_elegido'], $parametros['fecha_desde'], $parametros['fecha_hasta']);
		$cuerpo = str_replace($buscar, $reemplazo, $plantilla['texto']);                
		$asuntoEmail = str_replace($buscar, $reemplazo, $plantilla['titulo']);                
		$mensaje = guarani::act('act_mensajes')->set_mensajes_men_mensajes_personalizado($plantilla['titulo'], static::tipo_mensaje_general);
		guarani::act('act_mensajes')->set_mensajes_men_destinatarios($mensaje, $parametros['persona'], $cuerpo);		
		$this->encolar_email($asuntoEmail, $cuerpo, $parametros['persona'],$parametros['email']);
    }
	
    /**
     * La siguiente operacion se utiliza para enviar mail a cada uno de los docentes de las mesas 
	 * y comisiones vigentes donde se encuentre el alumno sancionado.
     * @param type $parametros
     * @return boolean
     */
    protected function enviar_notificacion_sancion_docente($parametros)
    {
        $obligatorios = ['nombres_elegido', 'fecha_desde', 'fecha_hasta','email','nombres_elegido_alumno'];
		if (!$this->validar_parametros_faltantes($parametros, $obligatorios)) {
			return false;
		}
        
        $plantilla = toba::consulta_php('co_mensajes')->get_plantilla(static::notificar_sancion_docente);
		$buscar = array("%nombre docente%","%nombre alumno%", "%fecha desde%", "%al fecha hasta%");
		$reemplazo = array($parametros['nombres_elegido'],$parametros['nombres_elegido_alumno'], $parametros['fecha_desde'], $parametros['fecha_hasta']);
		$cuerpo = str_replace($buscar, $reemplazo, $plantilla['texto']);                
		$asuntoEmail = str_replace($buscar, $reemplazo, $plantilla['titulo']);                
		$mensaje = guarani::act('act_mensajes')->set_mensajes_men_mensajes_personalizado($plantilla['titulo'], static::tipo_mensaje_general);
		guarani::act('act_mensajes')->set_mensajes_men_destinatarios($mensaje, $parametros['persona'], $cuerpo);
		$this->encolar_email($asuntoEmail, $cuerpo, $parametros['persona'],$parametros['email']);
    }

    /**
     * La siguiente operación se utiliza para enviar una notificación a los aspirantes a becas con reaperturas en la convocatoria.
     * @param array $parametros
     * @return boolean
     */
    protected function notificar_reapertura_convocatoria_beca($parametros)
    {
        $obligatorios = ['nombres_elegido_alumno', 'nombre_convocatoria', 'fecha_hasta', 'observaciones', 'email', 'datos_censales', 'documentos'];
        if (!$this->validar_parametros_faltantes($parametros, $obligatorios)) {
            return false;
        }

        $datos_censales = "";
        // Si hay datos censales a revisar
        if (!empty($parametros['datos_censales'])) {
            $etiquetas_datos_censales = \toba::consulta_php('co_datos_censales')->get_etiquetas_datos_censales($parametros['datos_censales']);
            $datos_censales .= "<h3>Datos censales a revisar:</h3>";
            $datos_censales .= "<ul>";
            foreach ($etiquetas_datos_censales as $etiqueta) {
                $datos_censales .= "<li>{$etiqueta['etiqueta']}</li>";
            }
            $datos_censales .= "</ul>";
        }

        $documentos = "";
        // Si hay documentos a revisar
        if (!empty($parametros['documentos'])) {
            $datos_requisito = \toba::consulta_php('co_requisitos')->get_datos_requisito($parametros['documentos']);
            $documentos .= "<h3>Documentos a revisar:</h3>";
            $documentos .= "<ul>";
            foreach ($datos_requisito as $dato_requisito) {
                $documentos .= "<li>{$dato_requisito['nombre_requisito']}</li>";
            }
            $documentos .= "</ul>";
        }
        
        $plantilla = toba::consulta_php('co_mensajes')->get_plantilla(static::notificar_reapertura_convocatoria_beca);
        $buscar = ["%nombre alumno%", "%nombre convocatoria%", "%fecha hasta%", "%observaciones%", "%datos_censales%", "%documentos%"];
        $reemplazo = [$parametros['nombres_elegido_alumno'], $parametros['nombre_convocatoria'], $parametros['fecha_hasta'], $parametros['observaciones'], $datos_censales, $documentos];
        $asuntoMensaje = str_replace($buscar, $reemplazo, $plantilla['titulo']);
        $cuerpoMensaje = str_replace($buscar, $reemplazo, $plantilla['texto']);
        $mensaje = guarani::act('act_mensajes')->set_mensajes_men_mensajes_personalizado($asuntoMensaje, static::tipo_mensaje_general);
        guarani::act('act_mensajes')->set_mensajes_men_destinatarios($mensaje, $parametros['persona'], $cuerpoMensaje);
        $this->encolar_email($asuntoMensaje, $cuerpoMensaje, $parametros['persona'], $parametros['email']);
    }

    /**
     * La siguiente operación se utiliza para enviar una notificación a los alumnos con reapertura de trámite de egreso.
	 * 
     * @param array $parametros
     * @return boolean
     */
    protected function notificar_reapertura_tramite_egreso($parametros)
    {
        $obligatorios = array('nombre_alumno', 'certificado_nombre');
        if (!$this->validar_parametros_faltantes($parametros, $obligatorios)) {
            return false;
        }
		$plantilla = toba::consulta_php('co_mensajes')->get_plantilla(static::notificar_reapertura_tramite_egreso);
        $buscar = array("%nombre alumno%", "%nombre titulo%");
        $reemplazar = array($parametros['nombre_alumno'], $parametros['certificado_nombre']);
        $asuntoMensaje = str_replace($buscar, $reemplazar, $plantilla['titulo']);
        $cuerpoMensaje = str_replace($buscar, $reemplazar, $plantilla['texto']);
        $mensaje = guarani::act('act_mensajes')->set_mensajes_men_mensajes_personalizado($asuntoMensaje, static::tipo_mensaje_general);
        guarani::act('act_mensajes')->set_mensajes_men_destinatarios($mensaje, $parametros['persona'], $cuerpoMensaje);
        $this->encolar_email($asuntoMensaje, $cuerpoMensaje, $parametros['persona'], $parametros['email']);
    }

	/**
	 * Envía mensaje a un conjunto de personas.
	 *
	 * @see https://repositorio.siu.edu.ar/trac/guarani/wiki/Casos_de_Uso/Enviar_Mensajes Caso de Uso.
	 *
	 * @param string		$titulo Título del mensaje.
	 * @param string		$cuerpo Cuerpo del mensaje.
	 * @param int			$alcance Alcance del mensaje.
	 * @param int			$tipoMensaje Tipo de mensaje.
	 * @param string		$fechaDesde Fecha a partir de la cual se enviará email/mostrará mensaje (formato: yyyy-mm-dd).
	 * @param string		$fechaHasta Fecha a hasta la cual se enviará email/mostrará mensaje (formato: yyyy-mm-dd).
	 * @param array			$personas Array de personas a notificar.
	 * @param bool			$enviarEmail Si se debe enviar email o no.
	 * @param array			$adjuntos Archivos adjuntos (formato de cada adjunto: array('path' => 'valor', 'nombre' => 'valor', 'tamanio' => 'valor', 'extension' => 'valor')).
	 * @param string		$responderA Email de ReplyTo.
	 * @param int			$enviarPorTwitter Si se debe enviar mensaje por Twitter (0 => No, 1 => Si).
	 * @param string		$mensajeTwitter Mensaje de Twitter.
	 * @param int			$enviarPorFacebook Si se debe enviar mensaje por Facebook (0 => No, 1 => Si).
	 * @param array			$dominios Dominios del mensaje
	 * @param char			$visibleGestion Si se usa en gestión el parámetro es 'S' y si es desde 3w es 'N'
	 * @param int			$interfaz Si es Gestión = 1 o 3w = 2.
	 * @param string|null	$remitente Remitente del mensaje para poner en el asunto del email.
	 *
	 * @throws guarani_error Si ocurre algún error durante el proceso.
	 */
	protected function enviar_mensaje($titulo, $cuerpo, $alcance, $tipoMensaje, $fechaDesde, $fechaHasta, $personas, $enviarEmail = true, $adjuntos = [], $responderA = null, $enviarPorTwitter = 0, $mensajeTwitter = '', $enviarPorFacebook = 0, $dominios = [], $visibleGestion = comunes::no, $interfaz = comunes::interfaz_gestion, $remitente = null)
	{

		try {
			
			//Trunco los valores que pueden ser muy extensos para la base.
		
			$titulo = mb_strimwidth($titulo, 0, 100, '...');
			$mensajeTwitter = mb_strimwidth($mensajeTwitter, 0, 140, '...');
			$responderA = mb_strimwidth($responderA, 0, 100, '...');

			// Si no hay personas para notificar salgo
			if (empty($personas)) {
				return true;
			}
			// Abro una transacción
			$this->abrir_transaccion();
			
			//Trunco los valores que pueden ser muy extensos para la base.
		
			$titulo = mb_strimwidth($titulo, 0, 100, '...');
			$mensajeTwitter = mb_strimwidth($mensajeTwitter, 0, 140, '...');
			$responderA = mb_strimwidth($responderA, 0, 100, '...');

			// Se inserta el mensaje en la tabla 'men_mensajes'.
			$mensaje = guarani::act('act_mensajes')->set_mensajes_men_mensajes_no_personalizado($titulo, $tipoMensaje, $cuerpo, $alcance, $fechaDesde, $fechaHasta, $enviarEmail, $responderA, $enviarPorTwitter, $mensajeTwitter, $enviarPorFacebook, $visibleGestion, $interfaz);

			// Guardo los archivos adjuntos en la DB
			foreach ($adjuntos as $adjunto) {
				// Guardo el adjunto en la tabla 'rep_archivos'.
				$id_archivo = guarani::act('act_mensajes')->insertar_rep_archivo($adjunto['path'], $adjunto['nombre'], $adjunto['tamanio'], $adjunto['extension']);
				// Asocio el adjunto al mensaje en la tabla 'men_documentos'.
				guarani::act('act_mensajes')->insertar_men_documentos($mensaje, $id_archivo);
			}


			foreach ($personas as $persona) {
				// Se inserta el destinatario en la tabla 'men_destinatarios'.
				guarani::act('act_mensajes')->set_mensajes_men_destinatarios($mensaje, $persona);
			}

			foreach ($dominios as $dominio) {
				//Se desetea la columna que usa toba porque no forma parte de la tabla men_dominio
				unset($dominio['x_dbr_clave']);
				$columnas = implode(',', array_keys($dominio));
				$valores = implode(', ', array_map(function($n) {
							return toba::db()->quote($n);
						}, array_values($dominio)));
				guarani::act('act_mensajes')->insertar_men_dominio($mensaje, $columnas, $valores);
			}

			// Si se debe enviar emails los encolo
			if ($enviarEmail) {
				
				// Si viene remitente se lo concateno al asunto del email.
				if (!is_null($remitente)) {
					$titulo .= " ".$remitente;
				}
				
				$this->encolar_email_masivo($titulo, $cuerpo, $personas, $adjuntos, $fechaDesde, $responderA);
			}

			// Si sale bien cierro la transacción
			$this->cerrar_transaccion();
		} catch (Exception $e) {
			// Si falla aborto la transacción
			$this->abortar_transaccion();
			$this->manejar_excepcion($e);
			$msj_error = guarani_mensajes::get($this->indx_msj_error);
			throw new guarani_error($msj_error);
		}
	}

	protected function encolar_email($asuntoEmail, $cuerpoEmail, $persona, $emailsPersonas, $adjuntos = array(), $fechaDesde = null, $responderA = null)
	{
                // Envío emails dependiendo del parámetro
                if(guarani::parametros()->get_valor(notificador::notificaciones_envia_mails, null, false) == comunes::no ) {
                    return false;
                }                
				       
                // Si la persona no desea recibir emails o no tiene email seteado salgo
		if ((!isset($emailsPersonas[$persona])) || (trim($emailsPersonas[$persona]['email']) == '')) {
			return false;
		}

		$email = trim($emailsPersonas[$persona]['email']);

		$mailJob = new mail_job($fechaDesde, $responderA);
		$mailJob->AddAddress($email);
		$mailJob->Subject = $asuntoEmail;
		$mailJob->Body = $cuerpoEmail;
		
		// Si hay adjuntos los agrego al email
		foreach ($adjuntos as $adjunto) {
			$mailJob->agregarAdjunto($adjunto['path'], "{$adjunto['nombre']}.{$adjunto['extension']}");
		}

		$mailJob->sendLater();
	}

	protected function encolar_email_masivo($asuntoEmail, $cuerpoEmail, $personas, $adjuntos = array(), $fechaDesde = null, $responderA = null)
	{
		// Es la cantidad máxima de destinatarios que puede haber en un email (0 para ilimitado)
		$cant_max_destinatarios_por_email = guarani::parametros()->get_valor('email_cant_max_destinatarios', null, false);

		// Traigo los emails de las personas
		$emails_personas = toba::consulta_php('co_personas')->get_emails_personas($personas);
		// Me quedo solo con la columna 'email' trimeada
		$emails_personas = array_map(function($email_persona) {
			return trim($email_persona['email']);
		}, $emails_personas);
		// Filtro los emails vacíos
		$emails = array_filter($emails_personas, function($email) {
			return $email != '';
		});
		// Filtro los emails duplicados
		$emails = array_unique($emails);

		$bloques_destinatarios = array();
		// Se envía un solo email con todos los destinatarios
		if ($cant_max_destinatarios_por_email == 0) {
			$bloques_destinatarios[0] = $emails;
		}
		// Se envían varios emails paginados por el valor de $cant_max_destinatarios_por_email
		else {
			$bloques_destinatarios = array_chunk($emails, $cant_max_destinatarios_por_email);
		}

		$mailJob = new mail_job($fechaDesde, $responderA);
		$mailJob->Subject = $asuntoEmail;
		$mailJob->Body = $cuerpoEmail;

		// Si hay adjuntos los agrego al email
		foreach ($adjuntos as $adjunto) {
			$mailJob->agregarAdjunto($adjunto['path'], "{$adjunto['nombre']}.{$adjunto['extension']}");
		}

		foreach ($bloques_destinatarios as $bloque_destinatarios) {

			// Clono el objeto 'mail_job'
			$mailJobBloque = clone $mailJob;

			foreach ($bloque_destinatarios as $destinatario) {
				// Agrego a los destinatarios como copia oculta
				$mailJobBloque->AddBCC($destinatario);
			}

			// Encolo el email para enviar luego
			$mailJobBloque->sendLater();

			// Destruyo el objeto clonado
			unset($mailJobBloque);
		}
	}

	protected function manejar_excepcion(Exception $e)
	{
		toba::logger()->error("Error notificador: " . $e->getMessage());
		toba::logger()->error("Archivo: " . $e->getFile() . ", Línea: " . $e->getLine());
		toba::logger()->error("Traza: " . $e->getTraceAsString());
	}
	
	/**
	 * Valida los parámetros faltantes.
	 * @param mixed[] $parametros ARRAY de parámetros a validar.
	 * @param string[] $obligatorios ARRAY con los nombres de los parámetros obligatorios.
	 * @return bool TRUE si la validación es correcta, FALSE en caso contrario.
	 */
	protected function validar_parametros_faltantes($parametros, $obligatorios)
	{
		
		$faltantes = [];
		
		foreach ($obligatorios as $key => $obligatorio) {
			
			if (!isset($parametros[$obligatorio]) || (isset($parametros[$obligatorio]) && is_null($parametros[$obligatorio]))) {
				$faltantes[] = $obligatorio;
			}
			
		}
		
		if (!empty($faltantes)) {
			$msj_error = guarani_mensajes::get('800_notificador_parametros_faltantes', [implode(", ", $faltantes)]);
			toba::logger()->error($msj_error);
			return false;
		}

		return true;
	}

}
