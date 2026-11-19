<?php
namespace siu\modelo\autenticacion;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\IntentosLogin;
use siu\modelo\autenticacion\auth_form;

class auth_ldap extends auth_form {

    protected function validar_user_pass($usuario, $password)
    {
        $parametros_conexion = $this->get_parametros();
        $this->validar_parametros($parametros_conexion);        
        $id_persona = $this->get_clase_usuarios()->autenticar_login_ldap($parametros_conexion, $usuario, $password);
        IntentosLogin::eliminar($usuario);
		return $id_persona;
    }
    
    private function validar_parametros($parametros)
    {
        $mensajes = array();
        //Valido los parametros que estén todos en el login.php
        if (!isset($parametros['server'])) {            
            $mensajes[] = '[Autenticación LDAP] El parametro server no esta seteado en el archivo instalacion/login.php';
        }
        if (!isset($parametros['user'])) {
            $mensajes[] = '[Autenticación LDAP] El parametro user no esta seteado en el archivo instalacion/login.php';
        }
        if (!isset($parametros['dn'])) {
            $mensajes[] = '[Autenticación LDAP] El parametro dn no esta seteado en el archivo instalacion/login.php';
        }
        if (!isset($parametros['bind_dn'])) {
            $mensajes[] = '[Autenticación LDAP] El parametro bind_dn no esta seteado en el archivo instalacion/login.php';
        }
        if (!isset($parametros['bind_pass'])) {
            $mensajes[] = '[Autenticación LDAP] El parametro bind_pass no esta seteado en el archivo instalacion/login.php';
        }            
        if (!isset($parametros['filter'])) {
            $mensajes[] = '[Autenticación LDAP] El parametro filter no esta seteado en el archivo instalacion/login.php';
        }
        
        if (\guarani_arreglos::count($mensajes) > 0) {  
            kernel::log()->add_error(new \Exception(implode('<br>', $mensajes)));
        }
    }
}