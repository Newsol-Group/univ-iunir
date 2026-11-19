<?php

namespace siu\operaciones\datos_censales;

use \siu\operaciones\datos_censales\_comun\pagelet_con_form_dc;
use SIU\Chulupi\kernel;
use siu\modelo\entidades\alumno_foto;
use siu\guarani;

class pagelet_datos_personales extends pagelet_con_form_dc
{
	protected $form_loc;
	protected $form_cp;

	function get_nombre() {
		return 'datos_personales';
	}

	function get_form($id_html = null) {
		if (!isset($this->form)) {
			$this->form = kernel::localizador()->instanciar('operaciones\datos_censales\datos_personales\form_datos_personales');
			$this->form->set_autoset_activo(true);
		}
		return $this->form;
	}

	function get_js_files() {
		$files = parent::get_js_files();
		$files[] = kernel::vinculador()->vinculo_recurso("js/utilidades.js");
		$files[] = kernel::vinculador()->vinculo_recurso("js/plugin_autosave.js");
		return $files;
	}

	function prepare() {

		$form = $this->get_form();
		$this->add_var_js('autosave', $form->is_autoset_activo());
		$this->add_var_js('msj_sin_cambios', kernel::traductor()->trans('form_sin_cambios'));
		$this->add_var_js('pueblo_originario_otros', \comunes::pueblo_originario_otros);
		$id_imagen = guarani::persona()->get_id_imagen();

		$url_identidad_genero = kernel::proyecto()->get_url_identidad_genero();

		$this->data['url_identidad_genero'] = $url_identidad_genero;
                
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
				$datos = $this->controlador->modelo()->info__datos_censales();
				$datos['tipo_documento'] = kernel::persona()->get_tipo_documento();
				$datos['tipo_documento_desc'] = kernel::persona()->get_tipo_documento_desc();
				$datos['numero_documento'] = kernel::persona()->get_nro_documento();
				$cuil = kernel::persona()->get_cuil();
				$datos['cuil'] = $cuil['nro_documento'] ?? "";
				$form->set_datos($datos);
		}
		if ($this->controlador->vista()->control_faltantes_activado()) {
			$this->marcar_faltantes();
		}

		if (!empty($id_imagen)) {
			$this->data['url_imagen'] = alumno_foto::url_imagen($id_imagen, alumno_foto::TAMANIO_MEDIANA);
		} else {
			$this->data['url_imagen'] = kernel::vinculador()->vinculo_recurso('img/iconos/mm_medio.png');
		}

		$this->add_var_js('identidad_genero_especifique', \persona::identidad_genero_ninguna);
		$this->add_var_js('si', \comunes::si);
		$this->add_mensaje_js('vacio', kernel::traductor()->trans('form_validacion_error.vacio'));
		$this->add_mensaje_js('guardado_datos_error', kernel::traductor()->trans('guardado_datos_error'));
		
		$form->activar_csrf($this->generar_csrf());
		$this->add_form($form);
	}

}