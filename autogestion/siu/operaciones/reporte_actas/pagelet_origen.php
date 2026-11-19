<?php
namespace siu\operaciones\reporte_actas;

use \SIU\Chulupi\kernel;
use \siu\modelo\datos\catalogo;

class pagelet_origen
	extends \SIU\Chulupi\interfaz\pagelet
{
	function get_nombre()
	{
		return 'origen';
	}

	function prepare()
	{
		foreach(pagelet_acta::get_actas() as $clave => $valor)
			$origen[$clave] = kernel::traductor()->trans('reporte_actas.'.$valor);
		$this->data['origen'] = $origen;
		$this->data['origen_actual'] = kernel::request()->getParam('origen');
		$this->add_var_js('url_index', kernel::vinculador()->crear('reporte_actas', 'index'));
	}
}
?>