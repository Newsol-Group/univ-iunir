<?php

namespace siu\modelo\_comun;

use SIU\Chulupi\kernel;
use siu\errores\error_guarani;
use siu\extension_kernel\chulupi_logger_rdi;
use siu\modelo\datos\catalogo;
use SIU\Chulupi\util\ManejadorArchivos;
use SIU\Chulupi\util\u;
use siu\modelo\transacciones\solicitar_certificacion;

class clienteRDI
{
	protected $clienteRdi;

	function __construct()
	{
		if (!kernel::proyecto()->usa_rdi()) {
			throw new error_guarani('El sistema no está configurado para usar RDI.');
		}
		\RDIAutoload::registrar();
	}

	function get_cliente()
	{
		if (!isset($this->clienteRdi)) {
			$this->clienteRdi = $this->instanciar_cliente();
		}
		return $this->clienteRdi;
	}

	//--------------------------------------------------------------------------
	//---- Métodos auxiliares
	//--------------------------------------------------------------------------

	protected function instanciar_cliente()
	{
		$parametros = kernel::proyecto()->get_parametros_rdi();
		$rdi = new \RDICliente(
			$parametros['conector'], $parametros['repositorio'], $parametros['usuario'], $parametros['clave'], $parametros['proyecto'], $_SERVER['ACC_ID'] // Instalación
		);
		$log = new chulupi_logger_rdi();
		$log->set_activo(true);
		$rdi->asociarLog($log);
		return $rdi;
	}

	public static function get_atributos_documento_digital_requisito_documental($tipo_documento_digital, $path)
	{
		$atributos = [];

		$datos_persona = catalogo::consultar('persona', 'datos_basicos', ['persona' => kernel::persona()->get_id()]);
		
		// Atributos de los recursos personales
		$atributos['tipoIdentificacion'] = $datos_persona['tipo_documento'];
		$atributos['numeroIdentificacion'] = $datos_persona['nro_documento'];
		if (isset($datos_persona['apellido'])) {
			$atributos['apellido'] = $datos_persona['apellido'];
		}
		if (isset($datos_persona['nombres'])) {
			$atributos['nombres'] = $datos_persona['nombres'];
		}
		switch ($tipo_documento_digital) {
			// Si es de tipo Foto de la persona (tipo 'RecursoPersonalFoto' en NUXEO)
			case \RDITipos::FOTO:
				$datos_archivo = getimagesize($path);
				if (isset($datos_archivo[0])) {
					$atributos['ancho'] = $datos_archivo[0];
				}
				if (isset($datos_archivo[0])) {
					$atributos['alto'] = $datos_archivo[1];
				}
				break;
			// Si es de tipo Genérico (tipo 'Recurso' en NUXEO)
			case \RDITipos::RECURSO:
				// Elimino dichos atributos ya que no los necesita, sino da el siguiente error:
				// Parametros invalidos: tipoIdentificacion,numeroIdentificacion,apellido,nombres
				unset($atributos['tipoIdentificacion']);
				unset($atributos['numeroIdentificacion']);
				unset($atributos['apellido']);
				unset($atributos['nombres']);
				break;
		}
		return $atributos;
	}

	public static function enviar_repositorio($path, $tipo_documento_digital, $id_recurso = null)
	{
		$srv = kernel::proyecto()->rdi()->servicio($tipo_documento_digital);
		// Obtengo el contenido del archivo
		$cont = file_get_contents($path);
		// Atributos del archivo
		$attr = static::get_atributos_documento_digital_requisito_documental($tipo_documento_digital, $path);
		// Si no existe documento cargado
		if (is_null($id_recurso)) {
			// Se crea un nuevo recurso en el RDI y se retorna el ID
			$id_recurso = $srv->crear($attr, $cont, mime_content_type($path));
			// Si existe documento cargado (botón "Modificar")
		} else {
			// Se actualiza el contenido
			$srv->modificarContenido($id_recurso, $cont, true, mime_content_type($path));
			// Se actualizan los atributos
			$srv->modificarAtributos($id_recurso, $attr);
		}

		return $id_recurso;
	}

	public static function eliminar_repositorio($tipo_documento_digital, $id_recurso)
	{
		// Elimino documento del RDI
		$srv = kernel::proyecto()->rdi()->servicio($tipo_documento_digital);
		$srv->eliminar($id_recurso);
	}

	//--------------------------------------------------------------------------
	//---- Validación
	//--------------------------------------------------------------------------
	
	/*
	 * @see https://www.php.net/manual/en/features.file-upload.errors.php
	 */
	public static function validar_error_documento_subido($indice)
	{
		$hubo_error = false;
		$msg_error = '';
		switch ($_FILES[$indice]['error']) {
			case \UPLOAD_ERR_OK:
				break;
			case \UPLOAD_ERR_INI_SIZE:

				// Devuelve 0 cuando no tiene limite
				$upload_max_size = ini_get('upload_max_filesize');
				$upload_max_size_bytes = u::return_bytes($upload_max_size);
				$upload_max_size_formateado = u::formatBytes($upload_max_size_bytes);
				$hubo_error = true;
				$msg_error = kernel::traductor()->trans('upload_err_ini_size', ['%tam_max_archivo%' => $upload_max_size_formateado]);
				break;
			case \UPLOAD_ERR_FORM_SIZE:
				$hubo_error = true;
				$msg_error = kernel::traductor()->trans('upload_err_form_size');
				break;
			case \UPLOAD_ERR_PARTIAL:
				$hubo_error = true;
				$msg_error = kernel::traductor()->trans('upload_err_partial');
				break;
			case \UPLOAD_ERR_NO_FILE:
				$hubo_error = true;
				$msg_error = kernel::traductor()->trans('upload_err_no_file');
				break;
			case \UPLOAD_ERR_NO_TMP_DIR:
				$hubo_error = true;
				$msg_error = kernel::traductor()->trans('upload_err_no_tmp_dir');
				break;
			case \UPLOAD_ERR_CANT_WRITE:
				$hubo_error = true;
				$msg_error = kernel::traductor()->trans('upload_err_cant_write');
				break;
			case \UPLOAD_ERR_EXTENSION:
				$hubo_error = true;
				$msg_error = kernel::traductor()->trans('upload_err_extension');
				break;
			default:
				$hubo_error = true;
				$msg_error = kernel::traductor()->trans('upload_err_generico');
				break;
		}
		return [$hubo_error, $msg_error];
	}

	public static function validar_extension_documento_subido($indice)
	{
		$hubo_error = false;
		$msg_error = '';
		$extensiones_validas = \toba::consulta_php('co_configuracion', true)->get_rdi_extensiones_permitidas();

		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mime_type = finfo_file($finfo, $_FILES[$indice]['tmp_name']);
		finfo_close($finfo);
		$extension = ManejadorArchivos::mime2ext($mime_type);

		// Si no pertenece a las extensiones validas, o no tiene extensión
		if (($extension === false) || !in_array($extension, $extensiones_validas)) {
			$hubo_error = true;
			$msg_error = kernel::traductor()->trans('upload_err_extension_no_permitida', ['%tipos%' => implode(', ', $extensiones_validas)]);
		}

		return [$hubo_error, $msg_error];
	}

	public static function validar_documento_subido($indice)
	{
		$hubo_error = false;
		$msg_error = '';
		list($hubo_error, $msg_error) = static::validar_error_documento_subido($indice);
		// Si no hubo error
		if (!$hubo_error) {
			list($hubo_error, $msg_error) = static::validar_extension_documento_subido($indice);
		}

		return [$hubo_error, $msg_error];
	}

	public static function guardar_archivos_subidos($indice)
	{
		$path = kernel::proyecto()->get_www_temp() . '/' . basename($_FILES[$indice]['name']);
		if (move_uploaded_file($_FILES[$indice]['tmp_name'], $path)) {
			return $path;
		} else {
			return false;
		}
	}

	//--------------------------------------------------------------------------
	//---- Queries
	//--------------------------------------------------------------------------

	public static function case_estado_req_digital($alias_tabla_presentados = 'sga_requisitos_presentados', $alias_tabla_draft = 'sga_requisitos_digitales_draft')
	{
		return "
			CASE
				WHEN {$alias_tabla_draft}.requisito_digital IS NOT NULL THEN '".solicitar_certificacion::estado_pendiente_validacion."'
				WHEN {$alias_tabla_presentados}.requisito_presentado IS NULL THEN '".solicitar_certificacion::estado_no_presentado."'
				WHEN ({$alias_tabla_presentados}.fecha_vencimiento IS NULL OR {$alias_tabla_presentados}.fecha_vencimiento >= CURRENT_DATE) THEN '".solicitar_certificacion::estado_presentado."'
				ELSE '".solicitar_certificacion::estado_vencido."'
			END
		";
	}

	public static function case_observaciones()
	{
		$sin_observaciones = kernel::traductor()->trans('requisitos.sin_observaciones');
		return "
			CASE
				WHEN NULLIF(TRIM(sga_requisitos_digitales_draft_detalle.observaciones), '') IS NULL THEN '{$sin_observaciones}'
				ELSE sga_requisitos_digitales_draft_detalle.observaciones
			END
		";
	}
}