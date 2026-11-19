<?php

namespace siu\operaciones\copiar_config_form;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use siu\extension_kernel\controlador_preinscripcion;
use siu\operaciones\copiar_config_form\pagelet_copia;
use siu\modelo\util;

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

	function accion__copiar_configuracion()
	{
		if (!kernel::request()->isPost()) {
			return;
		}

		$formulario = $this->validate_param(0, 'get', validador::TIPO_TEXTO, array('default' => 'copia'));
		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);

		$form = $pagelet->get_form();
		if ($form->procesar()) {
			$datos = $form->get_datos();
			$this->copiar_configuracion($datos['tipo_propuesta_origen'], $datos['tipo_propuesta_destino']);
			// Se resetea la caché para borrar las configuraciones almacenadas
			util::catalogo()->limpiar_cache();
			$pagelet->set_estado_info(pagelet_copia::COPIA_OK);
		} else {
			$pagelet->set_estado_info(pagelet_copia::FORM_ERROR_VALIDACION);
		}

		kernel::renderer()->add($pagelet);
	}

	protected function copiar_configuracion($tipo_origen, $tipo_destino)
	{
		$tipo_origen = kernel::db()->quote($tipo_origen);
		$tipo_destino = kernel::db()->quote($tipo_destino);

		$sql = "UPDATE 	sga_campos_conf 
				SET 	(visible, obligatorio, se_imprime, genera_reporte) = (a.visible, a.obligatorio, a.se_imprime, a.genera_reporte)
				FROM 	sga_campos_conf as a
				WHERE 	sga_campos_conf.columna = a.columna
						AND a.propuesta_tipo = $tipo_origen
						AND sga_campos_conf.propuesta_tipo = $tipo_destino
				";

		kernel::db()->ejecutar($sql);
	}

}
?>