<?php
namespace siu\operaciones\notas_mesa_examen;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_mesas extends pagelet
{
	function get_nombre()
	{
		return 'mesas';
	}

	function get_lista_mesas()
	{
		$mesas = $this->controlador->codificador_modelo()->info__mesas_docente();
		$mesas->link('LINK', 'notas_mesa_examen', 'edicion');
		return $mesas;
	}

	function prepare()
	{
		$this->data = array();
		$this->data['mesas'] = $this->get_lista_mesas();
		$this->data['link_admin_actas_examen_fc'] = kernel::vinculador()->crear('admin_actas_examen_fc');
	}

}
?>
