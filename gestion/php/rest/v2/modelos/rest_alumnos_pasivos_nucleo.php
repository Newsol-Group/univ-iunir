<?php

namespace SIU\Guarani\php\rest\v2\modelos;

use SIUToba\rest\lib\rest_error;
use SIUToba\rest\lib\rest_filtro_sql;
use SIUToba\rest\lib\rest_hidratador;
use SIUToba\rest\rest;
use SIU\Guarani\php\rest\v2\alumnos_pasivos\recurso_alumnos_pasivos;

class rest_alumnos_pasivos_nucleo
{
    /**
     * @var \co_alumnos
     */
    protected $modelo;

    function __construct()
    {
        $this->modelo = \guarani::co('co_alumnos');
    }

    protected function get_modelo($nombre)
    {
        $modelos = recurso_alumnos_pasivos::_get_modelos();
        return $modelos[$nombre];
    }

    /**
     * @return rest_filtro_sql
     */
    function get_filtro_base()
    {
        $filtro = new rest_filtro_sql();
        return $filtro;
    }

    function get_list()
    {

        $fecha_desde = trim(rest::request()->get('fecha_desde'));

        // Parámetro 'fecha_desde' obligatorio
        if (empty($fecha_desde)) {
            throw new rest_error(400, "Parámetro 'fecha_desde' obligatorio");
        }

        // Valido el parámetro 'fecha_desde'
        if (!\guarani_fecha::validar_fecha($fecha_desde, 'Y-m-d')) {
            throw new rest_error(400, "Parámetro 'fecha_desde' debe ser una fecha con el formato 'Y-m-d'");
        }

        $fecha_hasta = trim(rest::request()->get('fecha_hasta'));

        // Parámetro 'fecha_hasta' obligatorio
        if (empty($fecha_hasta)) {
            throw new rest_error(400, "Parámetro 'fecha_hasta' obligatorio");
        }

        // Valido el parámetro 'fecha_hasta'
        if (!\guarani_fecha::validar_fecha($fecha_hasta, 'Y-m-d')) {
            throw new rest_error(400, "Parámetro 'fecha_hasta' debe ser una fecha con el formato 'Y-m-d'");
        }

        // Valido fechas
        if (!\guarani_fecha::validar_fecha_inicio_fin($fecha_desde, $fecha_hasta, 'Y-m-d')) {
            throw new rest_error(400, "Parámetro 'fecha_desde' no puede ser mayor a 'fecha_hasta'");
        }

        $persona = trim(rest::request()->get('persona'));
        // Valido el parámetro 'persona'
        if (!empty($persona) && !preg_match('/^[0-9]+$/', $persona)) {
            throw new rest_error(400, "Parámetro 'persona' debe ser un número entero");
        }

        $propuesta = trim(rest::request()->get('propuesta'));
        // Valido el parámetro 'propuesta'
        if (!empty($propuesta) && !preg_match('/^[0-9]+$/', $propuesta)) {
            throw new rest_error(400, "Parámetro 'propuesta' debe ser un número entero");
        }

        $plan = trim(rest::request()->get('plan'));
        // Valido el parámetro 'plan'
        if (!empty($plan) && !preg_match('/^[0-9]+$/', $plan)) {
            throw new rest_error(400, "Parámetro 'plan' debe ser un número entero");
        }

        $opciones_si_no = [\comunes::si, \comunes::no];

        $plan_cobrable = trim(rest::request()->get('plan_cobrable'));
        // Valido el parámetro 'plan_cobrable'
        if (!empty($plan_cobrable) && !in_array($plan_cobrable, $opciones_si_no)) {
            throw new rest_error(400, "Parámetro 'plan_cobrable' debe ser uno de los siguientes valores: ".implode(', ', $opciones_si_no));
        }

        $nro_transaccion = trim(rest::request()->get('nro_transaccion'));
        // Valido el parámetro 'nro_transaccion'
        if (!empty($nro_transaccion) && !preg_match('/^[0-9]+$/', $nro_transaccion)) {
            throw new rest_error(400, "Parámetro 'nro_transaccion' debe ser un número entero");
        }

        $motivo_calidad = trim(rest::request()->get('motivo_calidad'));
        // Valido el parámetro 'motivo_calidad'
        if (!empty($motivo_calidad) && !preg_match('/^[0-9]+$/', $motivo_calidad)) {
            throw new rest_error(400, "Parámetro 'motivo_calidad' debe ser un número entero");
        }

        $rs = $this->modelo->get_listado_alumnos_pasivos_periodo_rest($fecha_desde, $fecha_hasta, $persona, $propuesta, $plan, $plan_cobrable, $nro_transaccion, $motivo_calidad);

        return rest_hidratador::hidratar($this->get_modelo('AlumnosPasivos'), $rs);
    }
}