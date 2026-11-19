<?php

namespace SIU\Guarani\php\rest\v2\oferta_cursos;

use SIUToba\rest\rest;
use SIUToba\rest\lib\modelable;
use PERS\Guarani\php\rest\v2\modelos\rest_oferta_cursos;

class recurso_oferta_cursos implements modelable {

    /**
     * @var rest_oferta_cursos
     */
    protected $modelo;

    function __construct() {
		$this->modelo = \guarani::rest(rest_oferta_cursos::class);
    }

    public static function _get_modelos() {
        $modalidad = array('modalidad' => array('type' => 'string'));

        $horario = array(
            'dia' => array('type' => 'string'),
            'inicio' => array('type' => 'string'),
            'fin' => array('type' => 'string'),
            'tipo' => array('type' => 'string'),
            'aula' => array('type' => 'string'),
        );

        $costo = array(
            'importe' => array('type' => 'number'),
            'moneda' => array('type' => 'string'),
        );

        $docente = array(
            'docente' => array('type' => 'string'),
            'cargo' => array('type' => 'string'),
        );

        $periodo_inscripcion = array(
            'inicio' => array('type' => 'string'),
            'fin' => array('type' => 'string'),
        );

        $periodo_dictado = array(
            'inicio' => array('type' => 'string'),
            'fin' => array('type' => 'string'),
        );

        $curso = array(
            'propuesta' => array('type' => 'integer'),
            'propuesta_nombre' => array('type' => 'string'),
            'actividad' => array('type' => 'integer'),
            'actividad_nombre' => array('type' => 'string'),
            'comision' => array('type' => 'integer'),
            'comision_nombre' => array('type' => 'string'),
            'ubicacion_nombre' => array('type' => 'string'),
            'periodo_lectivo_nombre' => array('type' => 'string'),
            'es_inscripcion_cerrada' => array('type' => 'string'),
            'propuesta_nombre' => array('type' => 'string'),
            'cupo_minimo' => array('type' => 'integer'),
            'cupo_maximo' => array('type' => 'integer'),
            'modalidades_cursada' => array( 'type' => 'array', 'items' => array('$ref' => 'Modalidad')),
            'horarios' => array( 'type' => 'array', 'items' => array('$ref' => 'Horario')),
            'es_cobrable' => array('type' => 'string'),
            'costos' => array( 'type' => 'array', 'items' => array('$ref' => 'Costo')),
            'estado' => array('type' => 'string'),
            'docentes' => array( 'type' => 'array', 'items' => array('$ref' => 'Docente_')),
            'periodo_inscripcion' => array('$ref' => '#/definitions/Periodo_inscripcion'),
            'periodo_dictado' => array('$ref' => '#/definitions/Periodo_dictado'),
        );

        return array('Oferta_cursos' => $curso,
            'Modalidad' => $modalidad,
            'Horario' => $horario,
            'Costo' => $costo,
            'Docente_' => $docente,
            'Periodo_inscripcion' => $periodo_inscripcion,
            'Periodo_dictado' => $periodo_dictado
        );
    }

    /**
     * GET /oferta-cursos
     * 
     * @param_query $inscripcion_vigente integer (defecto: 1) 1 SI - 0 NO
     *
     * @notes
     * Devuelve una lista de comisiones que corresponden a propuestas de tipo curso y que tienen período de inscripción vigente o aún no comenzado.
     *
     * @summary oferta_cursos
     * @responses 200 array {"$ref":"Oferta_cursos"}
     */
    function get_list() {
        $datos = $this->modelo->get_list();
        rest::response()->get_list($datos);
    }

}
