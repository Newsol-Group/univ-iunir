<?php

/**
 * Clase nucleo para Beneficio de Boleto Estudiantil
 * @see https://colab.siu.edu.ar/trac/guarani3/wiki/contrib/boleto_estudiantil
 * @package Boleto Estudiantil
 */
class co_boleto_estudiantil
{

    /**
     * Verifica si cumple las condiciones para solicitar el beneficio de Boleto Estudiantil.
     *
     * @param int $persona     ID de persona.
     *
     * @return bool Retorna true si cumple las condiciones, false en caso contrario.
     */
    function verificar_beneficio($persona)
    {
        $si             = comunes::si;
        $calidad_activo = alumno::calidad_activo;
        $sql            = "
				SELECT EXISTS(
                                    SELECT 1
                                    FROM
                                            sga_alumnos
                                    WHERE
                                            sga_alumnos.persona = {$persona}
                                            AND sga_alumnos.regular = '{$si}'
                                            AND sga_alumnos.calidad = '{$calidad_activo}'
                                ) AS cumple_condicion";

        $rs = guarani_db::consultar_fila($sql, null, false);
        return $rs['cumple_condicion'];
    }

    /**
     * Retorna la cantidad de propuestas donde es alumno regular y activo.
     *
     * @param int $persona     ID de persona.
     *
     * @return int Retorna la cantidad de propuestas donde es alumno regular y activo.
     */
    function get_cant_propuestas_alumno_regular_activo($persona)
    {
        $si             = comunes::si;
        $calidad_activo = alumno::calidad_activo;
        $sql            = "
				SELECT
					COUNT(1) AS cant_regular_activo
				FROM
					sga_alumnos
				WHERE
					sga_alumnos.persona = {$persona}
					AND sga_alumnos.regular = '{$si}'
					AND sga_alumnos.calidad = '{$calidad_activo}'";

        $rs = guarani_db::consultar_fila($sql, null, false);
        return $rs['cant_regular_activo'];
    }
    
    /**
     * Retorna las solicitudes pendientes a ser notificadas a SUBE.
     *
     * @param bool $solo_bajas     true para procesar solo las bajas, por defecto procesa todas.
     *
     * @return array Retorna las solicitudes pendientes a ser notificadas a SUBE.
     */
    function get_solicitudes_pendientes($solo_bajas = false)
    {
        $operacion_a = boleto_estudiantil::OPERACION_A;
        $operacion_b = boleto_estudiantil::OPERACION_B;
        $no          = comunes::no;

        $where_altas = "";
        // Para dar de bajas y altas
        if (!$solo_bajas) {
            $where_altas = ", '{$operacion_a}'";
        }

        $sql = "
				SELECT
					sol.id,
					sol.persona,
					sol.operacion,
					p.tipo_documento,
					p.nro_documento
				FROM
					vw_be_ultimas_solicitudes as sol,
					vw_personas as p
				WHERE
					p.persona = sol.persona
					AND sol.procesado = '{$no}'
					AND sol.operacion IN ('{$operacion_b}'{$where_altas})
				";
        return guarani_db::consultar($sql, null, false);
    }

    /**
     * Retorna las solicitudes procesadas.
     *
     * @param bool $solo_bajas     true para procesar solo las bajas, por defecto procesa todas.
     *
     * @return array Retorna las solicitudes procesadas.
     */
    function get_solicitudes_procesadas($solo_bajas = false)
    {
        $operacion_a           = boleto_estudiantil::OPERACION_A;
        $operacion_b           = boleto_estudiantil::OPERACION_B;
        $respuesta_aceptada    = boleto_estudiantil::RESPUESTA_ACEPTADA;
        $respuesta_actualizada = boleto_estudiantil::RESPUESTA_ACTUALIZADA;
        $si                    = comunes::si;

        $where_altas = "";
        // Para dar de bajas y altas
        if (!$solo_bajas) {
            $where_altas = " OR (operacion = '{$operacion_b}')";
        }

        $sql = "SELECT persona, hash, url_formulario_sube, id, operacion
				FROM vw_be_ultimas_solicitudes
				WHERE procesado = '{$si}' AND
				((operacion = '{$operacion_a}' AND respuesta IN ('{$respuesta_aceptada}', '{$respuesta_actualizada}')){$where_altas})";

        return guarani_db::consultar($sql, null, false);
    }

}