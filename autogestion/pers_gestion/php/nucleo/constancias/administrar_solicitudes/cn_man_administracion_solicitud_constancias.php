<?php

/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Administrar Solicitudes
 * 
 */
class cn_man_administracion_solicitud_constancias extends cn_manejador_entidad
{
	protected $s__datos_documento = null;
	// Se redefine la funci�n que obtiene las etiquetas en el cn manejador para que no se invoque al cn entidad.
	// Se recupera directamente la etiqueta de la tabla ya que no hay ninguna dependencia asociada.
	function etiquetas($dep = null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('sga_constancias_solicitud');
		} else {
			parent::etiquetas($dep);
		}
	}

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
	 * Registra el rechazo de la Solicitud con el id de Usuario que lo procesa y los datos indicando el Motivo del mismo.
	 */
	function rechazar_solicitud_constancia($id_solicitud, $id_persona, $observaciones = '')
	{
		try {
			guarani::act('act_constancias')->rechazar_solicitud($id_solicitud, $id_persona, $observaciones);
		} catch (toba_error $e) {
			throw $e;
		}
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
	 * Registra la entrega de la Solicitud con el id de Usuario que lo procesa. Fecha editada por el usuario para la
	 * Entrega y los dem�s datos de la misma.
	 */
	function registrar_entrega_solicitud_constancia($id_solicitud, $id_persona, $fecha_entrega, $datos_solicitud)
	{
		try {
			$this->validar_datos_entrega_solicitud($fecha_entrega, $datos_solicitud);
			guarani::act('act_constancias')->registrar_entrega_solicitud($id_solicitud, $id_persona, $fecha_entrega);
		} catch (toba_error $e) {
			throw $e;
		}
	}


	/**
	 * Setea los datos correspondientes al proceso de impresi�n de la constancia realizado por la persona dada.
	 * Adicionalmente si el usuario confirm� que desea registrar la entrega de la constancia junto con la impresi�n, se
	 * configurar� al usuario como la persona que realiza la entrega y a la fecha de entrega como la fecha y hora actual.
	 * No se realizar�n validaciones adicionales sobre la misma, ya que en este caso no tiene sentido (como al registrar
	 * la entrega de una constancia impresa con anterioridad).
	 */
	function registrar_impresion_solicitud_constancia($id_solicitud, $id_persona, $registrar_entrega = false, $observaciones = '')
	{
		try {
			if ($registrar_entrega) {
				guarani::act('act_constancias')->registrar_impresion_entrega_solicitud($id_solicitud, $id_persona, $observaciones);
			} else {
				guarani::act('act_constancias')->registrar_impresion_solicitud($id_solicitud, $id_persona, $observaciones);
			}
		} catch (toba_error $e) {
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

	//-------- Validaciones ---------------------------------------------

	function validar_datos_entrega_solicitud($fecha_entrega, $datos_solicitud)
	{
		// Se valida la fecha de entrega seteada por el usuario. Se sabe que la constancia ya fue Impresa.
		if (isset($datos_solicitud['fecha_impresion'])) {
			$fecha_impresion = $datos_solicitud['fecha_impresion'];
			$fecha_hora_actual = guarani_fecha::get_ahora();
			// Se verifica que la fecha y hora de entrega de la constancia sea mayor o igual a la de impresi�n de la misma 
			// y menor o igual a la fecha y hora actual.
			if (! guarani_fecha::esta_entre_fechas_horas($fecha_entrega, $fecha_impresion, $fecha_hora_actual, true)) {
				$msj_error = guarani_mensajes::get('800_control_fecha_entre', array('y hora de Entrega', 'Fecha y hora de Impresi�n', 'Fecha y hora Actual'));
				throw new guarani_error($msj_error);
			}
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
