<?php

namespace SIU\Guarani\php\rest\v1\modelos;

use SIUToba\rest\lib\rest_error;
use SIUToba\rest\lib\rest_filtro_sql;
use SIUToba\rest\lib\rest_hidratador;
use SIUToba\rest\rest;
use SIU\Guarani\php\rest\v1\personas\recurso_personas;

class rest_personas_nucleo
{
	/**
	 * 
	 * @var \co_personas
	 */
	protected $modelo;

	function __construct()
	{
		$this->modelo = \guarani::co('co_personas');
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

		$rs = $this->modelo->get_contactos_persona_rest_persona($usuario, $pais, $tipo_documento, $numero_documento);
		if ($rs) {
			$datos = rest_hidratador::hidratar($this->get_modelo('Contacto'), array($rs));
			return current($datos);
		} else {
			throw new rest_error(404, 'La persona no existe');
		}
	}

	function get_datos_personales($persona)
    {
		if ($persona == '') {
			throw new rest_error(400, "Parámetro 'persona' requerido");
		}

		if (!preg_match('/^[0-9]+$/', $persona)) {
			throw new rest_error(400, "Parámetro 'persona' debe ser un número entero");
		}

		$rs = $this->modelo->get_datos_personales_rest($persona);
		if ($rs) {
			$datos = rest_hidratador::hidratar($this->get_modelo('Persona'), array($rs));
			return current($datos);
		} else {
			throw new rest_error(404, 'La persona no existe');
		}
	}

	function get_datos_analitico($persona)
	{
		if ($persona == '') {
			throw new rest_error(400, "Parámetro 'persona' requerido");
		}               

		if (!preg_match('/^[0-9]+$/', $persona)) {
			throw new rest_error(400, "Parámetro 'persona' debe ser un número entero");
		}

		$codigo_titulo_araucano = trim(rest::request()->get('codigo_titulo_araucano'));
		if ($codigo_titulo_araucano == '') {
			$codigo_titulo_araucano = null;
		} else {
			if (!preg_match('/^[0-9]+$/', $codigo_titulo_araucano)) {
				throw new rest_error(400, "Parámetro 'codigo_titulo_araucano' debe ser un número entero");
			}
		}

		$rs = $this->modelo->get_datos_analitico_rest($persona, $codigo_titulo_araucano);
		if ($rs) {
			$datos = rest_hidratador::hidratar($this->get_modelo('Historia'), $rs);
			return $datos;
		} else {
			throw new rest_error(404, 'La persona no tiene historia académica');
		}
	}
    
	function get_datos_agenda_dia($persona)
	{
		if ($persona == '') {
			throw new rest_error(400, "Parámetro 'persona' requerido");
		}
        
        $fecha = trim(rest::request()->get('fecha'));
        $modalidad = trim(rest::request()->get('modalidad'));
        
        //Regex que valida que una fecha sea valida.
        $date_regex ='#^(19|20)\d\d[\- /.](0[1-9]|1[012])[\- /.](0[1-9]|[12][0-9]|3[01])$#'; 

		if ($fecha != '' && (!preg_match($date_regex, $fecha))) {
			throw new rest_error(400, "Parámetro fecha no es válido");
		}
        
		if (!preg_match('/^[0-9]+$/', $persona)) {
			throw new rest_error(400, "Parámetro 'persona' debe ser un número entero");
		}

        if ($modalidad != '') {
            if (strtolower($modalidad) != 'true' && strtolower($modalidad) != 'false') {
                throw new rest_error(400, "Parámetro 'modalidad' debe ser un booleano");
            }
        }
        
		$rs = $this->modelo->get_agenda_del_dia($persona, $fecha, $modalidad);
		if ($rs) {
			$datos = rest_hidratador::hidratar($this->get_modelo('Agenda'), $rs);
			return $datos;
		} else {
			throw new rest_error(404, 'La persona no tiene actividades para la fecha');
		}
	}
}