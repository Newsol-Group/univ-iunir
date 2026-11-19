<?php

namespace siu\operaciones\reinscripcion;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_form_reinscripcion extends pagelet
{
    function get_nombre()
    {
        return 'form_reinscripcion';
    }

    function prepare()
    {
        $this->data = array();
        if ($this->controlador->hay_var('mensajes')){
            $this->data['mensajes'] = $this->controlador->get_var('mensajes');
        }
        
        if ($this->controlador->hay_var('url_comprobante')){
            $this->data['url_comprobante'] = $this->controlador->get_var('url_comprobante');
        }
        
        
        if ($this->controlador->hay_var('reinscripcion_aceptada')){
            $this->data['reinscripcion_aceptada'] = true;
        }
        
        $this->data['url_reinscripcion'] = kernel::vinculador()->crear('reinscripcion', 'reinscribir');
    }
}
?>
