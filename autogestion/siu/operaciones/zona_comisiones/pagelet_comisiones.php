<?php
namespace siu\operaciones\zona_comisiones;

use SIU\Chulupi\interfaz\pagelet;
use \SIU\Chulupi\kernel;
use \siu\modelo\datos\catalogo;

class pagelet_comisiones extends pagelet
{

	function get_nombre()
	{
		return 'comisiones';
	}

    function get_js_files()
    {
        $archivos = parent::get_js_files();
        $archivos = array_merge($this->get_vista_form()->get_js_files(), $archivos);
        return $archivos;
    }

	function prepare()
	{

        $this->add_var_js('url_filtrar_comisiones', kernel::vinculador()->crear('zona_comisiones', 'index'));
        $this->add_mensaje_js('error_periodo_lectivo_invalido', kernel::traductor()->trans('zona_comisiones.error_periodo_lectivo_invalido'));

	}

    public function get_vista_form()
    {
        return $this->controlador->get_vista_form();
    }
}

?>