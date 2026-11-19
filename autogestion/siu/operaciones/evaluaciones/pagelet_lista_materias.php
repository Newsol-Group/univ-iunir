<?php
namespace siu\operaciones\evaluaciones;

use SIU\Chulupi\interfaz\pagelet;
use \SIU\Chulupi\kernel;
use SIU\Chulupi\util\modelo\ProcesadorVista;
use siu\errores\error_guarani;
use siu\modelo\_comun\comunes;
use \siu\modelo\datos\catalogo;

class pagelet_lista_materias extends pagelet
{
	function get_nombre()
	{
		return 'lista_materias';
	}

	function get_lista_materias()
	{
		$seleccion = $this->controlador->params_comision; //puede ser null
		if (empty($seleccion)) {
			throw new error_guarani('Falta filtro por comisión');
		}
		/** @var $evaluaciones ProcesadorVista */
		$evaluaciones = $this->controlador->codificador_modelo()->info__arbol_evaluaciones($seleccion);

		$com = $this->controlador->get_info_comision();
		$com->link('url_nueva_evaluacion', 'evaluaciones', 'nueva_evaluacion');
		$datos = $com->get_datos();
		$url_nueva_eval = $datos[0]['url_nueva_evaluacion'];

		$evaluaciones->link('url_editar_evaluacion', 'evaluaciones', 'editar_evaluacion');
		$evaluaciones->link('url_borrar_evaluacion', 'evaluaciones', 'borrar_evaluacion');
		$evaluaciones->link('url_listar_notas', 'evaluaciones', 'listar_notas');
		$evaluaciones->link('url_editar_notas', 'evaluaciones', 'editar_notas');

		$data = array();

		foreach ($evaluaciones as $evaluacion) {

			$data['materia'] = $evaluacion['actividad'];
			$data['nombre'] = $evaluacion['actividad_nombre'];

			$data['comision'] = $evaluacion;
			$data['comision']['url_nueva_evaluacion'] = $url_nueva_eval;

			if (isset($evaluacion['evaluacion'])) {
				$eval = $evaluacion['evaluacion'];

				$data['evaluaciones'][$eval] = $evaluacion;
			}
		}

		return $data;
	}

	function prepare()
	{
        $this->add_mensaje_js('seguro_desea_eliminar_evaluacion', kernel::traductor()->trans('seguro_desea_eliminar_evaluacion'));
		$this->data = $this->get_lista_materias();
		$this->data['estado_cerrado'] = comunes::estado_cerrado;
	}
}

?>