<?php

/**
 * Clase nucleo para Beneficio de Boleto Estudiantil
 * @see https://colab.siu.edu.ar/trac/guarani3/wiki/contrib/boleto_estudiantil
 * @package Boleto Estudiantil
 */
class act_boleto_estudiantil_nucleo
{

    /**
     * Actualiza el beneficio.
     *
     * @param int $id
     * @param string $msg
     *
     * @return void
     */
    function actualizar_beneficio($id, $msg)
    {
        $si        = comunes::si;
        $respuesta = boleto_estudiantil::RESPUESTA_ACTUALIZADA;
        $sql       = "UPDATE mbe_solicitudes SET respuesta_fecha = CURRENT_TIMESTAMP, respuesta_desc = '{$msg}', respuesta = '{$respuesta}', procesado = '{$si}' WHERE id = '{$id}';";
        guarani_db::ejecutar($sql);
        toba::logger()->info('************ Se actualizó el beneficio con éxito. ************');
    }

    /**
     * Da de baja el beneficio.
     *
     * @param int $persona
     * @param int $solicitud_id
     * @param string $tid
     * @param string $url_formulario_sube
     * @param string $mensaje
     *
     * @return void
     */
    function dar_baja_beneficio($persona, $solicitud_id, $tid, $url_formulario_sube, $mensaje)
    {
        $operacion_b = boleto_estudiantil::OPERACION_B;
        $pendiente   = boleto_estudiantil::RESPUESTA_PENDIENTE;
        $no          = comunes::no;

        $sql = "INSERT INTO mbe_solicitudes (persona, hash, url_formulario_sube, fecha_solicitud, condicion, observaciones, respuesta, operacion, procesado) VALUES
                                            ({$persona}, 'BAJA:{$solicitud_id};{$tid}', '{$url_formulario_sube}', CURRENT_TIMESTAMP, null, '{$mensaje}', '{$pendiente}', '{$operacion_b}', '{$no}');";
        guarani_db::ejecutar($sql);
        toba::logger()->info('************ Se dio de baja el beneficio con éxito. ************');
    }

    /**
     * Da de alta el beneficio.
     *
     * @param int $persona
     * @param int $solicitud_id
     * @param string $tid
     * @param string $url_formulario_sube
     * @param string $mensaje
     * @param string $condicion
     *
     * @return void
     */
    function dar_alta_beneficio($persona, $solicitud_id, $tid, $url_formulario_sube, $mensaje, $condicion)
    {
        $operacion_a = boleto_estudiantil::OPERACION_A;
        $pendiente   = boleto_estudiantil::RESPUESTA_PENDIENTE;
        $no          = comunes::no;

        $sql = "INSERT INTO mbe_solicitudes (persona, hash, url_formulario_sube, fecha_solicitud, condicion, observaciones, respuesta, operacion, procesado) VALUES
                                            ({$persona}, 'ALTA:{$solicitud_id};{$tid}', '{$url_formulario_sube}', CURRENT_TIMESTAMP, '{$condicion}', '{$mensaje}', '{$pendiente}', '{$operacion_a}', '{$no}');";
        guarani_db::ejecutar($sql);
        toba::logger()->info('************ Se dio de alta el beneficio con éxito. ************');
    }
}