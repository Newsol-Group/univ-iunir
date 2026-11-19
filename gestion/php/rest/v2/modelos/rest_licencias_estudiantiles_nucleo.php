<?php

namespace SIU\Guarani\php\rest\v2\modelos;

use SIUToba\rest\lib\rest_error;
use SIUToba\rest\lib\rest_filtro_sql;
use SIUToba\rest\lib\rest_hidratador;
use SIUToba\rest\rest;
use SIU\Guarani\php\rest\v2\licencias_estudiantiles\recurso_licencias_estudiantiles;

class rest_licencias_estudiantiles_nucleo
{
    /**
     * @var \co_licencias
     */
    protected $modelo;

    function __construct()
    {
        $this->modelo = \guarani::co('co_licencias');
    }

    protected function get_modelo($nombre)
    {
        $modelos = recurso_licencias_estudiantiles::_get_modelos();
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
        $persona = trim(rest::request()->get('persona'));
        // Valido el parámetro 'persona'
        if (!empty($persona) && !preg_match('/^[0-9]+$/', $persona)) {
            throw new rest_error(400, "Parámetro 'persona' debe ser un número entero");
        }

        $plan = trim(rest::request()->get('plan'));
        // Valido el parámetro 'plan'
        if (!empty($plan) && !preg_match('/^[0-9]+$/', $plan)) {
            throw new rest_error(400, "Parámetro 'plan' debe ser un número entero");
        }

        $fecha_desde = trim(rest::request()->get('fecha_desde'));
        // Valido el parámetro 'fecha_desde'
        if (!empty($fecha_desde) && !\guarani_fecha::validar_fecha($fecha_desde, 'Y-m-d')) {
            throw new rest_error(400, "Parámetro 'fecha_desde' debe ser una fecha con el formato 'Y-m-d'");
        }

        $fecha_hasta = trim(rest::request()->get('fecha_hasta'));
        // Valido el parámetro 'fecha_hasta'
        if (!empty($fecha_hasta) && !\guarani_fecha::validar_fecha($fecha_hasta, 'Y-m-d')) {
            throw new rest_error(400, "Parámetro 'fecha_hasta' debe ser una fecha con el formato 'Y-m-d'");
        }

        // Valido fechas
        if (!empty($fecha_desde) && !empty($fecha_hasta) && !\guarani_fecha::validar_fecha_inicio_fin($fecha_desde, $fecha_hasta, 'Y-m-d')) {
            throw new rest_error(400, "Parámetro 'fecha_desde' no puede ser mayor a 'fecha_hasta'");
        }

        $opciones_estado = [\licencia::estado_aceptado, \licencia::estado_pendiente, \licencia::estado_rechazado];

        $estado = trim(rest::request()->get('estado'));
        // Valido el parámetro 'estado'
        if (!empty($estado) && !in_array($estado, $opciones_estado)) {
            throw new rest_error(400, "Parámetro 'estado' debe ser uno de los siguientes valores: ".implode(', ', $opciones_estado));
        }

        $opciones_si_no = [\comunes::si, \comunes::no];

        $plan_cobrable = trim(rest::request()->get('plan_cobrable'));
        // Valido el parámetro 'plan_cobrable'
        if (!empty($plan_cobrable) && !in_array($plan_cobrable, $opciones_si_no)) {
            throw new rest_error(400, "Parámetro 'plan_cobrable' debe ser uno de los siguientes valores: ".implode(', ', $opciones_si_no));
        }

        $licencia_financiera = trim(rest::request()->get('licencia_financiera'));
        // Valido el parámetro 'licencia_financiera'
        if (!empty($licencia_financiera) && !in_array($licencia_financiera, $opciones_si_no)) {
            throw new rest_error(400, "Parámetro 'licencia_financiera' debe ser uno de los siguientes valores: ".implode(', ', $opciones_si_no));
        }

        $permite_rendir_examen = trim(rest::request()->get('permite_rendir_examen'));
        // Valido el parámetro 'permite_rendir_examen'
        if (!empty($permite_rendir_examen) && !in_array($permite_rendir_examen, $opciones_si_no)) {
            throw new rest_error(400, "Parámetro 'permite_rendir_examen' debe ser uno de los siguientes valores: ".implode(', ', $opciones_si_no));
        }

        $rs = $this->modelo->get_listado_rest($persona, $plan, $fecha_desde, $fecha_hasta, $estado, $plan_cobrable, $licencia_financiera, $permite_rendir_examen);
        
        return rest_hidratador::hidratar($this->get_modelo('LicenciaEstudiantil'), $rs);
    }
}