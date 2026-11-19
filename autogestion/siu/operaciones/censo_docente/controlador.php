<?php
namespace siu\operaciones\censo_docente;

use siu\guarani;
use SIU\Chulupi\kernel;
use siu\extension_kernel\controlador_g3w2;
use siu\modelo\datos\catalogo;

class controlador extends controlador_g3w2
{
    function ini(){
//        $this->controlar_csrf_accion('guardar');
    }
    
    function modelo()
    {
		return guarani::datos_censales();
    }

    function accion__index()
    {
        $this->vista()->mostrar('principales', $this->modelo()->get_datos_censales());        
        kernel::renderer()->add($this->vista()->pagelet('formulario'));
    }
	
	function accion__guardar()
	{
		if (kernel::request()->isPost()) {
			try {
				$respuesta = $this->modelo()->grabar_datos_censales($_POST);
				$respuesta['cont'] = "Guardado";
			} catch (\siu\errores\error_procesar_datos_censales $e) {
				$respuesta['cod'] = -3;
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
    
    function modo_reinscripcion()
    {
        return kernel::sesion()->esta_seteada('datos_censales_reinscripcion');
    }
}
?>