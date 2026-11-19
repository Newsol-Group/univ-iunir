<?php

namespace SIU\Guarani\php\rest\v2\licencias_estudiantiles;

use SIUToba\rest\rest;
use SIUToba\rest\lib\modelable;
use PERS\Guarani\php\rest\v2\modelos\rest_licencias_estudiantiles;

class recurso_licencias_estudiantiles implements modelable
{
    /**
     * @var rest_licencias_estudiantiles
     */
    protected $modelo;

    public static function _get_modelos()
    {

        //Datos de la Propuesta
        $propuesta = [
            'propuesta' => ['type' => 'integer'],
            'propuesta_nombre_abreviado' => ['type' => 'string'],
            'plan' => ['type' => 'integer'],
            'plan_cobrable' => ['type' => 'string'],
            'nro_transaccion' => ['type' => 'integer'],
        ];

        //Datos de la Licencia Estudiantil
        $licenciaEstudiantil = [
            'licencia' => ['type' => 'integer'],
            'persona' => ['type' => 'integer'],
            'licencia_financiera' => ['type' => 'string'],
            'permite_rendir_examen' => ['type' => 'string'],
            'alcance' => ['type' => 'integer'],
            'alcance_descr' => ['type' => 'string'],
            'fecha_desde' => ['type' => 'string'],
            'fecha_hasta' => ['type' => 'string'],
            'estado' => ['type' => 'string'],
            'motivo' => ['type' => 'integer'],
            'motivo_descr' => ['type' => 'string'],
            'observaciones' => ['type' => 'string'],
            'propuestas' => ['type' => 'array', 'items' => ['$ref' => 'Propuesta']],
        ];

        return [
            'LicenciaEstudiantil' => $licenciaEstudiantil,
            'Propuesta' => $propuesta,
        ];
    }

    function __construct()
    {
        $this->modelo = \guarani::rest(rest_licencias_estudiantiles::class);
    }

    /**
     * GET /licencias-estudiantiles
     *
     * @param_query $persona integer Filtro por una persona
     * @param_query $plan integer Filtro por un plan
     * @param_query $fecha_desde string Filtro por fecha desde, formato Y-m-d. Solo filtra licencias con alcance <b>Rango de Fechas</b>
     * @param_query $fecha_hasta string Filtro por fecha hasta, formato Y-m-d. Solo filtra licencias con alcance <b>Rango de Fechas</b>
     * @param_query $estado string Filtro por estado (Aceptado/Pendiente/Rechazado), por defecto trae todos los estados
     * @param_query $plan_cobrable string Filtro por si es plan cobrable (S/N), por defecto trae todos los planes
     * @param_query $licencia_financiera string Filtro por si es licencia financiera (S/N), por defecto trae todas las licencias
     * @param_query $permite_rendir_examen string Filtro por si permite rendir examen (S/N), por defecto trae todas las licencias
     * 
     * @notes
     * Devuelve una lista de <b>Licencias Estudiantiles</b>.<br>
     * Si se filtra por <b>fecha_desde</b> o <b>fecha_hasta</b> entonces solo devolverá <b>Licencias Estudiantiles</b> cuyo alcance sea <b>Rango de Fechas</b>.
     *
     * @summary Licencias Estudiantiles
     * @responses 200 array {"$ref":"LicenciaEstudiantil"} Arreglo de Licencias Estudiantiles
     * @responses 400 Error en los parámetros
     * @responses 403 Error en los permisos
     */
    function get_list()
    {
        $datos = $this->modelo->get_list();
        return rest::response()->get_list($datos);
    }
}