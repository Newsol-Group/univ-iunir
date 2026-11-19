<?php
class guarani_usuario extends toba_usuario_basico
{
	function __construct($id_usuario)
	{
		if (toba_editor::modo_prueba()) {
			parent::__construct($id_usuario);
		} else {
			// Datos básicos del usuario.
			$this->datos_basicos = toba::consulta_php('co_usuarios')->get_datos_basicos_usuario($id_usuario);

			// Grupos de acceso (o perfiles funcionales).
			$grupos_acceso = toba::consulta_php('co_usuarios')->get_grupos_acceso_usuario($id_usuario);
			if (! empty($grupos_acceso)) {
				foreach ($grupos_acceso as $grupo) {
					$this->grupos_acceso[] = $grupo['grupo_acceso'];
					if (toba::consulta_php('co_usuarios')->get_cant_membresias_grupo_acceso($grupo['grupo_acceso'])) {
						$this->grupos_acceso = array_merge($this->grupos_acceso, toba::proyecto()->get_perfiles_funcionales_asociados($grupo['grupo_acceso']));
					}
				}
				$this->grupos_acceso = array_unique($this->grupos_acceso);
			} else {
				$this->grupos_acceso = array();
			}

			// Perfil de datos.
			$this->perfiles_datos = toba::consulta_php('co_usuarios')->get_perfiles_datos_usuario($id_usuario);
		}
	}

	static function existe_usuario($usuario)
    {
		$datos_usuario = toba::consulta_php('co_usuarios')->get_datos_basicos_usuario($usuario);
		return (!empty($datos_usuario));
    }

	function get_perfiles_datos()
	{
		// Sólo puede haber un perfil funcional activo.
		$perfil_funcional = current(toba::manejador_sesiones()->get_perfiles_funcionales_activos());
		return toba::consulta_php('co_usuarios')->get_perfiles_datos_usuario_perfil_funcional($this->datos_basicos['id'], $perfil_funcional);
	}

	//-------------------------------------------------------
	//----- Autentificación
	//-------------------------------------------------------

	/**
	 * Realiza la autentificación de un usuario en el sistema.
	 *
	 * @return boolean
	 */
	static function autenticar($id_usuario, $clave, $datos_iniciales=null, $usar_log=true)
	{   
        $datos_usuario = toba::consulta_php('co_usuarios')->get_datos_autenticacion_usuario($id_usuario);
		if (empty($datos_usuario)) {
			if ($usar_log) {
				toba::logger()->error("El usuario '$id_usuario' no existe", 'toba');
			}
			return false;
		} else {
            //Si el tipo de auntenticación es ldap lo logueo por esta vía y no valido
            // la passwd, solo que exista la persona.
            if (toba::instalacion()->get_tipo_autenticacion() == 'guarani_ldap') {
                $guarani_ldap = new guarani_autenticacion_ldap();
                return $guarani_ldap->autenticar($id_usuario, $clave);
            } else {
                $algoritmo = $datos_usuario['autenticacion'];
                $enc = new bcrypt(10);
                $ok = ($enc->verify(md5($clave),  $datos_usuario['clave'] ));
                if (!$ok) {
                    if ($usar_log) {
                        toba::logger()->error("El usuario '$id_usuario' ingreso una clave incorrecta", 'toba');
                    }
                    return false;
                }
            }
		}
		return true;
	}

	static function chequear_clave($sin_encriptar, $encriptada)
	{
		$enc = new bcrypt(10);
		return $enc->verify(md5($sin_encriptar), $encriptada);
	}

	//-------------------------------------------------------
	//----- Bloqueo de usuarios
	//-------------------------------------------------------

	static function bloquear_usuario($usuario)
	{
		try {
			guarani::act('act_usuarios')->bloquear_usuario($usuario);
		} catch ( toba_error $e ) {
			// El usuario ya está bloqueado.
		}
	}

	static function es_usuario_bloqueado($usuario)
	{
		return toba::consulta_php('co_usuarios')->es_usuario_bloqueado($usuario);
	}
}
?>