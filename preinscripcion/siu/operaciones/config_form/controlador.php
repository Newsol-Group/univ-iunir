<?php

namespace siu\operaciones\config_form;

use SIU\Chulupi\kernel;
use siu\extension_kernel\controlador_preinscripcion;
use siu\modelo\util;
use siu\modelo\opciones;
use siu\modelo\consultas;
use siu\modelo\lib\abm;
use siu\modelo\lib\catalogo;

class controlador extends controlador_preinscripcion
{
	protected $campos_configurables;

	function modelo()
	{
		return null;
	}

	function ini()
	{
		
	}

	function get_campos_configurables()
	{
		if (!isset($this->campos_configurables)) {
			$campos_configurables = util::form()->get_campos_configurables();
			$campos_pagelets = util::form()->get_campos_secciones();

			$this->campos_configurables = array();
			foreach ($campos_pagelets as $campos_secciones) {
				foreach ($campos_secciones as $campos_seccion) {
					foreach ($campos_seccion as $campo) {
						if (in_array($campo, $campos_configurables)) {
							$this->campos_configurables[] = $campo;
						}
					}
				}
			}
		}
		return $this->campos_configurables;
	}

	function get_configuracion()
	{
		$salida = array();
		if (kernel::sesion()->esta_seteada('tipo_propuesta_config')) {
			$tipo_propuesta = kernel::sesion()->get('tipo_propuesta_config');
			$datos = util::consultas()->get_parametros_campos_form($tipo_propuesta);
			foreach ($datos as $dato) {
				$prefix = $dato['columna'];
				$salida[$prefix . '_visible'] = $dato['visible'];
				$salida[$prefix . '_obligatorio'] = $dato['obligatorio'];
				$salida[$prefix . '_se_imprime'] = $dato['se_imprime'];
				$salida[$prefix . '_genera_reporte'] = $dato['genera_reporte'];
			}
		}
		return $salida;
	}

	function accion__index()
	{
		
	}

	function accion__seleccionar_tipo_propuesta()
	{
		if (!kernel::request()->isPost()) {
			return;
		}

		$datos = kernel::request()->getPost('form_tipos_propuesta');
		if ($datos['tipo_de_propuesta'] != opciones::NO_SELECCION) {
			kernel::sesion()->set('tipo_propuesta_config', $datos['tipo_de_propuesta']);
		} else {
			kernel::sesion()->borrar('tipo_propuesta_config');
		}

		$formulario = 'configuracion';
		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);
		kernel::renderer()->add($pagelet);
	}

	function accion__guardar()
	{
		if (!kernel::request()->isPost()) {
			return;
		}

		$formulario = 'configuracion';
		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);

		$datos = kernel::request()->getPost('form_configuracion');
		$campos = $this->get_campos_configurables();
		$campos_no_ocultables = util::form()->get_campos_no_ocultables_obligatorios();

		try {
			kernel::db()->abrir_transaccion();

			foreach ($campos as $campo) {
                            /*salvo el error
                            if (is_null($datos[$campo . '_visible'])){
                                $datos[$campo . '_visible']=0;
                            }
                            if (is_null($datos[$campo . '_obligatorio'])){
                                $datos[$campo . '_obligatorio']=0;
                            }
                            
                            if (is_null($datos[$campo . '_se_imprime'])){
                                $datos[$campo . '_se_imprime']=0;
                            }
                            if (is_null($datos[$campo . '_genera_reporte'])){
                                $datos[$campo . '_genera_reporte']=0;
                            }*/
				$update = array();
				if (!in_array($campo, $campos_no_ocultables)) {
					$update['visible'] = $datos[$campo . '_visible'];
					$update['obligatorio'] = $datos[$campo . '_obligatorio'];
				}
				$update['se_imprime'] = $datos[$campo . '_se_imprime'];
				$update['genera_reporte'] = $datos[$campo . '_genera_reporte'];

				$clave = array(
					'propuesta_tipo' => kernel::sesion()->get('tipo_propuesta_config'),
					'columna' => $campo
				);

				abm::modificacion('sga_campos_conf', $update, $clave);
			}

			kernel::db()->cerrar_transaccion();

			// Se resetea la caché para borrar las configuraciones almacenadas
			util::catalogo()->limpiar_cache();

			$pagelet->set_estado_info(pagelet_configuracion::FORM_GUARDADO);
		} catch (Exception $e) {
			kernel::db()->abortar_transaccion();
			$pagelet->set_estado_info(pagelet_configuracion::FORM_ERROR_GUARDADO);
		}

		kernel::renderer()->add($pagelet);
	}

}
?>