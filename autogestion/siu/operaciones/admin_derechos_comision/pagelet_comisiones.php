<?php
namespace siu\operaciones\admin_derechos_comision;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\operaciones\admin_derechos_comision\comisiones\filtro_vista;
use siu\operaciones\admin_derechos_comision\comisiones\form_filtro_comisiones;
use siu\operaciones\admin_derechos_comision\comisiones\vista_filtro_comisiones;


class pagelet_comisiones extends pagelet
{
	protected $datos_filtro = array();

	function get_nombre()
    {
        return 'comisiones';
    }

	function get_lista_actividades()
	{

		/* @var $comisiones \SIU\Chulupi\util\modelo\ProcesadorVista */
		$comisiones = $this->controlador->codificador_modelo()->info__comisiones($this->datos_filtro);
		$comisiones->link('LINK', 'admin_derechos_comision', 'edicion');
		$comisiones->link('LINK_DOC', 'admin_derechos_comision', 'info_docentes');
		$actividades = $this->generar_corte_actividad($comisiones);

		return $actividades;
	}

	function set_filtro($datos_filtro){
		$this->datos_filtro = $datos_filtro;
	}

	function prepare()
	{
		$this->data = array();
	    //si hay un filtro las genero, sino va vacio

	    //son 2 casos: Vacio porque no filtro, y vacio porque no hay comisiones
	    $this->data['filtro_ok'] = 's';
		$this->data['actividades'] = $this->get_lista_actividades();

	   // $form = $this->get_filtro();
	   // $form->inicializar();
    }

	/**
	 * @param $comisiones
	 * @return array
	 */
	public function generar_corte_actividad($comisiones)
	{
		$actividades = array();
		foreach ($comisiones as $c) {
			$act = $c['actividad_codigo'];
			$actividades[$act]['nombre'] = $c['actividad_nombre'];
			$actividades[$act]['codigo'] = $c['actividad_codigo'];
			//estas 2 se pisan por cada comision repetida (pero codigo mas limpio)
			$actividades[$act]['comisiones'][] = $c;
		}
		return $actividades;
	}
}
?>
