<?php
namespace siu\operaciones\asistencias_planilla;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_planilla extends pagelet
{
	public $filtros = array('cantidad' => '', 'fecha' => '', 'tipo' => '', 'imprime_libres' => '');

	function get_nombre()
	{
		return 'planilla';
	}

	function get_js_files()
	{
		$js = parent::get_js_files();
		$archivos = array_merge($js, $this->get_vista_form()->get_js_files());
		$archivos[] = kernel::vinculador()->vinculo_recurso("js/date-es-AR.js");
		return $archivos;
	}

	/**
	 * @return guarani_form
	 */
	function get_form($id_html = null)
	{
		return $this->get_form_builder()->get_formulario();
	}

	function get_vista_form()
	{
		return $this->get_form_builder()->get_vista();
	}

	/**
	 * @return builder_form_filtro
	 */
	function get_form_builder()
	{
		return $this->controlador->get_form_builder();
	}

	function set_filtros($filtros)
	{
		$this->filtros = array_merge($this->filtros, $filtros);
	}

	function get_filtros()
	{
		return $this->filtros;
	}

	function get_comision_hash()
	{
		return $this->controlador->get_hash_comision();
	}

	function prepare()
	{
		$this->data['planilla'] = $this->controlador->get_planilla();
		$this->data['filtro_cantidad'] = $this->filtros['cantidad'];
	}

}
?>