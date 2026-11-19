<?php

namespace SIU\Guarani\php\rest\v1\modelos;

use SIUToba\rest\lib\rest_error;
use SIUToba\rest\lib\rest_filtro_sql;
use SIUToba\rest\lib\rest_hidratador;
use SIUToba\rest\rest;
use SIU\Guarani\php\rest\v1\alumnos\recurso_alumnos;

class rest_alumnos_nucleo
{
    /**
     * @var \co_personas
     */
    protected $modelo;

    function __construct()
    {
        $this->modelo = \guarani::co('co_personas');
    }

    protected function get_modelo($nombre)
    {
        $modelos = recurso_alumnos::_get_modelos();
        return $modelos[$nombre];
    }

    /**
     * @return rest_filtro_sql
     */
    function get_filtro_base(){
        $filtro = new rest_filtro_sql();
        return $filtro;
    }

    function get_list()
    {

        $tipo_documento = trim(rest::request()->get('tipo_documento'));
        $numero_documento = trim(rest::request()->get('numero_documento'));

        if($tipo_documento == "") throw new rest_error(400, "Parámetro 'tipo_documento' requerido");
        if($numero_documento == "") throw new rest_error(400, "Parámetro 'numero_documento' requerido");

        if(!preg_match('/^[0-9]+$/', $tipo_documento)) throw new rest_error(400, "Parámetro 'tipo_documento' debe ser un número entero");

        $rs = $this->modelo->get_contactos_persona_rest($tipo_documento, $numero_documento);

        if ($rs) {
            $datos = rest_hidratador::hidratar($this->get_modelo('Alumno'), array($rs));
            return $datos[0];
        } else {
            throw new rest_error(404, "El alumno no existe");
        }
    }
	
	function get_validacion_certificado($alumno, $certificado)
	{		
		if(!preg_match('/^[0-9]+$/', $alumno)) {
			throw new rest_error(400, "Parámetro 'alumno' debe ser un número entero");
		}
		if(!preg_match('/^[0-9]+$/', $certificado)) {
			throw new rest_error(400, "Parámetro 'certificado' debe ser un número entero");
		}
		
		//Validar el alumno si existe.
		if (!\guarani::co('co_alumnos')->existe_alumno($alumno)) {
			throw new rest_error(400, "El alumno ingresado no existe");
		}
		
		//Valido si el certificado existe.
		if (!\guarani::co('co_certificados')->existe_certificado($certificado)) {
			throw new rest_error(400, "El certificado ingresado no existe");
		}		
		$datos_alumno = current(\toba::consulta_php('co_alumnos')->get_datos_alumno($alumno));
		$this->s__conjunto = new \conjunto_validable($datos_alumno['plan_version']);
		$arbol_certificado = $this->s__conjunto->get_arbol_certificado($datos_alumno['plan_version'], $certificado);
		$this->s__conjunto->set_certificado($certificado, $arbol_certificado);
		$datos['cumple_condiciones'] = $this->s__conjunto->validar($alumno);
		return $datos;
	}
	
}