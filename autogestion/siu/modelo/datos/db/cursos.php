<?php
namespace siu\modelo\datos\db;

use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;

class cursos
{
    /**
     * parametros:
     * cache: memoria
     * filas: n
     */
    function propuestas_administran_cursos($parametros){
        $propuestas_administran_cursos = \toba::consulta_php('co_propuestas')->get_propuestas_administran_cursos();

        foreach($propuestas_administran_cursos as $key => $propuesta_curso){
            $propuestas_administran_cursos[$key][catalogo::id] = catalogo::generar_id($propuesta_curso['valor']);
        }

        return $propuestas_administran_cursos;
    }

    /**
     * parametros: propuesta_curso
     * cache: memoria
     * filas: n
     */
    function actividades_propuestas_administran_cursos_con_comisiones_vigentes($parametros)
    {
        $si							= kernel::db()->quote(\comunes::si);
        $estado_activo				= kernel::db()->quote(\comunes::estado_activo);
        $estado_activo_vigente		= kernel::db()->quote(\planes::estado_activo_vigente);
        $estado_activo_no_vigente	= kernel::db()->quote(\planes::estado_activo_no_vigente);
		$interfaz					= \comunes::interfaz_autogestion;
		
        $sql = "SELECT  
					vw_actividades_plan.elemento,
                    vw_actividades_plan.codigo,
                    vw_actividades_plan.nombre,
                    vw_actividades_plan.nombre_abreviado,
                    vw_actividades_plan.propuesta,
                    vw_actividades_plan.plan,
                    vw_actividades_plan.plan_version
            FROM sga_planes
			JOIN vw_actividades_plan ON vw_actividades_plan.propuesta = sga_planes.propuesta
            WHERE sga_planes.propuesta = {$parametros['propuesta_curso']}
			  AND sga_planes.administra_cursos = $si
			  AND vw_actividades_plan.estado = $estado_activo
			  AND (vw_actividades_plan.plan_version, vw_actividades_plan.elemento) IN 
			  		(SELECT sga_planes_versiones.plan_version, act.elemento
			           FROM sga_planes
			           JOIN sga_planes_versiones ON sga_planes_versiones.plan = sga_planes.plan
			           JOIN LATERAL f_actividades_con_comisiones_vigentes(sga_planes.propuesta, sga_planes.plan, sga_planes_versiones.plan_version, true, NULL, $interfaz, NULL) as act (elemento) ON TRUE
			          WHERE sga_planes.propuesta = {$parametros['propuesta_curso']}
			           AND sga_planes.estado IN ($estado_activo_vigente, $estado_activo_no_vigente)
			           AND sga_planes_versiones.estado IN ($estado_activo_vigente, $estado_activo_no_vigente)
			           AND sga_planes.administra_cursos = $si
					)
			ORDER BY vw_actividades_plan.nombre, vw_actividades_plan.plan, vw_actividades_plan.plan_version			
        ";
					
	
        $actividades = kernel::db()->consultar($sql);

        foreach($actividades as $key => $actividad){
            $actividades[$key][catalogo::id] = catalogo::generar_id($actividad['elemento']);
        }

        return $actividades;
    }

    /**
     * parametros: curso
     * cache: memoria
     * filas: 1
     */
    function get_info_curso($parametros)
    {

        $sql = "
                SELECT sga_elementos.elemento,
                       sga_elementos.codigo,
                       sga_elementos.nombre,
                       sga_elementos.nombre_abreviado,
                       sga_elementos_atrib.insc_cursada_por_prioridad,
                       sga_elementos_atrib.prioridad_cant_maxima,
                       array_to_string((SELECT array_agg(sga_responsables_academicas.nombre)
                                        FROM sga_elementos_ra, sga_responsables_academicas
                                        WHERE sga_elementos_ra.elemento = sga_elementos.elemento
                                        AND sga_elementos_ra.responsable_academica = sga_responsables_academicas.responsable_academica), '|') as responsables_academicas
                FROM sga_elementos
                JOIN sga_elementos_atrib ON (sga_elementos.elemento = sga_elementos_atrib.elemento)
                WHERE sga_elementos.elemento = {$parametros['curso']}
        ";

        return kernel::db()->consultar_fila($sql);
    }

    /**
     * parametros: curso
     * cache: memoria
     * filas: n
     */
    function get_info_comisiones_curso($parametros)
    {

        $sql = "
                SELECT  vw_actividades_plan.propuesta,
                        vw_actividades_plan.plan,
                        vw_actividades_plan.plan_version,
                        vw_actividades_plan.elemento,
                        vw_actividades_plan.codigo AS actividad_codigo,
                        vw_actividades_plan.nombre AS actividad_nombre,
                        sga_comisiones.comision,
                        sga_comisiones.nombre AS comision_nombre,
                        sga_comisiones.inscripcion_cerrada,
                        sga_comisiones.inscripcion_cerrada_codigo
                    FROM sga_comisiones
                    JOIN vw_actividades_plan ON (sga_comisiones.elemento=vw_actividades_plan.elemento)
                    WHERE vw_actividades_plan.elemento = {$parametros['curso']};
        ";

        $datos = kernel::db()->consultar($sql);

        foreach($datos as $key => $dato){
            $datos[$key][catalogo::id] = catalogo::generar_id($dato['propuesta'].$dato['plan'].$dato['plan_version'].$dato['elemento'].$dato['comision']);
        }

        return $datos;
    }

    /**
     * parametros: persona
     * cache: memoria
     * filas: n
     */
    function lista_constancias($parametros)
    {

        $si = kernel::db()->quote(\comunes::si);
        $estado_solicitud_rechazado = kernel::db()->quote(\constancias::estado_solicitud_rechazado);
        $estado_solicitud_entregado = kernel::db()->quote(\constancias::estado_solicitud_entregado);

        //Traigo todas las constancias de la persona que aplican a cursos
        $sql = "
        SELECT 	sga_constancias.constancia,
                sga_constancias.nombre AS constancia_nombre,
                sga_constancias.permite_descargar,
                sga_constancias_solicitud.solicitud,
                sga_constancias_solicitud.interfaz,
                sga_constancias_solicitud.estado,
                sga_constancias_solicitud.fecha_solicitud,
                sga_constancias_solicitud.fecha_fin_vigencia,
                sga_elementos.nombre AS curso_nombre,
                sga_elementos.nombre_abreviado AS curso_nombre_abreviado
            FROM sga_constancias
            JOIN sga_constancias_solicitud ON (sga_constancias.constancia = sga_constancias_solicitud.constancia)
            JOIN sga_alumnos ON (sga_alumnos.alumno = sga_constancias_solicitud.alumno)
            JOIN sga_constancias_solicitud_param ON ((sga_constancias_solicitud.solicitud = sga_constancias_solicitud_param.solicitud) AND (sga_constancias_solicitud_param.campo = 'comision'))
            JOIN sga_comisiones ON (sga_constancias_solicitud_param.valor::int = sga_comisiones.comision)
            JOIN sga_elementos ON (sga_elementos.elemento = sga_comisiones.elemento)
            WHERE 	sga_constancias.activo = {$si}
                AND sga_constancias.aplica_a_cursos = {$si}
                AND sga_constancias_solicitud.estado NOT IN ({$estado_solicitud_rechazado}, {$estado_solicitud_entregado})
                AND sga_alumnos.persona = {$parametros['persona']}
            ORDER BY sga_constancias_solicitud.fecha_solicitud
        ";

        $constancias = kernel::db()->consultar($sql);

        foreach($constancias as $key => $constancia){
            $constancias[$key][catalogo::id] = catalogo::generar_id($constancia['solicitud']);
        }

        return $constancias;
    }
	
	
	
	/**
	 * Recupera los cursos realizados en una propuesta de tipo curso.
	 * parametros: persona, propuesta
	 * param_null: propuesta
	 * cache: memoria
	 * filas: n
	 */
	function get_cursos_academica($parametros) 
	{
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
                if (is_null($parametros['propuesta'])){
                    $parametros['propuesta']= 'NULL';
                }
                
		$sql = "SELECT
                            cursos.propuesta,
                            cursos.propuesta_nombre,
                            cursos.anio_academico, 
                            cursos.actividad_codigo, 
                            cursos.actividad_nombre, 
                            cursos.estado,
                            cursos.fecha,
                            cursos.nota, 
                            cursos.nota_desc, 
                            cursos.resultado 
        FROM		get_cursos_realizados({$parametros['persona']}, {$parametros['propuesta']}) AS cursos
        ";
				
		return kernel::db()->consultar($sql);
	}
	
	/**
	 * parametros: persona
	 * cache: memoria
	 * filas: n
	 */
	
	function get_propuestas_academica($parametros){
		
		// Tipo de propuesta Curso.
		$propuesta_tipo_curso = kernel::db()->quote(\propuesta::tipo_curso);
		
		// Listado de propuestas de tipo curso en donde se encuentra el alumno inscripto en algun curso.
		$sql = "SELECT propuesta, propuesta_nombre
				FROM vw_alumnos
				WHERE persona = {$parametros['persona']}
				  AND propuesta_tipo = $propuesta_tipo_curso
				ORDER BY propuesta_nombre  
				";
				
		return 	kernel::db()->consultar($sql);
	}
	

}