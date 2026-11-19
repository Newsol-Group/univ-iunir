<?php

namespace siu\modelo\transacciones;

use GuzzleHttp\Client;
use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;
use siu\modelo\transacciones\transaccion_alumno;

class boleto_estudiantil extends transaccion_alumno {

    //Conexión Web Service SUBE
    protected static $config_sube_ws;
    protected static $cliente_sube_ws;
    protected static $WebConf;
	
    public static function info__config_sube_ws()
    {
		if(!isset(static::$config_sube_ws)) {
			// Parse with sections
			static::$WebConf = include kernel::proyecto()->get_dir_instalacion()."/servicios_web_config.php";
			static::$config_sube_ws = static::$WebConf['consumidos']['boleto_estudiantil'];
		}
		
		return static::$config_sube_ws;
    }
    
    public static function info__config_sube_ws_base_url()
    {
		$config = static::info__config_sube_ws();
		return $config['parametros']['base_uri'];
    }
	
    public static function info__config_sube_ws_user()
    {
		$config = static::info__config_sube_ws();
		return $config['parametros']['auth'][0];
    }
	
    public static function info__config_sube_ws_pass()
    {
		$config = static::info__config_sube_ws();
		return $config['parametros']['auth'][1];
    }
	
    public static function info__config_sube_ws_tipo()
    {
		$config = static::info__config_sube_ws();
		return $config['tipo'];
    }
	
    public static function info__config_sube_ws_ua()
    {
		$config = static::info__config_sube_ws();
		return $config['parametros']['auth_ua'];
    }
	
    public static function info__config_sube_ws_verify()
    {
		$config = static::info__config_sube_ws();
		return $config['parametros']['verify'];
    }
	
    function info__datos_basicos_persona()
    {
        // De acá obtengo el mismo email que figura en Configuración
        $configuracion = \siu\guarani::persona()->get_configuracion();
        $datos_basicos_persona = catalogo::consultar('boleto_estudiantil', 'datos_basicos_persona', array('persona' => $this->persona));
        $datos_basicos_persona['email'] = $configuracion['email'];
        return $datos_basicos_persona;
    }

    function info__tiene_solicitud()
    {
        return catalogo::consultar('boleto_estudiantil', 'tiene_solicitud', array('persona' => $this->persona));
    }

    function info__ultima_solicitud_pendiente()
    {
        return catalogo::consultar('boleto_estudiantil', 'ultima_solicitud_pendiente', array('persona' => $this->persona));
    }

    function info__cumple_condicion_be()
    {
        return catalogo::consultar('boleto_estudiantil', 'cumple_condicion_be', array('persona' => $this->persona));
    }

    function info__listado_solicitudes()
    {
        $solicitudes = catalogo::consultar('boleto_estudiantil', 'listado_solicitudes', array('persona' => $this->persona));

        foreach ($solicitudes as &$solicitud) {

            if ($solicitud['operacion'] == \boleto_estudiantil::OPERACION_B) {
                $solicitud['operacion_str'] = kernel::traductor()->trans('boleto_estudiantil.baja');
            }
            else if ($solicitud['operacion'] == \boleto_estudiantil::OPERACION_A) {
                $solicitud['operacion_str'] = kernel::traductor()->trans('boleto_estudiantil.alta');
            }

            if ($solicitud['procesado'] == \comunes::si) {
                $solicitud['procesado_str'] = kernel::traductor()->trans('boleto_estudiantil.procesado');
            }
            else if ($solicitud['procesado'] == \comunes::no) {
                $solicitud['procesado_str'] = kernel::traductor()->trans('boleto_estudiantil.pendiente');
            }
			
			switch ($solicitud['respuesta']) {
				case \boleto_estudiantil::RESPUESTA_ACEPTADA:
					$solicitud['respuesta_str'] = kernel::traductor()->trans('boleto_estudiantil.aceptada');
					break;
				case \boleto_estudiantil::RESPUESTA_RECHAZADA:
					$solicitud['respuesta_str'] = kernel::traductor()->trans('boleto_estudiantil.rechazada');
					break;
				case \boleto_estudiantil::RESPUESTA_PENDIENTE:
				case \boleto_estudiantil::RESPUESTA_NUEVA:
					$solicitud['respuesta_str'] = kernel::traductor()->trans('boleto_estudiantil.pendiente');
					break;
				case \boleto_estudiantil::RESPUESTA_ACTUALIZADA:
					if ($solicitud['operacion'] == \boleto_estudiantil::OPERACION_B) {
						$solicitud['respuesta_str'] = kernel::traductor()->trans('boleto_estudiantil.baja');
					} else {
						$solicitud['respuesta_str'] = kernel::traductor()->trans('boleto_estudiantil.aceptada');
					}
					break;
			}

            $solicitud['respuesta_desc_str'] = kernel::traductor()->trans("boleto_estudiantil.mensajes.{$solicitud['operacion']}.{$solicitud['respuesta']}.{$solicitud['respuesta_desc']}");

            $dt = new \DateTime($solicitud['fecha_solicitud']);
            $solicitud['fecha_solicitud_str'] = $dt->format('d/m/Y H:i');
        }

        return $solicitudes;
    }

    function evt__realizar_solicitud($hash, $url_formulario_sube, $condicion, $observaciones)
    {
        try{
            kernel::db()->abrir_transaccion();

            catalogo::consultar('boleto_estudiantil', 'insertar_solicitud', array(
                'hash' => $hash,
                'url_formulario_sube' => $url_formulario_sube,
                'persona' => $this->persona,
                'condicion' => $condicion,
                'observaciones' => $observaciones
            ));
            
            kernel::db()->cerrar_transaccion();
        }
        catch (\Exception $e) {
            kernel::db()->abortar_transaccion();
            throw $e;
        }

    }

    /**
     * @return Client
     */
    protected static function get_cliente_ws()
    {
        if (!isset(static::$cliente_sube_ws)) {

            $cliente = new Client(array(
				'base_uri' => static::info__config_sube_ws_base_url(),
				'auth' => array(static::info__config_sube_ws_user(), static::info__config_sube_ws_pass(), static::info__config_sube_ws_tipo()),
				'verify' => static::info__config_sube_ws_verify()
            ));

            static::$cliente_sube_ws = $cliente;
        }
        return static::$cliente_sube_ws;
    }

    /**
     * Servicio Web a SUBE "POST /benefit-request":
     * Solicita beneficio de boleto estudiantil a SUBE.
     * @param array $body arreglo asociativo con los datos del body recibidos por el servicio.
     * Consta del siguiente formato:
     * <code>
     *  array(
     *      "student_id_type" => "Tipo de documento del alumno",
     *      "student_id" => "Nro. documento del alumno",
     *      "student_name" => "Nombre del alumno",
     *      "student_last_name" => "Apellido del alumno",
     *      "student_department" => "Facultad del alumno"
     *  )
     * </code>
     * @return array
     * <code>
     * array(
     * "status" => "Código de estado HTTP",
     * "data"   => array(
     *                  "tid" => "Id de tramite",
     *                  "redirect-url" => "URL formulario SUBE",
     *                  "in_progress" => "Si tiene un trámite vigente"
     *              )
     * )
     * </code>
     */
    public static function ws_solicitar_beneficio($body)
    {

        kernel::log()->add_debug("Body POST benefit-request", $body);

        $response = static::get_cliente_ws()->post('benefit-request',
            array(
                'form_params' => array_a_utf8($body),
                'exceptions' => false
            ));

        $status = $response->getStatusCode();
        $data = rest_decode(\guarani_rest::get_response_json($response));

        kernel::log()->add_debug("Response POST benefit-request", $data);

        //data.in_progress: Devuelve 0 si no tiene un trámite vigente (en progreso) y 1 si lo tiene.
        /*if (isset($data['data']['in_progress']) && ($data['data']['in_progress'] == 1))
        {
            $data['status'] = static::STATUS_CODE_SOLICITUD_BENEFICIO_YA_ASIGNADO;
        }*/

        return $data;

    }

}