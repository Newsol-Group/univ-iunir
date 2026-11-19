<?php

namespace siu\operaciones\encuestas_respondidas;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\modelo\entidades\encuestas_kolla;

class pagelet_listado_encuestas extends pagelet {

    public function get_nombre(){
        return 'listado_encuestas';
    }

    function prepare()
    {

        $this->add_mensaje_js('debe_ingresar_codigo_recuperacion', kernel::traductor()->trans('encuestas_respondidas.debe_ingresar_codigo_recuperacion'));
        $this->add_mensaje_js('codigo_recuperacion_debe_ser_entero', kernel::traductor()->trans('encuestas_respondidas.codigo_recuperacion_debe_ser_entero'));
        $this->add_mensaje_js('codigo_recuperacion_error_longitud', kernel::traductor()->trans('encuestas_respondidas.codigo_recuperacion_error_longitud'));
        $this->add_mensaje_js('debe_ingresar_hash_validacion', kernel::traductor()->trans('encuestas_respondidas.debe_ingresar_hash_validacion'));
        $this->add_mensaje_js('hash_validacion_debe_ser_alfanumerico', kernel::traductor()->trans('encuestas_respondidas.hash_validacion_debe_ser_alfanumerico'));

        $this->data['si'] = \comunes::si;
        $this->data['no'] = \comunes::no;
		
		$this->add_var_js('dominio_kolla', encuestas_kolla::get_dominio_kolla());
		$this->add_var_js('alto_iframe_defecto', encuestas_kolla::ALTO_IFRAME_DEFECTO);

        $this->data['error_descarga_encuesta'] = false;
        // Si hubo errores al descargar la encuesta
        if (kernel::sesion()->esta_seteada('error_descarga_encuesta')) {
            $this->data['error_descarga_encuesta'] = kernel::sesion()->get('error_descarga_encuesta');
            kernel::sesion()->borrar('error_descarga_encuesta');
        }
    }

} 