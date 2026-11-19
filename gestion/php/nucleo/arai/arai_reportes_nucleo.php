<?php

/**
 * Clase para consumir los WS de SIU-Araí Reportes
 * @see https://documentacion.siu.edu.ar/reportes/docs/arquitectura/ SIU-Araí: Reportes.
 * @package Araí
 */

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Stream;

class arai_reportes_nucleo 
{
    const USA_ARAI_REPORTES = "UsaAraiReportes";

    // Códigos de respuesta
    const STATUS_CODE_GET_OK = 200;
    const STATUS_CODE_POST_OK = 201;
    const STATUS_CODE_DELETE_OK = 204;

    const PROYECTO = 'guarani';
    
    const CARPETA_NUCLEO = 'nucleo';
    const CARPETA_PERSONALIZACION = 'personalizacion';
    const CARPETA_IMAGENES_PERS = 'imagenes_pers';

    const SUFIJO_PERSONALIZACION = '_pers';
    const PREFIJO_PERSONALIZACION_REPORTES = 'Pers';
    const PREFIJO_PERSONALIZACION_ARCHIVOS = 'pers_';

    const CONTENT_DISPOSITION_INLINE = 'inline';
    const CONTENT_DISPOSITION_ATTACHMENT = 'attachment';

    const FORMATO_DATOS_JSON = 'json';
    const FORMATO_DATOS_XML = 'xml';
    
    const TIPO_SALIDA_PDF = 'pdf';

    /**
     * @var Client $clienteGuzzle El cliente de Guzzle
     */
    protected static $clienteGuzzle = null;

    /**
     * @return GuzzleHttp\Client El cliente de Guzzle.
     */
    public static function getClienteRest()
    {
        if (is_null(static::$clienteGuzzle)) {
            $cliente = toba::servicio_web_rest('rest_arai_reportes');
            static::$clienteGuzzle = $cliente->guzzle();
        }
        return static::$clienteGuzzle;
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
            throw guarani::error(guarani_mensajes::get('800_error_conexion_otro_modulo', ['SIU-Araí Reportes']));
        }
    }

    /**
     * @return bool TRUE si se puede conectar, FALSE caso contrario.
     */
    public static function isConexionOk()
    {
        $exito = true;
        try {
            // Intento llamar a GET /info, si falla es porque la API esta caida
            static::getInfo();
        } catch (Exception $e) {
            $exito = false;
        }

        return $exito;
    }

    /**
     * Servicio REST a Araí Reportes, GET /info:
     * Devuelve información sobre la API de Araí Reportes
     * @throws guarani_error En caso de error
     * @return array Información sobre la API de Araí Reportes.
     */
    public static function getInfo()
    {
        // https://docs.guzzlephp.org/en/stable/request-options.html#http-errors
        $options['http_errors'] = false;
        $response = static::getClienteRest()->get('info', $options);
        static::validarResponse($response, static::STATUS_CODE_GET_OK, __FUNCTION__);
        $respuesta = rest_decode(guarani_rest::get_response_json($response));
        toba::logger()->debug("API Araí Reportes - GET /info:");
        toba::logger()->debug(print_r($respuesta, true));
        return $respuesta;
    }

    /**
     * Servicio REST a SIU-Araí Reportes "DELETE /carpetas":
     * Elimina una carpeta del servidor.
     * @param string $uri URI de la carpeta a eliminar.
     */
    public static function eliminarCarpeta($uri)
    {
        $options = [];
        $options['http_errors'] = false;
        $options['query']['uri'] = $uri;
        $response = static::getClienteRest()->delete('carpetas', $options);
        static::validarResponse($response, static::STATUS_CODE_DELETE_OK, __FUNCTION__);
        $respuesta = rest_decode(guarani_rest::get_response_json($response));
        toba::logger()->debug("DELETE /carpetas:");
        toba::logger()->debug(print_r($respuesta, true));
        return $respuesta;
    }

    /**
     * Servicio REST a SIU-Araí Reportes "POST /carpetas":
     * Crea una carpeta en el servidor de reportes, ruta relativa dentro del proyecto.
     * @param string $uri URI de la carpeta a crear.
     * @return string|null URI de la carpeta creada o null en caso de error.
     */
    public static function crearCarpeta($uri)
    {
        $options = [];
        $options['http_errors'] = false;
        $options['json']        = [
            'proyecto' => static::PROYECTO,
            'uri' => $uri
        ];
        $response = static::getClienteRest()->post('carpetas', $options);
        static::validarResponse($response, static::STATUS_CODE_POST_OK, __FUNCTION__);
        $respuesta = rest_decode(guarani_rest::get_response_json($response));
        toba::logger()->debug("POST /carpetas:");
        toba::logger()->debug(print_r($respuesta, true));
        return $respuesta['uri'] ?? null;
    }

    /**
     * Servicio REST a SIU-Araí Reportes "GET /carpetas":
     * Lista el contenido de la carpeta base (si existe).
     * @return array Carpetas del proyecto.
     */
    public static function getCarpetasProyecto()
    {
        $options = [];
        $options['http_errors'] = false;
        $options['query'] = [
            'proyecto' => static::PROYECTO
        ];
        $response = static::getClienteRest()->get('carpetas', $options);
        
        // Si el Status Code es 200 devuelvo array de carpetas
        if ($response->getStatusCode() == static::STATUS_CODE_GET_OK){
            $respuesta = rest_decode(guarani_rest::get_response_json($response));
        } else {// sino devuelvo array vacío
            $respuesta = [];
        }

        toba::logger()->debug("GET /carpetas:");
        toba::logger()->debug(print_r($respuesta, true));
        return $respuesta;
    }

    /**
     * Servicio REST a SIU-Araí Reportes "GET /imagenes":
     * Recupera la lista de imagenes disponibles
     * @return array Imágenes del proyecto.
     */
    public static function getImagenesProyecto()
    {
        $options = [];
        $options['http_errors'] = false;
        $options['query'] = [
            'proyecto' => static::PROYECTO
        ];
        $response = static::getClienteRest()->get('imagenes', $options);

        // Si el Status Code es 200 devuelvo array de imagenes
        if ($response->getStatusCode() == static::STATUS_CODE_GET_OK){
            $respuesta = rest_decode(guarani_rest::get_response_json($response));
        } else {// sino devuelvo array vacío
            $respuesta = [];
        }

        toba::logger()->debug("GET /imagenes:");
        toba::logger()->debug(print_r($respuesta, true));
        return $respuesta;
    }

    /**
     * Servicio REST a SIU-Araí Reportes "POST /imagenes":
     * Agrega una imagen.
     * @throws guarani_error En caso de error
     * @param string $id Id de la imagen a crear.
     * @param string $path Ruta de la imagen a crear.
     * @return string|null URI de la imagen creada o null en caso de error.
     */
    public static function agregarImagen($id, $path)
    {

        // Verifico que la imagen exista
        if (!file_exists($path)) {
           throw new guarani_error("Imagen {$path} inexistente");
        }

        $proyecto = static::PROYECTO;
        $uri = "/reportes/{$proyecto}/imagenes/{$id}";

        try {
            $data = base64_encode(file_get_contents($path));
        } catch (Exception $e) {
            throw new guarani_error("No se pudo convertir {$path} a base64");
        }

        $options = [];
        $options['http_errors'] = false;
        $options['json']        = [
            'proyecto' => $proyecto,
            'id' => $id,
            'uri' => $uri,
            'imagen' => [
                'uri' => $uri,
                'data' => $data
            ]
        ];
        $response = static::getClienteRest()->post('imagenes', $options);
        static::validarResponse($response, static::STATUS_CODE_POST_OK, __FUNCTION__);
        $respuesta = rest_decode(guarani_rest::get_response_json($response));
        toba::logger()->debug("POST /imagenes:");
        toba::logger()->debug(print_r($respuesta, true));
        return $respuesta['uri'] ?? null;
    }

    /**
     * Servicio REST a SIU-Araí Reportes "GET /reportes":
     * Recupera la lista de reportes disponibles
     * @return array Reportes del proyecto.
     */
    public static function getReportesProyecto()
    {
        $options = [];
        $options['http_errors'] = false;
        $options['query'] = [
            'proyecto' => static::PROYECTO
        ];
        $response = static::getClienteRest()->get('reportes', $options);

        // Si el Status Code es 200 devuelvo array de reportes
        if ($response->getStatusCode() == static::STATUS_CODE_GET_OK){
            $respuesta = rest_decode(guarani_rest::get_response_json($response));
        } else {// sino devuelvo array vacío
            $respuesta = [];
        }

        toba::logger()->debug("GET /reportes:");
        toba::logger()->debug(print_r($respuesta, true));
        return $respuesta;
    }
    
    /**
     * Servicio REST a SIU-Araí Reportes "POST /reportes":
     * Agrega un reporte con todos sus recursos.
     * @throws guarani_error En caso de error
     * @param string $id Id de la reporte a crear.
     * @param string $path Ruta de la reporte a crear.
     * @param bool $esPersonalizado Si es personalizado o del núcleo.
     * @return string|null URI del reporte creado o null en caso de error.
     */
    public static function agregarReporte($id, $path, $esPersonalizado = false)
    {

        // Verifico que el reporte exista
        if (!file_exists($path)) {
           throw new guarani_error("Reporte {$path} inexistente");
        }

        // Si es personalizado
        if ($esPersonalizado) {
            $id .= static::SUFIJO_PERSONALIZACION;
        }
        
        $proyecto = static::PROYECTO;
        $carpeta = $esPersonalizado ? static::CARPETA_PERSONALIZACION : static::CARPETA_NUCLEO;
        $uri = "/reportes/{$proyecto}/{$carpeta}/{$id}.jrxml";

        try {
            $data = base64_encode(file_get_contents($path));
        } catch (Exception $e) {
            throw new guarani_error("No se pudo convertir {$path} a base64");
        }

        $options = [];
        $options['http_errors'] = false;
        $options['json']        = [
            'proyecto' => $proyecto,
            'id' => $id,
            'uri' => $uri,
            'source' => [
                'data' => $data
            ]
        ];
        $response = static::getClienteRest()->post('reportes', $options);
        static::validarResponse($response, static::STATUS_CODE_POST_OK, __FUNCTION__);
        $respuesta = rest_decode(guarani_rest::get_response_json($response));
        toba::logger()->debug("POST /reportes:");
        toba::logger()->debug(print_r($respuesta, true));
        return $respuesta['uri'] ?? null;
    }

    /**
     * Servicio REST a SIU-Araí Reportes "GET /reportes/{proyecto}":
     * Recupera un reporte en el formato indicado
     * @param string $uri Id del reporte.
     * @param array|null $data Datos para el reporte [opcional].
     * @param array|null $parametros Arreglo de parametros [opcional].
     * @return string Path del PDF del reporte.
     */
    public static function recuperarReporte($uri, $data = null, $parametros = null)
    {
        $proyecto = static::PROYECTO;
        $options = [];
        $options['http_errors'] = false;
        $options['query'] = [
            'uri' => $uri,
            'tipo_salida' => static::TIPO_SALIDA_PDF,
            'formato_datos' => static::FORMATO_DATOS_JSON
        ];

        // Si vienen datos
        if (!is_null($data)) {
            $options['query']['data'] = rest_encode($data);
        }

        // Si vienen parámetros
        if (!is_null($parametros)) {
            $options['query']['parametros'] = rest_encode($parametros);
        }
        
        $response = static::getClienteRest()->get("reportes/{$proyecto}", $options);
        static::validarResponse($response, static::STATUS_CODE_GET_OK, __FUNCTION__);
        
        $filestream = $response->getBody();

        $nombreReporte = pathinfo($uri, PATHINFO_FILENAME);
        $tempDir = static::getDirTemp();
        $fileName = "{$tempDir}/{$nombreReporte}.pdf";
        static::saveFile($fileName, $filestream);

        return $fileName;
    }

    protected static function getDirTemp()
    {
        return toba::proyecto()->get_path_temp();
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
     * @param string $rutaArchivo Ruta del archivo
     * @param string $nombreArchivo Nombre del archivo (opcional, por defecto el nombre de la URI)
     * @param string $tipoSalida inline o attachment (opcional, por defecto attachment)
     * @return void
     * @throws \Exception
     */
    public static function descargarArchivo($rutaArchivo, $nombreArchivo = null, $tipoSalida = arai_reportes::CONTENT_DISPOSITION_ATTACHMENT)
    {
        // Si el archivo no existe
        if (!file_exists($rutaArchivo)) {
            $mensaje = "No se encuentra el archivo '{$rutaArchivo}'.";
            throw guarani::error($mensaje);
        }

        // Tomo el nombre por defecto
        if (is_null($nombreArchivo)) {
            $nombreArchivo = basename($rutaArchivo);
        }

        header('Content-Description: File Transfer');
        header('Content-Type: '.mime_content_type($rutaArchivo));
        header('Content-Disposition: '.$tipoSalida.'; filename="'.$nombreArchivo.'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: '.filesize($rutaArchivo));
        ob_end_clean();
        readfile($rutaArchivo);
        unlink($rutaArchivo);
        exit;
    }
    
    /**
     * Obtiene las imágenes a crear en SIU-Araí Reportes.
     * @return array Listado de imágenes.
     */
    public static function getImagenesParaCrear()
    {
        return [
            'logo_grande.png'      => static::getPathImagen('img/logo_grande.png'),
            'logo.png'             => static::getPathImagen('img/logo.png'),
            'logo_republica.jpg'   => static::getPathImagen('img/logo_republica.jpg'),
            'fondo_diploma.jpg'    => static::getPathImagen('img/fondo_diploma.jpg'),
        ];
    }

    /**
     * Busco la imagen personalizada, si la misma no existe busco la del core
     * @param string $path Ruta de la imagen.
     * @return string Ruta completa de la imagen.
     */
    protected static function getPathImagen($path)
    {
        // Busco la imagen personalizada
        $img = toba::proyecto()->get_www_pers($path);
        // Si la imagen personalizada no existe busco la del core
        if (!file_exists($img['path'])) {
            $img = toba::proyecto()->get_www($path);
        }
        return $img['path'];
    }

    /**
     * Obtiene los reportes a crear en SIU-Araí Reportes.
     * @return array Listado de reportes.
     */
    public static function getReportesParaCrear()
    {
        $pathAraiReportes = '/exportaciones/arai_reportes/';
        $pathReportesCore = toba::proyecto()->get_path() . $pathAraiReportes;
        $pathReportesPers = toba::proyecto()->get_path_pers() . $pathAraiReportes;

        // Recupero todos los archivos .jrxml del core
        $reportesCore = [];
        foreach (glob("{$pathReportesCore}*.jrxml") as $reporte) {
            $filename = pathinfo($reporte, PATHINFO_FILENAME);
            $reportesCore[$filename] = $reporte;
        }

        // Recupero todos los archivos .jrxml de la personalización
        $reportesPers = [];
        foreach (glob("{$pathReportesPers}*.jrxml") as $reporte) {
            $filename = pathinfo($reporte, PATHINFO_FILENAME);
            $reportesPers[$filename] = $reporte;
        }

        return [$reportesCore, $reportesPers];
    }

    /**
     * Obtiene la URI del logo de impresión.
     * @return string URI del logo de impresión.
     */
    public static function getUriLogoImpresion()
    {
        // Si es Autogestión
        if (comunes::es_interfaz_autogestion()) {
            $uriLogoImpresion = toba::consulta_php('co_arai_reportes')->get_uri_archivo('logo-transparente.png');
        // Si es Gestión
        } else {
            // Parecido a lo que hace guarani::get_path_logo_impresion()
            //Obtengo el logo de la Institución correspondiente, si no existe seteo el logo de Guaraní
            $uriLogoImpresion = toba::consulta_php('co_arai_reportes')->get_uri_archivo('logo_institucion.jpg');
            if (is_null($uriLogoImpresion)) {
                $uriLogoImpresion = toba::consulta_php('co_arai_reportes')->get_uri_archivo('logo_grande.png');
            }
        }
        
        return $uriLogoImpresion;
    }

    /**
     * Obtiene los reportes.
     * La clave se recupera de la tabla 'sga_constancias' columna 'constancia'.
     * @return array Listado de reportes.
     */
    public static function getReportes()
    {
        // Se comenta para que no se rompa mientras se hace esto https://gitlab.siu.edu.ar/guarani/soporte-interno/-/issues/22
        return [];
        /*return [
            1 => [
                'id' => 'ConstanciaRegular.jrxml',
                'titulo' => 'Constancia de Alumno Regular',
                'clase_consulta' => 'co_constancias',
                'metodo_consulta' => 'get_datos_constancia_regular'
            ],
            2 => [
                'id' => 'ConstanciaPromedio.jrxml',
                'titulo' => 'Constancia de Promedio Académico',
                'clase_consulta' => 'co_constancias',
                'metodo_consulta' => 'get_datos_constancia_promedio_academico'
            ],
            3 => [
                'id' => 'ConstanciaPromedioSinAplazos.jrxml',
                'titulo' => 'Constancia de Promedio Académico Sin Aplazos',
                'clase_consulta' => 'co_constancias',
                'metodo_consulta' => 'get_datos_constancia_promedio_academico_sin_aplazos'
            ],
            4 => [
                'id' => 'ConstanciaActividadesRendidas.jrxml',
                'titulo' => 'Constancia de Actividades Rendidas por Examen',
                'clase_consulta' => 'co_constancias',
                'metodo_consulta' => 'get_datos_constancia_actividades_rendidas'
            ]
        ];*/
    }

    /**
     * Determina si un reporte esta habilitado o no.
     * @param int $id Id del reporte (ej: 1).
     * @return bool TRUE si el reporte esta habilitado, FALSE en caso contrario.
     */
    public static function esReporteHabilitado($id)
    {
        $reportesHabilitados = static::getReportes();
        return array_key_exists($id, $reportesHabilitados);
    }

    /**
     * Obtiene información del reporte.
     * @param int $id Id del reporte (ej: 1).
     * @return array Información del reporte.
     */
    public static function getInformacionReporteById($id)
    {
        $reportesHabilitados = static::getReportes();
        return $reportesHabilitados[$id];
    }
}