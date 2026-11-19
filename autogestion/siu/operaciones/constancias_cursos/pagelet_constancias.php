<?php
namespace siu\operaciones\constancias_cursos;

use SIU\Chulupi\interfaz\pagelet;

class pagelet_constancias extends pagelet
{
    function get_nombre()
    {
        return 'constancias';
    }

    /**
     * Shortcut al modelo
     * @return \siu\modelo\transacciones\constancias_cursos
     */
    protected function modelo()
    {
        return $this->controlador->modelo();
    }

    function prepare()
    {
        $this->data['constancias'] = $this->modelo()->info__constancias();
    }
}