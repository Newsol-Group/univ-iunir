<?php
namespace siu\operaciones\ficha_alumno;

use \SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_lista_reportes extends pagelet
{
	function get_nombre()
    {
        return 'lista_reportes';
    }
	
    function get_menu()
	{
		$menu_con_urls = array();		
		$forms = $this->controlador->vista()->get_reportes();
        foreach($forms as $item) {
			$menu_con_urls[] = array(
				'nombre' => 'ficha_alumno.menu.'.$item,
				'url'  => $this->controlador->get_url_item_menu($item),
				//kernel::vinculador()->crear($operacion, 'index', $item),
				'activo' => $this->controlador->vista()->get_id_rep_activo() == $item,
			);
        }
        return $menu_con_urls;
    }
    
	function prepare()
	{
//		$this->data['form_actual'] = $this->controlador->get_var('form_actual');
//		$this->data['mensaje_lateral'] = 'datos_censales.mensaje_carga_datos';
	}
}
?>