<?php
/**
 *
 *
 * @package Calendario
 * @subpackage Actualizaciones
 */
class co_periodos_de_inscripcion
{
    /**
      Obtiene una lista de los períodos de inscripción según el filtro recibido
     */
    function get_listado($where = '')
    {
        if ($where) {
            $where = ' AND ' . $where . ' ';
        }

        $sql = "	SELECT 	sga_periodos_inscripcion.periodo_inscripcion,
							sga_periodos_inscripcion.nombre,
							sga_periodos_inscripcion.descripcion,
							sga_periodos_inscripcion.periodo,
							sga_periodos_inscripcion.periodo_generico_tipo,
							sga_periodos_inscripcion_propuesta.anio_academico,
							sga_periodos_inscripcion_propuesta.fecha_cierre_requisitos,
							sga_per_insc_prop_tipos.nombre as periodo_insc_tipo_nombre
					FROM 	sga_periodos_inscripcion,
							sga_periodos_inscripcion_propuesta,
							sga_per_insc_prop_tipos
					WHERE	sga_periodos_inscripcion.periodo_inscripcion = sga_periodos_inscripcion_propuesta.periodo_inscripcion AND
							sga_periodos_inscripcion_propuesta.periodo_insc_tipo = sga_per_insc_prop_tipos.periodo_insc_tipo
							$where
					ORDER BY 6 DESC,2 
							;";
        return guarani_db::consultar($sql);
    }

    /**
     * Retorna el listado de datos básicos de los períodos de inscripción a propuesta filtrando por perfil de datos.
     * 
     * @param string $where
     * @return array
     */
    function get_periodos_inscripcion_propuesta_perfil_de_datos($where = '')
    {
        if ($where) {
            $where = " AND $where";
        }
        $sql = "	SELECT		sga_periodos_inscripcion_propuesta.periodo_inscripcion,
								sga_periodos_inscripcion_propuesta.anio_academico,
								sga_periodos_inscripcion.nombre,
								sga_per_insc_prop_tipos.nombre as periodo_insc_tipo_nombre
					FROM		sga_periodos_inscripcion_propuesta,
								sga_per_insc_prop_tipos,
								sga_periodos_inscripcion,
								sga_periodos_inscripcion_fechas,
								sga_periodos_inscripcion_aplanado,
								sga_planes_versiones
					WHERE		sga_periodos_inscripcion_propuesta.periodo_insc_tipo = sga_per_insc_prop_tipos.periodo_insc_tipo AND
								sga_periodos_inscripcion_propuesta.periodo_inscripcion = sga_periodos_inscripcion.periodo_inscripcion AND
								sga_periodos_inscripcion.periodo_inscripcion = sga_periodos_inscripcion_fechas.periodo_inscripcion AND
								sga_periodos_inscripcion_fechas.periodo_insc = sga_periodos_inscripcion_aplanado.periodo_insc AND
								sga_periodos_inscripcion_aplanado.plan_version = sga_planes_versiones.plan_version
								$where
					GROUP BY	sga_periodos_inscripcion_propuesta.periodo_inscripcion,
                                sga_periodos_inscripcion_propuesta.anio_academico,
								sga_periodos_inscripcion.nombre,
								sga_per_insc_prop_tipos.nombre
					ORDER BY	sga_periodos_inscripcion_propuesta.anio_academico DESC,
								sga_periodos_inscripcion.nombre
		";
        return guarani_db::consultar($sql);
    }

    function get_datos_periodo_inscripcion($periodo_inscr)
    {
        if (empty($periodo_inscr)) {
            return array();
        }
        $periodo_inscr = toba::db()->quote($periodo_inscr);
        $sql = "SELECT	sga_periodos_inscripcion.periodo_inscripcion,
						sga_periodos_inscripcion.nombre,
						sga_periodos_inscripcion_propuesta.anio_academico,
						sga_periodos_inscripcion.descripcion,
						sga_periodos_inscripcion.periodo,
						sga_periodos_inscripcion.periodo_generico_tipo
				FROM	sga_periodos_inscripcion,
						sga_periodos_inscripcion_propuesta
				WHERE	sga_periodos_inscripcion.periodo_inscripcion = sga_periodos_inscripcion_propuesta.periodo_inscripcion AND
						sga_periodos_inscripcion.periodo_inscripcion = $periodo_inscr
						;";
        return guarani_db::consultar_fila($sql);
    }

    function get_datos_periodo_insc_fechas($periodo_insc)
    {
        if (empty($periodo_insc)) {
            return array();
        }
        $periodo_insc = toba::db()->quote($periodo_insc);
        $sql = "SELECT	sga_periodos_inscripcion_fechas.periodo_inscripcion,
						sga_periodos_inscripcion_fechas.tipo,
						sga_periodos_inscripcion_fechas.entidad_tipo,
						sga_periodos_inscripcion_fechas.fecha_inicio,
						sga_periodos_inscripcion_fechas.fecha_fin,
						sga_periodos_inscripcion_fechas.fecha_tope_bajas,
						sga_periodos_inscripcion_fechas.tipo_cursado,
						sga_periodos_inscripcion_fechas.tipo_alumno,
                                                sga_periodos_inscripcion_fechas.habilitado_interfaz
				FROM	sga_periodos_inscripcion_fechas
				WHERE	sga_periodos_inscripcion_fechas.periodo_insc = $periodo_insc
						;";
        return guarani_db::consultar_fila($sql);
    }

    /**
      Obtiene una lista de los tipos de períodos de inscripcion según el filtro recibido
     */
    function get_listado_tipos($where = '')
    {

        if ($where)
            $where = ' WHERE ' . $where . ';';

        $sql = "SELECT 	sga_per_insc_prop_tipos.periodo_insc_tipo as valor,
						sga_per_insc_prop_tipos.nombre as descr
				FROM 	sga_per_insc_prop_tipos
				$where
				ORDER BY 2
				";

        return guarani_db::consultar($sql);
    }

    /**
      Obtiene los datos de un tipo de período de inscripcion
     */
    function get_datos_periodo_inscripcion_tipo($periodo_inscripcion_tipo, $dato = null)
    {
        $periodo_inscripcion_tipo = toba::db()->quote($periodo_inscripcion_tipo);
        $where = " sga_periodos_inscripcion_tipos.tipo = $periodo_inscripcion_tipo ";
        $datos = current($this->get_listado_tipos($where));
        return isset($dato) ? $datos[$dato] : $datos;
    }

    /**
     * Obtiene TODOS los datos de un período de inscripcion a partir de un plan_version, modalidad y ubicacion
	 * Periodos de inscripcion habilitados para todas las interfaces o gestion.
     */
    function get_datos_per_insc_pv($plan_version = null, $modalidad, $ubicacion)
    {
        $where = '';

        if ((!is_null($plan_version)) && (!is_null($ubicacion)) && (!is_null($modalidad))) {
            $plan_version = toba::db()->quote($plan_version);
            $ubicacion = toba::db()->quote($ubicacion);
            $modalidad = toba::db()->quote($modalidad);

            $where = "  sga_periodos_inscripcion_aplanado.plan_version = $plan_version AND
						sga_per_insc_modalidad.modalidad = $modalidad AND 
						sga_per_insc_ubicacion.ubicacion = $ubicacion AND 
						sga_periodos_inscripcion_fechas.habilitado = 'S' AND 
						sga_periodos_inscripcion_fechas.habilitado_interfaz IN (".comunes::interfaz_todas.", ".comunes::interfaz_gestion.") AND 						
						-- Período con fechas vigentes
						(" . guarani_sql::fecha_actual . " BETWEEN sga_periodos_inscripcion_fechas.fecha_inicio AND sga_periodos_inscripcion_fechas.fecha_fin) AND ";
        }

        $sql = "	SELECT 	sga_periodos_inscripcion.periodo_inscripcion,
							sga_periodos_inscripcion.nombre,
							sga_periodos_inscripcion.descripcion,
							sga_periodos_inscripcion_propuesta.anio_academico as anio,
							sga_periodos_inscripcion_propuesta.fecha_cierre_requisitos,
							to_char(sga_periodos_inscripcion_propuesta.fecha_cierre_requisitos, '" . comunes::formato_fecha_visual . "') as fecha_cierre_requisitos_visual,
							sga_periodos_inscripcion_fechas.fecha_inicio,
							to_char(sga_periodos_inscripcion_fechas.fecha_inicio, '" . comunes::formato_fecha_visual . "') as fecha_inicio_visual,
							sga_periodos_inscripcion_fechas.fecha_fin,
							to_char(sga_periodos_inscripcion_fechas.fecha_fin, '" . comunes::formato_fecha_visual . "') as fecha_fin_visual,
							sga_periodos_inscripcion_fechas.periodo_insc,
							sga_per_insc_modalidad.modalidad,
							sga_per_insc_ubicacion.ubicacion,
							sga_periodos_inscripcion_propuesta.anio_academico || '|' || sga_periodos_inscripcion.periodo_inscripcion || '|' || sga_periodos_inscripcion_fechas.periodo_insc as anio_periodo_insc,
							sga_periodos_inscripcion.nombre || ' (' ||
							to_char(sga_periodos_inscripcion_fechas.fecha_inicio, '" . comunes::formato_fecha_visual . "') || ' - ' ||
							to_char(sga_periodos_inscripcion_fechas.fecha_fin, '" . comunes::formato_fecha_visual . "') || ')' as periodo_nombre_fechas
					FROM 	
							sga_periodos_inscripcion,
							sga_periodos_inscripcion_propuesta,
							sga_periodos_inscripcion_fechas,
							sga_periodos_inscripcion_aplanado,
							sga_per_insc_modalidad,
							sga_per_insc_ubicacion 

					WHERE	
							$where
							sga_periodos_inscripcion_aplanado.periodo_insc = sga_periodos_inscripcion_fechas.periodo_insc AND
							sga_periodos_inscripcion.periodo_inscripcion = sga_periodos_inscripcion_fechas.periodo_inscripcion AND 
							sga_per_insc_modalidad.periodo_inscripcion = sga_periodos_inscripcion.periodo_inscripcion AND
							sga_per_insc_ubicacion.periodo_inscripcion = sga_periodos_inscripcion.periodo_inscripcion AND
							sga_periodos_inscripcion.periodo_inscripcion = sga_periodos_inscripcion_propuesta.periodo_inscripcion AND
							sga_periodos_inscripcion_propuesta.periodo_insc_tipo IN (" . periodo_de_inscripcion::tipo_inscripcion . ", " . periodo_de_inscripcion::tipo_todos . ")
					ORDER BY 2
							;";
        return guarani_db::consultar($sql);
    }

    /**
     * Retorna el listado de períodos de inscripción para una propuesta dada.
     * 
     * @param integer $propuesta
     * @return array
     */
    function get_periodos_inscripcion_por_propuesta($propuesta)
    {
        $propuesta = toba::db()->quote($propuesta);
        $sql = 'SELECT	sga_periodos_inscripcion.periodo_inscripcion,
						sga_periodos_inscripcion.nombre
				FROM	sga_periodos_inscripcion,
						sga_periodos_inscripcion_fechas,
						sga_periodos_inscripcion_aplanado,
						sga_planes_versiones,
						sga_planes
				WHERE	sga_periodos_inscripcion.periodo_inscripcion = sga_periodos_inscripcion_fechas.periodo_inscripcion AND
						sga_periodos_inscripcion_fechas.periodo_insc = sga_periodos_inscripcion_aplanado.periodo_insc AND
						sga_periodos_inscripcion_aplanado.plan_version = sga_planes_versiones.plan_version AND
						sga_planes_versiones.plan = sga_planes.plan AND
						sga_periodos_inscripcion.periodo_generico_tipo = ' . periodo_generico::tipo_propuesta . " AND
						sga_planes.propuesta = $propuesta
		";
        return guarani_db::consultar($sql);
    }

    /**
     * Obtiene el sga_periodos_inscripcion_fechas.periodo_insc vigente o vencido (menos antiguo) 
     * para un periodo_inscripcion, plan_version, modalidad y ubicacion dados
     */
    function get_periodo_insc_vigente_o_vencido($periodo_inscripcion, $plan_version, $modalidad, $ubicacion)
    {
        $periodo_inscripcion = toba::db()->quote($periodo_inscripcion);
        $plan_version = toba::db()->quote($plan_version);
        $modalidad = toba::db()->quote($modalidad);
        $ubicacion = toba::db()->quote($ubicacion);

        $sql = "SELECT 	sga_periodos_inscripcion_fechas.periodo_insc,
						CASE WHEN (" . guarani_sql::fecha_actual . " BETWEEN sga_periodos_inscripcion_fechas.fecha_inicio AND sga_periodos_inscripcion_fechas.fecha_fin)
							THEN 1
							ELSE 0
						END AS vigente
				FROM 	sga_periodos_inscripcion_aplanado,
						sga_periodos_inscripcion_propuesta,
						sga_periodos_inscripcion_fechas,
						sga_per_insc_modalidad,
						sga_per_insc_ubicacion 
				WHERE	sga_periodos_inscripcion_fechas.periodo_inscripcion = $periodo_inscripcion AND --Período seleccionado en la operación de Preinscripción masiva
						sga_periodos_inscripcion_aplanado.periodo_insc = sga_periodos_inscripcion_fechas.periodo_insc AND
						sga_per_insc_modalidad.periodo_inscripcion = sga_periodos_inscripcion_fechas.periodo_inscripcion AND
						sga_per_insc_ubicacion.periodo_inscripcion = sga_periodos_inscripcion_fechas.periodo_inscripcion AND
						sga_periodos_inscripcion_fechas.periodo_inscripcion = sga_periodos_inscripcion_propuesta.periodo_inscripcion AND
						sga_periodos_inscripcion_propuesta.periodo_insc_tipo IN (" . periodo_de_inscripcion::tipo_inscripcion . ", " . periodo_de_inscripcion::tipo_todos . ") AND
						sga_periodos_inscripcion_aplanado.plan_version = $plan_version AND
						sga_per_insc_modalidad.modalidad = $modalidad AND 
						sga_per_insc_ubicacion.ubicacion = $ubicacion AND 
						sga_periodos_inscripcion_fechas.habilitado = '" . comunes::si . "' AND
						sga_periodos_inscripcion_fechas.habilitado_interfaz IN (".comunes::interfaz_todas.", ".comunes::interfaz_gestion.") AND
						sga_periodos_inscripcion_fechas.fecha_inicio <= " . guarani_sql::fecha_actual . "
				ORDER BY 
						vigente DESC,
						sga_periodos_inscripcion_fechas.fecha_fin DESC 
						LIMIT 1
			;";

        return guarani_db::consultar($sql);
    }

    /**
     * Obtiene un Período de Inscripción y sus Fechas, vigente o vencido (menos antiguo) 
     * para un plan_version, modalidad y ubicacion dados. Utilizado para crear Planes Personalizados.
     */
    function get_periodo_inscripcion_vigente_o_vencido_para_plan_version_pers($propuesta, $ubicacion, $modalidad, $anio_academico)
    {
        $propuesta = toba::db()->quote($propuesta);
        $anio = toba::db()->quote($anio_academico);
        $modalidad = toba::db()->quote($modalidad);
        $ubicacion = toba::db()->quote($ubicacion);
		$formato_fecha_visual = toba::db()->quote(comunes::formato_fecha_visual);

        $sql = "SELECT	DISTINCT
				        sga_periodos_inscripcion_fechas.periodo_insc, 
						sga_periodos_inscripcion_fechas.periodo_inscripcion,
				        sga_periodos_inscripcion.nombre,
						sga_periodos_inscripcion_fechas.fecha_inicio,
						sga_periodos_inscripcion_fechas.fecha_fin,
						sga_periodos_inscripcion.nombre || ' - ' || to_char(sga_periodos_inscripcion_fechas.fecha_inicio, $formato_fecha_visual) || ' al ' || to_char(sga_periodos_inscripcion_fechas.fecha_fin, $formato_fecha_visual) as periodo_descr,
						sga_periodos_inscripcion_propuesta.anio_academico,
						CASE WHEN (" . guarani_sql::fecha_actual . " BETWEEN sga_periodos_inscripcion_fechas.fecha_inicio AND sga_periodos_inscripcion_fechas.fecha_fin)
							THEN 1
							ELSE 0
						END AS vigente
				FROM 	sga_periodos_inscripcion_aplanado,
						sga_periodos_inscripcion_propuesta,
						sga_periodos_inscripcion,
						sga_periodos_inscripcion_fechas,
						sga_per_insc_modalidad,
						sga_per_insc_ubicacion 
				WHERE	sga_periodos_inscripcion_aplanado.periodo_insc = sga_periodos_inscripcion_fechas.periodo_insc AND
						sga_periodos_inscripcion.periodo_inscripcion = sga_periodos_inscripcion_fechas.periodo_inscripcion AND
						sga_per_insc_modalidad.periodo_inscripcion = sga_periodos_inscripcion_fechas.periodo_inscripcion AND
						sga_per_insc_ubicacion.periodo_inscripcion = sga_periodos_inscripcion_fechas.periodo_inscripcion AND
						sga_periodos_inscripcion_fechas.periodo_inscripcion = sga_periodos_inscripcion_propuesta.periodo_inscripcion AND
						(sga_periodos_inscripcion_propuesta.periodo_insc_tipo = " . periodo_de_inscripcion::tipo_inscripcion . " OR
						 sga_periodos_inscripcion_propuesta.periodo_insc_tipo = " . periodo_de_inscripcion::tipo_todos . ") AND					
						sga_periodos_inscripcion_propuesta.anio_academico = $anio AND

						sga_per_insc_modalidad.modalidad = $modalidad AND 
						sga_per_insc_ubicacion.ubicacion = $ubicacion AND 
						sga_periodos_inscripcion_fechas.habilitado = '" . comunes::si . "' AND
						sga_periodos_inscripcion_fechas.habilitado_interfaz IN (".comunes::interfaz_todas.", ".comunes::interfaz_gestion.") AND

						sga_periodos_inscripcion_aplanado.propuesta = $propuesta
				ORDER BY 
						sga_periodos_inscripcion_fechas.fecha_fin DESC 
			;";

        return guarani_db::consultar($sql);
    }

    /**
      Valida si un nombre de período de inscripción a propuestas se puede usar o no.
     */
    function validar_nombre_periodo_de_inscripcion_propuestas($nombre, $periodo_inscripcion)
    {
        if (!isset($nombre)) {
            return false;
        }

        $nombre = toba::db()->quote($nombre);

        $sql = 'SELECT	count(sga_periodos_inscripcion.periodo_inscripcion) as cant
                FROM 	sga_periodos_inscripcion
				WHERE 	' . guarani_sql::armar_condicion_compara_cadenas('sga_periodos_inscripcion.nombre', $nombre) . '
				AND sga_periodos_inscripcion.periodo_generico_tipo = ' . periodo_generico::tipo_propuesta;

        if (!is_null($periodo_inscripcion)) {
            $periodo_inscripcion = toba::db()->quote($periodo_inscripcion);
            $sql .= " AND sga_periodos_inscripcion.periodo_inscripcion <> $periodo_inscripcion ";
        }
		
        $res = guarani_db::consultar_fila($sql);
        return ($res['cant'] == 0);
    }

    function get_periodos_inscripcion_fechas($periodo_inscripcion, $filtro='')
    {
		$where = '';
		if (!empty($periodo_inscripcion)) {
			$periodo_inscripcion = toba::db()->quote($periodo_inscripcion);
			$where = " AND sga_periodos_inscripcion_fechas.periodo_inscripcion = $periodo_inscripcion";
		}
		if ($filtro) {
			$where .= " AND $filtro";
		}
        $sql = "SELECT	sga_periodos_inscripcion_fechas.periodo_insc,
						sga_periodos_inscripcion_fechas.entidad_tipo,
						sga_periodos_inscripcion_fechas.habilitado,
						sga_periodos_inscripcion_fechas.habilitado_interfaz,
						sga_periodos_inscripcion.nombre,
						sga_periodos_inscripcion.descripcion,
						sga_periodos_inscripcion.periodo,
						sga_periodos_inscripcion.periodo_generico_tipo,
						sga_periodos.anio_academico 
				FROM	sga_periodos_inscripcion_fechas,
						sga_periodos_inscripcion
						LEFT JOIN sga_periodos ON sga_periodos_inscripcion.periodo = sga_periodos.periodo
				WHERE	sga_periodos_inscripcion.periodo_inscripcion = sga_periodos_inscripcion_fechas.periodo_inscripcion
						$where
		";
        return guarani_db::consultar($sql);
    }

    function get_datos_periodo($periodo)
    {
        if (empty($periodo))
            return array();
        $periodo = toba::db()->quote($periodo);
        $sql = "SELECT	sga_periodos.periodo,
						sga_periodos.nombre,
						sga_periodos.descripcion,
						sga_periodos.anio_academico,
						sga_periodos.periodo_generico,
						sga_periodos.fecha_inicio,
						sga_periodos.fecha_fin
				FROM	sga_periodos
				WHERE	sga_periodos.periodo = $periodo
						;";
        return guarani_db::consultar_fila($sql);
    }

    function get_entidades($periodo_inscripcion_fechas)
    {
        if (empty($periodo_inscripcion_fechas))
            return array();
        $periodo_inscripcion_fechas = toba::db()->quote($periodo_inscripcion_fechas);
        $sql = "SELECT	sga_periodos_inscripcion_entidad.entidad
				FROM	sga_periodos_inscripcion_entidad
				WHERE	sga_periodos_inscripcion_entidad.periodo_insc = $periodo_inscripcion_fechas";
        return guarani_db::consultar($sql);
    }

    function get_subtipos($periodo_inscripcion_fechas)
    {
        if (empty($periodo_inscripcion_fechas))
            return array();
        $periodo_inscripcion_fechas = toba::db()->quote($periodo_inscripcion_fechas);
        $sql = "SELECT		sga_periodos_inscripcion_subtipos.entidad_subtipo,
							sga_g3entidades_subtipos.nombre
				FROM		sga_periodos_inscripcion_subtipos,
							sga_g3entidades_subtipos
				WHERE		sga_periodos_inscripcion_subtipos.periodo_insc = $periodo_inscripcion_fechas AND
							sga_periodos_inscripcion_subtipos.entidad_subtipo = sga_g3entidades_subtipos.entidad_subtipo
				ORDER BY	sga_g3entidades_subtipos.nombre
		";
        return guarani_db::consultar($sql);
    }

    function get_descripcion_entidad($entidad, $entidad_tipo = null)
    {
        if (!$entidad_tipo) {
            $entidad_tipo = g3entidad::get_tipo_entidad($entidad);
        }
        $tabla_tipo = g3entidad::get_tabla_tipo($entidad_tipo);
        $datos_entidad = guarani::co('co_g3entidades')->get_listado_tipo_detallado($entidad_tipo, "$tabla_tipo.entidad = $entidad");
        return $datos_entidad[0];
    }

    /**
     * Retorna información del período de inscripción de la Propuesta Aspira Dada
     *
     * @param <type> $propuesta_aspira
     * @return <type>
     */
    function get_datos_periodo_insc($propuesta_aspira)
    {
        $propuesta_aspira = toba::db()->quote($propuesta_aspira);
        $sql = "SELECT	vw_periodos_insc_propuesta.anio_academico,
						vw_periodos_insc_propuesta.fecha_inicio,
						vw_periodos_insc_propuesta.fecha_fin,
						vw_periodos_insc_propuesta.nombre as periodo_nombre,
						vw_periodos_insc_propuesta.fecha_cierre_requisitos

				FROM	sga_propuestas_aspira,
						vw_periodos_insc_propuesta
				WHERE	vw_periodos_insc_propuesta.periodo_insc = sga_propuestas_aspira.periodo_insc
				AND		sga_propuestas_aspira.propuesta_aspira = $propuesta_aspira";
        return guarani_db::consultar_fila($sql);
    }

	/**
      Obtiene una lista de periodos de inscripción/reinscripción a propuestas según el filtro recibido
     */
    function get_periodos_inscripcion($where = '')
    {
        if ($where) {
            $where = ' AND ' . $where . ' ';
        }

        $sql = "SELECT 	periodo_inscripcion as valor,
						nombre as descr
  				FROM 	sga_periodos_inscripcion
				WHERE	periodo_generico_tipo = '4'
  						$where
				ORDER BY descr
				;";
        return guarani_db::consultar($sql);
    }

    /**
     * Retorna una lista de los Periodos de Inscripción junto con la cantidad de Inscripciones Pendientes para cada uno.
     * 
     */
    function get_periodos_inscripcion_inscriptos($where='')
    {
        if ($where) {
            $where = " AND $where";
        }
        $sql = "SELECT		sga_periodos_inscripcion.periodo_inscripcion,
							sga_periodos_inscripcion_fechas.periodo_insc,
							sga_periodos_inscripcion.nombre,
							sga_periodos_inscripcion_propuesta.anio_academico,
							sga_periodos_inscripcion_propuesta.fecha_cierre_requisitos,
							to_char(sga_periodos_inscripcion_propuesta.fecha_cierre_requisitos,'" . comunes::formato_fecha_visual . "') as fecha_cierre_requisitos_formato_visual,
							(
								SELECT 	COUNT(sga_propuestas_aspira.propuesta_aspira) 
								FROM 	sga_propuestas_aspira
										JOIN sga_situacion_aspirante ON sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp
								WHERE 	sga_periodos_inscripcion_fechas.periodo_insc = sga_propuestas_aspira.periodo_insc AND
										sga_situacion_aspirante.resultado_asp = '".propuesta::inscripcion_aceptada."'
							) 	as cant_aceptadas,
							(
								SELECT 	COUNT(sga_propuestas_aspira.propuesta_aspira) 
								FROM 	sga_propuestas_aspira
										JOIN sga_situacion_aspirante ON sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp
								WHERE 	sga_periodos_inscripcion_fechas.periodo_insc  = sga_propuestas_aspira.periodo_insc AND
										sga_situacion_aspirante.resultado_asp = '".propuesta::inscripcion_pendiente."'
							) 	as cant_pendientes
				FROM		sga_periodos_inscripcion
							JOIN sga_periodos_inscripcion_propuesta ON sga_periodos_inscripcion.periodo_inscripcion = sga_periodos_inscripcion_propuesta.periodo_inscripcion
							JOIN sga_periodos_inscripcion_fechas ON sga_periodos_inscripcion.periodo_inscripcion = sga_periodos_inscripcion_fechas.periodo_inscripcion
							JOIN sga_periodos_inscripcion_aplanado ON sga_periodos_inscripcion_fechas.periodo_insc = sga_periodos_inscripcion_aplanado.periodo_insc
							JOIN sga_planes_versiones ON sga_periodos_inscripcion_aplanado.plan_version = sga_planes_versiones.plan_version
				WHERE		true
							$where
				GROUP BY	sga_periodos_inscripcion.periodo_inscripcion,
							sga_periodos_inscripcion_fechas.periodo_insc,
							sga_periodos_inscripcion.nombre,
							sga_periodos_inscripcion_propuesta.anio_academico,
							sga_periodos_inscripcion_propuesta.fecha_cierre_requisitos
				ORDER BY	sga_periodos_inscripcion_propuesta.anio_academico DESC
		";
		return guarani_db::consultar($sql);
	}

    function get_periodos_inscripcion_para_gen_legajos($where = '')
    {
        if ($where) {
            $where = "WHERE $where";
        }
		$formato_fecha_visual = toba::db()->quote(comunes::formato_fecha_visual);
        $sql = "SELECT		sga_periodos_inscripcion.periodo_inscripcion,
							sga_periodos_inscripcion.nombre,
							sga_periodos_inscripcion_propuesta.anio_academico,
							sga_periodos_inscripcion_propuesta.fecha_cierre_requisitos,
							to_char(sga_periodos_inscripcion_propuesta.fecha_cierre_requisitos, $formato_fecha_visual) as fecha_cierre_requisitos_formato_visual,
							(	SELECT	COUNT(sga_propuestas_aspira.propuesta_aspira)
								FROM 	sga_periodos_inscripcion_fechas
										JOIN sga_propuestas_aspira ON sga_periodos_inscripcion_fechas.periodo_insc = sga_propuestas_aspira.periodo_insc
										JOIN sga_situacion_aspirante ON sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp
										JOIN sga_alumnos ON sga_propuestas_aspira.persona = sga_alumnos.persona AND sga_propuestas_aspira.propuesta = sga_alumnos.propuesta
								WHERE 	sga_periodos_inscripcion_fechas.periodo_inscripcion = sga_periodos_inscripcion.periodo_inscripcion AND		
										sga_situacion_aspirante.resultado_asp = '".propuesta::inscripcion_aceptada."' AND
										sga_alumnos.legajo IS NULL AND
										sga_alumnos.calidad = '".alumno::calidad_activo."') as cant_aceptadas,
							(	SELECT	COUNT(sga_propuestas_aspira.propuesta_aspira)
								FROM 	sga_periodos_inscripcion_fechas
										JOIN sga_propuestas_aspira ON sga_periodos_inscripcion_fechas.periodo_insc = sga_propuestas_aspira.periodo_insc
										JOIN sga_situacion_aspirante ON sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp
										JOIN sga_alumnos ON sga_propuestas_aspira.persona = sga_alumnos.persona AND sga_propuestas_aspira.propuesta = sga_alumnos.propuesta
								WHERE 	sga_periodos_inscripcion_fechas.periodo_inscripcion = sga_periodos_inscripcion.periodo_inscripcion AND
										sga_situacion_aspirante.resultado_asp = '".propuesta::inscripcion_pendiente."' AND
										sga_alumnos.legajo IS NULL AND
										sga_alumnos.calidad = '".alumno::calidad_activo."') as cant_pendientes
				FROM		sga_periodos_inscripcion
							JOIN sga_periodos_inscripcion_propuesta ON sga_periodos_inscripcion.periodo_inscripcion = sga_periodos_inscripcion_propuesta.periodo_inscripcion
							JOIN sga_periodos_inscripcion_fechas ON sga_periodos_inscripcion.periodo_inscripcion = sga_periodos_inscripcion_fechas.periodo_inscripcion
							JOIN sga_periodos_inscripcion_aplanado ON sga_periodos_inscripcion_fechas.periodo_insc = sga_periodos_inscripcion_aplanado.periodo_insc
							JOIN sga_planes_versiones ON sga_periodos_inscripcion_aplanado.plan_version = sga_planes_versiones.plan_version
				$where
				GROUP BY	sga_periodos_inscripcion.periodo_inscripcion,
							sga_periodos_inscripcion.nombre,
							sga_periodos_inscripcion_propuesta.anio_academico,
							sga_periodos_inscripcion_propuesta.fecha_cierre_requisitos
				ORDER BY	sga_periodos_inscripcion.nombre
		";
        return guarani_db::consultar($sql);
    }

    function get_periodos_inscripcion_genericos_inscriptos($where = '')
    {
        if ($where) {
            $where = " AND $where";
        }
        $sql = "SELECT		sga_periodos_inscripcion.periodo_inscripcion,
							sga_periodos_inscripcion.nombre,
							sga_periodos_inscripcion_propuesta.anio_academico,
							sga_periodos_inscripcion_propuesta.fecha_cierre_requisitos,
							to_char(sga_periodos_inscripcion_propuesta.fecha_cierre_requisitos,'" . comunes::formato_fecha_visual . "') as fecha_cierre_requisitos_formato_visual,
							(
								SELECT 	COUNT (sga_propuestas_aspira.propuesta_aspira) 
								FROM 	sga_propuestas_aspira,
										sga_situacion_aspirante,
										sga_periodos_inscripcion_fechas
								WHERE 	sga_periodos_inscripcion_fechas.periodo_insc  = sga_propuestas_aspira.periodo_insc
										AND sga_periodos_inscripcion_fechas.periodo_inscripcion	= sga_periodos_inscripcion.periodo_inscripcion
										AND sga_propuestas_aspira.situacion_asp = sga_situacion_aspirante.situacion_asp
										AND	sga_situacion_aspirante.resultado_asp = '" . propuesta::inscripcion_pendiente . "'
							) 	as cant_pendientes
				FROM		sga_periodos_inscripcion_propuesta,
							sga_periodos_inscripcion,
							sga_periodos_inscripcion_fechas,
							sga_periodos_inscripcion_aplanado,
							sga_planes_versiones
				WHERE		sga_periodos_inscripcion_propuesta.periodo_inscripcion = sga_periodos_inscripcion.periodo_inscripcion AND
							sga_periodos_inscripcion.periodo_inscripcion = sga_periodos_inscripcion_fechas.periodo_inscripcion AND
							sga_periodos_inscripcion_fechas.periodo_insc = sga_periodos_inscripcion_aplanado.periodo_insc AND
							sga_periodos_inscripcion_aplanado.plan_version = sga_planes_versiones.plan_version AND
							sga_periodos_inscripcion_propuesta.periodo_insc_tipo in ('" . periodo_de_inscripcion::tipo_inscripcion . "', '" . periodo_de_inscripcion::tipo_todos . "')
							$where
				GROUP BY	sga_periodos_inscripcion.periodo_inscripcion,
							sga_periodos_inscripcion.nombre,
							sga_periodos_inscripcion_propuesta.anio_academico,
							sga_periodos_inscripcion_propuesta.fecha_cierre_requisitos
				ORDER BY	sga_periodos_inscripcion_propuesta.anio_academico DESC,
							sga_periodos_inscripcion.nombre
		";
        return guarani_db::consultar($sql);
    }

    /**
      Obtiene los datos de un Período de Inscripción Propuesta
     */
    function get_datos_periodo_inscripcion_propuesta($periodo_inscripcion)
    {
        $periodo_inscripcion = toba::db()->quote($periodo_inscripcion);

        $sql = "SELECT	vw_periodos_insc_propuesta.nombre as periodo_nombre,
						vw_periodos_insc_propuesta.anio_academico,
						vw_periodos_insc_propuesta.fecha_cierre_requisitos

				FROM	vw_periodos_insc_propuesta
						
				WHERE	vw_periodos_insc_propuesta.periodo_inscripcion = $periodo_inscripcion
				;";

        return guarani_db::consultar_fila($sql);
    }

    /**
     * Obtiene las modalidades de un Período de Inscripción
     */
    function get_modalidades_periodo_inscripcion($periodo_inscripcion)
    {
        $periodo_inscripcion = toba::db()->quote($periodo_inscripcion);

        $sql = "SELECT	sga_modalidad_cursada.modalidad,
						sga_modalidad_cursada.nombre
				FROM	sga_per_insc_modalidad,
						sga_modalidad_cursada
				WHERE	sga_per_insc_modalidad.periodo_inscripcion = $periodo_inscripcion
				AND		sga_per_insc_modalidad.modalidad = sga_modalidad_cursada.modalidad
				";

        return guarani_db::consultar($sql);
    }

    /**
     * Obtiene las ubicaciones de un Período de Inscripción
     */
    function get_ubicaciones_periodo_inscripcion($periodo_inscripcion)
    {
        $periodo_inscripcion = toba::db()->quote($periodo_inscripcion);

        $sql = "SELECT	sga_ubicaciones.ubicacion,
						sga_ubicaciones.nombre
				FROM	sga_per_insc_ubicacion,
						sga_ubicaciones
				WHERE	sga_per_insc_ubicacion.periodo_inscripcion = $periodo_inscripcion
				AND		sga_per_insc_ubicacion.ubicacion = sga_ubicaciones.ubicacion
				";

        return guarani_db::consultar($sql);
    }

    /**
     * Obtiene los datos de todos los Períodos de Inscripción Fechas definidos para el Período Lectivo, los Planes 
     * (planes-versiones asociados a los mismos), la Ubicación y las Modalidades dadas como parámetro.
     *
     * @param integer	$p_lectivo		id del período lectivo para el cual se quieren recuperar los períodos de inscripción asociados.
     * @param array		$planes 		arreglo que contiene los planes para los cuales se quieren recuperar los períodos de inscripción definidos. 
     * @param integer	$ubicacion		id de la ubicación para la cual se quieren recuperar los períodos de inscripción definidos.
     * @param arrat		$modalidades	arreglo que contiene las modalidades para las cuales se quieren recuperar los períodos de inscripción definidos.
     */
    function get_datos_per_insc_planes_ubicacion_modalidad($p_lectivo = null, $planes, $ubicacion = null, $modalidades)
    {
        $where = '';
        if (!is_null($p_lectivo)) {
            $p_lectivo = toba::db()->quote($p_lectivo);
            $where .= " AND sga_periodos_lectivos.periodo_lectivo = $p_lectivo ";
        }
        if (!is_null($ubicacion)) {
            $ubicacion = toba::db()->quote($ubicacion);
        }
        if (!empty($planes)) {
            $where .= ' AND ' . guarani_sql::armar_condicion_in($planes, 'sga_planes_versiones', 'plan', 'plan');
        }

        $sql = "SELECT		DISTINCT sga_periodos_inscripcion_fechas.periodo_insc,
							sga_periodos_inscripcion_fechas.periodo_inscripcion,
							sga_periodos_inscripcion_fechas.fecha_inicio,
							sga_periodos_inscripcion_fechas.fecha_fin,
							sga_periodos_inscripcion_fechas.fecha_tope_bajas,
							sga_periodos_inscripcion_fechas.habilitado_interfaz,
							sga_periodos_inscripcion_fechas.tipo_cursado,
							sga_periodos_inscripcion_fechas.tipo_alumno,
							sga_periodos_inscripcion.nombre as periodo_insc_nombre,
							sga_periodos.anio_academico
				FROM		sga_periodos_inscripcion_aplanado,
							sga_planes_versiones,
							sga_periodos_inscripcion_fechas,
							sga_periodos_inscripcion,
							sga_periodos_lectivos,
							sga_periodos
				WHERE		sga_periodos_inscripcion_aplanado.plan_version = sga_planes_versiones.plan_version AND
							sga_periodos_inscripcion_aplanado.periodo_insc = sga_periodos_inscripcion_fechas.periodo_insc AND
							sga_periodos_inscripcion_fechas.periodo_inscripcion = sga_periodos_inscripcion.periodo_inscripcion AND
							sga_periodos_inscripcion.periodo = sga_periodos.periodo AND
							sga_periodos_lectivos.periodo = sga_periodos.periodo AND
							EXISTS	(SELECT 1	FROM	sga_per_insc_modalidad 
              									WHERE	sga_per_insc_modalidad.periodo_inscripcion = sga_periodos_inscripcion.periodo_inscripcion AND "
                . guarani_sql::armar_condicion_in($modalidades, 'sga_per_insc_modalidad', 'modalidad', 'modalidad') . "
                					) AND
                			EXISTS (SELECT 1	FROM	sga_per_insc_ubicacion
              									WHERE	sga_per_insc_ubicacion.periodo_inscripcion = sga_periodos_inscripcion.periodo_inscripcion AND 
              											sga_per_insc_ubicacion.ubicacion = $ubicacion
           					)
							$where
				ORDER BY	sga_periodos.anio_academico, sga_periodos_inscripcion.nombre
				;";

        return guarani_db::consultar($sql);
    }

    function get_aspirantes_propuesta($where = '')
    {
        if ($where) {
            $where = ' AND ' . $where;
        }

        $sql = "SELECT 
					sga_propuestas_aspira.propuesta_aspira,
                    sga_alumnos.alumno,
					sga_propuestas_aspira.persona,
					sga_propuestas_aspira.periodo_insc,
					sga_propuestas_aspira.anio_academico,
					sga_propuestas_aspira.propuesta,
					sga_propuestas_aspira.plan_version,
					sga_propuestas_aspira.modalidad,
					sga_propuestas_aspira.ubicacion,
					sga_propuestas_aspira.fecha_inscripcion,
					sga_propuestas_aspira.fecha_alta,
					sga_propuestas_aspira.tipo_ingreso,
					sga_propuestas_aspira.convenio,
					sga_propuestas_aspira.insc_fuera_de_termino,
					sga_propuestas_aspira.motivo_inscripcion,
					sga_propuestas_aspira.situacion_asp,
					sga_propuestas_aspira.observaciones,
					sga_propuestas_aspira.nro_transaccion
				FROM 
					sga_propuestas_aspira,
					sga_situacion_aspirante,
					sga_alumnos,
					sga_periodos_inscripcion_fechas
				WHERE
					sga_situacion_aspirante.situacion_asp = sga_propuestas_aspira.situacion_asp
                    AND sga_alumnos.persona = sga_propuestas_aspira.persona
                    AND sga_alumnos.propuesta = sga_propuestas_aspira.propuesta	
                    AND sga_propuestas_aspira.periodo_insc = sga_periodos_inscripcion_fechas.periodo_insc
					$where
				";


        return guarani_db::consultar($sql);
    }

    function validar_nombre_llamado($nombre, $turno_examen, $llamado = null)
    {
        if (!isset($nombre)) {
            return false;
        }

        $nombre = toba::db()->quote($nombre);
        $turno_examen = toba::db()->quote($turno_examen);

        $sql = "SELECT 
					COUNT (sga_llamados_turno.periodo) AS cant
				FROM 
					sga_llamados_turno,
				    sga_periodos,
				    sga_turnos_examen,
				    sga_periodos as turno
				WHERE
					sga_periodos.periodo = 	sga_llamados_turno.periodo
				    AND sga_turnos_examen.turno_examen = sga_llamados_turno.turno_examen
				    AND sga_turnos_examen.periodo = turno.periodo
				    AND sga_periodos.nombre = $nombre
				    AND sga_llamados_turno.turno_examen = $turno_examen
				";

        if (!is_null($llamado)) {
            $llamado = toba::db()->quote($llamado);
            $sql .= " AND sga_llamados_turno.llamado <> $llamado ";
        }

        $res = guarani_db::consultar_fila($sql);
        return ($res['cant'] == 0);
    }

    function validar_periodos_en_rango($periodo, $fecha_inicio, $fecha_fin)
    {
        $periodo = toba::db()->quote($periodo);
        $fecha_inicio = toba::db()->quote($fecha_inicio);
        $fecha_fin = toba::db()->quote($fecha_fin);

        $sql = "SELECT 
					COUNT (1) AS cant
				FROM 
					sga_periodos_inscripcion,
				    sga_periodos_inscripcion_fechas
				WHERE 
					sga_periodos_inscripcion.periodo = $periodo
				    AND sga_periodos_inscripcion_fechas.periodo_inscripcion = sga_periodos_inscripcion.periodo_inscripcion
				    AND (CAST(sga_periodos_inscripcion_fechas.fecha_fin AS DATE) > DATE $fecha_fin)
				";

        $res = guarani_db::consultar_fila($sql);

        return ($res['cant'] == 0);
    }

    function validar_superposicion_periodos($periodo, $entidad_tipo, $fecha_inicio, $fecha_fin, $periodo_insc = null)
    {
        $periodo = toba::db()->quote($periodo);
        $entidad_tipo = toba::db()->quote($entidad_tipo);
        $fecha_inicio = toba::db()->quote($fecha_inicio);
        $fecha_fin = toba::db()->quote($fecha_fin);

        if (isset($periodo_insc)) {
            $periodo_insc = toba::db()->quote($periodo_insc);
            $where = "AND sga_periodos_inscripcion_fechas.periodo_insc <> $periodo_insc";
        } else {
            $where = '';
        }

        $sql = "SELECT 
					COUNT (1) AS cant
				FROM 
					sga_periodos_inscripcion,
				    sga_periodos_inscripcion_fechas
				WHERE 
					sga_periodos_inscripcion.periodo = $periodo
					AND sga_periodos_inscripcion.periodo_inscripcion = sga_periodos_inscripcion_fechas.periodo_inscripcion
				    AND sga_periodos_inscripcion_fechas.entidad_tipo = $entidad_tipo
				    AND ((sga_periodos_inscripcion_fechas.fecha_inicio >= DATE $fecha_inicio
						AND sga_periodos_inscripcion_fechas.fecha_inicio <= DATE $fecha_fin )
						OR	(sga_periodos_inscripcion_fechas.fecha_fin >= DATE $fecha_inicio
						AND sga_periodos_inscripcion_fechas.fecha_fin <= DATE $fecha_fin ))
				    	 
				    $where
				";

        $res = guarani_db::consultar_fila($sql);

        return ($res['cant'] > 0);
    }

    /**
     * Retorna el listado de períodos de inscripción (con sus fechas y nivel de aplicación) para un período dado.
     *
     * @param integer $periodo
     * @return array
     */
    function get_periodos_inscripcion_por_periodo($periodo, $where = '')
    {
        $periodo = toba::db()->quote($periodo);
        if ($where) {
            $where = " AND $where";
        }
        $sql = "SELECT	sga_periodos_inscripcion.periodo_inscripcion,
						sga_periodos_inscripcion.nombre,
						sga_periodos_inscripcion_fechas.periodo_insc,
						sga_periodos_inscripcion_fechas.fecha_inicio,
						to_char(sga_periodos_inscripcion_fechas.fecha_inicio,'" . comunes::formato_fecha_hora_visual_sin_segundos . "') as fecha_inicio_visual,
						sga_periodos_inscripcion_fechas.fecha_fin,
						to_char(sga_periodos_inscripcion_fechas.fecha_fin,'" . comunes::formato_fecha_hora_visual_sin_segundos . "') as fecha_fin_visual,
						sga_periodos_inscripcion_fechas.dias_previos_fin,
						sga_periodos_inscripcion_fechas.hs_previas_fin,
						sga_periodos_inscripcion_fechas.fecha_tope_bajas,
						to_char(sga_periodos_inscripcion_fechas.fecha_tope_bajas,'" . comunes::formato_fecha_hora_visual_sin_segundos . "') as fecha_tope_bajas_visual,
						sga_periodos_inscripcion_fechas.dias_previos_tope_bajas,
						sga_periodos_inscripcion_fechas.hs_previas_tope_bajas,
						sga_periodos_inscripcion_fechas.tipo_fin,
						sga_periodos_inscripcion_fechas.tipo_tope_bajas,
						sga_periodos_inscripcion_fechas.tipo,
						sga_periodos_inscripcion_fechas.entidad_tipo,
						  (CASE sga_periodos_inscripcion_fechas.tipo_fin
						     WHEN 1 THEN to_char(sga_periodos_inscripcion_fechas.fecha_fin,'" . comunes::formato_fecha_hora_visual_sin_segundos . "')
						     WHEN 2 THEN cast(sga_periodos_inscripcion_fechas.dias_previos_fin as text) || ' dias antes de cada mesa'
						     WHEN 3 THEN cast(sga_periodos_inscripcion_fechas.dias_previos_fin as text) || ' dias hábiles antes de cada mesa'
						     WHEN 4 THEN cast(sga_periodos_inscripcion_fechas.hs_previas_fin  as text) || ' hs antes de cada mesa'
						     WHEN 5 THEN cast(sga_periodos_inscripcion_fechas.hs_previas_fin  as text) || ' hs hábiles antes de cada mesa'
						  END) as fin_descripcion,
						  (CASE sga_periodos_inscripcion_fechas.tipo_tope_bajas
						     WHEN 1 THEN to_char(sga_periodos_inscripcion_fechas.fecha_tope_bajas,'" . comunes::formato_fecha_hora_visual_sin_segundos . "')
						     WHEN 2 THEN cast(sga_periodos_inscripcion_fechas.dias_previos_tope_bajas as text) || ' dias antes de cada mesa'
						     WHEN 3 THEN cast(sga_periodos_inscripcion_fechas.dias_previos_tope_bajas as text) || ' dias hábiles antes de cada mesa'
						     WHEN 4 THEN cast(sga_periodos_inscripcion_fechas.hs_previas_tope_bajas  as text) || ' hs antes de cada mesa'
						     WHEN 4 THEN cast(sga_periodos_inscripcion_fechas.hs_previas_tope_bajas  as text) || ' hs hábiles antes de cada mesa'
						  END) as fin_bajas_descripcion
				FROM	sga_periodos_inscripcion,
						sga_periodos_inscripcion_fechas
				WHERE	sga_periodos_inscripcion.periodo_inscripcion = sga_periodos_inscripcion_fechas.periodo_inscripcion AND
						sga_periodos_inscripcion.periodo = $periodo
						$where
		";
        $rs = guarani_db::consultar($sql);
        foreach ($rs as $clave => $valor) {
            $etiquetas = guarani_etiquetas::get_tabla(g3entidad::get_tabla_tipo($valor['entidad_tipo']));
            switch ($valor['tipo']) {
                case g3entidad::nivel_aplicacion_tipo_entidad:
                    $rs[$clave]['nivel_aplicacion'] = ($valor['entidad_tipo'] == g3entidad::tipo_institucion) ? 'Toda la Institución' : $etiquetas->get('Algunos');
                    break;
                case g3entidad::nivel_aplicacion_tipo_subtipo:
                    $rs[$clave]['nivel_aplicacion'] = 'Algunos Tipos de ' . $etiquetas->get_plural();
            }
        }
        return $rs;
    }

    /**
     * Retorna el listado de períodos de inscripción para un año académico dado.
     * 
     * @param integer $anio_academico
     * @return array
     */
    function get_periodos_inscripcion_por_anio_academico($anio_academico)
    {
        $anio_academico = toba::db()->quote($anio_academico);
        $sql = "SELECT		sga_periodos_inscripcion.periodo_inscripcion,
							sga_periodos_inscripcion.nombre
				FROM		sga_periodos_inscripcion,
							sga_periodos_inscripcion_propuesta
				WHERE		sga_periodos_inscripcion_propuesta.periodo_inscripcion = sga_periodos_inscripcion.periodo_inscripcion AND
							sga_periodos_inscripcion_propuesta.periodo_insc_tipo IN ('" . periodo_de_inscripcion::tipo_inscripcion . "', '" . periodo_de_inscripcion::tipo_todos . "') AND
							sga_periodos_inscripcion_propuesta.anio_academico = $anio_academico
				ORDER BY	sga_periodos_inscripcion.nombre
		";
        return guarani_db::consultar($sql);
    }

    /**
     * Retorna el listado de períodos de PREINSCRIPCIÓN para un año académico dado.
     * 
     * @param integer $anio_academico
     * @return array
     */
    function get_periodos_preinscripcion_por_anio_academico($anio_academico)
    {
        $anio_academico = toba::db()->quote($anio_academico);
        $sql = "SELECT		sga_periodos_inscripcion.periodo_inscripcion,
							sga_periodos_inscripcion.nombre
				FROM		sga_periodos_inscripcion 
							JOIN sga_periodos_inscripcion_propuesta ON sga_periodos_inscripcion.periodo_inscripcion = sga_periodos_inscripcion_propuesta.periodo_inscripcion
				WHERE		sga_periodos_inscripcion_propuesta.periodo_insc_tipo IN ('".periodo_de_inscripcion::tipo_preinscripcion."', '".periodo_de_inscripcion::tipo_todos."') AND
							sga_periodos_inscripcion_propuesta.anio_academico = $anio_academico
				ORDER BY	sga_periodos_inscripcion.nombre
		";
        return guarani_db::consultar($sql);
    }

    /**
     * Retorna verdadero en caso de que exista alguna inscripción a propuesta asociada al período de inscripción y false en caso contrario
     */
    function tiene_inscripciones_a_propuesta($periodo_insc)
    {
        $periodo_insc = toba::db()->quote($periodo_insc);

        $sql = "SELECT	COUNT(*) AS cantidad
				FROM	sga_propuestas_aspira
				WHERE	sga_propuestas_aspira.periodo_insc = $periodo_insc
				";

        $datos = guarani_db::consultar_fila($sql);
        return ($datos['cantidad'] > 0);
    }

    /**
     * Obtiene el último período de inscripción ya iniciado definido para la 
     * propuesta y año académico recibidos
     * 
     * @param int $propuesta
     * @param int $anio_academico
     * @return array
     */
    function get_periodo_inscripcion_para_insc_cursos($plan_version, $modalidad, $ubicacion, $anio_academico, $interfaz=comunes::interfaz_gestion)
    {
        $plan_version = toba::db()->quote($plan_version);
		$modalidad = toba::db()->quote($modalidad);
		$ubicacion = toba::db()->quote($ubicacion);
        $anio_academico = toba::db()->quote($anio_academico);
		$interfaz = toba::db()->quote($interfaz);
        $sql = "SELECT		vw_periodos_insc_propuesta.periodo_insc,  -- es el que va a sga_propuestas_aspira.periodo_insc
							vw_periodos_insc_propuesta.periodo_inscripcion
				FROM		vw_periodos_insc_propuesta,
							sga_per_insc_modalidad,
							sga_per_insc_ubicacion,
							sga_periodos_inscripcion_aplanado
				WHERE		vw_periodos_insc_propuesta.periodo_inscripcion = sga_per_insc_modalidad.periodo_inscripcion AND
							vw_periodos_insc_propuesta.periodo_inscripcion = sga_per_insc_ubicacion.periodo_inscripcion AND
							vw_periodos_insc_propuesta.periodo_insc = sga_periodos_inscripcion_aplanado.periodo_insc AND

							vw_periodos_insc_propuesta.anio_academico = $anio_academico AND
							vw_periodos_insc_propuesta.periodo_insc_tipo IN ('".periodo_de_inscripcion::tipo_inscripcion."', '".periodo_de_inscripcion::tipo_todos."') AND
							vw_periodos_insc_propuesta.habilitado = '".comunes::si."' AND
							vw_periodos_insc_propuesta.habilitado_interfaz IN (".comunes::interfaz_todas.", $interfaz) AND
							vw_periodos_insc_propuesta.fecha_inicio <= CURRENT_TIMESTAMP AND

							sga_per_insc_modalidad.modalidad = $modalidad AND 
							sga_per_insc_ubicacion.ubicacion = $ubicacion AND 

							sga_periodos_inscripcion_aplanado.plan_version = $plan_version
				ORDER BY	vw_periodos_insc_propuesta.fecha_fin DESC
				LIMIT		1
		";
        return guarani_db::consultar_fila($sql);
    }

    /**
     * Obtiene el  período de inscripción de la comisión
     * @param int $comision
     */
    function get_periodo_inscripcion_por_comision($comision)
    {
		
		$formato_fecha_visual = toba::db()->quote(comunes::formato_fecha_visual);
		
        $sql = "         
            SELECT to_char(COALESCE(e.fecha_inicio,sga_periodos_inscripcion_fechas.fecha_inicio), $formato_fecha_visual) as inicio,
                  to_char(COALESCE(e.fecha_fin,sga_periodos_inscripcion_fechas.fecha_fin), $formato_fecha_visual) as fin
             FROM sga_comisiones,
                  sga_periodos_lectivos,
                  sga_periodos,
                  sga_periodos_inscripcion,
                  sga_periodos_inscripcion_fechas
                  LEFT JOIN sga_comisiones_excep_perinsc as e ON e.periodo_insc = sga_periodos_inscripcion_fechas.periodo_insc AND e.comision = $comision
            WHERE sga_comisiones.comision = $comision
              AND sga_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo
              AND sga_periodos.periodo = sga_periodos_lectivos.periodo
              AND sga_periodos_inscripcion.periodo = sga_periodos_lectivos.periodo
              AND sga_periodos_inscripcion_fechas.periodo_inscripcion = sga_periodos_inscripcion.periodo_inscripcion
              AND sga_periodos_inscripcion_fechas.habilitado = 'S'
            ";
        $datos = guarani::db()->consultar($sql);
        return $datos;
    }

    /**
     * Obtiene períodos de inscripción vigentes del plan versión
     * @param integer $plan_version
     * @param integer $interfaz
     *
     * @return array Lista de períodos de inscripción vigentes del plan versión
     */
    function get_periodos_inscripcion_vigentes_por_plan_version($plan_version, $interfaz = comunes::interfaz_gestion)
    {

        $interfaz_todas         = comunes::interfaz_todas;
        $si                     = comunes::si;
        $tipo_periodo_lectivo   = periodo_generico::tipo_periodo_lectivo;

        $sql = "    SELECT
                        sga_periodos.periodo,
                        sga_periodos.nombre AS periodo_nombre,
                        sga_periodos.anio_academico,
                        sga_periodos_inscripcion.periodo_inscripcion,
                        sga_periodos_inscripcion.nombre AS periodo_inscripcion_nombre,
                        sga_periodos_inscripcion_fechas.fecha_inicio,
                        sga_periodos_inscripcion_fechas.fecha_fin,
                        sga_periodos_inscripcion_fechas.fecha_tope_bajas
                    FROM sga_periodos_inscripcion_aplanado
                        JOIN sga_periodos_inscripcion_fechas ON sga_periodos_inscripcion_fechas.periodo_insc = sga_periodos_inscripcion_aplanado.periodo_insc
                        JOIN sga_periodos_inscripcion ON sga_periodos_inscripcion.periodo_inscripcion = sga_periodos_inscripcion_fechas.periodo_inscripcion
                        JOIN sga_periodos ON sga_periodos.periodo = sga_periodos_inscripcion.periodo
                        JOIN sga_periodos_genericos ON sga_periodos_genericos.periodo_generico = sga_periodos.periodo_generico
                    WHERE sga_periodos_inscripcion_aplanado.plan_version = $plan_version
                        AND sga_periodos_inscripcion_fechas.habilitado = '$si'
                        AND sga_periodos_inscripcion_fechas.habilitado_interfaz IN ($interfaz_todas, $interfaz)
                        AND sga_periodos_genericos.periodo_generico_tipo = $tipo_periodo_lectivo -- Periodos Lectivos.
                        AND CURRENT_TIMESTAMP BETWEEN sga_periodos_inscripcion_fechas.fecha_inicio AND get_mayor(sga_periodos_inscripcion_fechas.fecha_fin, sga_periodos_inscripcion_fechas.fecha_tope_bajas)
                    ORDER BY sga_periodos_inscripcion_fechas.fecha_inicio";

        return guarani_db::consultar($sql, null, false);
    }

    /**
     * Obtiene próximo período de inscripción vigente del plan versión
     * @param integer $plan_version
     * @param integer $interfaz
     *
     * @return array Próximo período de inscripción vigente del plan versión
     */
    function get_proximo_periodo_inscripcion_vigente_por_plan_version($plan_version, $interfaz = comunes::interfaz_gestion)
    {

        $interfaz_todas         = comunes::interfaz_todas;
        $si                     = comunes::si;
        $tipo_periodo_lectivo   = periodo_generico::tipo_periodo_lectivo;

        $sql = "    SELECT
                        sga_periodos.periodo,
                        sga_periodos.nombre AS periodo_nombre,
                        sga_periodos.anio_academico,
                        sga_periodos_inscripcion.periodo_inscripcion,
                        sga_periodos_inscripcion.nombre AS periodo_inscripcion_nombre,
                        sga_periodos_inscripcion_fechas.fecha_inicio,
                        sga_periodos_inscripcion_fechas.fecha_fin,
                        sga_periodos_inscripcion_fechas.fecha_tope_bajas,
                        CASE extract(dow from sga_periodos_inscripcion_fechas.fecha_inicio)
                                WHEN 0 THEN '".comunes::domingo."'
                                WHEN 1 THEN '".comunes::lunes."'
                                WHEN 2 THEN '".comunes::martes."'
                                WHEN 3 THEN '".comunes::miercoles."'
                                WHEN 4 THEN '".comunes::jueves."'
                                WHEN 5 THEN '".comunes::viernes."'
                                WHEN 6 THEN '".comunes::sabado."'
                                END
                                ||' '||
                                to_char(sga_periodos_inscripcion_fechas.fecha_inicio,'dd')
                                ||' de '||
                        CASE extract(MONTH from sga_periodos_inscripcion_fechas.fecha_inicio)
                                WHEN 1 THEN '".comunes::enero."'
                                WHEN 2 THEN '".comunes::febrero."'
                                WHEN 3 THEN '".comunes::marzo."'
                                WHEN 4 THEN '".comunes::abril."'
                                WHEN 5 THEN '".comunes::mayo."'
                                WHEN 6 THEN '".comunes::junio."'
                                WHEN 7 THEN '".comunes::julio."'
                                WHEN 8 THEN '".comunes::agosto."'
                                WHEN 9 THEN '".comunes::septiembre."'
                                WHEN 10 THEN '".comunes::octubre."'
                                WHEN 11 THEN '".comunes::noviembre."'
                                WHEN 12 THEN '".comunes::diciembre."'
                                END
                                ||' del '||
                        trim(to_char(sga_periodos_inscripcion_fechas.fecha_inicio,'yyyy a las HH24:MI hs')) AS fecha_inicio_visual
                    FROM sga_periodos_inscripcion_aplanado
                        JOIN sga_periodos_inscripcion_fechas ON sga_periodos_inscripcion_fechas.periodo_insc = sga_periodos_inscripcion_aplanado.periodo_insc
                        JOIN sga_periodos_inscripcion ON sga_periodos_inscripcion.periodo_inscripcion = sga_periodos_inscripcion_fechas.periodo_inscripcion
                        JOIN sga_periodos ON sga_periodos.periodo = sga_periodos_inscripcion.periodo
                        JOIN sga_periodos_genericos ON sga_periodos_genericos.periodo_generico = sga_periodos.periodo_generico
                    WHERE sga_periodos_inscripcion_aplanado.plan_version = $plan_version
                        AND sga_periodos_inscripcion_fechas.habilitado = '$si'
                        AND sga_periodos_inscripcion_fechas.habilitado_interfaz IN ($interfaz_todas, $interfaz)
                        AND sga_periodos_genericos.periodo_generico_tipo = $tipo_periodo_lectivo -- Periodos Lectivos.
                        AND CURRENT_TIMESTAMP <= sga_periodos_inscripcion_fechas.fecha_inicio
                    ORDER BY sga_periodos_inscripcion_fechas.fecha_inicio
                    LIMIT 1";

        return guarani_db::consultar_fila($sql, null, false);
    }

    /**
     * Obtiene excepciones del período de inscripción del plan versión
     * @param integer $plan_version
     * @param integer $interfaz
     *
     * @return array Lista de excepciones del período de inscripción del plan versión
     */
    function get_excepciones_periodo_inscripcion_por_plan_version($plan_version, $interfaz = comunes::interfaz_gestion)
    {

        $interfaz_todas         = comunes::interfaz_todas;
        $si                     = comunes::si;

        $sql = "    SELECT
                        sga_comisiones.comision,
                        sga_comisiones.nombre AS comision_nombre,
                        sga_comisiones_excep_perinsc.fecha_inicio,
                        sga_comisiones_excep_perinsc.fecha_fin,
                        sga_comisiones_excep_perinsc.fecha_tope_bajas
                    FROM sga_planes_versiones
                        JOIN sga_planes ON sga_planes.plan = sga_planes_versiones.plan
                        JOIN sga_comisiones_propuestas ON sga_comisiones_propuestas.propuesta = sga_planes.propuesta
                        JOIN sga_comisiones ON sga_comisiones.comision = sga_comisiones_propuestas.comision
                        JOIN sga_periodos_lectivos ON sga_periodos_lectivos.periodo_lectivo = sga_comisiones.periodo_lectivo
                        JOIN sga_periodos ON sga_periodos.periodo = sga_periodos_lectivos.periodo
                        JOIN sga_comisiones_excep_perinsc ON sga_comisiones_excep_perinsc.comision = sga_comisiones.comision
                        JOIN sga_periodos_inscripcion_fechas ON sga_periodos_inscripcion_fechas.periodo_insc = sga_comisiones_excep_perinsc.periodo_insc
                    WHERE sga_planes_versiones.plan_version = $plan_version
                        AND sga_periodos.fecha_fin >= CURRENT_DATE
                        AND CURRENT_TIMESTAMP BETWEEN sga_comisiones_excep_perinsc.fecha_inicio AND get_mayor(sga_comisiones_excep_perinsc.fecha_fin, sga_comisiones_excep_perinsc.fecha_tope_bajas)
                        AND sga_comisiones_excep_perinsc.habilitado_interfaz IN ($interfaz_todas, $interfaz)
                        AND sga_periodos_inscripcion_fechas.habilitado = '$si'
                    ORDER BY sga_comisiones_excep_perinsc.fecha_inicio";

        return guarani_db::consultar($sql, null, false);
    }

	function propuesta_habilitada_en_periodo($propuesta, $periodo_inscripcion)
	{
		$sql = "SELECT 
					1 AS habilitada
				FROM 
					sga_periodos_inscripcion_aplanado,
					sga_planes_versiones,
					sga_planes,
					sga_periodos_inscripcion_fechas
				WHERE
					sga_periodos_inscripcion_aplanado.plan_version = sga_planes_versiones.plan_version
					AND sga_planes_versiones.plan = sga_planes.plan
					AND sga_planes.propuesta = $propuesta
					AND sga_periodos_inscripcion_aplanado.periodo_insc = sga_periodos_inscripcion_fechas.periodo_insc
					AND sga_periodos_inscripcion_fechas.periodo_inscripcion = $periodo_inscripcion
				";
		
		$res = guarani_db::consultar_fila($sql);
		return $res['habilitada'];
	}

	/**
	 * Retorna el listado de períodos de inscripción por propuesta/plan-versión según el filtro especificado.
	 * 
	 * @param string $where
	 * @param int $propuesta
	 * @return array
	 */
	function get_periodos_inscripcion_propuesta($where='', $propuesta=null)
	{
		if ($where) {
			$where = " AND $where";
		}
		if (!is_null($propuesta)) {
			$propuesta = toba::db()->quote($propuesta);
			$where .= " AND (sga_periodos_inscripcion_aplanado.propuesta IS NULL OR sga_periodos_inscripcion_aplanado.propuesta = $propuesta)
						AND (sga_periodos_inscripcion_aplanado.plan_version IS NULL OR sga_planes.propuesta = $propuesta)";
		}
		$sql = "SELECT		vw_periodos_inscripcion.nombre as periodo_inscripcion_nombre,
							COALESCE(propuesta_aplanado.nombre_abreviado, propuesta_plan_version.nombre_abreviado) as propuesta_nombre,
							sga_planes.nombre as plan_nombre,
							sga_planes_versiones.nombre as plan_version_nombre,
							vw_periodos_inscripcion.fecha_inicio,
							vw_periodos_inscripcion.sfecha_fin as fecha_fin,
							vw_periodos_inscripcion.fecha_tope_bajas,
							CASE vw_periodos_inscripcion.periodo_generico_tipo
								WHEN ".periodo_generico::tipo_propuesta." THEN vw_periodos_inscripcion.periodo_generico_tipo_nombre || ' - ' || vw_periodos_inscripcion.tipo_insc_propuesta_nombre
								ELSE vw_periodos_inscripcion.periodo_generico_tipo_nombre
							END as periodo_generico_tipo_nombre,
							CASE vw_periodos_inscripcion.periodo_generico_tipo
								WHEN ".periodo_generico::tipo_periodo_lectivo." THEN vw_periodos_inscripcion.periodo_nombre
								WHEN ".periodo_generico::tipo_llamado." THEN (	SELECT	sga_periodos.nombre || '-' || vw_periodos_inscripcion.periodo_nombre
																				FROM	sga_llamados_turno
																						JOIN sga_turnos_examen ON sga_llamados_turno.turno_examen = sga_turnos_examen.turno_examen
																						JOIN sga_periodos ON sga_turnos_examen.periodo = sga_periodos.periodo
																				WHERE	sga_llamados_turno.periodo = vw_periodos_inscripcion.periodo)
								ELSE ''
							END as turno_llamado_periodo_lectivo
				FROM		sga_periodos_inscripcion_aplanado
							JOIN vw_periodos_inscripcion ON sga_periodos_inscripcion_aplanado.periodo_insc = vw_periodos_inscripcion.periodo_insc
							LEFT JOIN sga_propuestas as propuesta_aplanado ON sga_periodos_inscripcion_aplanado.propuesta = propuesta_aplanado.propuesta
							LEFT JOIN sga_planes_versiones ON sga_periodos_inscripcion_aplanado.plan_version = sga_planes_versiones.plan_version
							LEFT JOIN sga_planes ON sga_planes_versiones.plan = sga_planes.plan
							LEFT JOIN sga_propuestas as propuesta_plan_version ON sga_planes.propuesta = propuesta_plan_version.propuesta
				WHERE		true
							$where
				ORDER BY	vw_periodos_inscripcion.fecha_inicio,
							propuesta_nombre,
							sga_planes.nombre,
							sga_planes_versiones.nombre
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna los periodos de inscripicion fechas de un año academico
	 * 
	 * @param int $anio_academico
	 * @return array
	 */
	function get_periodos_insc_fechas_preinsc_de_anio_academico($anio_academico)
	{
		$anio_academico = toba::db()->quote($anio_academico);
        $sql = "SELECT		sga_periodos_inscripcion_fechas.periodo_insc
				FROM		sga_periodos_inscripcion 
							JOIN sga_periodos_inscripcion_propuesta ON sga_periodos_inscripcion_propuesta.periodo_inscripcion	= sga_periodos_inscripcion.periodo_inscripcion			
							JOIN sga_periodos_inscripcion_fechas	ON sga_periodos_inscripcion_fechas.periodo_inscripcion		= sga_periodos_inscripcion.periodo_inscripcion
							
				WHERE		sga_periodos_inscripcion_propuesta.periodo_insc_tipo IN ('".periodo_de_inscripcion::tipo_preinscripcion."', '".periodo_de_inscripcion::tipo_todos."') AND
							sga_periodos_inscripcion_propuesta.anio_academico = $anio_academico

		";
        return guarani_db::consultar($sql);
	}
	
	
	/**
	 * Obtiene el conjunto de IDs de la tabla sga_periodos_inscripcion_fechas
	 * para un $periodo_inscripcion dado
	 * 
	 * @param int $periodo_inscripcion
	 * @return array
	 */
	function get_periodos_insc_fechas_de_periodo_inscripcion($periodo_inscripcion)
	{
		$periodo_inscripcion = toba::db()->quote($periodo_inscripcion);
		
		$sql = "SELECT	periodo_insc
				FROM	sga_periodos_inscripcion_fechas
				WHERE	periodo_inscripcion = $periodo_inscripcion
				";
		
		$datos = guarani_db::consultar($sql);
		return aplanar_matriz($datos, 'periodo_insc');
	}
	
	/**
	 * Retorna el listado de todos los períodos de inscripción a períodos lectivos 
	 * por propuesta, junto a la ubicación, cantidad de rangos cargados y las fechas y hora de inicio y fin 
	 * del período de inscripción correspondiente.
	 * 
	 * @param string $where
	 * @return array
	 */
	function get_periodos_inscripcion_propuesta_con_rangos($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT		DISTINCT vw_periodos_inscripcion.anio_academico,
							vw_periodos_inscripcion.periodo_nombre,
							vw_periodos_inscripcion.periodo_insc,
							vw_periodos_inscripcion.nombre as periodo_inscripcion_nombre,
							vw_planes.propuesta,
							vw_planes.propuesta_nombre,
							vw_periodos_inscripcion.fecha_inicio,
							vw_periodos_inscripcion.fecha_fin,
							vw_periodos_inscripcion.fecha_tope_bajas,
							(	SELECT	COUNT(1)
								FROM	sga_periodos_inscripcion_coeficiente
								WHERE	periodo_insc = vw_periodos_inscripcion.periodo_insc AND
										propuesta = vw_planes.propuesta) as cant_rangos_definidos,
							f_ubicaciones_periodo_inscripcion(vw_periodos_inscripcion.periodo_inscripcion) as ubicaciones
				FROM		vw_periodos_inscripcion
							JOIN sga_periodos_lectivos ON sga_periodos_lectivos.periodo = vw_periodos_inscripcion.periodo
							JOIN sga_periodos_inscripcion_aplanado ON sga_periodos_inscripcion_aplanado.periodo_insc = vw_periodos_inscripcion.periodo_insc
							JOIN vw_planes ON vw_planes.plan_version = sga_periodos_inscripcion_aplanado.plan_version
				WHERE		vw_periodos_inscripcion.periodo_generico_tipo = ".periodo_generico::tipo_periodo_lectivo."
							$where
				ORDER BY	vw_planes.propuesta_nombre,
							vw_periodos_inscripcion.fecha_inicio
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retrona el conjunto de propuestas que tienen definido algún rango de inscripción por coeficiente para un período.
	 * 
	 * @param integer $periodo_insc
	 * @param integer $propuesta
	 * @return array
	 */
	function get_propuestas_con_periodos_inscripcion_por_rangos($periodo_insc, $propuesta)
	{
		$periodo_insc = toba::db()->quote($periodo_insc);
		$propuesta = toba::db()->quote($propuesta);
		$sql = "SELECT		sga_propuestas.propuesta,
							sga_propuestas.nombre,
							sga_propuestas.codigo,
							COUNT(sga_periodos_inscripcion_coeficiente.id_rango) as cant_rangos_coeficiente
				FROM		sga_periodos_inscripcion_coeficiente
							JOIN sga_propuestas ON sga_periodos_inscripcion_coeficiente.propuesta = sga_propuestas.propuesta
				WHERE		sga_periodos_inscripcion_coeficiente.periodo_insc = $periodo_insc AND
							sga_periodos_inscripcion_coeficiente.propuesta <> $propuesta
				GROUP BY	sga_propuestas.propuesta,
							sga_propuestas.nombre,
							sga_propuestas.codigo
				ORDER BY	sga_propuestas.nombre
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Rangos definidos de la propuesta seleccionada.
	 * Esto se recupera cuando se selecciona una propuesta de la cual copiar los rangos definidos.
	 * 
	 * @param integer $periodo_inscripcion
	 * @param integer $propuesta
	 * @return array
	 */
	function get_rangos_de_coeficientes_por_propuestas($periodo_insc, $propuesta)
	{
		$periodo_insc = toba::db()->quote($periodo_insc);
		$propuesta = toba::db()->quote($propuesta);
		$sql = "SELECT		sga_periodos_inscripcion_coeficiente.periodo_insc,
							sga_periodos_inscripcion_coeficiente.coeficiente_desde,
							sga_periodos_inscripcion_coeficiente.coeficiente_hasta,
							sga_periodos_inscripcion_coeficiente.fecha_inicio,
							sga_periodos_inscripcion_coeficiente.fecha_fin,
							sga_periodos_inscripcion_coeficiente.fecha_tope_bajas				
				FROM		sga_periodos_inscripcion_coeficiente
				WHERE		sga_periodos_inscripcion_coeficiente.periodo_insc = $periodo_insc AND
							sga_periodos_inscripcion_coeficiente.propuesta = $propuesta
				ORDER BY	sga_periodos_inscripcion_coeficiente.coeficiente_desde
		";
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Retorna el período de solicitud de readmisión vigente para un alumno.
	 * 
	 * @param int $alumno	# Id de alumno
	 * @return array		# Retorna el periodo de solicitud de readmision y año academico al que pertenece el periodo..
	 */
	function get_periodo_vigente_solicitud_readmision($alumno)
	{
		$sql = "SELECT	vw_periodos_insc_propuesta.periodo_insc as periodo_insc,
						vw_periodos_insc_propuesta.periodo_inscripcion as periodo_inscripcion,
						vw_periodos_insc_propuesta.anio_academico as anio_academico
				FROM sga_alumnos	
				JOIN sga_periodos_inscripcion_aplanado ON  sga_periodos_inscripcion_aplanado.plan_version = sga_alumnos.plan_version
				JOIN vw_periodos_insc_propuesta ON vw_periodos_insc_propuesta.periodo_insc = sga_periodos_inscripcion_aplanado.periodo_insc
				
				WHERE sga_alumnos.alumno = $alumno
				  AND vw_periodos_insc_propuesta.periodo_insc_tipo IN (".periodo_de_inscripcion::tipo_solicitud_readmision.",".periodo_de_inscripcion::tipo_todos.")
				  AND CURRENT_DATE BETWEEN vw_periodos_insc_propuesta.fecha_inicio AND vw_periodos_insc_propuesta.fecha_fin
				ORDER BY vw_periodos_insc_propuesta.fecha_fin DESC
				LIMIT 1
		";
		return guarani_db::consultar_fila($sql);
	}
	
	
	
}