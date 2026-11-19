<?php

namespace siu\operaciones\limpiar_cache;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use siu\extension_kernel\controlador_preinscripcion;
use siu\operaciones\limpiar_cache\pagelet_limpieza;

class controlador extends controlador_preinscripcion
{
	/**
	 * @return null
	 */
	function modelo()
	{
		return null;
	}

	function ini()
	{
		
	}

	function accion__index()
	{
		
	}

	function accion__limpieza()
	{
		if (!kernel::request()->isPost()) {
			return;
		}

		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO, array('default' => 'limpieza'));
		$pagelet = $this->vista()->pagelet($formulario);

		$path_clearapc = __DIR__ . '/../../www/clearapc.php';
		file_put_contents($path_clearapc, "
			<?php
				try {
				  apcu_clear_cache();
				  echo json_encode(array('success' => true));
				} catch (Exception \$e) {
				  echo json_encode(array('success' => false));
				}

			?>");

		$alias = kernel::proyecto()->get_alias();
		$url = "http://localhost" . $alias . "/clearapc.php";
		$result = json_decode(file_get_contents($url));

		if ($result->success) {
			$pagelet->set_estado_info(pagelet_limpieza::BORRADO_OK);
		} else {
			$pagelet->set_estado_info(pagelet_limpieza::ERROR);
		}

		unlink($path_clearapc);
		kernel::renderer()->add($pagelet);
	}

}