<?php
/**
 * Metodos relacionados al manejo del protocolo Nuxeo
 *
 * @package _lib
 * @subpackage cliente_rdi
 */
class guarani_cliente_rdi_nucleo
{
	/**
	 * Genera una entra nueva para un documento en el repositorio digital, con sus atributos
	 * asociados, a partir de la la clase de servicio dada para el tipo correspondiente.
	 * @param array $archivo datos del archivo a agregar provenientes del ef upload
	 * @param str $servicio constante correspondiente a la clase de servicio que debe
	 * invocarse, segun el tipo de documento a crear en el repositorio digital
	 * @param array $atributos atributos asociados al archivo en el repositorio digital
	 * @return integer id con el que se identifica al documento creado en el repositorio digital
	 */
	public static function crear_documento($archivo, $servicio, $atributos)
	{
		try {
			$cliente = toba::rdi();
			$srv = $cliente->servicio($servicio);
			if (file_exists($archivo['tmp_name'])) {
				ob_start();
				$contenido = file_get_contents($archivo['tmp_name']);
				ob_end_clean();
			}
			$idRecurso = $srv->crear($atributos, $contenido);
			return $idRecurso;
		} catch (Exception $e) {
			toba::logger()->error($e->getMessage());
			toba::logger()->error($e->getTraceAsString());
			$msj = guarani_mensajes::get('800_rdi_error_conexion_archivo', array('subir'));
			throw new guarani_error($msj);
		}
	}

	/**
	 * Elimina el documento asociado al codigo dado del repositorio digital
	 * @param text $cod_archivo id asociado al documento en el repositorio digital
	 * @param str $servicio constante correspondiente a la clase de servicio que debe
	 * invocarse, segun el tipo de documento a crear en el repositorio digital
	 * @return integer 0/1 dependiendo de si se elimino correctamente o surgieron errores
	 */
	public static function eliminar_documento($cod_archivo, $servicio)
	{
		try {
			$cliente = toba::rdi();
			$srv = $cliente->servicio($servicio);
			$srv->eliminar($cod_archivo);
			return 0;
		} catch (Exception $e) {
			toba::logger()->error($e->getMessage());
			toba::logger()->error($e->getTraceAsString());
			$msj = guarani_mensajes::get('800_rdi_error_conexion_archivo', array('eliminar'));
			throw new guarani_error($msj);
		}
	}

	/**
	 * Modifica el contenido del archivo asociado al documento correspondiente al
	 * codigo dado en el repositorio digital
	 * @param text $cod_archivo id asociado al documento en el repositorio digital
	 * @param str $servicio constante correspondiente a la clase de servicio que debe
	 * invocarse, segun el tipo de documento a crear en el repositorio digital
	 * @param array $archivo datos del archivo a agregar provenientes del ef upload
	 */
	public static function modificar_contenido_documento($cod_archivo, $servicio, $archivo)
	{
		try {
			$cliente = toba::rdi();
			$srv = $cliente->servicio($servicio);
			if (file_exists($archivo['tmp_name'])) {
				ob_start();
				$contenido = file_get_contents($archivo['tmp_name']);
				ob_end_clean();
			}
			$srv->modificarContenido($cod_archivo, $contenido);
		} catch (Exception $e) {
			toba::logger()->error($e->getMessage());
			toba::logger()->error($e->getTraceAsString());
			$msj = guarani_mensajes::get('800_rdi_error_conexion_archivo', array('modificar'));
			throw new guarani_error($msj);
		}
	}

	/**
	 * Verifica si existe un documento en el repositorio digital con el id asociado dado
	 * @param text $cod_archivo id asociado al documento en el repositorio digital
	 * @param str $servicio constante correspondiente a la clase de servicio que debe
	 * invocarse, segun el tipo de documento a crear en el repositorio digital
	 * @return boolean true or false dependiendo de si existe un documento en el repositorio
	 * digital para el id dado.
	 */
	public static function existe_documento($cod_archivo, $servicio)
	{
		try {
			$cliente = toba::rdi();
			$srv = $cliente->servicio($servicio);
			$atributos = $srv->recuperarAtributos($cod_archivo);
			return true;
		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Retorna el contenido (archivo) de un documento recuperado desde el repositorio digital,
	 * a partir del id del mismo y la clase de servicio dada para el tipo correspondiente.
	 * @param text $cod_archivo id asociado al documento en el repositorio digital
	 * @param str $servicio constante correspondiente a la clase de servicio que debe
	 * invocarse, segun el tipo de documento a crear en el repositorio digital
	 * @return bytea contenido del documento en el repositorio digital
	 */
	public static function get_contenido_documento($cod_archivo, $servicio)
	{
		try {
			$cliente = toba::rdi();
			$srv = $cliente->servicio($servicio);
			$resultado = array();
			ob_start();
			$contents = $srv->recuperarContenido($cod_archivo);
			ob_end_clean();

			$resultado['contents'] = $contents;
			$resultado['tamanio'] = strlen($contents);

			return $resultado;
		} catch (Exception $e) {
			toba::logger()->error($e->getMessage());
			toba::logger()->error($e->getTraceAsString());
			$msj = guarani_mensajes::get('800_rdi_error_conexion_archivo', array('obtener'));
			throw new guarani_error($msj);
		}
	}

	/**
	 * Retorna los atributos asociados a un documento en el repositorio digital, a partir 
	 * del id del mismo y la clase de servicio dada para el tipo correspondiente.
	 * @param text $cod_archivo id asociado al documento en el repositorio digital
	 * @param str $servicio constante correspondiente a la clase de servicio que debe
	 * invocarse, segun el tipo de documento a crear en el repositorio digital
	 * @return array atributos asociados al documento en el repositorio digital
	 */
	public static function get_datos_documento($cod_archivo, $servicio)
	{
		try {
			$cliente = toba::rdi();
			$srv = $cliente->servicio($servicio);
			$atributos = $srv->recuperarAtributos($cod_archivo);
			return $atributos;
		} catch (Exception $e) {
			toba::logger()->error($e->getMessage());
			toba::logger()->error($e->getTraceAsString());
			$msj = guarani_mensajes::get('800_rdi_error_conexion_archivo', array('obtener los datos de'));
			throw new guarani_error($msj);
		}
	}

	/**
	 * Retorna los atributos asociados a un documento en el repositorio digital, a partir 
	 * del id del mismo y la clase de servicio dada para el tipo correspondiente.
	 * @param text $cod_archivo id asociado al documento en el repositorio digital
	 * @param str $servicio constante correspondiente a la clase de servicio que debe
	 * invocarse, segun el tipo de documento a crear en el repositorio digital
	 * @return array atributos asociados al documento en el repositorio digital
	 */
	public static function get_nombre_documento($cod_archivo, $servicio)
	{
		try {
			$cliente = toba::rdi();
			$srv = $cliente->servicio($servicio);
			$atributos = static::get_datos_documento($cod_archivo, $servicio);
			$nombre = $srv->getNombre($atributos);
			return $nombre;
		} catch (Exception $e) {
			toba::logger()->error($e->getMessage());
			toba::logger()->error($e->getTraceAsString());
			$msj = guarani_mensajes::get('800_rdi_error_conexion_archivo', array('obtener el nombre de'));
			throw new guarani_error($msj);
		}
	}

}
?>