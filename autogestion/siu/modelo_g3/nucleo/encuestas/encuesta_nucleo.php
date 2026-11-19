<?php
/**
 * Constantes para encuestas.
 * 
 * @package Encuestas
 */

use GuzzleHttp\Psr7\Response;

class encuesta_nucleo
{
	// Alcances.
	const alcance_toda_institucion = 1;
	const alcance_responsables_academicas = 2;
	const alcance_tipos_propuesta = 3;
	const alcance_propuestas = 4;
	const alcance_actividades = 5;

	// Tipos.
	const tipo_docente = 1;
	const tipo_academica = 2;
	const tipo_docente_academica = 3;
	const tipo_generica = 4;
	const tipo_egresados = 5;
	const tipo_pares = 6;

	// Subtipos.
	const subtipo_academica = 'A';
	const subtipo_docente = 'D';
	const subtipo_generica = 'G';
	const subtipo_egresados = 'E';

	// Encuestados
	const encuestado_alumno  = 'A'; 
	const encuestado_docente = 'D';
	const encuestado_todos   = 'X';
		
	// Alumnos.
	const alumnos_todos = 'T';
	const alumnos_algunos = 'A';
	const alumnos_estado_tesis = 'E';

	// Inscriptos a comisión.
	const inscriptos_comision_todos = 'T';
	const inscriptos_comision_algunos = 'A';

	// Docentes.
	const docentes_todos = 'T';
	const docentes_por_roles = 'R';

	// Egresados.
	const egresados_todos = 'T';
	const egresados_algunos = 'A';

	// Pares.
	const pares_responsable_docentes = 'R';
	const pares_docentes_responsable = 'D';
	const pares_todos = 'T';

	// Tipos de relevamiento.
	const tipo_relevamiento_becas = 'B';
	const tipo_relevamiento_becas_descr = 'Becas';
	const tipo_relevamiento_encuestas = 'E';
	const tipo_relevamiento_encuestas_descr = 'Encuestas';

	//Estados de la sincronizacion de mis respuestas
	const sinc_pendiente = 'PENDIENTE';
	const sinc_error = 'ERROR';
	const sinc_ok = 'OK';			
	
	//Imagen default si el docente no tiene foto cargada
	const imagen_docente_default = 'mm_medio.png';
	protected static $cliente_kolla;
    protected static $ug;

	/**
	 * Verifica la conexión con SIU-Kolla
	 * 
	 * @return boolean true: conexión OK | false: no se puede conectar
	 */
	public static function conexion_kolla_ok()
	{
		try {
			// Se realiza un pedido dummy a Kolla para testear la conexión
			$response = static::get_cliente_rest()->get('unidad-gestion', array());
			$exito = true;
		}
		catch (\GuzzleHttp\Exception\ClientException $e) {
			toba::logger()->error($e->getRequest());
			toba::logger()->error($e->getResponse());
			toba::logger()->error($e->getMessage());
			$exito = false;
		}
		catch (Exception $e) {
			toba::logger()->error($e->getMessage());
			$exito = false;
		}
		return $exito;
	}
	
	/**
	 * Obtiene las Unidades de Gestión definidas en Kolla
	 * 
	 * @return array
	 */
	public static function get_kolla_ugs()
	{
		try {
			$response = static::get_cliente_rest()
				->get('unidad-gestion',
					array()
				);
			static::validar_response($response, 200, __FUNCTION__);
			$datos = rest_decode(guarani_rest::get_response_json($response));
			// Se excluye la unidad de gestión predeterminada de Kolla.
			$salida = array();
			foreach ($datos as $dato) {
				if ($dato['unidad_gestion'] !== static::get_ug_default_kolla()) {
					$salida[] = $dato;
				}
			}
			return $salida;
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			toba::logger()->error($e->getMessage());
			return null;
		} catch (Exception $e) {
			toba::logger()->error($e->getMessage());
			return null;
		}
	}
	
	/**
	 * Determina si está parametrizado que se usan Unidades de Gestión de Guaraní 
	 * en la vinculación de encuestas con Kolla.
	 * 
	 * @return boolean	true: se utilizan UGs para la comunicación
	 *					false: la vinculación de encuestas con Kolla es única para toda la institución
	 */
	public static function kolla_utiliza_ug()
	{
		return guarani::parametros()->get_valor('enc_utiliza_ug_kolla', null, false) == comunes::si;
	}
	
	public static function get_ug_default_kolla()
	{
		return guarani::parametros()->get_valor('enc_kolla_unidad_gestion', null, false);
	}

    public static function get_encuestas($ug)
	{
		$response = static::get_cliente_rest()
			->get('encuestas',
				array('query' => array('unidad_gestion' => $ug))
			);
		static::validar_response($response, 200, __FUNCTION__);
		return rest_decode(guarani_rest::get_response_json($response));
	}

	public static function crear_habilitacion($datos, $ug)
	{
		$response = static::get_cliente_rest()
			->post('habilitaciones',
				array(
                    'query' => array('unidad_gestion' => $ug),
                    'body'  => rest_encode($datos)
                )
			);
		static::validar_response($response, 201, __FUNCTION__);
		return rest_decode(guarani_rest::get_response_json($response));
	}

	public static function actualizar_habilitacion($datos, $ug)
	{
		$id = $datos['habilitacion'];
		
		$campos_permitidos = [
			"fecha_desde",
			"fecha_hasta",
			"paginado",
			"anonima",
			"publica",
			"estilo",
			"password",
			"descripcion",
			"texto_preliminar",
			"generar_codigo_recuperacion",
			"url_imagenes_base",
			"unidad_gestion",
			"descarga_pdf"
		];

		$body = [];
		// Recorro los campos permitidos por PUT /habilitaciones/{id_habilitacion}
		foreach ($campos_permitidos as $campo) {
			// Si se pasa un campo permitido va al body
			if (isset($datos[$campo])) {
				$body[$campo] = $datos[$campo];
			}
		}
                
		$response = static::get_cliente_rest()->put("habilitaciones/{$id}",
			[
				'query' => ['unidad_gestion' => $ug],
				'body'  => rest_encode($body)
			]
		);

		static::validar_response($response, 204, __FUNCTION__);
	}

        /**
	 * Servicio REST a Kolla "PUT /habilitaciones/{id_habilitacion}/formularios-respondidos/{codigo_externo}"
	 * Modifica datos de un formulario respondido en una habilitación.
         * @param int $id_habilitacion
         * @param string $codigo_externo
         * @param string $ug
	 * @param array $datos
	 * Consta del siguiente formato:
	 * <code>
	 *  Array
         *  (
         *      [prorroga_hasta] => 2024-03-30
         *  )
	 * </code>
	 * @throws guarani_error Si falla.
	 */
        public static function modificar_formulario_respondido($id_habilitacion, $codigo_externo, $ug, $datos)
	{
            $response = static::get_cliente_rest()->put(
                "habilitaciones/{$id_habilitacion}/formularios-respondidos/{$codigo_externo}",
                [
                    'query' => ['unidad_gestion' => $ug],
                    'body'  => rest_encode($datos)
                ]
            );

            static::validar_response($response, 204, __FUNCTION__);
	}

	public static function get_habilitacion($id, $ug)
	{
		$response = static::get_cliente_rest()
			->get('habilitaciones/'. $id,
				array(
                    'query' => array('unidad_gestion' => $ug)
                    )
			);
		static::validar_response($response, 200, __FUNCTION__);
		return rest_decode(guarani_rest::get_response_json($response));
	}

	public static function actualizar_masivo($recursos, $endpoint, $ug)
	{
		$response = static::get_cliente_rest()
			->put($endpoint.'/masivo',
				array(
                    'query'		 => array('unidad_gestion' => $ug),
                    'body'       => rest_encode($recursos),
                    'exceptions' => false,
                )
			);

        if ($response->getStatusCode() == 204) {
            return array();
        }

        $errores = rest_decode(guarani_rest::get_response_json($response));
        toba::logger()->var_dump($response);

        if (isset($errores['errores'])) {
            toba::logger()->error(print_r($errores['errores'], true));

            return $errores['errores'];
        }

        throw guarani::error(static::get_mensaje_descripcion($response));
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
				$mje = 'Error en la comunicación con Kolla';
			}
		}
		return $mje;
	}

	public static function get_bloques($encuesta, $ug)
	{
		$response = static::get_cliente_rest()
			->get('encuestas/'. $encuesta.'/bloques',
				array(
                    'query' => array('unidad_gestion' => $ug)
                    )
			);
		static::validar_response($response, 200, __FUNCTION__);
		return rest_decode(guarani_rest::get_response_json($response));
	}
	
	
	//Consulta la lista de preguntas del bloque $id_bloque de la encuesta $id_encuesta
	public static function get_preguntas($id_encuesta, $id_bloque, $unidad_gestion)
	{

		$response = static::get_cliente_rest()
			->get("encuestas/{$id_encuesta}/bloques/{$id_bloque}/preguntas",
				  array('query' => array('unidad_gestion' => $unidad_gestion),
					    'exceptions' => false
				  		)
				 );

		
		if ($response->getStatusCode() == "404") {//La Unidad de Gestion o la encuesta o el bloque no existe
			//throw new error_guarani(kernel::traductor()->trans('errores_kolla.parametros_incorrectos'));
		} elseif ($response->getStatusCode() == "400") {//No se pudo validar la encuesta
			//throw new error_guarani(kernel::traductor()->trans('errores_kolla.no_existe_encuesta', array('%encuesta%' => $id_encuesta)));
		} elseif ($response->getStatusCode() == "500") {//Error interno de kolla
			//throw new error_guarani(kernel::traductor()->trans('errores_kolla.error_interno_kolla'));
		} else {
			static::validar_response($response, 200, __FUNCTION__);
			$preguntas = rest_decode(\guarani_rest::get_response_json($response));			
			return ($preguntas['detalle']['detalle']);
		}
	}
	
	//Consulta la respuesta de la pregunta $id_pregunta para la encuesta $id_encuesta
	public static function get_respuesta($id_habilitacion, $id_formulario, $id_encuesta, $id_elemento, $id_bloque, $id_pregunta, $pregunta_orden, $unidad_gestion)
	{

		$response = static::get_cliente_rest()->get("habilitaciones/{$id_habilitacion}/formularios/{$id_formulario}/encuestas/{$id_encuesta}/elementos/{$id_elemento}/bloque/{$id_bloque}/pregunta/{$id_pregunta}/orden/{$pregunta_orden}/respuestas",
			array('query' => array('unidad_gestion' => $unidad_gestion,				
			),
				'exceptions' => false)
		);
		
		if ($response->getStatusCode() == "404") {//Alguno de los parámetros no existe
			//throw new error_guarani(kernel::traductor()->trans('errores_kolla.no_existe_parametros'));
		} elseif ($response->getStatusCode() == "400") {//No se pudieron validar los datos ingresados
			//throw new error_guarani(kernel::traductor()->trans('errores_kolla.parametros_incorrectos'));
		} elseif ($response->getStatusCode() == "500") {//Error interno de kolla
			//throw new error_guarani(kernel::traductor()->trans('errores_kolla.error_interno_kolla'));
		} else {
			static::validar_response($response, 200, __FUNCTION__);
			$respuesta = rest_decode(\guarani_rest::get_response_json($response));

			return ($respuesta);
		}

	}
	
	/**
	 * Obtiene el PDF correspondiente a un formulario respondido.
	 *
	 * @see http://docs.guzzlephp.org/en/stable/request-options.html#sink
	 * 
	 * @param int $habilitacion
	 * @param int $formulario
	 * @param string $codigo_externo
	 * @param string $unidad_gestion
	 * @param string $path_descarga
	 *
	 * @return array En 'ok' si salió todo bien, en 'path' la ubicación del PDF.
	 */
	public static function get_pdf_formulario_respondido($habilitacion, $formulario, $codigo_externo, $unidad_gestion, $path_descarga = '/tmp/kolla.pdf')
	{
		
		$retorno = ['ok' => false, 'path' => $path_descarga];
		
		try {
			
			$resource = fopen($path_descarga, 'w');

			$response = static::get_cliente_rest()->get(
				"formularios_habilitados/respondidos/{$formulario}/pdf/{$codigo_externo}", 
				[
					'query' => [
						'habilitacion' => $habilitacion,
						'unidad_gestion' => $unidad_gestion,
				],
				'sink' => $resource
			]);
			
			if ($response->getStatusCode() == 200) {
				$retorno['ok'] = true;
			}
		
		} catch (\Exception $e) {
			
			$retorno['ok'] = false;
			toba::logger()->error($e->getMessage());
			
		}
		
		return $retorno;
	}
	
	/**
	 * Retorna un listado de todos los estilos disponibles para las habilitaciones
	 * 
	 * @return array
	 */
	public static function get_estilos_habilitaciones()
	{
		$response = static::get_cliente_rest()->get('habilitaciones/propiedades/estilos');
		static::validar_response($response, 200, __FUNCTION__);
		return rest_decode(guarani_rest::get_response_json($response));
	}
	
	/**
	 * @return \GuzzleHttp\Client
	 */
	protected static function get_cliente_rest()
	{
		if(!isset(self::$cliente_kolla)){
			$cliente = toba::servicio_web_rest('kolla');
			self::$cliente_kolla = $cliente->guzzle();
		}
		return self::$cliente_kolla;
	}

	/**
	 * Valida si el response tiene el status requerido, en caso de error devuelve una descripción del mismo
	 *
	 * @param Response						$response 
	 * @param integer						$status	estado contra el que valida si el response tiene ese estado
	 * @param string						$desc_error	descripción en caso de que haya un error.
	 */
	protected static function validar_response(Response $response, $status, $desc_error)
	{
		if($response->getStatusCode() != $status){
			toba::logger()->error('Error en '. $desc_error . ". Se esperaba $status y se obtuvo {$response->getStatusCode()}");
			toba::logger()->var_dump(rest_decode(guarani_rest::get_response_json($response)));
			throw guarani::error(static::get_mensaje_descripcion($response));
		}
	}
}