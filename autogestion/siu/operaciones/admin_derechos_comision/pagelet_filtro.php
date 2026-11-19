<?php
namespace siu\operaciones\admin_derechos_comision;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

use siu\extension_kernel\formularios\builder_filtro;
use siu\extension_kernel\formularios\guarani_form;
use siu\extension_kernel\formularios\guarani_vista_form;
use siu\operaciones\admin_derechos_comision\filtro\vista_filtro_comisiones;


class pagelet_filtro extends pagelet
{
    function get_nombre()
    {

        return 'filtro';
    }

	function get_js_files()
	{
		$archivos = parent::get_js_files();
		$archivos = array_merge($archivos, $this->get_filtro_vista()->get_js_files());
		return $archivos;
	}

	/**
	 * @return guarani_vista_form
	 */
	function get_filtro_vista()
	{
		return $this->controlador->get_builder_filtro()->get_vista();
	}

    function prepare()
    {
		$this->data = array();
	    $this->data['filtro'] = $this->get_filtro_vista();

    }

}
?>
