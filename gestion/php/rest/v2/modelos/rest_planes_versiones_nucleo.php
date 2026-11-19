<?php

namespace SIU\Guarani\php\rest\v2\modelos;

use SIUToba\rest\lib\rest_error;
use SIUToba\rest\lib\rest_filtro_sql;
use SIUToba\rest\lib\rest_hidratador;
use SIUToba\rest\rest;
use SIU\Guarani\php\rest\v2\planes_versiones\recurso_planes_versiones;

class rest_planes_versiones_nucleo 
{
    
    protected function get_modelo($nombre)
	{
		$modelos = recurso_planes_versiones::_get_modelos();
		return $modelos[$nombre];
	}
    
    function get_list()
    {        
        $filtro = new rest_filtro_sql();
                
		$campos = $filtro
				->agregar_campo('propuesta_codigo', 'sga_propuestas.codigo')
				->agregar_campo('propuesta_nombre', 'sga_propuestas.nombre')
				->agregar_campo('plan_nombre', 'sga_planes.nombre')
				->agregar_campo('plan_codigo', 'sga_planes.codigo')
				->agregar_campo('version_nombre', 'sga_planes_versiones.nombre')
				->agregar_campo('version_codigo', 'sga_planes_versiones.version');
        $where = $campos->get_sql_where();
        
        $titulo_araucano = trim(rest::request()->get('titulo_araucano'));
        if ($titulo_araucano != '') {
            $where .= "AND sga_planes_versiones.plan_version IN (
                            SELECT plan_version 
                            FROM sga_planes_certificados, sga_certificados 
                            WHERE sga_planes_certificados.certificado = sga_certificados.certificado
                            AND sga_planes_certificados.plan_version = sga_planes_versiones.plan_version 
                            AND titulo_araucano = $titulo_araucano)";
        }        

        $where .= " AND sga_planes.tipo_plan IN ('". \planes::tipo_estructurado . "'";

        //Verifico si se usan planes personalizados.
        if (\guarani::parametros()->get_valor('pro_plan_pers_usa_planes_personalizados', null, false) == \comunes::si) {
            $where .= ", '". \planes::tipo_personalizado."'";
        }
        $where .= ')';

        $where .= " AND sga_planes.estado IN ('". \planes::estado_activo."','". \planes::estado_vigente."')                  
                  AND sga_planes.administra_cursos = '". \comunes::no."'
                  AND sga_propuestas_tipos.propuesta_tipo NOT IN (". \propuesta::tipo_vocacional.", ". \propuesta::tipo_curso.")";
        
        $rs = \toba::consulta_php('co_propuestas')->get_propuestas($where);
        
        foreach(array_keys($rs) as $key) {            
            $propuesta = $rs[$key]['propuesta'];
            $rs[$key]['propuesta'] = [];
            
            $rs[$key]['propuesta']['propuesta'] = $propuesta;
            $rs[$key]['propuesta']['codigo'] = $rs[$key]['propuesta_codigo'];
            $rs[$key]['propuesta']['nombre'] = $rs[$key]['propuesta_nombre'];
            $rs[$key]['propuesta']['tipo'] = $rs[$key]['propuesta_tipo_descripcion'];
            
            $plan = $rs[$key]['plan'];
            $rs[$key]['plan'] = [];            
            $rs[$key]['plan']['plan'] = $plan;
            $rs[$key]['plan']['codigo'] = $rs[$key]['plan_codigo_nombre'];
            $rs[$key]['plan']['nombre'] = $rs[$key]['plan_nombre'];
            $rs[$key]['plan']['estado'] = $rs[$key]['plan_estado'];
        }
        $datos = rest_hidratador::hidratar($this->get_modelo('PlanVersion'), $rs);

        return $datos;
    }
    
    function get_actividades_plan($plan_version)
	{        
        //Valido si existe el plan version
        $rs_plan = \toba::consulta_php('co_planes')->get_plan_version_nombre($plan_version);
        
        //Si viene vacio no existe el plan version
        if (empty($rs_plan)) {
            throw new rest_error(404, "El plan versión $plan_version no existe");
        } else {
            $rs = \toba::consulta_php('co_planes')->get_actividades_plan_version($plan_version);
            $datos = rest_hidratador::hidratar($this->get_modelo('Actividades'), $rs);
            return $datos;                    
        }
	}
}
