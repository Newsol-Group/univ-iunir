<?php

/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Administrar Solicitudes
 * 
 */
class cn_man_administracion_solicitud_constancias_nsg extends cn_man_administracion_solicitud_constancias
{	
		
	protected $s__datos_documento = null;

	function get_listado($filtro)
	{
		// Se recuperan s�lo aquellas solicitudes en estado de Pendiente o Impreso (no se administran las ya Rechazadas o Entregadas).
		$where_base = "(sga_constancias_solicitud.estado = '" . constancias::estado_solicitud_pendiente . "' OR sga_constancias_solicitud.estado = '" . constancias::estado_solicitud_impreso . "')";
		$where = $where_base;

		if ($filtro) {
			$where .= ' AND ' . $filtro;
		}

		return toba::consulta_php('co_constancias')->get_listado_const_solicitadas2($where, '', $where_base);
	}

	function get_procesar_pendientes_masivo($filtro)
	{
		// Se recuperan s�lo aquellas solicitudes en estado de Pendiente.
		$where = 'where ';
		if ($filtro) {
			$where .= $filtro;
		}

		return toba::consulta_php('co_constancias')->get_procesar_pendientes_masivo($where);
	}


	/**
	 * Registra la Eliminaci�n de la Solicitud con el id de la misma.
	 */
	function eliminar_solicitud_constancia($id_solicitud)
	{
		$this->desactivar_transacciones_anidadas();
		try {
			toba::db()->abrir_transaccion();
			$this->limpiar__datos_documento($id_solicitud);
			// Eliminaci�n de los par�metros actuales de la solicitud, si es que existen.
			guarani::act('act_constancias')->eliminar_parametros_solicitud($id_solicitud);
			// Se elimina el registro de la solicitud.
			guarani::act('act_constancias')->eliminar_solicitud($id_solicitud);
			toba::db()->cerrar_transaccion();
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			$this->activar_transacciones_anidadas();
			throw $e;
		}
	}

	
	/**
	 * 23/07/2025
	 * Newsol - Joaquin
	 * registrar_impresiones_multiples
	 * Registra la Impresión simultánea de varias Solicitudes de Constancias. 
	 */
	function registrar_impresiones_multiples($id_solicitudes, $id_persona, $observaciones = '')
	{
		try {
			guarani::act('act_constancias')->registrar_impresiones_multiples($id_solicitudes, $id_persona, $observaciones);
		} catch (toba_error $e) {
			throw $e;
		}
	}

	/**
	 * 23/12/2025
	 * Newsol - Joaquin
	 * get_datos_documento
	 * Recupera los datos del documento asociado a la Solicitud de Constancia.
	 */
	function get_datos_documento($solicitud)
	{

		toba::logger()->debug("Obteniendo datos del documento para solicitud $solicitud");
		
		if (isset($this->s__datos_documento[$solicitud])) {
			$doc = $this->s__datos_documento[$solicitud];
			if (isset($doc['path']) && file_exists($doc['path'])) {
				return $doc;
			} else {
				unset($this->s__datos_documento[$solicitud]);
			}
		}

		$id_documento = toba::consulta_php('co_constancias')->get_documento_constancia_x_solicitud($solicitud);

		if (is_null($id_documento)) {
			return null;
		}

		$contenido_doc = guarani_cliente_rdi::get_contenido_documento($id_documento, RDITipos::RECURSOPERSONAL);
		$bin   = $contenido_doc['contents'] ?? '';
		$finfo = new finfo(FILEINFO_MIME_TYPE);
		$mime  = $finfo->buffer($bin) ?: 'application/octet-stream';

		switch ($mime) {
			case 'image/png':
				$ext = '.png';
				break;
			case 'image/jpeg':
				$ext = '.jpg';
				break;
			case 'image/gif':
				$ext = '.gif';
				break;
			default:
				$ext = ''; // otras cosas (PDF, etc.)
				break;
		}

		$token  = md5(uniqid('', true)) . $ext;
		$nombre = toba::proyecto()->get_www_temp($token);

		file_put_contents($nombre['path'], $bin);
		@chmod($nombre['path'], 0644);

		$es_img = strpos($mime, 'image/') === 0;

		if (!$es_img) {
			$generico = toba::proyecto()->get_www("img/doc_cargado.png");
			$url = $generico['url'];
		} else {
			$url = $nombre['url']; 
		}

		$this->s__datos_documento[$solicitud] = [
			'id_documento' => $id_documento,
			'url_preview'  => $url,          
			'url_download' => $nombre['url'], 
			'path' => $nombre['path'], 
			'mime' => $mime
		];

		toba::logger()->debug("Documento cargado para solicitud $solicitud: " . print_r($this->s__datos_documento[$solicitud], true));

		return $this->s__datos_documento[$solicitud];
	}

	/**
	 * 23/12/2025
	 * Newsol - Joaquin
	 * limpiar_s_datos_documento
	 * Elimina los archivos temporales creados para la visualizaci�n y descarga de los documentos asociados a las Solicitudes de Constancias.
	 */

	function limpiar_s_datos_documento()
	{
		if (is_null($this->s__datos_documento)) return;

		foreach ($this->s__datos_documento as $key => $doc) {
			if (isset($doc['path']) && file_exists($doc['path'])) {
				unlink($doc['path']);
			}
			unset($this->s__datos_documento[$key]);
		}

		$this->s__datos_documento = [];
	}

	function limpiar__datos_documento($solicitud) {
		if (is_null($this->s__datos_documento)) return;

		if (isset($this->s__datos_documento[$solicitud])) {
			$doc = $this->s__datos_documento[$solicitud];
			if (isset($doc['path']) && file_exists($doc['path'])) {
				unlink($doc['path']);
			}
			unset($this->s__datos_documento[$solicitud]);
		}
	}
}
