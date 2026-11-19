<?php
class ci_edi_diploma_analitico_digital extends guarani_pers_ci
{   
    protected $s__datos;
    
    // Flag para indicar cuándo debe abrirse la ventana de impresión en jasper
	protected $s__imprimir = false;
	protected $s__solicitud_sicer;
	protected $s__documento;
    
	protected $firmantes_seleccionados;

    function resetear_variables()
    {
        $this->cn()->resetear();
        unset($this->s__datos);
    }

    //-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
        if ($this->cn()->existeCertificadoDocumento()) {
            $certificadoDocumento = $this->cn()->getCertificadoDocumento();
            $certificadoDocumento = reset($certificadoDocumento);
            $datos['solicitud'] = $certificadoDocumento['nro_tramite_sicer'];            
            $this->evt__filtro_sidcer__buscar($datos);
            $this->dep('filtro_sidcer')->evento('buscar')->desactivar();            
            $this->dep('filtro_sidcer')->ef('solicitud')->set_solo_lectura();            
        }
        
        if (!$this->cn()->solicitudAprobada()) {
            $pantalla->eliminar_dep('cuadro_estado_sidcer');
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_estado_sidcer ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_estado_sidcer(guarani_pers_ei_cuadro $cuadro)
	{
        //Si ya llego acá es porque el trámite ya esta aprobado.
        if ($this->cn()->solicitudAprobada()) {
            $documentos = $this->cn()->getDocumentos($this->s__datos['solicitud']);
            $cuadro->set_datos($documentos);
        }
        
	}

	function evt__cuadro_estado_sidcer__ver($datos)
	{
        $this->s__solicitud_sicer = $datos['solicitud'];
        $this->s__documento = $datos['documento'];
		$this->s__imprimir = true;
	}

	function evt__cuadro_estado_sidcer__enviar($datos)
	{
		$this->cn()->setSolicitudSicer($datos['solicitud']);
		$this->cn()->setDocumento($datos['documento']);
		
		$this->set_pantalla('pant_datos');
	}
    
    function evt__cuadro_estado_sidcer__desvincular($datos)
    {
        $this->cn()->setSolicitudSicer($datos['solicitud']);
        $this->cn()->setDocumento($datos['documento']);

        $this->cn()->desvincularDocumento();
    }

    function evt__cuadro_estado_sidcer__enviar_sicer($datos)
	{
		$this->cn()->setSolicitudSicer($datos['solicitud']);
		$this->cn()->setDocumento($datos['documento']);
		
		$resultado = $this->cn()->enviarDocumentoSicer();
		// Si hubo error en SICer
		if ($resultado['error']) {
			throw guarani::error(guarani_mensajes::get('800_fallo_envio_documento_sicer'));
		} else {// si salio todo bien
			$this->informar_msg(guarani_mensajes::get('800_documento_enviado_sicer_exito'), 'info');
		}
	}
       
    /*
     * @see https://redmine.siu.edu.ar/redmine/projects/guarani3/wiki/Casos_de_UsoAdministrar_diploma_Analitico_Digital Caso de uso.
     * Este botón deberá estar deshabilitado si el trámite en SIDCer ya está finalizado o si el documento está enviado (estadosolicitud/get).
     */
    function conf_evt__cuadro_estado_sidcer__enviar(toba_evento_usuario $evento, $fila)
    {
        // Todos los datos del cuadro
        $datos = $this->dep('cuadro_estado_sidcer')->get_datos();
        // Datos de la fila
        $datos_fila = $datos[$fila];

        // Limpio el evento
        $evento->activar();
        $evento->set_msg_ayuda('');
        
        $this->cn()->setSolicitudSicer($datos_fila['solicitud']);
		$this->cn()->setDocumento($datos_fila['documento']);
        
        // Obtengo documento de la tabla 'sga_certificados_otorg_documentos'
        $documento_digital_arai = $this->cn()->getDocumentoDigitalArai();
        
        // El botón 'Enviar a la Firma' se debería deshabilitar una vez que se crea el documento en Araí.
        if (!empty($documento_digital_arai)) {
            $evento->desactivar();
            $evento->set_msg_ayuda(guarani_mensajes::get('800_documento_ya_enviado_firma'));
            return false;
        }
        
        // Si el trámite en SIDCer ya está finalizado 
        if ($this->cn()->estaFinalizado($datos_fila['solicitud'])) {
            $evento->desactivar();
            $evento->set_msg_ayuda(guarani_mensajes::get('800_tramite_sicer_finalizado'));
            return false;
        }

        $estadoDocumento = $this->cn()->estadoDocumentoSicer($datos_fila['solicitud'], $datos_fila['documento']);
        // Si el documento está enviado (estadosolicitud/get)
        if ($estadoDocumento == sidcer::ESTADO_DOCUMENTO_ENVIADO) {
            $evento->desactivar();
            $evento->set_msg_ayuda(guarani_mensajes::get('800_documento_sicer_enviado'));
            return false;
        }
    }

    function conf_evt__cuadro_estado_sidcer__desvincular(toba_evento_usuario $evento, $fila)
    {
        // Todos los datos del cuadro
        $datos = $this->dep('cuadro_estado_sidcer')->get_datos();
        // Datos de la fila
        $datos_fila = $datos[$fila];

        // Limpio el evento
        $evento->activar();
        $evento->set_msg_ayuda('');

        // Si el documento no esta anulado no lo dejo "Desvincular de la Firma"
        if ($datos_fila['estado'] != arai_documentos::DOCUMENTO_ANULADO) {
            $evento->desactivar();
            $evento->set_msg_ayuda(guarani_mensajes::get('800_documento_desvincular_firma'));
            return false;
        }
    }
    /*
     * @see https://redmine.siu.edu.ar/redmine/projects/guarani3/wiki/Casos_de_UsoAdministrar_diploma_Analitico_Digital Caso de uso.
     * Este botón sólo se habilitará cuando el documento correspondiente ya se encuentre firmado en ARAI DOCUMENTOS y cuando la documentación se encuentre Sin Enviar o Rechazada (estadosolicitud/get).
     */
    function conf_evt__cuadro_estado_sidcer__enviar_sicer(toba_evento_usuario $evento, $fila)
	{
        // Todos los datos del cuadro
        $datos = $this->dep('cuadro_estado_sidcer')->get_datos();
        // Datos de la fila
        $datos_fila = $datos[$fila];
        
        // Limpio el evento
        $evento->activar();
        $evento->set_msg_ayuda('');
        
        $this->cn()->setSolicitudSicer($datos_fila['solicitud']);
		$this->cn()->setDocumento($datos_fila['documento']);
        
        // Obtengo documento de la tabla 'sga_certificados_otorg_documentos'
        $documento_digital_arai = $this->cn()->getDocumentoDigitalArai();

        // Si el documento no fue enviado a la firma
        if (empty($documento_digital_arai)) {
            $evento->desactivar();
            $evento->set_msg_ayuda(guarani_mensajes::get('800_documento_no_enviado_firma'));
            return false;
        }
        
        // Si el documento ya fue enviado a SICer
        if ($documento_digital_arai['enviado_a_sicer'] == comunes::si) {
            $evento->desactivar();
            $evento->set_msg_ayuda(guarani_mensajes::get('800_documento_ya_enviado_sicer'));
            return false;
        }
        
        $estaFirmado = $this->cn()->estaFirmadoDocumentoArai($documento_digital_arai['id_documento_digital']);

        // Si el documento no esta firmado en SIU-Araí
        if (!$estaFirmado) {
            $evento->desactivar();
            $evento->set_msg_ayuda(guarani_mensajes::get('800_no_firmado_documento_arai'));
            return false;
        }
        
        // Si el trámite en SIDCer ya está finalizado 
        if ($this->cn()->estaFinalizado($datos_fila['solicitud'])) {
            $evento->desactivar();
            $evento->set_msg_ayuda(guarani_mensajes::get('800_tramite_sicer_finalizado'));
            return false;
        }
        
		$estadoDocumento = $this->cn()->estadoDocumentoSicer($datos_fila['solicitud'], $datos_fila['documento']);
        // Si el estado de la solicitud es finalizada o el estado del documento en SICer es distinto a 'SIN_ENVIAR' u 'OBSERVADO' desactivo el evento.
        if(!in_array($estadoDocumento, [sidcer::ESTADO_DOCUMENTO_SIN_ENVIAR, sidcer::ESTADO_DOCUMENTO_OBSERVADO])) {
            $evento->desactivar();
            $evento->set_msg_ayuda(guarani_mensajes::get('800_solicitud_sicer_sin_enviar_observada'));
            return false;
        }
    }
    
	function vista_jasperreports(toba_vista_jasperreports $report)
	{
		$this->cn()->setSolicitudSicer($this->s__solicitud_sicer);
		$this->cn()->setDocumento($this->s__documento);
		$this->cn()->descargarDocumentoOblea();
		exit();
	}
    
    function extender_objeto_js()
	{
		if ($this->s__imprimir) {
			$this->s__imprimir = false;
			echo "window.open(vinculador.get_url(null, null, 'vista_jasperreports', null, [{$this->objeto_js}._id]));";
		}
	}

    
	//-----------------------------------------------------------------------------------
	//---- Pantalla Datos --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_datos(toba_ei_pantalla $pantalla)
	{
        // Obtengo todos los firmantes
        $firmantes = $this->cn()->cargar_firmantes();
        
        // Si no hay firmantes deshabilito el botón "Aceptar"
        if (empty($firmantes)) {
            $this->evento('aceptar')->desactivar();
            $this->evento('aceptar')->set_msg_ayuda(guarani_mensajes::get('800_sin_autoridades_firmantes'));
        }
	}
	
    //-----------------------------------------------------------------------------------
    //---- form_modelo_firma -------------------------------------------------------------------
    //-----------------------------------------------------------------------------------

    function conf__form_modelo_firma(toba_ei_formulario $form)
    {
        $datos = [];

        $modeloFirma = $this->cn()->getModeloFirma();
        // Si se seleccionó un 'Modelo de Firma' lo seteo por defecto
        if (!is_null($modeloFirma)) {
            $datos['modelo_firma'] = $modeloFirma;
        }

        $form->set_datos_defecto($datos);
    }

    function evt__form_modelo_firma__modificacion($datos)
    {
        $this->cn()->setModeloFirma($datos['modelo_firma']);
    }

    //-----------------------------------------------------------------------------------
	//---- ml_firmantes_seleccionados ---------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__ml_firmantes_seleccionados(toba_ei_formulario_ml $form_ml)
	{
		$firmantes_seleccionados = $this->cn()->get_firmantes(true);
		$form_ml->set_datos($firmantes_seleccionados);
	}

	function evt__ml_firmantes_seleccionados__eliminar($seleccion)
	{
		$this->cn()->eliminar_firmante($seleccion);
	}

	function evt__ml_firmantes_seleccionados__modificacion($datos)
	{
		try {
			$this->cn()->procesar_firmantes($datos);
		} catch (guarani_error $e) {
			throw guarani::error($e->getMessage());
		}
	}
    
    //-----------------------------------------------------------------------------------
	//---- cuadro_firmantes_disponibles -------------------------------------------------
	//-----------------------------------------------------------------------------------
    
    function conf__cuadro_firmantes_disponibles(cuadro_seleccion_multiple $cuadro)
	{
		$cuadro->set_eof_mensaje(guarani_mensajes::get('800_eof_cuadro', ["firmantes disponibles"]));

		$firmantes_disponibles = $this->cn()->get_firmantes(false);
		$cuadro->set_datos($firmantes_disponibles);
	}

	function evt__cuadro_firmantes_disponibles__seleccion($seleccion)
	{
		$this->firmantes_seleccionados = $seleccion;
	}
	
	function evt__cuadro_firmantes_disponibles__agregar()
	{
		if (empty($this->firmantes_seleccionados)) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('un registro'));
			throw guarani::error($mje);
		}
		foreach ($this->firmantes_seleccionados as $firmante) {
			$this->cn()->agregar_firmante($firmante['persona']);
		}
	}
    
	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
    // Vuelve al listado de diplomas y analíticos.
    function evt__volver()
    {
        $this->cn()->resetear();
        $this->controlador()->set_pantalla('seleccion');
    }
    
    // Cancela la edición
    function evt__cancelar()
    {
        $this->cn()->resetear_cancelar();
        $this->set_pantalla('pant_inicial');
    }
    
    // Integra Diploma o Analítico al EEI
    function evt__aceptar()
    {
        $this->cn()->guardar();
        $this->cn()->resetear_aceptar();
        $this->set_pantalla('pant_inicial');
    }
    
	//-----------------------------------------------------------------------------------
	//---- filtro_sidcer ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_sidcer(guarani_pers_ei_formulario $form)
	{
		$form->ef('solicitud')->set_expreg(comunes::er_numero_entero);

        if (isset($this->s__datos)) {
            return $this->s__datos;
        }
	}

	function evt__filtro_sidcer__buscar($datos)
	{
        //Voy a buscar si el estado del trámite tiene o tuvo el estado aprobado.
        $this->cn()->resetear();
        $this->s__datos = $datos;
        if (!$this->cn()->estaAprobado($datos['solicitud'])) {
            throw guarani::error("Obleas no Disponibles");
        }
    }
}