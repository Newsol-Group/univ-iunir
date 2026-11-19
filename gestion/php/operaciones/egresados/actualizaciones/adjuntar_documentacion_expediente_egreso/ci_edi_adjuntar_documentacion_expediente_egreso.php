<?php
/**
 * 
 *
 * @package 	Egresados
 * @subpackage 	Actualizaciones
 * 
 */
class ci_edi_adjuntar_documentacion_expediente_egreso extends guarani_pers_ci
{
	
	protected $s__imprimir = false;
    
    protected $firmantes_seleccionados;
    
    //-----------------------------------------------------------------------------------
	//---- PANTALLA INICIAL -------------------------------------------------------------
	//-----------------------------------------------------------------------------------
    
    //-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$documentos_adjuntados = $this->cn()->getDocumentosAdjuntados();
		$cuadro->set_datos($documentos_adjuntados);
	}
	
	function evt__cuadro__ver($seleccion)
	{
		$this->cn()->setDocumentoDigitalArai($seleccion['id_documento_digital']);
		$this->s__imprimir = true;
	}
    
    function evt__cuadro__desvincular($seleccion)
    {
        $this->cn()->desvincularDocumento($seleccion['formulario']);
    }

    function conf_evt__cuadro__desvincular(toba_evento_usuario $evento, $fila)
    {
        $datos = $this->dep('cuadro')->get_datos();
        // Si el documento no esta anulado no lo dejo "Desvincular"
        if ($datos[$fila]['estado'] != arai_documentos::DOCUMENTO_ANULADO) {
            $evento->anular();
        }
    }

    function vista_jasperreports(toba_vista_jasperreports $report)
	{
		$this->cn()->descargarDocumento();
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
	//---- PANTALLA DATOS -------------------------------------------------------------
	//-----------------------------------------------------------------------------------
    
    function conf__pant_datos(toba_ei_pantalla $pantalla)
    {
        $firmantes = $this->cn()->cargar_firmantes();
        
        // Si no hay firmantes deshabilito el botón "Adjuntar"
        if (empty($firmantes)) {
            $this->evento('adjuntar')->desactivar();
            $this->evento('adjuntar')->set_msg_ayuda(guarani_mensajes::get('800_sin_autoridades_firmantes'));
        }
    }

    //-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__formulario(toba_ei_formulario $form)
	{
        $datos = [];
        
		$formulario = $this->cn()->getFormulario();
		// Si se seleccionó un 'Formulario' lo seteo por defecto
		if (!is_null($formulario)) {
			$datos['formulario'] = $formulario;
		}
        
        $modeloFirma = $this->cn()->getModeloFirma();
        // Si se seleccionó un 'Modelo de Firma' lo seteo por defecto
		if (!is_null($modeloFirma)) {
			$datos['modelo_firma'] = $modeloFirma;
		}
        
        $form->set_datos_defecto($datos);
	}

	function evt__formulario__modificacion($datos)
	{
		$this->cn()->setFormulario($datos['formulario']);
		$this->cn()->setModeloFirma($datos['modelo_firma']);
	}

	function get_listado_formularios_expediente()
	{
		return $this->cn()->getFormularios();
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

    // Vuelve al listado de trámites de certificación con expediente electrónico.
    function evt__volver()
    {
        $this->cn()->resetear();
        $this->controlador()->set_pantalla('seleccion');
    }

    // Va a la pantalla para agregar un nuevo documento al trámite de certificación
    function evt__agregar()
    {
        $this->set_pantalla('pant_datos');
    }

    // Cancela la edición
    function evt__cancelar()
    {
        $this->cn()->resetear_cancelar();
        $this->set_pantalla('pant_inicial');
    }

    // Adjunta un nuevo documento al trámite de certificación
    function evt__adjuntar()
    {
        $this->cn()->guardar();
        $this->cn()->resetear_adjuntar();
        $this->set_pantalla('pant_inicial');
    }
}