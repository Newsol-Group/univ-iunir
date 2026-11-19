<?php
/**
 *
 *
 * @package Matrícula
 * @subpackage Regularidad
 */
class co_readmisiones
{
	/**
	 *  Obtiene una lista de readmisiones según el filtro recibido
     */
	function get_listado($where='')
	{
		if ($where) {
			$where = " WHERE $where ";			        
		}
		
		$readmisiones_tipos = $this->get_opciones_readmisiones_tipos();
		$sql = "SELECT 		sga_readmisiones.readmision,
							sga_readmisiones.fecha,
							sga_readmisiones.fecha_vencimiento,
							".guarani_sql::get_case('sga_readmisiones', 'tipo', 'motivo_readmision', $readmisiones_tipos)."
				FROM		sga_readmisiones
				$where
				ORDER BY	sga_readmisiones.tipo,
							sga_readmisiones.fecha_vencimiento DESC
				";
		
		return guarani_db::consultar($sql);
	}
	
	/**
     * Retorna las opciones correspondientes a los motivos de readmisiones.
     */
    function get_opciones_readmisiones_tipos()
    {
		return array(array('valor' => 'S', 'descr' => 'Reincorporación por Error'),
					 array('valor' => 'R', 'descr' => 'Reincorporación por Resolución'));
    }
    
	function get_perdidas_regularidades($where='')
	{
		if ($where) {
			$where = " AND $where ";			        
		}
		
		$sql = "SELECT 		sga_perdida_regularidad.perdida_regularidad,
							sga_perdida_regularidad.fecha,
							sga_perdida_regularidad.anio_academico,
							sga_perdida_regularidad_causas.nombre AS causa_nombre,
							sga_documentos.documento_numero
				FROM		sga_perdida_regularidad LEFT JOIN sga_documentos ON sga_perdida_regularidad.documento = sga_documentos.documento,
							sga_perdida_regularidad_det,
							sga_perdida_regularidad_causas
				WHERE		sga_perdida_regularidad.perdida_regularidad = sga_perdida_regularidad_det.perdida_regularidad
				AND			sga_perdida_regularidad_det.causa_perdida_reg = sga_perdida_regularidad_causas.causa_perdida_reg
							$where
				ORDER BY	sga_perdida_regularidad.fecha DESC
				";
		
		return guarani_db::consultar($sql);
	}
	
	function get_fecha_perdida_regularidad($perdida_regularidad)
	{
		$perdida_regularidad = toba::db()->quote($perdida_regularidad);
		
		$sql = "SELECT 	sga_perdida_regularidad.fecha
				FROM	sga_perdida_regularidad
				WHERE	sga_perdida_regularidad.perdida_regularidad = $perdida_regularidad";
		
		$res = guarani_db::consultar_fila($sql);
		return $res['fecha'];
	}

	/**
	 * Retorna las causas de pérdida de regularidad ACTIVAS por propuesta y ubicación.
	 * 
	 * @param string $where
	 * @return array
	 */	
	function get_causas_perdida_regularidad_propuesta_ubicacion($propuesta, $ubicacion, $where='')
	{
		$propuesta = toba::db()->quote($propuesta);
		$ubicacion = toba::db()->quote($ubicacion);
		if ($where) {
			$where = "AND $where";			        
		}
		$sql = "SELECT	sga_perdida_regularidad_causas.causa_perdida_reg,
						sga_perdida_regularidad_causas.nombre,
						sga_perdida_regularidad_causas.descripcion,
						sga_perdida_regularidad_causas.regla,
						sga_reglas.php_clase
				FROM	sga_propuestas_regularidad
						JOIN sga_perdida_regularidad_causas	ON sga_propuestas_regularidad.causa_perdida_reg = sga_perdida_regularidad_causas.causa_perdida_reg
						JOIN sga_reglas ON sga_perdida_regularidad_causas.regla = sga_reglas.regla
				WHERE	sga_propuestas_regularidad.propuesta = $propuesta AND
						sga_propuestas_regularidad.ubicacion = $ubicacion AND
						sga_perdida_regularidad_causas.estado = '".comunes::estado_activo."'
						$where
		";
		return guarani_db::consultar($sql);
	}
	
	function get_readmisiones_alumnos($where='')
	{
		if ($where) {
			$where = " AND $where ";
		}
		
		$readmisiones_tipos = $this->get_opciones_readmisiones_tipos();
		$sql = "SELECT		sga_alumnos.persona,
							sga_alumnos.alumno,
							sga_alumnos.legajo,
							sga_perdida_regularidad.anio_academico,
							vw_personas.apellido_nombres,
							sga_perdida_regularidad.fecha		AS fecha_perdida_regularidad,
							to_char(sga_perdida_regularidad.fecha, '" . comunes::formato_fecha_visual . "') AS fecha_perdida_regularidad_visual,
							vw_perfil_de_datos_propuestas.nombre_abreviado AS propuesta_nombre,
							sga_readmisiones.anio_academico		AS anio_academico_readmision,
							sga_readmisiones.fecha				AS fecha_readmision,
							to_char(sga_readmisiones.fecha, '" . comunes::formato_fecha_visual . "') AS fecha_readmision_visual,
							sga_readmisiones.fecha_vencimiento,
							to_char(sga_readmisiones.fecha_vencimiento, '" . comunes::formato_fecha_visual . "') AS fecha_vencimiento_visual,
							sga_documentos.documento_numero,
							".guarani_sql::get_case('sga_readmisiones', 'tipo', 'tipo_readmision', $readmisiones_tipos)."
				
				FROM 		sga_alumnos,
							vw_personas,
							sga_perdida_regularidad LEFT JOIN sga_documentos ON sga_perdida_regularidad.documento = sga_documentos.documento,
							sga_readmisiones,
							vw_perfil_de_datos_propuestas
				
				WHERE 		sga_alumnos.persona = vw_personas.persona
				AND 		sga_alumnos.calidad = '".alumno::calidad_activo."'
				AND			sga_perdida_regularidad.perdida_regularidad = sga_readmisiones.perdida_regularidad
				AND			sga_perdida_regularidad.alumno = sga_alumnos.alumno
				AND			sga_alumnos.propuesta = vw_perfil_de_datos_propuestas.propuesta
							$where
				
				ORDER BY	vw_personas.apellido_nombres
				";
		
		return guarani_db::consultar($sql);
	}
	
	function get_causas_perdida_regularidad_cant_propuestas($filtro)
	{
		$where="";
		
		if ($filtro) {
			$where = ' AND '.$filtro.' ';
		}
		
		$sql = "
			SELECT
				sga_perdida_regularidad_causas.causa_perdida_reg,
				sga_perdida_regularidad_causas.nombre,
				(
					SELECT 	count(sga_propuestas_regularidad.causa_perdida_reg) 
					FROM 	sga_propuestas_regularidad  
					WHERE 	sga_propuestas_regularidad.causa_perdida_reg = sga_perdida_regularidad_causas.causa_perdida_reg
				) AS cant_propuestas
			FROM
				sga_perdida_regularidad_causas
			WHERE
				sga_perdida_regularidad_causas.estado = '" . comunes::estado_activo . "'
				$where
			ORDER BY	nombre
		";
		
		return guarani_db::consultar($sql);
	}
	
	function get_nombre_causa_perdida($id_causa)
	{
		$id_causa = toba::db()->quote($id_causa);
		
		$sql = "
				SELECT
					sga_perdida_regularidad_causas.nombre
				FROM
					sga_perdida_regularidad_causas
				WHERE				
					sga_perdida_regularidad_causas.causa_perdida_reg = " . $id_causa . "
				";
		
		$regla_nombre = guarani_db::consultar_fila($sql);
		
		return $regla_nombre;
	}
	
	function get_para_controlar()
    {
		return array(
			array('valor' => vencimiento_regularidad::para_controlar_regularidad_readmisiones, 'descr' => vencimiento_regularidad::para_controlar_regularidad_readmisiones_descr),
			array('valor' => vencimiento_regularidad::para_controlar_regularidad, 'descr' => vencimiento_regularidad::para_controlar_regularidad_descr),
			array('valor' => vencimiento_regularidad::para_controlar_readmisiones, 'descr' => vencimiento_regularidad::para_controlar_readmisiones_descr)
		);
    }

	/**
	 * Retorna los datos de la configuración de reglas para el cálculo de regularidad para un conjunto de propuestas en una ubicación.
	 * 
	 * @param array $propuestas
	 * @param int $ubicacion
	 * @return array
	 */
	function get_reglas_calculo_regularidad_propuestas_ubicacion($propuestas, $ubicacion)
	{
		$filtro_propuestas = guarani_sql::armar_condicion_in($propuestas, 'sga_propuestas_regularidad', 'propuesta');
		$ubicacion = toba::db()->quote($ubicacion);
		$sql = "SELECT		sga_propuestas_regularidad.propuesta,
							sga_propuestas_regularidad.causa_perdida_reg,
							sga_propuestas_regularidad.para_controlar,
							'('  || sga_propuestas.codigo || ') ' || sga_propuestas.nombre as propuesta_descr,
							sga_perdida_regularidad_causas.nombre as causa_nombre,
							CASE sga_propuestas_regularidad.para_controlar  
								WHEN 0 THEN 'Regularidad y Readmision'
								WHEN 1 THEN 'Regularidad'
								WHEN 2 THEN 'Readmision'
							END as para_controlar_desc
				FROM		sga_propuestas_regularidad 
							JOIN sga_propuestas ON sga_propuestas_regularidad.propuesta = sga_propuestas.propuesta
							JOIN sga_perdida_regularidad_causas ON sga_propuestas_regularidad.causa_perdida_reg = sga_perdida_regularidad_causas.causa_perdida_reg
				WHERE		$filtro_propuestas AND
							sga_propuestas_regularidad.ubicacion = $ubicacion
				ORDER BY	propuesta_descr
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna la cantidad de reglas de cálculo de regularidad configuradas para una propuesta en una ubicación.
	 * 
	 * @param int $propuesta
	 * @param int $ubicacion
	 * @return int
	 */
	function get_cant_reglas_calculo_regularidad_propuesta_ubicacion($propuesta, $ubicacion)
	{
		$propuesta = toba::db()->quote($propuesta);
		$ubicacion = toba::db()->quote($ubicacion);
		$sql = "SELECT	COUNT(sga_propuestas_regularidad.causa_perdida_reg) as cant
				FROM	sga_propuestas_regularidad
				WHERE	sga_propuestas_regularidad.propuesta = $propuesta AND
						sga_propuestas_regularidad.ubicacion = $ubicacion
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['cant'];
	}

	function get_solicitud_readmision_vigente($perdida_regularidad)
	{
		$perdida_regularidad = toba::db()->quote($perdida_regularidad);
		
		$sql = "SELECT date(fecha_solicitud) as fecha
		FROM sga_readmisiones_solicitud
		WHERE perdida_regularidad = $perdida_regularidad
		AND estado = 'P'
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['fecha'] ?? null;
	}
	

    /**
     * Devuelve una lista de alumnos no regulares en un período de tiempo con fecha de perdida de regularidad y readmisión para el ENDPOINT GET /alumnos-no-regulares.
     *
     * @param string $fecha_desde Obligatorio.
     * @param string $fecha_hasta Obligatorio.
     * @param string $persona Opcional, por defecto vacío.
     * @param string $propuesta Opcional, por defecto vacío.
     * @param string $plan Opcional, por defecto vacío.
     * @param string $plan_cobrable Opcional, por defecto vacío.
     * @param string $nro_transaccion Opcional, por defecto vacío.
     *
     * @return array Alumnos que no son regulares por rango de fechas para el ENDPOINT GET /alumnos-no-regulares.
     */
    function get_listado_alumnos_no_regulares_periodo_rest(
        $fecha_desde,
        $fecha_hasta,
        $persona = '',
        $propuesta = '',
        $plan = '',
        $plan_cobrable = '',
        $nro_transaccion = ''
    )
    {

        $where_persona = "";
        // Si filtra por persona
        if (!empty($persona)) {
            $where_persona = "AND sga_alumnos.persona = $persona";
        }

        $where_propuesta = "";
        // Si filtra por propuesta
        if (!empty($propuesta)) {
            $where_propuesta = "AND sga_propuestas.propuesta = {$propuesta}";
        }

        $where_plan = "";
        // Si filtra por plan
        if (!empty($plan)) {
            $where_plan = "AND sga_planes.plan = {$plan}";
        }

        $where_plan_cobrable = "";
        // Si filtra por plan_cobrable
        if (!empty($plan_cobrable)) {
            $where_plan_cobrable = "AND sga_planes.cobrable = '$plan_cobrable'";
        }

        $where_nro_transaccion = "";
        // Si filtra por nro_transaccion
        if (!empty($nro_transaccion)) {
            $where_nro_transaccion = "AND sga_propuestas_aspira.nro_transaccion = {$nro_transaccion}";
        }

        $formato_fecha_bd = comunes::formato_fecha_bd;
        $inscripcion_aceptada = propuesta::inscripcion_aceptada;
        $inscripcion_pendiente = propuesta::inscripcion_pendiente;

        $sql = "
            -- Perdidas de regularidad vigentes en el periodo (al menos un dia)
            SELECT
                sga_alumnos.persona,
                sga_propuestas.propuesta,
                sga_propuestas.nombre_abreviado AS propuesta_nombre_abreviado,
                sga_planes.plan,
                sga_planes.cobrable AS plan_cobrable,
                sga_propuestas_aspira.nro_transaccion,
                TO_CHAR(sga_perdida_regularidad.fecha, '{$formato_fecha_bd}') AS fecha_perdida_regularidad,
                TO_CHAR(sga_readmisiones.fecha, '{$formato_fecha_bd}') AS fecha_readmision,
                -- https://www.pgcasts.com/episodes/generating-json-from-sql
                (SELECT json_agg(row_to_json(t)) AS causas FROM (

                    SELECT
                    sga_perdida_regularidad_causas.causa_perdida_reg AS causa,
                    sga_perdida_regularidad_causas.nombre AS causa_nombre
                    FROM sga_perdida_regularidad_det
                    JOIN sga_perdida_regularidad_causas ON (sga_perdida_regularidad_det.causa_perdida_reg = sga_perdida_regularidad_causas.causa_perdida_reg)
                    WHERE sga_perdida_regularidad_det.perdida_regularidad = sga_perdida_regularidad.perdida_regularidad

                ) t)
            FROM
                sga_perdida_regularidad
                JOIN sga_alumnos ON (sga_alumnos.alumno = sga_perdida_regularidad.alumno)
                JOIN sga_propuestas ON (sga_propuestas.propuesta = sga_alumnos.propuesta)
                JOIN sga_propuestas_aspira ON (sga_propuestas_aspira.persona = sga_alumnos.persona AND sga_propuestas_aspira.propuesta = sga_alumnos.propuesta)
                JOIN sga_situacion_aspirante ON (sga_situacion_aspirante.situacion_asp = sga_propuestas_aspira.situacion_asp)
                JOIN sga_planes_versiones ON (sga_planes_versiones.plan_version = sga_alumnos.plan_version)
                JOIN sga_planes ON (sga_planes.plan = sga_planes_versiones.plan)
                LEFT JOIN sga_readmisiones ON (sga_readmisiones.perdida_regularidad = sga_perdida_regularidad.perdida_regularidad)
            WHERE
                sga_situacion_aspirante.resultado_asp IN ('{$inscripcion_aceptada}', '{$inscripcion_pendiente}')
                AND sga_perdida_regularidad.fecha <= TO_DATE('{$fecha_hasta}', '{$formato_fecha_bd}')  -- Menor o igual a la fecha hasta
                -- (1) No se informan las perdidas de regularidad si existe una readmision automatica (en el mismo dia) o fue readmitido antes de la fecha desde
                AND NOT EXISTS (
                    SELECT 1
                    FROM sga_readmisiones
                    WHERE sga_readmisiones.perdida_regularidad = sga_perdida_regularidad.perdida_regularidad
                    AND (sga_readmisiones.fecha = sga_perdida_regularidad.fecha OR sga_readmisiones.fecha <= TO_DATE('{$fecha_desde}', '{$formato_fecha_bd}'))
                )
                $where_persona
                $where_propuesta
                $where_plan
                $where_plan_cobrable
                $where_nro_transaccion
            ORDER BY nro_transaccion, fecha_perdida_regularidad;
        ";

        $alumnos_no_regulares = guarani_db::consultar($sql);

        $rs = [];
        // Hago un corte de control por 'nro_transaccion'
        foreach ($alumnos_no_regulares as $alumno) {
            $rs[$alumno['nro_transaccion']]['persona'] = $alumno['persona'];
            $rs[$alumno['nro_transaccion']]['propuesta'] = $alumno['propuesta'];
            $rs[$alumno['nro_transaccion']]['propuesta_nombre_abreviado'] = $alumno['propuesta_nombre_abreviado'];
            $rs[$alumno['nro_transaccion']]['plan'] = $alumno['plan'];
            $rs[$alumno['nro_transaccion']]['plan_cobrable'] = $alumno['plan_cobrable'];
            $rs[$alumno['nro_transaccion']]['nro_transaccion'] = $alumno['nro_transaccion'];

            // Si no hay causas
            if (is_null($alumno['causas'])) {
                $causas = [];
            // Si hay causas
            } else {
                // Convierto el string del JSON a UTF-8 sino se rompe cuando hay caracteres especiales
                $causas = iconv("ISO-8859-1", "UTF-8", $alumno['causas']);
                $causas = json_decode($causas, true);
            }

            $rs[$alumno['nro_transaccion']]['historial'][] = [
                'fecha_perdida_regularidad' => $alumno['fecha_perdida_regularidad'],
                'fecha_readmision' => $alumno['fecha_readmision'],
                'causas' => $causas
            ];
        }

        return $rs;
    }
    
}