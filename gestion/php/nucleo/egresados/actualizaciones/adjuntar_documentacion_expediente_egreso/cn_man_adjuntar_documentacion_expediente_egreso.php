<?php
/**
 *
 * @package Egresados
 * @subpackage Actualizaciones
 */
class cn_man_adjuntar_documentacion_expediente_egreso extends cn_manejador_entidad
{
	
	protected $s__nro_solicitud;
	protected $s__datos_tramite;
	protected $s__documentos_adjuntados;
	protected $s__documento_digital_arai;
	protected $s__formulario;
	protected $s__formularios;
	protected $s__links_impresion_formularios;
	protected $s__modelo_firma;
	protected $s__firmantes;
	
	function etiquetas($dep=null)
	{
		return is_null($dep) ? guarani_etiquetas::get_tabla('sga_certificados_otorg') : parent::etiquetas($dep);
	} 

	function get_listado($filtro)
	{
		// Sólo solicitudes no anuladas.
		$filtro .= ' AND sga_certificados_otorg.anulado = 0';
		// Sólo solicitudes con expediente.
		$filtro .= ' AND sga_certificados_otorg.nro_expediente IS NOT NULL';
		
		$order = "sga_certificados_otorg.fecha_cambio_estado DESC";
		
		$tramites = toba::consulta_php('co_tramites_certificacion')->get_listado($filtro, $order);
		
		foreach ($tramites as $key => $tramite) {
			$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($tramite['propuesta']);
			$egr_usa_expediente_electronico = guarani::parametros()->get_valor(sudocu::EGR_USA_EXPEDIENTE_ELECTRONICO, $ras);
			// Si no usa expediente electrónico no listo el trámite
			if ($egr_usa_expediente_electronico == comunes::no) {
				unset($tramites[$key]);
			}
		}
		
		return $tramites;
	}
	
    // Reset del botón "Volver" 
    function resetear()
    {
        unset($this->s__nro_solicitud);
        unset($this->s__datos_tramite);
        unset($this->s__documentos_adjuntados);
        unset($this->s__documento_digital_arai);
        unset($this->s__formulario);
        unset($this->s__formularios);
        unset($this->s__links_impresion_formularios);
        unset($this->s__modelo_firma);
        unset($this->s__firmantes);
    }

    // Reset del botón "Cancelar" 
    function resetear_cancelar()
    {
        unset($this->s__documento_digital_arai);
        unset($this->s__formulario);
        unset($this->s__modelo_firma);
        unset($this->s__firmantes);
    }

    // Reset del botón "Adjuntar" 
    function resetear_adjuntar()
    {
        unset($this->s__documentos_adjuntados);
        unset($this->s__documento_digital_arai);
        unset($this->s__formulario);
        unset($this->s__formularios);
        unset($this->s__links_impresion_formularios);
        unset($this->s__modelo_firma);
        unset($this->s__firmantes);
    }

    function cargar_firmantes()
	{
        if (!isset($this->s__firmantes)) {
            
            $datos_tramite = $this->getDatosTramite();
            $propuesta = $datos_tramite['propuesta'];
            $firmantes = toba::consulta_php('co_autoridades_cargos')->get_autoridades_firmantes_diploma_analitico_digital($propuesta, false, false, true);
            
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
    
    function getNroSolicitud()
	{
		return $this->s__nro_solicitud;
	}
	
	function setNroSolicitud($nroSolicitud)
	{
		$this->s__nro_solicitud = $nroSolicitud;
	}
	
	function getDatosTramite()
	{
		return $this->s__datos_tramite;
	}
	
	function setDatosTramite($datosTramite)
	{
		$this->s__datos_tramite = $datosTramite;
	}
	
	function getDocumentoDigitalArai()
	{
		return $this->s__documento_digital_arai;
	}
	
	function setDocumentoDigitalArai($documento_digital_arai)
	{
		$this->s__documento_digital_arai = $documento_digital_arai;
	}
	
	public function descargarDocumento()
	{
		$documento_digital_arai = $this->getDocumentoDigitalArai();
		arai_documentos::descargarDocumento($documento_digital_arai);
	}

    /*
     * Obtiene los "Documentos Adjuntados" y se fija el estado en Araí Documentos
     */
    function getDocumentosAdjuntados()
    {
        if (!isset($this->s__documentos_adjuntados)) {
            $nro_solicitud = $this->getNroSolicitud();
            $this->s__documentos_adjuntados = toba::consulta_php('co_tramites_certificacion')->get_documentos_adjuntados_expediente_egreso($nro_solicitud);
            foreach ($this->s__documentos_adjuntados as $key => $documento_adjuntado) {

                $uidDocumento = $documento_adjuntado['id_documento_digital'];
                $movimientosDocumento = arai_documentos::getMovimientosDocumento($uidDocumento);

                // Si el documento esta autorizado / firmado
                if (arai_documentos::esDocumentoAutorizadoByMovimientos($movimientosDocumento)) {
                    $this->s__documentos_adjuntados[$key]['estado'] = arai_documentos::DOCUMENTO_FIRMADO;
                // Si el documento fue anulado
                } else if (arai_documentos::esDocumentoAnulado($movimientosDocumento)) {
                    $this->s__documentos_adjuntados[$key]['estado'] = arai_documentos::DOCUMENTO_ANULADO;
                } else {// Aún esta pendiente de firma
                    $this->s__documentos_adjuntados[$key]['estado'] = arai_documentos::DOCUMENTO_NO_FIRMADO;
                }
            }
        }

        return $this->s__documentos_adjuntados;
    }

    function getFormularios()
	{
		if (!isset($this->s__formularios)) {
			$nro_solicitud = $this->getNroSolicitud();
			$this->s__formularios = toba::consulta_php('co_circuitos_tramite')->get_listado_formularios_expediente_por_nro_solicitud($nro_solicitud);
		}
		
		return $this->s__formularios;
	}
	
	function getFormulario()
	{
		return $this->s__formulario;
	}
	
	function setFormulario($formulario)
	{
		$this->s__formulario = $formulario;
	}
    
    function getModeloFirma()
	{
		return $this->s__modelo_firma;
	}
	
	function setModeloFirma($modeloFirma)
	{
		$this->s__modelo_firma = $modeloFirma;
	}
    
    /*
	 * Genera links a los jaspers de los formularios.
	 * Se basa en los siguientes archivos:
	 * php/operaciones/egresados/actualizaciones/actualizar_tramites_certificacion/pant_resumen.php
	 * php/operaciones/_lib/ci_imprimir_formulario.php
	 */
    function getLinksImpresionFormularios()
    {

        if (!isset($this->s__links_impresion_formularios)) {
            
            // Obtengo todos los formularios
            $formularios = $this->getFormularios();

            $links_impresiones = [];

            foreach ($formularios as $formulario) {

                // Obtengo los datos a pasar a la plantilla
                $nro_solicitud = $this->getNroSolicitud();
                $datos_tramite = toba::consulta_php('co_tramites_certificacion')->get_datos_tramite_todos($nro_solicitud);

                // Obtengo los campos a completar para un circuito
                $campos = toba::consulta_php('co_circuitos_tramite')->get_campos_por_circuito_estado_accion($datos_tramite['circuito'], $datos_tramite['estado']);

                $datos_plantilla = [];
                foreach ($campos as $campo) {
                    $datos_campo = current(toba::consulta_php('co_campos')->get_datos_campo($campo['campo']));
                    $datos_plantilla[] = ['nombre' => $campo['campo'], 'valor' => $datos_tramite[$campo['campo']], 'tipo_de_dato' => $datos_campo['tipo_de_dato']];
                }
                $datos_plantilla[] = ['nombre' => 'nro_solicitud', 'tipo_de_dato' => 'E', 'valor' => $datos_tramite['nro_solicitud']];

                // Obtengo la plantilla para el formulario
                $plantilla_formulario = toba::consulta_php('co_circuitos_tramite')->get_plantilla_by_formulario($formulario['formulario']);
                $plantilla = $plantilla_formulario['archivo_plantilla'];

                // Genero link a la operación con ID 32000020 (Imprimir Formulario)
                toba::memoria()->set_dato("{$plantilla}__parametros", $datos_plantilla);
                // Importante pasar "celda_memoria" para que no borre los datos de la sesión de la operación.
                $vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '32000020', ['plantilla' => $plantilla], ['servicio' => 'vista_jasperreports', 'celda_memoria' => 'celda_impresion']);

                $links_impresiones[$formulario['formulario']] = $vinculo;
            }
            
            $this->s__links_impresion_formularios = $links_impresiones;
            
        }
        
        return $this->s__links_impresion_formularios;
    }

    /**
     * Desvincula el documento del Expediente de Egreso.
     * 
     * @param int $formulario
     */
    function desvincularDocumento($formulario)
    {
        try {
            $nro_solicitud = $this->getNroSolicitud();
            // Desvinculo en la DB
            guarani::act('act_tramites_certificacion')->desvincular_expediente_egreso_arai($nro_solicitud, $formulario);
            // Limpio variables para que vuelva a cargar desde la DB
            unset($this->s__documentos_adjuntados);
            unset($this->s__formularios);
            unset($this->s__links_impresion_formularios);
        } catch (\Exception $e) {
            throw guarani::error($e->getMessage());
        }
    }

    function validarFirmantesSeleccionados()
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
	
	
	/*
	 * Genera el jasper del formulario seleccionado.
	 * Se basa en los siguientes archivos:
	 * php/operaciones/egresados/actualizaciones/actualizar_tramites_certificacion/pant_resumen.php
	 * php/operaciones/_lib/ci_imprimir_formulario.php
	 */
	function generarPdfExpedienteEgreso()
	{
        
        $datos_plantilla = [];
        
		try {

			$formulario = $this->getFormulario();
            // Obtengo la plantilla a usar
			$plantilla_formulario = toba::consulta_php('co_circuitos_tramite')->get_plantilla_by_formulario($formulario);

			$report = new toba_vista_jasperreports();
			// Seteo la plantilla a usar
			$report->set_path_reporte(guarani::get_path_reporte_jasper($plantilla_formulario['archivo_plantilla']));

			// Obtengo los datos a pasar a la plantilla
			$nro_solicitud = $this->getNroSolicitud();
			$datos_tramite = toba::consulta_php('co_tramites_certificacion')->get_datos_tramite_todos($nro_solicitud);

            // Obtengo los campos a completar para un circuito
			$campos = toba::consulta_php('co_circuitos_tramite')->get_campos_por_circuito_estado_accion($datos_tramite['circuito'], $datos_tramite['estado']);

			foreach ($campos as $campo) {
				$datos_campo = current(toba::consulta_php('co_campos')->get_datos_campo($campo['campo']));
				$datos_plantilla[] = ['nombre' => $campo['campo'], 'valor' => $datos_tramite[$campo['campo']], 'tipo_de_dato' => $datos_campo['tipo_de_dato']];
			}
			$datos_plantilla[] = ['nombre' => 'nro_solicitud', 'tipo_de_dato' => 'E', 'valor' => $datos_tramite['nro_solicitud']];

			//Seteo el logo de la Institución correspondiente, si no existe seteo el logo de Guarani
			$path_logo = guarani::get_path_logo_impresion();
			$report->set_parametro('logo', 'S', $path_logo);

			foreach ($datos_plantilla as $param) {
				/** Los parámetros de tipo Fecha que se obtienen de la base (mce_campos en el circuito de egreso), 
				 *  se convierten a tipo string con el formato visual adecuado, ya que el set parametro de la salida 
				 *  jasper de Toba los pasa como bigdecimal a java, asumiendo que es un timestamp y en este caso, los
				 *  campos de mce_campos son de tipo date y no pueden pasarse con ese tipo en jasper.
				 */
				if ($param['tipo_de_dato'] == 'F') {
					if (isset($param['valor'])) {
						$param['valor'] = guarani_fecha::convertir_fecha_formato_frontend($param['valor']);
					} else {
						$param['valor'] = '';
					}
				} elseif (!isset($param['valor'])) {
					$param['valor'] = '';
					$param['tipo_de_dato'] = 'S';
				}
				$report->set_parametro($param['nombre'], $param['tipo_de_dato'], $param['valor']);
			}

			$report->generar_salida();
			return $report->get_nombre_archivo_generado();
			
		} catch (\Exception $e) {
			
			toba::logger()->error($e);
			throw guarani::error(guarani_mensajes::get('800_error_generar_pdf', [$datos_plantilla['nombre']]));
			
		}
	}
	
	
	/*
	 * Envia a la firma el Expediente de Egreso.
	 */
	function enviarFirmaExpedienteEgreso()
	{
		
		$datos_tramite = $this->getDatosTramite();
		$formulario = $this->getFormulario();
        
		$modeloFirma = $this->getModeloFirma();

		$datos_formulario = current(toba::consulta_php('co_circuitos_tramite')->get_datos_formulario($formulario));
		
		$documento_titulo = $this->getTituloDocumentoArai($datos_tramite, $datos_formulario);
		$documento_descripcion =$this->getDescripcionDocumentoArai($datos_tramite, $datos_formulario);
                $autoridades_firmantes = $this->get_firmantes(true);
                $autoridades_firmantes = array_column($autoridades_firmantes, 'valor');
		$parametros = [
			'id_tramite'	=> $datos_tramite['nro_expediente'],
			'nro_solicitud' => $datos_tramite['nro_solicitud'],
			'propuesta'     => $datos_tramite['propuesta']
		];
		
		$archivo = $this->generarPdfExpedienteEgreso();

		try {
			
			$uid_documento = sudocu::enviar_expediente_egreso_arai_documentos($documento_titulo, $documento_descripcion, $modeloFirma, $autoridades_firmantes, $parametros, $archivo);
			
			guarani::act('act_tramites_certificacion')->actualizar_expediente_egreso_arai($datos_tramite['nro_solicitud'], $formulario, $uid_documento);
            
		} catch (\Exception $e) {
			
			throw guarani::error($e->getMessage());
			
		}
	}
	
	function guardar()
	{
		
		$this->validarFirmantesSeleccionados();
		
		$this->enviarFirmaExpedienteEgreso();
		
	}

       /**
        * Devuelve el título para el documento de Araí.
        * @param array $datos_tramite Datos del Trámite.
        * @param array $datos_formulario Datos del Formulario.
        * @return string Título para el documento de Araí.
        */
        public function getTituloDocumentoArai($datos_tramite, $datos_formulario)
        {
            $apellido_nombres = str_replace(",", "", $datos_tramite['apellido_y_nombres']);

            return "{$datos_formulario['formulario_nombre']} - {$apellido_nombres}";
        }

       /**
        * Devuelve la descripción para el documento de Araí.
        * @param array $datos_tramite Datos del Trámite.
        * @param array $datos_formulario Datos del Formulario.
        * @return string Descripción para el documento de Araí.
        */
        public function getDescripcionDocumentoArai($datos_tramite, $datos_formulario)
        {
            return $datos_tramite['certificado_nombre'];
        }

}