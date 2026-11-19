<?php

namespace siu\operaciones\censo;

use SIU\Chulupi\kernel;
use siu\operaciones\_comun\pagelet_con_form;
use siu\preinscripcion;
use siu\modelo\comunes;

class pagelet_datos_estudios extends pagelet_con_form
{
	function get_nombre()
	{
		return 'datos_estudios';
	}

	function get_form($id_html = null)
	{
		if (!isset($this->form)) {
			$this->form = kernel::localizador()->instanciar('operaciones\censo\datos_estudios\form_datos_estudios');
			$this->form->set_autoset_activo(true);
		}
		return $this->form;
	}

	function get_css_files()
	{
		$files = parent::get_css_files();
		$files[] = kernel::vinculador()->vinculo_recurso("css/jTPS.css");
		return $files;
	}

	function get_js_files()
	{
		$files = parent::get_js_files();
		$files[] = kernel::vinculador()->vinculo_recurso("js/jqueryui.extra.js");
		$files[] = kernel::vinculador()->vinculo_recurso("js/jTPS.js");
		$files[] = kernel::vinculador()->vinculo_recurso("js/plugin_autosave.js");
		$files[] = kernel::vinculador()->vinculo_recurso("js/_comun/selector_colegio/selector_colegio.js");
		$files[] = kernel::vinculador()->vinculo_recurso("js/_comun/selector_titulo_sec/selector_titulo_sec.js");
		return $files;
	}

	function prepare()
	{
		$form = $this->get_form();
		$this->add_var_js('autosave', $form->is_autoset_activo());
		$this->add_var_js('msj_sin_cambios', kernel::traductor()->trans('form_sin_cambios'));
		$this->add_var_js('estado_estudio_ABANDONADO', preinscripcion::estado_estudio_ABANDONADO);
		$this->add_var_js('estado_estudio_FINALIZADO', preinscripcion::estado_estudio_FINALIZADO);
		$this->add_var_js('razon_abandono_OTRO', preinscripcion::razon_abandono_OTRO);

		switch ($this->estado) {
			case self::FORM_ERROR_VALIDACION:
				kernel::renderer()->add_to_ajax_response('info_estado', array(
					'mensaje' => kernel::traductor()->trans('guardado_datos_error'),
					'exitoso' => false
				));
				break;
			case self::FORM_CAMBIOS_DESDE_IMPRESION:
				kernel::renderer()->add_to_ajax_response('info_estado', array(
					'mensaje' => kernel::traductor()->trans('cambios_desde_impresion'),
					'exitoso' => true,
					'cambios_desde_impresion' => true
				));
				break;
			case self::FORM_GUARDADO:
				kernel::renderer()->add_to_ajax_response('info_estado', array(
					'mensaje' => kernel::traductor()->trans('guardado_datos_exitoso'),
					'exitoso' => true,
					'cambios_desde_impresion' => false
				));
			default:
				$form->inicializar();
				$datos = kernel::persona()->datos()->get_datos_formularios_preinsc();
				
				$this->add_var_js('fecha_nacimiento', $datos['fecha_nacimiento']);
				$es_menor_25 = false;
				if (!empty($datos['fecha_nacimiento'])) {
					$edad = comunes::calcular_edad($datos['fecha_nacimiento']);
					$es_menor_25 = ($edad < 25);
				}
				$this->add_var_js('es_menor_25', $es_menor_25);
				$form->set_datos($datos);
		}

		if ($this->controlador->vista()->control_faltantes_activado()) {
			$this->marcar_faltantes();
		}

		$form->activar_csrf($this->generar_csrf());
		$this->add_form($form);

		$this->add_var_js('selector_colegio', kernel::load_template('_comun/selector_colegio/selector_colegio.twig')->render(array()));
		$this->add_var_js('mensaje_filtrar_colegio', kernel::traductor()->trans('mensaje_filtrar_colegio'));
		$this->add_var_js('url_colegios', kernel::vinculador()->crear('censo', 'buscar_colegios'));
		$this->add_var_js('url_buscar_colegio_por_id', kernel::vinculador()->crear('censo', 'buscar_colegio_por_id'));

		$this->add_var_js('selector_titulo_sec', kernel::load_template('_comun/selector_titulo_sec/selector_titulo_sec.twig')->render(array()));
		$this->add_var_js('mensaje_filtrar_titulo_sec', kernel::traductor()->trans('mensaje_filtrar_titulo_sec'));
		$this->add_var_js('url_titulo_sec', kernel::vinculador()->crear('censo', 'buscar_titulo_sec'));
		$this->add_var_js('url_buscar_titulo_por_id', kernel::vinculador()->crear('censo', 'buscar_titulo_por_id'));

		$this->add_var_js('mensaje_filtrar_caract', kernel::traductor()->trans('mensaje_filtrar_caract'));
		
		$this->add_var_js('url_lista_localidades', kernel::vinculador()->crear('censo', 'lista_localidades'));
                
		$this->add_mensaje_js('error_es_mayor_25_sin_secundario', kernel::traductor()->trans('error_es_mayor_25_sin_secundario'));
		$this->add_mensaje_js('error_fecha_nacimiento_vacia', kernel::traductor()->trans('error_fecha_nacimiento_vacia'));
	}

}