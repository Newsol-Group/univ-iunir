<?php

namespace SIU\Guarani\php\rest\v2\alumnos_pasivos;

use SIUToba\rest\rest;
use SIUToba\rest\lib\modelable;
use PERS\Guarani\php\rest\v2\modelos\rest_alumnos_pasivos;

class recurso_alumnos_pasivos implements modelable
{
    /**
     * @var rest_alumnos_pasivos
     */
    protected $modelo;

    public static function _get_modelos()
    {

        //Historial
        $historialPasivos = [
            'fecha_pasivo' => ['type' => 'string'],
            'fecha_activo' => ['type' => 'string'],
            'motivo_calidad' => ['type' => 'integer'],
            'motivo_calidad_nombre' => ['type' => 'string'],
            'motivo_calidad_descripcion' => ['type' => 'string'],
        ];

        //Datos de los alumnos pasivos
        $alumnosPasivos = [
            'persona' => ['type' => 'integer'],
            'propuesta' => ['type' => 'integer'],
            'propuesta_nombre_abreviado' => ['type' => 'string'],
            'plan' => ['type' => 'integer'],
            'plan_cobrable' => ['type' => 'string'],
            'nro_transaccion' => ['type' => 'integer'],
            'historial' => ['type' => 'array', 'items' => ['$ref' => 'HistorialPasivos']],
        ];

        return [
            'AlumnosPasivos' => $alumnosPasivos,
            'HistorialPasivos' => $historialPasivos,
        ];
    }

    function __construct()
    {
        $this->modelo = \guarani::rest(rest_alumnos_pasivos::class);
    }

    /**
     * GET /alumnos-pasivos
     *
     * @param_query $fecha_desde string [required] Filtro por fecha desde, formato Y-m-d.
     * @param_query $fecha_hasta string [required] Filtro por fecha hasta, formato Y-m-d.
     * @param_query $persona integer Filtro por una persona
     * @param_query $propuesta integer Filtro por una propuesta
     * @param_query $plan integer Filtro por un plan
     * @param_query $plan_cobrable string Filtro por si es plan cobrable (S/N), por defecto trae todos los planes
     * @param_query $nro_transaccion integer Filtro por el Nro de transacción
     * @param_query $motivo_calidad integer Filtro por el motivo de cambio de calidad
     * (1 = Alta Alumno/2 = Egreso/3 = Pasivo/4 = Abandono/5 = Rechazado por Requisitos/7 = Rechazado por cambio de carrera/8 = Rechazado por Abandono de Carrera/
     * 9 = Rechazo Administrativo/10 = Falta de pago/11 = Activo - Migración/12 = Pasivo - Migración/13 = Rechazo de inscripción anulado), por defecto trae todos los motivos
     * 
     * 
     *
     * @notes
     * Devuelve una lista de alumnos pasivos en un período de tiempo con historial de cambios de calidad.<br>
     *
     * @summary Alumnos pasivos en un período de tiempo con historial de cambios de calidad.
     * @responses 200 array {"$ref":"AlumnosPasivos"} Arreglo de alumnos pasivos en un período de tiempo con historial de cambios de calidad
     * @responses 400 Error en los parámetros
     * @responses 403 Error en los permisos
     */
    function get_list()
    {
        $datos = $this->modelo->get_list();
        return rest::response()->get_list($datos);
    }
}