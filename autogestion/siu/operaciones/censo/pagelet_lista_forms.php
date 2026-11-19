<?php
namespace siu\operaciones\censo;
use \SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_lista_forms extends pagelet
{       
	public $actual_form = "";
	private $menu_items = array(
        array(
            'nombre' => 'Datos principales',
            'form' => 'principales'
            ),
		array(
            'nombre' => 'Financiamiento de estudios',
            'form' => 'financiamiento'
            ),
		array(
            'nombre' => 'Situación laboral',
            'form' => 'trabajo'
            ),
        array(
            'nombre' => 'Situación familiar',
            'form' => 'padres'
            ),
        array(
            'nombre' => 'Estudios superiores',
            'form' => 'estudios'
            ),
        array(
            'nombre' => 'Tecnología',
            'form' => 'tecnologia'
            ),
		array(
            'nombre' => 'Deportes',
            'form' => 'deporte'
            ),
		array(
            'nombre' => 'Idiomas',
            'form' => 'idioma'
            )
    );
    
	function get_nombre()
    {
        return 'lista_forms';
    }
	
    function get_menu()
	{
        $operacion = kernel::ruteador()->get_id_operacion();
        
        $menu = $this->menu_items;
        foreach($menu as $key => $value) {
            $menu[$key]['url'] = kernel::vinculador()->crear($operacion, 'index', $menu[$key]['form']);
        }
        
        $visitados = array();
		if ($this->controlador->modo_reinscripcion()) {
			$visitados = $this->controlador->get_forms_visitados();
			foreach ($menu as $key => $value) {
				if (isset($menu[$key]['form'])) {
					if (isset($visitados[$value['form']])) {
						$menu[$key]['visitado'] = true;
					}
				}
			}
		}
        return $menu;
    }
    
	function prepare()
	{
		$this->data['form_actual'] = $this->controlador->get_var('form_actual');
        $this->data['url_imgs'] = kernel::vinculador()->vinculo_recurso('img');
		$this->data['menu'] = $this->get_menu();
		
        if ($this->controlador->modo_reinscripcion()) {
			$this->data['modo_reinscripcion'] = true;
            $this->add_var_js('modo_reinscripcion', true);
        }
		
        $this->add_var_js('url_reinscripcion', kernel::vinculador()->crear('reinscripcion', 'reinscribir'));
		
		$this->add_var_js('se_perderan_datos', kernel::traductor()->trans('censo.se_perderan_datos'));
		
		$this->add_var_js('reinscripcion_mensaje', kernel::traductor()->trans('reinscripcion_mensaje'));
		$this->add_var_js('reinscripcion_verificacion_completa', kernel::traductor()->trans('reinscripcion_verificacion_completa'));
		$this->add_var_js('reinscripcion_completa', kernel::traductor()->trans('reinscripcion_completa'));
	}
}

?>
