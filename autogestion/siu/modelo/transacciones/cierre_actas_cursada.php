<?php

namespace siu\modelo\transacciones;

use SIU\Chulupi\kernel;
use siu\errores\error_guarani;
use siu\errores\error_guarani_acta_incompleta;
use siu\modelo\datos\catalogo;
use siu\modelo\entidades\parametro;
use SIU\Chulupi\util\VistaJasperreports;
use SIU\Chulupi\util\encoding;
use siu\modelo\entidades\rs_consulta;
use SIU\Chulupi\util\MailJob;
use SIU\Chulupi\util\modelo\ModeloDependiente;
use siu\modelo_g3\arai_documentos;
use siu\modelo_g3\acta;

class cierre_actas_cursada extends transaccion implements ModeloDependiente
{
	protected $operacion;
	protected $evento = '14';
	protected $cache_request_acta_cabecera = array();

	function iniciar()
	{
		
	}

	//--------------------------------------------------------------------------
	//---- INFO
	//--------------------------------------------------------------------------

	function info__actas_abiertas($seleccion = null)
	{
		$parametros = array(
			'persona' => $this->persona,
			'comision' => $seleccion['comision']
		);
		$rs = new rs_consulta('carga_notas_cursada', 'lista_actas_abiertas_docente', $parametros);
		$rs->set_columna_id('id_acta');
		$rs->agregar_entidad('comision');
		return $rs;
	}

	function info__porcentaje_carga($acta)
	{
		return catalogo::consultar('carga_notas_cursada', 'porcentaje_carga_notas', array('acta' => $acta));
	}

	function info__acta_cabecera($datos)
	{
		$parametros = array('acta' => $datos['id_acta']);
		return catalogo::consultar('carga_notas_cursada', 'acta_cabecera', $parametros);
	}

	//--------------------------------------------------------------------------
	//---- COMPROBANTE
	//--------------------------------------------------------------------------

	// Si se modifica aquí también modificar la función 'generar_pdf_acta_cursada' de la clase 'src/siu/modelo_g3/nucleo/actas/acta_nucleo.php'
	function generar_comprobante($acta)
	{
		$datos_acta = $this->info__acta_cabecera($acta);
		$propuestas = catalogo::consultar('atributos_params', 'get_propuestas_comision', array('comision' => $datos_acta['comision']));
		$ras = catalogo::consultar('atributos_params', 'get_propuestas_ras', array('propuestas' => $propuestas));

		$imprimir_ultima_version_acta_web_param = ($datos_acta['origen'] == \acta::origen_regulares) ? \acta::cur_imprimir_ultima_version_acta_regulares_web : \acta::cur_imprimir_ultima_version_acta_promo_web;

		$utiliza_acta_digital = ($datos_acta['digital'] == \comunes::si);

		/**
		 * Descarga desde Araí-Documentos.
		 * @see https://redmine.siu.edu.ar/redmine/issues/30958 Ticket #30958
		 * @see https://redmine.siu.edu.ar/redmine/issues/30957 Ticket #30957
		 * @see https://redmine.siu.edu.ar/redmine/issues/32523 Ticket #32523
		 */
		if ($utiliza_acta_digital) { 
			$fallo = false;
			$msg_error = "";

			switch ($datos_acta['estado_documento_digital']) {
				// Si falló la creación del documento en SIU-Araí.
				case \arai_documentos::ESTADO_DOCUMENTO_DIGITAL_FALLO:
					$fallo = true;
					$msg_error = kernel::traductor()->trans('impresion_actas.fallo_creacion');
					break;
				// Si aún no se creo el documento en SIU-Araí.
				case \arai_documentos::ESTADO_DOCUMENTO_DIGITAL_PENDIENTE:
					$fallo = true;
					$msg_error = kernel::traductor()->trans('impresion_actas.pendiente_creacion');
					break;
				// Si la creación del documento en SIU-Araí fue exitosa (incluye autorizados).
				case \arai_documentos::ESTADO_DOCUMENTO_DIGITAL_EXITO:
				case \arai_documentos::ESTADO_DOCUMENTO_DIGITAL_AUTORIZADO:
					// Si no hay documento de SIU-Araí asociado
					if (is_null($datos_acta['id_documento_digital'])) {
						$fallo = true;
						$msg_error = kernel::traductor()->trans('impresion_actas.documento_no_asociado');
					} else {
						return ['path' => arai_documentos::descargarDocumentoToFilesystem($datos_acta['id_documento_digital']), 'nombre_descarga' => 'archivo.pdf'];
					}
					break;
			}

			if ($fallo) {
				throw new \Exception($msg_error);
			}
		}
                
		/**
		 * Si se imprime un acta abierta se debe actualizar su versión impresa.
		 * @see https://redmine.siu.edu.ar/redmine/issues/30958 Ticket #30958
		 * @see https://redmine.siu.edu.ar/redmine/issues/30957 Ticket #30957
		 */
		$imprimir_ultima_version_acta_web = parametro::get_valor($imprimir_ultima_version_acta_web_param, $ras);
		if (!$utiliza_acta_digital && ($imprimir_ultima_version_acta_web == \comunes::si)) {
                
                    if ($datos_acta['estado'] == \acta::estado_abierta && $datos_acta['version'] != $datos_acta['version_impresa']) {
                            try {
                                    kernel::db()->abrir_transaccion();
                                    $forma_asignacion_folio_fisico = ($datos_acta['origen'] == \acta::origen_regulares) ? parametro::get_valor('cur_forma_asignacion_folio_fisico_regul', $propuestas) : parametro::get_valor('cur_forma_asignacion_folio_fisico_promo', $propuestas);
                                    // Si corresponde, se realiza la asignación de folios físicos.
                                    if (!$utiliza_acta_digital && ($forma_asignacion_folio_fisico == \acta::forma_asignacion_folio_fisico_impresion)) {
                                            if ($datos_acta['origen'] == \acta::origen_regulares) {
                                                    $carga_manual_libro = parametro::get_valor('cur_carga_manual_libro_regulares', $propuestas);
                                                    $agrega_folios_fisicos_extra = parametro::get_valor('cur_agrega_folios_fisicos_extra_regul', $propuestas);
                                            } else {
                                                    $carga_manual_libro = parametro::get_valor('cur_carga_manual_libro_promocion', $propuestas);
                                                    $agrega_folios_fisicos_extra = parametro::get_valor('cur_agrega_folios_fisicos_extra_promo', $propuestas);
                                            }
                                            $mensaje_error = \acta::asignar_folio_fisico($datos_acta, $datos_acta['cant_alumnos'], $forma_asignacion_folio_fisico, $carga_manual_libro, $agrega_folios_fisicos_extra);
                                            if (!empty($mensaje_error)) {
                                                    throw new error_guarani(kernel::traductor()->trans($mensaje_error));
                                            }
                                    }
                                    \guarani::act('act_actas')->actualizar_version_impresa($datos_acta['id_acta']);
                                    kernel::db()->cerrar_transaccion();
                            } catch (error_guarani $e) {
                                    kernel::db()->abortar_transaccion();
                                    kernel::log()->add_error($e);
                                    throw $e;
                            } catch (\guarani_error $e) {
                                    kernel::db()->abortar_transaccion();
                                    kernel::log()->add_error($e);
                                    throw new error_guarani($e->getMessage());
                            } catch (ErrorKernelDb $e) {
                                    kernel::db()->abortar_transaccion();
                                    if ($e->get_sqlstate() == 'db_23505') {
                                            $mensaje = kernel::traductor()->trans('800_cerrar_acta_error_ff');
                                            throw new error_guarani($mensaje);
                                    } else {
                                            throw $e;
                                    }
                            }
                    }
                    
                }

		$titulo = 'ACTA';

		if ($datos_acta['origen'] == \acta::origen_regulares) {
			// Acta de regulares
			$archivo = \acta::plantilla_regulares;
			$titulo .= ' DE REGULARES ';
			$instancia_aux = \comision::inst_cursar;
                        $cnt_ejemplares_param = 'cur_actas_regulares';
		} elseif ($datos_acta['origen'] == \acta::origen_promociones) {
			// Acta de promociones
			$archivo = \acta::plantilla_promocion;
			$titulo .= ' DE PROMOCIÓN ';
			$instancia_aux = \comision::inst_promocionar;
                        $cnt_ejemplares_param = 'cur_actas_promocion';
		}
                
                // Cantidad de ejemplares a imprimir.
		$nombre_parametro = $cnt_ejemplares_param;
		$nombre_parametro .= ($datos_acta['estado'] == \acta::estado_cerrada) ? '_cerradas' : '_abiertas';
		$nombre_parametro .= '_cnt_ejemplares';
		$cnt_ejemplares = parametro::get_valor($nombre_parametro, $propuestas);

		$report = new VistaJasperreports();

		// Se setea la plantilla.
		$path = estatica('guarani', 'get_path_rep_jasper', array($archivo));
		$report->set_path_reporte($path);

		$copia = ($datos_acta['version_impresa'] && $datos_acta['estado'] == \acta::estado_cerrada) ? 'ES COPIA' : '';
		$anulada = ($datos_acta['estado'] == \acta::estado_anulada) ? 'ANULADA' : '';
		//Institucion
		$institucion = current(\toba::consulta_php('co_instituciones')->get_institucion_gestionante());
		$actividad =$datos_acta['elemento'];
		$comision = $datos_acta['comision'];
		$datos = \toba::consulta_php('co_responsables_academicas')->get_ras_actividad_comision($actividad, $comision);
		$text_aux = $copia . $anulada;
		$report->set_parametro('titulo', 'S', $titulo);
		$path_logo = kernel::localizador()->encontrar_path_archivo('www/img', 'logo-transparente.png');
		$report->set_parametro('logo', 'S', $path_logo);
		$report->set_parametro('institucion', 'S', $institucion['nombre']);
		$report->set_parametro('ra_nombre', 'S', $datos['resp_acad_nombre']);
		$report->set_parametro('texto_aux', 'S', $text_aux);
		$str = (is_null($datos_acta['observaciones'])) ? '' : $datos_acta['observaciones'];
		$report->set_parametro('observaciones', 'S', $str);
		$report->set_parametro('version', 'S', $datos_acta['version']);
		$report->set_parametro('comision_nombre', 'S', $datos_acta['comision_nombre']);
		$report->set_parametro('anio_academico_periodo', 'E', $datos_acta['anio_academico_periodo']);
		$report->set_parametro('nro_acta', 'S', $datos_acta['nro_acta']);
		$report->set_parametro('codigo_y_nombre_actividad', 'S', $datos_acta['codigo_y_nombre_actividad']);
		$report->set_parametro('comision_nombre', 'S', $datos_acta['comision_nombre']);
		$report->set_parametro('periodo_nombre', 'S', $datos_acta['periodo_lectivo_nombre']);
		$report->set_parametro('nombre_ubicacion', 'S', $datos_acta['nombre_ubicacion']);
		$report->set_parametro('acta_digital', 'S', $datos_acta['digital']);

		if ($datos_acta['tipo_acta'] == \acta::tipo_rectificativa) {
			$nro_acta_rectificada = \toba::consulta_php('co_actas')->get_nro_acta_rectificada($datos_acta['acta_referencia']);
			if (!empty($nro_acta_rectificada)) {
				$report->set_parametro('acta_referencia', 'S', $nro_acta_rectificada[0]['nro_acta_rectificada']);
			}
		}
		$report->set_parametro('fecha_inicio', 'S', $datos_acta['fecha_inicio_periodo_visual']);
		$report->set_parametro('fecha_fin', 'S', $datos_acta['fecha_fin_periodo_visual']);
		$docentes = (is_null($datos_acta['docentes'])) ? '' : $datos_acta['docentes'];
		$report->set_parametro('docentes', 'S', $docentes);

		// Se agregan los datos de los alumnos.
		if (($datos_acta['estado'] == \acta::estado_anulada || $datos_acta['estado'] == \acta::estado_cerrada)) {
			$instancia = null;
		} else {
			$instancia = $instancia_aux;
		}

		$params_sistema = array(
			'cur_inserta_alumnos_no_promocionales_en_acta' => parametro::get_valor('cur_inserta_alumnos_no_promocionales_en_acta', $propuestas),
			'cur_orden_detalle_actas' => parametro::get_valor('cur_orden_detalle_actas', $ras),
		);

		$default_valor_libro_folio_folio = ($utiliza_acta_digital)? \acta::libro_no_aplica : 0;
		
		$datos_alumnos = \toba::consulta_php('co_actas')->get_alumnos_acta_cursadas($datos_acta['id_acta'], $instancia, '', '', '', false, $params_sistema);
		foreach ($datos_alumnos as $clave => $valor) {
			$datos_folio_acta = \toba::consulta_php('co_actas')->get_datos_folio_acta($datos_acta['id_acta'], $valor['folio']);
			$datos_alumnos[$clave]['folio_fisico'] = isset($datos_folio_acta['folio_fisico']) ? $datos_folio_acta['folio_fisico'] : $default_valor_libro_folio_folio;
			$datos_alumnos[$clave]['libro'] = isset($datos_folio_acta['nombre_libro']) ? $datos_folio_acta['nombre_libro'] : $default_valor_libro_folio_folio;
			$datos_alumnos[$clave]['tomo'] = isset($datos_folio_acta['nro_tomo']) ? $datos_folio_acta['nro_tomo'] : $default_valor_libro_folio_folio;
		}

		$columnas = array('renglon', 'documento_principal', 'apellido_y_nombre', 'fecha', 'cond_regularidad_nombre', 'nota_nombre', 'resultado_nombre', 'folio_fisico', 'libro', 'tomo', 'folio');
		$xml = "<tabla break-after='page'><datos>";
		foreach ($datos_alumnos as $datos_alumno) {
			$xml .= '<fila>';
			foreach ($columnas as $clave) {
				$valor = isset($datos_alumno[$clave]) ? $datos_alumno[$clave] : '';
                                // Escape a caracteres especiales xml que generan error en el parser de jasper.
                                $valor = \guarani_texto::reemplazar_caracteres_especiales_xml($valor);
                                $valor = \guarani_texto::parsear_caracteres_xml($valor);
				$xml .= '<dato clave="' . $clave . '" valor="' . $valor . '"/>';
                        }
			$xml .= '</fila>';
		}
		$xml .= '</datos></tabla>';

		$report->set_xml(encoding::e_seguro($xml));

		// Si hay que generar más de una copia se replica el reporte.
		for ($i = 0; $i < $cnt_ejemplares; $i++) {
			$report->completar_con_datos();
		}

		$report->generar_salida();
		return ['path' => $report->get_nombre_archivo_generado(), 'nombre_descarga' => 'archivo.pdf'];
	}

	//--------------------------------------------------------------------------
	//---- EVENTOS
	//--------------------------------------------------------------------------

	function evt__cerrar_acta($acta, $libro = null)
	{
		$cierre_ok = false;
		$datos_acta = $this->info__acta_cabecera($acta);
		kernel::log()->add_debug("===== Datos acta =====", $datos_acta);

		$esActaRegular = ($datos_acta['origen'] == \acta::origen_regulares);
		$esActaPromocion = ($datos_acta['origen'] == \acta::origen_promociones);
		$esActaRectificativa = $datos_acta['tipo_acta'] == \acta::tipo_rectificativa && !empty($datos_acta['acta_referencia']);

		if($esActaRegular){

			$instancia = \comision::inst_cursar;
			$cur_forma_asignacion_folio_fisico_instancia = \acta::cur_forma_asignacion_folio_fisico_regul;
			$cur_carga_manual_libro_instancia = \acta::cur_carga_manual_libro_regulares;
			$cur_agrega_folios_fisicos_extra_instancia = \acta::cur_agrega_folios_fisicos_extra_regul;
			$cur_notifica_cierre = \acta::cur_notificar_cierre_acta_regulares;
			$cur_imprimir_ultima_version_acta_web = \acta::cur_imprimir_ultima_version_acta_regulares_web;
			$cur_actas_cerradas_cnt_ejemplares = \acta::cur_actas_regulares_cerradas_cnt_ejemplares;
			$cur_actas_abiertas_cnt_ejemplares = \acta::cur_actas_regulares_abiertas_cnt_ejemplares;
			$alcance_notificador = \notificador::cerrar_acta_regulares;

			$campo_otra_instancia = 'instancia_promocion';
			$campo_otra_instancia_cerrada = 'acta_promocion_cerrada';
			$metodo_fin_vigencia = 'get_fin_vigencia_regularidad';
			
		} elseif ($esActaPromocion) {

			// Basarse en CU: https://repositorio.siu.edu.ar/trac/g3w2/wiki/Casos_de_Uso/Cerrar_Acta_Promocion

			$instancia = \comision::inst_promocionar;
			$cur_forma_asignacion_folio_fisico_instancia = \acta::cur_forma_asignacion_folio_fisico_promo;
			$cur_carga_manual_libro_instancia = \acta::cur_carga_manual_libro_promocion;
			$cur_agrega_folios_fisicos_extra_instancia = \acta::cur_agrega_folios_fisicos_extra_promo;
			$cur_notifica_cierre = \acta::cur_notificar_cierre_acta_promocion;
			$cur_imprimir_ultima_version_acta_web = \acta::cur_imprimir_ultima_version_acta_promo_web;
			$cur_actas_cerradas_cnt_ejemplares = \acta::cur_actas_promocion_cerradas_cnt_ejemplares;
			$cur_actas_abiertas_cnt_ejemplares = \acta::cur_actas_promocion_abiertas_cnt_ejemplares;
			$alcance_notificador = \notificador::cerrar_acta_promociones;

			$campo_otra_instancia = 'instancia_cursada';
			$campo_otra_instancia_cerrada = 'acta_cursada_cerrada';
			$metodo_fin_vigencia = 'get_fin_vigencia_promocion';

		}

		$propuestas = aplanar_matriz(\toba::consulta_php('co_comisiones')->get_propuestas_comision($datos_acta['comision']), 'propuesta');
		$ras = catalogo::consultar('atributos_params', 'get_propuestas_ras', array('propuestas' => $propuestas));

		$id_acta = $datos_acta['id_acta'];
		$utiliza_acta_digital = ($datos_acta['digital'] == \comunes::si);
		$autoridad_firma_acta_digital = parametro::get_valor('autoridad_firma_acta_digital', $ras);

                /**
                 * @see https://redmine.siu.edu.ar/redmine/issues/30958 Ticket #30958
                 * @see https://redmine.siu.edu.ar/redmine/issues/30957 Ticket #30957
                 */
                if ($utiliza_acta_digital) {
                    try {
                        \comunes::verificar_jasper_corriendo();
                        arai_documentos::validar_cuenta_usuario_origen_arai_documentos();
                        acta::validar_autorizantes_asociados_acta($id_acta, $autoridad_firma_acta_digital, $ras);            
                    } catch (\Exception $e) {
                        throw new error_guarani($e->getMessage());
                    }
                }
                
                $cur_inserta_alumnos_no_promocionales_en_acta = parametro::get_valor(\acta::cur_inserta_alumnos_no_promocionales_en_acta, $propuestas);
                $cur_orden_detalle_actas = parametro::get_valor(\acta::cur_orden_detalle_actas, $ras);
		$nombre_persona_doc_oficiales = parametro::get_valor('nombre_persona_doc_oficiales');
                $params_sistema = array(
			'cur_inserta_alumnos_no_promocionales_en_acta' => $cur_inserta_alumnos_no_promocionales_en_acta,
			'cur_orden_detalle_actas' => $cur_orden_detalle_actas,
                        'nombre_persona_doc_oficiales'=> $nombre_persona_doc_oficiales
		);

		$alumnos = \toba::consulta_php('co_actas')->get_alumnos_acta_cursadas($datos_acta['id_acta'], $instancia, '', '', '', false, $params_sistema);

		kernel::log()->add_debug("===== Alumnos acta =====", $alumnos);

		$notificaciones = $this->validar_datos_cierre($datos_acta, $alumnos);
		$this->validar_puntos_de_control_alumnos($datos_acta, $alumnos);

		// Parámetros para la asignación de folio físico.
		$forma_asignacion_folio_fisico = parametro::get_valor($cur_forma_asignacion_folio_fisico_instancia, $propuestas);

		try {
                        kernel::db()->abrir_transaccion();
                        
                        /**
                         * Si corresponde, se realiza la asignación de folios físicos
                         * @see https://redmine.siu.edu.ar/redmine/issues/30958 Ticket #30958
                         * @see https://redmine.siu.edu.ar/redmine/issues/30957 Ticket #30957
                         */
			if (!$utiliza_acta_digital && ($forma_asignacion_folio_fisico == \acta::forma_asignacion_folio_fisico_cierre)) {
				// Parámetros adicionales
				$carga_manual_libro = parametro::get_valor($cur_carga_manual_libro_instancia, $propuestas);
				$agrega_folios_fisicos_extra = parametro::get_valor($cur_agrega_folios_fisicos_extra_instancia, $propuestas);

				$mensaje_error = \acta::asignar_folio_fisico($datos_acta, \guarani_arreglos::count($alumnos), $forma_asignacion_folio_fisico, $carga_manual_libro, $agrega_folios_fisicos_extra, $libro);
				if (!empty($mensaje_error)) {
					$mensaje_error = kernel::traductor()->trans($mensaje_error);
					throw new error_guarani($mensaje_error);
				}
			}

			// Fechas de fin de vigencia, según los parámetros definidos por Propuesta.
			$params_vig_por_plan = array();

			$emails_destinatarios = array();

			// Se actualizan o eliminan detalles de evaluación, se insertan detalles de acta.
			foreach ($alumnos as $alumno) {
				
				// Si la otra instancia es válida y no está cerrada...
				if ($alumno[$campo_otra_instancia] && !$alumno[$campo_otra_instancia_cerrada]) {
					// Se modifica el detalle indicando que el acta para la instancia actual está cerrada.
					\guarani::act('act_actas')->cerrar_eval_detalle_cursada($alumno['evaluacion'], $alumno['alumno'], $instancia);
				} else {
					// Sino, se elimina el detalle.
					\guarani::act('act_actas')->eliminar_eval_detalle_cursada($alumno['evaluacion'], $alumno['alumno']);
				}

				// Verifica si ya se obtuvo el parámetro de fin de vigencia, correspondiente al 
				// plan de inscripcion del alumno, si es así se recupera del arreglo, en otro caso se consulta 
				// al método que retorna la fecha desplazada de vigencia según el valor del parámetro.
				$plan_inscripcion = $alumno['plan_inscripcion'];
				if (!isset($params_vig_por_plan[$plan_inscripcion])) {
					// El valor del parámetro 'cur_cant_meses_vigencia_regularidad' se obtiene para cada alumno,
					// dado que puede variar en función del plan de inscripción de cada uno.
					$cant_meses_vigencia_regularidad = parametro::get_valor('cur_cant_meses_vigencia_regularidad', array($plan_inscripcion));

					// Se busca una excepción de vigencia de regularidad para el elemento y el plan de inscripción.
					$filtro = " sga_excep_regularidad.elemento = {$datos_acta['elemento']} AND
								sga_excep_regularidad_planes.plan = $plan_inscripcion";
					$excep_vigencia = current(\toba::consulta_php('co_elementos')->get_planes_excepcion_regularidad($filtro));
					if (!empty($excep_vigencia) && isset($excep_vigencia['vigencia_cursada'])) {
						$cant_meses_vigencia_regularidad = $excep_vigencia['vigencia_cursada'];					
					}

					$params_vig_por_plan[$plan_inscripcion] = call_user_func_array(array(\toba::consulta_php('co_actas'), $metodo_fin_vigencia), array($datos_acta['comision'], $cant_meses_vigencia_regularidad));
				}
				$alumno['fecha_vigencia'] = $params_vig_por_plan[$plan_inscripcion];
				$alumno['plan_version'] = $alumno['plan_version_inscripcion'];
				
				\guarani::act('act_actas')->insertar_acta_detalle($alumno);

				// Si el acta es de tipo 'Rectificativa', para cada alumno se marcan los detalles del acta original 
				// y sus rectificativas cerradas como rectificados.
				if ($esActaRectificativa) {
					\guarani::act('act_actas')->rectificar_detalle($alumno['alumno'], $datos_acta['acta_referencia']);
					// Se elimina (si existe) el registro para el alumno en la tabla de log de no promocionales.
					if ($esActaPromocion) {
						\guarani::act('act_actas')->eliminar_alumno_no_promocional($datos_acta['comision'], $alumno['alumno']);
					}
				}
				
			}

			if ($datos_acta['origen'] == \acta::origen_promociones &&
					$params_sistema['cur_inserta_alumnos_no_promocionales_en_acta'] == \comunes::no &&
					$datos_acta['tipo_acta'] == \acta::tipo_normal) {
				$this->procesar_alumnos_no_promocionales($datos_acta);
			}
			
			// Se actualiza la cabecera del acta.
			\guarani::act('act_actas')->cerrar_acta($datos_acta['id_acta']);
			
			/* Si no es un acta digital y el parámetro cur_imprimir_ultima_version_acta_regulares_web/cur_imprimir_ultima_version_acta_promo_web == 'N' 
			 * se debe actualizar el campo sga_actas.cerrada_por_docente = S. 
                         * @see https://redmine.siu.edu.ar/redmine/issues/30958 Ticket #30958
                         * @see https://redmine.siu.edu.ar/redmine/issues/30957 Ticket #30957
                         */
			$param_imprimir_ultima_version_acta_web = parametro::get_valor($cur_imprimir_ultima_version_acta_web, $ras);
			if (!$utiliza_acta_digital && ($param_imprimir_ultima_version_acta_web == \comunes::no)) {
				\guarani::act('act_actas')->actualizar_cerrada_por_docente($datos_acta['id_acta'], \comunes::si);
			}
                        
			$parametros = ['id_acta' => $datos_acta['id_acta']];
			// Se envía mensaje usando el módulo notificador
            \guarani::notificador()->enviar($alcance_notificador, $parametros, false);
			
			kernel::db()->cerrar_transaccion();
			$cierre_ok = true;
		} catch (error_guarani $e) {
			$cierre_ok = false;
			kernel::db()->abortar_transaccion();
			kernel::log()->add_error($e);
			throw $e;
		} catch (\guarani_error $e) {
			$cierre_ok = false;
			kernel::db()->abortar_transaccion();
			kernel::log()->add_error($e);
			throw new error_guarani($e->getMessage());
		} catch (\Exception $e) {
			$cierre_ok = false;
			kernel::db()->abortar_transaccion();
			kernel::log()->add_error($e);
			throw new error_guarani($e->getMessage());
		}

		if ($cierre_ok) {
			try {
				$parametros = [];
				$parametros['utiliza_acta_digital'] = $datos_acta['digital'];
				$parametros['docentes_nivel_autorizacion'] = parametro::get_valor('docentes_nivel_autorizacion', $ras);
				$parametros['autoridad_firma_acta_digital'] = $autoridad_firma_acta_digital;
				$parametros['cur_actas_cerradas_cnt_ejemplares'] = parametro::get_valor($cur_actas_cerradas_cnt_ejemplares, $propuestas);
				$parametros['cur_actas_abiertas_cnt_ejemplares'] = parametro::get_valor($cur_actas_abiertas_cnt_ejemplares, $propuestas);
				$parametros['cur_inserta_alumnos_no_promocionales_en_acta'] = $cur_inserta_alumnos_no_promocionales_en_acta;
				$parametros['cur_orden_detalle_actas'] = $cur_orden_detalle_actas;
			   /**
				* Envía un acta a Araí-Documentos.
				* @see https://redmine.siu.edu.ar/redmine/issues/30958 Ticket #30958
				* @see https://redmine.siu.edu.ar/redmine/issues/30957 Ticket #30957
				*/
				acta::enviar_acta_arai_documentos($id_acta, $parametros, $ras);
			} catch (\Exception $e) {
				kernel::log()->add_error($e);
				throw new error_guarani($e->getMessage());
			}
		}
		return $notificaciones;
	}

	function procesar_alumnos_no_promocionales($datos_acta)
	{
		$alumnos = \toba::consulta_php('co_actas')->get_alumnos_no_promocionales_acta($datos_acta['id_acta']);
		foreach ($alumnos as $alumno) {
			
			$detalle = \toba::consulta_php('co_evaluaciones')->get_detalle_evaluacion_cursada_by_evaluacion_alumno($datos_acta['evaluacion'], $alumno['alumno']);

			// Si la instancia cursada es válida y el acta de cursada está abierta...
			if ($detalle['instancia_cursada'] && !$detalle['acta_cursada_cerrada']) {
				// Se modifica el detalle indicando que el acta de promoción está cerrada.
				\guarani::act('act_evaluaciones')->marcar_acta_promocion_cerrada_detalle_cursadas($datos_acta['evaluacion'], $alumno['alumno']);
			} else {
				// Sino, se elimina el detalle.
				\guarani::act('act_evaluaciones')->eliminar_detalle_cursadas_by_evaluacion_alumno($datos_acta['evaluacion'], $alumno['alumno']);
			}

			// Se pasan los datos del alumno no promocional a la tabla de logs.
			\guarani::act('act_actas')->insertar_alumno_no_promocional($datos_acta['id_acta'], $alumno['alumno'], $alumno['plan_version'], $alumno['fecha_promocion'], $alumno['escala_nota_promocion'], $alumno['nota_promocion'], $alumno['resultado_promocion'], $alumno['observacion_promocion']);
		}
	}
	
	protected function enviar_mails_cierre_acta($emails_destinatarios, $datos_mail)
	{
		$asunto = kernel::traductor()->trans('cierre_cursadas.mail.asunto');
		$cuerpo = kernel::traductor()->trans('cierre_cursadas.mail.cuerpo', $datos_mail);
		
		$mailjob = new MailJob('', $asunto, $cuerpo, null, '', 'utf-8');

		$cant_max_destinatarios_por_email = kernel::proyecto()->get_cant_max_destinatarios_por_email();

		$bloques_destinatarios = array();
		if ($cant_max_destinatarios_por_email == 0) {
			// Se envía un solo email con todos los destinatarios
			$bloques_destinatarios[0] = $emails_destinatarios;
		} else {
			// Se envían varios emails paginados por el valor de $cant_max_destinatarios_por_email
			$bloques_destinatarios = array_chunk($emails_destinatarios, $cant_max_destinatarios_por_email);
		}

		foreach ($bloques_destinatarios as $bloque_destinatarios) {
			$mailjob->set_hacia($bloque_destinatarios);
			$mailjob->set_html(true);
			// Se encolan los emails para enviar luego
			$mailjob->sendLater();
		}
	}

	function validar_datos_cierre($datos_acta, $alumnos)
	{
		// Atributos para la resolución de parámetros
		$planes = aplanar_matriz(\toba::consulta_php('co_comisiones')->get_planes_comision($datos_acta['comision']), 'plan');
		$propuestas = aplanar_matriz(\toba::consulta_php('co_comisiones')->get_propuestas_comision($datos_acta['comision']), 'propuesta');
		$ras = catalogo::consultar('atributos_params', 'get_propuestas_ras', array('propuestas' => $propuestas));

		$asentar_notas_actas = ($datos_acta['origen'] == \acta::origen_regulares) ? \acta::cur_asentar_notas_actas_regulares : \acta::cur_asentar_notas_actas_promocion;
		$imprimir_ultima_version_acta = ($datos_acta['origen'] == \acta::origen_regulares) ? \acta::cur_imprimir_ultima_version_acta_regulares_web : \acta::cur_imprimir_ultima_version_acta_promo_web;
		$utiliza_acta_digital = ($datos_acta['digital'] == \comunes::si);
		
		$parametros = array(
			'multiples_actas_comision' => parametro::get_valor('cur_multiples_actas_comision', $propuestas),
			'asentar_notas_actas' => parametro::get_valor($asentar_notas_actas, $propuestas),
			'imprimir_ultima_version_acta' => parametro::get_valor($imprimir_ultima_version_acta, $ras),
			'utiliza_acta_digital' => $utiliza_acta_digital
		);
                
		$validador = \acta::validador_actas_cursada();
		$validador->set_interfaz(\comunes::interfaz_autogestion);
		$validador->validar_datos_cierre($datos_acta, $alumnos, $parametros);
		if ($validador->hay_errores()) {
			$errores = $validador->get_errores();
			$mensaje_error = $this->get_mensaje_error($errores);
			if ($this->hay_errores_incompletitud($errores)) {
				throw new error_guarani_acta_incompleta($mensaje_error, $datos_acta['comision']);
			}
			throw new error_guarani($mensaje_error);
		}
		if ($validador->hay_notificaciones()) {
			return $validador->get_notificaciones();
		}
		return array();
	}

	function validar_puntos_de_control_alumnos($datos_acta, $alumnos)
	{

		if ($datos_acta['origen'] == \acta::origen_regulares) {
			$instancia = \comision::inst_cursar;
			$parametro_cierre = \acta::cur_aplicar_controles_cierre_reg;
			$punto_control_cierre = \guarani_punto_de_control::cerrar_acta_regulares;
			$this->operacion = \acta::operacion_acta_regular;
		}
		else {
			$instancia = \comision::inst_promocionar;
			$parametro_cierre = \acta::cur_aplicar_controles_cierre_promo;
			$punto_control_cierre = \guarani_punto_de_control::cerrar_acta_promocion;
			$this->operacion = \acta::operacion_acta_promocion;
		}

		$puntos_de_control = [
			\guarani_punto_de_control::alumno_entidad,
			\guarani_punto_de_control::persona_operacion,
			\guarani_punto_de_control::alumno_operacion,
			\guarani_punto_de_control::alumno_actividad,
			\guarani_punto_de_control::alumno_comision,
			\guarani_punto_de_control::alumno_anio_academico,
			\guarani_punto_de_control::persona_propuesta,
			$punto_control_cierre
		];
		$puntos_de_control_instancia = \toba::consulta_php('co_puntos_de_control')->get_puntos_de_control_instancia($instancia);
		$puntos_de_control = array_merge($puntos_de_control, $puntos_de_control_instancia);

		$propuestas = aplanar_matriz(\toba::consulta_php('co_comisiones')->get_propuestas_comision($datos_acta['comision']), 'propuesta');
		$aplicar_controles_cierre = parametro::get_valor($parametro_cierre, $propuestas);
		
		foreach ($alumnos as $alumno) {
			// Si es ausente, o solo se controla a los alumnos que aprobaron la cursada y no aprobó entonces no corro los controles sobre dicho alumno.
			if (!($alumno['resultado'] == \escalas_notas::resultado_ausente || ($aplicar_controles_cierre == 'APROBADOS' && $alumno['resultado'] != \escalas_notas::resultado_aprobado))) {
				$parametros = [
					'alumno' => $alumno['alumno'],
					'elemento' => $datos_acta['elemento'],
					'comision' => $datos_acta['comision'],
					'nota' => $alumno['nota'],
					'resultado' => $alumno['resultado'],
					'fecha' => \guarani_fecha::get_hoy(),
					'persona' => $alumno['persona'],
					'plan_version' => $alumno['plan_version'],
					'propuesta' => $alumno['propuesta'],
					'anio_academico' => $datos_acta['anio_academico_periodo']
				];
				
				if ($datos_acta['tipo_acta'] == \acta::tipo_rectificativa) {
					$parametros['id_acta_original'] = $datos_acta['acta_referencia'];
				}
				
				// Si se trata de un Acta de Regulares se agregan parámetros de contexto faltantes para los puntos de control 14 y 15 en cierre de actas de cursada y promoción.
				if ($punto_control_cierre == \guarani_punto_de_control::cerrar_acta_regulares) {
					$parametros['cond_regularidad'] = $alumno['cond_regularidad'];
				}
				
				$proceso = $this->nuevo_proceso_control($puntos_de_control, $parametros);
				$resultado_controles = $proceso->ejecutar();
				$mensajes_controles = $proceso->get_mensajes();

				if ($resultado_controles != \guarani_punto_de_control::autorizado) {
					$e = new error_guarani(kernel::traductor()->trans('error_ejecutando_controles'));
					$e->add_mensajes_usuario($mensajes_controles);
					throw $e;
				}
			}
		}
	}

	function get_mensaje_error($errores)
	{
		$mensaje_error = '';
		foreach ($errores as $error) {
			$params = array();
			$i = 1;
			foreach ($error['params'] as $param) {
				$params["%$i%"] = $param;
				$i++;
			}
			$mensaje_error .= "\n" . kernel::traductor()->trans($error['descripcion_corta'], $params);
		}
		return $mensaje_error;
	}

	function hay_errores_incompletitud(array $errores)
	{
		foreach ($errores as $error) {
			if ($error['descripcion_corta'] == '800_nota_invalida' || $error['descripcion_corta'] == '800CUR_error_nota_resultado' || $error['descripcion_corta'] == '800CUR_error_resultado_condicion' || $error['descripcion_corta'] == '800CUR_error_resultado_instancia' || $error['descripcion_corta'] == '800_acta_vacia' || $error['descripcion_corta'] == '800_acta_incompleta' || $error['descripcion_corta'] == '800CUR_error_porc_asistencia' || $error['descripcion_corta'] == '800_acta_sin_notas') {
				return true;
			}
		}
		return false;
	}

}
