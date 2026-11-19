<?php

use SIU\Chulupi\kernel;
/**
 * Emula la clase toba_instancia de Guaraní Gestión
 */
class toba_instancia
{
    static private $instancia;

    /**
     * @return toba_instancia
     */
    static function instancia($recargar=false)
    {
        if (!isset(static::$instancia) || $recargar) {
            static::$instancia = new toba_instancia();
        }
        return static::$instancia;
    }

    private function __construct()
    {

    }

    /**
     * @return \SIU\Chulupi\util\db\InterfaceDb
     */
    function get_db()
    {
        return kernel::db();
    }
}