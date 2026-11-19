<?php
namespace siu\operaciones\evaluaciones;

use SIU\Chulupi\interfaz\pagelet;
use \SIU\Chulupi\kernel;
use \siu\modelo\datos\catalogo;
use siu\operaciones\evaluaciones\nueva_evaluacion\builder_nueva_evaluacion;

class pagelet_nueva_evaluacion extends pagelet
{
	function get_nombre()
	{
		return 'nueva_evaluacion';
	}

	function get_js_files()
	{
		$archivos = parent::get_js_files();
		$archivos = array_merge($this->get_vista_form()->get_js_files(), $archivos);
		return $archivos;
	}

	/**
	 * @return \siu\extension_kernel\formularios\guarani_vista_form
	 */
	function get_vista_form()
	{
		$vista = $this->controlador->get_vista_form();
		return $vista;
	}


	function prepare()
	{
		$comision = $this->controlador->params_comision;

		$this->data['comision'] = $comision;
		$this->data['url_cancelar'] = $this->controlador->get_index_url();
	}
}

?>