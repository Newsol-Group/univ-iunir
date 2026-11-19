<?php

namespace siu\modelo\datos\db;

use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;
use siu\modelo_g3\beca;

class becas 
{
    /**
	 * parametros: persona, vigentes
	 * cache: memoria
	 * filas: n
	 */
	function get_convocatorias_by_persona($parametros)
	{
		$si = kernel::db()->quote(\comunes::si); 
		if ($parametros['vigentes'] == $si) {
			// Relevamientos vigentes para constestar
			$where = " AND gde_habilitaciones.activo = $si
				AND COALESCE(gde_becas_reapertura.fecha_respuesta_original, gde_encuestas_pendientes.fecha_respuesta) IS NULL
				AND CURRENT_DATE BETWEEN gde_habilitaciones.fecha_desde AND COALESCE(gde_becas_reapertura.fecha_hasta, gde_habilitaciones.fecha_hasta) ";
		} else {
			// Relevamientos vigentes y no vigentes que hayan sido constestados por el alumno.
			$where = " AND COALESCE(gde_becas_reapertura.fecha_respuesta_original, gde_encuestas_pendientes.fecha_respuesta) IS NOT NULL ";
		}
		$sql = "SELECT		
				gde_encuestas_pendientes.respuesta,
				gde_formularios.formulario,
				gde_habilitaciones.habilitacion,
				gde_encuestas_pendientes.persona,
				COALESCE(gde_becas_reapertura.fecha_respuesta_original, gde_encuestas_pendientes.fecha_respuesta) AS fecha_respuesta,
                                TO_CHAR(COALESCE(gde_becas_reapertura.fecha_respuesta_original, gde_encuestas_pendientes.fecha_respuesta), '".\comunes::formato_fecha_visual."') AS fecha_respuesta_display,
				gde_encuestas_pendientes.hash,
				gde_habilitaciones.descripcion,
				gde_habilitaciones.kolla_id_habilitacion,
				gde_habilitaciones.kolla_unidad_gestion,
				gde_habilitaciones.tehuelche_unidad_gestion,
				gde_habilitaciones.becas_convocatoria,
				gde_habilitaciones.fecha_desde,
				TO_CHAR(gde_habilitaciones.fecha_desde, '".\comunes::formato_fecha_visual."') AS fecha_desde_display,
				gde_habilitaciones.fecha_hasta,
				TO_CHAR(gde_habilitaciones.fecha_hasta, '".\comunes::formato_fecha_visual."') AS fecha_hasta_display,
				gde_habilitaciones.fecha_evaluacion_ha_desde,
				TO_CHAR(gde_habilitaciones.fecha_evaluacion_ha_desde, '".\comunes::formato_fecha_visual."') AS fecha_evaluacion_ha_desde_display,
				gde_habilitaciones.fecha_evaluacion_ha_hasta,
				TO_CHAR(gde_habilitaciones.fecha_evaluacion_ha_hasta, '".\comunes::formato_fecha_visual."') AS fecha_evaluacion_ha_hasta_display,
				gde_becas_reapertura.reapertura,
				gde_becas_reapertura.fecha_hasta AS fecha_hasta_reapertura,
				TO_CHAR(gde_becas_reapertura.fecha_hasta, '".\comunes::formato_fecha_visual."') AS fecha_hasta_reapertura_display,
				(gde_becas_reapertura.reapertura IS NOT NULL) AND (gde_becas_reapertura.fecha_hasta >= CURRENT_DATE) AND (gde_encuestas_pendientes.fecha_respuesta IS NULL) AS reapertura_vigente
			FROM gde_encuestas_pendientes
			JOIN gde_formularios ON gde_encuestas_pendientes.formulario = gde_formularios.formulario
			JOIN gde_habilitaciones ON gde_formularios.habilitacion = gde_habilitaciones.habilitacion
			LEFT JOIN gde_becas_reapertura ON gde_becas_reapertura.respuesta = gde_encuestas_pendientes.respuesta
			
			WHERE gde_encuestas_pendientes.persona = {$parametros['persona']} 
			  AND gde_habilitaciones.tipo_relevamiento = '".\encuesta::tipo_relevamiento_becas."' 
			  AND gde_habilitaciones.kolla_sincronizado = $si
			$where
			ORDER BY gde_habilitaciones.fecha_desde";
		return kernel::db()->consultar($sql);
	}
	
	/**
	 * parametros: persona
	 * no_quote: persona
	 * cache: memoria
	 * filas: n
	 */
	function get_convocatorias_vigentes_by_persona($parametros)
	{
		$convocatorias = catalogo::consultar('becas', 'get_convocatorias_by_persona', array('persona' => $parametros['persona'], 'vigentes' => \comunes::si));
		
		$tehuelche_unidades_gestion = array_unique(array_column($convocatorias, 'tehuelche_unidad_gestion'));
		
		$convocatorias_tehuelche = [];
		foreach ($tehuelche_unidades_gestion as $tehuelche_unidad_gestion) {
			$convocatorias_rest = beca::get_convocatorias($tehuelche_unidad_gestion);

			foreach ($convocatorias_rest as $convocatoria_rest) {
				$convocatorias_tehuelche[$tehuelche_unidad_gestion][$convocatoria_rest['convocatoria']] = $convocatoria_rest;
			}
		}
			
		kernel::log()->add_debug("=== Convocatorias Tehuelche ===", $convocatorias_tehuelche);
		
		$convocatorias_final = [];
		foreach ($convocatorias as $convocatoria) {
			
			if (isset($convocatorias_tehuelche[$convocatoria['tehuelche_unidad_gestion']][$convocatoria['becas_convocatoria']])) {
				
				$convocatoria_tehuelche = $convocatorias_tehuelche[$convocatoria['tehuelche_unidad_gestion']][$convocatoria['becas_convocatoria']];
			
				$convocatoria['tehuelche_nombre'] = $convocatoria_tehuelche['nombre'];
				foreach ($convocatoria_tehuelche['llamados'] as $llamado) {
					if ($llamado['es_llamado_vigente']) {
						$becas_llamado_vigente = '';
						foreach ($llamado['becas'] as $beca) {
							$becas_llamado_vigente .= empty($becas_llamado_vigente) ? $beca['nombre'] : "/{$beca['nombre']}";
						}
					}
				}
				$convocatoria['tehuelche_becas'] = $becas_llamado_vigente;
			}
			
			$convocatorias_final[catalogo::generar_id($convocatoria['habilitacion'])] = $convocatoria;
		}
			
		return $convocatorias_final;
	}

	/**
	 * parametros: persona
	 * no_quote: persona
	 * cache: memoria
	 * filas: n
	 */
	function get_convocatorias_aplicadas_by_persona($parametros)
	{
		$convocatorias = catalogo::consultar('becas', 'get_convocatorias_by_persona', array('persona' => $parametros['persona'], 'vigentes' => \comunes::no));
		$unidades_gestion_tehuelche = array();
		foreach ($convocatorias as $convocatoria) {
			$unidades_gestion_tehuelche[$convocatoria['tehuelche_unidad_gestion']][] = $convocatoria['becas_convocatoria'];
		}
		$convocatorias_tehuelche = array();
		foreach ($unidades_gestion_tehuelche as $unidad_gestion_tehuelche => $lista_ids) {
			$convocatorias_rest = beca::get_lista_convocatorias($unidad_gestion_tehuelche, implode(',', $lista_ids));
			foreach ($convocatorias_rest as $convocatoria_rest) {
				$convocatorias_tehuelche[$unidad_gestion_tehuelche][$convocatoria_rest['convocatoria']] = $convocatoria_rest;
			}
		}
		kernel::log()->add_debug("=== Convocatorias Tehuelche ===", $convocatorias_tehuelche);
		$convocatorias_final = array();
		foreach ($convocatorias as $convocatoria) {
			if (isset($convocatorias_tehuelche[$convocatoria['tehuelche_unidad_gestion']][$convocatoria['becas_convocatoria']])) {
				$convocatoria_tehuelche = $convocatorias_tehuelche[$convocatoria['tehuelche_unidad_gestion']][$convocatoria['becas_convocatoria']];
				$convocatoria['tehuelche_nombre'] = $convocatoria_tehuelche['nombre'];
				$convocatoria['tehuelche_informacion_adicional'] = $convocatoria_tehuelche['informacion_adicional'];
			}
			$convocatorias_final[catalogo::generar_id($convocatoria['habilitacion'])] = $convocatoria;
		}
		return $convocatorias_final;
	}
	
	/**
	 * parametros: convocatoria, persona
	 * no_quote: convocatoria, persona
	 * cache: memoria
	 * filas: n
	 */
	function get_convocatoria_impresion($parametros)
	{
		
		$where = "mdp_personas.persona = {$parametros['persona']}";
		
		$datos = \toba::consulta_php('co_encuestas')->get_datos_aspirantes_convocatoria($parametros['convocatoria'], $where);
		
		return current($datos);
	}


	/**
	 * parametros:  persona, cuil
	 * no_quote:  persona, cuil
	 * cache: memoria
	 * filas: n
	 */
	function get_aspirante_solicitudes($parametros)
	{
		$convocatorias = catalogo::consultar('becas', 'get_convocatorias_by_persona', array('persona' => $parametros['persona'], 'vigentes' => \comunes::no));
		$unidades_gestion_tehuelche = [];
		foreach ($convocatorias as $convocatoria) {
			$unidades_gestion_tehuelche[$convocatoria['tehuelche_unidad_gestion']][] = $convocatoria['becas_convocatoria'];
		}
		$convocatorias_tehuelche = [];
		foreach ($unidades_gestion_tehuelche as $unidad_gestion_tehuelche => $lista_ids) {
			$convocatorias_rest = beca::get_aspirante_solicitudes($unidad_gestion_tehuelche, $parametros['cuil']);
			
			foreach ($convocatorias_rest as $convocatoria_rest) {
				$convocatorias_tehuelche[$unidad_gestion_tehuelche][$convocatoria_rest['convocatoria']] = $convocatoria_rest;
			}
		}
		kernel::log()->add_debug("=== Convocatorias Tehuelche ===", $convocatorias_tehuelche);
		$convocatorias_final = [];
		foreach ($convocatorias as $convocatoria) {
			if (isset($convocatorias_tehuelche[$convocatoria['tehuelche_unidad_gestion']][$convocatoria['becas_convocatoria']])) {
				$convocatoria_tehuelche = $convocatorias_tehuelche[$convocatoria['tehuelche_unidad_gestion']][$convocatoria['becas_convocatoria']];
                                $convocatorias_final[catalogo::generar_id($convocatoria['habilitacion'])] = $convocatoria_tehuelche;
			}
		}
		return $convocatorias_final;

	}

        /**
	 * parametros: reapertura
	 * no_quote: reapertura
	 * cache: memoria
	 * filas: n
	 */
	function get_datos_reapertura($parametros)
	{

            $sql = "SELECT
                    respuesta,
                    observaciones,
                    fecha_hasta,
                    fecha_respuesta_original,
                    fecha_reapertura,
                    ARRAY_TO_JSON(datos_censales) AS datos_censales,
                    ARRAY_TO_JSON(requisitos) AS requisitos
                    FROM gde_becas_reapertura
                    WHERE reapertura = {$parametros['reapertura']};";

            $fila = kernel::db()->consultar_fila($sql);

            $datos_censales = [];
            // Si hay datos censales para la reapertura
            if (!is_null($fila['datos_censales'])) {
                // Convierto el string del JSON a UTF-8 sino se rompe cuando hay caracteres especiales
                $datos_censales = iconv("ISO-8859-1", "UTF-8", $fila['datos_censales']);
                $datos_censales = json_decode($datos_censales, true);
            }

            $requisitos = [];
            // Si hay requisitos para la reapertura
            if (!is_null($fila['requisitos'])) {
                // Convierto el string del JSON a UTF-8 sino se rompe cuando hay caracteres especiales
                $requisitos = iconv("ISO-8859-1", "UTF-8", $fila['requisitos']);
                $requisitos = json_decode($requisitos, true);
            }

            $fila['datos_censales'] = $datos_censales;
            $fila['requisitos'] = $requisitos;

            return $fila;
	}

    /**
     * parametros: habilitacion, persona
     * no_quote: habilitacion, persona
     * cache: no
     * filas: 1
     */
    function insertar_requisitos_convocatoria($parametros)
    {
        $sql = "
            INSERT INTO gde_alumnos_requisitos (habilitacion, requisito, persona, fecha_carga, id_documento, copiado) (

                SELECT
                    gde_becas_requisitos.habilitacion,
                    gde_becas_requisitos.requisito,
                    {$parametros['persona']} AS persona,
                    CASE WHEN sga_requisitos_digitales.id_documento IS NULL
                        THEN null
                        ELSE CURRENT_TIMESTAMP
                    END fecha_carga,
                    sga_requisitos_digitales.id_documento,
                    CASE WHEN sga_requisitos_digitales.id_documento IS NULL
                        THEN '".\comunes::no."'
                        ELSE '".\comunes::si."'
                    END copiado
                FROM gde_becas_requisitos
                LEFT JOIN sga_requisitos_digitales ON (gde_becas_requisitos.requisito = sga_requisitos_digitales.requisito AND sga_requisitos_digitales.persona = {$parametros['persona']})
                LEFT JOIN sga_requisitos_presentados ON (sga_requisitos_presentados.requisito_digital = sga_requisitos_digitales.requisito_digital)
                WHERE gde_becas_requisitos.habilitacion = {$parametros['habilitacion']}
                ORDER BY sga_requisitos_presentados.fecha_presentacion DESC

            ) 
            -- Cuando vuelve a aplicar a la convocatoria no se debe actualizar nada
            ON CONFLICT(habilitacion, requisito, persona) DO NOTHING
            RETURNING habilitacion, requisito, persona, id_documento
       ";

        return kernel::db()->consultar($sql);
    }

    /**
     * parametros: habilitacion, persona
     * no_quote: habilitacion, persona
     * cache: no
     * filas: 1
     */
    function marcar_requisitos_convocatoria_no_rechazados($parametros)
    {

        $sql = "
            UPDATE gde_alumnos_requisitos
            SET rechazado = '".\comunes::no."'
            WHERE persona = {$parametros['persona']}
            AND habilitacion = {$parametros['habilitacion']}
            ";
        return kernel::db()->ejecutar($sql);
    }

        /**
	 * parametros: habilitacion, persona
	 * no_quote: habilitacion, persona
	 * cache: memoria
	 * filas: n
	 */
	function get_requisitos_convocatoria_by_persona($parametros)
	{
            $sql = "SELECT
                        sga_requisitos.requisito,
                        sga_requisitos.nombre,
                        sga_requisitos.descripcion,
                        sga_requisitos.tipo_documento_digital,
                        gde_becas_requisitos.habilitacion,
                        gde_becas_requisitos.obligatorio,
                        gde_alumnos_requisitos.fecha_alta,
                        gde_alumnos_requisitos.fecha_carga,
                        gde_alumnos_requisitos.id_documento,
                        CASE WHEN gde_alumnos_requisitos.no_posee IS NOT NULL
                            THEN gde_alumnos_requisitos.no_posee
                            ELSE 0
                        END no_posee,
                        CASE WHEN gde_alumnos_requisitos.rechazado IS NOT NULL
                            THEN gde_alumnos_requisitos.rechazado
                            ELSE '".\comunes::no."'
                        END rechazado,
                        CASE WHEN gde_alumnos_requisitos.id_documento IS NOT NULL
                            THEN '".\comunes::si."'
                            ELSE '".\comunes::no."'
                        END presentado,
                        CASE
                          WHEN gde_alumnos_requisitos.rechazado = '".\comunes::si."' THEN '".\beca::REQUISITO_RECHAZADO."'
                          WHEN gde_alumnos_requisitos.no_posee = 1 THEN '".\beca::REQUISITO_NO_POSEE."'
                          WHEN gde_alumnos_requisitos.id_documento IS NOT NULL THEN '".\beca::REQUISITO_PRESENTADO."'
                          ELSE '".\beca::REQUISITO_NO_PRESENTADO."'
                        END estado,
                        gde_alumnos_requisitos.copiado
                    FROM gde_alumnos_requisitos
                    JOIN gde_becas_requisitos ON (gde_becas_requisitos.habilitacion = gde_alumnos_requisitos.habilitacion AND gde_becas_requisitos.requisito = gde_alumnos_requisitos.requisito)
                    JOIN sga_requisitos ON (sga_requisitos.requisito = gde_becas_requisitos.requisito)
                    WHERE   gde_alumnos_requisitos.habilitacion = {$parametros['habilitacion']}
                    AND     gde_alumnos_requisitos.persona = {$parametros['persona']}
                    -- Primero los obligatorios ordenados por nombre
                    ORDER BY gde_becas_requisitos.obligatorio DESC, sga_requisitos.nombre";

            $datos = kernel::db()->consultar($sql);
            return array_combine(array_column($datos, 'requisito'), $datos);
	}

    /**
     * parametros: habilitacion, requisito, persona, id_documento, extension, no_posee
     * no_quote: habilitacion, requisito, persona, id_documento, extension, no_posee
     * param_null: id_documento, extension
     * cache: no
     * filas: 1
     */
    function upsert_requisito_aspirante($parametros)
    {
        $fecha_carga = 'null';
        $id_documento = 'null';
        $extension = 'null';
        // Si posee documento
        if (!is_null($parametros['id_documento'])) {
            $fecha_carga = 'CURRENT_TIMESTAMP';
            $id_documento = kernel::db()->quote($parametros['id_documento']);
            $extension = kernel::db()->quote($parametros['extension']);
        }

        $sql = "
            INSERT INTO gde_alumnos_requisitos
            (habilitacion, requisito, persona, fecha_carga, id_documento, extension, no_posee, copiado)
            VALUES
            ({$parametros['habilitacion']}, {$parametros['requisito']}, {$parametros['persona']}, $fecha_carga, $id_documento, $extension, {$parametros['no_posee']}, '".\comunes::no."')
            ON CONFLICT(habilitacion, requisito, persona)
            DO UPDATE SET
              fecha_carga = EXCLUDED.fecha_carga,
              id_documento = EXCLUDED.id_documento,
              extension = EXCLUDED.extension,
              no_posee = EXCLUDED.no_posee,
              copiado = EXCLUDED.copiado
       ";

        return kernel::db()->ejecutar($sql);
    }

    /**
     * parametros: habilitacion, requisito, persona
     * no_quote: habilitacion, requisito, persona
     * cache: no
     * filas: 1
     */
    function eliminar_requisito_aspirante($parametros)
    {
        $sql = "UPDATE gde_alumnos_requisitos
                    SET fecha_carga = null, id_documento = null, extension = null, copiado = '".\comunes::no."'
                WHERE   habilitacion = {$parametros['habilitacion']}
                        AND requisito = {$parametros['requisito']}
                        AND persona = {$parametros['persona']}";

        return kernel::db()->ejecutar($sql);
    }

    /**
     * parametros: habilitacion
     * no_quote: habilitacion
     * cache: memoria
     * filas: 1
     */
    function get_info_habilitacion($parametros)
    {
        $sql = "SELECT
                    habilitacion,
                    titulo,
                    tipo_relevamiento,
                    becas_convocatoria,
                    tipo,
                    alcance,
                    encuestado,
                    descripcion,
                    fecha_desde,
                    fecha_hasta,
                    tehuelche_unidad_gestion
                FROM gde_habilitaciones
                WHERE habilitacion = {$parametros['habilitacion']}";

        $datos = kernel::db()->consultar_fila($sql);

        // Recupero info de la convocatoria de Tehuelche
        try {
            $datos['convocatoria'] = beca::get_convocatoria($datos['tehuelche_unidad_gestion'], $datos['becas_convocatoria']);
        } catch (\Exception $e) {
            // Si falla devuelvo array vacío
            $datos['convocatoria'] = [];
        }

        return $datos;
    }

    /**
     * parametros: requisito
     * no_quote: requisito
     * cache: memoria
     * filas: 1
     */
    function get_tipo_documento_digital($parametros)
    {
        $sql = "SELECT tipo_documento_digital FROM sga_requisitos WHERE requisito = {$parametros['requisito']}";
        $fila = kernel::db()->consultar_fila($sql);
        return $fila['tipo_documento_digital'] ?? \RDITipos::RECURSO;
    }

     /**
     * parametros: habilitacion, requisito, persona, extension
     * no_quote: habilitacion, requisito, persona
     * cache: no
     * filas: 1
     */
    function update_extension_requisitos_convocatoria($parametros)
    {

        $sql = "
            UPDATE gde_alumnos_requisitos
            SET extension = {$parametros['extension']}
            WHERE habilitacion = {$parametros['habilitacion']}
            AND requisito = {$parametros['requisito']}
            AND persona = {$parametros['persona']}
            ";
        return kernel::db()->ejecutar($sql);
    }

    /**
     * parametros: habilitacion
     * no_quote: habilitacion
     * cache: memoria
     */
    public function get_requisitos_habilitacion($parametros)
    {
        return \toba::consulta_php('co_encuestas')->get_requisitos_habilitacion($parametros['habilitacion']);
    }

	/**
	 * parametros: formulario, persona
	 * no_quote: formulario, persona
	 * cache: memoria
	 * filas: n
	 */
	function get_datos_academicos_formulario_by_persona($parametros)
	{
		$sql = "SELECT
				DISTINCT sga_propuestas.propuesta,
				sga_propuestas.nombre AS propuestas_nombre,
				ROUND(f_promedio(sga_alumnos.alumno, gde_habilitaciones.fecha_evaluacion_ha_hasta, NULL, '".\comunes::si."', gde_habilitaciones.fecha_evaluacion_ha_desde), 2) AS promedio_con_aplazos,
				ROUND(f_promedio(sga_alumnos.alumno, gde_habilitaciones.fecha_evaluacion_ha_hasta, NULL, '".\comunes::no."', gde_habilitaciones.fecha_evaluacion_ha_desde), 2) AS promedio_sin_aplazos,
				(
					SELECT COUNT(DISTINCT vw_hist_academica_basica.elemento)
					FROM vw_hist_academica_basica
					WHERE alumno = sga_alumnos.alumno
					AND resultado = '".\escalas_notas::resultado_aprobado."'
					AND (gde_habilitaciones.fecha_evaluacion_ha_desde IS NULL OR fecha >= gde_habilitaciones.fecha_evaluacion_ha_desde)
					AND fecha <= gde_habilitaciones.fecha_evaluacion_ha_hasta
				) AS cantidad_actividades_aprobadas
				FROM gde_encuesta_pendiente_propuestas
				JOIN sga_alumnos ON (gde_encuesta_pendiente_propuestas.persona = sga_alumnos.persona AND gde_encuesta_pendiente_propuestas.propuesta = sga_alumnos.propuesta)
				JOIN sga_propuestas ON (gde_encuesta_pendiente_propuestas.propuesta = sga_propuestas.propuesta)
				JOIN gde_formularios ON (gde_encuesta_pendiente_propuestas.formulario = gde_formularios.formulario)
				JOIN gde_habilitaciones ON (gde_formularios.habilitacion = gde_habilitaciones.habilitacion)
				WHERE	gde_encuesta_pendiente_propuestas.formulario = {$parametros['formulario']}
				AND		gde_encuesta_pendiente_propuestas.persona = {$parametros['persona']}
				-- No listo Cursos de Ingreso ni propuestas de tipo Curso
				-- AND     sga_propuestas.propuesta_tipo NOT IN (".\g3entidad_nucleo::subtipo_curso_ingreso.", ".\g3entidad_nucleo::subtipo_propuesta_curso.")
				ORDER BY propuestas_nombre";

		return kernel::db()->consultar($sql);
	}

	/**
	 * parametros: habilitacion, persona
	 * no_quote: habilitacion, persona
	 * cache: no
	 * filas: 1
	 */
	function actualizar_datos_academicos_aspirante_becas($parametros)
	{
		$sql = "SELECT * FROM f_becas_actualizar_datos_aspirante({$parametros['habilitacion']}, {$parametros['persona']});";
		return kernel::db()->consultar_fila($sql);
	}
}