<?php
namespace siu\modelo\datos\db;
use SIU\Chulupi\kernel;

class matricula
{
	/**
	 * parametros: plan_version, alumno
	 * cache: memoria
	 * cache_expiracion: 600
	 * filas: n
	 */
	function periodos_reinscripcion($parametros) 
	{
		// Recupera el ultimo periodo de reinscripción a propuesta vigente para una version de plan de estudios usado a reisncripcion a propuesta
		// Periodos de reinscripcion de años academicos mayores o igual al año academico de ingreso del alumno.
		//	habilitado_para = 0 = todas las interfaces / 2 = Autogestion	

		$interfaz_todas = \comunes::interfaz_todas;
		$interfaz_autogestion = \comunes::interfaz_autogestion;
		$si = kernel::db()->quote(\comunes::si);
		$tipo_reinscripcion = \periodo_de_inscripcion::tipo_reinscripcion;
		$tipo_todos = \periodo_de_inscripcion::tipo_todos;
		$inscripcion_aceptada = kernel::db()->quote(\propuesta::inscripcion_aceptada);
		$inscripcion_pendiente = kernel::db()->quote(\propuesta::inscripcion_pendiente);
		
		$sql = "SELECT p.periodo_inscripcion, p.anio_academico
				FROM sga_periodos_inscripcion_propuesta as p
				JOIN sga_periodos_inscripcion as i ON (i.periodo_inscripcion = p.periodo_inscripcion)
				JOIN sga_periodos_inscripcion_fechas as f ON (f.periodo_inscripcion = i.periodo_inscripcion)
				JOIN sga_periodos_inscripcion_aplanado as a ON (a.periodo_insc = f.periodo_insc)
				JOIN sga_alumnos ON (sga_alumnos.alumno = {$parametros['alumno']})
				JOIN sga_propuestas_aspira as pa ON (pa.propuesta = sga_alumnos.propuesta AND pa.persona = sga_alumnos.persona)
				JOIN sga_situacion_aspirante as sa ON (sa.situacion_asp = pa.situacion_asp)
				WHERE p.periodo_insc_tipo IN ($tipo_reinscripcion, $tipo_todos)
				  AND a.plan_version = {$parametros['plan_version']}
				  AND f.habilitado = $si
				  AND f.habilitado_interfaz IN ($interfaz_todas, $interfaz_autogestion)
				  AND CURRENT_DATE BETWEEN f.fecha_inicio AND f.fecha_fin
				  AND sa.resultado_asp IN ($inscripcion_aceptada, $inscripcion_pendiente)           -- inscripcion aceptada o pendiente
				  AND p.anio_academico >= pa.anio_academico   -- solo periodos de reinscripcion de años academico mayor o igual al año academico de ingreso del alumno
				ORDER BY p.anio_academico DESC, f.fecha_fin DESC 
				LIMIT 1
				";
				
		return kernel::db()->consultar_fila($sql);
	}
	
	/**
	 * parametros: plan_version, periodo_insc_tipo, alumno
	 * cache: memoria
	 * cache_expiracion: 600
	 * filas: 1
	 */
	function ultimo_periodo_inscripcion_vigente($parametros) 
	{
		/*  Recupera el ultimo periodo de inscripción/reinscripción vigente para una version de plan de estudios.
			periodo_insc_tipo: 
				1 = Inscripción
				2 = Reinscripcion
				3 = Inscripción/Reinscripción
			habilitado_para = 0 = todas las interfaces / 2 = Autogestion	
		 */
		$sql = "SELECT p.periodo_inscripcion, p.anio_academico
				FROM sga_periodos_inscripcion_propuesta as p 
				JOIN sga_periodos_inscripcion as i ON i.periodo_inscripcion = p.periodo_inscripcion
				JOIN sga_periodos_inscripcion_fechas as f ON f.periodo_inscripcion = i.periodo_inscripcion
				JOIN sga_periodos_inscripcion_aplanado as a ON a.periodo_insc = f.periodo_insc
				JOIN sga_alumnos ON sga_alumnos.alumno = {$parametros['alumno']}
				JOIN sga_propuestas_aspira as pa ON (pa.propuesta = sga_alumnos.propuesta AND pa.persona = sga_alumnos.persona)
				JOIN sga_situacion_aspirante as sa ON sa.situacion_asp = pa.situacion_asp
				
				WHERE f.habilitado = 'S'
				  AND f.habilitado_interfaz IN (0,2)
				  AND (p.periodo_insc_tipo = {$parametros['periodo_insc_tipo']} OR
					   (p.periodo_insc_tipo = 3 AND ({$parametros['periodo_insc_tipo']} = 1 OR {$parametros['periodo_insc_tipo']} = 2))			 
					   )
				  AND a.plan_version = {$parametros['plan_version']}
				  AND CURRENT_DATE BETWEEN f.fecha_inicio AND f.fecha_fin
				  AND sa.resultado_asp IN ('A','P')           -- inscripcion aceptada o pendiente
				  AND p.anio_academico >= pa.anio_academico   -- solo periodos de reinscripcion de años academico mayor o igual al año academico de ingreso del alumno
			ORDER BY p.anio_academico DESC, f.fecha_fin DESC
			LIMIT 1";

		return kernel::db()->consultar_fila($sql);
	}
	
	/**
	 * parametros: periodo_inscripcion
	 * cache: memoria
	 * cache_expiracion: 600
	 * filas: n
	 */
	function anio_academico_reinscripcion($parametros) 
	{
		$sql = "SELECT anio_academico
				FROM sga_periodos_inscripcion_propuesta
				WHERE periodo_inscripcion = {$parametros['periodo_inscripcion']}
				";
		$datos = kernel::db()->consultar_fila($sql);
		return $datos;
	}
	
	
	/**
	 * parametros: persona, anio_academico_reinscripcion
	 * cache: no
	 */
	function carreras_a_reinscribirse($parametros) 
	{
		// Solo propuestas de Grado.
		$sql = "SELECT sga_propuestas.propuesta AS propuesta, 
						sga_propuestas.nombre AS propuesta_nombre,
			           (SELECT COUNT(*) FROM sga_reinscripciones
					    WHERE alumno = sga_alumnos.alumno
						AND anio_academico = {$parametros["anio_academico_reinscripcion"]}
						) AS reinscripto,
						sga_alumnos.calidad
				FROM sga_alumnos
				JOIN sga_propuestas ON (sga_propuestas.propuesta = sga_alumnos.propuesta)
				WHERE sga_alumnos.persona = {$parametros["persona"]}
				";
		
		$datos = kernel::db()->consultar($sql);
		$nuevo = array();
		foreach($datos as $dato) {
			$nuevo[$dato['propuesta']] = $dato;
		}
		return $nuevo;
	}		
	
	/**
	 * parametros: alumno, plan_version
	 * cache: no
	 */
	function reinscripcion($parametros) 
	{
		$interfaz_web = 2; // Web
		
		// Recupero el año académico $anio_academico_reinscripcion = 
		$periodo_anio['periodo_insc_tipo'] = 2; // Reinscripcion
		$periodo_anio['plan_version']      = $parametros["plan_version"];
		$periodo_anio['alumno']            = $parametros["alumno"];
		$anio_academico_reinscripcion = $this->ultimo_periodo_inscripcion_vigente($periodo_anio);
		
        // Si no hay periodo de reinscripcion vigente. salgo con error.
		if (empty($anio_academico_reinscripcion)) {
            $periodo_anio['periodo_insc_tipo'] = 3; // Reinscripción e Inscripción
            $anio_academico_reinscripcion = $this->ultimo_periodo_inscripcion_vigente($periodo_anio);
            if (empty($anio_academico_reinscripcion)) {
            	return false;
            }
		}
		$anio = $anio_academico_reinscripcion['anio_academico'];
		
		// Genero la reinscripción en la propuesta
		$sql = "INSERT INTO sga_reinscripciones (alumno, anio_academico, interfaz)
			      VALUES ({$parametros["alumno"]}, $anio, $interfaz_web)";

		$estado = kernel::db()->ejecutar($sql);
		
                if ($estado > 0){
                    $sql_rs = "SELECT nro_transaccion "
                            . "FROM sga_reinscripciones "
                            . "WHERE reinscripcion = ("
                            . "SELECT currval('sga_reinscripciones_seq')"
                            . ")";
                    $rs = kernel::db()->consultar_fila($sql_rs);
                }
                
                if ($rs > 0){
                    return $rs['nro_transaccion'];
                }
                return false;
	}
	
	/**
	 * parametros: nro_trans
	 * cache: no
	 * filas: 1
	 */
	function datos_reinscripcion($parametro) 
	{   
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$sql = "SELECT 
            vw_comprobantes.alumno_nombre as alumno_nombre,
            vw_comprobantes.alumno_identificacion as identificacion,
            vw_comprobantes.alumno_legajo as legajo,
            vw_comprobantes.nro_transaccion as nro_trans,
            vw_comprobantes.inscripcion as reinscripcion,
            vw_comprobantes.propuesta as propuesta,
            sga_propuestas.nombre_abreviado AS propuesta_nombre,
            vw_comprobantes.ubicacion as ubicacion,
            sga_ubicaciones.nombre as ubicacion_nombre,
            to_char(vw_comprobantes.fecha, $formato_fecha_visual) as fecha_reinscripcion,
            vw_comprobantes.descripcion,
            vw_comprobantes.anio_academico as anio_academico,
            sga_planes.codigo as plan_codigo,
            sga_planes.nombre as plan_nombre,
            sga_planes_versiones.version as version,
            sga_planes_versiones.nombre as version_nombre,
            sga_modalidad_cursada.nombre as modalidad_cursada_nombre
         FROM
            vw_comprobantes,
            sga_propuestas, 
            sga_alumnos,
            vw_personas,
            sga_planes_versiones,
            sga_planes,
            sga_ubicaciones,
            sga_modalidad_cursada
         WHERE
             vw_comprobantes.nro_transaccion = {$parametro['nro_trans']}
         AND vw_comprobantes.tipo = 'RP'    
         AND sga_ubicaciones.ubicacion = vw_comprobantes.ubicacion
         AND vw_comprobantes.propuesta = sga_propuestas.propuesta
         AND sga_alumnos.alumno = vw_comprobantes.alumno 
         AND vw_personas.persona = sga_alumnos.persona
         AND sga_modalidad_cursada.modalidad = sga_alumnos.modalidad
         AND sga_planes_versiones.plan_version = sga_alumnos.plan_version
         AND sga_planes.plan = sga_planes_versiones.plan
         ";
		$datos = kernel::db()->consultar_fila($sql);
		return $datos;
	}
}

?>