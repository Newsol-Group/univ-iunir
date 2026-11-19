<?php
/**
 * Catalogo de tipos de requisitos
 *
 * @package 	Requisitos
 * @subpackage 	-
 * 
 */
class requisito_digital_nucleo
{
	// Tipos de Documentos Digitales para Requisitos Documentales
	const TIPO_RECURSO = 'Recurso';
	const TIPO_RECURSO_PERSONAL_FOTO = 'RecursoPersonalFoto';
	const TIPO_RECURSO_PERSONAL_DNI = 'RecursoPersonalDni';
	const TIPO_RECURSO_PERSONAL_TITULO_SECUNDARIO = 'RecursoPersonalTituloSecundario';
	const TIPO_RECURSO_PERSONAL_DOCUMENTO_ACADEMICO = 'RecursoPersonalDocumentoAcademico';
	
	// Indices de los atributos para Recursos Personales
	// Obligatorios
	const ATRIB_TIPO_IDENTIFICACION = 'tipoIdentificacion';
	const ATRIB_NRO_IDENTIFICACION = 'numeroIdentificacion';
	// Opcionales
	const ATRIB_APELLIDO = 'apellido';
	const ATRIB_NOMBRE = 'nombres';
	const ATRIB_CUIT = 'identificacionFiscal';
	
	// Indices de los atributos para todos Recursos de Documentos Académicos
	//Opcionales
	const ATRIB_FECHA_PRESENTACION = 'fechaPresentacion';
	
	// Indices de los atributos Opcionales para Recurso DNI
	//Opcionales
	const ATRIB_NRO_DOCENTE = 'numeroDocente';
	
	// Indices de los atributos para Recurso Titulo Secundario
	//Opcionales
	const ATRIB_CODIGO_COLEGIO = 'cue';
	
	// Indices de los atributos para Recurso Foto (opcionales)
	const ATRIB_ANCHO = 'ancho';
	const ATRIB_ALTO = 'alto';
	const ATRIB_UNIDAD = 'unidad';
	const ATRIB_COLOR = 'color';
	const ATRIB_RESOLUCION = 'resolucion';
	
	const NO_TIENE_REQUISITOS_PARA_DESCARGAR = 'No hay documentos asociados a esta preinscripción.';

	/**
	 * @todo: reemplazar por datos obtenidos de tabla
	 * 
	 * Retorna los tipos de archivos permitidos para documentos digitales de requisitos.
	 */
	static function get_extensiones_archivo_permitidas()
	{
		return array('gif', 'png', 'jpg', 'jpeg', 'pdf', 'doc', 'docx');
	}

	/**
	 * Retorna los atributos que deben asociarse al crear un documento en repositorio digital,
	 * segun el tipo de documento al que este asociado el requisito documental, a partir de los
	 * datos del requisito.
	 * 
	 * @param $tipo tipo de documento digital asociado al requisito documental
	 * @param $datos datos del requisito documental para el que se crea el documento digital
	 */
	static function get_atributos_documento_digital_requisito_documental($datos_requisito, $datos_persona, $datos_archivo)
	{
		$atributos = array();
		// Atributos de los recursos personales
		$atributos[static::ATRIB_TIPO_IDENTIFICACION] = $datos_persona['tipo_doc'];
		$atributos[static::ATRIB_NRO_IDENTIFICACION] = $datos_persona['nro_documento'];
		if (isset($datos_persona['apellido'])) {
			$atributos[static::ATRIB_APELLIDO] = $datos_persona['apellido'];
		}
		if (isset($datos_persona['nombres'])) {
			$atributos[static::ATRIB_NOMBRE] = $datos_persona['nombres'];
		}
		if (isset($datos_requisito['fecha_presentacion'])) {
			// Atributo fecha presentación común a todos los recursos de requisitos documentales
			// La fecha en nuxeo tiene el formato  aaa-mm-ddThh:mm:ss.000Z
			$fecha_hora_nuxeo = $datos_requisito['fecha_presentacion'] . "T00:00:00.000Z";
			$atributos[static::ATRIB_FECHA_PRESENTACION] = $fecha_hora_nuxeo;
		}

		switch ($datos_requisito['tipo_documento_digital']) {
                        // Si es de tipo Foto de la persona (tipo 'RecursoPersonalFoto' en NUXEO)
			case static::TIPO_RECURSO_PERSONAL_FOTO: //El control se hace para el llamado anterior sin importar la actividad.
				if (isset($datos_archivo['ancho'])) {
					$atributos[static::atrib_ancho] = $datos_archivo['ancho'];
				}
				if (isset($datos_archivo['alto'])) {
					$atributos[static::atrib_alto] = $datos_archivo['alto'];
				}
                                break;
                        // Si es de tipo Título Secundario (tipo 'RecursoPersonalTituloSecundario' en NUXEO)
			case static::TIPO_RECURSO_PERSONAL_TITULO_SECUNDARIO:
				if (isset($datos_persona['cod_colegio_secundario'])) {
					$atributos[static::atrib_codigo_colegio] = $datos_requisito['cue'];
				}
				break;
                        // Si es de tipo Genérico (tipo 'Recurso' en NUXEO)
                        case static::TIPO_RECURSO:
                                // Elimino dichos atributos ya que no los necesita, sino da el siguiente error:
                                // Parametros invalidos: tipoIdentificacion,numeroIdentificacion,apellido,nombres
                                unset($atributos[static::ATRIB_TIPO_IDENTIFICACION]);
                                unset($atributos[static::ATRIB_NRO_IDENTIFICACION]);
                                unset($atributos[static::ATRIB_APELLIDO]);
                                unset($atributos[static::ATRIB_NOMBRE]);
				break;
		}
		return $atributos;
	}

}