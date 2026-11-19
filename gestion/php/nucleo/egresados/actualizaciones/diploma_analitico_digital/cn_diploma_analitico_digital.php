<?php

use Clegginabox\PDFMerger\PDFMerger;

class cn_diploma_analitico_digital extends guarani_pers_cn
{
	
	const NOMBRE_ARCHIVO = 'archivo.pdf';
	
    protected $s__estadoSolicitud;
    protected $s__solicitudAprobada = false;
    protected $s__nro_solicitud;
    protected $s__tramite;
    protected $s__solicitud_sicer;
    protected $s__documento;
    protected $s__modelo_firma;
    protected $s__firmantes;
            
    function etiquetas()
    {
        return '';
    }

    function get_listado($filtro = '')
    {
		// Sólo solicitudes no anuladas.
		$where = 'sga_certificados_otorg.anulado = 0';
		if ($filtro) {
			$where .= " AND $filtro";
		}
		// Ordenados en forma descendente por fecha
		$order = 'sga_certificados_otorg.fecha_inicio_tramite DESC';
        
        // Obtengo los Trámites de Certificación
        $tramites = toba::consulta_php('co_tramites_certificacion')->get_listado($where, $order);
        
        foreach ($tramites as $key => $tramite) {
            // Obtiene la descripción del Trámite de Certificación desde SUDOCU.
            $tramites[$key]['nro_expediente_descr'] = sudocu::getDescripcionTramiteCertificacion($tramite);
        }
        
        return $tramites;
    }
    
    function setNroSolicitud($seleccion)
    {
        $this->s__nro_solicitud = $seleccion['nro_solicitud'];
		$this->setTramite($this->s__nro_solicitud);
    }

    function getNroSolicitud()
    {
        return $this->s__nro_solicitud;
    }

    function setTramite($nro_solicitud)
    {
        $filtro = "sga_certificados_otorg.nro_solicitud = {$nro_solicitud}";
        $tramite = $this->get_listado($filtro);
        $this->s__tramite = reset($tramite);
    }

    function getTramite()
    {
        return $this->s__tramite;
    }
	
	function setSolicitudSicer($solicitud_sicer)
    {
        $this->s__solicitud_sicer = $solicitud_sicer;
    }

    function getSolicitudSicer()
    {
        return $this->s__solicitud_sicer;
    }
	
	function setDocumento($documento)
    {
        $this->s__documento = $documento;
    }

    function getDocumento()
    {
        return $this->s__documento;
    }
    
    function getModeloFirma()
    {
        return $this->s__modelo_firma;
    }

    function setModeloFirma($modeloFirma)
    {
        $this->s__modelo_firma = $modeloFirma;
    }

    function cargar_firmantes()
    {
        if (!isset($this->s__firmantes)) {

            $documentoSidcer = $this->getDocumento();
            
            $diploma = false;
            $analitico = false;
            if ($documentoSidcer == sidcer::documentoDiploma) {
                $diploma = true;
            } elseif ($documentoSidcer == sidcer::documentoAnalitico) {
                $analitico = true;
            }

            $tramite = $this->getTramite();
            $propuesta = $tramite['propuesta'];
            $firmantes = toba::consulta_php('co_autoridades_cargos')->get_autoridades_firmantes_diploma_analitico_digital($propuesta, $diploma, $analitico);

            $this->s__firmantes = [];
            foreach ($firmantes as $firmante) {
                // El id de persons será el índice de cada firmante
                $id_fila = $firmante['persona'];
                $this->s__firmantes[$id_fila] = $firmante;
                $this->s__firmantes[$id_fila]['seleccionado'] = 0;
                $this->s__firmantes[$id_fila]['orden'] = null;
            }
        }

        return $this->s__firmantes;
    }

    function get_firmantes($seleccionado = true)
    {
        $condicion = $seleccionado ? 1 : 0;

        $firmantes = [];
        foreach ($this->s__firmantes as $id_fila => $firmante) {
            if ($firmante['seleccionado'] == $condicion) {
                $firmantes[$id_fila] = $firmante;
            }
        }

        $orden = $seleccionado ? 'orden' : 'apellido_nombre';
        // Ordeno los firmantes manteniendo las claves
        //https://www.php.net/manual/en/function.uasort.php
        uasort($firmantes, function ($a, $b) use ($orden) {

            // https://www.w3schools.com/Php/func_string_strcasecmp.asp
            return strcasecmp($a[$orden], $b[$orden]);
        });
        return $firmantes;
    }

    function procesar_firmantes($modificaciones)
    {
        foreach ($modificaciones as $id_fila => $modificacion) {
            $this->s__firmantes[$id_fila]['orden'] = $modificacion['orden'];
        }
    }

    function eliminar_firmante($id_fila)
    {
        $this->s__firmantes[$id_fila]['seleccionado'] = 0;
        $this->s__firmantes[$id_fila]['orden'] = null;
    }

    function agregar_firmante($id_fila)
    {
        $this->s__firmantes[$id_fila]['seleccionado'] = 1;
        $this->s__firmantes[$id_fila]['orden'] = $this->get_proximo_orden();
    }

    function get_proximo_orden()
    {
        return guarani_arreglos::count($this->get_firmantes(true)) + 1;
    }
    
    // Reset del botón "Volver" 
    function resetear()
    {
        unset($this->s__estadoSolicitud);
        unset($this->s__modelo_firma);
        unset($this->s__firmantes);
        $this->s__solicitudAprobada = false;
    }

    // Reset del botón "Cancelar" 
    function resetear_cancelar()
    {
        unset($this->s__estadoSolicitud);
        unset($this->s__modelo_firma);
        unset($this->s__firmantes);
        $this->s__solicitudAprobada = false;
    }

    // Reset del botón "Aceptar" 
    function resetear_aceptar()
    {
        unset($this->s__estadoSolicitud);
        unset($this->s__modelo_firma);
        unset($this->s__firmantes);
        $this->s__solicitudAprobada = false;
    }
    
    
    function desvincularDocumento()
    {
        // Obtengo datos del Trámite de Certificación
        $tramite = $this->getTramite();
        $nro_solicitud = $tramite['nro_solicitud'];

        $documentoSicer = $this->getDocumento();
        // Si el documento es Diploma
        if ($documentoSicer == sidcer::documentoDiploma) {
            $tipoDocSicer = "Diploma";
            $tipo_documento = sidcer::documentoDiplomaAbrev;
            // Si el documento es Certificado Analítico
        } elseif ($documentoSicer == sidcer::documentoAnalitico) {
            $tipoDocSicer = "Certificado Analítico";
            $tipo_documento = sidcer::documentoAnaliticoAbrev;
        }

        try {
            guarani::act('act_tramites_certificacion')->desvincular_documento_digital_arai($nro_solicitud, $tipo_documento);
            $this->informar_msg(guarani_mensajes::get('800_documento_desvinculado_firma_exito'), 'info');
        } catch (\Exception $e) {
            throw guarani::error($e->getMessage());
        }
    }

    function guardar()
    {
        $this->enviarFirma();
        $this->informar_msg(guarani_mensajes::get('800_documento_enviado_arai_exito'), 'info');
    }

    function get_estado_solicitud($solicitud)
    {
        if (!isset($this->s__estadoSolicitud)) {
            $estado = sidcer::getEstadoSolicitud(['solicitud' => $solicitud]);
			if ($estado['error']) {
				throw guarani::error(guarani_mensajes::get('800_error_solicitud_sidcer', [$estado['mensaje']]));
			}
            $this->s__estadoSolicitud = current($estado['solicitudes']);
        }
        return $this->s__estadoSolicitud;
    }
        
    public function solicitudAprobada()
    {
        return $this->s__solicitudAprobada;
    }
    
    /*
     * Verifica si la solicitud esta aprobada
     */
    public function estaAprobado($solicitud) 
    {
        $this->validarPersonaSolicitud($solicitud);        
        $datosSolicitud = $this->get_estado_solicitud($solicitud);
        
        //Validar que sea de la misma persona        
        if (!isset($datosSolicitud['error'])) {
            // El campo 'id_estado_solicitud' trae el último 'id_estado_tramite' del 'historial'
            //$ultimoEstado = sidcer::getUltimoEstadoSolicitudSicer($datosSolicitud['historial']);
            $ultimoEstado = $datosSolicitud['id_estado_solicitud'];
            $this->s__solicitudAprobada = in_array($ultimoEstado, [sidcer::solicitudAprobada, sidcer::solicitudFinalizada]);
            return $this->s__solicitudAprobada;
        }
        return false;
    }
    
    public function estaFinalizado($solicitud) 
    {
        $datosSolicitud = $this->get_estado_solicitud($solicitud);
        // El campo 'id_estado_solicitud' trae el último 'id_estado_tramite' del 'historial'
        //$ultimoEstado = sidcer::getUltimoEstadoSolicitudSicer($datosSolicitud['historial']);
        $ultimoEstado = $datosSolicitud['id_estado_solicitud'];
        return in_array($ultimoEstado, [sidcer::solicitudFinalizada]);
    }
    
    /*
     * Devuelve el estado del documento en sicer.
     */
    public function estadoDocumentoSicer($solicitud, $documento)
    {
        $datosSolicitud = $this->get_estado_solicitud($solicitud);
        // 'historial_documentos' puede venir en null
        $historialDocumentos = $datosSolicitud['historial_documentos'] ?? [];
		// Si no tiene estado por defecto es 'SIN_ENVIAR'
		$estadoDocumento = sidcer::ESTADO_DOCUMENTO_SIN_ENVIAR;
        foreach ($historialDocumentos as $historial) {
            if ($historial['documento'] == $documento) {
                $estadoDocumento = $historial['estado'];
            }
        }
        return $estadoDocumento;        
    }
    
    /*
     *  Verifica si la solicitud tuvo o tiene el estado deseado. 
     */
    protected function verificarEstado($historial, $estadoDeseado)
    {
        $estados = array_unique(array_column($historial, 'id_estado_tramite'));
        return in_array($estadoDeseado, $estados);
    }
    
    /*
     * Valida si la persona selecciona coincide con la persona de la solicitud buscada
     */
    public function validarPersonaSolicitud($solicitud)
    {
        $datosSolicitud = $this->get_estado_solicitud($solicitud);
        $tipo_documento = $datosSolicitud['tipo_documento'];
        $numero_documento = $datosSolicitud['numero_documento'];
		$tramite = $this->getTramite();
        if ($tipo_documento != $tramite['desc_tipo_documento'] ||
            $numero_documento != $tramite['nro_documento']) {
            throw guarani::error('La solicitud no corresponde para la persona');
        }
        return true;
    }
    
    /*
     * Retorna los documentos que tiene la solicitud
     */
    public function getDocumentos($solicitud)
    {   
		$tramite = $this->getTramite();
		$nro_solicitud = $tramite['nro_solicitud'];
		
        // Armo un array con los documentos que tengo y que le voy a enviar al cuadro 
        $documentos = [];
		$datos_diploma = toba::consulta_php('co_tramites_certificacion')->get_documento_digital_arai($nro_solicitud, sidcer::documentoDiplomaAbrev);
		$documentos[0]['documento'] = sidcer::documentoDiploma;
		$documentos[0]['documento_descr'] = 'Diploma';
		$documentos[0]['solicitud'] = $solicitud;
		$documentos[0]['estado'] = $this->getEstadoDocumento($datos_diploma);
		
		$datos_analitico = toba::consulta_php('co_tramites_certificacion')->get_documento_digital_arai($nro_solicitud, sidcer::documentoAnaliticoAbrev);
		$documentos[1]['documento'] = sidcer::documentoAnalitico;
		$documentos[1]['documento_descr'] = 'Analítico';
		$documentos[1]['solicitud'] = $solicitud;
		$documentos[1]['estado'] = $this->getEstadoDocumento($datos_analitico);

        return $documentos;
    }
	
	public function getEstadoDocumento($datos_documento)
    {
        // Los estados de los documentos deberán ser:
        // No Generado: Si aún no hay registros.
        if (empty($datos_documento)) {
            return sidcer::ESTADO_NO_GENERADO_DESC;
        } else {
            // Enviado a SiCer: Cuando está enviado a sidcer (enviado_a_sicer = S)
            if ($datos_documento['enviado_a_sicer'] == comunes::si) {
                return sidcer::ESTADO_ENVIADO_SIDCER_DESC;
            } else {

                $uidDocumento = $datos_documento['id_documento_digital'];
                $movimientosDocumento = arai_documentos::getMovimientosDocumento($uidDocumento);

                // Firmado: Cuando está firmado en araí pero no enviado a sicer (enviado_a_sicer = N)
                if (arai_documentos::esDocumentoAutorizadoByMovimientos($movimientosDocumento)) {
                    return sidcer::ESTADO_FIRMADO_DESC;
                    // Si el documento fue anulado
                } else if (arai_documentos::esDocumentoAnulado($movimientosDocumento)) {
                    return arai_documentos::DOCUMENTO_ANULADO;
                } else {
                    // Pendiente de Firma: si hay documento digital asociado pero aun sin firmar en araí.
                    return sidcer::ESTADO_PENDIENTE_FIRMA_DESC;
                }
            }
        }
    }

    /**
	 * @param string $id_documento_digital UID del documento de SIU-Araí.
	 * @return SIU\DocumentosCli\Entities\Documento.
	 */
	public function getDocumentoDesdeArai($id_documento_digital)
	{
		return arai_documentos::getDocumento($id_documento_digital);
	}
	
	/**
	 * @param string $id_documento_digital UID del documento de SIU-Araí.
	 * @return bool TRUE si el documento esta firmado, FALSE si no esta firmado.
	 */
	public function estaFirmadoDocumentoArai($id_documento_digital)
	{
		$documento = $this->getDocumentoDesdeArai($id_documento_digital);
		return ($documento['autorizado'] == 1);
	}
	
	public function getDocumentoDigitalArai()
	{
		$tramite = $this->getTramite();
		$nro_solicitud = $tramite['nro_solicitud'];
		
		$documentoSicer = $this->getDocumento();
		if ($documentoSicer == sidcer::documentoDiploma) {
			$tipo_documento = sidcer::documentoDiplomaAbrev;
        } elseif ($documentoSicer == sidcer::documentoAnalitico) {
			$tipo_documento = sidcer::documentoAnaliticoAbrev;
        }
		
		return toba::consulta_php('co_tramites_certificacion')->get_documento_digital_arai($nro_solicitud, $tipo_documento);
	}
    
    /*
     * Verifica que ya exista un registro en la tabla sga_certificados_otorg_documentos
     */
    public function existeCertificadoDocumento() 
    {
        return count($this->getCertificadoDocumento()) > 0;
    }
    
    public function getCertificadoDocumento()
    {
        $tramite = $this->getTramite();        
        $where = ' sga_certificados_otorg_documentos.nro_solicitud = '.$tramite['nro_solicitud'];
        $registros = toba::consulta_php('co_tramites_certificacion')->get_documentos_asociados_tramite_certificacion($where);
        return $registros;
    }
    
	public function descargarDocumentoOblea()
	{
		
		$documento_digital_arai = $this->getDocumentoDigitalArai();
		
		// Si el documento no existe en SIU-Araí lo genero
		if (empty($documento_digital_arai)) {
			arai_documentos::downloadFile($this->generarDocumentoOblea());
		} else {// Si el documento ya existe en SIU-Araí lo descargo desde allí
			arai_documentos::descargarDocumento($documento_digital_arai['id_documento_digital']);
		}
	}
    
    /*
     * Genero el Documento de Sidcer
     */
    public function generarDocumentoOblea()
    {
		$solicitudSicer = $this->getSolicitudSicer();
		$documentoSicer = $this->getDocumento();
		
        // Voy a buscar la oblea para el documento pasado por parametro.
        $etiqueta = sidcer::getEtiquetasSolicitud($solicitudSicer);
		if ($etiqueta['error']) {
			throw guarani::error(guarani_mensajes::get('800_error_etiquetas_sidcer', [$etiqueta['mensaje']]));
		}

        $pdfDocumento = $etiqueta['etiqueta'][strtolower($documentoSicer)];
        
        // Bajo el contenido del pdf a una variable.
        $content = file_get_contents($pdfDocumento);

        $pathTemp = toba::proyecto()->get_path_temp();
		$nombreArchivo = "{$pathTemp}/{$documentoSicer}_{$solicitudSicer}.pdf";
        
        // Guardo el contenido en el file system
        file_put_contents($nombreArchivo, $content);

        // Voy a mergear el contenido del archivo con la del Analitico o Certificado
        return $this->mergearCertificadoDocumento($nombreArchivo, $documentoSicer);    
    }
    
    /*
     * Mergeo del Documento de Sidcer con el Certificado de Guarani (Diploma o Analitico)
     */
    public function mergearCertificadoDocumento($archivoSidcer, $documentoSicer)
    {
        // Tengo el documento y dependiendo que sea uso un jasper o el otro        
        if ($documentoSicer == sidcer::documentoDiploma) {
            $archivoCertificado = $this->generarJasperDiploma();
        } elseif ($documentoSicer == sidcer::documentoAnalitico) {
			$archivoCertificado = $this->generarJasperAnalitico();
        }
       
        $pdfMerger = new PDFMerger();
        $pdfMerger->addPDF($archivoCertificado);
        $pdfMerger->addPDF($archivoSidcer);
		$pathTemp = toba::proyecto()->get_path_temp();
		$archivoTemp = static::NOMBRE_ARCHIVO;
		$pathCertificado = "$pathTemp/$archivoTemp";
        $pdfMerger->merge('file', $pathCertificado);
		unlink($archivoCertificado);
		unlink($archivoSidcer);

		return $pathCertificado;
    }
    
    /*
     * Genera el jasper del Analitico y devuelve el pdf.
     */
    protected function generarJasperAnalitico()
    {
		
		try {
                
			
			$report = new toba_vista_jasperreports();
			// Seteo la plantilla a usar
			$report->set_path_reporte(guarani::get_path_reporte_jasper(tramite_certificacion::plantillaAnalitico));
			
			$tramite = $this->getTramite();
			
			$report->set_parametro('logo', 'S', guarani::get_path_logo_impresion());
			$report->set_parametro('nro_solicitud', 'E', $tramite['nro_solicitud']);
			$param_nombre_persona_doc_oficiales = persona::get_param_nombre_persona_doc_oficiales();		
			$report->set_parametro('apellido_y_nombre', 'S', $param_nombre_persona_doc_oficiales);	
			
			$report->generar_salida();
			return $report->get_nombre_archivo_generado();

		} catch (\Exception $e) {

			toba::logger()->error($e);
			throw guarani::error($e->getMessage());

		}
		
    }
    
    /*
     * Genera el jasper del Diploma y devuelve el pdf.
	 * @see http://jasperreports.sourceforge.net/sample.reference/styledtext/ Estilos Jasper
	 * @see https://fonts2u.com/old-script.font Fuente
     */
    protected function generarJasperDiploma() 
    {
		try {
		
                
			$report = new toba_vista_jasperreports();
			// Seteo la plantilla a usar
			$report->set_path_reporte(guarani::get_path_reporte_jasper(tramite_certificacion::plantillaDiploma));
			
			$tramite = $this->getTramite();
			$pais_origen = toba::consulta_php('co_personas')->get_pais_origen_persona($tramite['persona']);
			$datos_extra = $this->getDatosExtraDiploma($tramite['propuesta']);
			
			$fecha_fin = (is_null($tramite['fecha_egreso']))? '' : DateTime::createFromFormat('Y-m-d', $tramite['fecha_egreso'])->format('d/m/Y');
			
			$report->set_parametro('logo', 'S', guarani::get_path_logo_impresion());
			$report->set_parametro('logo_republica', 'S', guarani::get_path_logo_republica());
			$report->set_parametro('borde', 'S', guarani::get_path_fondo_diploma());
			$report->set_parametro('frase_rector_decano', 'S', tramite_certificacion::frase_rector_decano_diploma());
			$report->set_parametro('localidad_institucion', 'S', $datos_extra['localidad_institucion']);
			$report->set_parametro('nombre_responsable_academica', 'S', $datos_extra['nombre_ra']);
			$report->set_parametro('nombre_institucion', 'S', $datos_extra['nombre_institucion']);
			$report->set_parametro('tipo_nro_documento', 'S', $tramite['documento_principal']);
			$report->set_parametro('nombre_titulo', 'S', $tramite['certificado_nombre']);
			$report->set_parametro('pais_origen', 'S', $pais_origen);
			$report->set_parametro('fecha_fin', 'S', $fecha_fin);
			$report->set_parametro('nombre_carrera', 'S', $tramite['propuesta_nombre_abrv']);
			$report->set_parametro('alumno_nombre_apellido', 'S', $tramite['apellido_y_nombres']);
			$report->set_parametro('fecha_actual', 'S', tramite_certificacion::fecha_actual_diploma());
			
			$report->generar_salida();
			return $report->get_nombre_archivo_generado();

		} catch (\Exception $e) {

			toba::logger()->error($e);
			throw guarani::error($e->getMessage());

		}
		
    }
	
	protected function getDatosExtraDiploma($propuesta) 
	{
		$datos_extra = [];
		
		$institucion_gestionante = toba::consulta_php('co_instituciones')->get_institucion_gestionante();
		$institucion = reset($institucion_gestionante);
		
		$ra = toba::consulta_php('co_propuestas')->get_ra_por_defecto($propuesta);
		$where = "sga_responsables_academicas.responsable_academica = {$ra}";
		$datos_ra = toba::consulta_php('co_responsables_academicas')->get_ras_institucion_instalacion($where);
		$datos_ra = reset($datos_ra);
		
		$datos_extra['nombre_institucion'] = $institucion['nombre'];
		$datos_extra['localidad_institucion'] = $institucion['localidad_nombre'];
		$datos_extra['nombre_ra'] = $datos_ra['descr'];
		
		return $datos_extra;
	}
    
    /*
     * Envia a la firma el documento anteriormente generado.
     */
    public function enviarFirma()
    {
        // Valido los firmantes seleccionados
        $this->validarFirmantesSeleccionados();        
        
        // Obtengo datos del Trámite de Certificación
		$tramite = $this->getTramite();
		$nro_solicitud = $tramite['nro_solicitud'];
		
		$documentoSicer = $this->getDocumento();
        // Si el documento es Diploma
		if ($documentoSicer == sidcer::documentoDiploma) {
            $tipoDocSicer = "Diploma";
			$tipo_documento = sidcer::documentoDiplomaAbrev;
        // Si el documento es Certificado Analítico
        } elseif ($documentoSicer == sidcer::documentoAnalitico) {
            $tipoDocSicer = "Certificado Analítico";
			$tipo_documento = sidcer::documentoAnaliticoAbrev;
        }
		
        // Recupero el Modelo de Firma ("Simple" o "Cadena / Escalonada") 
        $modeloFirma = $this->getModeloFirma();
        
        $documento_titulo = $this->getTituloDocumentoArai($tipoDocSicer, $tramite);
        $documento_descripcion = $this->getDescripcionDocumentoArai($tipoDocSicer, $tramite);
		
        // Recupero los firmantes seleccionados en orden
        $autoridades_firmantes = $this->get_firmantes(true);
        $autoridades_firmantes = array_column($autoridades_firmantes, 'valor');
        
		$solicitudSicer = $this->getSolicitudSicer();
		$parametros = [
			'solicitud_guarani' => $nro_solicitud, 
			'solicitud_sicer' => $solicitudSicer
		];
		$archivo = $this->generarDocumentoOblea();
        
        // Obtengo el parámetro "egr_usa_expediente_electronico" para el Trámite de Certificación
        $ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($tramite['propuesta']);
        $egr_usa_expediente_electronico = guarani::parametros()->get_valor(sudocu::EGR_USA_EXPEDIENTE_ELECTRONICO, $ras);
        $parametros['ras'] = $ras;

        $parametros['id_tramite'] = null;
        // Si el Trámite de Certificación usa EEI y tiene un expediente asociado
        if (($egr_usa_expediente_electronico == comunes::si) && !empty($tramite['nro_expediente'])) {
            $parametros['id_tramite'] = $tramite['nro_expediente'];
        }
        
		try {
			$uid_documento = sidcer::enviar_diploma_analitico_arai_documentos($documento_titulo, $documento_descripcion, $modeloFirma, $autoridades_firmantes, $parametros, $archivo);
			
			$documento_digital_arai = $this->getDocumentoDigitalArai();
		
			// Si el documento no existe en la tabla 'sga_certificados_otorg_documentos' lo agrego
			if (empty($documento_digital_arai)) {
				guarani::act('act_tramites_certificacion')->insertar_documento_digital_arai($nro_solicitud, $tipo_documento, $uid_documento, $solicitudSicer);
			} else {// Si existe en la tabla 'sga_certificados_otorg_documentos' lo actualizo
				guarani::act('act_tramites_certificacion')->actualizar_documento_digital_arai($nro_solicitud, $tipo_documento, $uid_documento, $solicitudSicer, comunes::no);
			}
		} catch (\Exception $e) {
			throw guarani::error($e->getMessage());
		}
    }
	
    public function validarFirmantesSeleccionados()
	{
		// Tomo las autoridades firmantes que selecciono el usuario
		$firmantesSeleccionados = $this->get_firmantes(true);
        $firmantesSeleccionados = array_column($firmantesSeleccionados, 'valor');
        
		// Si no se eligió ningún firmante
		if (empty($firmantesSeleccionados)) {
			throw guarani::error(guarani_mensajes::get('800_no_existen_firmantes'));
		}

		$firmantesSinUsuario = [];

		foreach ($firmantesSeleccionados as $firmante) {
			$aux = explode('|', $firmante);
			if ($aux[0] == arai_usuarios::SIU_USUARIO_ARAI) {
				$nombre = str_replace(",", "", $aux[1]);
				$firmantesSinUsuario[] = $nombre;
			}
		}

		// Si hay firmantes sin usuario de Araí asociado
		if (!empty($firmantesSinUsuario)) {
			throw new guarani_error(guarani_mensajes::get('800_autoridad_sin_usuario_arai', [implode(", ", $firmantesSinUsuario)]));
		}
	}
    
	/*
     * Envia documento SICer.
     */
	public function enviarDocumentoSicer()
	{
		$documento_digital_arai = $this->getDocumentoDigitalArai();
		$id_tramite = $documento_digital_arai['nro_tramite_sicer'];
		if ($documento_digital_arai['tipo_documento'] == sidcer::documentoDiplomaAbrev) {
			$id_tipo_documentacion = sidcer::documentoDiplomaCod;
			$tipoDocSicer = "diploma";
		} else if ($documento_digital_arai['tipo_documento'] == sidcer::documentoAnaliticoAbrev) {
			$id_tipo_documentacion = sidcer::documentoAnaliticoCod;
			$tipoDocSicer = "analitico";
		}
		
		$nombre_archivo = "{$tipoDocSicer}_{$id_tramite}.pdf";
		
		$archivo = arai_documentos::descargarDocumentoToFilesystem($documento_digital_arai['id_documento_digital']);
		$resultado = sidcer::postDocumentacion($id_tramite, $id_tipo_documentacion, $nombre_archivo, $archivo);
		unlink($archivo);
		// Si salio todo bien pongo enviado_a_sicer = 'S'
		if (!$resultado['error']) {
			guarani::act('act_tramites_certificacion')->actualizar_documento_digital_arai($documento_digital_arai['nro_solicitud'], $documento_digital_arai['tipo_documento'], null, null, comunes::si);
		}
		return $resultado;
	}

       /**
        * Devuelve el título para el documento de Araí.
        * @param string $tipoDocSicer Diploma o Certificado Analítico.
        * @param array $tramite Datos del Trámite de Certificación.
        * @return string Título para el documento de Araí.
        */
        public function getTituloDocumentoArai($tipoDocSicer, $tramite)
        {
            return "{$tipoDocSicer} - {$tramite['certificado_nombre']} - {$tramite['documento_principal']} - {$tramite['apellido_y_nombres']}";
        }

       /**
        * Devuelve la descripción para el documento de Araí.
        * @param string $tipoDocSicer Diploma o Certificado Analítico.
        * @param array $tramite Datos del Trámite de Certificación.
        * @return string Descripción para el documento de Araí.
        */
        public function getDescripcionDocumentoArai($tipoDocSicer, $tramite)
        {
            return "{$tipoDocSicer} - {$tramite['certificado_nombre']} - {$tramite['documento_principal']} - {$tramite['apellido_y_nombres']}";
        }
}