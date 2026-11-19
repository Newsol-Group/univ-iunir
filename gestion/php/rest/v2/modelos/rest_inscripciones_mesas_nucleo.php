<?php

namespace SIU\Guarani\php\rest\v2\modelos;

use SIUToba\rest\lib\rest_error;
use SIUToba\rest\lib\rest_filtro_sql;
use SIUToba\rest\lib\rest_hidratador;
use SIUToba\rest\rest;
use SIU\Guarani\php\rest\v2\inscripciones_mesas\recurso_inscripciones_mesas;

class rest_inscripciones_mesas_nucleo
{
    /**
     * @var \co_inscripciones_examenes
     */
    protected $modelo;

    function __construct()
    {
        $this->modelo = \guarani::co('co_inscripciones_examenes');
    }

    protected function get_modelo($nombre)
    {
        $modelos = recurso_inscripciones_mesas::_get_modelos();
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
		$llamado_mesa = trim(rest::request()->get('llamado_mesa'));
		$nro_transaccion = trim(rest::request()->get('nro_transaccion'));
		$historicas = trim(rest::request()->get('historicas'));

		// Si el parámetro 'historicas' viene vacío se setea en false.
		if (empty($historicas)) {
			$historicas = false;
		} elseif ($historicas == 'true' || $historicas == 'false') {
			// Si viene debe ser true o false.
			$historicas = ($historicas === 'true');	
		} else {
			// Si viene algo distinto a true o false se muestra un error.
			throw new rest_error(400, "Parámetro 'historicas' debe ser 'true' o 'false'");
		}

		// Para inscripciones históricas debe filtra por 'llamado_mesa' o 'nro_transaccion' por cuestiones de performance.
		if ($historicas && empty($llamado_mesa) && empty($nro_transaccion)){
			throw new rest_error(400, "Para inscripciones históricas debe filtrar por 'llamado_mesa' o 'nro_transaccion' por cuestiones de performance");
		}

		if (! empty($llamado_mesa) && !preg_match('/^[0-9]+$/', $llamado_mesa)) {
			throw new rest_error(400, "Parámetro 'llamado_mesa' debe ser un número entero");
		}

        if (! empty($nro_transaccion) && !preg_match('/^[0-9]+$/', $nro_transaccion)) {
			throw new rest_error(400, "Parámetro 'nro_transaccion' debe ser un número entero");
		}

		$filtro = $this->get_filtro_base()
			->agregar_campo_simple('llamado_mesa', 'insc_examen.llamado_mesa = %s')
			->agregar_campo_simple('nro_transaccion', 'insc_examen.nro_transaccion = %s');
		$where = $filtro->get_sql_where();

		$solo_vigentes = !$historicas;
		$rs = $this->modelo->get_listado_rest($where, $solo_vigentes);
		$datos = rest_hidratador::hidratar($this->get_modelo('InscripcionMesa'), $rs);
		return $datos;
    }
}