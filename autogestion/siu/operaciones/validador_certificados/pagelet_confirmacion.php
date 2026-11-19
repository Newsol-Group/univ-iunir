<?php
namespace siu\operaciones\validador_certificados;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_confirmacion extends pagelet{

    public function get_nombre(){
        return 'confirmacion';
    }

    function prepare()
    {

        $this->data['url_volver'] = kernel::vinculador()->crear('validador_certificados', 'index');
        

    }

    function ini()
    {
    }

}