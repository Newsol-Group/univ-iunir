<?php
namespace siu\operaciones\censo;

use siu\guarani;
use SIU\Chulupi\kernel;
use siu\errores\error_guarani;
use siu\extension_kernel\controlador_g3w2;
use siu\modelo\datos\catalogo;

class controlador extends controlador_g3w2
{	
    function ini() 
	{
        $this->controlar_csrf_accion('guardar');
    }

    function modelo()
    {
		return guarani::datos_censales();
    }

    function accion__index()
    {
		$form_actual = (kernel::url()->hay_param(0)) ? $this->get_param(0, 'get') : 'principales';
		
		if ($this->modo_reinscripcion()) {
			$this->set_form_visitado($form_actual);
		}
		
		if(kernel::url()->hay_param(1)) {
			$this->vista()->pagelet('acciones')->data['save_status'] = $this->get_param(1, 'get');
		}
        
        $datos = $this->modelo()->get_datos_censales();
		$this->vista()->mostrar($form_actual, $datos);
		$this->add_var('form_actual', $form_actual);
		kernel::renderer()->add($this->vista()->pagelet('formulario'));
    }
	
	function accion__guardar()
	{
		if (kernel::request()->isPost()) {
			try {
				$respuesta = $this->modelo()->grabar_datos_censales($_POST);
				$respuesta['cont'] = "Guardado";
			} catch (error_guarani $e) {
				$respuesta['cod'] = -1;
				$respuesta['cont'] = $e->getMessage();				
			}
			$this->render_ajax($respuesta['cont'], $respuesta);
		}
	}
	
	function accion__provincias()
	{
		$pais = $this->get_param('pais', 'post');		
		
		$provincias = catalogo::consultar('etiquetas', 
										'provincias', 
										array('pais' => $pais)
										);
		$this->render_raw_json($provincias);
	}
	
	function accion__localidades()
	{
		$provincia = $this->get_param('provincia', 'post');
		$localidad = $this->get_param('localidad', 'post');		
		
		$parametros = array('provincia_id' => $provincia, 'localidad' => $localidad);
		
		$localidades = catalogo::consultar('etiquetas', 'localidades', $parametros);
		$this->render_raw_json($localidades);
	}
    
	
	// MANEJO REINSCRIPCION
	
    function modo_reinscripcion()
    {
        return kernel::sesion()->esta_seteada('datos_censales_reinscripcion');
    }
	
	function set_form_visitado($form)
	{
		$visitados = $this->get_forms_visitados();
		$visitados[$form]['visitado'] = true;
		$this->guardar_forms_visitados($visitados);
		
		$completo = true;
		$menu = $this->vista()->pagelet('lista_forms')->get_menu();
		
		foreach ($menu as $key => $value) {
            if (isset($menu[$key]['form'])) {
                if (!isset($visitados[$value['form']])) {                    
                    $completo = false;
                }
            }
        }
        
		$this->set_revision_completa($completo);
	}
	
	function get_forms_visitados()
	{
		if (kernel::sesion()->esta_seteada('dcensales_form_visitados')) {
			return kernel::sesion()->get('dcensales_form_visitados');
		} else {
			return array();
		}
	}
	
	protected function guardar_forms_visitados($forms_visitados)
	{
		kernel::sesion()->set('dcensales_form_visitados', $forms_visitados);
	}
	
	function set_revision_completa($value)
	{
		// habría que hacer un $this->modelo()->marcar_revisado();
		kernel::sesion()->set('dcensales_revision_completa', $value);
	}
	
	function get_revision_completa()
	{
		return kernel::sesion()->get('dcensales_revision_completa');
	}
	
}
?>