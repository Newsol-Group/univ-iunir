<?php

namespace SIU\Guarani\php\rest\v2\planes_versiones;

use SIUToba\rest\rest;
use SIUToba\rest\lib\modelable;
use PERS\Guarani\php\rest\v2\modelos\rest_planes_versiones;

class recurso_planes_versiones implements modelable
{
    /**
     * @var rest_planes_versiones
     */
    protected $modelo;

    public static function _get_modelos()
    {   
        
        $propuesta = array(
            'propuesta' => array('type' => 'integer', '_mapeo' => 'propuesta'),
            'codigo' => array('type' => 'string', '_mapeo' => 'propuesta_codigo'),
            'nombre' => array('type' => 'string', '_mapeo' => 'propuesta_nombre'),
            'tipo' => array('type' => 'string', '_mapeo' => 'propuesta_tipo_descripcion'),
            );
        
        $plan = array(
            'plan' => array('type' => 'integer', '_mapeo' => 'plan'),
            'codigo' => array('type' => 'string', '_mapeo' => 'plan_codigo_nombre'),
            'nombre' => array('type' => 'string', '_mapeo' => 'plan_nombre'),
            'estado' => array('type' => 'string', '_mapeo' => 'plan_estado'),
            );
        
        $planes_versiones = array(            
            'plan_version'	=> array('type' => 'integer', '_mapeo' => 'plan_version'),
            'codigo' => array('type' => 'string', '_mapeo' => 'version'),
            'nombre' => array('type' => 'string', '_mapeo' => 'version_nombre'),
            'estado' => array('type' => 'string', '_mapeo' => 'plan_version_estado'),
            'fecha_entrada_vigencia' => array('type' => 'string', '_mapeo' => 'fecha_entrada_vigencia'),
            'propuesta'	=> array('$ref' => '#/definitions/PropuestaPlan'),
            'plan' => array('$ref' => '#/definitions/Plan'),
            );
        
        $actividades = array(
            'codigo' => array('type' => 'string'),
            'nombre' => array('type' => 'string'),
            'optativa' => array('type' => 'boolean'),
            );
        
        return array(
                'Plan'          => $plan, 
                'PropuestaPlan' => $propuesta,
                'PlanVersion'   => $planes_versiones, 
                'Actividades'   => $actividades
            );
    }

    function __construct()
    {
		$this->modelo = \guarani::rest(rest_planes_versiones::class);
    }
    
    /**
	 * GET /planes_versiones/
	 * 
	 * @param_query $propuesta_nombre string Nombre de la Propuesta
	 * @param_query $propuesta_codigo string Código de la Propuesta
	 * @param_query $plan_nombre string Nombre del Plan
	 * @param_query $plan_codigo string Código del Plan
	 * @param_query $version_nombre string Nombre de la Versión
	 * @param_query $version_codigo string Código de la Versión
	 * @param_query $titulo_araucano string Código Araucano del Título
	 * 
	 * @notes
	 * Devuelve una lista de planes-versiones activos vigentes, activos no vigentes y nuevos<br>
	 * Los Filtros se definen como <b>condicion;valor</b>, donde <b>condicion</b> puede ser: <br/>
	 * entre, es_mayor_que, desde, es_mayor_igual_que, es_menor_que, es_menor_igual_que, hasta, es_igual_a, es_distinto_de, contiene, no_contiene, comienza_con, termina_con
	 * 
	 * @summary PlanVersion
	 * @responses 200 array {"$ref":"PlanVersion"}
	 */    
    function get_list()
	{
		$datos = $this->modelo->get_list();
		rest::response()->get_list($datos);
	}
    
    /**
	 * GET /planes-versiones/id_plan_version/actividades
	 *
	 * @notes Actividades correspondientes a un determinado plan version
	 *
	 * @summary Actividades del plan version{$id_plan_version}
	 * @responses 200 array {"$ref":"Actividades"}
	 * @responses 404 El plan version {$id_plan_version} no existe
	 */
	function get_actividades_list($id_plan_version)
	{	
		$datos = $this->modelo->get_actividades_plan($id_plan_version); 
		rest::response()->get($datos);
	}
}