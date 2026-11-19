<?php
namespace siu\modelo\autenticacion;

use SIU\Chulupi\acceso\LoginManager;
use siu\modelo\datos\catalogo;
use siu\errores\error_guarani_login;
use siu\errores\error_guarani_login_externo;
use \SIU\Chulupi\kernel;
use \siu\modelo\entidades\parametro;
use SIU\Chulupi\util\bcrypt;
use siu\modelo\entidades\persona;

class fuente_usuarios_guarani
{

        const AUTH_FORM = 'form';
        const AUTH_SAML = 'saml';
        const AUTH_CUENTA_EXT = 'cuenta_ext';
        const AUTH_DEV = 'dev';
        const AUTH_APP_JWT = 'app_jwt';
        const AUTH_LDAP = 'ldap';
    
	protected $status;
	protected $usuario;
	protected $mensaje;


	/**
	 * id: como es multiperfil, se podria usar cualquier ID
	 * el login tiene que fallar si no tiene al menos un perfil definido.
	 *
	 * retorna el id de la persona
	 */
	function autenticar_usuario_clave($id, $clave)
	{
		$enc = new bcrypt(10);
		$hash = md5($clave);
		$datos_clave = catalogo::consultar('persona', 'buscar_clave', array('usuario' => $id));
		if(empty($datos_clave)){
			throw new error_guarani_login('-2'); //'login.errores.id_inexistente'
		}
		$login_ok = ($enc->verify($hash, $datos_clave['clave']));

		if ($login_ok===false) {
			throw new error_guarani_login('-1'); //'login.errores.clave_invalida';
		}
		kernel::sesion()->set('login_por', 'guarani');
		return $this->validar_persona_logueada($datos_clave['persona']);
	}


	/**
	 * @param $params array (proveedor, id, mail)
	 * @throws \siu\errores\error_guarani_login
	 * @return mixed
	 */
	function autenticar_login_externo($params)
	{
		$persona = catalogo::consultar('persona', 'login_externo', $params);

		if(empty($persona)){
			throw new error_guarani_login('-7');
		}
		kernel::sesion()->set('login_por', $params['proveedor']);
		return $this->validar_persona_logueada($persona['persona']);
	}

	/**
	 * @param $attributes array atributos de saml,
	 * @param $uid_local  string la columna local
	 * @param $uid_saml   string el atributo que referencia al id en saml
	 * @throws \siu\errores\error_guarani_login
	 * @return integer id_persona
	 */
	function autenticar_login_saml($attributes, $uid_local, $uid_saml)
	{
		$funcion = 'saml_buscar_por_'.$uid_local;
		$parametros = array($uid_local => $attributes[$uid_saml][0]);
		$persona = catalogo::consultar('persona', $funcion, $parametros);
		if(empty($persona)){
			throw new error_guarani_login('-7');
		}
		kernel::sesion()->set('login_por', 'saml');
		return $this->validar_persona_logueada($persona['persona']);
	}
    
    function autenticar_login_ldap($parametros, $usuario, $passwd)
    {
        if (!$this->validar_login_ldap($parametros, $usuario, $passwd)) {
            throw new error_guarani_login('-2');
        }
        //Busco para saber si el usuario está en la tabla personas
        $datos = catalogo::consultar('persona', 'buscar_persona', array('usuario' => $usuario));
		if(empty($datos)){
			throw new error_guarani_login('-2');
		}
        kernel::sesion()->set('login_por', 'ldap');
        return $this->validar_persona_logueada($datos['persona']);
    }
    
    function validar_login_ldap($parametros, $usuario, $passwd)
    {        
        $conexion = @ldap_connect($parametros['server']);
		ldap_set_option($conexion, LDAP_OPT_PROTOCOL_VERSION, 3);                
        if (! $conexion) {
            kernel::log()->add_error(new \Exception('[Autenticacion LDAP] No es posible conectarse con el servidor: '.ldap_error($conexion)));            
            return false;
		}
		//$bind = @ldap_bind($conexion);
		$bind = @ldap_bind($conexion, $parametros['bind_dn'], $parametros['bind_pass']); 
		if (! $bind) {
            kernel::log()->add_error(new \Exception('[Autenticacion LDAP] No es posible conectarse con el servidor: '.ldap_error($conexion)));
			return false;
		}
		$res_id = @ldap_search($conexion, $parametros['dn'], sprintf($parametros['filter'], $usuario));
		if (! $res_id) {
            
            kernel::log()->add_error(new \Exception('[Autenticación LDAP] Fallo búsqueda en el árbol: '.ldap_error($conexion)));			
			return false;
		}
		$cantidad = ldap_count_entries($conexion, $res_id);
		if ($cantidad == 0) {
            kernel::log()->add_error(new \Exception("[Autenticación LDAP] El usuario $usuario no tiene una entrada en el árbol"));			
			return false;
		}
		if ($cantidad > 1) {
            kernel::log()->add_error(new \Exception("[Autenticación LDAP] El usuario $usuario tiene más de una entrada en el árbol"));			
			return false;
		}
		$entrada_id = ldap_first_entry($conexion, $res_id);
		if ($entrada_id == false) {
            kernel::log()->add_error(new \Exception("[Autenticación LDAP] No puede obtenerse el resultado de la búsqueda". ldap_error($conexion)));   	
			return false;
		}
		$usuario_dn = ldap_get_dn($conexion, $entrada_id);
		if ($usuario_dn == false) {
            kernel::log()->add_error(new \Exception("[Autenticación LDAP] No pude obtenerse el DN del usuario: ". ldap_error($conexion)));
			return false;
		}
		$link_id = @ldap_bind($conexion, $usuario_dn, $passwd);
		if ($link_id == false) {
            kernel::log()->add_error(new \Exception("[Autenticación LDAP] Usuario/Contraseña incorrecta: ".ldap_error($conexion)));
			return false;
		}
		ldap_close($conexion);
		return true;
    }

	function registrar_login($auth)
	{
		return; //no implementado en g3
		if(isset($this->status)){ //se logueo este request
			$parametros = array('id'		=> $this->usuario,
			                    'status'	=> $this->status,
//		                    'mensaje'	=> kernel::traductor()->trans('login.aud.conexion_ok'),
			                    'registrar'	=> parametro::registrar_log_ingresos(),
			                    'interfaz'	=> kernel::agente()->es_browser()?'3':'5'
			);
		
			switch($this->status){
				case error_guarani_login::id_inexistente:
					$parametros['mensaje'] = kernel::traductor()->trans('login.aud.id_inexistente');
					break;
				case error_guarani_login::clave_invalida:
					$parametros['mensaje'] = kernel::traductor()->trans('login.aud.clave_invalida');
					break;
				case error_guarani_login::conexion_ok:
					$parametros['mensaje'] = kernel::traductor()->trans('login.aud.conexion_ok');
					break;
				default:
					$parametros['mensaje'] = 'no-especificado';
			}
		catalogo::consultar('persona', 'insertar_registro_logueo', $parametros);
		}else {
			//registrar acceso?
	}
	}

	/**
	 * Para autenticar en rest solo se retorna true/false. Luego se puede recuperar el id de la persona
	 * dentro de la libreria a partir del campo $usuario (rest::usuario())
	 * @param $usuario
	 * @param $clave
	 * @return bool si es valido o no
	 */
	public function autenticar_login_rest($usuario, $clave){
		$enc = new bcrypt(10);
		$hash = md5($clave);
		$datos_clave = catalogo::consultar('persona', 'buscar_clave', array('usuario' => $usuario));
		if(empty($datos_clave)){
			return false;
		}
		return ($enc->verify($hash, $datos_clave['clave']));
	}


	protected function validar_persona_logueada($id_persona)
	{
	
		$parametros = array('persona' => $id_persona);
//		$test_stress = defined('TEST_STRESS__NUNCA_ACTIVAR_EN_PRODUCCION') && (TEST_STRESS__NUNCA_ACTIVAR_EN_PRODUCCION == 1);
		$datos = persona::cantidad_perfiles($parametros);
		// tiene perfiles asociados?
		if (empty($datos) || $datos == 0) {
			throw new error_guarani_login('-5'); //'login.errores.perfiles';
		}
		$datos_perfil = catalogo::consultar('persona', 'perfiles', $parametros);
		if (in_array(persona::perfil_alumno, $datos_perfil)) {
			//verifico si es alumno que tenga almenos una carrera activa/pendiente
			$carreras = catalogo::consultar('persona', 'carreras', $parametros);

			if (empty($carreras) && count($datos_perfil) == 1) {
				//si solo es alumno envio el error
				kernel::log()->add_error(new \Exception(kernel::traductor()->trans('login.errores.no_tienen_carreras')));
				throw new error_guarani_login('-9');
			} elseif (empty($carreras)) {
				//grabo en el log que el alumno no tiene carreras pero continua
				kernel::log()->add_error(new \Exception(kernel::traductor()->trans('login.errores.no_tienen_carreras')));
			}
		}
		//login_ok
		$param = array('persona' => $id_persona,
			'registrar' => kernel::proyecto()->get('registrar_log_ingresos'),
			'ip' => kernel::login_manager()->get_real_ip(),
			'login_por' => kernel::sesion()->get('login_por'),
			'status' => null
		);
		catalogo::consultar('persona', 'insertar_registro_logueo', $param);
		// Retorno la persona
		return $parametros['persona'];
	}

}
?>