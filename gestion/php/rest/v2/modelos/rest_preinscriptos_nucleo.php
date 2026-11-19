<?php

namespace SIU\Guarani\php\rest\v2\modelos;

use SIUToba\rest\lib\rest_error;
use SIUToba\rest\lib\rest_filtro_sql;
use SIUToba\rest\lib\rest_hidratador;
use SIUToba\rest\rest;
use SIU\Guarani\php\rest\v2\personas\recurso_personas;

class rest_preinscriptos_nucleo 
{
    /**
	 * 
	 * @var co_personas 
	 */
	protected $modelo;

	function __construct()
	{
		$this->modelo = \guarani::co('co_preinscripciones');
	}

	protected function get_modelo($nombre)
	{
		$modelos = recurso_personas::_get_modelos();
		return $modelos[$nombre];
	}

	/**
	 * 
	 * @return rest_filtro_sql
	 */
	function get_filtro_base()
	{
		$filtro = new rest_filtro_sql();
		return $filtro;
	}

	function get_list()
	{
        $usuario = trim(rest::request()->get('usuario'));
		$pais = trim(rest::request()->get('pais'));
		$tipo_documento = trim(rest::request()->get('tipo_documento'));
		$numero_documento = trim(rest::request()->get('numero_documento'));

        if ($usuario == '') {
            if ($pais == '') {
                throw new rest_error(400, "Parámetro 'pais' requerido");
            }
            if ($tipo_documento == '') {
                throw new rest_error(400, "Parámetro 'tipo_documento' requerido");
            }
            if ($numero_documento == '') {
                throw new rest_error(400, "Parámetro 'numero_documento' requerido");
            }
        }

		if ((!preg_match('/^[0-9]+$/', $pais)) && $pais != '') {
			throw new rest_error(400, "Parámetro 'pais' debe ser un número entero");
		}
		if ((!preg_match('/^[0-9]+$/', $tipo_documento)) && $tipo_documento != '') {
			throw new rest_error(400, "Parámetro 'tipo_documento' debe ser un número entero");
		}

		$rs = \preinscripcion::get_preinscripto_rest($usuario, $pais, $tipo_documento, $numero_documento);        
        
		if ($rs) {			
			return $rs;
		} else {
			throw new rest_error(404, 'El preinscripto no existe');
		}
	}
    
    function get_datos_agenda_dia($id_preinscripcion)
	{
		if ($id_preinscripcion == '') {
			throw new rest_error(400, "Parámetro idPreinscripcion requerido");
		}
        
        $fecha = trim(rest::request()->get('fecha'));
        
        //Regex que valida que una fecha sea valida.
        $date_regex ='#^(19|20)\d\d[\- /.](0[1-9]|1[012])[\- /.](0[1-9]|[12][0-9]|3[01])$#'; 

		if ($fecha != '' && (!preg_match($date_regex, $fecha))) {
			throw new rest_error(400, "Parámetro fecha no es válido");
		}
        
		if (!preg_match('/^[0-9]+$/', $id_preinscripcion)) {
			throw new rest_error(400, "Parámetro '$id_preinscripcion' debe ser un número entero");
		}
        
		$rs = $this->modelo->get_turno_rest($id_preinscripcion, $fecha);
		if ($rs) {
			$datos = rest_hidratador::hidratar($this->get_modelo('Agenda'), $rs);
			return $datos;
		} else {
			throw new rest_error(404, 'El preinscripto no tiene turno para la fecha');
		}
	}
}
