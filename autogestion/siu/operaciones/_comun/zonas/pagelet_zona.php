<?php
namespace siu\operaciones\_comun\zonas;

use SIU\Chulupi\interfaz\pagelet;
use \SIU\Chulupi\kernel;


class pagelet_zona extends pagelet
{

	/**
	 * @var zona
	 */
	protected $zona;

	protected function get_operacion_fisica()
	{
		return '_comun/zonas';
	}

	function get_nombre()
	{
		return 'zona';
	}

	/**
	 * @param zona $zona
	 */
	public function set_zona(zona $zona)
	{
		$this->zona = $zona;
		if (!!$zona->get_template()) {
			$this->set_template($zona->get_template());
		}
	}

	function prepare()
	{
		$this->zona->procesar();
		$this->data = array();
		$this->data['links'] = $this->zona->get_links_navegacion();

		$this->data['combo'] = $this->zona->get_links_combo();
		$this->data['url_back'] = $this->zona->get_link_indice();
		$this->data['detalle'] = $this->zona->get_datos();
		//$this->add_var_js('url_ver_mas', kernel::vinculador()->crear('notas_cursada_comision', 'ver_mas'));
		
		// Mensajes para el prompt de "perderá los cambios que ha realizado"
		$this->add_mensaje_js('msg_prompt_perdera_cambios', kernel::traductor()->trans('notas_cursada_comision.msg_prompt_perdera_cambios'));
		$this->add_mensaje_js('aceptar', kernel::traductor()->trans('aceptar'));
		$this->add_mensaje_js('cancelar', kernel::traductor()->trans('cancelar'));
	}
}