<?php
namespace siu\operaciones\datos_censales;

use \SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_lista_forms extends pagelet
{
	function get_nombre()
    {
        return 'lista_forms';
    }
	
    function get_menu()
	{
        $operacion = kernel::ruteador()->get_id_operacion();
        $menu_con_urls = array();
		
		$forms = $this->controlador->vista()->get_forms();
		
		if ($this->controlador->vista()->control_faltantes_activado()) {
			$faltantes = $this->controlador->get_campos_faltantes();
		} else {
			$faltantes = array();
		}
		
        foreach($forms as $item) {
			$menu_con_urls[] = array(
				'nombre' => 'datos_censales.form.'.$item,
				'url'  => kernel::vinculador()->crear($operacion, 'index', $item),
				'activo' => $this->controlador->vista()->get_id_form_activo() == $item,
				'con_faltantes' => isset($faltantes[$item])
			);
        }
        
        return $menu_con_urls;
    }
    
	function prepare()
	{
		$this->data['form_actual'] = $this->controlador->get_var('form_actual');
		$this->data['mensaje_lateral'] = 'datos_censales.mensaje_carga_datos';
		$this->data['fecha_ult_actualizacion'] = $this->controlador->modelo()->info__fecha_acutalizacion_dc();
	}
}
?>