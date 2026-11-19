<?php
namespace siu\operaciones\zona_clases;

use SIU\Chulupi\interfaz\pagelet;

class pagelet_home extends pagelet
{
	function get_nombre()
	{
		return 'home';
	}

	function prepare()
	{
		$this->data = array();

		$contr = $this->controlador;
		$params_comision = $contr->params_comision;

		/** @var $comision ProcesadorVista */
		$comision = $contr->codificador_modelo()->info__comision_links($params_comision);
		$comision->link('LINK_ASISTENCIA', 'asistencias', 'index');
		$comision->link('LINK_TEMAS', 'temas_dictados', 'index');
		$comision->link('LINK_PLANILLA', 'asistencias_planilla', 'index');

		$clases = $contr->modelo()->info__clases($params_comision);
		$this->data = $clases;

		$this->data['comision'] = current($comision->get_datos());
	}

}
?>
