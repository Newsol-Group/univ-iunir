<?php
namespace siu\operaciones\zona_examenes;

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
		$data = $this->controlador->modelo()->info__mesa_cabecera($this->controlador->llamado_mesa_params);
		$this->data = $data;
	}

}
?>
