<?php

/**
 * Clase personalizada para consumir el reemplazo de Araí Usuarios
 * @package Araí
 */
class ext_usuarios implements interface_arai_usuarios
{

    /**
     * Datos de un usuario
     * @param string $usuario Identificador del usuario.
     * @throws guarani_error En caso de error
     * @return array Retorna datos de un usuario.
     */
    public function getUsuario($usuario)
    {
        // Formato usuario de ejemplo
        return [
            'nombre' => 'Nombre 1',
            'apellido' => 'Apellido 1',
            'email' => 'usuario1@siu.edu.ar'
        ];
    }

    /**
     * @return bool TRUE si se puede conectar, FALSE caso contrario.
     */
    public function isConexionOk()
    {
        return true;
    }

    /**
     * Devuelve uid de un usuario.
     * @param string $identificador Identificador del usuario.
     * @throws guarani_error En caso de error
     * @return string uid de un usuario.
     */
    public function getUidUsuario($identificador)
    {
        return null;
    }
}
