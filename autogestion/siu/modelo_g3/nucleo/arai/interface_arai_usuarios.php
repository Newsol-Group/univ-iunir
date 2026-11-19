<?php

/**
 * Interface para consumir los WS de Araí Usuarios
 * @package Araí
 */
interface interface_arai_usuarios
{
    /*
     *  Drivers
     */

    const DRIVER_ARAI = 'arai';
    const DRIVER_EXT = 'ext';

    /**
     * @return bool TRUE si se puede conectar, FALSE caso contrario.
     */
    public function isConexionOk();

    /**
     * Servicio REST a Araí Usuarios, GET /usuarios/{identificador}:
     * Usuario de Araí.
     * @param string $usuario Identificador del usuario.
     * @throws guarani_error En caso de error
     * @return array Retorna datos de un usuario.
     */
    public function getUsuario($usuario);

    /**
     * Servicio REST a Araí Usuarios, GET /uuid/{identificador}:
     * Devuelve uid de un usuario.
     * Solo disponible a partir de API V2
     * @param string $identificador Identificador del usuario.
     * @throws guarani_error En caso de error
     * @return string uid de un usuario.
     */
    public function getUidUsuario($identificador);
}
