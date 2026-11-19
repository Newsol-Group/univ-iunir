<?php

/**
 * Manipulación de Criptografía para PostgreSQL
 * 
 * @package Centrales
 * @subpackage Utilidades
 */
class guarani_pgcrypto
{

    /**
     * Encripta dato usando la función pgp_sym_encrypt de pgcrypto
     * 
     * @see https://www.postgresql.org/docs/current/pgcrypto.html
     * @see https://marcopeg.com/per-user-encryption-with-postgres/
     *
     * @param string $dato_plano Dato plano a encriptar.
     * 
     * @return string Dato encriptado.
     */
    static function encriptar($datoPlano)
    {
        $key = toba::db()->quote(static::getPgcryptoKey());

        $sql = "SELECT public.pgp_sym_encrypt('$datoPlano', $key)::text AS encrypt";
        $result = guarani_db::consultar_fila($sql);
        return $result['encrypt'];
    }

    /**
     * Desencripta dato usando la función pgp_sym_decrypt de pgcrypto
     * 
     * @see https://www.postgresql.org/docs/current/pgcrypto.html
     * @see https://marcopeg.com/per-user-encryption-with-postgres/
     *
     * @param string $dato_encriptado Dato ya encriptado.
     * 
     * @return string Dato desencriptado.
     */
    static function desencriptar($datoEncriptado)
    {

        $key = toba::db()->quote(static::getPgcryptoKey());

        // Reemplazo '\' por '\\'
        $datoEncriptado = str_replace("\\", "\\\\", $datoEncriptado);
        $sql = "SELECT public.pgp_sym_decrypt('$datoEncriptado'::bytea, $key) AS decrypt";
        $result = guarani_db::consultar_fila($sql);
        return $result['decrypt'];
    }

    /**
     * Arma el SQL para desencriptar una columna usando la función pgp_sym_decrypt de pgcrypto
     * 
     * @see https://www.postgresql.org/docs/current/pgcrypto.html
     * @see https://marcopeg.com/per-user-encryption-with-postgres/
     *
     * @param string $columna Columna a desencriptar.
     * @param string $alias Alias.
     * 
     * @return string SQL para desencriptar columna.
     */
    static function desencriptarColumnaSql($columna, $alias)
    {
        $key = toba::db()->quote(static::getPgcryptoKey());

        return "public.pgp_sym_decrypt($columna::bytea, $key) AS $alias";
    }

    /**
     * Retorna la clave de pgcrypto para encriptar/desencriptar los datos
     * 
     * @see https://www.postgresql.org/docs/current/pgcrypto.html
     * @see https://marcopeg.com/per-user-encryption-with-postgres/
     * 
     * @throws Exception Si la clave de pgcrypto no esta definida
     * 
     * @return string Clave de pgcrypto.
     */
    static function getPgcryptoKey()
    {
        // Si se trata de Autogestión
        if (\comunes::es_interfaz_autogestion()) {
            //$clave = \SIU\Chulupi\kernel::proyecto()->get_ttl_token();
        } else {// Si se trata de Gestión
            $clave = guarani_instalacion_conf::get_parametro_instalacion('pgcrypto_key', true);
        }

        return $clave;
    }
}
