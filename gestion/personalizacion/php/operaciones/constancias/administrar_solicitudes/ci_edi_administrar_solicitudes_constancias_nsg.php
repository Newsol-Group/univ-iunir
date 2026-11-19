<?php

/**
 *
 *
 * @package     Constancias y Certificados
 * @subpackage     Administrar Solicitudes
 * 
 */
class ci_edi_administrar_solicitudes_constancias_nsg extends ci_edi_administrar_solicitudes_constancias
{

	function get_preview_documento_url()
	{
		$datos_documento = $this->cn()->get_datos_documento($this->s__solicitud);
		return $datos_documento['url_preview'];
	}

	function get_download_documento_url(){
		$datos_documento = $this->cn()->get_datos_documento($this->s__solicitud);
		return $datos_documento['url_download'];
	}

	function evt__visualizar() {

	}

}