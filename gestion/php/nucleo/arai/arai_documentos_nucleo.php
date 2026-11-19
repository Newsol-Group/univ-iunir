<?php
/**
 * Clase para consumir los Web Services de SIU-Araí Documentos
 * @see https://api.siu.edu.ar/?spec=arai-docs-backend_v1
 * @see https://gitlab.siu.edu.ar/siu-arai/documentos-cli/-/tree/v1.0.1
 * @see https://gitlab.siu.edu.ar/siu-pilaga/pilaga/-/blob/develop/src/SIU/Pilaga/Documentos/PilagaDocumentos.php
 * @package Araí
 */

use SIU\DocumentosCli\DocumentosCli;
use SIU\DocumentosCli\Services\Documentos;
use SIU\DocumentosCli\Services\Tramites;
use SIU\DocumentosCli\Services\Usuarios;
use SIU\DocumentosCli\Services\Notificaciones;
use SIU\DocumentosCli\Entities\Documento;
use SIU\DocumentosCli\Entities\Origen;
use SIU\DocumentosCli\Entities\Autorizacion;
use SIU\DocumentosCli\Entities\Archivo;
use SIU\DocumentosCli\Util\FileUtil;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

class arai_documentos_nucleo
{
    /**
     *  Tipos de documentos de Araí Documentos
     *  @see https://gitlab.siu.edu.ar/siu-arai/docs-api/-/blob/develop/sql/modelo.sql#L78
     */
    const TIPO_DOCUMENTO_GENERICO = 'Generico';
    const TIPO_DOCUMENTO_ACTA = 'ACTA';
    const TIPO_DOCUMENTO_TRAMITE = 'TRA';
    const TIPO_DOCUMENTO_CERTIFICADO = 'CERT';
    const TIPO_DOCUMENTO_DIPLOMA = 'DIPL';
    const TIPO_DOCUMENTO_EXPEDIENTE_DIGITAL = 'EXPD';
    
    const ID_INSTALACION = 'guarani';
    const ID_INSTALACION_ESTAMPA = 'Guaraní';
    
    const CONTENT_DISPOSITION_INLINE = 'inline';
    const CONTENT_DISPOSITION_ATTACHMENT = 'attachment';
    
    const DOCENTE_AUTORIZACION_SIMPLE = 'S';
    const DOCENTE_FIRMA_DIGITAL = 'D';
    
    // Códigos de respuesta
    const STATUS_CODE_GET_OK = 200;
        
	const SISTEMA_ORIGEN = "SIU-Guaraní";
	const TABLA_COLA = "arai_documentos_cola";
	const POLLING_INTERVAL = 1000;
	
	const NOMBRE_ARCHIVO = 'archivo.pdf';
	
	const USUARIO_ORIGEN_DEFAULT = 'admin';
	
	const ESTADO_DOCUMENTO_DIGITAL_PENDIENTE = 'P';
    const ESTADO_DOCUMENTO_DIGITAL_EXITO = 'E';
    const ESTADO_DOCUMENTO_DIGITAL_FALLO = 'F';
    const ESTADO_DOCUMENTO_DIGITAL_AUTORIZADO = 'A';
	
	const DOCUMENTO_FIRMADO_DESC = 'Firmado Digitalmente';
	const DOCUMENTO_PENDIENTE_FIRMA_DESC = 'Pendiente de Firma';
	const DOCUMENTO_PROCESO_ENVIO_DESC = 'En proceso de envío';
	const DOCUMENTO_NO_INFO_DESC = 'No se pudo recuperar información';
	const DOCUMENTO_RECHAZADO_DESC = 'Rechazado';
    
	const MODELO_AUTORIZACION_SIMPLE_DESCR = 'Simple';
	const MODELO_AUTORIZACION_CADENA_DESCR = 'Cadena / Escalonada';
    
	const DOCUMENTO_FIRMADO = 'Firmado';
	const DOCUMENTO_NO_FIRMADO = 'No firmado';
	const DOCUMENTO_ANULADO = 'Anulado';

        const ESTADO_SOLICITUD_AUTORIZACION_PENDIENTE = 1;
        const ESTADO_SOLICITUD_AUTORIZACION_ACEPTADA = 2;
        const ESTADO_SOLICITUD_AUTORIZACION_RECHAZADA = 3;
        const ESTADO_SOLICITUD_AUTORIZACION_ESPERANDO_FIRMA = 4;
        const ESTADO_SOLICITUD_AUTORIZACION_COMPLETADA = 5;
        const ESTADO_SOLICITUD_AUTORIZACION_ANULADA = 6;

        const ESTADO_SOLICITUD_AUTORIZACION_PENDIENTE_DESC = 'Pendiente';
        const ESTADO_SOLICITUD_AUTORIZACION_ACEPTADA_DESC = 'Aceptada';
        const ESTADO_SOLICITUD_AUTORIZACION_RECHAZADA_DESC = 'Rechazada';
        const ESTADO_SOLICITUD_AUTORIZACION_ESPERANDO_FIRMA_DESC = 'Esperando Firma';
        const ESTADO_SOLICITUD_AUTORIZACION_COMPLETADA_DESC = 'Completada';
        const ESTADO_SOLICITUD_AUTORIZACION_ANULADA_DESC = 'Anulada';
    
    /**
     * @see https://gitlab.siu.edu.ar/siu-arai/docs-api/-/blob/develop/src/SIU/DocsNucleo/Entities/CodigoResultado.php Códigos de resultado Araí Documentos.
     * @see https://documentacion.siu.edu.ar/documentos/docs/next/codigos-notificacion/ Códigos de resultado Araí Documentos.
     */
    const EN_EJECUCION = 0;
    const DOCUMENTO_CREADO = 1;
    const DOCUMENTO_AUTORIZADO = 21;
    const DOCUMENTO_AUTORIZADO_ASOCIADO = 22;
    const SOLICITUD_AUTORIZADA = 24;
    const SOLICITUD_RECHAZADA = 25;
    const FALLO_PROCESO_AUTORIZACION = -50;
    
    const VERSION_MINIMA = '1.3.0';
    const VERSION_TIPOS_DOCUMENTOS_NUEVOS = '1.4.0';

    /**
     * Apunta al archivo: php/operaciones/operaciones_publicas/notificaciones_arai_documentos/notificaciones_arai_documentos.php
     */
    const PATH_URL_NOTIFICACIONES = 'aplicacion.php?ah=st64515f7e28c1a1.16170238&ai=guarani%7C%7C89000018&tz=1&tcm=previsualizacion';

    /**
     * @var DocumentosCli
     */
    protected static $documentosCli = null;
    
    /**
     * @var Client $clienteGuzzle El cliente de Guzzle
     */
    protected static $clienteGuzzle = null;
	
	/**
	 * @var string|null $origen Usuario origen creador del documento o NULL
	 */
	protected static $origen = null;
    
    /**
     * @var array Tipos de documentos de Araí
     */
    protected static $tiposDocumentos = [];

    /**
     * @return array
     */
    protected static function getConfDocumentos()
    {
        $config = [];
        $configFile = static::getPathConfig();
        if (!file_exists($configFile)) {
            $error = "El archivo '{$configFile}' no existe.";
            throw guarani::error($error);
        }
        $config = parse_ini_file($configFile);

		$config['sistema_origen'] = static::SISTEMA_ORIGEN;
		$config['polling_interval'] = static::POLLING_INTERVAL;

		$params_db = static::getParamsDB();
		$config = array_merge($params_db, $config);

		if (!isset($config['logs_dir'])) {
            $config['logs_dir'] = static::getDirLogs();
        }
        
		if (!isset($config['queue_path'])) {
            $config['queue_path'] = static::getDirTemp();
        }

        if (!isset($config['queue_temp_dir'])) {
            $config['queue_temp_dir'] = static::getDirTemp();
        }
        
        return $config;
    }
    
    protected static function getPathConfig()
    {
        $instalacionDir = toba::instalacion()->get_path_carpeta_instalacion();
        return $instalacionDir."/arai_documentos.ini";
    }
    
    protected static function getDirLogs()
    {
        $instalacionDir = toba::instalacion()->get_path_carpeta_instalacion();
        return $instalacionDir."/i__desarrollo/p__guarani/logs";
    }
    
    protected static function getDirTemp()
    {
        return toba::proyecto()->get_path_temp();
    }

    protected static function getParamsDB()
    {
        $params = toba::db()->get_parametros();
		$params_db = [];
		$params_db['db_queue'] = $params['base'];
		$params_db['dbq_host'] = $params['profile'];
		$params_db['dbq_port'] = $params['puerto'];
		$params_db['dbq_user'] = $params['usuario'];
		$params_db['dbq_password'] = $params['clave'];
		$params_db['dbq_table_name'] = $params['schema'].".".static::TABLA_COLA;
		return $params_db;
    }
    
    /**
     * @return Client
     */
    protected static function getClienteGuzzle()
    {
        if (is_null(static::$clienteGuzzle)) {
            $cont = static::getInstancia()->getContainter();
            $base_uri = "{$cont['config']['host_arai']}/rest/backend/";
            $type = (isset($cont['config']['type_arai']))? $cont['config']['type_arai'] : 'basic';
            $verify = (isset($cont['config']['verify']))? $cont['config']['verify'] : "1";
            
            // Si $verify es un valor que se puede convertir a boolean
            if (in_array(strtolower($verify), array_merge(comunes::valores_falsos, comunes::valores_verdaderos))) {
                $verify = filter_var($verify, FILTER_VALIDATE_BOOLEAN);
            }
            
            $config = [
                'base_uri' => $base_uri,
                'auth' => [$cont['config']['usr_arai'], $cont['config']['pass_arai'], $type],
                'verify' => $verify
            ];
            static::$clienteGuzzle = new Client($config);
        }
        
        return static::$clienteGuzzle;
    }
    
    /**
     * Devuelve la versión de Araí Documentos.
     *
     * @throws \Exception Si la versión no esta configurada en "instalacion/arai_documentos.ini"
     * 
     * @return string Versión de Araí Documentos.
     */
    protected static function getVersion()
    {
        $cont = static::getInstancia()->getContainter();
        // Si esta seteada la versión en "instalacion/arai_documentos.ini"
        if (isset($cont['config']['version'])) {
            return $cont['config']['version'];
        } else {// sino lanzo excepción
            throw new \Exception("Debe configurar la versión de SIU-Araí Documentos en el archivo 'instalacion/arai_documentos.ini'.");
        }
    }

    /**
     * Devuelve la URL pública para las notificaciones de SIU-Araí Documentos.
     *
     * @throws \Exception Si la URL no esta configurada en "instalacion/arai_documentos.ini"
     * 
     * @return string URL pública para las notificaciones de SIU-Araí Documentos.
     */
    public static function getUrlPublicaNotificaciones()
    {
        $cont = static::getInstancia()->getContainter();
        // Si esta seteado el 'host_notificaciones' en "instalacion/arai_documentos.ini"
        if (isset($cont['config']['host_notificaciones'])) {
            $host_notificaciones = $cont['config']['host_notificaciones'];
        } else {// sino lanzo excepción
            throw new \Exception("Debe configurar 'host_notificaciones' en el archivo 'instalacion/arai_documentos.ini'.");
        }
        
        $path_url_notificaciones = static::PATH_URL_NOTIFICACIONES;
        return "$host_notificaciones/$path_url_notificaciones";
    }

    /**
     * Valida si la respuesta tiene el Status Code esperado.
     *
     * @param Response  $response       Objeto de tipo Response.
     * @param int	$statusEsperado	Status Code esperado.
     * @param string	$funcion	Función que llama.
     * @throws \Exception
     */
    protected static function validarResponse(Response $response, $statusEsperado, $funcion)
    {
        if ($response->getStatusCode() != $statusEsperado) {
            toba::logger()->error("Error en '{$funcion}', se esperaba {$statusEsperado} y se obtuvo {$response->getStatusCode()}.");
            toba::logger()->var_dump(rest_decode(guarani_rest::get_response_json($response)));
            throw guarani::error(guarani_mensajes::get('800_error_conexion_arai_documentos'));
        }
    }
    
    /**
     * Servicio REST a SIU-Araí Documentos "GET autorizacion/solicitudes":
     * Obtiene lista de autorizaciones pendientes.
     * @param string $idUsuarioSSO Usuario de SIU-Araí.
     * @return array Lista de autorizaciones pendientes.
     */
    public static function getAutorizacionesPendientes($idUsuarioSSO)
    {
        $options = [];
        $options['http_errors'] = false;
        $options['query']['id_usuario_sso'] = $idUsuarioSSO;
        $response = static::getClienteGuzzle()->get('autorizacion/solicitudes', $options);
        static::validarResponse($response, static::STATUS_CODE_GET_OK, __FUNCTION__);
        $respuesta = rest_decode(guarani_rest::get_response_json($response));
        toba::logger()->debug("GET autorizacion/solicitudes:");
        toba::logger()->debug(print_r($respuesta, true));
        return $respuesta;
    }
    
    /**
     * @return bool TRUE si se puede conectar, FALSE caso contrario.
     */
    public static function isConexionOk()
    {
        $ok = false;
        
        try {
            
            $version = static::getVersion();
            $versionMinima = static::VERSION_MINIMA;

            // https://www.php.net/manual/en/function.version-compare.php
            if (version_compare($version, $versionMinima, '<')) {
                throw new \Exception("Se está utilizando la versión '{$version}' de SIU-Araí Documentos, debe utilizar una versión mayor o igual a '{$versionMinima}'.");
            }

            $options = [];
            $response = static::getClienteGuzzle()->get('status', $options);
            if ($response->getStatusCode() == static::STATUS_CODE_GET_OK) {
                $ok = true;
            }
        } catch (\Exception $e) {
            toba::logger()->error('Error al verificar la conexión con SIU-Araí Documentos:');
            toba::logger()->error($e->getMessage());
        }
        
        return $ok;
        
    }
    
    /**
     * Verifica si la conexión con SIU-Araí Documentos funciona correctamente, 
     * de no serlo lanza una excepción.
     * @throws \Exception
     */
    public static function verificarConexion()
    {
        $arai_documentos_class = (\comunes::es_interfaz_autogestion())? \SIU\Chulupi\kernel::localizador()->instanciar('modelo_g3\arai_documentos') : 'arai_documentos';
        $ok = $arai_documentos_class::isConexionOk();
        if (!$ok) {
            throw guarani::error(guarani_mensajes::get('800_error_conexion_arai_documentos'));
        }
    }
    
    /**
     * Servicio REST a SIU-Araí Documentos "GET documentos/{uidDocumento}/movimientos":
     * Obtiene el listado de movimientos registrados sobre un documento.
     * @param string $uidDocumento UID del documento.
     * @return array Movimientos registrados sobre un documento.
     */
    public static function getMovimientosDocumento($uidDocumento)
    {
        $options = [];
        $options['http_errors'] = false;
        $response = static::getClienteGuzzle()->get("documentos/{$uidDocumento}/movimientos", $options);
        static::validarResponse($response, static::STATUS_CODE_GET_OK, __FUNCTION__);
        $respuesta = rest_decode(guarani_rest::get_response_json($response));
        toba::logger()->debug("GET /documentos/{uidDocumento}/movimientos:");
        toba::logger()->debug(print_r($respuesta, true));
        return $respuesta;
    }
    
    /**
     * Si el documento esta anulado o no:
     * @param array $movimientosDocumento Movimientos del documento.
     * @return bool Si el documento esta anulado o no.
     */
    public static function esDocumentoAnulado($movimientosDocumento)
    {
        $documentoAnulado = false;
        $resultadoUltimoEvento = static::getResultadoUltimoEvento($movimientosDocumento);

        if ($resultadoUltimoEvento == static::FALLO_PROCESO_AUTORIZACION) {
                $documentoAnulado = true;
        }
        
        return $documentoAnulado;
    }

    /**
     * Si el documento esta Autorizado y Asociado a SUDOCU o no:
     * @param array $movimientosDocumento Movimientos del documento.
     * @return bool Si el documento esta Autorizado y Asociado SUDOCU o no.
     */
    public static function esDocumentoAutorizadoAsociado($movimientosDocumento)
    {
        $documentoAutorizadoAsociado = false;
        $resultadoUltimoEvento = static::getResultadoUltimoEvento($movimientosDocumento);

        if ($resultadoUltimoEvento == static::DOCUMENTO_AUTORIZADO_ASOCIADO) {
            $documentoAutorizadoAsociado = true;
        }

        return $documentoAutorizadoAsociado;
    }

    /**
     * Resultado del último evento según movimientos del documento.
     * @param array $movimientosDocumento Movimientos del documento.
     * @return int  Resultado del último evento.
     */
    public static function getResultadoUltimoEvento($movimientosDocumento)
    {
        $resultadoUltimoEvento = static::EN_EJECUCION;

        if (isset($movimientosDocumento['eventos'])) {
            // Tomo el ultimo evento
            $ultimoEvento = end($movimientosDocumento['eventos']);
            $resultadoUltimoEvento = $ultimoEvento['id_codigo_resultado'];
        }

        return $resultadoUltimoEvento;
    }

    /**
     * Si el documento esta autorizado o no:
     * @param array $movimientosDocumento Movimientos del documento.
     * @return bool Si el documento esta autorizado o no.
     */
    public static function esDocumentoAutorizadoByMovimientos($movimientosDocumento)
    {
        return ($movimientosDocumento['autorizacion']['autorizado'] ?? false);
    }

    /**
     * Modelo de autorización del documento:
     * @param array $movimientosDocumento Movimientos del documento.
     * @return string Modelo de autorización del documento.
     */
    public static function getModeloAutorizacionByMovimientos($movimientosDocumento)
    {
        return $movimientosDocumento['autorizacion']['modelo'];
    }

    /**
     * Autorizaciones del documento:
     * @param array $movimientosDocumento Movimientos del documento.
     * @return array Autorizaciones del documento.
     */
    public static function getAutorizacionesByMovimientos($movimientosDocumento)
    {
        return ($movimientosDocumento['autorizacion']['autorizaciones'] ?? []);
    }

    /**
     * Servicio REST a SIU-Araí Documentos "GET tipos-documentos":
     * Obtiene listado de tipos de documentos.
     * @return array Listado de tipos de documentos.
     */
    public static function getTiposDocumentos()
    {
        // Si la variable esta vacía busco en la API
        if (empty(static::$tiposDocumentos)) {
            $options = [];
            $options['http_errors'] = false;
            $response = static::getClienteGuzzle()->get("tipos-documentos", $options);
            static::validarResponse($response, static::STATUS_CODE_GET_OK, __FUNCTION__);
            $respuesta = rest_decode(guarani_rest::get_response_json($response));
            toba::logger()->debug("GET /tipos-documentos:");
            toba::logger()->debug(print_r($respuesta, true));
            static::$tiposDocumentos = $respuesta['tipos'];
        }

        toba::logger()->debug("=== Tipos de documentos de Araí ===");
        toba::logger()->debug(print_r(static::$tiposDocumentos, true));
        return static::$tiposDocumentos;
    }

    /**
     * Obtiene el tipo de documento (para versiones menores a 1.4.0 devuelve los tipos legacy).
     * @param string $tipoDocumento Tipo de documento de Araí.
     * @return string|null Tipo de documento (para versiones menores a 1.4.0 devuelve los tipos legacy), null en caso de error.
     */
    public static function getTipoDocumento($tipoDocumento)
    {
        try {

            $version = static::getVersion();
            $versionTiposDocumentosNuevos = static::VERSION_TIPOS_DOCUMENTOS_NUEVOS;

            // Si la versión es mayor o igual a la 1.4.0 devuelvo $tipoDocumento
            // https://www.php.net/manual/en/function.version-compare.php
            if (version_compare($version, $versionTiposDocumentosNuevos, '>=')) {
                return $tipoDocumento;
            }

            // Si la versión es menor a la 1.4.0 devuelvo $tipoDocumentoLegacy
            $tipoDocumentoLegacy = $tipoDocumento;
            switch ($tipoDocumento) {
                case static::TIPO_DOCUMENTO_DIPLOMA:
                    $tipoDocumentoLegacy = static::TIPO_DOCUMENTO_TRAMITE;
                    break;
                case static::TIPO_DOCUMENTO_CERTIFICADO:
                    $tipoDocumentoLegacy = static::TIPO_DOCUMENTO_EXPEDIENTE_DIGITAL;
                    break;
            }

            return $tipoDocumentoLegacy;
        } catch (\Exception $e) {
            toba::logger()->error($e->getMessage());
            return null;
        }
    }

    /**
     * @return DocumentosCli
     * @throws \Exception
     */
    public static function getInstancia()
    {
        if (is_null(static::$documentosCli)) {
            $config = static::getConfDocumentos();
            static::$documentosCli = new DocumentosCli();
            static::$documentosCli->setConfig($config);
        }

        return static::$documentosCli;
    }

    
    /**
     * @return Documentos
     */
    protected static function getDocumentosService()
    {    
        return static::getInstancia()->getDocumentosService();
    }
    
    /**
     * @return Tramites
     */
    protected static function getTramitesService()
    {    
        return static::getInstancia()->getTramitesService();
    }
    
    /**
     * @return Usuarios
     */
    protected static function getUsuariosService()
    {    
        return static::getInstancia()->getUsuariosService();
    }
    
    /**
     * @return Notificaciones
     */
    protected static function getNotificacionesService()
    {    
        return static::getInstancia()->getNotificacionesService();
    }
    
    /**
     * Arma un objeto de tipo Documento desde un array
     * @param array $documentoArr
     * Consta del siguiente formato:
     * <code>
     *  [
     *      "tipo" => "string",
     *      "titulo" => "string",
     *      "descripcion" => "string",
     *      "id_tramite" => "string",
     *      "url" => "string",
     *      "identificador" => "string",
     *      "referencia_interna" => "string",
     *      "atributos" => "string",
     *      "archivo" => [
     *          "md5" => "string",
     *          "contenido_base64" => "string",
     *          "filepath" => "string"
     *      ],
     *      "autorizacion" => [
     *          "modelo" => "simple",
     *          "autorizantes" => []
     *      ],
     *      "origen" => [
     *          "id_usuario_sso" => "string",
     *          "id_instalacion" => "string",
     *          "cuenta_usuario" => "string"
     *      ]
     *  ]
     * </code>
     * @return Documento
     */
    public static function armarDocumentoFromArray(array $documentoArr)
    {
        try {
            $documento = new Documento();
            $documento->fromArray($documentoArr);
            return $documento;
        } catch (\Exception $e) {
            toba::logger()->error($e);
            throw guarani::error($e->getMessage());
        }
    }
    
    protected static function getBase64File($filePath)
    {
        return base64_encode(FileUtil::getContent($filePath, false));
    }
    
    /**
     * Escribe Stream en un archivo
     * @param string $fileName Ruta del archivo donde se escribe la información 
     * @param Stream $data La información a escribir 
     * @return void
     * @throws \Exception
     */
    public static function saveFile($fileName, $data)
    {
        if (!file_put_contents($fileName, $data)) {
            $mensaje = "Falló la escritura del archivo '{$fileName}'.";
            throw guarani::error($mensaje);
        }
    }
    
    /**
     * Descarga un archivo
     * @param string $fileName Ruta del archivo
     * @param string $tipoSalida inline o attachment (por defecto attachment)
     * @param string $nombreDescarga el nombre con el que se va a descargar el archivo (por defecto el nombre en $fileName)
     * @param bool $eliminar Si se debe eliminar el archivo luego de ser descargado (por defecto true - Será eliminado)
     * @return void
     * @throws \Exception
     */
    public static function downloadFile($fileName, $tipoSalida = arai_documentos::CONTENT_DISPOSITION_ATTACHMENT, $nombreDescarga = '', $eliminar = true)
    {
        if (!file_exists($fileName)) {
            $mensaje = "No se encuentra el archivo '{$fileName}'.";
            throw guarani::error($mensaje);
        }

        if (empty($nombreDescarga)) {
            $nombreDescarga = basename($fileName);
        }

        header('Content-Description: File Transfer');
        header('Content-Type: '.mime_content_type($fileName));
        header('Content-Disposition: '.$tipoSalida.'; filename="'.$nombreDescarga.'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: '.filesize($fileName));
        ob_end_clean();
        readfile($fileName);
        if ($eliminar) {
            unlink($fileName);
        }
        exit;
    }
    
    /**
     * Arma un objeto de tipo Documento
     * @param array $data Datos para arma un objeto de tipo Documento.
     * Consta del siguiente formato:
     * <code>
        $data['archivo'] = '/usr/local/proyecto/www/temp/specs_Secuencias.pdf';
        $data['autorizacion']['modelo'] = Autorizacion::MODELO_MULTIPLE_CADENA;
        $data['autorizantes'][0]['usuario'] = 'dariel';
        $data['autorizantes'][0]['nivel_autorizacion'] = Autorizacion::NIVEL_BASICA;
        $data['autorizantes'][0]['secuencia'] = 1;
        $data['autorizantes'][1]['usuario'] = 'admin';
        $data['autorizantes'][1]['nivel_autorizacion'] = Autorizacion::NIVEL_FIRMA;
        $data['autorizantes'][1]['secuencia'] = 2;
        $data['origen']['id_usuario_sso'] = 'dfrancisco';
        $data['origen']['cuenta_usuario'] = 'dfrancisco';
        $data['documento']['tipo'] = arai_documentos::TIPO_DOCUMENTO_GENERICO;
        $data['documento']['titulo'] = 'Acta de examen 2020';
        $data['documento']['descripcion'] = 'Esta es un acta de examen para mesas del 2020';
        $data['documento']['id_tramite'] = 'AA3-R3';
        $data['documento']['referencia_interna'] = ["id_guarani" => "1", "convocatoria" => "LPU 1/2019"];
        $data['documento']['identificador'] = '';
        $data['documento']['atributos'] = ["tipo" => "PLIEGO", "numero" => "3822", "anio" => "2020"];
     * </code>
     * @param bool $async Si la creación del documento en SIU-Araí es asincrónica o no.
     * @return Documento
     * @throws \Exception
     */
    public static function armarDocumento($data, $async = true)
    {
        try {
            
            toba::logger()->debug('Documento SIU-Araí:');
            toba::logger()->var_dump($data);
            
            $filebase64 = "";
            if ($async) {
                    $filebase64 = file_get_contents($data['archivo']);
            } else {
                    $filebase64 = static::getBase64File($data['archivo']);
            }

            $archivo = new Archivo();
            $archivo->setContenidoBase64($filebase64);

            $autorizacion = new Autorizacion();
            $autorizacion->setModelo($data['autorizacion']['modelo']);
            
            foreach ($data['autorizantes'] as $autorizante) {
                $autorizacion->addPersona($autorizante['usuario'], $autorizante['nivel_autorizacion'], $autorizante['secuencia']);
            }
            
            $origen = new Origen();
            $origen->setIDUsuarioSSO($data['origen']['id_usuario_sso']);
            $origen->setIDInstalacion(static::ID_INSTALACION);
            $origen->setCuentaUsuario($data['origen']['cuenta_usuario']);

            $documento = new Documento();
            $documento->setTipo($data['documento']['tipo']);
            $documento->setTitulo(utf8_encode($data['documento']['titulo']));
            $documento->setDescripcion(utf8_encode($data['documento']['descripcion']));
            $documento->setIDTramite($data['documento']['id_tramite']);

            $url = static::getUrlPublicaNotificaciones();
            $documento->setURL($url);

            $documento->setArchivo($archivo);
            $documento->setAutorizacion($autorizacion);
            $documento->setOrigen($origen);
            
            $documento->setReferenciaInterna($data['documento']['referencia_interna']);
            $documento->setIdentificador($data['documento']['identificador']);
            $documento->setAtributos($data['documento']['atributos']);
            
            return $documento;
            
        } catch (\Exception $e) {
            toba::logger()->error($e);
            throw guarani::error($e->getMessage());
        }
    }
    
    /**
     * Envía un documento a Araí-Documentos
     * @param Documento $documento
     * @return array
     * <code>
     * [
     * "uri" => "string", URI del recurso recientemente creado.
     * "uid_documento" => "string" uuid del documento.
     * ]
     * </code>
     * @throws \Exception
     */
    public static function postDocumento(Documento $documento)
    {
        try {
            return static::getDocumentosService()->postDocumento($documento);
        } catch (\Exception $e) {
            toba::logger()->error($e);
            throw guarani::error($e->getMessage());
        }
        
    }
    
    /**
     * Encola un documento en Araí-Documentos - No llama al callback setFileGeneratorCallback
     * @param Documento $documento
     * @throws \Exception
     */
    public static function postDocumentoAsync(Documento $documento)
    {
        try {
            static::getDocumentosService()->postDocumentoAsync($documento);
        } catch (\Exception $e) {
            toba::logger()->error($e);
            throw guarani::error($e->getMessage());
        }
        
    }

    /**
     * Encola un documento en Araí-Documentos - Llama al callback setFileGeneratorCallback
     * @param Documento $documento
     * @throws \Exception
     */
    public static function generateAndPostAsync(Documento $documento)
    {
        try {
            static::getDocumentosService()->generateAndPostAsync($documento);
        } catch (\Exception $e) {
            toba::logger()->error($e);
            throw guarani::error($e->getMessage());
        }
        
    }

    /**
     * Obtiene el documento guardado en Araí con el id del documento
     * @param string $uidDocumento
     * @return Documento
     * @throws \Exception
     */
    public static function getDocumento($uidDocumento)
    {
        try {
            return static::getDocumentosService()->getDocumento($uidDocumento);
        } catch (\Exception $e) {
            toba::logger()->error($e);
            throw guarani::error($e->getMessage());
        }
    }

    /**
     * Obtiene una notificacion
     * @param string $uidNotificacion
     * @return Array Estado del documento
     * @throws \Exception
     */
    public static function getNotificacion($uidNotificacion)
    {
        try {
            $notificacion = static::getNotificacionesService()->getNotificacion($uidNotificacion);
            toba::logger()->debug("API Araí Documentos - GET /notificaciones/{$uidNotificacion}");
            toba::logger()->debug(print_r($notificacion, true));
            return $notificacion;
        } catch (\Exception $e) {
            toba::logger()->error($e);
            throw guarani::error($e->getMessage());
        }
    }

    /**
     * Obtiene el binario del documento guardado en Araí con el uid del documento
     * @param string $uidDocumento
     * @return Stream
     * @throws \Exception
     */
    public static function downloadDocumento($uidDocumento)
    {
        try {
            return static::getDocumentosService()->downloadDocumento($uidDocumento);
        } catch (\Exception $e) {
            toba::logger()->error($e);
            throw guarani::error($e->getMessage());
        }
    }

    /**
     * Vincula un documento a un tramite
     * @param string $uidDocumento
     * @param array $data
     * @return array Resultado Array,
     * @throws \Exception
     */
    public static function vincularTramite($uidDocumento, $data)
    {
        try {
            return static::getDocumentosService()->vincularTramite($uidDocumento, $data);
        } catch (\Exception $e) {
            toba::logger()->error($e);
            throw guarani::error($e->getMessage());
        }
    }

    /**
     * Obtiene un listado de tramites dado un usuario y area
     * @param string $idUsuarioSso
     * @param string $idArea Área SUDOCU (Opcional).
     * @return array Array tramites
     * @throws \Exception
     */
    public static function getTramites($idUsuarioSso, $idArea = null)
    {
        try {
            return static::getTramitesService()->getTramites($idUsuarioSso, $idArea);
        } catch (\Exception $e) {
            toba::logger()->error($e);
            throw guarani::error($e->getMessage());
        }
    }

    /**
     * Obtiene datos de un tramite
     * @param string $idTramite
     * @return array Array con datos del tramite (id_tramite, descripcion, area)
     * @throws \Exception
     */
    public static function getTramite($idTramite)
    {
        try {
            return static::getTramitesService()->getTramite($idTramite);
        } catch (\Exception $e) {
            toba::logger()->error($e);
            throw guarani::error($e->getMessage());
        }
    }

    /**
     * Obtiene un listado de usuarios
     * @param string $filtro
     * @return array Array usuarios
     * @throws \Exception
     */
    public static function getUsuarios($filtro)
    {
        try {
            $usuarios = static::getUsuariosService()->getUsuarios($filtro);
            toba::logger()->debug("API Araí Documentos - GET /usuarios?filtro=$filtro");
            toba::logger()->debug(print_r($usuarios, true));
            return $usuarios;
        } catch (\Exception $e) {
            toba::logger()->error($e);
            throw guarani::error($e->getMessage());
        }
    }
	
	/**
	 * Servicio REST a Araí Documentos, GET /usuarios:
	 * Usuarios de Araí Documentos que no están asociados a Guaraní.
	 * @see https://documentacion.siu.edu.ar/apis/?spec=arai-docs-backend_v1#tag/usuarios API de Araí Documentos.
	 * @return array Usuarios de Araí no asociados.
	 */
	public static function getUsuariosNoAsociados($filtro)
	{
		$usuarios = static::getUsuarios($filtro);
		// Traigo los usuarios de Araí ya asociados
		$usuarios_arai_asociados = toba::consulta_php('co_personas')->get_usuarios_arai_asociados();

		$uids_arai_asociados = [];
		if (!empty($usuarios_arai_asociados)) {
			$uids_arai_asociados = array_column($usuarios_arai_asociados, 'arai_identificador_sso');
		}

                toba::logger()->debug("API Araí Documentos - usuarios asociados");
                toba::logger()->debug(print_r($uids_arai_asociados, true));

		foreach ($usuarios as $key => $usuario) {
			// Si el usuario de Araí ya esta asociado lo quito del array 
			if (in_array($usuario['id_usuario_sso'], $uids_arai_asociados)) {
				unset($usuarios[$key]);
			}
		}

		return $usuarios;
	}
    
    /**
     * Descarga documento desde SIU-Araí Documentos.
     * @see https://redmine.siu.edu.ar/redmine/issues/30923 Ticket #30923
     * @param string $uidDocumento
     * @return void
     * @throws \Exception
     */
    public static function descargarDocumento($uidDocumento)
    {
        try {
            $fileName = static::descargarDocumentoToFilesystem($uidDocumento);
            static::downloadFile($fileName, static::CONTENT_DISPOSITION_ATTACHMENT, static::NOMBRE_ARCHIVO, true);
        } catch (\Exception $e) {
            toba::logger()->error($e);
            $mensaje = guarani_mensajes::get('800_fallo_descarga_documento_arai');
            throw guarani::error($mensaje);
        }
    }
	
	/**
     * Descarga el documento desde SIU-Araí Documentos al Filesystem, y devuelve el path.
	 * @see https://redmine.siu.edu.ar/redmine/issues/30925 Ticket #30925
     * @see https://redmine.siu.edu.ar/redmine/issues/30958 Ticket #30958
     * @see https://redmine.siu.edu.ar/redmine/issues/30957 Ticket #30957
     * @see https://redmine.siu.edu.ar/redmine/issues/32726 Ticket #32726
     * @param string $uidDocumento
     * @return string Path del archivo descargado en el Filesystem.
     * @throws \Exception
     */
	public static function descargarDocumentoToFilesystem($uidDocumento)
	{
		 try {
            $documento = static::downloadDocumento($uidDocumento);
            $tempDir = static::getDirTemp();
            $fileName = "{$tempDir}/{$uidDocumento}.pdf";
            static::saveFile($fileName, $documento);
            return $fileName;
        } catch (\Exception $e) {
            toba::logger()->error($e);
            $mensaje = guarani_mensajes::get('800_fallo_descarga_documento_arai');
            throw guarani::error($mensaje);
        }
	}
	
	/**
	* Obtiene el usuario origen de Araí
	* @param array $params Parámetros para determinar el usuario originante en personalización, se envían por defecto id, documento_tipo, ras y metodo. Consta del siguiente formato:
        * <pre>
        *   [
        *       'documento_tipo' => 'ACTA',
        *       'id'             => 123,
        *       'ras'            => [1, 2, 3, 4],
        *       'metodo'         => 'acta_nucleo::enviar_acta_arai_documentos'
        *   ]
        * </pre>
		* $usuario_proceso En caso de que la operación sea un proceso (background), el usuario que la ejecutó
	* @throws \Exception Si el origen no tiene usuario vinculado en Araí.
	* @return string Usuario origen de Araí
	*/
	public static function get_cuenta_usuario_origen_arai_documentos($params=[], $usuario_proceso=null)
	{
		if (!isset(static::$origen)) {
			
			// Primero tomo de la sesión del usuario logeado
			$usuario = toba::usuario()->get_id();
			
			if (is_null($usuario) || ($usuario == toba_usuario_no_autenticado::NO_AUTENTICADO)) {
				$usuario = $usuario_proceso;
			}
			if (is_null($usuario)) {
				static::$origen = null;
			} else {
				$datos_persona = toba::consulta_php('co_personas')->get_persona_x_usuario($usuario);
				// Si la columna mdp_personas.arai_identificador_sso es NULL o un string vacío con solo espacios
				static::$origen = (is_null($datos_persona['arai_identificador_sso']) || trim($datos_persona['arai_identificador_sso']) == '')? null : $datos_persona['arai_identificador_sso'];
			} 
			if (is_null(static::$origen)) {
				throw guarani::error(guarani_mensajes::get('800_origen_no_vinculado_arai'));
			}
			
		}
		
		return static::$origen;
	}
	
	/**
	 * Valida que el origen tenga usuario vinculado en Araí.
	 * @throws \Exception Si el origen no tiene usuario vinculado en Araí.
	 */
	public static function validar_cuenta_usuario_origen_arai_documentos($usuario=null)
	{
		$arai_documentos_class = (\comunes::es_interfaz_autogestion()) ? \SIU\Chulupi\kernel::localizador()->instanciar('modelo_g3\arai_documentos') : 'arai_documentos';
                $params['metodo'] = __METHOD__;
		$arai_documentos_class::get_cuenta_usuario_origen_arai_documentos($params, $usuario);
	}

	/**
	* Obtiene las autoridades firmantes.
	* @see https://redmine.siu.edu.ar/redmine/issues/30921 Ticket #30921
	* @param string $autoridad_firma_acta_digital   Parámetro 'autoridad_firma_acta_digital'.
	* @param int[]  $ras                            Array con los ids de las responsables académicas.
	* @throws \Exception Si existen autoridades sin usuario vinculado en Araí.
	* @return array Autoridades firmantes.
	*/
	public static function get_autoridades_firmantes($autoridad_firma_acta_digital, $ras)
	{
		if ($autoridad_firma_acta_digital == comunes::no) {
			return [];
		}

                $autoridades_firmantes = [];
                $autoridades = toba::consulta_php('co_autoridades_cargos')->get_autoridades_firmantes_arai_documentos($ras);

                foreach ($autoridades as $autoridad) {
                        // Si la columna mdp_personas.arai_identificador_sso es NULL o un string vacío con solo espacios
                        if (is_null($autoridad['arai_identificador_sso']) || trim($autoridad['arai_identificador_sso']) == '') {
                                throw guarani::error(guarani_mensajes::get('800_autoridad_no_vinculada_arai'));
                        } else {
                                $autoridades_firmantes[] = $autoridad['arai_identificador_sso'];
                        }
                }

		return $autoridades_firmantes;
	}
    
   /**
     * Obtiene los modelos de autorización.
     * @see https://redmine.siu.edu.ar/redmine/issues/42189 Ticket #42189
     * @see https://documentacion.siu.edu.ar/documentos/docs/proc-aut-app/#post-backenddocumentos
     * @see https://documentacion.siu.edu.ar/documentos/docs/keyconcepts/#modelos-de-autorizaci%C3%B3n
     * @return array Modelos de autorización.
     */
    public static function get_modelos_autorizacion()
    {
        return [
            ['valor' => Autorizacion::MODELO_SIMPLE, 'descr' => static::MODELO_AUTORIZACION_SIMPLE_DESCR],
            ['valor' => Autorizacion::MODELO_MULTIPLE_CADENA, 'descr' => static::MODELO_AUTORIZACION_CADENA_DESCR],
        ];
    }

    /**
     * Obtiene las opciones para el filtro 'Estado de la Firma'.
     * @see https://redmine.siu.edu.ar/redmine/issues/42016 Ticket #42016
     * @see https://redmine.siu.edu.ar/redmine/issues/33994 Ticket #33994
     * @return array Opciones para el filtro 'Estado de la Firma'.
     */
    public static function get_opciones_estado_firma()
    {
        return [
            ['valor' => arai_documentos::EN_EJECUCION,                  'descr' => arai_documentos::DOCUMENTO_PROCESO_ENVIO_DESC],
            ['valor' => arai_documentos::SOLICITUD_AUTORIZADA,          'descr' => arai_documentos::DOCUMENTO_PENDIENTE_FIRMA_DESC],
            ['valor' => arai_documentos::DOCUMENTO_AUTORIZADO,          'descr' => arai_documentos::DOCUMENTO_FIRMADO_DESC],
            ['valor' => arai_documentos::FALLO_PROCESO_AUTORIZACION,    'descr' => arai_documentos::DOCUMENTO_RECHAZADO_DESC],
        ];
    }
}
