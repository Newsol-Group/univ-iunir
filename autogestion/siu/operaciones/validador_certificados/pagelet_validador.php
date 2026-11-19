<?php
namespace siu\operaciones\validador_certificados;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_validador extends pagelet{

    const REGEXP_COD_VALIDACION = '^[0-9]{14}$';

    public function get_nombre(){
        return 'validador';
    }
    
    function prepare()
    {
    	$this->data['url_validar'] = kernel::vinculador()->crear('validador_certificados', 'validar');
        $this->data['mensajes'] = $this->controlador->get_var('mensajes');

        $this->add_mensaje_js('error_debe_ingresar_codigo_validacion', kernel::traductor()->trans('validador_certificados.error_debe_ingresar_codigo_validacion'));
        $this->add_mensaje_js('error_codigo_validacion_invalido', kernel::traductor()->trans('validador_certificados.error_codigo_validacion_invalido'));
        $this->add_mensaje_js('error_debe_ingresar_documento', kernel::traductor()->trans('validador_certificados.error_debe_ingresar_documento'));
        $this->add_mensaje_js('error_documento_invalido', kernel::traductor()->trans('validador_certificados.error_documento_invalido'));
        $this->add_mensaje_js('error_recaptcha', kernel::traductor()->trans('error_recaptcha'));
        $this->add_var_js('usa_recaptcha', kernel::proyecto()->usa_captcha());

        $this->add_var_js('regexp_documento', \persona::exp_reg_alfanum);
        $this->add_var_js('regexp_cod_validacion', static::REGEXP_COD_VALIDACION);
    }
    
    function ini()
    {
    }

}