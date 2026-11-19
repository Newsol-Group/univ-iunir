<?php

namespace siu\operaciones\convocatorias_becas;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\guarani;

class pagelet_preaplicar extends pagelet
{

    function get_nombre()
    {
        return 'preaplicar';
    }

    /**
     * @return \siu\modelo\transacciones\becas
     */
    function modelo()
    {
        return guarani::becas();
    }

    function prepare()
    {
        $operacion = kernel::ruteador()->get_id_operacion();
        $this->data['url_aplicar'] = kernel::vinculador()->crear($operacion, 'aplicar', $this->data['hash_habilitacion']);
        $this->data['url_volver'] = kernel::vinculador()->crear($operacion, 'index');
        $this->data['datos_academicos'] = $this->modelo()->get_datos_academicos_formulario_by_persona($this->data['convocatoria']['formulario']);
        $this->data['requisitos'] = $this->modelo()->get_requisitos_habilitacion($this->data['convocatoria']['habilitacion']);
        $this->data['si'] = \comunes::si;
        $this->data['si_desc'] = \comunes::si_desc;
        $this->data['no'] = \comunes::no;
        $this->data['no_desc'] = \comunes::no_desc;
    }
}