<?php
namespace siu\errores;

class error_guarani_login extends error_guarani
{
    protected $error_code;
    
    function __construct($error_code)
    {
        $this->error_code = $error_code;
		parent::__construct($this->get_mensaje(), $error_code);
    }
    
    function get_mensaje()
    {
        // ver db\ale\v270\Nuevo\sp_login.sql
        switch ($this->error_code) {
			case -5: return 'login.errores.perfiles';
			case -4: return 'login.errores.codigo_incorrecto';
            case -3: return 'login.errores.clave_expiro';
            case -2: return 'login.errores.id_inexistente';
            case -1: return 'login.errores.clave_invalida';
	        case -7: return 'login.errores.cuenta_externa_invalida';
	        case -8: return 'login.errores.no_tienen_carreras';
	        case -9: return 'login.errores.usuario.contactate';
        }
    }
    
    function clave_expiro()
    {
        return $this->error_code == -3;
    }
}

?>
