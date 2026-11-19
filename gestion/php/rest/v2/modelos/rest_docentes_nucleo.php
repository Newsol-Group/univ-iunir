<?php

namespace SIU\Guarani\php\rest\v2\modelos;

use SIUToba\rest\lib\rest_error;
use SIUToba\rest\lib\rest_filtro_sql;
use SIUToba\rest\lib\rest_hidratador;
use SIUToba\rest\rest;
use SIU\Guarani\php\rest\v2\docentes\recurso_docentes;
use PERS\Guarani\php\rest\v2\modelos\rest_comisiones;

class rest_docentes_nucleo {
	
	protected static $campos_minimos = array(
	                'docente', // '480',
	                'legajo', // 'P9231',
	                'documento', // 'DNI 1324684',
	                'apellido', // 'Aaa',
	                'nombres', // 'Bbb',
			        );
	
	/**
	 * @var \co_docentes
	 */
	protected $modelo;
	
	function __construct()
	{
		$this->modelo = \guarani::co('co_docentes');
	}
	 
	protected function get_modelo($nombre)
	{
		$modelos = recurso_docentes::_get_modelos();
		return $modelos[$nombre];
	}

	protected function get_filtro_base()
	{
		$filtro = new rest_filtro_sql();
		$filtro->agregar_campo_flag_local('activo', "sga_docentes.estado = '" . \comunes::estado_activo . "'", '', 1);
		return $filtro;
	}

	function get_list()
	{
		$tipo_documento = trim(rest::request()->get('tipo_documento'));
        if ($tipo_documento != '' && !preg_match('/^[0-9]+$/', $tipo_documento)) {
			throw new rest_error(400, "Parámetro 'tipo_documento' debe ser un número entero");
		}

		$filtro = $this->get_filtro_base()
			->agregar_campo('apellido', 'vw_personas.apellido')
			->agregar_campo('nombres', 'vw_personas.nombres')
			->agregar_campo_simple('tipo_documento', 'mdp_personas_documentos.tipo_documento = %s')
			->agregar_campo('nro_documento', 'mdp_personas_documentos.nro_documento')
			->agregar_campo_ordenable('apellido', 'vw_personas.apellido')
			->agregar_campo_ordenable('nombres', 'vw_personas.nombres')
			->agregar_campo_ordenable('legajo', 'sga_docentes.legajo');

		$where = $filtro->get_sql_where();
		$limit = (rest::request()->get('limit') === '0') ? '' : $filtro->get_sql_limit(20);
		$order = $filtro->get_sql_order_by('+vw_personas.apellido, +vw_personas.nombres');

		$rs = $this->modelo->get_listado_rest($where, $order, $limit);
		$datos = rest_hidratador::hidratar($this->get_modelo('Docente'), $rs);
		return $datos;
	}

	function get($id_docente)
	{
		if ($id_docente == '') {
			throw new rest_error(400, "Parámetro 'id_docente' requerido");
		}
		if (!preg_match('/^[0-9]+$/', $id_docente)) {
			throw new rest_error(400, "Parámetro 'id_docente' debe ser un número entero");
		}
		$filtro = $this->get_filtro_base();
		$filtro->agregar_campo_simple_local('docente', "sga_docentes.docente = %s", $id_docente);
		$rs = $this->modelo->get_listado_rest($filtro->get_sql_where());
		if (\guarani_arreglos::count($rs) == 1){
			$docente_rest = rest_hidratador::hidratar_fila($this->get_modelo('Docente'), $rs[0]);
			return $docente_rest;
		} else {
			throw new rest_error(404, "El docente $id_docente no existe");
		}
	}

	function get_comisiones($id_docente)
	{
		/** @var $rest_comisiones rest_comisiones */
		$rest_comisiones = \guarani::rest(rest_comisiones::class);
		return $rest_comisiones->get_by_docente($id_docente);
	}

	function get_mesas($id_docente)
	{
		$filtro = $this->get_filtro_base();

		$rs = $this->modelo->get_mesas_docente_rest($id_docente, true, $filtro->get_sql_where());

		$datos = rest_hidratador::hidratar($this->get_modelo('RolLlamadoMesa'), $rs);
		return $datos;
	}
} 