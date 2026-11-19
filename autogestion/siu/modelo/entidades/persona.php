<?php
namespace siu\modelo\entidades;

use siu\guarani;
use siu\modelo\datos\catalogo;
use siu\modelo\entidades\encuestas_kolla;
use siu\errores\error_guarani;
use siu\errores\error_guarani_procesar_renglones;
use SIU\Chulupi\kernel;
use siu\errores\error_guarani_usuario;
use SIU\Chulupi\acceso\PersonaConDerechos;
use SIU\Chulupi\acceso\FactoryPerfil;
use siu\modelo\entidades\parametro;
use siu\modelo\_comun\comunes;
use siu\modelo\autenticacion\auth_saml;
use siu\modelo\transacciones\solicitar_certificacion;

class persona extends PersonaConDerechos
{
	// PERFILES
	const perfil_alumno = 'Alumno';
	const perfil_docente = 'Docente';
	const perfil_gerencial = 'Gerencial';
	const perfil_anonimo = 'anonimo';
	const perfil_cursos = 'Cursos';
	const perfil_administrador_3w = 'Administrador3w';
	const perfil_gestion = 'Gestion';
	const perfil_preinscripcion = 'Preinscripcion';

	const SESION_ID_PERSONA_PERFIL = '__persona_perfil';

	protected $persona;
	protected $datos_basicos = array();
	protected $datos_propuestas = array();
	protected $datos_perfiles = array();
	protected $datos_configuracion = array();
	protected $perfil;
	protected $id_perfil_activo;
	protected $id_propuesta_activa;
	protected $estado_cursadas;
	protected $estado_examenes;

	function __construct($persona)
	{
		$this->persona = $persona;
		kernel::sesion()->set('__persona', $this->persona);
		$parametros = array('persona' => $this->persona);
		$this->datos_basicos = catalogo::consultar('persona', 'datos_basicos', $parametros);
		$this->datos_perfiles = catalogo::consultar('persona', 'perfiles', $parametros);
		$this->datos_propuestas = catalogo::consultar('persona', 'carreras', $parametros);
		$this->cargar_configuracion();
		$this->set_perfil_activo($this->get_id_perfil_activo());
		if ($this->tiene_propuestas()) {
			$this->set_propuesta_activa($this->get_id_propuesta_activa());
		}
		kernel::log()->add_debug('cargar PERSONA', $this->persona);

		
	}
	
	static function cantidad_perfiles($parametros)
	{
		// Recupera la cantidad de perfiles de acceso a autogestion
		return count(catalogo::consultar('persona', 'perfiles', $parametros)) ;
	}
	
	static function perfiles_validos($perfiles, $persona)
	{
		//El metodo consiste en validar el parametro pre_preinsc_alumnos_solo_cursos y en el caso de ser 'P'
		//tambien quita el perfil Cursos segun el parametro 'cur_alta_usuario_cursos_autogestion' y saca el perfil 'gestion' 
		//Debo comprobar si el usuario es solo tipo curso y preinscripcion, para este caso no podra prinscribirse a propuesta
		//caso contrario, si el usuario ademas del perfil curso tiene un perfil, docente, administrativo3w o gestion hago la vista gorda =) 
		$array_perfiles_validos = [persona::perfil_alumno, //3w
				persona::perfil_administrador_3w,
				persona::perfil_docente,//3w
				persona::perfil_gerencial,
				//persona::perfil_preinscripcion,
				//persona::perfil_cursos,
				persona::perfil_gestion
			];
		if (parametro::get_valor(parametro::cur_alta_usuario_cursos_autogestion) == comunes::no) {
			unset($perfiles[persona::perfil_cursos]);
		}

		if (parametro::get_valor('pre_preinsc_alumnos_solo_cursos') == 'P') {
			
			$persona_verificada = false;
			foreach ($array_perfiles_validos as $perfil_valido) {
				if (in_array($perfil_valido, $perfiles)) {
					$persona_verificada = true;
					break;
				}
			}

			if (!$persona_verificada) {
				//Si la persona solo es de tipo Cursos, elimino el perfil preinscripcion 
				//porque debe hacer el circuito completo de preinscripcion como denota el parametro
				//solo si no tiene propuesta aceptada pendiente rechazadas	
				if (isset($perfiles[persona::perfil_preinscripcion])) {
					$propuestas_arp = count(catalogo::consultar('persona', 'carreras', ['persona' => $persona, 'arp' => true]));
					if ($propuestas_arp < 1) {
						unset($perfiles[persona::perfil_preinscripcion]);
					}
				}
			}
		}

		if (isset($perfiles[persona::perfil_gestion])) {
			unset($perfiles[persona::perfil_gestion]);
		}
		
		

		return $perfiles;
	}

	protected function cargar_configuracion()
	{
		// limpiar cache
		$parametros = array('persona' => $this->persona);
		$this->datos_configuracion = catalogo::consultar('persona', 'configuracion', $parametros);
	}

	protected function recargar_configuracion()
	{
		// limpiar cache
		$parametros = array('persona' => $this->persona);
		catalogo::limpiar_cache('persona', 'configuracion', $parametros);
		$this->cargar_configuracion();
	}

	function get_id()
	{
		return $this->persona;
	}

	function get_tipo_documento()
	{
		return $this->datos_basicos['tipo_documento'];
	}

	function get_tipo_documento_desc()
	{
		return $this->datos_basicos['tipo_documento_desc'];
	}

	function get_tipo_documento_desc_abreviada()
	{
		return $this->datos_basicos['tipo_documento_desc_abreviada'];
	}

	function get_nro_documento()
	{
		return $this->datos_basicos['nro_documento'];
	}

	function get_inicial_apellido()
	{
		return $this->datos_basicos['inicial_apellido'];
	}

	function get_nombre()
	{
		return $this->datos_basicos['nombre_mostrar'] . ' ' . $this->datos_basicos['apellido_mostrar'];
	}

	function get_solo_nombres()
	{
		return $this->datos_basicos['nombres'];
	}

	function get_solo_apellido()
	{
		return $this->datos_basicos['apellido'];
	}

	function get_nombres_elegido()
	{
		return $this->datos_basicos['nombre_mostrar'];
	}

	function get_apellido_elegido()
	{
		return $this->datos_basicos['apellido_mostrar'];
	}

	function get_fecha_nacimiento()
	{
		return $this->datos_basicos['fecha_nacimiento_visual'];
	}

	function get_identificacion()
	{
		return $this->datos_basicos['identificacion'];
	}

	function get_usuario_arai()
	{
		return $this->datos_basicos['arai_identificador_sso'];
	}
	
	function get_uuid_arai()
	{
		return $this->datos_basicos['arai_uuid'];
	}

	function get_mail()
	{
		return $this->datos_configuracion['email'];
	}

	function get_configuracion()
	{
		return $this->datos_configuracion;
	}

	function get_id_imagen()
	{
		return $this->datos_basicos['id_imagen'];
	}

	//Envio como parametro el tipo de documento ejemplo = 3 que es CUIL
	function get_cuil()
	{
		$cuil = \persona::doc_CUIL;
		catalogo::limpiar_cache('persona', 'get_documentos_persona', array(
			'persona' => $this->persona,
			'tipo_documento' => $cuil
		));
		$datos = catalogo::consultar('persona', 'get_documentos_persona', array(
			'persona' => $this->persona,
			'tipo_documento' => $cuil
		));
		return current($datos);
	}

	//--------------------------------------------------------------------------
	//---- ENUESTAS
	//--------------------------------------------------------------------------

	function get_encuestas_pendientes()
	{
		return encuestas_kolla::get_encuestas_pendientes($this->persona);
	}

	function set_encuestas_respondidas($hab, $token, $fecha_respuesta)
	{
		return encuestas_kolla::set_encuestas_respondidas($this->persona, $hab, $token, $fecha_respuesta);
	}

	function get_encuestas_respondidas($fecha_desde, $fecha_hasta, $docente, $actividad)
	{
		return encuestas_kolla::get_encuestas_respondidas($this->get_id(), $fecha_desde, $fecha_hasta, $docente, $actividad);
	}

	//--------------------------------------------------------------------------
	//---- PERFILES
	//--------------------------------------------------------------------------

	function perfil()
	{
		return $this->perfil;
	}

	function set_perfil_activo($id)
	{		
		if (!in_array($id, $this->get_perfiles())) {
			throw new \Exception("PERSONA: Error interno '$id'");
		}
		$this->perfil = FactoryPerfil::instancia()->construir($id, kernel::proyecto()->get('bloque_acceso'));
		kernel::sesion()->set(static::SESION_ID_PERSONA_PERFIL, $id);
	}

	function get_perfiles()
	{
		return $this->datos_perfiles;
	}

        /**
         * Devuelve el perfil activo, se rompe con el perfil An�nimo.
         *
         * @return string Perfil activo.
         */
	function get_id_perfil_activo()
	{
            if (kernel::sesion()->esta_seteada(static::SESION_ID_PERSONA_PERFIL)) {
                return kernel::sesion()->get(static::SESION_ID_PERSONA_PERFIL);
            }
            return $this->get_perfil_defecto();
        }

        /**
         * Devuelve el perfil activo, se debe llamar de forma est�tica.
         *
         * @return string Perfil activo.
         */
        public static function get_perfil_activo()
	{
            return kernel::sesion()->esta_seteada(static::SESION_ID_PERSONA_PERFIL)? kernel::sesion()->get(static::SESION_ID_PERSONA_PERFIL) : static::perfil_anonimo;
        }

	function get_perfil_default_sugerido()
	{
		$perfiles = $this->datos_perfiles;
		return current($perfiles);
	}

	function get_tipo_usuario_inicial()
	{
		$parametros['persona'] = $this->persona;
		return catalogo::consultar('persona', 'get_tipo_usuario_inicial', $parametros);
	}

	protected function get_perfil_defecto()
	{
		if (in_array($this->datos_basicos['perfil_inicial'], $this->datos_perfiles)) {
			if($this->datos_basicos['perfil_inicial'] == static::perfil_alumno && !$this->tiene_propuestas()){
				return current(array_diff($this->datos_perfiles, [static::perfil_alumno]));
			}
				return $this->datos_basicos['perfil_inicial'];
		} else {
			return current($this->datos_perfiles);
		}
	}

	function get_count_perfiles()
	{
		return \guarani_arreglos::count($this->datos_perfiles);
	}

	function es_perfil_docente()
	{
		return $this->es_docente() && ($this->get_id_perfil_activo() == persona::perfil_docente);
	}

	function es_perfil_cursos()
	{
		return $this->es_cursos() && ($this->get_id_perfil_activo() == persona::perfil_cursos);
	}
	
	function es_perfil_alumno()
	{
		return $this->es_alumno() && ($this->get_id_perfil_activo() == persona::perfil_alumno);
	}

	//--------------------------------------------------------------------------
	//---- DOCENTE
	//--------------------------------------------------------------------------

	function es_docente()
	{
		return (in_array(persona::perfil_docente, $this->get_perfiles()) && isset($this->datos_basicos['docente']));
	}

	function get_legajo_docente()
	{
		if ($this->es_docente()) {
			return $this->datos_basicos['legajo_docente'];
		} else {
			throw new error_guarani('La persona no es docente');
		}
	}

	function get_id_docente()
	{
		if ($this->es_docente()) {
			return $this->datos_basicos['docente'];
		} else {
			throw new error_guarani('La persona no es docente');
		}
	}

	//--------------------------------------------------------------------------
	//---- ALUMNO
	//--------------------------------------------------------------------------

	function es_alumno()
	{
		return in_array(persona::perfil_alumno, $this->get_perfiles());
	}

	function es_ingresante()
	{
		$parametros['alumno'] = $this->get_id_alumno_activo();
		$es_ingresante = catalogo::consultar('alumno', 'fecha_cierre_requisitos_ingreso_mayor_a_hoy', $parametros);
		return $es_ingresante;
	}

	function tiene_propuestas()
	{
		return \guarani_arreglos::count($this->datos_propuestas) > 0;
	}
	
	function get_count_propuestas()
	{
		return \guarani_arreglos::count($this->datos_propuestas);
	}

	function set_propuesta_activa($id)
	{
		if (!in_array($id, $this->get_propuestas())) {
			throw new \Exception("PERSONA: Error interno. La carrera activa: $id no esta entre las carreras del alumno");
		}
		kernel::sesion()->set('__persona_carrera', $id);
	}

	function get_propuestas()
	{
		if (!isset($this->id_propuestas)) {
			foreach ($this->datos_propuestas as $propuesta) {
				$this->id_propuestas[] = $propuesta['propuesta'];
			}
		}
		return $this->id_propuestas;
	}

	function get_lista_propuestas()
	{
		$propuestas = array();
		foreach ($this->datos_propuestas as $id => $propuesta) {
			$propuestas[$id]['id'] = $propuesta['propuesta'];
			$propuestas[$id]['nombre'] = $propuesta['propuesta_nombre'];
			$propuestas[$id]['calidad'] = $propuesta['calidad'];
		}
		return $propuestas;
	}

	function get_id_propuesta_activa()
	{
		// Ver ticket #42999 si la propuesta seteada este dentro de las propuestas activas 
		if (kernel::sesion()->esta_seteada('__persona_carrera') && in_array(kernel::sesion()->esta_seteada('__persona_carrera'), $this->get_propuestas())) {
			return kernel::sesion()->get('__persona_carrera');
		}
		
		// Ver ticket #34224 (https://redmine.siu.edu.ar/redmine/issues/34224)
		// Si esta seteada una propuesta inicial asigno la misma, sino la propuesta por defecto.
		$propuesta_inicial = $this->get_propuesta_inicial();
		return (is_null($propuesta_inicial))? $this->get_propuesta_defecto() : $propuesta_inicial;
	}

	function get_info_propuesta_activa()
	{
		$id = $this->get_id_propuesta_activa();
		foreach ($this->datos_propuestas as $propuesta) {
			if ($propuesta['propuesta'] == $id) {
				return array(
					'id' => $propuesta['propuesta'],
					'nombre' => $propuesta['propuesta_nombre'],
					'codigo' => $propuesta['propuesta_codigo'],
					'calidad' => $propuesta['calidad']
				);
			}
		}
	}

	protected function get_propuesta_defecto()
	{
		$propuestas = $this->get_propuestas();
		if (!isset($propuestas[0])) {
			throw new error_guarani('No existen carreras');
		}
		return $propuestas[0];
	}
	
	function get_propuesta_inicial()
	{
		$parametros['persona'] = $this->persona;
		return catalogo::consultar('persona', 'get_propuesta_inicial', $parametros);
	}


		/**
	 * 22/09/2025
	 * Newsol - Joaquin	
	 * Si el usuario tiene el perfil cursos, se obtiene el id_alumno de otra forma
	 */
	function get_id_alumno_activo_2()
	{
		if (!$this->es_cursos()) {
			return $this->get_id_alumno_activo_base();
		} else {

			$curso_propuestas = catalogo::consultar(
				'cursos',
				'get_propuestas_academica',
				['persona' => $this->persona]
			);

			kernel::log()->add_debug('Propuestas cursos', $curso_propuestas);

			$pers_prop_param = [
				'persona' => $this->persona,
				'propuesta' => $this->get_id_propuesta_activa()
			];

			foreach ($curso_propuestas as &$propuesta) {
				$propuesta['cursos'] = catalogo::consultar(
					'cursos',
					'get_cursos_academica',
					$pers_prop_param
				);
			}

			//kernel::log()->add_debug('Cursos propuestas - propuesta', $propuesta['propuesta']);

			$pers_prop_param = [
				'persona' => $this->persona,
				'propuesta' => $propuesta['propuesta']
			];

			//kernel::log()->add_debug('Parametros alumno', $pers_prop_param);

			$alumno = catalogo::consultar(
				'alumno',
				'get_alumno',
				$pers_prop_param
			);

			$parametros['alumno'] = $alumno;
			return $parametros['alumno']['alumno'];
		}
	}

	// ORIGINAL 
	function get_id_alumno_activo()
	{

		$id = $this->get_id_propuesta_activa();

		foreach ($this->datos_propuestas as $propuesta) {
			if ($propuesta['propuesta'] == $id) {
				return $propuesta['alumno'];
			}
		}
	}

/*/
	function get_id_alumno_activo()
	{
		$id = $this->get_id_propuesta_activa();

		foreach ($this->datos_propuestas as $propuesta) {
			if ($propuesta['propuesta'] == $id) {
				return $propuesta['alumno'];
			}
		}
	}
*/



	function get_legajo_activo()
	{
		$id = $this->get_id_propuesta_activa();
		foreach ($this->datos_propuestas as $propuesta) {
			if ($propuesta['propuesta'] == $id) {
				return $propuesta['legajo'];
			}
		}
	}

	function get_id_plan_activo()
	{
		$id = $this->get_id_propuesta_activa();
		foreach ($this->datos_propuestas as $propuesta) {
			if ($propuesta['propuesta'] == $id) {
				return $propuesta['plan'];
			}
		}
	}

	function get_info_plan_activo()
	{
		$id = $this->get_id_propuesta_activa();
		foreach ($this->datos_propuestas as $propuesta) {
			if ($propuesta['propuesta'] == $id) {
				return array(
					'plan' => $propuesta['plan'],
					'nombre' => $propuesta['plan_nombre'],
					'codigo' => $propuesta['plan_codigo']
				);
			}
		}
	}

	function get_id_plan_version_activo()
	{
		$id = $this->get_id_propuesta_activa();
		foreach ($this->datos_propuestas as $propuesta) {
			if ($propuesta['propuesta'] == $id) {
				return $propuesta['plan_version'];
			}
		}
	}

	function get_info_plan_version_activo()
	{
		$id = $this->get_id_propuesta_activa();
		foreach ($this->datos_propuestas as $propuesta) {
			if ($propuesta['propuesta'] == $id) {
				return array(
					'plan_version' => $propuesta['plan_version'],
					'nombre' => $propuesta['planes_versiones_nombre'],
					'version' => $propuesta['planes_versiones_version']
				);
			}
		}
	}

	function get_id_ubicacion_activa()
	{
		$id = $this->get_id_propuesta_activa();
		foreach ($this->datos_propuestas as $propuesta) {
			if ($propuesta['propuesta'] == $id) {
				return $propuesta['ubicacion'];
			}
		}
	}

	function get_id_modalidad_activa()
	{
		$id = $this->get_id_propuesta_activa();
		foreach ($this->datos_propuestas as $propuesta) {
			if ($propuesta['propuesta'] == $id) {
				return $propuesta['modalidad'];
			}
		}
	}

	function get_id_division_activa()
	{
		$id = $this->get_id_propuesta_activa();
		foreach ($this->datos_propuestas as $propuesta) {
			if ($propuesta['propuesta'] == $id) {
				return $propuesta['division'];
			}
		}
	}

	//--------------------------------------------------------------------------
	//--- CURSOS
	//--------------------------------------------------------------------------

	function es_cursos()
	{
		return in_array(persona::perfil_cursos, $this->get_perfiles());
	}

	//--------------------------------------------------------------------------
	//---- MENSAJES
	//--------------------------------------------------------------------------

	function get_cantidad_mensajes()
	{
		$param = array('persona' => $this->persona);
		return catalogo::consultar('mensajes', 'get_cant_mensajes', $param);
	}

	function limpiar_cache_cantidad_mensajes()
	{
		$param = array('persona' => $this->persona);
		return catalogo::limpiar_cache('mensajes', 'get_cant_mensajes', $param);
	}

	//--------------------------------------------------------------------------
	//---- ESTADO INSCRIPCIONES CURSADA
	//--------------------------------------------------------------------------
	/**
	 *
	 * @return \siu\modelo\entidades\alumno_estado_insc_cursada
	 */
	function cursadas()
	{
		if (!isset($this->estado_cursadas)) {
			$this->estado_cursadas = new alumno_estado_insc_cursada($this->persona);
		}
		return $this->estado_cursadas;
	}

	//--------------------------------------------------------------------------
	//---- ESTADO INSCRIPCIONES EX�MENES
	//--------------------------------------------------------------------------
	/**
	 *
	 * @return \siu\modelo\entidades\alumno_estado_insc_examen
	 */
	function examenes()
	{
		if (!isset($this->estado_examenes)) {
			$this->estado_examenes = new alumno_estado_insc_examen($this->persona);
		}
		return $this->estado_examenes;
	}

	//--------------------------------------------------------------------------

	function get_locale()
	{
		return 'es_AR';
	}

	protected function get_mem_key($seed)
	{
		$persona = $this->get_id();
		return "userdata[$persona]" . md5("$persona$seed");
	}

	/**
	 * 
	 * @param string $key
	 * @return mixed Si la clave existe devuelve el valor asociado. Sino devuelve FALSE
	 */
	function get_user_data($key)
	{
		$mem_key = $this->get_mem_key($key);
		if (kernel::proyecto()->memcached() !== null) {
			return kernel::proyecto()->memcached()->get($mem_key);
		} else {
			kernel::log()->add_debug('ACCESO USER DATA DESDE SESSION, NO EST� DEFINIDO EL HANDLER DE MEMCACHE', array());
			if (isset($_SESSION[$mem_key])) {
				return $_SESSION[$mem_key];
			}
			return false;
		}
	}

	/**
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $expiration Tiempo de expiraci�n, defaults a 0 (sin expiraci�n). 
	 * M�s info en: http://www.php.net/manual/en/memcached.expiration.php
	 * @return boolean TRUE si se escribi� correctamente y FALSE en caso contrario 
	 */
	function set_user_data($key, $value, $expiration = false)
	{
		if ($expiration === false) {
			$expiration = 0; // no hay tiempo de expiraci�n
		}

		$mem_key = $this->get_mem_key($key);
		if (kernel::proyecto()->memcached() !== null) {
			return kernel::proyecto()->memcached()->set($mem_key, $value, $expiration);
		} else {
			kernel::log()->add_debug('ESCRITURA USER DATA EN $_SESSION, NO USAR AS� EN PRODUCCI�N', array());
			$_SESSION[$mem_key] = $value;
			return true;
		}
	}

	/**
	 *
	 * @param integer $pais
	 * @param integer $tipo_doc
	 * @param integer $doc
	 * @return array list($mail, $persona)
	 * @throws error_guarani_usuario 
	 */
	static function get_data_recuperar_pass($pais, $tipo_doc, $doc)
	{
		$param = array(
			'pais' => $pais,
			'tipo_doc' => $tipo_doc,
			'doc' => $doc
		);
		$res = catalogo::consultar('persona', 'data_recuperar_pass', $param);

		if ($res[0] == -1) {
			throw new error_guarani_usuario($res[1]);
		}

		return array($res[2], $res[3], $res[4]);
	}

	static function update_token($persona, $token)
	{
		$param = array(
			'persona' => $persona,
			'token' => $token
		);
		catalogo::consultar('persona', 'update_token', $param);
	}
    
    static function limpiar_token($persona)
	{
		$param = [
			'persona' => $persona
		];
		catalogo::consultar('persona', 'limpiar_token', $param);
	}

	static function update_mail_temporal($persona, $email)
	{
		$param = array(
			'persona' => $persona,
			'email' => $email
		);
		catalogo::consultar('persona', 'update_email_temporal', $param);
	}

	static function blanquear_mail_temporal($persona)
	{
		$param = array(
			'persona' => $persona
		);
		catalogo::consultar('persona', 'blanquear_email_temporal', $param);
	}

	static function refresh_token($persona)
	{
		$token = \persona::generar_token();
		$param = array(
			'persona' => $persona,
			'token' => $token
		);
		catalogo::consultar('persona', 'update_token', $param);
		return $token;
	}

	static function get_persona_from_token($token)
	{
		$param = array(
			'token' => $token
		);

		$res = catalogo::consultar('persona', 'buscar_por_token', $param);
		if (empty($res)) {
			throw new error_guarani('no hay una persona asociada a este token');
		}

		return $res;
	}

	static function get_mail_from_token($token)
	{
		$param = array(
			'token' => $token,
		);

		$res = catalogo::consultar('persona', 'buscar_email_temporal_por_token', $param);
		if (empty($res)) {
			throw new error_guarani('no hay una persona asociada a este token');
		}

		return $res;
	}

	static function reset_password($persona, $password)
	{
		$param = array(
			'persona' => $persona,
			'password' => guarani::encriptar($password)
		);
		catalogo::consultar('persona', 'reset_password', $param);
	}

	static function actualizar_configuracion($persona, $recibe_mail_mensajes, $email)
	{
		$parametros = array();
		$parametros['persona'] = $persona;
		$parametros['recibe_mail_mensajes'] = $recibe_mail_mensajes;
		$parametros['email'] = $email;

		catalogo::consultar('persona', 'actualizar_configuracion', $parametros);
		guarani::persona()->recargar_configuracion();
	}

	static function actualizar_configuracion_email($persona, $email)
	{
		$parametros = array();
		$parametros['persona'] = $persona;

		$existe_configuracion_email = catalogo::consultar('persona', 'existe_configuracion_email', $parametros);

		$parametros['email'] = $email;

		if ($existe_configuracion_email) {
			catalogo::consultar('persona', 'actualizar_configuracion_email', $parametros);
		} else {
			catalogo::consultar('persona', 'insertar_configuracion_email', $parametros);
		}

		guarani::persona()->recargar_configuracion();
	}

	static function actualizar_configuracion_checks($persona, $recibe_mail_mensajes)
	{
		$parametros = array();
		$parametros['persona'] = $persona;
		$parametros['recibe_mail_mensajes'] = $recibe_mail_mensajes;

		catalogo::consultar('persona', 'actualizar_configuracion_checks', $parametros);
		guarani::persona()->recargar_configuracion();
	}

	static function actualizar_password($usuario, $actual_pass, $nuevo_pass)
	{
		persona::controlar_password($nuevo_pass);
		$parametros = array();
		$parametros['usuario'] = $usuario;
		$parametros['actual_pass'] = $actual_pass;
		$parametros['nuevo_pass'] = $nuevo_pass;

		$respuesta = catalogo::consultar('persona', 'actualizar_password', $parametros);

		$resultado = array(
			'cod' => $respuesta[0],
			'cont' => $respuesta[1]
		);
		return $resultado;
	}

	static function controlar_password($password)
	{
		$error = new error_guarani_procesar_renglones('Contrase�a invalida');
		$error_nro_linea = 0;

		if (!\usuario::validar_clave_permitida($password)) {
			$error->add_renglon($error_nro_linea, kernel::traductor()->trans("clave_longitud_minima", array('%1%' => parametro::get_valor('login_clave_longitud_minima'))));
			$error_nro_linea++;
		}

		if ($error->hay_renglones()) {
			throw $error;
		}
	}

	function tiene_derecho_entidad($derecho, $tipo_entidad, $id_modelo)
	{
		$rs = $this->tiene_derecho_entidades($derecho, $tipo_entidad, array($id_modelo));
		return isset($rs[$id_modelo]);
	}

	/**
	 * Consulta todos los derechos en 1 consulta. Devuelve un arreglo [id_modelo]=> 1 para cada $id_modelo
	 * que tiene acceso.
	 */
	function tiene_derecho_entidades($derecho, $tipo_entidad, $ids_modelo)
	{
		$ids_planos = implode(',', $ids_modelo); //Lo hago aca en vez de en la consulta ya que
		//el catalogo hace un implode para aplanar los params y no anda para array(array)
		//seguridad?? no se devuelve info, pero se podria updetear algo con inyecciones?
		//las entidades salen de columnas de las consultas. -Cuidado si cambia-
		switch ($tipo_entidad) {
			case 'comision':
				$params = array(
					'persona' => $this->get_id(),
					'derecho' => $derecho,
					'comisiones' => $ids_planos
				);
				return \siu\modelo\datos\catalogo::consultar('derecho_entidades', 'tiene_derecho_comisiones', $params);
			case 'llamado_mesa':
				$params = array(
					'persona' => $this->get_id(),
					'derecho' => $derecho,
					'llamados_mesas' => $ids_planos
				);
				return \siu\modelo\datos\catalogo::consultar('derecho_entidades', 'tiene_derecho_llamados_mesas', $params);
			case 'acta_fc':
				$params = array(
					'persona' => $this->get_id(),
					'derecho' => $derecho,
					'llamados_mesas' => $ids_planos
				);
				return \siu\modelo\datos\catalogo::consultar('derecho_entidades', 'tiene_derecho_llamados_mesas', $params);

			default :
				$error = new \siu\errores\error_guarani("Derechos - No existe tipo de entidad - $tipo_entidad");
				kernel::log()->add_error($error);
				throw $error;
		}
	}

	//--------------------------------------------------------------------------
	//---- REQUISITOS DE INGRESO
	//--------------------------------------------------------------------------

	function get_cant_requisitos_pendientes()
	{
		$perfil_activo = $this->get_id_perfil_activo();
		if ($perfil_activo == \persona::perfil_alumno) {
			$requisitos_pendientes = $this->get_requisitos_pendientes();
			$cant_req_pendientes = \guarani_arreglos::sizeof($requisitos_pendientes['requisitos']);
		} else {
			$cant_req_pendientes = 0;
		}
		klog2("-- Cantidad requisitos pendientes", $cant_req_pendientes);
		return $cant_req_pendientes;
	}
	
	function get_verifica_f_tope_presentacion($datos)
	{
		$operacion = kernel::ruteador()->get_id_operacion();
		foreach ($datos as $key => $dato) {			
			if (isset($dato['requisito'])) {
				// Si tiene 'Fecha l�mite presentaci�n'
				if (isset($dato['f_tope_presentacion'])) {
					// Si 'Fecha l�mite presentaci�n' es mayor o igual a hoy
					if ($dato['esta_vigente_para_presentar']) {
						$datos[$key]['url_doc_digital'] = kernel::vinculador()->crear($operacion, 'doc_digital', array($dato[catalogo::id]));
					} else {
						unset($datos[$key]);
					}
				} else {
					$datos[$key]['url_doc_digital'] = kernel::vinculador()->crear($operacion, 'doc_digital', array($dato[catalogo::id]));
				}
			}
		}
		klog2("-- Requisitos pendientes vigentes --", $datos);
		return $datos;
	}

	function get_requisitos_pendientes()
	{
		$parametros['alumno'] = $this->get_id_alumno_activo();
		$parametros['estado_req_digital'] = [solicitar_certificacion::estado_no_presentado, solicitar_certificacion::estado_vencido];
		$req_pendientes = [];
		$req_pendientes_vigentes = [];
		//Primero traigo todos los requisitos pendientes
		$req_pendientes = catalogo::consultar('alumno', 'get_requisitos_ingreso_propuesta', $parametros);
		//Segundo calculo que la fecha tope de presentacion sea vigente
		$req_pendientes_vigentes = $this->get_verifica_f_tope_presentacion($req_pendientes);
		$propuesta = $this->get_info_propuesta_activa();
		$inscripcion['propuesta'] = $propuesta['codigo'];
		$inscripcion['propuesta_nombre'] = $propuesta['nombre'];
		$inscripcion['requisitos'] = $req_pendientes_vigentes;
		klog2("--Requisitos pendientes procesados --", $req_pendientes_vigentes);
		
		return $inscripcion;
	}

	function get_requisitos_todos()
	{
		$parametros['alumno'] = $this->get_id_alumno_activo();
		$parametros['estado_req_digital'] = null;
		$req = [];
		$req_vigentes = [];
		//Primero traigo todos los requisitos
		$req_pendientes = catalogo::consultar('alumno', 'get_requisitos_ingreso_propuesta', $parametros);
		//Segundo calculo que la fecha tope de presentacion sea vigente
		$req_vigentes = $this->get_verifica_f_tope_presentacion($req_pendientes);
		$propuesta = $this->get_info_propuesta_activa();
		$inscripcion['propuesta'] = $propuesta['codigo'];
		$inscripcion['propuesta_nombre'] = $propuesta['nombre'];
		$inscripcion['requisitos'] = $req_vigentes;
		klog2("--Requisitos procesados --", $req_vigentes);

		return $inscripcion;
	}

        //--------------------------------------------------------------------------
	//---- SAML
	//--------------------------------------------------------------------------

        /**
         * Si es login por SAML o no.
         *
         *
         * @return boolean TRUE si es login por SAML, FALSE en caso contrario.
         */
	function es_saml_login()
	{
            return kernel::sesion()->esta_seteada(auth_saml::SAML_USER_DATA);
	}

        /**
         * Datos del usuario SAML o null si no est� logueado por SAML.
         *
         *
         * @return array|null Datos del usuario SAML o null si no est� logueado por SAML.
         */
        function get_saml_user_data()
	{
            return $this->es_saml_login()? kernel::sesion()->get(auth_saml::SAML_USER_DATA) : null;
	}
}