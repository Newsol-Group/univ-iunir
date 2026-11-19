<?php

namespace siu\modelo\datos\db;

use SIU\Chulupi\kernel;

class boleto_estudiantil {

    /**
     * parametros: persona
     * cache: no
     * filas: 1
     */
    function datos_basicos_persona($parametros)
	{
		$sql = "SELECT  mdp_personas.persona,
                        mdp_personas.apellido,
                        mdp_personas.nombres,
                        mdp_personas_documentos.nro_documento,
                        mdp_tipo_documento.tipo_documento,
                        COALESCE(mdp_tipo_documento_pais.descripcion, mdp_tipo_documento.descripcion) as tipo_documento_desc ,
                        COALESCE(mdp_tipo_documento_pais.desc_abreviada, mdp_tipo_documento.desc_abreviada) as tipo_documento_desc_abrev
                
                FROM mdp_personas
                LEFT JOIN mdp_personas_documentos ON ((mdp_personas.persona = mdp_personas_documentos.persona) AND (mdp_personas.documento_principal = mdp_personas_documentos.documento))
                JOIN mdp_tipo_documento ON (mdp_personas_documentos.tipo_documento = mdp_tipo_documento.tipo_documento)
                JOIN mdp_tipo_documento_pais ON (mdp_personas_documentos.tipo_documento = mdp_tipo_documento_pais.tipo_documento AND mdp_personas_documentos.pais_documento = mdp_tipo_documento_pais.pais)
				WHERE mdp_personas.persona = {$parametros['persona']}";

		return kernel::db()->consultar_fila($sql);
	}

    /**
     * parametros: persona
     * cache: no
     * filas: 1
     */
    function tiene_solicitud($parametros)
    {

        $no = kernel::db()->quote(\comunes::no);
        $respuesta_aceptada = kernel::db()->quote(\boleto_estudiantil::RESPUESTA_ACEPTADA);
        $operacion_a = kernel::db()->quote(\boleto_estudiantil::OPERACION_A);

        $sql = "SELECT COUNT(1) AS tiene_solicitudes
                FROM mbe_solicitudes
                WHERE (mbe_solicitudes.procesado = $no OR (mbe_solicitudes.respuesta = $respuesta_aceptada AND mbe_solicitudes.operacion = $operacion_a))
                  AND mbe_solicitudes.persona = {$parametros['persona']}
                GROUP BY mbe_solicitudes.fecha_solicitud
                ORDER BY mbe_solicitudes.fecha_solicitud DESC
                LIMIT 1";

        $datos =  kernel::db()->consultar_fila($sql);
        return (isset($datos['tiene_solicitudes']) && $datos['tiene_solicitudes'] > 0)? true : false;
    }


    /**
     * parametros: persona
     * cache: no
     * filas: 1
     */
    function ultima_solicitud_pendiente($parametros)
    {

        $no = kernel::db()->quote(\comunes::no);
        $operacion_a = kernel::db()->quote(\boleto_estudiantil::OPERACION_A);
        $respuesta_nueva = kernel::db()->quote(\boleto_estudiantil::RESPUESTA_NUEVA);

        $sql = "SELECT  mbe_solicitudes.id, 
                        mbe_solicitudes.hash, 
                        mbe_solicitudes.url_formulario_sube, 
                        mbe_solicitudes.persona, 
                        mbe_solicitudes.fecha_solicitud
                FROM mbe_solicitudes
                WHERE mbe_solicitudes.procesado = $no
                  AND mbe_solicitudes.operacion = $operacion_a
                  AND mbe_solicitudes.respuesta = $respuesta_nueva
                  AND mbe_solicitudes.persona = {$parametros['persona']}
                ORDER BY mbe_solicitudes.fecha_solicitud DESC
                LIMIT 1";

        return kernel::db()->consultar_fila($sql);
    }

    /**
     * parametros: persona
     * cache: no
     * filas: 1
     */
    function cumple_condicion_be($parametros)
    {
        $cumple_condicion = false;
        $mensaje = "";
		
        // Verifica si cumple las condiciones para solicitar el beneficio de Boleto Estudiantil.
        $cumple_condicion = \boleto_estudiantil::verificar_beneficio($parametros['persona']);

        if ($cumple_condicion){
            $status = 1;
            $mensaje = kernel::traductor()->trans('boleto_estudiantil.alumno_cumple_requisitos_academicos');
        }
        else {
            $status = -1;
            $mensaje = kernel::traductor()->trans('boleto_estudiantil.alumno_no_cumple_requisitos_academicos');
        }

        $this->registrar_intentos($parametros['persona'], $status, $mensaje);

        return array(
            'cumple' => $cumple_condicion,
            'condicion' => \boleto_estudiantil::CONDICION_NO_INGRESANTE,
            'mensaje' => $mensaje
        );
    }

    protected function registrar_intentos($persona, $status, $mensaje)
    {

        $status = kernel::db()->quote($status);
        $mensaje = kernel::db()->quote($mensaje);

        $sql = "INSERT INTO mbe_intentos (
                  persona,
                  status_control,
                  mensaje
                  )
                VALUES (
                {$persona},
                {$status},
                {$mensaje}
                )";
        kernel::db()->ejecutar($sql);
    }

    /**
     * parametros: hash, url_formulario_sube, persona, condicion, observaciones
     * cache: no
     * filas: 1
     */
    function insertar_solicitud($parametros)
    {

        $operacion_a = kernel::db()->quote(\boleto_estudiantil::OPERACION_A);
        $nueva = kernel::db()->quote(\boleto_estudiantil::RESPUESTA_NUEVA);

        $sql = "INSERT INTO mbe_solicitudes (
                    hash, 
                    url_formulario_sube, 
                    persona,
                    condicion, 
                    observaciones, 
                    respuesta,
                    operacion
                )
                VALUES 
                (
                    {$parametros['hash']}, 
                    {$parametros['url_formulario_sube']}, 
                    {$parametros['persona']},
                    {$parametros['condicion']}, 
                    {$parametros['observaciones']}, 
                    $nueva,
                    $operacion_a
                )";

        return kernel::db()->ejecutar($sql);
    }

    /**
     * parametros: persona
     * cache: no
     * filas: n
     */
    function listado_solicitudes($parametros)
    {
        $sql = "SELECT  mbe_solicitudes.id, 
                        mbe_solicitudes.fecha_solicitud,
                        mbe_solicitudes.operacion,
                        mbe_solicitudes.procesado,
                        mbe_solicitudes.respuesta,
                        mbe_solicitudes.respuesta_desc 
                FROM mbe_solicitudes 
                WHERE mbe_solicitudes.persona = {$parametros['persona']} 
                ORDER BY mbe_solicitudes.fecha_solicitud ASC";

        return kernel::db()->consultar($sql);
    }
}