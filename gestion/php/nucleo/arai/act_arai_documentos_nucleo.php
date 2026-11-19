<?php

/**
 * Clase nucleo para SIU-Araí Documentos
 * @package SIU-Araí Documentos
 */
class act_arai_documentos_nucleo
{

    /**
     * Actualiza el estado del documento.
     *
     * @param string $id_documento_digital
     * @param boolean $autorizado
     * @param integer $estado_codigo
     * @param string|null $modelo
     * @param string|null $notificacion
     *
     * @return void
     */
    function actualizar_estado_documento($id_documento_digital, $autorizado, $estado_codigo, $modelo = null, $notificacion = null)
    {

        $id_documento_digital = toba::db()->quote($id_documento_digital);

        $autorizado = $autorizado ? 'true' : 'false';

        if (is_null($notificacion)) {
            $notificacion = 'NULL';
            $fecha_ultima_respuesta = 'NULL';
        } else {
            $notificacion = toba::db()->quote($notificacion);
            $fecha_ultima_respuesta = 'CURRENT_TIMESTAMP';
        }

        if (is_null($modelo)) {
            $modelo = 'NULL';
        } else {
            $modelo = toba::db()->quote($modelo);
        }

        $sql       = "INSERT INTO arai_documentos_estados
                        (id_documento_digital, notificacion, autorizado, estado_codigo, modelo, fecha_ultima_respuesta)
                        VALUES
                        ({$id_documento_digital}, {$notificacion}, {$autorizado}, {$estado_codigo}, {$modelo}, {$fecha_ultima_respuesta})
                        ON CONFLICT (id_documento_digital)
                        DO
                           UPDATE SET
                           notificacion = EXCLUDED.notificacion,
                           autorizado = EXCLUDED.autorizado,
                           estado_codigo = EXCLUDED.estado_codigo,
                           modelo = EXCLUDED.modelo,
                           fecha_ultima_respuesta = EXCLUDED.fecha_ultima_respuesta;";

        guarani_db::ejecutar($sql);
        toba::logger()->info('************ Se actualizó el estado del documento con éxito. ************');
    }

    /**
     * Elimina las autorizaciones del documento.
     *
     * @param string $id_documento_digital
     *
     * @return void
     */
    function eliminar_autorizaciones_documento($id_documento_digital)
    {

        $sql = "DELETE FROM arai_documentos_autorizaciones WHERE id_documento_digital = '{$id_documento_digital}';";

        guarani_db::ejecutar($sql);
        toba::logger()->info('************ Se eliminaron las autorizaciones del documento con éxito. ************');
    }

    /**
     * Agrega las autorizaciones del documento.
     *
     * @param string $id_documento_digital
     * @param array $autorizaciones
     *
     * @return void
     */
    function agregar_autorizaciones_documento($id_documento_digital, $autorizaciones)
    {

        $inserts = [];
        foreach ($autorizaciones as $autorizacion) {
            // Si el documento se rechaza pero luego se vuelve a reabrir se repiten las autorizaciones, aquí solo me quedo con las últimas
            $inserts[$autorizacion['usuario_sso']] = "('{$id_documento_digital}', '{$autorizacion['usuario_sso']}', '{$autorizacion['nivel']}', '{$autorizacion['estado']}', {$autorizacion['secuencia']}, '{$autorizacion['fecha']}')";
        }

        $inserts_sql = implode(', ', $inserts);
        $sql = "INSERT INTO arai_documentos_autorizaciones (id_documento_digital, usuario_sso, nivel, estado, secuencia, fecha) VALUES {$inserts_sql};";

        guarani_db::ejecutar($sql);
        toba::logger()->info('************ Se agregaron las autorizaciones del documento con éxito. ************');
    }

    /**
     * Vincula el documento de SIU-Araí a la tabla correspondiente.
     *
     * @param integer $id ID dentro de la tabla
     * @param string $estado_documento_digital Posibles valores 'P', 'E', 'F' o 'A'.
     * @param string $tabla Posibles valores 'sga_actas' o 'sga_equiv_tramite'.
     * @param string $columna Posibles valores 'id_acta' o 'equivalencia_tramite'.
     * @param string|null $uid_documento UUID del Documento de SIU-Araí.
     * @param array|null $parametros Datos de contexto en caso de error.
     *
     * @return void
     */
    function vincular_documento($id, $estado_documento_digital, $tabla, $columna, $uid_documento = null, $parametros = null)
    {

        $estado_documento_digital = toba::db()->quote($estado_documento_digital);

        if (is_null($uid_documento)) {
            $uid_documento = 'NULL';
        } else {
            $uid_documento = toba::db()->quote($uid_documento);
        }

        if (is_null($parametros)) {
            $parametros = 'NULL';
        } else {
            $parametros = json_encode($parametros);
            $parametros = toba::db()->quote($parametros);
        }

        $sql = "UPDATE {$tabla} 
                SET id_documento_digital = {$uid_documento},
                    estado_documento_digital = {$estado_documento_digital},
                    parametros_documento_digital = {$parametros}
                WHERE {$columna} = {$id};";

        guarani_db::ejecutar($sql);
        toba::logger()->info("************ Se vincularon documentos en la tabla '{$tabla}' con éxito. ************");

    }
}