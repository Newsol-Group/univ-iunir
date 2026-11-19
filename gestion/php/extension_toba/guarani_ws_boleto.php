<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Description of guarani_ws_boleto
 *
 * @author jmarino
 */
class guarani_ws_boleto
{
	protected static $cliente_boleto;
	
	/**
	 * @return GuzzleHttp\Client
	 */
	protected static function get_cliente_rest()
	{
		if(!isset(self::$cliente_boleto)){
			$cliente = toba::servicio_web_rest('sube');
			self::$cliente_boleto = $cliente->guzzle();
		}
		return self::$cliente_boleto;
	}

	public static function conexion()
	{
		$conexion = array();
		$ini = new toba_ini(toba::instancia()->get_path_instalacion_proyecto('guarani').'/rest/sube/cliente.ini');
		$conexion['usuario'] = $ini->get('conexion', 'auth_usuario');
		$conexion['clave'] = $ini->get('conexion', 'auth_password');
		$conexion['ua'] = $ini->get('conexion', 'auth_ua');
		return $conexion;
	}
	
	public static function get_mensaje_descripcion($guzzle_res)
	{
		$response = rest_decode(guarani_rest::get_response_json($guzzle_res));
		if (isset($response['descripcion'])) {
			$mje = $response['descripcion'];
		} else {
			if (isset($response['errores']) && isset($response['errores'][0]['error'])) {
				$mje = $response['errores'][0]['error']; //muestro el primer error
			} else {
				$mje = 'Error en la comunicación con Sube';
			}
		}
		return $mje;
	}
	
	/*
	 * Valida si la respuesta es igual al estado $status.
	 */
	protected static function validar_response($response, $status, $desc_error)
	{		
		if($response->getStatusCode() != $status){
			echo 'Error en '. $desc_error . ". Se esperaba $status y se obtuvo {$response->getStatusCode()}\n";			
			echo static::get_mensaje_descripcion($response);
		} else {
			$resultados = rest_decode(guarani_rest::get_response_json($response));
			return static::generar_respuestas_actualizar($resultados['results']);			
		}		
	}
	
	/*
	 * Agrega novedad del alumno
	 */
	public static function agregar_novedad($tipo, $nro, $id, $operacion)
	{
		// Si la operación es 'B' (baja) deshabilito el beneficio, sino se lo vuelvo a habilitar.
		$operation = ($operacion == boleto_estudiantil::OPERACION_B)? boleto_estudiantil::OPERACION_QUITAR_BENEFICIO : boleto_estudiantil::OPERACION_REANUDAR_BENEFICIO;
		return array(
					"id" => $id,
					"student_id_type" =>  $tipo,
					"student_id" => $nro,
					"operation" => $operation,
					"metadata" => "",
				);
	}
	
	/*
	 * Para el llamado al servicio deben pasarse estos parametros
	 * user => Usuario
	 * password => Password
	 * student_department => UnidadAcademica
	 * updates => Novedades 
	 */
	public static function get_parametros_novedad($updates)
    {
		$conexion = static::conexion();
		$body = array(
						'user' => $conexion['usuario'],
						'password' => $conexion['clave'],
						'student_department' => $conexion['ua'],
						'updates' =>  json_encode(array('updates'=>$updates))
					);		
		$param = array( 'form_params' => $body);
		return $param;
    }
	
	/*
	 * Con todos los parametros se actualiza el beneficio
	 */
	public static function actualizar_beneficio($updates)
	{		
		$parametros = static::get_parametros_novedad($updates);
		try {
			$response = static::get_cliente_rest()->post('benefit-update', $parametros);
			//Valida el status de la respuesta.			
			return static::validar_response($response, 200, __FUNCTION__);
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			echo $e->getMessage();
			return null;
		} catch (Exception $e) {
			echo $e->getMessage();			
			return null;
		}
	}
	
	protected static function generar_respuestas_actualizar($resultados)
    {
        $respuestas = array();
        foreach ($resultados as $resultado)
        {
            $respuestas[] = static::generar_respuesta_actualizar($resultado);
        }
        return $respuestas;
    }
    
	/*
	 * Arma el array de respuesta.
	 */
    protected static function generar_respuesta_actualizar($resultado)
    {	
        return array(
            'id' => $resultado['request_id'],
            'respuesta_id' => $resultado['request_id'],
            'exito' => ($resultado['code'] == '200'),
            'msg' => $resultado['message'],
            'msg_id' => $resultado['code'],
        );
    }
}