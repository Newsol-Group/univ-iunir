<?php

namespace siu\operaciones\censo;

use SIU\Chulupi\kernel;
use siu\operaciones\censo\_comun\pagelet_con_edicion;
use siu\modelo\lib\catalogo;

class pagelet_actuacion_docente extends pagelet_con_edicion
{
	function get_nombre()
	{
		return 'actuacion_docente';
	}

	function get_form($id_html = null)
	{
		if (!isset($this->form)) {
			$this->form = kernel::localizador()->instanciar('operaciones\censo\actuacion_docente\form_actuacion_docente');
		}
		return $this->form;
	}

	function get_datos_actuacion_docente()
	{
		$datos = kernel::persona()->datos()->get_datos_actuacion_docente();
		foreach ($datos as $key => $dato) {
			$datos[$key]['LINK_EDITAR'] = kernel::vinculador()->crear('censo', 'editar', array('actuacion_docente', $dato[catalogo::id]));
			$datos[$key]['LINK_ELIMINAR'] = kernel::vinculador()->crear('censo', 'eliminar_actuacion_docente', array('actuacion_docente', $dato[catalogo::id]));
		}
		return $datos;
	}

	function get_js_files()
	{
		$files = parent::get_js_files();
		$files[] = kernel::vinculador()->vinculo_recurso("js/jqueryui.extra.js");
		return $files;
	}

	function prepare()
	{
		$form = $this->get_form();

		if ($this->pantalla == self::ESTADO_EDICION) {
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
					$datos = $this->controlador->get_actuacion_docente();
					$form->set_datos($datos);
			}
		}

		$form->activar_csrf($this->generar_csrf());
		$this->add_form($form);

		$this->data['cant_antecedentes'] = count($this->get_datos_actuacion_docente());
		$this->data['modo'] = $this->pantalla;

		$this->add_var_js('msj_confirmar_eliminar', kernel::traductor()->trans('msj_confirmar_eliminar'));
	}

}
?>