<?php

namespace SIU\Guarani\php\rest\v2\alumnos_no_regulares;

use SIUToba\rest\rest;
use SIUToba\rest\lib\modelable;
use PERS\Guarani\php\rest\v2\modelos\rest_alumnos_no_regulares;

class recurso_alumnos_no_regulares implements modelable
{
    /**
     * @var rest_alumnos_no_regulares
     */
    protected $modelo;

    public static function _get_modelos()
    {

        //Causas
        $causasNoRegulares = [
            'causa' => ['type' => 'integer'],
            'causa_nombre' => ['type' => 'string']
        ];

        //Historial
        $historialNoRegulares = [
            'fecha_perdida_regularidad' => ['type' => 'string'],
            'fecha_readmision' => ['type' => 'string'],
            'causas' => ['type' => 'array', 'items' => ['$ref' => 'causasNoRegulares']]
        ];

        //Datos de los alumnos no regulares
        $alumnosNoRegulares = [
            'persona' => ['type' => 'integer'],
            'propuesta' => ['type' => 'integer'],
            'propuesta_nombre_abreviado' => ['type' => 'string'],
            'plan' => ['type' => 'integer'],
            'plan_cobrable' => ['type' => 'string'],
            'nro_transaccion' => ['type' => 'integer'],
            'historial' => ['type' => 'array', 'items' => ['$ref' => 'HistorialNoRegulares']],
        ];

        return [
            'AlumnosNoRegulares' => $alumnosNoRegulares,
            'HistorialNoRegulares' => $historialNoRegulares,
            'causasNoRegulares' => $causasNoRegulares
        ];
    }

    function __construct()
    {
        $this->modelo = \guarani::rest(rest_alumnos_no_regulares::class);
    }

    /**
     * GET /alumnos-no-regulares
     *
     * @param_query $fecha_desde string [required] Filtro por fecha desde, formato Y-m-d.
     * @param_query $fecha_hasta string [required] Filtro por fecha hasta, formato Y-m-d.
     * @param_query $persona integer Filtro por una persona
     * @param_query $propuesta integer Filtro por una propuesta
     * @param_query $plan integer Filtro por un plan
     * @param_query $plan_cobrable string Filtro por si es plan cobrable (S/N), por defecto trae todos los planes
     * @param_query $nro_transaccion integer Filtro por el Nro de transacción
     *
     * @notes
     * Devuelve una lista de alumnos no regulares en un período de tiempo con fecha de perdida de regularidad y readmisión.<br>
     *
     * @summary Alumnos no regulares en un período de tiempo con fecha de perdida de regularidad y readmisión.
     * @responses 200 array {"$ref":"AlumnosNoRegulares"} Arreglo de alumnos no regulares en un período de tiempo con fecha de perdida de regularidad y readmisión
     * @responses 400 Error en los parámetros
     * @responses 403 Error en los permisos
     */
    function get_list()
    {
        $datos = $this->modelo->get_list();
        return rest::response()->get_list($datos);
    }
}