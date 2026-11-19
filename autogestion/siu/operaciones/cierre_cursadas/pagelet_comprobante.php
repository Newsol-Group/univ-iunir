<?php
namespace siu\operaciones\cierre_cursadas;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_comprobante extends pagelet
{
	protected $acta, $acta_hash;

	function get_nombre()
	{
		return 'comprobante';
	}

	/**
	 * @param $acta setea un arreglo que contiene el acta para poder hacer consultas
	 */
	public function set_acta_params($acta)
	{
		$this->acta = $acta;
	}

	/**
	 * @param $acta setea el acta codificada para poder hacer links
	 */
	public function set_acta_hash($acta)
	{
		$this->acta_hash = $acta;
	}

	function prepare()
	{
		$this->data = array();
		$this->data['acta'] = $this->controlador->modelo()->info__acta_cabecera($this->acta);
		$this->data['url_imprimir'] = kernel::vinculador()->crear('cierre_cursadas', 'imprimir', $this->acta_hash);
		$this->data['url_index'] = kernel::vinculador()->crear('cierre_cursadas', 'index');
	}

}
?>