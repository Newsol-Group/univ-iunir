<?php

namespace SIU\Guarani\php\rest\v2\modelos;

use SIUToba\rest\lib\rest_error;
use SIUToba\rest\lib\rest_filtro_sql;
use SIUToba\rest\lib\rest_hidratador;
use SIUToba\rest\lib\rest_validador;
use SIU\Guarani\php\rest\v2\cursos\recurso_cursos;

class rest_cursos_nucleo
{

	/**
	 * @var \co_cursos
	 */
	protected $modelo;

	/**
	 * @var \act_cursos
	 */
	protected $modelo_act;

	function __construct()
	{
		$this->modelo = \guarani::co('co_cursos');
		$this->modelo_act = \guarani::act('act_cursos');
	}
		
	protected function get_modelo($id){
		$modelos = recurso_cursos::_get_modelos();
		return $modelos[$id];
	}

	function get_list()
	{
		$filtro = new rest_filtro_sql();
		$filtro->agregar_campo_simple('plataforma', 'int_pv_plataformas.plataforma = %s')
			->agregar_campo_ordenable('id_curso', 'int_pv_cursos.curso')
			->agregar_campo_ordenable('id_curso_externo', 'int_pv_cursos.id_curso_externo::int')
		;

		$limit = $filtro->get_sql_limit(20);
		$order = $filtro->get_sql_order_by('+int_pv_cursos.curso');
		$where = $filtro->get_sql_where();

		$rs = $this->modelo->get_listado_rest($where, $order, $limit);
		$datos = rest_hidratador::hidratar($this->get_modelo('Curso'), $rs);
		return $datos;
	}

	function get($id)
	{
		$filtro = new rest_filtro_sql();
		$filtro->agregar_campo_simple_local('id_curso', 'int_pv_cursos.curso = %s', $id);

		$rs = $this->modelo->get_listado_rest($filtro->get_sql_where());
		if (\guarani_arreglos::count($rs) > 0){
			$datos = rest_hidratador::hidratar($this->get_modelo('Curso'), $rs);
			return $datos;
		} else {
			throw new rest_error(404, "El curso $id no existe");
		}
	}

	/**
	 * Retorna el id del recurso creado (id_curso de los datos)
	 */
	function post($data)
    {
        $modelos = recurso_cursos::_get_modelos();
        $modelo_curso = $modelos['CursoPost'];
        // Valido el modelo
        rest_validador::validar($data, $modelo_curso);
		
		list($resultado, $curso) = $this->modelo_act->crear_curso($data);
        if ($resultado) {
			return $curso;
		}
		
		throw new rest_error(500, "Falló la creación del curso");
    }
	
	/**
	 * Retorna si tuvo exito
	 */
	function put($id, $data)
	{
		$modelos = recurso_cursos::_get_modelos();
        $modelo_curso = $modelos['CursoPut'];
        // Valido el modelo
        rest_validador::validar($data, $modelo_curso);
		
		return $this->modelo_act->modificar_curso($id, $data);
	}

	public function delete($id_curso)
	{
		return $this->modelo_act->eliminar_curso($id_curso);
	}

	function get_alumnos($id_curso)
	{
		$rs = $this->modelo->get_alumnos($id_curso);
		
		$alumnos = rest_hidratador::hidratar($this->get_modelo('AlumnoCurso'), $rs);
		foreach ($alumnos as &$a) {
			$a['instancias'] = explode('/', $a['instancias']);
		}
		
		return $alumnos;
	}


	public function put_comision($id_curso, $id_comision)
	{
		$this->modelo_act->asociar_comision($id_curso, $id_comision);
	}

	public function delete_comision($id_curso, $id_comision)
	{
		if (!$this->modelo_act->desasociar_comision($id_curso, $id_comision) ){
			throw new rest_error(404, "La comisión $id_comision no está asociada al curso");
		}

	}

	protected function agregar_comisiones($curso, $rs)
	{
		//O(n^2)
		foreach ($rs as $r) {
			foreach ($curso as &$c) {
				if ($r['curso'] == $c['curso']) {
					$c['comisiones'][] = $r['comisiones'];
					break;
				}
			}
		}
	}


	/**
	 * Revierte un objeto hidratado - Deberìa ser el formato que maneja el usuario, por lo tanto
	 * puede usarse para mapear el input del usuario al formato del modelo.
	 * @param $data array la fila para deshidratar
	 * @param $spec_hidratar
	 * @param array $nueva_fila Se utiliza para poder llamar la funcion recursivamente.
	 *                          Posiblemente no se necesite utilizarla en la llamada inicial
	 * @return array la fila deshidratado
	 */
	static function rest_deshidratar_fila($data, $spec_hidratar, &$nueva_fila = array())
	{

		foreach ($spec_hidratar as $key => $campo) {
			if(!is_array($campo)){ //si no proveen todos los campos no los incluyo.
				if(isset($data[(string)$campo]))
					$nueva_fila[(string)$campo] = $data[(string)$campo]; // 2 => 'campo'
				continue;
			}
			if(!isset($data[$key])) continue;

			if(isset($campo['_mapeo'])){// "nombre" => array('_mapeo' => "otro nombre",
				$nueva_fila[$campo['_mapeo']] = $data[$key];
				continue;
			}
			if(isset($campo['_compuesto'])){
				//pongo en la misma fila, las columnas del compuesto
				static::rest_deshidratar_fila($data, $spec_hidratar['_compuesto'], $nueva_fila);
				continue;
			}
			//pasa derecho
			$nueva_fila[$key] = $data[$key];
		}

		return $nueva_fila;
	}
}