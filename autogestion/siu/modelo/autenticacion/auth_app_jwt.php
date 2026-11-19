<?php

namespace siu\modelo\autenticacion;

use SIU\Chulupi\kernel;
use siu\errores\error_guarani_login;
use siu\modelo\datos\catalogo;
use SIU\JWT\Util;
use SIU\JWT\Decoder\SimetricDecoder;
use siu\modelo\entidades\persona;

class auth_app_jwt extends auth_con_sesion {

	protected $id_persona = null;
	protected $usarios_bd;

	/**
	 * @var error_guarani_login
	 */
	protected $error_login = null;


	public function autenticar()
	{
		if( isset($_SERVER['HTTP_TOKEN'])){
				
			$config = kernel::proyecto()->get_login_config();
			$simetric_decoder = new SimetricDecoder(Util::ALG_HS256, $config['app_jwt']['parametros']['keysimetrica'] );
			$jwt = new Util();
			$jwt->setDecoder($simetric_decoder);

			try{
				$persona = $jwt->decode($_SERVER['HTTP_TOKEN'])->persona;
			}
			finally{
				if(!isset($persona)) {
					return null;
				}
			}

			$parametros = array('persona' => $persona);
			$datos = persona::cantidad_perfiles($parametros);
			// tiene perfiles asociados?
			if (empty($datos) || $datos == 0) {
				throw new error_guarani_login('-5'); //'login.errores.perfiles';
			}
			$datos_perfil = catalogo::consultar('persona', 'perfiles', $parametros);
			if (in_array(persona::perfil_alumno, $datos_perfil)) {
				//verifico si es alumno que tenga al menos una carrera activa/pendiente
				$carreras = catalogo::consultar('persona', 'carreras', $parametros);
				if (empty($carreras)) {
					throw new error_guarani_login('-8');
				}
			}

			kernel::sesion()->set('login_por', 'guarani');
			$param = array('persona' => $persona,
				'registrar' => kernel::proyecto()->get('registrar_log_ingresos'),
				'ip' => kernel::login_manager()->get_real_ip(),
				'login_por' => kernel::sesion()->get('login_por'),
				'status' => null
			);
			catalogo::consultar('persona', 'insertar_registro_logueo', $param);
			
			$this->id_persona = $persona;
			
			return $this->id_persona;
		}
		return null;
	}

	public function get_modelo(){
		$datos = array();
		$datos['accion'] = kernel::vinculador()->crear('acceso', 'index', array('auth' => 'app_jwt'));
		return $datos;
	}

	public function logout()
	{
		// Ver ticket #34224 (https://redmine.siu.edu.ar/redmine/issues/34224)
		$this->registrar_propuesta_inicial_logout();
		
		$log_ingreso = kernel::sesion()->get('log_ingreso');
		if(kernel::proyecto()->get('registrar_log_ingresos') && isset($log_ingreso))
		{
			$parametros = array ( 'log_ingreso' => $log_ingreso );
			catalogo::consultar('persona', 'registrar_logout', $parametros);					
		}
	}

}