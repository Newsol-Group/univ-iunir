<?php

/**
 * Clase nucleo para SIU-Araí Reportes
 * @package SIU-Araí Reportes
 */
class act_arai_reportes_nucleo
{

    /**
     * Guarda los reportes creados en SIU-Araí Reportes
     *
     * @param array $reportes
     *
     * @return array Resultado.
     */
    function guardar_reportes($reportes)
    {
        $resultado = [
            'hubo_error' => false,
            'msg_error' => ''
        ];

        try {
            toba::db()->abrir_transaccion();

            // Trunco la tabla arai_reportes
            // https://www.postgresqltutorial.com/postgresql-tutorial/postgresql-truncate-table/
            $sql = 'TRUNCATE TABLE arai_reportes;';
            guarani_db::ejecutar($sql);

            foreach ($reportes as $reporte) {

                $prefijo = \arai_reportes::PREFIJO_PERSONALIZACION_REPORTES;
                $proyecto = \arai_reportes::PROYECTO;
                $carpeta_nucleo = \arai_reportes::CARPETA_NUCLEO;
                $uri_archivo = $reporte['uri'];
                $id = basename($uri_archivo);

                $pos = strpos($uri_archivo, "/reportes/{$proyecto}/{$carpeta_nucleo}");
                // Si se trata de una personalización
                if ($pos === false) {
                    $id = preg_replace('/^'.$prefijo.'/', '', $id);
                    $column_list = 'id, uri_pers';
                    $value_list = "'{$id}', '{$uri_archivo}'";
                    $conflict_column = 'uri_pers';
                } else {// Si se trata del núcleo
                    $column_list = 'id, uri';
                    $value_list = "'{$id}', '{$uri_archivo}'";
                    $conflict_column = 'uri';
                }

                // Hago un UPSERT
                // https://www.postgresqltutorial.com/postgresql-tutorial/postgresql-upsert/
                $sql = "INSERT INTO arai_reportes ({$column_list})
                        VALUES ({$value_list})
                        ON CONFLICT (id)
                        DO UPDATE SET {$conflict_column} = EXCLUDED.{$conflict_column};";
                guarani_db::ejecutar($sql);
            }

            toba::db()->cerrar_transaccion();
            toba::logger()->info('************ Se guardaron todos los reportes con éxito. ************');
        } catch (\Exception $e) {
            $resultado = [
                'hubo_error' => true,
                'msg_error' => $e->getMessage()
            ];
            toba::db()->abortar_transaccion();
            toba::logger()->error("Ocurrió un error al guardar reportes: ".$e->getMessage());
        }

        return $resultado;
    }

    /**
     * Guarda los archivos creadas en SIU-Araí Reportes
     *
     * @param array $archivos
     *
     * @return array Resultado.
     */
    function guardar_archivos($archivos)
    {
        $resultado = [
            'hubo_error' => false,
            'msg_error' => ''
        ];

        try {
            toba::db()->abrir_transaccion();

            // Trunco la tabla arai_reportes_archivos
            // https://www.postgresqltutorial.com/postgresql-tutorial/postgresql-truncate-table/
            $sql = 'TRUNCATE TABLE arai_reportes_archivos;';
            guarani_db::ejecutar($sql);

            foreach ($archivos as $archivo) {

                $prefijo = \arai_reportes::PREFIJO_PERSONALIZACION_ARCHIVOS;
                $proyecto = \arai_reportes::PROYECTO;
                $carpeta_nucleo = \arai_reportes::CARPETA_NUCLEO;
                $uri_archivo = $archivo['uri'];
                $id = basename($uri_archivo);

                $pos = strpos($uri_archivo, "/reportes/{$proyecto}/{$carpeta_nucleo}");
                // Si se trata de una personalización
                if ($pos === false) {
                    $id = preg_replace('/^'.$prefijo.'/', '', $id);
                    $column_list = 'id, uri_pers';
                    $value_list = "'{$id}', '{$uri_archivo}'";
                    $conflict_column = 'uri_pers';
                } else {// Si se trata del núcleo
                    $column_list = 'id, uri';
                    $value_list = "'{$id}', '{$uri_archivo}'";
                    $conflict_column = 'uri';
                }

                // Hago un UPSERT
                // https://www.postgresqltutorial.com/postgresql-tutorial/postgresql-upsert/
                $sql = "INSERT INTO arai_reportes_archivos ({$column_list})
                        VALUES ({$value_list})
                        ON CONFLICT (id)
                        DO UPDATE SET {$conflict_column} = EXCLUDED.{$conflict_column};";
                guarani_db::ejecutar($sql);
            }

            toba::db()->cerrar_transaccion();
            toba::logger()->info('************ Se guardaron todas los archivos con éxito. ************');
        } catch (\Exception $e) {
            $resultado = [
                'hubo_error' => true,
                'msg_error' => $e->getMessage()
            ];
            toba::db()->abortar_transaccion();
            toba::logger()->error("Ocurrió un error al guardar archivos: ".$e->getMessage());
        }

        return $resultado;
    }
    
}