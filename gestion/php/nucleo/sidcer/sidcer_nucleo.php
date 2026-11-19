<?php

use GuzzleHttp\Client;
use SIU\DocumentosCli\Entities\Autorizacion;

/**
 * Clase para consumir los WS de SidCer
 * @package SidCer
 */
class sidcer_nucleo
{
	
	/*
	 * Códigos de respuesta HTTP
	 */
	const STATUS_CODE_OK		= 200;
	const STATUS_CODE_CREATED	= 201;
	const STATUS_CODE_NOT_FOUND	= 404;
	const STATUS_CODE_ERROR		= 500;

	/*
     * Tipos de documentos de sidcer.
     */
    const documentoDiploma = 'Diploma';
    const documentoAnalitico = 'Analitico';
	
	/*
     * Tipos de documentos de sidcer abreviados.
     */
    const documentoDiplomaAbrev = 'D';
    const documentoAnaliticoAbrev = 'A'; 
    
	/*
     * Tipos de documentos de sidcer código.
     */
    const documentoDiplomaCod = 1;
    const documentoAnaliticoCod = 2; 
	
    /*
     * Estados en los cuales ya se encuentran las etiquetas disponibles. 
     */
    const solicitudEnCarga = 1;
    const solicitudEnviada = 2;
    const solicitudEnProcesoEvaluacion = 3;
    const solicitudAprobada = 4;
    const solicitudRechazada = 5;
    const solicitudFinalizada = 6;
    const solicitudAnulada = 9;

    /*
     * Estados. 
     */
    const ESTADO_NO_GENERADO = 1;
    const ESTADO_NO_GENERADO_DESC = 'No Generado';
	const ESTADO_PENDIENTE_FIRMA = 2;
    const ESTADO_PENDIENTE_FIRMA_DESC = 'Pendiente de Firma';
	const ESTADO_FIRMADO = 3;
    const ESTADO_FIRMADO_DESC = 'Firmado';
	const ESTADO_ENVIADO_SIDCER = 4;
    const ESTADO_ENVIADO_SIDCER_DESC = 'Enviado a SICer';
	
    const ESTADO_DOCUMENTO_SIN_ENVIAR = 'SIN_ENVIAR';
    const ESTADO_DOCUMENTO_CARGADO = 'CARGADO';
    const ESTADO_DOCUMENTO_ENVIADO = 'ENVIADO';
    const ESTADO_DOCUMENTO_APROBADO = 'APROBADO';
    const ESTADO_DOCUMENTO_OBSERVADO = 'OBSERVADO';
    
	/**
	 * @var Client $cliente_sidcer El cliente de Guzzle
	 */
	protected static $cliente_sidcer;

	/**
	 * @return \GuzzleHttp\Client
	 */
	protected static function get_cliente_rest()
	{
		if (!isset(static::$cliente_sidcer)) {
			$cliente = toba::servicio_web_rest('sidcer');
			static::$cliente_sidcer = $cliente->guzzle();
		}
		return static::$cliente_sidcer;
	}
	
	/**
	 * Servicio REST a SICer "GET /estadosolicitud/get":
	 * Obtiene listado de solicitudes.
	 * @param array $query Filtro.
	 * Consta del siguiente formato:
	 * <code>
	 *	[
	 *		"tipo_documento" => "DNI",
	 *		"numero_documento" => "35789456"
	 *	]
	 * o
	 *	[
	 *		"solicitud" => "30923"
	 *	]
	 * </code>
	 * @return array Solicitudes
	 * Consta del siguiente formato:
	 * <code>
	 *  Array 
	 *  (
	 *      [error] => true/false
	 *		[mensaje] => "Mensaje de error"
	 *      [solicitudes] => Array
	 *      (
	 *          Array
	 *          (
	 *              [numero_solicitud]	=> "30923"
	 *              [tipo_documento]	=> "DNI"
	 *              [numero_documento]	=> "93916447"
	 *              [id_institucion]	=> "92"
	 *              [institucion]		=> "Universidad Adventista del Plata"
	 *              [id_titulo]			=> "959"
	 *              [titulo]			=> "Médico"
	 *              [estado_solicitud]	=> "Trámite finalizado"
	 *          ),
	 *          ...
	 *      )
	 *  )
	 * </code>
	 */
	public static function getEstadoSolicitud($query)
	{

		$resultado = [
			'error' => false,
			'mensaje' => '',
			'solicitudes' => []
		];

		try {
			
			$response = static::get_cliente_rest()->get('estadosolicitud/get', [
				'query' => $query
			]);

			$responsePhp = rest_decode(guarani_rest::get_response_json($response));
			list($responseOk, $messageError) = static::validarResponse($responsePhp);

			// Si salió todo OK
			if ($responseOk) {
				$resultado['error'] = false;
				$resultado['solicitudes'] = $responsePhp;
			} else {
				$resultado['error'] = true;
				$resultado['mensaje'] = $messageError;
			}
			
		} catch (\Exception $e) {// Si dió un error
			toba::logger()->error("Error SICer: ".$e->getMessage());
			$resultado['error'] = true;
			$resultado['mensaje'] = $e->getMessage();
		}

		return $resultado;
	}
	
	/*
     *  Obtiene el último estado de la solicitud de Sicer.
	 *  @todo SICer va a agregar un campo con el "estado actual".
	 *	@param array $historial Historial de cambios de estado.
	 *	@return int Último estado
     */
    public static function getUltimoEstadoSolicitudSicer($historial)
    {
        $ultimoEstado = end($historial);
        return $ultimoEstado['id_estado_tramite'];
    }
	

	/**
	 * Servicio REST a SICer "GET /etiqueta/get":
	 * Obtiene las URLs de los PDFs.
	 * @param int $solicitud Solicitud.
	 * @return array Etiqueta
	 * Consta del siguiente formato:
	 * <code>
	 * 	Array 
	 *  (
	 *      [error] => true/false           
	 *      [mensaje] => "Mensaje de error"
	 *      [etiqueta] => Array
	 *      (
	 *			[solicitud]	=> "30923"
	 *			[diploma]	=> "http://sidcer.siu.edu.ar/sicerws/3.0/archivos/tramites/30923/etiquetas/D_93916447_92_460_959.pdf"
	 *			[analitico]	=> "http://sidcer.siu.edu.ar/sicerws/3.0/archivos/tramites/30923/etiquetas/A_93916447_92_460_959.pdf"
	 *      )
	 *  )
	 * </code>
	 */
    public static function getEtiquetasSolicitud($solicitud)
    {

		$resultado = [
			'error' => false,
			'mensaje' => '',
			'etiqueta' => [],
		];

		try {
		
			$response = static::get_cliente_rest()->get('etiqueta/get', [
				'query' => ['solicitud' => $solicitud]
			]);

			$responsePhp = rest_decode(guarani_rest::get_response_json($response));
			list($responseOk, $messageError) = static::validarResponse($responsePhp);

			// Si salió todo OK
			if ($responseOk) {
				$resultado['error'] = false;
				$resultado['etiqueta'] = $responsePhp;
			} else {
				$resultado['error'] = true;
				$resultado['mensaje'] = $messageError;
			}
			
		} catch (\Exception $e) {// Si dió un error			
            toba::logger()->error("Error SICer: ".$e->getMessage());
			$resultado['error'] = true;
			$resultado['mensaje'] = $e->getMessage();
		}

		return $resultado;
    }
    
	/**
	 * Servicio REST a SICer "POST /documentacion":
	 * Envío de documentación firmada.
	 * @param int $id_tramite Solicitud.
	 * @param int $id_tipo_documentacion 1 = diploma, 2 = analítico.
	 * @param string $nombre_archivo Nombre del archivo, ej: diploma.pdf.
	 * @param string $archivo Path o URL del archivo.
	 * @return array
	 * Consta del siguiente formato:
	 * <code>
	 * 	Array 
	 *  (
	 *      [error] => false/true
	 *      [status_code] => 201/400
	 *      [descripcion] => "La documentación se agrego correctamente."/"El usuario no se encuentra habilitado para realizar el ingreso de imagenes."
	 *  )
	 * </code>
	 */
    public static function postDocumentacion($id_tramite, $id_tipo_documentacion, $nombre_archivo, $archivo)
    {
		
		try {
			
			$resultado = [
				'error' => false,
				'status_code' => static::STATUS_CODE_CREATED,
				'descripcion' => ""
			];

			$body['id_tramite'] = $id_tramite;
			$body['id_tipo_documentacion'] = $id_tipo_documentacion;
			$body['nombre_archivo'] = $nombre_archivo;

			if (!file_exists($archivo)) {
				throw new \Exception("El archivo '{$archivo}' no existe.");
			}
			
			$archivo = base64_encode(file_get_contents($archivo));
			if ($archivo === false) {
				throw new \Exception("Problemas al trasformar el archivo a texto.");
			}
			$body['archivo'] = $archivo;
		
			$response = static::get_cliente_rest()->post('documentacion', [
				'body' => rest_encode($body),
				'http_errors' => false
			]);
			
			$responsePhp = rest_decode(guarani_rest::get_response_json($response));
			
			toba::logger()->debug("Response SICer POST /documentacion:");
			toba::logger()->debug(print_r($responsePhp, true));
        
			$resultado['status_code'] = $response->getStatusCode();
			// Si el status code es distinto a 201 es porque hubo un error
			if ($resultado['status_code'] != static::STATUS_CODE_CREATED) {
				toba::logger()->error("Error SICer: {$responsePhp['descripcion']}");
				$resultado['error'] = true;
				$resultado['descripcion'] = $responsePhp['descripcion'];
            }
			
		} catch (\Exception $e) {// Si dió un error			
            toba::logger()->error("Error SICer: ".$e->getMessage());
			$resultado['error'] = true;
			$resultado['status_code'] = static::STATUS_CODE_ERROR;
			$resultado['descripcion'] = $e->getMessage();
		}

		return $resultado;
    }
	
    /**
     * @return bool TRUE si se puede conectar, FALSE caso contrario.
     */
    public static function isConexionOk()
    {
        $ok = false;
        
        try {

			$response = static::get_cliente_rest()->get('maestros/tipos_de_documento');

            if ($response->getStatusCode() == static::STATUS_CODE_OK) {
                $ok = true;
            }

        } catch (\Exception $e) {// Si dió un error
            toba::logger()->error("Error SICer: ".$e->getMessage());
        }
        
        return $ok;
        
    }

	public static function validarResponse($response)
	{
		toba::logger()->debug("Response SICer:");
		toba::logger()->debug(print_r($response, true));

		$responseOk = true;
		$messageError = "";
		
		// Si dió un error
		if (isset($response['error'])) {
			
			$responseOk = false;
			$messageError = $response['error'];
			toba::logger()->error("Error SICer: {$messageError}");
			
		}

		return [$responseOk, $messageError];
	}
	
       /**
        * Envía un diploma/analítico a Araí-Documentos.
        * @see https://redmine.siu.edu.ar/redmine/issues/32726 Ticket #32726
        * @param string $documento_titulo Título del documento.
        * @param string $documento_descripcion Descripción del documento.
        * @param string $modelo_autorizacion Modelo de autorización [simple|cadena].
        * @param string[] $autoridades_firmantes Array de autoridades firmantes.
        * @param array $parametros Parámetros necesarios, consta del siguiente formato:
        * <code>
           $parametros['solicitud_guarani'] = '15';
           $parametros['solicitud_sicer'] = '30923';
           
           // id_tramite de SUDOCU, si no se pasa no se asocia el documento a SUDOCU (EEI)
           $parametros['id_tramite'] = '901e7f46-4e07-42ae-addc-8f75b383aee9';
           $parametros['ras'] = [1, 2, 3];
        * </code>
        * @param string $archivo Path del archivo PDF del diploma/analítico.
        * @param bool $borrar_archivo Si el archivo debe ser borrado una vez que se lo envió a SIU-Araí Documentos o no.
        * @throws \Exception Si falla algo en el proceso
        * @return string|null UID del documento creado en Araí, null en caso de error.
        */
        public static function enviar_diploma_analitico_arai_documentos($documento_titulo, $documento_descripcion, $modelo_autorizacion, $autoridades_firmantes, $parametros, $archivo, $borrar_archivo = true)
        {
                
            try {
                
                $data = [];
                $data['autorizacion']['modelo'] = $modelo_autorizacion;
                
                $data['documento']['tipo'] = arai_documentos::getTipoDocumento(arai_documentos::TIPO_DOCUMENTO_DIPLOMA);

				// Si no existe el archivo
                if (!file_exists($archivo)) {
                    $mensaje = guarani_mensajes::get('800_pdf_inexistente', [$archivo]);
                    throw new \Exception($mensaje);
                } else {
                    // Si existe el archivo
                    $data['archivo'] = $archivo;
                }
                
                $data['documento']['titulo'] = $documento_titulo;
                $data['documento']['descripcion'] = $documento_descripcion;
                // Ver Ticket #42326 (https://redmine.siu.edu.ar/redmine/issues/42326)
                $data['documento']['id_tramite'] = ($parametros['id_tramite'] ?? null);
                /*
                 * @see https://documentacion.siu.edu.ar/documentos/docs/repo-docs/#request 
                 * Este campo se utiliza para almacenar en formato json, los datos que requiera la aplicación que genera el documento.
                 */
                $data['documento']['referencia_interna'] = [
					'solicitud_guarani' => $parametros['solicitud_guarani'],
					'solicitud_sicer' => $parametros['solicitud_sicer'],
					'sistema' => arai_documentos::ID_INSTALACION
				];
                $data['documento']['identificador'] = $parametros['solicitud_guarani'];
                /*
                 * @see https://documentacion.siu.edu.ar/documentos/docs/repo-docs/#request 
                 * Atributos adicionales del documento.
                 */
                $data['documento']['atributos'] = [];
                
                $secuencia = 0;
                foreach ($autoridades_firmantes as $autoridad_firmante) {
                    $data['autorizantes'][] = [
                        'usuario' => $autoridad_firmante,
                        'nivel_autorizacion' => Autorizacion::NIVEL_FIRMA,
                        // Si el modelo de autorización es simple no se pasa la secuencia
                        'secuencia' => ($modelo_autorizacion == Autorizacion::MODELO_SIMPLE)? null : $secuencia
                    ];
                    $secuencia++;
                }

                $arai_documentos_class = (\comunes::es_interfaz_autogestion())? \SIU\Chulupi\kernel::localizador()->instanciar('modelo_g3\arai_documentos') : 'arai_documentos';

                $params['documento_tipo'] = $data['documento']['tipo'];
                $params['id'] = $parametros['solicitud_guarani'];
                $params['ras'] = $parametros['ras'];
                $params['metodo'] = __METHOD__;
		$origen = $arai_documentos_class::get_cuenta_usuario_origen_arai_documentos($params);
                $data['origen']['id_usuario_sso'] = $origen;
                $data['origen']['cuenta_usuario'] = $origen;
                // Se le debe pasar 'id_instalacion' ya que se muestra en la estampa
                $data['origen']['id_instalacion'] = arai_documentos::ID_INSTALACION_ESTAMPA;
                
                try {
                    $documento = $arai_documentos_class::armarDocumento($data, false);
                    $datos_documento = $arai_documentos_class::postDocumento($documento);
                    return $datos_documento['uid_documento'];
                } catch (\Exception $e) {
                    $mensaje = $e->getMessage();
                    throw new \Exception(utf8_decode($mensaje));
                }
                
            } catch (\Exception $e) {
                
                toba::logger()->error($e->getMessage());
                $mensaje = guarani_mensajes::get('800_fallo_creacion_documento_arai');
                throw guarani::error($mensaje);
                
            } finally {
                
                // Borro el archivo
                if ($borrar_archivo && file_exists($archivo)) {
                    unlink($archivo);
                }
				
            }    
	}
	
}