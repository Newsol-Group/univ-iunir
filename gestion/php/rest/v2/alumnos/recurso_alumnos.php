<?php

namespace SIU\Guarani\php\rest\v2\alumnos;

use SIUToba\rest\rest;
use SIUToba\rest\lib\modelable;
use PERS\Guarani\php\rest\v2\modelos\rest_alumnos;

class recurso_alumnos implements modelable
{

    /**
     * @var rest_alumnos
     */
    protected $modelo;

    public static function _get_modelos(){

        $alumno = array(
            'email'	=> array('type' => 'string'),
            'telefono' => array('type'	=> 'string'),
        );
				
        return array('Alumno' => $alumno);
    }

    function __construct()
    {
		$this->modelo = \guarani::rest(rest_alumnos::class);
    }

    /**
     * GET /alumnos
     *
     * @param_query $tipo_documento integer [required] Tipo de Documento del alumno
     * @param_query $numero_documento string [required] Número de Documento del alumno
     *
     * @notes
     * Devuelve una lista de alumnos.<br>
     *
     * @summary Alumnos
     * @responses 200 array {"$ref":"Alumno"}
     * @responses 404 El alumno no existe
     * @responses 400 Error en los parámetros
     */
    function get_list()
    {
        $datos = $this->modelo->get_list();
        rest::response()->get($datos);
    }
	
	/**
     * GET /alumnos/id_alumno/certificados/id_certificado
     *
	 *
     * @notes
     * Devuelve si el certificado pasado por parámetro es valida para el alumno<br>
     *
     * @summary Valido o no el certificado $certificado     
     * @responses 404 El alumno no existe o el certificado es inválido
     * @responses 400 Error en los parámetros
     */
    function get_certificados($alumno, $certificado)
    {
        $datos = $this->modelo->get_validacion_certificado($alumno, $certificado);
        rest::response()->get($datos);
    }

}