<?php

namespace siu\operaciones\convocatorias_becas;

use siu\extension_kernel\controlador_g3w2;
use siu\guarani;
use siu\modelo_g3\beca;
use SIU\Chulupi\util\validador;
use SIU\Chulupi\kernel;
use siu\modelo\transacciones\becas;
use siu\operaciones\_comun\operaciones\reporte\generador_pdf;
use siu\operaciones\datos_censales\_comun\util_form_censo;
use SIU\Chulupi\util\ManejadorArchivos;

class controlador extends controlador_g3w2
{
	

	const TIPO_ELEMENTO_OPCIONES = 'opciones';
	const TIPO_ELEMENTO_SI_NO = 's-n';
	const TIPO_ELEMENTO_TEXTO = 'texto';
	const VACIO = '---';
	
	protected $paper = 'a4';
	protected $orientation = 'portrait';
	protected $pdf_encabezado = array("", "");
    protected $pdf_encabezado_img = array("img" => "", "width" => "");
    protected $pdf_fuente_texto = 11;
    protected $pdf_fuente_titulo = 26;
    protected $pdf_fuente_subtitulo = 24;
    protected $pdf_fuente_encabezado_1 = 20;
    protected $pdf_fuente_encabezado_2 = 16;
    protected $pdf_fuente_encabezado_3 = 14;
    protected $tiene_encabezado = true;
    protected $tiene_encabezado_long = true;
    protected $encabezado_reporte = "";
    protected $columnas_reporte;

	/**
	 * @return \siu\modelo\transacciones\becas
	 */
	function modelo()
	{
		return guarani::becas();
	}
	
	/**
	 * @return \siu\modelo\transacciones\datos_censales
	 */
	function modelo_datos_censales()
	{
		return guarani::datos_censales();
	}

			
	function accion__index()
	{
		try {
			
			$conexion_tehuelche_ok = beca::conexion_tehuelche_ok();

			if ($conexion_tehuelche_ok) {
				
				$this->vista()->set_pagelet('pantalla_inicial');
				
			} else {
				
				$data['mensaje_tipo'] = 'alert-error';
				$data['mensaje'] = 'convocatorias_becas.fallo_conexion_tehuelche';
				$this->vista()->set_pagelet('mensaje', $data);
				
			}
		
		} catch (\Exception $e) {
			
			kernel::log()->add_error($e);
			
			$data['mensaje_tipo'] = 'alert-error';
			$data['mensaje'] = 'convocatorias_becas.ha_ocurrido_error';
			$this->vista()->set_pagelet('mensaje', $data);
			
		}
	}

        function accion__preaplicar()
        {
            try {
                //sin cuil no pueden aplicar
                if (empty(kernel::persona()->get_cuil())) {
                    $data['mensaje_tipo'] = 'alert-error';
                    $data['mensaje']      = 'convocatorias_becas.cuit_inexistente';
                    $this->vista()->set_pagelet('mensaje', $data);
                } else {
                    $hash_habilitacion      = $this->validate_param(0, 'get', validador::TIPO_ALPHANUM);
                    $convocatorias_vigentes = $this->modelo()->info__convocatorias_vigentes();

                    // Si existe la convocatoria
                    if (isset($convocatorias_vigentes[$hash_habilitacion])) {

                        $convocatoria = $convocatorias_vigentes[$hash_habilitacion];

                        $data['hash_habilitacion'] = $hash_habilitacion;
                        $data['convocatoria'] = $convocatoria;
                        $this->vista()->set_pagelet('preaplicar', $data);
                        
                    } else { // sino
                        $data['mensaje_tipo'] = 'alert-error';
                        $data['mensaje']      = 'convocatorias_becas.convocatoria_inexistente';
                        $this->vista()->set_pagelet('mensaje', $data);
                    }
                }
            } catch (\Exception $e) {

                kernel::log()->add_error($e);

                $data['mensaje_tipo'] = 'alert-error';
                $data['mensaje']      = 'convocatorias_becas.ha_ocurrido_error';
                $this->vista()->set_pagelet('mensaje', $data);
            }
        }

    function accion__aplicar()
	{
		try {
			//sin cuil no pueden aplicar
			if(empty( kernel::persona()->get_cuil())){
					$data['mensaje_tipo'] = 'alert-error';
					$data['mensaje'] = 'convocatorias_becas.cuit_inexistente';
					$this->vista()->set_pagelet('mensaje', $data);
			}else{
				$hash_habilitacion = $this->validate_param(0, 'get', validador::TIPO_ALPHANUM);
				$convocatorias_vigentes = $this->modelo()->info__convocatorias_vigentes();
			
			// Si existe la convocatoria
				if (isset($convocatorias_vigentes[$hash_habilitacion])) {

					$convocatoria = $convocatorias_vigentes[$hash_habilitacion];
                                        $habilitacion = $convocatoria['habilitacion'];
					kernel::sesion()->set(becas::CLAVE_HABILITACION_SESION, $habilitacion);
					kernel::sesion()->set(becas::CLAVE_HASH_SESION, $convocatoria['hash']);

                                        $datos_censales_obligatorios_by_habilitacion = \toba::consulta_php('co_datos_censales', true)->get_datos_censales_obligatorios_by_habilitacion($habilitacion);
                                        $datos_censales = array_column($datos_censales_obligatorios_by_habilitacion, 'campo');

                                        // Cuando aplica a la Convocatoria a beca le copio los requisitos de la persona
                                        guarani::becas()->insertar_requisitos_convocatoria($habilitacion);

                                        $requisitos = \toba::consulta_php('co_encuestas', true)->get_requisitos_habilitacion($habilitacion);

                                        // Si hay datos censales para aplicar a la convocatoria
                                        if (!empty($datos_censales)) {
                                            // Secciones a mostrar para los datos censales de la convocatoria
                                            $secciones = util_form_censo::get_secciones_by_campos($datos_censales);
                                            kernel::sesion()->set(becas::CLAVE_SECCIONES_DATOS_CENSALES_SESION, $secciones);
                                            kernel::sesion()->set(becas::CLAVE_CAMPOS_DATOS_CENSALES_SESION, $datos_censales);
                                            // Lo redirijo a "Mis datos personales" primer sección
                                            $url_redirect = kernel::vinculador()->crear('datos_censales', $secciones[0]);
                                        // Si hay requisitos para aplicar a la convocatoria
                                        } elseif (!empty($requisitos)) {
                                            // Lo redirijo a "Documentación Digital de Becas"
                                            $url_redirect = kernel::vinculador()->crear('requisitos_becas');
                                       // Si no hay datos censales ni requisitos para aplicar a la convocatoria
                                        } else {
                                            // Lo redirijo a la encuesta
                                            $url_redirect = kernel::vinculador()->crear('encuestas_kolla', 'index', $convocatoria['hash']);
                                        }
                                        
					header("Location: $url_redirect");
					die();

				} else { // sino

					$data['mensaje_tipo'] = 'alert-error';
					$data['mensaje'] = 'convocatorias_becas.convocatoria_inexistente';
					$this->vista()->set_pagelet('mensaje', $data);

				}
			}
		
		} catch (\Exception $e) {
			
			kernel::log()->add_error($e);
			
			$data['mensaje_tipo'] = 'alert-error';
			$data['mensaje'] = 'convocatorias_becas.ha_ocurrido_error';
			$this->vista()->set_pagelet('mensaje', $data);
			
		}
	}

        function accion__modificar_datos()
	{
            try {
                //sin cuil no pueden aplicar
                if(empty( kernel::persona()->get_cuil())){
                    $data['mensaje_tipo'] = 'alert-error';
                    $data['mensaje'] = 'convocatorias_becas.cuit_inexistente';
                    $this->vista()->set_pagelet('mensaje', $data);
                }else{
                    $hash_habilitacion = $this->validate_param(0, 'get', validador::TIPO_ALPHANUM);
                    $convocatorias_aplicadas = $this->modelo()->info__convocatorias_aplicadas();
                    
                    // Si existe la convocatoria
                    if (isset($convocatorias_aplicadas[$hash_habilitacion])) {

                        $convocatoria = $convocatorias_aplicadas[$hash_habilitacion];
                        
                        // Si no hay una reapertura vigente
                        if (!$convocatoria['reapertura_vigente']) {
                            $data['mensaje_tipo'] = 'alert-error';
                            $data['mensaje'] = 'convocatorias_becas.convocatoria_sin_reapertura_vigente';
                            $this->vista()->set_pagelet('mensaje', $data);
                            return false;
                        }

                        $reapertura = $convocatoria['reapertura'];
                        kernel::sesion()->set(becas::CLAVE_HABILITACION_SESION, $convocatoria['habilitacion']);
                        kernel::sesion()->set(becas::CLAVE_REAPERTURA_SESION, $reapertura);
                        kernel::sesion()->set(becas::CLAVE_HASH_SESION, $convocatoria['hash']);

                        $datos_reapertura = $this->modelo()->info__datos_reapertura($reapertura);
                        $datos_censales = $datos_reapertura['datos_censales'];
                        $requisitos = $datos_reapertura['requisitos'];
                        // Si hay datos censales para modificar en la reapertura
                        if (!empty($datos_censales)) {
                            // Secciones a mostrar para los datos censales de la reapertura
                            $secciones = util_form_censo::get_secciones_by_campos($datos_censales);
                            kernel::sesion()->set(becas::CLAVE_SECCIONES_DATOS_CENSALES_SESION, $secciones);
                            kernel::sesion()->set(becas::CLAVE_CAMPOS_DATOS_CENSALES_SESION, $datos_censales);
                            $url_redirect = kernel::vinculador()->crear('datos_censales', $secciones[0]);
                        // Si hay requisitos para modificar en la reapertura
                        } elseif (!empty($requisitos)) {
                            // Lo redirijo a "Documentación Digital de Becas"
                            $url_redirect = kernel::vinculador()->crear('requisitos_becas');
                        // Si no hay datos censales ni requisitos para modificar en la reapertura
                        } else {
                            // Lo redirijo a la encuesta
                            $url_redirect = kernel::vinculador()->crear('encuestas_kolla', 'index', $convocatoria['hash']);
                        }
                        
                        header("Location: $url_redirect");
                        die();

                    } else { // sino

                        $data['mensaje_tipo'] = 'alert-error';
                        $data['mensaje'] = 'convocatorias_becas.convocatoria_inexistente';
                        $this->vista()->set_pagelet('mensaje', $data);

                    }
                }

            } catch (\Exception $e) {

                kernel::log()->add_error($e);

                $data['mensaje_tipo'] = 'alert-error';
                $data['mensaje'] = 'convocatorias_becas.ha_ocurrido_error';
                $this->vista()->set_pagelet('mensaje', $data);

            }
	}

	function accion__imprimir_solicitud()
	{
		try {
			
			$hash_habilitacion = $this->validate_param(0, 'get', validador::TIPO_ALPHANUM);
			$convocatorias_aplicadas = $this->modelo()->info__convocatorias_aplicadas();

			// Si existe la convocatoria
			if (isset($convocatorias_aplicadas[$hash_habilitacion])) {

				$convocatoria = $convocatorias_aplicadas[$hash_habilitacion];

                                // Si hay una reapertura vigente
                                if ($convocatoria['reapertura_vigente']) {
                                    $data['mensaje_tipo'] = 'alert-error';
                                    $data['mensaje'] = 'convocatorias_becas.convocatoria_con_reapertura_vigente';
                                    $this->vista()->set_pagelet('mensaje', $data);
                                    return false;
                                }

				$datos_impresion = $this->modelo()->info__convocatoria_impresion($convocatoria['becas_convocatoria']);
				$datos_impresion['convocatoria_nombre'] = $convocatoria['tehuelche_nombre'];
				$datos_impresion['convocatoria_descripcion'] = $convocatoria['descripcion'];

                                $es_reapertura = !empty($datos_impresion['fecha_respuesta_reapertura']);
                                $nombre_zip = "Convocatoria";
                                // Si se trata de una reapertura le cambio el nombre al ZIP
                                if ($es_reapertura) {
                                    $nombre_zip .= " (Reapertura)";
                                }

				// https://packagist.org/packages/maennchen/zipstream-php#v0.5.2
				$zip = new \ZipStream\ZipStream("{$nombre_zip}.zip");

				$path_pdf_kolla = $this->modelo()->info__pdf_formulario_kolla($convocatoria['kolla_id_habilitacion'], $convocatoria['formulario'], $convocatoria['hash'], $convocatoria['kolla_unidad_gestion']);
				$zip->addFileFromPath('Encuesta.pdf', $path_pdf_kolla);
				unlink($path_pdf_kolla);

				$path_pdf_guarani = $this->generar_pdf_guarani($datos_impresion);
				$zip->addFileFromPath('Datos Aspirante.pdf', $path_pdf_guarani);
				unlink($path_pdf_guarani);

                                // Agrego los documentos al ZIP
                                $requisitos = $this->modelo()->info__requisitos_convocatoria_by_persona($convocatoria['habilitacion']);
                                foreach ($requisitos as $requisito) {

                                    // Si no tiene documento en el RDI salto al siguiente
                                    if (is_null($requisito['id_documento'])) {
                                        continue;
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
                                    // No soporta nombre de archivos con acentos, ñ o Ñ
                                    $nombre_con_ext = \guarani_texto::simplificar_caracteres("Documentos/{$requisito['nombre']}.{$ext}");
                                    $zip->addFileFromPath($nombre_con_ext, $path);

                                    // Elimino el archivo temporal
                                    unlink($path);
                                }

				$zip->finish();

				
			} else { // sino

				$data['mensaje_tipo'] = 'alert-error';
				$data['mensaje'] = 'convocatorias_becas.convocatoria_inexistente';
				$this->vista()->set_pagelet('mensaje', $data);

			}
		
		} catch (\Exception $e) {
			
			kernel::log()->add_error($e);
			
			$data['mensaje_tipo'] = 'alert-error';
			$data['mensaje'] = 'convocatorias_becas.ha_ocurrido_error';
			$this->vista()->set_pagelet('mensaje', $data);
			
		}
	}
	function accion__consultar_estado()
	{
		try {

			$hash_habilitacion = $this->validate_param('hash_habilitacion', 'get', validador::TIPO_ALPHANUM);
			$cuil = kernel::persona()->get_cuil();

                        // Si no tiene CUIL
			if (empty($cuil)) {
				$data['mensaje_tipo'] = 'alert-error';
				$data['mensaje'] = kernel::traductor()->trans('convocatorias_becas.cuit_inexistente');
			} else {
				$convocatorias_aplicadas = $this->modelo()->info__aspirantes_solicitudes($cuil);
				// Si existe la convocatoria
				if (isset($convocatorias_aplicadas[$hash_habilitacion])) {

					$convocatoria = $convocatorias_aplicadas[$hash_habilitacion];
                                        
                                        // Si hay una reapertura vigente
                                        if (isset($convocatoria['reapertura_vigente']) && $convocatoria['reapertura_vigente']) {
                                            throw new \Exception(kernel::traductor()->trans('convocatorias_becas.convocatoria_con_reapertura_vigente'));
                                        }

					/*
					  confirmado: informar si la solicitud recibió el beneficio o no de acuerdo al listado de asignaciones:
					 * Confirmado con beneficio / Confirmado sin beneficio
					  en estados intermedios (evaluación, revisión, cerrado, asignación):
					 * informar En proceso de evaluación
					  vigente: informar Llamado en vigencia
					 */

					$data['mensaje_tipo'] = 'alert-info';
					$data['mensaje'] = $convocatoria['solicitud_estado'];
				} else { // sino
					$data['mensaje_tipo'] = 'alert-info';
					//No hay información del estado de las solicitudes del aspirante
					$data['mensaje'] = kernel::traductor()->trans('convocatorias_becas.solicitud_evaluacion');
				}
			}
		} catch (\Exception $e) {

			kernel::log()->add_error($e);

			$data['mensaje_tipo'] = 'alert-error';
			$data['mensaje'] = kernel::traductor()->trans('convocatorias_becas.ha_ocurrido_error');
		}

                kernel::renderer()->add_to_ajax_response('estado', $data);
	}

	protected function crear_ezpdf() {
        $instancia = new \Cezpdf($this->paper, $this->orientation);
        return $instancia;
    }
	
	protected function completar_vacios($valor)
	{	
		$valor = trim($valor);
		return ($valor == '')? static::VACIO : $valor;
	}
	
	protected function generar_pdf_guarani($datos_impresion)
	{

        $pdf = new generador_pdf($this->crear_ezpdf());

        $pdf->set_font($this->pdf_fuente);
        $pdf->set_options(array(
			'showHeadings' => 1, 
			'protectRows' => 1, 
			'maxWidth' => 500, 
			'width' => 500, 
			'fontSize' => $this->pdf_fuente_texto,
			'shadeCol' => array(0.8, 0.8, 0.8),
			'cols' => array(),
			'shaded' => 0,
			'showLines' => 0,
			)
		);
        $pdf->set_text_options(array(
			'left' => 5, 
			'top' => 20,
			'maxWidth' => 500, 
			'width' => 500)
		);
        $pdf->set_pdf_fuente_texto($this->pdf_fuente_texto);
        $pdf->set_pdf_fuente_titulo($this->pdf_fuente_titulo);
        $pdf->set_pdf_fuente_subtitulo($this->pdf_fuente_subtitulo);
        $pdf->set_tiene_encabezado($this->tiene_encabezado);
        $pdf->set_tiene_encabezado_long($this->tiene_encabezado_long);
        $pdf->set_nombre_archivo('Datos Aspirante.pdf');

        $this->pdf_encabezado = ["", "<u><b>{$datos_impresion['convocatoria_nombre']}</b></u>", 0];
        $pdf->set_encabezado($this->pdf_encabezado[0], $this->pdf_encabezado[1], $this->pdf_encabezado[2]);

        $logo = kernel::localizador()->encontrar_img('www/img', 'logo_guarani_con_bajada_chico.jpg');
        $pdf->set_encabezado_img($logo['path'], '100');
		
		//*********************************************************
		//************* Descripción de la convocatoria ************
		//*********************************************************

                $pdf_descr_convocatoria = kernel::traductor()->trans('convocatorias_becas.pdf_descr_convocatoria');
                $pdf->dejar_espacio(-$this->pdf_fuente_encabezado_1);
                $pdf->set_pdf_fuente_texto($this->pdf_fuente_encabezado_1);
                $pdf->agregar_texto("<u><b>{$pdf_descr_convocatoria}</b></u>");
                $pdf->set_pdf_fuente_texto($this->pdf_fuente_texto);

                $convocatoria_descripcion = $this->completar_vacios($datos_impresion['convocatoria_descripcion']);
		$datos = [
			['col1' => $convocatoria_descripcion]
		];
		$columnas = [
			'col1' => ''
		];
                $pdf->agregar_tabla($datos, $columnas);
		
		//*********************************************************
		//******************** Datos Generales ********************
		//*********************************************************

                $etiqueta_datos_generales = kernel::traductor()->trans('convocatorias_becas.pdf_datos_generales');
                $pdf->dejar_espacio(-$this->pdf_fuente_encabezado_1);
                $pdf->set_pdf_fuente_texto($this->pdf_fuente_encabezado_1);
                $pdf->agregar_texto("<u><b>{$etiqueta_datos_generales}</b></u>");
                $pdf->set_pdf_fuente_texto($this->pdf_fuente_texto);
		
		$pdf_apellido_nombre = kernel::traductor()->trans('convocatorias_becas.pdf_apellido_nombre');
		$pdf_identificacion = kernel::traductor()->trans('convocatorias_becas.pdf_identificacion');
		$pdf_fecha_nacimiento = kernel::traductor()->trans('convocatorias_becas.pdf_fecha_nacimiento');
		$pdf_cuil = kernel::traductor()->trans('convocatorias_becas.pdf_cuil');
		
		$fecha_nacimiento_obj = \DateTime::createFromFormat("!Y-m-d", $datos_impresion['fecha_nacimiento']);
		
		if (trim($datos_impresion['cuil']) == '') {
			$cuil_formateado = $this->completar_vacios($datos_impresion['cuil']);
		} else {
			$cuil_formateado = \persona::formatearCUIT($datos_impresion['cuil']);
		}
		
		$datos = [
			['col1' => "<i>{$pdf_apellido_nombre}</i>: {$datos_impresion['apellido']} {$datos_impresion['nombres']}"],
			['col1' => "<i>{$pdf_identificacion}</i>: {$datos_impresion['tipo_documento_desc_abreviada']} {$datos_impresion['nro_documento']}"],
			['col1' => "<i>{$pdf_fecha_nacimiento}</i>: ".$fecha_nacimiento_obj->format('d/m/Y')],
			['col1' => "<i>{$pdf_cuil}</i>: {$cuil_formateado}"],
		];
		$columnas = [
			'col1' => '',
		];
                $pdf->agregar_tabla($datos, $columnas);
		
		//*********************************************************
		//******************** Datos Académicos *******************
		//*********************************************************

                $etiqueta_datos_academicos = kernel::traductor()->trans("convocatorias_becas.pdf_datos_academicos");
                $pdf->dejar_espacio(-$this->pdf_fuente_encabezado_1);
                $pdf->set_pdf_fuente_texto($this->pdf_fuente_encabezado_1);
                $pdf->agregar_texto("<u><b>{$etiqueta_datos_academicos}</b></u>");
                $pdf->set_pdf_fuente_texto($this->pdf_fuente_texto);

		$pdf_promedio_con_aplazos = kernel::traductor()->trans('convocatorias_becas.pdf_promedio_con_aplazos');
		$pdf_promedio_sin_aplazos = kernel::traductor()->trans('convocatorias_becas.pdf_promedio_sin_aplazos');
		$pdf_cantidad_materias_aprobadas = kernel::traductor()->trans('convocatorias_becas.pdf_cantidad_materias_aprobadas');
		
		foreach ($datos_impresion['datos_academicos'] as $propuesta) {
			
			$pdf->dejar_espacio(-$this->pdf_fuente_encabezado_2);
                        $pdf->set_pdf_fuente_texto($this->pdf_fuente_encabezado_2);
                        $pdf->agregar_texto("<b>{$propuesta['descripcion']}</b>");
                        $pdf->set_pdf_fuente_texto($this->pdf_fuente_texto);

			$promedio_con_aplazos = $this->completar_vacios($propuesta['promedio_con_aplazos']);
			$promedio_sin_aplazos = $this->completar_vacios($propuesta['promedio_sin_aplazos']);
			$cantidad_actividades_aprobadas = $this->completar_vacios($propuesta['cantidad_actividades_aprobadas']);
			
			$datos = [
				['col1' => "<i>{$pdf_promedio_con_aplazos}</i>: {$promedio_con_aplazos}"],
				['col1' => "<i>{$pdf_promedio_sin_aplazos}</i>: {$promedio_sin_aplazos}"],
				['col1' => "<i>{$pdf_cantidad_materias_aprobadas}</i>: {$cantidad_actividades_aprobadas}"],
			];
			$columnas = [
				'col1' => '',
			];
			
			$pdf->agregar_tabla($datos, $columnas);
			
		}
		
		//*********************************************************
		//******************** Datos Censales *********************
		//*********************************************************

                $etiqueta_datos_censales = kernel::traductor()->trans("convocatorias_becas.pdf_datos_censales");
                $pdf->dejar_espacio(-$this->pdf_fuente_encabezado_1);
                $pdf->set_pdf_fuente_texto($this->pdf_fuente_encabezado_1);
                $pdf->agregar_texto("<u><b>{$etiqueta_datos_censales}</b></u>");
                $pdf->set_pdf_fuente_texto($this->pdf_fuente_texto);

		$this->generar_pdf_guarani_datos_censales($pdf, $datos_impresion['datos_censales']);

                //*********************************************************
		//******************** Reaperturas ************************
		//*********************************************************

                // Si hubo reaperturas
                if (isset($datos_impresion['reaperturas']) && !empty($datos_impresion['reaperturas'])) {
                    // Se deja preparado para multiples reaperturas
                    foreach ($datos_impresion['reaperturas'] as $reapertura) {

                        // Si el aspirante nunca reviso/corrigio los datos entonces no muestro la reapertura
                        if (empty($reapertura['fecha_respuesta'])) {
                            continue;
                        }

                        $etiqueta_datos_censales_reapertura = kernel::traductor()->trans("convocatorias_becas.pdf_datos_censales_reapertura");
			$pdf->dejar_espacio(-$this->pdf_fuente_encabezado_1);
			$pdf->set_pdf_fuente_texto($this->pdf_fuente_encabezado_1);
                        $pdf->agregar_texto("<u><b>{$etiqueta_datos_censales_reapertura}</b></u>");
			$pdf->set_pdf_fuente_texto($this->pdf_fuente_texto);

                        $pdf->dejar_espacio(-$this->pdf_fuente_texto);
                        $fecha_respuesta_reapertura = \DateTime::createFromFormat('Y-m-d H:i:s', $reapertura['fecha_respuesta'])->format('d/m/Y H:i:s');
                        $pdf->agregar_texto('<i>'.kernel::traductor()->trans('convocatorias_becas.reapertura_fecha_revision').'</i>: '.$fecha_respuesta_reapertura);

                        // Si hubo cambios en los datos censales
                        if (isset($reapertura['datos_censales']) && !empty($reapertura['datos_censales'])) {
                            $this->generar_pdf_guarani_datos_censales($pdf, $reapertura['datos_censales']);
                        }
                    }
                }
		
		//*********************************************************
		//******************* Declaración y firma *****************
		//*********************************************************
		
		$pdf->dejar_espacio(-10);
		$pdf->agregar_texto(kernel::traductor()->trans('convocatorias_becas.pdf_declaracion'));
		
		$pdf->set_text_options(array(
			'left' => 5, 
			'top' => 20, 
			'maxWidth' => 500, 
			'width' => 500,
			'justification' => 'right')
		);
		
		$pdf->dejar_espacio(-30);
		$pdf->agregar_texto("________________");
		$pdf->agregar_texto(kernel::traductor()->trans('convocatorias_becas.pdf_firma_aclaracion'));

		return $pdf->generar(false);
	}
	
	protected function generar_pdf_guarani_datos_censales(generador_pdf &$pdf, $datos)
	{

		$datos_censales = [];
		$datos_censales_final = [];
                
		foreach ($datos as $dato_censal) {
			$datos_censales[$dato_censal['campo']] = [
				'etiqueta' => $dato_censal['etiqueta'],
				'valor' => $dato_censal['valor'],
			];
		}
		
		$definiciones = $this->get_definicion_datos_censales();
		foreach ($definiciones as $campo => $definicion) {
			
			if (!isset($datos_censales[$campo])) {
				continue;
			}
			
			if ($definicion['tipo'] == static::TIPO_ELEMENTO_OPCIONES) {
				
				$funcion = $definicion['funcion'];
				$opciones = $this->modelo_datos_censales()->$funcion();
				$datos_censales[$campo]['valor'] = (isset($opciones[$datos_censales[$campo]['valor']]))? $opciones[$datos_censales[$campo]['valor']] : static::VACIO;
				
			} else if ($definicion['tipo'] == static::TIPO_ELEMENTO_SI_NO) {
				
				$datos_censales[$campo]['valor'] = ($datos_censales[$campo]['valor'] == \comunes::si)? \comunes::si_desc : \comunes::no_desc;
				
			} else {
			
				$datos_censales[$campo]['valor'] = $this->completar_vacios($datos_censales[$campo]['valor']);
				
			}
			
			if (isset($definicion['subgrupo'])) {
				$datos_censales_final[$definicion['grupo']]['subgrupos'][$definicion['subgrupo']]['datos'][] = $datos_censales[$campo];
			} else {
				$datos_censales_final[$definicion['grupo']]['datos'][] = $datos_censales[$campo];
			}
			
		}
		
		kernel::log()->add_debug("=== datos_censales_final ===", $datos_censales_final);
		
		foreach ($datos_censales_final as $grupo => $datos_grupo) {
			
			$etiqueta_grupo = kernel::traductor()->trans("datos_censales.form.{$grupo}");
			$pdf->dejar_espacio(-$this->pdf_fuente_encabezado_2);
			$pdf->set_pdf_fuente_texto($this->pdf_fuente_encabezado_2);
			$pdf->agregar_texto("<b>{$etiqueta_grupo}</b>");
			$pdf->set_pdf_fuente_texto($this->pdf_fuente_texto);
			
			if (isset($datos_grupo['subgrupos'])) {
				
				foreach ($datos_grupo['subgrupos'] as $subgrupo => $datos_subgrupo) {
					
					$datos = $this->generar_datos_tabla_dos_columnas($datos_subgrupo['datos']);

					$etiqueta_subgrupo = kernel::traductor()->trans("datos_censales.{$subgrupo}");

                                        $pdf->dejar_espacio(-$this->pdf_fuente_encabezado_3);
                                        $pdf->set_pdf_fuente_texto($this->pdf_fuente_encabezado_3);
                                        $pdf->agregar_texto("<b>{$etiqueta_subgrupo}</b>");
                                        $pdf->set_pdf_fuente_texto($this->pdf_fuente_texto);

					$columnas = [
						'col1' => '',
						'col2' => '',
					];
					$pdf->agregar_tabla($datos, $columnas);
				}
				
			} else {
				
				$datos = $this->generar_datos_tabla_dos_columnas($datos_grupo['datos']);
				
				$columnas = [
					'col1' => '',
					'col2' => '',
				];
				
				$pdf->agregar_tabla($datos, $columnas);
			}
			
			
		}
		
	}
	
	protected function generar_datos_tabla_dos_columnas($dat_cen) 
	{
		$datos = [];
		$fila = [];

		for ($i = 0; $i < \guarani_arreglos::count($dat_cen); $i += 2) {

			if (isset($dat_cen[$i])) {
				$fila['col1'] = "<i>{$dat_cen[$i]['etiqueta']}</i>: {$dat_cen[$i]['valor']}";
			}

			if (isset($dat_cen[$i + 1])) {
				$fila['col2'] = "<i>{$dat_cen[$i + 1]['etiqueta']}</i>: {$dat_cen[$i + 1]['valor']}";
			} else {
				$fila['col2'] = "";
			}

			$datos[] = $fila;
			$fila = [];
		}
		
		return $datos;
	}
	
	protected function get_definicion_datos_censales()
	{
		
		return [
			//************************************************
			//****************Datos personales****************
			//************************************************
			'estado_civil' => [
				'tipo' => static::TIPO_ELEMENTO_OPCIONES,
				'funcion' => 'opciones__estados_civiles',
				'grupo' => 'datos_personales',
				'subgrupo' => 'situacion_familiar',
			],
			'unido_hecho' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'datos_personales',
				'subgrupo' => 'situacion_familiar',
			],
			'cantidad_hijos' => [
				'tipo' => static::TIPO_ELEMENTO_OPCIONES,
				'funcion' => 'opciones__cantidades_hijos',
				'grupo' => 'datos_personales',
				'subgrupo' => 'situacion_familiar',
			],
			'cantidad_familia' => [
				'tipo' => static::TIPO_ELEMENTO_OPCIONES,
				'funcion' => 'opciones__cantidades_familia',
				'grupo' => 'datos_personales',
				'subgrupo' => 'situacion_familiar',
			],
			'situacion_padre' => [
				'tipo' => static::TIPO_ELEMENTO_OPCIONES,
				'funcion' => 'opciones__situacion_padres',
				'grupo' => 'datos_personales',
				'subgrupo' => 'situacion_familiar',
			],
			'situacion_madre' => [
				'tipo' => static::TIPO_ELEMENTO_OPCIONES,
				'funcion' => 'opciones__situacion_padres',
				'grupo' => 'datos_personales',
				'subgrupo' => 'situacion_familiar',
			],
			'turno_preferido' => [
				'tipo' => static::TIPO_ELEMENTO_OPCIONES,
				'funcion' => 'opciones__turnos',
				'grupo' => 'datos_personales',
				'subgrupo' => 'turno_preferido',
			],
			'pertenece_pueblo_originario' => [
				'tipo' => static::TIPO_ELEMENTO_OPCIONES,
				'funcion' => 'opciones__si_no_no_desea_contestar',
				'grupo' => 'datos_personales',
				'subgrupo' => 'pueblos_originarios',
			],
			'pueblo_originario' => [
				'tipo' => static::TIPO_ELEMENTO_OPCIONES,
				'funcion' => 'opciones__pueblos_originarios',
				'grupo' => 'datos_personales',
				'subgrupo' => 'pueblos_originarios',
			],
			'pueblo_originario_otro' => [
				'tipo' => static::TIPO_ELEMENTO_TEXTO,
				'grupo' => 'datos_personales',
				'subgrupo' => 'pueblos_originarios',
			],
			
			//************************************************
			//*************** Domicilio **********************
			//************************************************
			'periodo_lectivo_calle' => [
				'tipo' => static::TIPO_ELEMENTO_TEXTO,
				'grupo' => 'domicilio',
				'subgrupo' => 'domicilio_periodo_lectivo'
			],
			'periodo_lectivo_numero' => [
				'tipo' => static::TIPO_ELEMENTO_TEXTO,
				'grupo' => 'domicilio',
				'subgrupo' => 'domicilio_periodo_lectivo'
			],
			'periodo_lectivo_piso' => [
				'tipo' => static::TIPO_ELEMENTO_TEXTO,
				'grupo' => 'domicilio',
				'subgrupo' => 'domicilio_periodo_lectivo'
			],
			'periodo_lectivo_departamento' => [
				'tipo' => static::TIPO_ELEMENTO_TEXTO,
				'grupo' => 'domicilio',
				'subgrupo' => 'domicilio_periodo_lectivo'
			],
			'periodo_lectivo_unidad' => [
				'tipo' => static::TIPO_ELEMENTO_TEXTO,
				'grupo' => 'domicilio',
				'subgrupo' => 'domicilio_periodo_lectivo'
			],
			'periodo_lectivo_pais' => [
				'tipo' => static::TIPO_ELEMENTO_OPCIONES,
				'funcion' => 'opciones__paises',
				'grupo' => 'domicilio',
				'subgrupo' => 'domicilio_periodo_lectivo'
			],
			'periodo_lectivo_localidad' => [
				'tipo' => static::TIPO_ELEMENTO_TEXTO,
				'grupo' => 'domicilio',
				'subgrupo' => 'domicilio_periodo_lectivo'
			],
			'periodo_lectivo_localidad_descr' => [
				'tipo' => static::TIPO_ELEMENTO_TEXTO,
				'grupo' => 'domicilio',
				'subgrupo' => 'domicilio_periodo_lectivo'
			],
			'periodo_lectivo_codigo_postal' => [
				'tipo' => static::TIPO_ELEMENTO_TEXTO,
				'grupo' => 'domicilio',
				'subgrupo' => 'domicilio_periodo_lectivo'
			],
			'periodo_lectivo_barrio' => [
				'tipo' => static::TIPO_ELEMENTO_TEXTO,
				'grupo' => 'domicilio',
				'subgrupo' => 'domicilio_periodo_lectivo'
			],
			'tipo_vivienda' => [
				'tipo' => static::TIPO_ELEMENTO_OPCIONES,
				'funcion' => 'opciones__tipos_vivienda',
				'grupo' => 'domicilio',
				'subgrupo' => 'domicilio_periodo_lectivo'
			],
			'vive_con' => [
				'tipo' => static::TIPO_ELEMENTO_OPCIONES,
				'funcion' => 'opciones__vive_con',
				'grupo' => 'domicilio',
				'subgrupo' => 'domicilio_periodo_lectivo'
			],
			
			'procedencia_calle' => [
				'tipo' => static::TIPO_ELEMENTO_TEXTO,
				'grupo' => 'domicilio',
				'subgrupo' => 'domicilio_procedencia'
			],
			'procedencia_numero' => [
				'tipo' => static::TIPO_ELEMENTO_TEXTO,
				'grupo' => 'domicilio',
				'subgrupo' => 'domicilio_procedencia'
			],
			'procedencia_piso' => [
				'tipo' => static::TIPO_ELEMENTO_TEXTO,
				'grupo' => 'domicilio',
				'subgrupo' => 'domicilio_procedencia'
			],
			'procedencia_departamento' => [
				'tipo' => static::TIPO_ELEMENTO_TEXTO,
				'grupo' => 'domicilio',
				'subgrupo' => 'domicilio_procedencia'
			],
			'procedencia_unidad' => [
				'tipo' => static::TIPO_ELEMENTO_TEXTO,
				'grupo' => 'domicilio',
				'subgrupo' => 'domicilio_procedencia'
			],
			'procedencia_pais' => [
				'tipo' => static::TIPO_ELEMENTO_OPCIONES,
				'funcion' => 'opciones__paises',
				'grupo' => 'domicilio',
				'subgrupo' => 'domicilio_procedencia'
			],
			'procedencia_localidad' => [
				'tipo' => static::TIPO_ELEMENTO_TEXTO,
				'grupo' => 'domicilio',
				'subgrupo' => 'domicilio_procedencia'
			],
			'procedencia_localidad_descr' => [
				'tipo' => static::TIPO_ELEMENTO_TEXTO,
				'grupo' => 'domicilio',
				'subgrupo' => 'domicilio_procedencia'
			],
			'procedencia_codigo_postal' => [
				'tipo' => static::TIPO_ELEMENTO_TEXTO,
				'grupo' => 'domicilio',
				'subgrupo' => 'domicilio_procedencia'
			],
			'procedencia_barrio' => [
				'tipo' => static::TIPO_ELEMENTO_TEXTO,
				'grupo' => 'domicilio',
				'subgrupo' => 'domicilio_procedencia'
			],
			
			//************************************************
			//**************Financiamiento********************
			//************************************************
			'costeo_estudios_familiar' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'financiamiento',
				'subgrupo' => 'financ_fuente'
			],
			'costeo_estudios_plan_social' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'financiamiento',
				'subgrupo' => 'financ_fuente'
			],
			'costeo_estudios_trabajo' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'financiamiento',
				'subgrupo' => 'financ_fuente'
			],
			'costeo_estudios_beca' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'financiamiento',
				'subgrupo' => 'financ_fuente'
			],
			'costeo_estudios_otro' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'financiamiento',
				'subgrupo' => 'financ_fuente'
			],
			'costeo_estudios_descripcion' => [
				'tipo' => static::TIPO_ELEMENTO_TEXTO,
				'grupo' => 'financiamiento',
				'subgrupo' => 'financ_fuente'
			],
			
			'beca_fuente_universidad' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'financiamiento',
				'subgrupo' => 'financ_beca_fuente'
			],
			'beca_fuente_nacional' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'financiamiento',
				'subgrupo' => 'financ_beca_fuente'
			],
			'beca_fuente_provincial' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'financiamiento',
				'subgrupo' => 'financ_beca_fuente'
			],
			'beca_fuente_municipal' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'financiamiento',
				'subgrupo' => 'financ_beca_fuente'
			],
			'beca_fuente_internacional' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'financiamiento',
				'subgrupo' => 'financ_beca_fuente'
			],
			'beca_fuente_otra' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'financiamiento',
				'subgrupo' => 'financ_beca_fuente'
			],
			
			'beca_tipo_economica' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'financiamiento',
				'subgrupo' => 'financ_beca_tipo'
			],
			'beca_tipo_servicios' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'financiamiento',
				'subgrupo' => 'financ_beca_tipo'
			],
			'beca_tipo_investigacion' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'financiamiento',
				'subgrupo' => 'financ_beca_tipo'
			],
			
			'beca_economica_transporte' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'financiamiento',
				'subgrupo' => 'financ_beca_tipo_econom'
			],
			'beca_economica_efectivo' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'financiamiento',
				'subgrupo' => 'financ_beca_tipo_econom'
			],
			'beca_economica_fotocopias' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'financiamiento',
				'subgrupo' => 'financ_beca_tipo_econom'
			],
			'beca_economica_habitacional' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'financiamiento',
				'subgrupo' => 'financ_beca_tipo_econom'
			],
			'beca_economica_comedor' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'financiamiento',
				'subgrupo' => 'financ_beca_tipo_econom'
			],
			
			//************************************************
			//**************Datos laborales*******************
			//************************************************
			'trabajo_existe' => [
				'tipo' => static::TIPO_ELEMENTO_OPCIONES,
				'funcion' => 'opciones__trabajo_existe',
				'grupo' => 'datos_laborales',
				'subgrupo' => 'datos_laborales_sit'
			],
			'trabajo_hace' => [
				'tipo' => static::TIPO_ELEMENTO_OPCIONES,
				'funcion' => 'opciones__trabajo_hace',
				'grupo' => 'datos_laborales',
				'subgrupo' => 'datos_laborales_sit'
			],
			'trabajo_ocupacion' => [
				'tipo' => static::TIPO_ELEMENTO_OPCIONES,
				'funcion' => 'opciones__trabajo_ocupacion',
				'grupo' => 'datos_laborales',
				'subgrupo' => 'datos_laborales_sit'
			],
			'trabajo_hora_sem' => [
				'tipo' => static::TIPO_ELEMENTO_OPCIONES,
				'funcion' => 'opciones__trabajo_hora_sem',
				'grupo' => 'datos_laborales',
				'subgrupo' => 'datos_laborales_sit'
			],
			'trabajo_carrera' => [
				'tipo' => static::TIPO_ELEMENTO_OPCIONES,
				'funcion' => 'opciones__trabajo_carrera',
				'grupo' => 'datos_laborales',
				'subgrupo' => 'datos_laborales_sit'
			],
			'trabajo_tarea_descripcion' => [
				'tipo' => static::TIPO_ELEMENTO_TEXTO,
				'grupo' => 'datos_laborales',
				'subgrupo' => 'datos_laborales_sit'
			],
			
			'trabajo_familiar' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'datos_laborales',
				'subgrupo' => 'datos_laborales_adic'
			],
			'trabajo_con_descuentos_jubilatorios' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'datos_laborales',
				'subgrupo' => 'datos_laborales_adic'
			],
			'trabajo_recibe_pago' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'datos_laborales',
				'subgrupo' => 'datos_laborales_adic'
			],
			
			//************************************************
			//**************Tecnología************************
			//************************************************
			'tecnologia_pc_casa' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'tecnologia',
				'subgrupo' => 'dispon_pc'
			],
			'tecnologia_pc_trabajo' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'tecnologia',
				'subgrupo' => 'dispon_pc'
			],
			'tecnologia_pc_universidad' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'tecnologia',
				'subgrupo' => 'dispon_pc'
			],
			'tecnologia_pc_otro_lugar' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'tecnologia',
				'subgrupo' => 'dispon_pc'
			],
			
			'tecnologia_int_casa' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'tecnologia',
				'subgrupo' => 'accede_internet_en'
			],
			'tecnologia_int_trabajo' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'tecnologia',
				'subgrupo' => 'accede_internet_en'
			],
			'tecnologia_int_universidad' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'tecnologia',
				'subgrupo' => 'accede_internet_en'
			],
			'tecnologia_int_movil' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'tecnologia',
				'subgrupo' => 'accede_internet_en'
			],
			'tecnologia_int_otro_lugar' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'tecnologia',
				'subgrupo' => 'accede_internet_en'
			],
			
			//************************************************
			//**************Deportes**************************
			//************************************************
			'deportes' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'deportes',
			],
			
			'deportes_universidad' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'deportes',
				'subgrupo' => 'listado_deportes_lugares'
			],
			'deportes_gimnasio' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'deportes',
				'subgrupo' => 'listado_deportes_lugares'
			],
			'deportes_particular' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'deportes',
				'subgrupo' => 'listado_deportes_lugares'
			],
			'deportes_otro_lugar' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'deportes',
				'subgrupo' => 'listado_deportes_lugares'
			],
			
			'deportes_futbol' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'deportes',
				'subgrupo' => 'listado_deportes'
			],
			'deportes_basquet' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'deportes',
				'subgrupo' => 'listado_deportes'
			],
			'deportes_voley' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'deportes',
				'subgrupo' => 'listado_deportes'
			],
			'deportes_gimnasia' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'deportes',
				'subgrupo' => 'listado_deportes'
			],
			'deportes_tenis' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'deportes',
				'subgrupo' => 'listado_deportes'
			],
			'deportes_natacion' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'deportes',
				'subgrupo' => 'listado_deportes'
			],
			'deportes_handball' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'deportes',
				'subgrupo' => 'listado_deportes'
			],
			'deportes_otros' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'deportes',
				'subgrupo' => 'listado_deportes'
			],
			
			//************************************************
			//**************Idiomas***************************
			//************************************************	
			'nivel_idioma_ingles' => [
				'tipo' => static::TIPO_ELEMENTO_OPCIONES,
				'funcion' => 'opciones__niveles_idiomas',
				'grupo' => 'idiomas',
				'subgrupo' => 'conoc_idiomas'
			],
			'nivel_idioma_aleman' => [
				'tipo' => static::TIPO_ELEMENTO_OPCIONES,
				'funcion' => 'opciones__niveles_idiomas',
				'grupo' => 'idiomas',
				'subgrupo' => 'conoc_idiomas'
			],
			'nivel_idioma_frances' => [
				'tipo' => static::TIPO_ELEMENTO_OPCIONES,
				'funcion' => 'opciones__niveles_idiomas',
				'grupo' => 'idiomas',
				'subgrupo' => 'conoc_idiomas'
			],
			'nivel_idioma_italiano' => [
				'tipo' => static::TIPO_ELEMENTO_OPCIONES,
				'funcion' => 'opciones__niveles_idiomas',
				'grupo' => 'idiomas',
				'subgrupo' => 'conoc_idiomas'
			],
			'nivel_idioma_portugues' => [
				'tipo' => static::TIPO_ELEMENTO_OPCIONES,
				'funcion' => 'opciones__niveles_idiomas',
				'grupo' => 'idiomas',
				'subgrupo' => 'conoc_idiomas'
			],
			'nivel_idioma_chino' => [
				'tipo' => static::TIPO_ELEMENTO_OPCIONES,
				'funcion' => 'opciones__niveles_idiomas',
				'grupo' => 'idiomas',
				'subgrupo' => 'conoc_idiomas'
			],
			'idioma_otro' => [
				'tipo' => static::TIPO_ELEMENTO_TEXTO,
				'grupo' => 'idiomas',
				'subgrupo' => 'conoc_idiomas'
			],
			'nivel_idioma_otro' => [
				'tipo' => static::TIPO_ELEMENTO_OPCIONES,
				'funcion' => 'opciones__niveles_idiomas',
				'grupo' => 'idiomas',
				'subgrupo' => 'conoc_idiomas'
			],
			
			//************************************************
			//**************Datos de salud********************
			//************************************************	
			'cobertura_salud' => [
				'tipo' => static::TIPO_ELEMENTO_OPCIONES,
				'funcion' => 'opciones__cobertura_salud',
				'grupo' => 'datos_salud',
				'subgrupo' => 'salud'
			],
			'es_celiaco' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'datos_salud',
				'subgrupo' => 'salud'
			],
			
			//************************************************
			//****************Preferencias********************
			//************************************************	
			'como_conocio_institucion' => [
				'tipo' => static::TIPO_ELEMENTO_TEXTO,
				'grupo' => 'preferencias',
				'subgrupo' => 'preferencias.conocim_institucion'
			],
			
			'insc_otra_institucion' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'preferencias',
				'subgrupo' => 'preferencias.otras_insc'
			],
			'insc_otra_institucion_desc' => [
				'tipo' => static::TIPO_ELEMENTO_TEXTO,
				'grupo' => 'preferencias',
				'subgrupo' => 'preferencias.otras_insc'
			],
			
			'mot_economico' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'preferencias',
				'subgrupo' => 'preferencias.institucion'
			],
			'mot_prestigio' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'preferencias',
				'subgrupo' => 'preferencias.institucion'
			],
			'mot_difusion' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'preferencias',
				'subgrupo' => 'preferencias.institucion'
			],
			'mot_recomendacion_estudiantes' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'preferencias',
				'subgrupo' => 'preferencias.institucion'
			],
			'mot_recomendacion_amigos' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'preferencias',
				'subgrupo' => 'preferencias.institucion'
			],
			'mot_sistema_ingreso' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'preferencias',
				'subgrupo' => 'preferencias.institucion'
			],
			'mot_ubicacion' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'preferencias',
				'subgrupo' => 'preferencias.institucion'
			],
			'mot_otros_i' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'preferencias',
				'subgrupo' => 'preferencias.institucion'
			],
			
			'mot_vocacion' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'preferencias',
				'subgrupo' => 'preferencias.propuesta'
			],
			'mot_sugerencia_familiar' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'preferencias',
				'subgrupo' => 'preferencias.propuesta'
			],
			'mot_reconocimiento_social' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'preferencias',
				'subgrupo' => 'preferencias.propuesta'
			],
			'mot_insercion_laboral' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'preferencias',
				'subgrupo' => 'preferencias.propuesta'
			],
			'mot_perfil_profesional' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'preferencias',
				'subgrupo' => 'preferencias.propuesta'
			],
			'mot_mejora_economica' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'preferencias',
				'subgrupo' => 'preferencias.propuesta'
			],
			'mot_util_sociedad' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'preferencias',
				'subgrupo' => 'preferencias.propuesta'
			],
			'mot_otros_p' => [
				'tipo' => static::TIPO_ELEMENTO_SI_NO,
				'grupo' => 'preferencias',
				'subgrupo' => 'preferencias.propuesta'
			],

                        //************************************************
			//****************Discapacidad********************
			//************************************************
			'condicion_discapacidad' => [
                            'tipo' => static::TIPO_ELEMENTO_OPCIONES,
                            'funcion' => 'opciones__condicion_discapacidad',
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'cond_discapacidad'
			],
                        'tiene_cud' => [
                            'tipo' => static::TIPO_ELEMENTO_SI_NO,
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'cond_discapacidad'
			],
                        'disc_auditiva' => [
                            'tipo' => static::TIPO_ELEMENTO_SI_NO,
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'listado_discapacidad_tipos'
			],
                        'disc_visual' => [
                            'tipo' => static::TIPO_ELEMENTO_SI_NO,
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'listado_discapacidad_tipos'
			],
                        'disc_motora' => [
                            'tipo' => static::TIPO_ELEMENTO_SI_NO,
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'listado_discapacidad_tipos'
			],
                        'disc_cond_psicosocial' => [
                            'tipo' => static::TIPO_ELEMENTO_SI_NO,
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'listado_discapacidad_tipos'
			],
                        'disc_otra' => [
                            'tipo' => static::TIPO_ELEMENTO_SI_NO,
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'listado_discapacidad_tipos'
			],
                        //****************Auditiva********************
                       'aud_tipo' => [
                            'tipo' => static::TIPO_ELEMENTO_OPCIONES,
                            'funcion' => 'opciones__dificultades',
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'auditiva'
			],
                        'aud_lenguaje_senias' => [
                            'tipo' => static::TIPO_ELEMENTO_SI_NO,
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'auditiva'
			],
                      'aud_lenguaje_labial' => [
                            'tipo' => static::TIPO_ELEMENTO_SI_NO,
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'auditiva'
			],
                        'aud_comunicacion_otra' => [
                            'tipo' => static::TIPO_ELEMENTO_TEXTO,
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'auditiva'
			],
                        'aud_apoyo_vida_diaria' => [
                            'tipo' => static::TIPO_ELEMENTO_TEXTO,
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'auditiva'
			],
                        'aud_req_interprete_lengua_senias' => [
                            'tipo' => static::TIPO_ELEMENTO_SI_NO,
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'auditiva'
			],
                        'aud_req_aro_magnetico' => [
                            'tipo' => static::TIPO_ELEMENTO_SI_NO,
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'auditiva'
			],
                        'aud_req_otros_apoyos' => [
                            'tipo' => static::TIPO_ELEMENTO_SI_NO,
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'auditiva'
			],
                        'aud_otros_apoyos' => [
                            'tipo' => static::TIPO_ELEMENTO_TEXTO,
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'auditiva'
			],
                        //****************Visual********************
                       'vis_dificultad_para_ver' => [
                            'tipo' => static::TIPO_ELEMENTO_OPCIONES,
                            'funcion' => 'opciones__dificultades',
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'visual'
			],
                        'vis_apoyo_vida_diaria' => [
                            'tipo' => static::TIPO_ELEMENTO_TEXTO,
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'visual'
			],
                        'vis_req_archivo_audio' => [
                            'tipo' => static::TIPO_ELEMENTO_SI_NO,
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'visual'
			],
                        'vis_req_texto_digital' => [
                            'tipo' => static::TIPO_ELEMENTO_SI_NO,
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'visual'
			],
                        'vis_req_texto_braile' => [
                            'tipo' => static::TIPO_ELEMENTO_SI_NO,
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'visual'
			],
                        'vis_req_otros_apoyos' => [
                            'tipo' => static::TIPO_ELEMENTO_SI_NO,
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'visual'
			],
                        'vis_otros_apoyos' => [
                            'tipo' => static::TIPO_ELEMENTO_TEXTO,
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'visual'
			],
                        //****************Motora********************
                       'mot_apoyo_vida_diaria' => [
                            'tipo' => static::TIPO_ELEMENTO_TEXTO,
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'motora'
			],
                        'mot_req_apoyo_aula' => [
                            'tipo' => static::TIPO_ELEMENTO_SI_NO,
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'motora'
			],
                        'mot_apoyo_aula' => [
                            'tipo' => static::TIPO_ELEMENTO_TEXTO,
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'motora'
			],
                        'mot_req_otros_apoyos' => [
                            'tipo' => static::TIPO_ELEMENTO_SI_NO,
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'motora'
			],
                        'mot_otros_apoyos' => [
                            'tipo' => static::TIPO_ELEMENTO_TEXTO,
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'motora'
			],
                        //****************Condición Psicosocial********************
                       'psi_descripcion' => [
                            'tipo' => static::TIPO_ELEMENTO_TEXTO,
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'psicosocial'
			],
                        'psi_apoyo_vida_diaria' => [
                            'tipo' => static::TIPO_ELEMENTO_TEXTO,
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'psicosocial'
			],
                        'psi_req_otros_apoyos' => [
                            'tipo' => static::TIPO_ELEMENTO_SI_NO,
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'psicosocial'
			],
                        'psi_otros_apoyos' => [
                            'tipo' => static::TIPO_ELEMENTO_TEXTO,
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'psicosocial'
			],
                        //****************Otra situación de discapacidad********************
                       'otra_descripcion' => [
                            'tipo' => static::TIPO_ELEMENTO_TEXTO,
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'otras'
			],
                        'otra_req_apoyo_vida_diaria' => [
                            'tipo' => static::TIPO_ELEMENTO_SI_NO,
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'otras'
			],
                        'otra_apoyo_vida_diaria' => [
                            'tipo' => static::TIPO_ELEMENTO_TEXTO,
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'otras'
			],
                        //****************Alguna otra información que consideres importante comunicar********************
                       'informacion_importante' => [
                            'tipo' => static::TIPO_ELEMENTO_TEXTO,
                            'grupo' => 'discapacidad',
                            'subgrupo' => 'importante'
			],
		];
		
	}

}