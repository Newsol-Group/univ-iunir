<?php

namespace SIU\Guarani\php\rest\v2\alumnos_cambios_calidades;

use SIUToba\rest\rest;
use SIUToba\rest\lib\modelable;
use PERS\Guarani\php\rest\v2\modelos\rest_alumnos_cambios_calidades;

class recurso_alumnos_cambios_calidades implements modelable
{
    /**
     * @var rest_alumnos_cambios_calidades
     */
    protected $modelo;

    public static function _get_modelos()
    {

		//Historial
        $historialCambiosCalidades = [
            'fecha' => ['type' => 'string', 'example' => '2024-01-25 10:09:16.964312-03'],
            'calidad' => ['type' => 'string', 'example' => 'P'],
            'motivo_calidad' => ['type' => 'integer', 'example' => 10],
            'motivo_calidad_nombre' => ['type' => 'string', 'example' => 'Falta de pago'],
            'motivo_calidad_descripcion' => ['type' => 'string', 'example' => 'Baja por falta de pago'],
        ];

        //Datos de los alumnos pasivos
        $alumnosCambiosCalidades = [
            'persona' => ['type' => 'integer', 'example' => 127],
            'propuesta' => ['type' => 'integer', 'example' => 34],
            'propuesta_nombre_abreviado' => ['type' => 'string', 'example' => 'Paleontología'],
            'plan' => ['type' => 'integer', 'example' => 37],
            'plan_cobrable' => ['type' => 'string', 'example' => 'S'],
            'nro_transaccion' => ['type' => 'integer', 'example' => 1797],
            'historial' => ['type' => 'array', 'items' => ['$ref' => 'HistorialCambiosCalidades']],
        ];

        return [
            'AlumnosCambiosCalidades' => $alumnosCambiosCalidades,
			'HistorialCambiosCalidades' => $historialCambiosCalidades,
        ];
    }

    function __construct()
    {
        $this->modelo = \guarani::rest(rest_alumnos_cambios_calidades::class);
    }

    /**
     * GET /alumnos-cambios-calidades
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
     * Devuelve una lista de alumnos con cambios de calidades en un período de tiempo.<br>
     *
     * @summary Alumnos con cambios de calidades en un período de tiempo.
     * @responses 200 array {"$ref":"AlumnosCambiosCalidades"} Arreglo de alumnos con cambios de calidades en un período de tiempo
     * @responses 400 Error en los parámetros
     * @responses 403 Error en los permisos
     */
    function get_list()
    {
        $datos = $this->modelo->get_list();
        return rest::response()->get_list($datos);
    }
}