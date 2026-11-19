<?php

namespace SIU\Guarani\php\rest\v2\modelos;

use SIUToba\rest\lib\rest_error;
use SIUToba\rest\lib\rest_filtro_sql;
use SIUToba\rest\lib\rest_hidratador;
use SIUToba\rest\rest;
use SIU\Guarani\php\rest\v2\comisiones\recurso_comisiones;

class rest_comisiones_nucleo
{
	/**
	 * @var \co_comisiones
	 */
	protected $modelo;

	function __construct()
	{
		$this->modelo = \guarani::co('co_comisiones');
	}

	protected function get_modelo($nombre)
	{
		$modelos = recurso_comisiones::_get_modelos();
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
		$con_horarios = rest::request()->get('con_horarios', '0');
		$con_docentes = rest::request()->get('con_docentes', '0');
		$solo_vigentes = false;

		$filtro = $this->get_filtro_base()
				->agregar_campo('actividad', 'sga_elementos.nombre')
				->agregar_campo('nombre', 'sga_comisiones.nombre')
				->agregar_campo_simple('actividad_codigo', 'sga_elementos.codigo= %s')
				->agregar_campo_ordenable('actividad', 'sga_elementos.nombre')
				->agregar_campo_ordenable('comision', 'sga_comisiones.nombre')
				->agregar_campo_flag('activas', 'sga_periodos_lectivos.fecha_fin_dictado >= current_date', '', 1);

		$limit = $filtro->get_sql_limit(20);
		$order = $filtro->get_sql_order_by('+sga_elementos.nombre');
		$where = $filtro->get_sql_where();

		$rs = $this->modelo->get_listado_rest($where, $con_horarios, $con_docentes, $solo_vigentes, $order, $limit);

		$datos = rest_hidratador::hidratar($this->get_modelo('Comision'), $rs);

		$this->separar_instancias_y_modalidades($datos);

		return $datos;
	}

	function get($id)
	{
		$filtro = $this->get_filtro_base();

		$filtro->agregar_campo_simple_local('comision', 'sga_comisiones.comision = %s', $id);

		$rs = $this->modelo->get_listado_rest($filtro->get_sql_where(), 1, 1); //con horarios y docentes

		if (\guarani_arreglos::count($rs) == 1) {
			$comision = rest_hidratador::hidratar($this->get_modelo('Comision'), $rs);
			$this->separar_instancias_y_modalidades($comision);
			return $comision[0];
		} else {
			throw new rest_error(404, "La comisión $id no existe");
		}
	}

	/**
	 * Como subrecurso del recurso docente
	 */
	function get_by_docente($docente)
	{
		$filtro = $this->get_filtro_base();

		$docentes = array('docente' => $docente);

		//Filtro propio de los docentes
		$filtro->agregar_campo_flag_local('activo', "sga_docentes.estado = '" . \comunes::estado_activo . "'", '', 1);

		$comisiones_docente = $this->modelo->get_listado_rest($filtro->get_sql_where(), 1, 0, true, '', '', $docentes); //1: con horarios

		$modelo = $this->get_modelo('Comision');
		$campos = array_merge($modelo, array('responsabilidad'));
		$datos = rest_hidratador::hidratar($campos, $comisiones_docente);
		$this->separar_instancias_y_modalidades($datos);
		$this->insertar_responsabilidad($datos);

		return $datos;
	}

	protected function insertar_responsabilidad(&$datos)
	{
		foreach ($datos as &$dato) {
			$resp = $dato['responsabilidad'];
			unset($dato['responsabilidad']);
			$nuevo = array(
				'responsabilidad' => $resp,
				'comision' => $dato
			);
			$dato = $nuevo;
		}
	}

	protected function separar_instancias_y_modalidades(&$datos)
	{
		// Ahora vienen en 1 linea, por el uso de las funciones de la bd.
		// Es medio ineficiente aca volver a deshacerlo, pero la gran ventaja es
		// que viene en una fila.
		// Si viniese en mas lineas, hay que hacer calculos adicionales para el paginado (limit)
		// ya que el limit se refiere a cantidad de comisiones, no cantidad de filas de la consulta.
		foreach ($datos as &$d) {
			$this->separar_campo($d, 'modalidades');
			$this->separar_campo($d, 'instancias');
		}
	}

	protected function separar_campo(&$d, $clave)
	{
		$d[$clave] = explode('/', $d[$clave]);
	}

}