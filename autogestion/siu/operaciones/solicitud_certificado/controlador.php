<?php
namespace siu\operaciones\solicitud_certificado;

use SIU\Chulupi\kernel;
use siu\guarani;
use siu\extension_kernel\controlador_g3w2;
use SIU\Chulupi\util\validador;

class controlador extends controlador_g3w2
{
	function modelo()
	{
		return guarani::solicitud_certificados();
	}
	
	function accion__index()
	{
		$pagelet = $this->vista()->pagelet('formulario');
		kernel::renderer()->add($pagelet);
	}
	
	function accion__solicitar()
	{
		if (kernel::request()->isPost()) {
			$respuesta = array();
			try {
				$certificado = kernel::request()->getPost('nro_certificado');
				$cantidad_copias = kernel::request()->getPost('cantidad');
				$argumentos = kernel::request()->getPost('args');
				
				$str_respuesta = $this->modelo()->evt__solicitar($certificado, $cantidad_copias, $argumentos);
				$respuesta['cod'] = 1;
				$respuesta['cont'] = $str_respuesta;
			} catch (error_guarani $e) {
				$respuesta['cod'] = -3;
				$respuesta['cont'] = $e->getMessage();
			}
			$this->render_ajax($respuesta['cont'], $respuesta);
			
		} else {
			$respuesta['cod'] = -3;
			$respuesta['cont'] = "Error en la solicitud de certificado";
			$this->render_ajax($respuesta['cont'], $respuesta);
		}
	}
	
	function get_id_certificado()
	{		
		return $this->validate_param(0, 'get', 
				validador::TIPO_INT, array('allowempty' => true, 'default' => false));
	}
}
?>