<?php

namespace siu\operaciones\importar_config_form;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use SIU\Chulupi\util\db\dba;
use SIU\Chulupi\ErrorKernelDb;
use siu\extension_kernel\controlador_preinscripcion;
use siu\operaciones\importar_config_form\pagelet_import;
use siu\modelo\lib\catalogo;

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

	function accion__importar_configuracion()
	{
		if (!kernel::request()->isPost()) {
			return;
		}

		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO, array('default' => 'import'));
		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);

		$form = $pagelet->get_form();
		if ($form->procesar()) {
			$datos = $form->get_datos();
			try {
				$this->importar_configuracion($datos);

				/**
				 * Se resetea la caché de configuración de campos, para que se
				 * recargue con los nuevos valores en el próximo pedido de página 
				 */
				catalogo::cache()->eliminar($_SERVER['UA'] . 'parametros_campos');

				$pagelet->set_estado_info(pagelet_import::IMPORT_OK);
			} catch (ErrorKernelDb $e) {
				$pagelet->set_estado_info(pagelet_import::ERROR_BD);
			}
		} else {
			$pagelet->set_estado_info(pagelet_import::FORM_ERROR_VALIDACION);
		}

		kernel::renderer()->add($pagelet);
	}

	protected function importar_configuracion($param)
	{
		$param['vendor'] = 'pgsql';
		$db = dba::conectar($param);
		$sql = "SELECT 
					tipo_de_carrera,
					columna,
					visible,
					obligatorio,
					se_imprime,
					genera_reporte
				FROM 
					sga_campos_conf";
		$datos = $db->consultar($sql);

		$sql = '';
		foreach ($datos as $dato) {
			$sql .= "	UPDATE	sga_campos_conf
						SET		visible = '{$dato['visible']}',
								obligatorio = '{$dato['obligatorio']}',
								se_imprime = '{$dato['se_imprime']}',
								genera_reporte = '{$dato['genera_reporte']}'
						WHERE	tipo_de_carrera = '{$dato['tipo_de_carrera']}'
								AND columna = '{$dato['columna']}';

								";
		}

		try {
			kernel::db()->abrir_transaccion();
			kernel::db()->ejecutar($sql);
			kernel::db()->cerrar_transaccion();
		} catch (ErrorKernelDb $e) {
			kernel::db()->abortar_transaccion();
			throw $e;
		}
	}

}
?>