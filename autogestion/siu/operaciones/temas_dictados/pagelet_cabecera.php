<?php
namespace siu\operaciones\temas_dictados;

use SIU\Chulupi\interfaz\pagelet;

class pagelet_cabecera extends pagelet
{
	function get_nombre()
	{
		return 'cabecera';
	}

	function prepare()
	{
		$this->data = array();
		$this->data = $this->controlador->get_clase_encabezado();
		$this->data['clases_no_anuladas'] = $this->controlador->get_clases_comision();
                
	}

}
?>
