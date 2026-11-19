<?php

/**
 * Clase nucleo para SIU-Araí Reportes
 * @package SIU-Araí Reportes
 */
class co_arai_reportes
{

    /**
     * Retorna la URI de un reporte, primero busca el personalizado.
     *
     * @param string $id ID del reporte en la tabla arai_reportes.
     * @param bool $forzar_nucleo TRUE para que traiga directamente de la columna arai_reportes.uri.
     *
     * @return string|null URI de un reporte o null si el mismo no existe.
     */
    function get_uri_reporte($id, $forzar_nucleo = false)
    {
        // Reemplazo guiones medios - por guiones bajos _
        $id = str_replace('-', '_', $id);

        if ($forzar_nucleo) {
            $select = 'uri';
        } else {
            $select = 'COALESCE (uri_pers, uri) AS uri';
        }
        $sql = "
            SELECT {$select}
            FROM arai_reportes
            WHERE id = '{$id}'
        ";

        $rs = guarani_db::consultar_fila($sql, null, false);
        return $rs['uri'] ?? null;
    }

    /**
     * Retorna la URI de un archivo, primero busca el personalizado.
     *
     * @param string $id ID del archivo en la tabla arai_reportes_archivos.
     * @param bool $forzar_nucleo TRUE para que traiga directamente de la columna arai_reportes_archivos.uri.
     *
     * @return string|null URI de un archivo o null si el mismo no existe.
     */
    function get_uri_archivo($id, $forzar_nucleo = false)
    {
        // Reemplazo guiones medios - por guiones bajos _
        $id = str_replace('-', '_', $id);
        
        if ($forzar_nucleo) {
            $select = 'uri';
        } else {
            $select = 'COALESCE (uri_pers, uri) AS uri';
        }
        $sql = "
            SELECT {$select}
            FROM arai_reportes_archivos
            WHERE id = '{$id}'
        ";

        $rs = guarani_db::consultar_fila($sql, null, false);
        return $rs['uri'] ?? null;
    }
}