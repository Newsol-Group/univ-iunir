<?php
namespace siu\operaciones\ficha_alumno;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;

class pagelet_bitacora extends pagelet
{
	const FORM_ERROR_VALIDACION = 'form_error_validacion';
	const FORM_GUARDADO = 'form_guardado';
	
	const ESTADO_LISTADO = 'listado';
	const ESTADO_EDICION = 'edicion';
	
	protected $form;
	protected $pantalla = 'listado';

	function set_pantalla($id)
	{
        $this->pantalla = $id;
	}
	
	function get_nombre()
    {
        return 'bitacora';
    }
	
	function get_form($id_html = null)
	{
		if (! isset($this->form)) {
			$this->form = kernel::localizador()->instanciar('operaciones\ficha_alumno\bitacora\form_bitacora', $this->controlador);
		}
		return $this->form;
	}
	
	function get_accion_alta_bitacora()
	{
		return $this->controlador->get_url_item_menu('bitacora', 'bitacora_editar');
	}
	
	function get_accion_volver_edicion()
	{
		return $this->controlador->get_url_item_menu('bitacora');;
	}
	
	function puede_editar_bitacora()
	{
		return $this->controlador->puede_editar_bitacora();
	}
	
	function get_datos_bitacora()
	{
		$datos = $this->controlador->get_registros_bitacora();
		foreach ($datos as $key => $dato) {
			$datos[$key]['LINK_EDITAR'] = $this->controlador->get_url_item_menu('bitacora', 'bitacora_editar', array('r' => $dato[catalogo::id]));
			if ($dato['usuario_alta'] == kernel::persona()->get_id()) {
				$datos[$key]['LINK_ELIMINAR'] = $this->controlador->get_url_item_menu('bitacora', 'bitacora_eliminar', array('r' => $dato[catalogo::id]));
				$datos[$key]['tooltip_editar'] = kernel::traductor()->trans('editar');
				$datos[$key]['icono_editar'] = 'icon-pencil';
			} else {
				$datos[$key]['tooltip_editar'] = kernel::traductor()->trans('ver');
				$datos[$key]['icono_editar'] = 'icon-search';
			}
		}
		return $datos;
	}
	
	function get_js_files()
	{
		$files = parent::get_js_files();
		$files[] = kernel::vinculador()->vinculo_recurso("js/utilidades.js");
		$files[] = kernel::vinculador()->vinculo_recurso("js/date-es-AR.js");
		return $files;
	}
	
	function prepare()
	{
		$this->add_var_js('msj_confirmar_eliminar', kernel::traductor()->trans('datos_censales.msj_confirmar_eliminar'));
		
		if ($this->pantalla == self::ESTADO_LISTADO) {
			$registros = $this->get_datos_bitacora();
			$this->data['registros'] = $registros;
			$this->data['cant_registros'] = \guarani_arreglos::count($registros);
		} else {
			$form = $this->get_form();
			switch ($this->estado) {
				case self::FORM_ERROR_VALIDACION:
					kernel::renderer()->add_to_ajax_response('info_estado', array(
						'mensaje' => kernel::traductor()->trans('guardado_datos_error'),
						'exitoso' => false
					));
					break;
				case self::FORM_GUARDADO:
					kernel::renderer()->add_to_ajax_response('info_estado', array(
						'mensaje' => kernel::traductor()->trans('guardado_datos_exitoso'),
						'exitoso' => true
					));
				default:
					$form->inicializar();
					$datos = $this->controlador->get_registro_bitacora();
					if (empty($datos)) {
						// Si es un alta, se establecen los valores especificados por defecto
						$datos = array(
							'fecha' => date("d/m/Y", time()),
							'visibilidad' => \persona_nucleo::bitacora_visibilidad_todos
						);
					}
					$form->set_datos($datos);
			}
			$form->activar_csrf($this->generar_csrf());
			$this->add_form($form);
		}
		
		$this->data['modo'] = $this->pantalla;
	}
}
?>