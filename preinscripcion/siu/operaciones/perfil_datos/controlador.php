<?php

namespace siu\operaciones\perfil_datos;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use siu\extension_kernel\controlador_preinscripcion;

class controlador extends controlador_preinscripcion
{
	static protected $modelo;

	function modelo()
	{
		if (!self::$modelo) {
			self::$modelo = kernel::localizador()->instanciar('operaciones\perfil_datos\modelo');
		}
		return self::$modelo;
	}

	function ini()
	{
		
	}

	function accion__index()
	{
		
	}

	function accion__guardar()
	{
		if (!kernel::request()->isPost()) {
			return;
		}

		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO, array('default' => 'config'));
		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);

		$form = $pagelet->get_form();
		if ($form->procesar()) {
			$datos = $form->get_datos();
			$this->modelo()->guardar_config($datos);
		}

		kernel::renderer()->add($pagelet);
	}

}
?>