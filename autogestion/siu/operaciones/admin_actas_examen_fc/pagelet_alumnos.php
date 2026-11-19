<?php
namespace siu\operaciones\admin_actas_examen_fc;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\extension_kernel\formularios\guarani_vista_form;
use siu\modelo\entidades\parametro;
use siu\modelo\_comun\comunes;
use siu\operaciones\admin_actas_examen_fc\alumnos\form_alumnos;

class pagelet_alumnos extends pagelet
{
	const FORM_ERROR_VALIDACION = 'form_error_validacion';
	const FORM_ERROR = 'form_error';
	const FORM_GUARDADO = 'form_guardado';

	protected $datos;
	protected $instancia_automatica;
	protected $form_builder;

	function get_nombre()
	{
		return 'alumnos';
	}

	function get_js_files()
	{
		$archivos = parent::get_js_files();
		$archivos = array_merge($archivos, $this->get_form_vista()->get_js_files());
		$archivos[] = kernel::vinculador()->vinculo_recurso("js/lib/plugin.form.ml.js");
		return $archivos;
	}

	/**
	 * @return form_alumnos
	 */
	function get_form($id_html = null)
	{
		return $this->get_builder_form()->get_formulario();
	}

	/**
	 * @return guarani_vista_form
	 */
	function get_form_vista()
	{
		return $this->get_builder_form()->get_vista();
	}

	/**
	 * @return form_alumnos
	 */
	function get_builder_form()
	{
		if (!isset($this->form_builder)) {
			$this->form_builder = kernel::localizador()->instanciar('operaciones\admin_actas_examen_fc\alumnos\form_alumnos');
			$id_acta = $this->controlador->get_var('id_acta');
			$hash = $this->controlador->get_var('hash');
			$this->form_builder->set_id_acta($id_acta);
			$this->form_builder->set_hash($hash);

			if (!empty($id_acta)) {
				$this->form_builder->set_datos_originales($this->controlador->get_datos_completos_acta($id_acta));
			}
		}
		return $this->form_builder;
	}

	function prepare()
	{
		$form = $this->get_form();
		$hash = $this->controlador->get_var('hash');
		$this->add_var_js('url_buscar_alumnos', kernel::vinculador()->crear(kernel::ruteador()->get_id_operacion(), 'buscar_alumnos'));
		$this->add_var_js('url_agregar_alumno', kernel::vinculador()->crear(kernel::ruteador()->get_id_operacion(), 'agregar_alumno', array($hash)));
		$this->add_var_js('url_instancia_automatica', kernel::vinculador()->crear(kernel::ruteador()->get_id_operacion(), 'det_instancia_automatica'));
		$this->add_var_js('msg_navegacion', kernel::traductor()->trans('admin_actas_examen_fc.msg_navegacion'));
		$this->add_var_js('msg_sin_alumnos', kernel::traductor()->trans('admin_actas_examen_fc.msg_sin_alumnos'));
		$this->add_var_js('msg_persona_existe', kernel::traductor()->trans('admin_actas_examen_fc.msg_persona_existe'));
		$this->add_var_js('msg_confirmacion_crear_acta', kernel::traductor()->trans('admin_actas_examen_fc.msg_confirmacion_crear_acta'));
		$this->add_var_js('msg_confirmacion_crear_acta_tit', kernel::traductor()->trans('admin_actas_examen_fc.msg_confirmacion_crear_acta_tit'));
		$this->add_var_js('msg_eliminar_alumno', kernel::traductor()->trans('800_confirma_sacar_alimno_acta'));

		switch ($this->estado) {
			case self::FORM_ERROR_VALIDACION:
				$form->set_hubo_error(true);
				kernel::renderer()->add_to_ajax_response('info_estado', array(
					'mensaje' => kernel::traductor()->trans('admin_actas_examen_fc.guardado_datos_error'),
					'exitoso' => false
				));
				break;
			case self::FORM_GUARDADO:
				kernel::renderer()->add_to_ajax_response('info_estado', array(
					'mensaje' => kernel::traductor()->trans('admin_actas_examen_fc.guardado_datos_exitoso'),
					'exitoso' => true
				));
				break;
			default:
				$form->inicializar();
				$id_acta = $this->controlador->get_var('id_acta');
				$datos = array('alumnos' => $this->controlador->modelo()->info__acta_alumnos($id_acta));
				$form->set_datos($datos);
		}

		$this->add_form($form);
	}

}
?>