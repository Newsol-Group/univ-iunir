<?php

namespace SIU\Guarani\php\rest\v1\modelos;

use SIUToba\rest\lib\rest_error;
use SIUToba\rest\lib\rest_filtro_sql;
use SIUToba\rest\lib\rest_hidratador;
use SIU\Guarani\php\rest\v1\propuestas_formativas\recurso_propuestas_formativas;

class rest_propuestas_formativas_nucleo
{

	/**
	 * @var \co_propuestas
	 */
	protected $modelo;

	function __construct()
	{
		$this->modelo = \guarani::co('co_propuestas');
	}

    /**
     * @return rest_filtro_sql
     */
    function get_filtro_base()
	{
        $filtro = new rest_filtro_sql();
        return $filtro;
    }

    protected function get_modelo($nombre)
	{
        $modelos = recurso_propuestas_formativas::_get_modelos();
        return $modelos[$nombre];
    }

	function get_list()
	{

		$filtro = $this->get_filtro_base()
				->agregar_campo('propuesta_codigo', 'sga_propuestas.codigo')
				->agregar_campo('propuesta_nombre', 'sga_propuestas.nombre')
				->agregar_campo('ra_codigo', 'sga_responsables_academicas.codigo')
				->agregar_campo('ra_nombre', 'sga_responsables_academicas.nombre')
				->agregar_campo_ordenable('propuesta_codigo', 'sga_propuestas.codigo')
				->agregar_campo_ordenable('propuesta_nombre', 'sga_propuestas.nombre');

		$order = $filtro->get_sql_order_by('+sga_propuestas.nombre');
		$where = $filtro->get_sql_where();

		$datos = $this->modelo->get_listado_rest($where, $order);
		
		$propuestas = [];
		$propuestasHidratadas = [];
		foreach ($datos as $propuesta) {
			$this->armarModelo($propuestas, $propuesta);
		}

		if (!empty($propuestas)) {
            $propuestasHidratadas = rest_hidratador::hidratar($this->get_modelo('Propuesta'), $propuestas);
        }

		return $propuestasHidratadas;
	}

	function get($id)
	{
		$filtro = $this->get_filtro_base();

		$filtro->agregar_campo_simple_local('propuesta', 'sga_propuestas.propuesta = %s', $id);

		$datos = $this->modelo->get_listado_rest($filtro->get_sql_where());

		if (\guarani_arreglos::count($datos) > 0) {
			$propuestas = [];
			$propuestasHidratadas = [];
			foreach ($datos as $propuesta) {
				$this->armarModelo($propuestas, $propuesta);
			}
			$propuestasHidratadas = rest_hidratador::hidratar($this->get_modelo('Propuesta'), $propuestas);
			return $propuestasHidratadas[0];
		} else {
			throw new rest_error(404, "La propuesta formativa $id no existe");
		}
	}

	function armarModelo(&$propuestas, $datos)
	{
		// Propuesta
		$propuestas[$datos['propuesta']]['propuesta'] = $datos['propuesta'];
		$propuestas[$datos['propuesta']]['codigo'] = $datos['propuesta_codigo'];
		$propuestas[$datos['propuesta']]['nombre'] = $datos['propuesta_nombre'];
		$propuestas[$datos['propuesta']]['nombre_abreviado'] = $datos['propuesta_nombre_abreviado'];
		// Tipo propuesta
		$propuestas[$datos['propuesta']]['tipo']['tipo'] = $datos['propuesta_tipo'];
		$propuestas[$datos['propuesta']]['tipo']['descripcion'] = $datos['propuesta_tipo_descripcion'];
		// Responsables académicas 
		$propuestas[$datos['propuesta']]['responsables_academicas'][] = [
			'responsable_academica' => $datos['responsable_academica'],
			'codigo' => $datos['responsable_academica_codigo'],
			'nombre' => $datos['responsable_academica_nombre'],
			'institucion' => [
				'institucion' => $datos['institucion'],
				'codigo' => $datos['institucion_codigo'],
				'nombre' => $datos['institucion_nombre'],
				'nombre_abreviado' => $datos['institucion_nombre_abreviado']
			]
		];
	}

}