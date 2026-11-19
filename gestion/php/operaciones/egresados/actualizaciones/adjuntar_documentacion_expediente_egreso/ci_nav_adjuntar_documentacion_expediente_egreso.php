<?php
/**
 * 
 *
 * @package 	Egresados
 * @subpackage 	Actualizaciones
 * 
 */
class ci_nav_adjuntar_documentacion_expediente_egreso extends ci_nav_sin_listado_inicial
{

	// Componente Visual de Información.
	protected $ev_info;
	
	function ini__operacion()
	{
		// Si la conexión a Araí Documentos falla no le permito ingresar a la operación
		if (!arai_documentos::isConexionOk()) {
			throw guarani::error(guarani_mensajes::get('800_error_conexion_arai_documentos'));
		}
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function evt__cuadro__seleccion($seleccion)
	{
		$this->cn()->setNroSolicitud($seleccion['nro_solicitud']);
		$this->set_pantalla('edicion');
	}
	
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__seleccion(toba_ei_pantalla $pantalla)
	{
		$pantalla->set_descripcion(guarani_mensajes::get('800EGR_tramites_expediente_electronico'), 'warning');
	}

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		$nro_solicitud = $this->cn()->getNroSolicitud();
		$filtro = "sga_certificados_otorg.nro_solicitud = {$nro_solicitud}";
		$datos_tramite = current(toba::consulta_php('co_tramites_certificacion')->get_listado($filtro));
        
        $descr_expediente = $datos_tramite['nro_expediente'];
        
        // Si el Expediente es un UUID válido lo busco en SUDOCU
        if (sudocu::idTramiteValido($datos_tramite['nro_expediente'])) {
            // Busco la descripción del trámite en SUDOCU, sino puede obtenerla la toma de la DB (sga_certificados_otorg.nro_expediente_descr)
            // La descripción del trámite en SUDOCU puede cambiar al editarse
            $descrTramiteSUDOCU = sudocu::getDescripcionTramiteComboEditable($datos_tramite['nro_expediente']);
            $descr_expediente = $descrTramiteSUDOCU ?? $datos_tramite['nro_expediente_descr'];
        }
        
        $datos_tramite['descr_expediente'] = $descr_expediente;

        $this->cn()->setDatosTramite($datos_tramite);
		$this->ev_info()->set_datos($datos_tramite);
	}

	function conf__resumen()
	{
		$this->ev_info()->set_datos($this->cn()->getDatosTramite());
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(['apellido_y_nombres', 'documento_principal', 'certificado_nombre', 'descr_expediente']);
			$this->ev_info->set_max_columnas_por_fila(2);

			// Fila 1
			$this->ev_info->set_columnas_fila(['apellido_y_nombres', 'documento_principal'], 1);
			// Fila 2
			$this->ev_info->set_columnas_fila(['certificado_nombre', 'descr_expediente'], 2);

			$this->ev_info->set_etiqueta('apellido_y_nombres', 'Alumno:');
			$this->ev_info->set_etiqueta('documento_principal', 'Documento:');
			$this->ev_info->set_etiqueta('certificado_nombre', 'Certificación:');
			$this->ev_info->set_etiqueta('descr_expediente', 'Expediente:');
		}
		return $this->ev_info;
	}
	
}