<?php
namespace siu\modelo\datos\db;
use SIU\Chulupi\kernel;

class plan_estudio
{
    /**
     * parametros: plan_version
     * cache: memoria
     * filas: n
     */
    function get_detalle_materias_plan_version($parametros)
    {

        $entidad_tipo_actividad = kernel::db()->quote(\elemento::tipo_actividad);
        $entidad_tipo_modulo = kernel::db()->quote(\elemento::tipo_modulo);
        $entidad_subtipo_materia_generica = kernel::db()->quote(\elemento::subtipo_materia_generica);

        $condicion_tipo_cursar = kernel::db()->quote(\condicion::tipo_cursar);
        $condicion_tipo_rendir = kernel::db()->quote(\condicion::tipo_rendir);

		// Recupera actividades y materias genericas
        $sql = "SELECT vw_elementos_plan.codigo as materia,
                       vw_elementos_plan.nombre as nombre_materia,
                       sga_elementos_plan.anio_de_cursada as anio,
                       sga_elementos_plan.periodo_de_cursada as periodo,
					   sga_periodos_genericos.nombre as periodo_cursada_nombre,
                       vw_elementos_plan.entidad_subtipo as tipo,
                       vw_elementos_plan.entidad_subtipo_nombre as tipo_detalle,
                       sga_elementos_plan.creditos as credito,
					   vw_elementos_plan.elemento as elemento,
					   vw_elementos_plan.entidad_subtipo as entidad_subtipo,
					   sga_g3entidades_subtipos.entidad_tipo as entidad_tipo,
					   (SELECT COUNT(1) FROM sga_condiciones 
					     WHERE plan_version = vw_elementos_plan.plan_version
						   AND entidad = sga_elementos.entidad
						   AND condicion_tipo IN ($condicion_tipo_cursar, $condicion_tipo_rendir)
						) as cant_correlativas,
						vw_elementos_plan.estado as estado
                FROM vw_elementos_plan
                     JOIN sga_elementos_plan ON (sga_elementos_plan.elemento_plan = vw_elementos_plan.elemento_plan)
					 LEFT JOIN sga_periodos_genericos ON (sga_periodos_genericos.periodo_generico = sga_elementos_plan.periodo_de_cursada)
                     JOIN sga_elementos_revision ON (vw_elementos_plan.elemento_revision = sga_elementos_revision.elemento_revision)
                     JOIN sga_elementos ON (sga_elementos.elemento = sga_elementos_revision.elemento)
                     JOIN sga_g3entidades_subtipos ON (sga_g3entidades_subtipos.entidad_subtipo = sga_elementos.entidad_subtipo)
                WHERE vw_elementos_plan.plan_version = {$parametros["plan_version"]}
                  AND (sga_g3entidades_subtipos.entidad_tipo = $entidad_tipo_actividad OR  
                       (sga_g3entidades_subtipos.entidad_tipo = $entidad_tipo_modulo AND sga_g3entidades_subtipos.entidad_subtipo = $entidad_subtipo_materia_generica)
                      )
                ";

        $datos = kernel::db()->consultar($sql);

        foreach($datos as $dato){
            $materias_plan[$dato['elemento']] = $dato;
        }

        return $materias_plan;
    }


}