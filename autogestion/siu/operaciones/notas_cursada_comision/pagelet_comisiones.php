<?php
namespace siu\operaciones\notas_cursada_comision;

use \SIU\Chulupi\kernel;

class pagelet_comisiones extends \SIU\Chulupi\interfaz\pagelet
{
	function get_nombre()
	{
		return 'comisiones';
	}

	function prepare()
	{
		$this->add_var_js('url_ver_mas', kernel::vinculador()->crear('notas_cursada_comision', 'ver_mas'));
	}

}
?>