<?php
use GuzzleHttp\Client;

/**
 * Clase para consumir los WS de Moodle
 * @see https://docs.moodle.org/dev/Web_service_API_functions
 * @package Moodle
 */
class moodle_nucleo
{
	/*
	 * Códigos de respuesta HTTP
	 */
	const STATUS_CODE_OK = 200;

	/*
	 * Formato de respuesta de Moodle
	 */
	const FORMATO_RESPUESTA_JSON = 'json';

	/*
	 * Segmentos de URLs de Moodle
	 */
	const SEGMENTO_URL_CURSO = '/course/view.php?id=';

	/*
	 * Crear Usuarios
	 */
	const LANG_ES = 'es';
	const AUTH_EMAIL = 'email';
	const AUTH_MANUAL = 'manual';
	const AUTH_NOLOGIN = 'nologin';
	const AUTH_USERKEY = 'userkey';
	const AUTH_CAS = 'cas';
	const AUTH_DB = 'db';
	const AUTH_LDAP = 'ldap';
	const AUTH_LTI = 'lti';
	const AUTH_MNET = 'mnet';
	const AUTH_NONE = 'none';
	const AUTH_OAUTH2 = 'oauth2';
	const AUTH_SHIBBOLETH = 'shibboleth';
	const AUTH_WEBSERVICE = 'webservice';
	const TIMEZONE_BSAS = 'America/Argentina/Buenos_Aires';
	const COUNTRY_AR = 'AR';
	const CANT_PAGINADO_USUARIOS = 500;
	/**
	 * Expresion regular para usuario
	 * username can only contain alphabetical lowercase letters , numbers, hypen '-', underscore '_', period '.', or at-sign '@'
	 */
	const exp_regular_usuario = '([^a-z0-9.@_-])';
	const msj_error_username = "El nombre de usuario solo puede contener letras minúsculas, números, guion '-', guión bajo '_', punto '.' o signo de arroba '@'";
	const error_shortnametaken = 'shortnametaken';
        
	const NOMBRE_COMPLETO_CURSO_MAXLENGTH = 254;
	const NOMBRE_CORTO_CURSO_MAXLENGTH = 100;

	protected $host;
	protected $url;
	protected $token;
	/**
	 * @var Client $clienteGuzzle El cliente de Guzzle
	 */
	protected $clienteGuzzle;
	/**
	 * @var array $funcionesRequeridas Funciones requeridas para que Guaraní se pueda comunicar con Moodle
	 */
	protected $funcionesRequeridas = [
		'core_webservice_get_site_info',
		'core_course_get_categories',
		'core_course_get_courses',
		'core_course_get_courses_by_field',
		'core_enrol_get_enrolled_users',
		'core_enrol_get_users_courses',
		'core_user_create_users',
		'core_user_update_users',
		'enrol_manual_enrol_users',
		'enrol_manual_unenrol_users',
		'auth_userkey_request_login_url',
		'core_course_create_courses',
		'core_course_import_course',
		'local_user_get_roles',
		'core_user_get_users',
		'core_group_create_groups',
		'core_group_add_group_members',
		'core_group_delete_groups',
			//'core_user_get_users_by_field',
	];

	/**
	 * Constructor de la clase.
	 *
	 * @param string $host Host de Moodle, ej: http://uba.edu.ar/moodle.
	 * @param string $url Url de Moodle, ej: webservice/rest/server.php.
	 * @param string $token Token de Moodle, ej: e20c039445674046702510d0fd882ea4.
	 *
	 * @return void.
	 */
	public function __construct($host, $url, $token)
	{
		$this->host = trim($host, '/');
		$this->url = trim($url, '/');
		$this->token = trim($token);

		$partes_uri = [$this->host, $this->url];
		$base_uri = implode('/', $partes_uri);

		toba::logger()->debug("base_uri: {$base_uri}");
		$this->clienteGuzzle = new Client(['base_uri' => $base_uri]);
	}

	/**
	 * Servicio REST a Moodle "core_webservice_get_site_info":
	 * Verifica si se puede conectar Moodle.
	 * @see https://docs.moodle.org/dev/Web_service_API_functions API Moodle.
	 * @return boolean TRUE si se puede conectar, FALSE caso contrario.
	 */
	public function verificarConexion()
	{

		$conexionOk = false;

		try {

			$response = $this->clienteGuzzle->post('', [
				'form_params' => [
					'wstoken' => $this->token,
					'moodlewsrestformat' => static::FORMATO_RESPUESTA_JSON,
					'wsfunction' => 'core_webservice_get_site_info'
				]
			]);

			$responsePhp = rest_decode(guarani_rest::get_response_json($response));

			list($conexionOk, $messageMoodle) = static::validarResponse($responsePhp);
		} catch (Exception $e) {

			$conexionOk = false;
			toba::logger()->error($e->getMessage());
		}

		return $conexionOk;
	}

	/**
	 * Servicio REST a Moodle "core_webservice_get_site_info":
	 * Verifica si faltan habilitar funciones en Moodle.
	 * @see https://docs.moodle.org/dev/Web_service_API_functions API Moodle.
	 * @param array $funcionesRequeridas Arreglo de funciones requeridas, por defecto toma todas.
	 * @return array Arreglo de funciones que faltan habilitar en Moodle.
	 */
	public function verificarFunciones($funcionesRequeridas = [])
	{
		$funcionesFaltantes = [];

		if (empty($funcionesRequeridas)) {
			$funcionesRequeridas = $this->funcionesRequeridas;
		}

		try {

			$response = $this->clienteGuzzle->post('', [
				'form_params' => [
					'wstoken' => $this->token,
					'moodlewsrestformat' => static::FORMATO_RESPUESTA_JSON,
					'wsfunction' => 'core_webservice_get_site_info'
				]
			]);

			$responsePhp = rest_decode(guarani_rest::get_response_json($response));

			list($responseOk, $messageMoodle) = static::validarResponse($responsePhp);

			// Si la respuesta esta OK
			if ($responseOk && isset($responsePhp['functions'])) {

				// Obtengo la lista de funciones habilitadas
				$funciones = array_column($responsePhp['functions'], 'name');
				// Obtengo las funciones faltantes
				$funcionesFaltantes = array_diff($funcionesRequeridas, $funciones);
			}
		} catch (Exception $e) {

			toba::logger()->error($e->getMessage());
		}

		return $funcionesFaltantes;
	}

	public static function validarResponse($response)
	{
		toba::logger()->debug("Response:");
		toba::logger()->debug(print_r($response, true));

		$responseOk = true;
		$messageMoodle = "";

		// Si dio una excepción
		if (isset($response['exception'])) {

			$responseOk = false;

                        /* Si en Moodle "Mensajes de depuración" esta configurado como:
                         * - NORMAL: Mostrar errores, avisos y noticias
                         * - TODOS: Mostrar todos los mensajes de depuración PHP
                         * - DESARROLLADOR: mensajes extra de depuración Moodle para desarrolladores
                         *
                         * @see https://documentacion.siu.edu.ar/wiki/SIU-Guarani/version3.21.0/interfaces/moodle#Habilitar_la_Depuraci.C3.B3n_para_desarrolladores
                         */
                        if (isset($response['debuginfo'])) {
                            $messageMoodle = $response['debuginfo'];
                        } else {
                        /* Si en Moodle "Mensajes de depuración" esta configurado como:
                         * - NINGUNO: No mostrar errores ni avisos
                         * - MÍNIMO: Mostrar solamente errores fatales
                         */
                            $messageMoodle = "{$response['errorcode']} - {$response['message']}";
                        }
                        
			$e = new Exception("Response: " . print_r($response, true));
			toba::logger()->error($e->getMessage());
		}

		return [$responseOk, $messageMoodle];
	}

	/**
	 * Servicio REST a Moodle "core_course_get_categories":
	 * Categorías de cursos de Moodle.
	 * @see https://docs.moodle.org/dev/Web_service_API_functions API Moodle.
	 * @return array Categorías de cursos de Moodle.
	 */
	public function getCategoriasCursos()
	{

		$categorias = [];

		try {

			$response = $this->clienteGuzzle->post('', [
				'form_params' => [
					'wstoken' => $this->token,
					'moodlewsrestformat' => static::FORMATO_RESPUESTA_JSON,
					'wsfunction' => 'core_course_get_categories'
				]
			]);

			$responsePhp = rest_decode(guarani_rest::get_response_json($response));

			list($responseOk, $messageMoodle) = static::validarResponse($responsePhp);

			// Si la respuesta esta OK devuelvo la misma
			if ($responseOk)
				$categorias = $responsePhp;
		} catch (Exception $e) {

			toba::logger()->error($e->getMessage());
		}

		return $categorias;
	}

	/**
	 * Servicio REST a Moodle "local_user_get_roles":
	 * Roles de Moodle.
	 * @return array Roles de Moodle.
	 */
	public function getRoles()
	{

		$roles = [];

		try {

			$response = $this->clienteGuzzle->post('', [
				'form_params' => [
					'wstoken' => $this->token,
					'moodlewsrestformat' => static::FORMATO_RESPUESTA_JSON,
					'wsfunction' => 'local_user_get_roles'
				]
			]);

			$responsePhp = rest_decode(guarani_rest::get_response_json($response));

			list($responseOk, $messageMoodle) = static::validarResponse($responsePhp);

			// Si la respuesta esta OK devuelvo la misma
			if ($responseOk)
				$roles = $responsePhp;
		} catch (Exception $e) {

			toba::logger()->error($e->getMessage());
		}

		return $roles;
	}

	/**
	 * Servicio REST a Moodle "core_course_get_courses":
	 * Cursos de Moodle.
	 * @see https://docs.moodle.org/dev/Web_service_API_functions API Moodle.
	 * @param int|array|null $ids ids de Cursos a obtener.
	 * Consta del siguiente formato:
	 * <code>
	 *  [1, 8, 14] va a obtener los cursos con id 1, 8 y 14
	 * 	8 va a obtener el curso con id 8
	 * 	null va a obtener todos los cursos
	 * </code>
	 * @param bool $devolver_vacio_error Si cuando hay un error devuelve -1 o [] (array vacío).
	 * @return array Cursos de Moodle.
	 */
	public function getCursos($ids = null, $devolver_vacio_error = true)
	{

		$cursos = ($devolver_vacio_error) ? [] : -1;

		try {

			$form_params = [
				'wstoken' => $this->token,
				'moodlewsrestformat' => static::FORMATO_RESPUESTA_JSON,
				'wsfunction' => 'core_course_get_courses'
			];

			// Si viene el parámetro $ids
			if (!is_null($ids)) {
				$options = [];
				if (is_array($ids)) {
					foreach ($ids as $key => $id) {
						$options['ids'][$key] = $id;
					}
				} else {
					$options['ids'][0] = $ids;
				}

				$form_params['options'] = $options;
			}

			$response = $this->clienteGuzzle->post('', [
				'form_params' => $form_params
			]);

			$responsePhp = rest_decode(guarani_rest::get_response_json($response));

			list($responseOk, $messageMoodle) = static::validarResponse($responsePhp);

			// Si la respuesta esta OK devuelvo la misma
			if ($responseOk)
				$cursos = $responsePhp;
		} catch (Exception $e) {

			toba::logger()->error($e->getMessage());
		}

		return $cursos;
	}

	/**
	 * Servicio REST a Moodle "core_course_get_courses_by_field":
	 * Obtiene datos de un curso de Moodle.
	 * @see https://docs.moodle.org/dev/Web_service_API_functions API Moodle.
	 * @param int $curso Curso de Moodle.
	 * @return array|null|int Datos de dicho curso, null si el curso no existe o -1 si falla la comunicación con Moodle.
	 */
	public function getInfoCurso($curso)
	{

		$info_curso = -1;

		try {

			$response = $this->clienteGuzzle->post('', [
				'form_params' => [
					'wstoken' => $this->token,
					'moodlewsrestformat' => static::FORMATO_RESPUESTA_JSON,
					'wsfunction' => 'core_course_get_courses_by_field',
					'field' => 'id',
					'value' => $curso
				]
			]);

			$responsePhp = rest_decode(guarani_rest::get_response_json($response));

			list($responseOk, $messageMoodle) = static::validarResponse($responsePhp);

			// Si la respuesta esta OK
			if ($responseOk) {

				// Si existe curso con el id proporcionado
				if (!empty($responsePhp['courses'])) {
					$info_curso = $responsePhp['courses'][0];
				} else { // Si el curso no existe en Moodle
					$info_curso = null;
				}
			}
		} catch (Exception $e) {

			toba::logger()->error($e->getMessage());
		}

		return $info_curso;
	}

	/**
	 * Servicio REST a Moodle "core_enrol_get_enrolled_users":
	 * Obtiene usuarios matriculados en un curso de Moodle.
	 * @see https://docs.moodle.org/dev/Web_service_API_functions API Moodle.
	 * @param int $curso Curso de Moodle.
	 * @return array Usuarios matriculados a dicho curso.
	 */
	public function getUsuariosByCurso($curso)
	{

		$usuarios = [];

		try {

			$response = $this->clienteGuzzle->post('', [
				'form_params' => [
					'wstoken' => $this->token,
					'moodlewsrestformat' => static::FORMATO_RESPUESTA_JSON,
					'wsfunction' => 'core_enrol_get_enrolled_users',
					'courseid' => $curso
				]
			]);

			$responsePhp = rest_decode(guarani_rest::get_response_json($response));

			list($responseOk, $messageMoodle) = static::validarResponse($responsePhp);

			// Si la respuesta esta OK
			if ($responseOk) {

				$usuarios = $responsePhp;
			}
		} catch (Exception $e) {

			toba::logger()->error($e->getMessage());
		}

		return $usuarios;
	}

	function getAlumnosByCurso($curso, $rolAlumno, $rolCurso)
	{
		$usuarios = $this->getUsuariosByCurso($curso);
		$alumnos = [];

		// Recorro todos los usuarios matriculados en el curso
		foreach ($usuarios as $key => $usuario) {

			// Obtengo los roles del usuario
			$roles = array_column($usuario['roles'], 'roleid');

			// Si entre los roles esta el rol de "alumno" o "curso" es un alumno propiamente dicho
			if (in_array($rolAlumno, $roles) || in_array($rolCurso, $roles)) {
				$alumnos[] = $usuario;
			}
		}

		return $alumnos;
	}

	function getDocentesByCurso($curso, $rolesDocentes)
	{
		$usuarios = $this->getUsuariosByCurso($curso);
		$docentes = [];

		// Recorro todos los usuarios matriculados en el curso
		foreach ($usuarios as $key => $usuario) {

			// Obtengo los roles del usuario
			$roles = array_column($usuario['roles'], 'roleid');

			$interseccion = array_intersect($rolesDocentes, $roles);

			// Si es docente lo devuelvo
			if (!empty($interseccion)) {
				$docentes[] = $usuario;
			}
		}

		return $docentes;
	}

	/**
	 * Servicio REST a Moodle "core_enrol_get_users_courses":
	 * Obtiene cursos de un usuario matriculado en Moodle.
	 * @see https://docs.moodle.org/dev/Web_service_API_functions API Moodle.
	 * @param int $usuario Usuario de Moodle.
	 * @return array Cursos de dicho usuario.
	 */
	public function getCursosByUsuario($usuario)
	{

		$cursos = [];

		try {

			$response = $this->clienteGuzzle->post('', [
				'form_params' => [
					'wstoken' => $this->token,
					'moodlewsrestformat' => static::FORMATO_RESPUESTA_JSON,
					'wsfunction' => 'core_enrol_get_users_courses',
					'userid' => $usuario
				]
			]);

			$responsePhp = rest_decode(guarani_rest::get_response_json($response));

			list($responseOk, $messageMoodle) = static::validarResponse($responsePhp);

			// Si la respuesta esta OK
			if ($responseOk) {

				$cursos = $responsePhp;
			}
		} catch (Exception $e) {

			toba::logger()->error($e->getMessage());
		}

		return $cursos;
	}

	function getCursosByUsuarios($usuarios)
	{

		$cursos = [];

		foreach ($usuarios as $key => $usuario) {

			// Por cada usuario traigo los cursos de Moodle en los que se encuentra matriculado
			$cursosMoodle = $this->getCursosByUsuario($usuario);
			$cursosIds = array_column($cursos, 'id');

			foreach ($cursosMoodle as $key => $cursoMoodle) {
				// Me fijo que los cursos no se repitan
				if (!in_array($cursoMoodle['id'], $cursosIds)) {
					$cursos[] = $cursoMoodle;
				}
			}
		}

		return $cursos;
	}

	/**
	 * Servicio REST a Moodle "core_user_create_users":
	 * Crea usuarios en Moodle.
	 * @see https://docs.moodle.org/dev/Web_service_API_functions API Moodle.
	 * @param array $usuarios Usuarios a crear.
	 * Consta del siguiente formato:
	 * <code>
	 *  Array 
	 *   (
	 *  [0] =>
	 *       Array 
	 *           (
	 *           [username] => "ajose"           
	 *           [firstname] => "Jose"                
	 *           [lastname] => "Arias"                
	 *           [email] => "ajose@gmail.com"
	 *           [idnumber] => "1"
	 *           )
	 *  [1] =>
	 *       Array 
	 *           (
	 *           [username] => "ahernan"               
	 *           [firstname] => "Hernan"                
	 *           [lastname] => "Arias"                
	 *           [email] => "ahernan@gmail.com"
	 *           [idnumber] => "2"
	 *           )
	 *   )
	 * </code>
	 * @param boolean $excepciones Si lanza excepciones o no.
	 * @throws Exception Si fallan los Web Services de Moodle.
	 * @return array Usuarios creados con el ID de Moodle
	 * <code>
	 *  Array 
	 *   (
	 *  [0] =>
	 *       Array 
	 *           (
	 *           [id] => 30
	 *           [username] => "ajose"                
	 *           )
	 *  [1] =>
	 *       Array 
	 *           (
	 *           [id] => 31
	 *           [username] => "ahernan" 
	 *           )
	 *   )
	 * </code>
	 */
	public function crearUsuarios($usuarios, $excepciones = false)
	{

		array_walk($usuarios, function (&$usuario) {
			$usuario['createpassword'] = 1;
			$usuario['auth'] = static::AUTH_MANUAL;
			$usuario['lang'] = static::LANG_ES;
			$usuario['timezone'] = static::TIMEZONE_BSAS;
			$usuario['country'] = static::COUNTRY_AR;
			$usuario['preferences'] = [
				['type' => 'auth_forcepasswordchange', 'value' => '0']
			];
		});

		toba::logger()->debug("Usuarios a crear en Moodle:");
		toba::logger()->var_dump($usuarios);

		$usuariosMoodle = [];

		try {

			$response = $this->clienteGuzzle->post('', [
				'form_params' => [
					'wstoken' => $this->token,
					'moodlewsrestformat' => static::FORMATO_RESPUESTA_JSON,
					'wsfunction' => 'core_user_create_users',
					'users' => array_a_utf8($usuarios)
				]
			]);

			$responsePhp = rest_decode(guarani_rest::get_response_json($response));



			list($responseOk, $messageMoodle) = static::validarResponse($responsePhp);

			// Si la respuesta esta OK
			if ($responseOk) {

				$usuariosMoodle = $responsePhp;
			}
		} catch (Exception $e) {
			$responseOk = '';
			$messageMoodle = '';
			toba::logger()->error($e->getMessage());
		}

		if ($excepciones && !$responseOk) {
			throw new Exception($messageMoodle);
		}

		return $usuariosMoodle;
	}

	/**
	 * Servicio REST a Moodle "core_user_update_users":
	 * Modifica usuarios en Moodle.
	 * @see https://docs.moodle.org/dev/Web_service_API_functions API Moodle.
	 * @param array $usuarios Usuarios a modificar.
	 * Consta del siguiente formato:
	 * <code>
	 *  Array 
	 *   (
	 *  [0] =>
	 *       Array 
	 *           (
	 * 			 [id] => 14 
	 *           [username] => "ajose"           
	 *           [firstname] => "Jose"                
	 *           [lastname] => "Arias"                
	 *           [email] => "ajose@gmail.com"
	 *           [idnumber] => "1"
	 *           )
	 *  [1] =>
	 *       Array 
	 *           (
	 * 			 [id] => 15 
	 *           [username] => "ahernan"               
	 *           [firstname] => "Hernan"                
	 *           [lastname] => "Arias"                
	 *           [email] => "ahernan@gmail.com"
	 *           [idnumber] => "2"
	 *           )
	 *   )
	 * </code>
	 * @return boolean TRUE si sale todo bien, FALSE caso contrario.
	 */
	public function modificarUsuarios($usuarios)
	{

		toba::logger()->debug("Usuarios a modificar en Moodle:");
		toba::logger()->var_dump($usuarios);

		$modificacionOk = false;

		try {

			$response = $this->clienteGuzzle->post('', [
				'form_params' => [
					'wstoken' => $this->token,
					'moodlewsrestformat' => static::FORMATO_RESPUESTA_JSON,
					'wsfunction' => 'core_user_update_users',
					'users' => array_a_utf8($usuarios)
				]
			]);

			$responsePhp = rest_decode(guarani_rest::get_response_json($response));

			list($responseOk, $messageMoodle) = static::validarResponse($responsePhp);

			// Si la respuesta esta OK
			if ($responseOk) {

				$modificacionOk = true;
			}
		} catch (Exception $e) {

			toba::logger()->error($e->getMessage());
		}

		return $modificacionOk;
	}

	/**
	 * Servicio REST a Moodle "enrol_manual_enrol_users":
	 * Matricula usuarios en Moodle de manera manual.
	 * @see https://docs.moodle.org/dev/Web_service_API_functions API Moodle.
	 * @param array $matriculaciones Usuarios a matricular.
	 * Consta del siguiente formato:
	 * <code>
	 *  Array 
	 *   (
	 *  [0] =>
	 *       Array 
	 *           (
	 *           [roleid] => 5              
	 *           [userid] => 30                
	 *           [courseid] => 9
	 *           )
	 *  [1] =>
	 *       Array 
	 *           (
	 *           [roleid] => 5              
	 *           [userid] => 31                
	 *           [courseid] => 9
	 *           )
	 *   )
	 * </code>
	 * @param boolean $excepciones Si lanza excepciones o no.
	 * @throws Exception Si fallan los Web Services de Moodle.
	 * @return boolean TRUE si sale todo bien, FALSE caso contrario.
	 */
	public function matricularUsuarios($matriculaciones, $excepciones = false)
	{

		toba::logger()->debug("Usuarios a matricular en Moodle:");
		toba::logger()->var_dump($matriculaciones);

		$matriculacionOk = false;
		$responseOk = false;
		$messageMoodle = null;
		try {

			$response = $this->clienteGuzzle->post('', [
				'form_params' => [
					'wstoken' => $this->token,
					'moodlewsrestformat' => static::FORMATO_RESPUESTA_JSON,
					'wsfunction' => 'enrol_manual_enrol_users',
					'enrolments' => $matriculaciones
				]
			]);

			$responsePhp = rest_decode(guarani_rest::get_response_json($response));

			list($responseOk, $messageMoodle) = static::validarResponse($responsePhp);

			// Si la respuesta esta OK
			if ($responseOk) {

				$matriculacionOk = true;
			}
		} catch (Exception $e) {

			toba::logger()->error($e->getMessage());
		}
		/*
		  if ($excepciones && !$responseOk) {
		  throw new Exception($messageMoodle);
		  }
		 */
		return $matriculacionOk;
	}

	/**
	 * Servicio REST a Moodle "core_group_add_group_members":
	 * Matricula usuarios en grupos de cursos Moodle de manera manual.
	 * @see https://docs.moodle.org/dev/Web_service_API_functions API Moodle.
	 * @param array $matriculaciones_grupos Usuarios a matricular.
	 * Consta del siguiente formato:
	 * <code>
	 *  Array 
	 *   (
	 *  [0] =>
	 *       Array 
	 *           (
	 *           [groupid] => 9    
	 *    		 [userid] => 30     
	 *           )
	 *  [1] =>
	 *       Array 
	 *           (
	 *           [groupid] => 9    
	 *    		 [userid] => 35
	 *           )
	 *   )
	 * </code>
	 * @param boolean $excepciones Si lanza excepciones o no.
	 * @throws Exception Si fallan los Web Services de Moodle.
	 * @return boolean TRUE si sale todo bien, FALSE caso contrario.
	 */
	public function matricularGrupoUsuarios($matriculaciones_grupos, $excepciones = false)
	{

		toba::logger()->debug("Usuarios a matricular grupo de Moodle:");
		toba::logger()->var_dump($matriculaciones_grupos);

		$matriculacionOk = false;
		$responseOk = false;
		$messageMoodle = null;
		try {

			$response = $this->clienteGuzzle->post('', [
				'form_params' => [
					'wstoken' => $this->token,
					'moodlewsrestformat' => static::FORMATO_RESPUESTA_JSON,
					'wsfunction' => 'core_group_add_group_members',
					'members' => $matriculaciones_grupos
				]
			]);

			toba::logger()->debug("response:");
			toba::logger()->var_dump($response);

			$responsePhp = rest_decode(guarani_rest::get_response_json($response));

			toba::logger()->debug("responsePhp:");
			toba::logger()->var_dump($responsePhp);

			list($responseOk, $messageMoodle) = static::validarResponse($responsePhp);

			toba::logger()->debug("response ok de matricular usuario en grupo:");
			toba::logger()->var_dump($responseOk);

			// Si la respuesta esta OK
			if ($responseOk) {

				$matriculacionOk = true;
			}
		} catch (Exception $e) {

			toba::logger()->error($e->getMessage());
		}
		/*
		  if ($excepciones && !$responseOk) {
		  throw new Exception($messageMoodle);
		  }
		 */
		return $matriculacionOk;
	}

	/**
	 * Servicio REST a Moodle "core_group_delete_group_members":
	 * Desmatricula usuarios en grupos de cursos Moodle de manera manual.
	 * @see https://docs.moodle.org/dev/Web_service_API_functions API Moodle.
	 * @param array $matriculaciones_grupos Usuarios a desmatricular.
	 * Consta del siguiente formato:
	 * <code>
	 *  Array 
	 *   (
	 *  [0] =>
	 *       Array 
	 *           (
	 *           [groupid] => 9    
	 *    		 [userid] => 30     
	 *           )
	 *  [1] =>
	 *       Array 
	 *           (
	 *           [groupid] => 9    
	 *    		 [userid] => 35
	 *           )
	 *   )
	 * </code>
	 * @param boolean $excepciones Si lanza excepciones o no.
	 * @throws Exception Si fallan los Web Services de Moodle.
	 * @return boolean TRUE si sale todo bien, FALSE caso contrario.
	 */
	public function desmatricularGrupoUsuarios($matriculaciones_grupos, $excepciones = false)
	{

		toba::logger()->debug("Usuarios a desmatricular grupo de Moodle:");
		toba::logger()->var_dump($matriculaciones_grupos);

		$desmatriculacionOk = false;
		$responseOk = false;
		$messageMoodle = null;
		try {

			$response = $this->clienteGuzzle->post('', [
				'form_params' => [
					'wstoken' => $this->token,
					'moodlewsrestformat' => static::FORMATO_RESPUESTA_JSON,
					'wsfunction' => 'core_group_delete_group_members',
					'members' => $matriculaciones_grupos
				]
			]);

			toba::logger()->debug("response:");
			toba::logger()->var_dump($response);

			$responsePhp = rest_decode(guarani_rest::get_response_json($response));

			toba::logger()->debug("responsePhp:");
			toba::logger()->var_dump($responsePhp);

			list($responseOk, $messageMoodle) = static::validarResponse($responsePhp);

			toba::logger()->debug("response ok de desmatricular usuario en grupo:");
			toba::logger()->var_dump($responseOk);

			// Si la respuesta esta OK
			if ($responseOk) {

				$desmatriculacionOk = true;
			}
		} catch (Exception $e) {

			toba::logger()->error($e->getMessage());
		}
		/*
		  if ($excepciones && !$responseOk) {
		  throw new Exception($messageMoodle);
		  }
		 */
		return $desmatriculacionOk;
	}

	/**
	 * Servicio REST a Moodle "enrol_manual_unenrol_users":
	 * Desmatricula usuarios en Moodle de manera manual.
	 * @see https://docs.moodle.org/dev/Web_service_API_functions API Moodle.
	 * @param array $desmatriculaciones Usuarios a desmatricular.
	 * Consta del siguiente formato:
	 * <code>
	 *  Array 
	 *   (
	 *  [0] =>
	 *       Array 
	 *           (
	 *           [roleid] => 5              
	 *           [userid] => 30                
	 *           [courseid] => 9
	 *           )
	 *  [1] =>
	 *       Array 
	 *           (
	 *           [roleid] => 5              
	 *           [userid] => 31                
	 *           [courseid] => 9
	 *           )
	 *   )
	 * </code>
	 * @param boolean $excepciones Si lanza excepciones o no.
	 * @throws Exception Si fallan los Web Services de Moodle.
	 * @return boolean TRUE si sale todo bien, FALSE caso contrario.
	 */
	public function desmatricularUsuarios($desmatriculaciones, $excepciones = false)
	{

		toba::logger()->debug("Usuarios a desmatricular en Moodle:");
		toba::logger()->var_dump($desmatriculaciones);

		$desmatriculacionOk = false;

		try {

			$response = $this->clienteGuzzle->post('', [
				'form_params' => [
					'wstoken' => $this->token,
					'moodlewsrestformat' => static::FORMATO_RESPUESTA_JSON,
					'wsfunction' => 'enrol_manual_unenrol_users',
					'enrolments' => $desmatriculaciones
				]
			]);

			$responsePhp = rest_decode(guarani_rest::get_response_json($response));

			list($responseOk, $messageMoodle) = static::validarResponse($responsePhp);

			// Si la respuesta esta OK
			if ($responseOk) {

				$desmatriculacionOk = true;
			}
		} catch (Exception $e) {

			toba::logger()->error($e->getMessage());
		}

		if ($excepciones && !$responseOk) {
			throw new Exception($messageMoodle);
		}

		return $desmatriculacionOk;
	}

	/**
	 * Servicio REST a Moodle "auth_userkey_request_login_url":
	 * Loguea a un usuario en Moodle usando un TOKEN.
	 * @see https://docs.moodle.org/dev/Web_service_API_functions API Moodle.
	 * @see https://moodle.org/plugins/auth_userkey Plugin.
	 * @see https://github.com/catalyst/moodle-auth_userkey Readme.
	 * @param int $idnumber Es el ID de persona de Guaraní.
	 * @return string URL con TOKEN para el logueo
	 */
	public function loguearUsuario($idnumber)
	{

		toba::logger()->debug("idnumber: {$idnumber}");

		$loginUrl = null;

		try {

			$response = $this->clienteGuzzle->post('', [
				'form_params' => [
					'wstoken' => $this->token,
					'moodlewsrestformat' => static::FORMATO_RESPUESTA_JSON,
					'wsfunction' => 'auth_userkey_request_login_url',
					'user' => ['idnumber' => $idnumber]
				]
			]);

			$responsePhp = rest_decode(guarani_rest::get_response_json($response));

			list($responseOk, $messageMoodle) = static::validarResponse($responsePhp);

			// Si la respuesta esta OK
			if ($responseOk) {

				$loginUrl = $responsePhp['loginurl'];
			}
		} catch (Exception $e) {

			toba::logger()->error($e->getMessage());
		}

		return $loginUrl;
	}

	/**
	 * Servicio REST a Moodle "core_course_create_courses":
	 * Crea cursos en Moodle.
	 * @see https://docs.moodle.org/dev/Web_service_API_functions API Moodle.
	 * @param array $cursos Cursos a crear.
	 * Consta del siguiente formato:
	 * <code>
	 *  Array 
	 *   (
	 *  [0] =>
	 *       Array 
	 *           (
	 *           [fullname] => "Curso Tejido"              
	 *           [shortname] => "Tejido"            
	 *           [categoryid] => 1                
	 *           [summary] => "<b>Descripción del curso</b>"
	 *           [visible] => [0|1]
	 *           [startdate] => (timestamp) "1256953732"
	 *           [enddate] => (timestamp) "1256953732"
	 *           )
	 *  [1] =>
	 *       Array 
	 *           (
	 *           [fullname] => "Curso Chochet"              
	 *           [shortname] => "Chochet"            
	 *           [categoryid] => 3                
	 *           [summary] => "<b>Descripción del curso</b>"
	 *           [visible] => [0|1]
	 *           [startdate] => (timestamp) "1256953732"
	 *           [enddate] => (timestamp) "1256953732"
	 *           )
	 *   )
	 * </code>
	 * @param boolean $excepciones Si lanza excepciones o no.
	 * @throws Exception Si fallan los Web Services de Moodle.
	 * @return array Cursos creados con el ID de Moodle
	 * <code>
	 *  Array 
	 *   (
	 *  [0] =>
	 *       Array 
	 *           (
	 *           [id] => 4
	 *           [shortname] => "Tejido"                
	 *           )
	 *  [1] =>
	 *       Array 
	 *           (
	 *           [id] => 5
	 *           [shortname] => "Chochet" 
	 *           )
	 *   )
	 * </code>
	 */
	public function crearCursos($cursos, $excepciones = false)
	{

		toba::logger()->debug("Cursos a crear en Moodle:");
		toba::logger()->var_dump($cursos);

		$cursosMoodle = [];

		try {

			$response = $this->clienteGuzzle->post('', [
				'form_params' => [
					'wstoken' => $this->token,
					'moodlewsrestformat' => static::FORMATO_RESPUESTA_JSON,
					'wsfunction' => 'core_course_create_courses',
					'courses' => array_a_utf8($cursos)
				]
			]);

			$responsePhp = rest_decode(guarani_rest::get_response_json($response));

			list($responseOk, $messageMoodle) = static::validarResponse($responsePhp);

			// Si la respuesta esta OK
			if ($responseOk) {

				$cursosMoodle = $responsePhp;
			}
		} catch (Exception $e) {

			toba::logger()->error($e->getMessage());
		}

		if ($excepciones && !$responseOk) {
			throw new Exception($messageMoodle);
		}

		return $cursosMoodle;
	}

	public function crearGrupos($grupos, $excepciones = false)
	{

		toba::logger()->debug("Grupos a crear en Moodle:");
		toba::logger()->var_dump($grupos);

		$gruposMoodle = [];

		try {

			$response = $this->clienteGuzzle->post('', [
				'form_params' => [
					'wstoken' => $this->token,
					'moodlewsrestformat' => static::FORMATO_RESPUESTA_JSON,
					'wsfunction' => 'core_group_create_groups',
					'groups' => array_a_utf8($grupos)
				]
			]);

			$responsePhp = rest_decode(guarani_rest::get_response_json($response));

			list($responseOk, $messageMoodle) = static::validarResponse($responsePhp);

			// Si la respuesta esta OK
			if ($responseOk) {

				$gruposMoodle = $responsePhp;
				toba::logger()->debug("Datos Grupos si entra en response ok:");
				toba::logger()->var_dump($gruposMoodle);
			}
		} catch (Exception $e) {

			toba::logger()->error($e->getMessage());
		}

		if ($excepciones && !$responseOk) {
			throw new Exception($messageMoodle);
		}

		return $gruposMoodle;
	}

	public function borrarGrupos($grupos, $excepciones = false)
	{

		$gruposMoodle = '';

		try {

			$response = $this->clienteGuzzle->post('', [
				'form_params' => [
					'wstoken' => $this->token,
					'moodlewsrestformat' => static::FORMATO_RESPUESTA_JSON,
					'wsfunction' => 'core_group_delete_groups',
					'groupids' => array_a_utf8($grupos)
				]
			]);

			$responsePhp = rest_decode(guarani_rest::get_response_json($response));

			list($responseOk, $messageMoodle) = static::validarResponse($responsePhp);

			// Si la respuesta esta OK
			if ($responseOk) {
				$gruposMoodle = 'exitoso';
			}
		} catch (Exception $e) {

			toba::logger()->error($e->getMessage());
		}

		if ($excepciones && !$responseOk) {
			throw new Exception($messageMoodle);
		}

		return $gruposMoodle;
	}

	function crearAsociarCursoComision($plataforma, $comision, $fullname, $shortname, $categoryid, $summary, $visible, $startdate = null, $enddate = null, $excepciones = false, $interactivo = true)
	{
		/* 	
		 * $interfaz indica si es por la operacion masiva.
		 * como es masiva no tiene interaccion con el usuario por lo cual los cambios no se consultan (ej nombrecorto en el caso que este repetido)	
		 * Nombre completo = 254 caracteres (no hay problema. Moodle permite nombre largo duplicado)
		 * Nombre corto = 100 caracteres
		 */
		toba::db()->abrir_transaccion();

		$cursos = [];

		try {

			if ($interactivo == false) {
				$shortname = substr($shortname, 0, static::NOMBRE_CORTO_CURSO_MAXLENGTH);
				$fullname = substr($fullname, 0, static::NOMBRE_COMPLETO_CURSO_MAXLENGTH);
			}

			$cursos[0]['fullname'] = $fullname;
			$cursos[0]['shortname'] = $shortname;
			$cursos[0]['categoryid'] = $categoryid;
			$cursos[0]['summary'] = $summary;
			$cursos[0]['visible'] = $visible;

			// Si ingresó "Fecha Inicio"
			if (!is_null($startdate)) {
				$cursos[0]['startdate'] = $startdate;
			}

			// Si ingresó "Fecha Fin"
			if (!is_null($enddate)) {
				$cursos[0]['enddate'] = $enddate;
			}

			try {
				$cursos_moodle = $this->crearCursos($cursos, $excepciones);
			} catch (Exception $e) {

				if ((strpos($e->getMessage(), static::error_shortnametaken) !== false) && $interactivo == false) {


					$cursos[0]['shortname'] = substr($shortname, 0, 76) . ' ' . date("Y-m-d H:i:s.u");
					$cursos[0]['fullname'] = substr($fullname, 0, static::NOMBRE_COMPLETO_CURSO_MAXLENGTH);
					try {
						$cursos_moodle = $this->crearCursos($cursos, $excepciones);
					} catch (Exception $e) {

						throw new Exception($e->getMessage(), 1);
						//return [false, $e->getMessage()];
					}
				} else {

					throw new Exception($e->getMessage(), 1);
					//return [false, $e->getMessage()];
				}
			}

			// Si fallo la creación del curso en Moodle
			if (empty($cursos_moodle)) {
				return [false, ""];
			} else { // sino
				// Guardo el curso en la DB
				$curso_id = guarani::act('act_plataformas_virtuales')->insertar_curso_pv($plataforma, $cursos_moodle[0]['id']);
				// Lo asocio a la comisión
				if (!is_array($comision)) {

					guarani::act('act_plataformas_virtuales')->insertar_curso_comision_pv($curso_id, $comision);
				} else {
					foreach ($comision as $comision_agrupada) {
						guarani::act('act_plataformas_virtuales')->insertar_curso_comision_pv($curso_id, $comision_agrupada);
					}
				}
			}

			toba::db()->cerrar_transaccion();

			return [true, ""];
		} catch (Exception $e) {

			toba::db()->abortar_transaccion();
			toba::logger()->error($e->getMessage());
			return [false, $e->getCode() == 1 ? $e->getMessage() : ''];
		}
	}

	function CrearAsociarGrupoComision($idnumber, $description, $courseid, $name, $comision, $excepciones = false, $interactivo = true)
	{

		/* 	 	
		 * $interfaz indica si es por la operacion masiva.
		 * como es masiva no tiene interaccion con el usuario por lo cual los cambios no se consultan (ej nombrecorto en el caso que este repetido)	
		 * Nombre completo = 254 caracteres (no hay problema. Moodle permite nombre largo duplicado)
		 * Nombre corto = 100 caracteres
		 */
		toba::db()->abrir_transaccion();

		$grupos = [];

		try {

			if ($interactivo == false) {
				$name = substr($name, 0, static::NOMBRE_CORTO_CURSO_MAXLENGTH);
				$description = substr($description, 0, static::NOMBRE_COMPLETO_CURSO_MAXLENGTH);
			}

			$grupos[0]['courseid'] = $courseid;
			$grupos[0]['idnumber'] = $idnumber;
			$grupos[0]['name'] = $name;
			$grupos[0]['description'] = $description;
			//$grupos[0]['descriptionformat'] = 'FORMAT_HTML';

			try {
				$grupos_cursos_moodle = $this->crearGrupos($grupos, $excepciones);
				toba::logger()->debug("grupos_cursos_moodle");
				toba::logger()->var_dump($grupos_cursos_moodle);
			} catch (Exception $e) {
				throw new Exception($e->getMessage(), 1);
				//return [false, $e->getMessage()];
			}

			// Si falló la creación del curso en Moodle
			if (empty($grupos_cursos_moodle)) {
				return [false, ""];
			} else { // sino
				// Lo asocio a la comisión
				$grupo = $grupos_cursos_moodle[0]['id'];

				toba::logger()->debug("Grupo a guardar en base:");
				toba::logger()->var_dump($grupo);

				if (!is_array($comision)) {
					guarani::act('act_plataformas_virtuales')->insertar_grupo_curso_comision_pv($grupo, $comision);
				} else {
					foreach ($comision as $comision_agrupada) {
						guarani::act('act_plataformas_virtuales')->insertar_grupo_curso_comision_pv($grupo, $comision_agrupada);
					}
				}
			}

			toba::db()->cerrar_transaccion();

			return [true, ""];
		} catch (Exception $e) {

			toba::db()->abortar_transaccion();
			toba::logger()->error($e->getMessage());
			return [false, $e->getCode() == 1 ? $e->getMessage() : ''];
		}
	}

	function EliminarAsociarGrupoComision($id, $excepciones = false)
	{

		/* 	 	
		 * $interfaz indica si es por la operacion masiva.
		 * como es masiva no tiene interaccion con el usuario por lo cual los cambios no se consultan (ej nombrecorto en el caso que este repetido)	
		 * Nombre completo = 254 caracteres (no hay problema. Moodle permite nombre largo duplicado)
		 * Nombre corto = 100 caracteres
		 */
		toba::db()->abrir_transaccion();

		$grupos[] = ['id' => $id];
		try {

			try {
				$borrar_grupos_cursos_moodle = $this->borrarGrupos($grupos[0], $excepciones);
				toba::logger()->debug("borrar_grupos_cursos_moodle");
				toba::logger()->var_dump($borrar_grupos_cursos_moodle);
			} catch (Exception $e) {
				throw new Exception($e->getMessage(), 1);
			}
			toba::db()->cerrar_transaccion();
			return [true, ""];
		} catch (Exception $e) {
			toba::db()->abortar_transaccion();
			toba::logger()->error($e->getMessage());
			return [false, $e->getCode() == 1 ? $e->getMessage() : ''];
		}
	}

	function crearAsociarCursoLlamadoMesa($plataforma, $llamado_mesa, $fullname, $shortname, $categoryid, $summary, $visible, $startdate = null, $enddate = null, $excepciones = false, $interactivo = true)
	{
		toba::db()->abrir_transaccion();
		$cursos = [];
		try {
			$cursos[0]['fullname'] = $fullname;
			$cursos[0]['shortname'] = $shortname;
			$cursos[0]['categoryid'] = $categoryid;
			$cursos[0]['summary'] = $summary;
			$cursos[0]['visible'] = $visible;
			if (!is_null($startdate)) {
				$cursos[0]['startdate'] = $startdate;
			}
			if (!is_null($enddate)) {
				$cursos[0]['enddate'] = $enddate;
			}

			try {
				$cursos_moodle = $this->crearCursos($cursos, $excepciones);
			} catch (Exception $e) {


				if ((strpos($e->getMessage(), static::error_shortnametaken) !== false) && $interactivo == false) {
				$now = DateTime::createFromFormat('U.u', microtime(true));
				

					$cursos[0]['shortname'] = substr($shortname, 0, 72) . ' ' .$now->format("m-d-Y H:i:s.u");
					$cursos[0]['fullname'] = substr($fullname, 0, static::NOMBRE_COMPLETO_CURSO_MAXLENGTH);
					try {
						$cursos_moodle = $this->crearCursos($cursos, $excepciones);
					} catch (Exception $e) {
						throw new Exception($e->getMessage(), 1);
						//return [false, $e->getMessage()];
					}
				} else {
					//return [false, $e->getMessage()];
					throw new Exception($e->getMessage(), 1);
				}
			}
			if (empty($cursos_moodle)) {
				return [false, ''];
			} else {
				$curso_id = guarani::act('act_plataformas_virtuales')->insertar_curso_pv($plataforma, $cursos_moodle[0]['id']);
				if (!is_array($llamado_mesa)) {
					guarani::act('act_plataformas_virtuales')->insertar_curso_llamado_mesa_pv($curso_id, $llamado_mesa);
				} else {
					foreach ($llamado_mesa as $llamado_mesa_agrupado) {
						guarani::act('act_plataformas_virtuales')->insertar_curso_llamado_mesa_pv($curso_id, $llamado_mesa_agrupado);
					}
				}
			}
			toba::db()->cerrar_transaccion();
			return [true, ''];
		} catch (Exception $e) {
			toba::db()->abortar_transaccion();
			toba::logger()->error($e->getMessage());
			return [false, $e->getCode() == 1 ? $e->getMessage() : ''];
		}
	}

	/**
	 * Servicio REST a Moodle "core_course_import_course":
	 * Importa el contenido de un curso de Moodle a otro.
	 * @see https://docs.moodle.org/dev/Web_service_API_functions API Moodle.
	 * @param int $cursoDesde Curso de Moodle desde el cual se importará.
	 * @param int $cursoHacia Curso de Moodle hacia el cual se importará.
	 * @param boolean $excepciones Si lanza excepciones o no.
	 * @throws Exception Si fallan los Web Services de Moodle.
	 * @return bool Si la importación fue exitosa o no.
	 */
	public function importarContenidoCurso($cursoDesde, $cursoHacia, $excepciones = false)
	{

		$importacionOk = false;

		try {

			$response = $this->clienteGuzzle->post('', [
				'form_params' => [
					'wstoken' => $this->token,
					'moodlewsrestformat' => static::FORMATO_RESPUESTA_JSON,
					'wsfunction' => 'core_course_import_course',
					'importfrom' => $cursoDesde,
					'importto' => $cursoHacia
				]
			]);

			$responsePhp = rest_decode(guarani_rest::get_response_json($response));

			list($importacionOk, $messageMoodle) = static::validarResponse($responsePhp);
		} catch (Exception $e) {

			toba::logger()->error($e->getMessage());
		}

		if ($excepciones && !$importacionOk) {
			throw new Exception($messageMoodle);
		}

		return $importacionOk;
	}

	/**
	 * Servicio REST a Moodle "core_user_get_users":
	 * Obtiene información de usuarios de Moodle dado un criterio de busqueda.
	 * @see https://docs.moodle.org/dev/Web_service_API_functions API Moodle.
	 * @param array $criteria Se puede buscar por los campos ['id'|'lastname'|'firstname'|'idnumber'|'username'|'email'|'auth'].
	 * <code>
	 *  [
	 *     ["key" => "lastname", "value" => "Pérez"],
	 *     ["key" => "firstname", "value" => "José"],
	 *     ["key" => "auth", "value" => "manual"],
	 *  ]
	 * </code>
	 * @return array|int Arreglo de usuarios o -1 si falla la comunicación con Moodle.
	 */
	public function getUsuarios($criteria)
	{

		$usuarios = -1;

		try {

			$response = $this->clienteGuzzle->post('', [
				'form_params' => [
					'wstoken' => $this->token,
					'moodlewsrestformat' => static::FORMATO_RESPUESTA_JSON,
					'wsfunction' => 'core_user_get_users',
					'criteria' => $criteria,
				]
			]);

			$responsePhp = rest_decode(guarani_rest::get_response_json($response));

			list($responseOk, $messageMoodle) = static::validarResponse($responsePhp);

			// Si la respuesta esta OK
			if ($responseOk) {

				$usuarios = $responsePhp['users'];
			}
		} catch (Exception $e) {

			toba::logger()->error($e->getMessage());
		}

		return $usuarios;
	}

	/**
	 * Servicio REST a Moodle "core_user_get_users_by_field":
	 * Obtiene información de usuarios de Moodle dado un campo en especifico.
	 * @see https://docs.moodle.org/dev/Web_service_API_functions API Moodle.
	 * @param string $campo Se puede buscar por los campos: 'id', 'idnumber', 'username' o 'email'.
	 * @param array $valores Array de valores a filtrar, por ejemplo un array de ids:
	 * <code>
	 *  [5, 10, 35, 82]
	 * </code>
	 * @return array|int Arreglo de usuarios o -1 si falla la comunicación con Moodle.
	 */
	/* public function getUsuariosPorCampo($campo, $valores)
	  {

	  $usuarios = -1;

	  try {

	  $response = $this->clienteGuzzle->post('', [
	  'form_params' => [
	  'wstoken' => $this->token,
	  'moodlewsrestformat' => static::FORMATO_RESPUESTA_JSON,
	  'wsfunction' => 'core_user_get_users_by_field',
	  'field' => $campo,
	  'values' => $valores,
	  ]
	  ]);

	  $responsePhp = rest_decode(guarani_rest::get_response_json($response));

	  list($responseOk, $messageMoodle) = static::validarResponse($responsePhp);

	  // Si la respuesta esta OK
	  if ($responseOk) {

	  $usuarios = $responsePhp;

	  }

	  } catch (Exception $e) {

	  toba::logger()->error($e->getMessage());

	  }

	  return $usuarios;
	  } */
	/**
	 * Obtiene la URL de un curso de Moodle:
	 * @see https://moodle.org/plugins/auth_userkey Plugin.
	 * @see https://github.com/catalyst/moodle-auth_userkey Readme.
	 * @param int $idnumber Es el ID de persona de Guaraní.
	 * @param int $curso Curso de Moodle.
	 * @return string URL del Curso de Moodle.
	 */
	public function getUrlCurso($idnumber, $curso)
	{
		$loginUrl = $this->loguearUsuario($idnumber);
		$urlCurso = $this->host . static::SEGMENTO_URL_CURSO . $curso;

		// Si el usuario existe en Moodle
		if (!is_null($loginUrl)) {
			$urlCurso = $loginUrl . '&wantsurl=' . urlencode($urlCurso);
		}

		return $urlCurso;
	}

	/**
	 * Genera la URL de un curso de Moodle:
	 * @see https://moodle.org/plugins/auth_userkey Plugin.
	 * @see https://github.com/catalyst/moodle-auth_userkey Readme.
	 * @param string $host Host de Moodle, ej: http://uba.edu.ar/moodle.
	 * @param string $url Url de Moodle, ej: webservice/rest/server.php.
	 * @param string $token Token de Moodle, ej: e20c039445674046702510d0fd882ea4.
	 * @param int $idnumber Es el ID de persona de Guaraní.
	 * @param int $curso Curso de Moodle.
	 * @return string URL del Curso de Moodle.
	 */
	public static function generarUrlCurso($host, $url, $token, $idnumber, $curso)
	{
		$moodle = new moodle($host, $url, $token);
		return $moodle->getUrlCurso($idnumber, $curso);
	}

	public static function coincidenAlumnosMoodle($alumnosComision, $bajasComision, $alumnosCursoVirtualGuarani, $alumnosCursoVirtualMoodle, $esDesvinculacion = false)
	{

		$coinciden = false;
		$alumnosParaCrear = [];
		$alumnosParaMatricular = [];
		$alumnosParaDesmatricular = [];

		// Si se trata de una desvinculación de Comisión Guaraní - Curso Moodle
		if ($esDesvinculacion) {
			// Tomo como bajas a todos los alumnos de la comisión
			$bajasComision = array_merge($alumnosComision, $bajasComision);
			// Vacio los alumnos de la comisión para que no los vuelva a crear/matricular en Moodle
			$alumnosComision = [];
		}

		// Obtengo los usuarios a crear en Moodle (hay que crearlos y matricularlos)
		$alumnosParaCrear = array_filter($alumnosComision, function($alumno) {
			return is_null($alumno['usuario_moodle']);
		});

		// Alumnos vinculados al curso del lado de Guaraní
		$alumnosCursoGuaraniIds = array_column($alumnosCursoVirtualGuarani, "usuario_moodle");
		// Alumnos vinculados al curso del lado de Moodle
		$alumnosCursoMoodleIds = array_column($alumnosCursoVirtualMoodle, "id");

		// Obtengo los usuarios que solo hay que matricular en Moodle
		$alumnosParaMatricular = array_filter($alumnosComision, function($alumno) use ($alumnosCursoMoodleIds) {
			// Si el alumno ya fue creado en Moodle y no esta ya matriculado en ese curso de Moodle
			return ((!is_null($alumno['usuario_moodle'])) && (!in_array($alumno['usuario_moodle'], $alumnosCursoMoodleIds)));
		});

		// Me quedo con las bajas que ya tiene usuario en Moodle
		$bajasComision = array_filter($bajasComision, function($alumno) {
			return !is_null($alumno['usuario_moodle']);
		});

		// Obtengo el 'usuario_moodle' de los alumnos que fueron dados de baja de la comisión
		$alumnosBajasIds = array_unique(array_column($bajasComision, 'usuario_moodle'));

		// Obtengo los usuarios que hay que desmatricular en Moodle
		// Alumnos que fueron dados de baja y no están actualmente inscriptos en ninguna comisión asociada al curso de Moodle
		$alumnosParaDesmatricular = array_diff($alumnosBajasIds, $alumnosCursoGuaraniIds);

		// Me aseguro que los alumnos a desmatricular están efectivamente matriculados en Moodle
		$alumnosParaDesmatricular = array_intersect($alumnosParaDesmatricular, $alumnosCursoMoodleIds);

		// Si no hay usuarios para crear, matricular o desmatricular es que coinciden
		if (empty($alumnosParaCrear) && empty($alumnosParaMatricular) && empty($alumnosParaDesmatricular)) {
			$coinciden = true;
		}

		return [$coinciden, $alumnosParaCrear, $alumnosParaMatricular, $alumnosParaDesmatricular];
	}

	public static function coincidenDocentesMoodle($docentesComision, $docentesCursoVirtualGuarani, $docentesCursoVirtualMoodle)
	{
		$coinciden = false;
		$docentesParaCrear = [];
		$docentesParaMatricular = [];
		$docentesParaDesmatricular = [];

		// Obtengo los usuarios a crear en Moodle (hay que crearlos y matricularlos)
		$docentesParaCrear = array_filter($docentesComision, function($docente) {
			return (is_null($docente['usuario_moodle']) && !is_null($docente['rol_moodle']));
		});

		$roles_docente_moodle = [];
		foreach ($docentesCursoVirtualMoodle as $key => $docente_moodle) {
			$roles_docente_moodle[$docente_moodle['id']] = array_column($docente_moodle['roles'], 'roleid');
		}

		foreach ($docentesComision as $key => $docenteComision) {

			// Si el docente ya fue creado en Moodle y tiene rol
			if (!is_null($docenteComision['usuario_moodle']) && !is_null($docenteComision['rol_moodle'])) {

				$roles_docente = (isset($roles_docente_moodle[$docenteComision['usuario_moodle']])) ? $roles_docente_moodle[$docenteComision['usuario_moodle']] : [];

				// Si no esta ya matriculado en ese curso con ese rol
				if (!in_array($docenteComision['rol_moodle'], $roles_docente)) {
					$docentesParaMatricular[] = $docenteComision;
				}
			}
		}

		$docentesCursoGuaraniIds = array_column($docentesCursoVirtualGuarani, "usuario_moodle");
		$docentesCursoMoodleids = array_column($docentesCursoVirtualMoodle, "id");

		// Obtengo los usuarios que hay que desmatricular en Moodle
		$docentesParaDesmatricular = array_diff($docentesCursoMoodleids, $docentesCursoGuaraniIds);
		// solo desmatricula si son usuarios de guarani
		if (!empty($docentesParaDesmatricular)) {
			$personas_guarani_moodle = toba::consulta_php('co_configuracion')->get_persona_idMoodle($docentesParaDesmatricular);

			$personas_guarani_moodle = array_column($personas_guarani_moodle, "id_usuario_externo");
			$docentesParaDesmatricular = $personas_guarani_moodle;
		}
		// Si no hay usuarios para crear, matricular o desmatricular es que coinciden
		if (empty($docentesParaCrear) && empty($docentesParaMatricular) && empty($docentesParaDesmatricular)) {
			$coinciden = true;
		}

		return [$coinciden, $docentesParaCrear, $docentesParaMatricular, $docentesParaDesmatricular];
	}

}