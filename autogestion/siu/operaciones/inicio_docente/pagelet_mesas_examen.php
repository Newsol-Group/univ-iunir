<?php

namespace siu\operaciones\inicio_docente;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_mesas_examen extends pagelet
{
    const cantidad_examenes = 5;

    function get_nombre()
    {
        return 'mesas_examen';
    }

    function prepare()
    {
        $this->data['items'] = $this->get_lista_mesas();
        $this->add_var_js('ver_mas', kernel::traductor()->trans('inicio_docente.ver_mas'));
        $this->add_var_js('ocultar', kernel::traductor()->trans('inicio_docente.ocultar'));
    }

    function get_lista_mesas()
    {
        $mesas = $this->controlador->codificador_modelo()->info__examenes_indice();
        $mesas->link('LINK', 'zona_examenes', 'home');
        return $mesas;
    }

    function get_cantidad_examenes()
    {
        return static::cantidad_examenes;
    }

}