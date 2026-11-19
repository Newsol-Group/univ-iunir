<?php
namespace siu\operaciones\admin_derechos_mesa;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\operaciones\admin_derechos_mesa\mesas\filtro_vista;
use siu\operaciones\admin_derechos_mesa\mesas\form_filtro_mesas;
use siu\operaciones\admin_derechos_mesa\mesas\vista_filtro_mesas;


class pagelet_mesas extends pagelet
{
	protected $datos_filtro = array();

	function get_nombre()
    {
        return 'mesas';
    }

	function get_lista_actividades()
	{

		/* @var $mesas \SIU\Chulupi\util\modelo\ProcesadorVista */
		$mesas = $this->controlador->codificador_modelo()->info__mesas($this->datos_filtro);
		$mesas->link('LINK', 'admin_derechos_mesa', 'edicion');
		$mesas->link('LINK_DOC', 'admin_derechos_mesa', 'info_docentes');
		$actividades = $this->generar_corte_actividad($mesas);

		return $actividades;
	}

	function set_filtro($datos_filtro){
		$this->datos_filtro = $datos_filtro;
	}

	function prepare()
	{
		$this->data = array();
	    //si hay un filtro las genero, sino va vacio

	    //son 2 casos: Vacio porque no filtro, y vacio porque no hay mesas
	    $this->data['filtro_ok'] = 's';
		$this->data['actividades'] = $this->get_lista_actividades();
    }

	/**
	 * @param $mesas
	 * @return array
	 */
	public function generar_corte_actividad($mesas)
	{
		$actividades = array();
		foreach ($mesas as $c) {
			$act = $c['actividad_codigo'];
			$actividades[$act]['nombre'] = $c['actividad_nombre'];
			$actividades[$act]['codigo'] = $c['actividad_codigo'];
			//estas 2 se pisan por cada comision repetida (pero codigo mas limpio)
			$actividades[$act]['mesas'][] = $c;
		}
		return $actividades;
	}
}
?>
