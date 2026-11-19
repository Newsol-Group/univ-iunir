<?php

namespace siu\operaciones\censo;

use SIU\Chulupi\kernel;
use siu\operaciones\_comun\pagelet_con_form;
use siu\preinscripcion;
use siu\modelo\comunes;

class pagelet_datos_principales extends pagelet_con_form
{
	protected $form_loc;

	function permite_cedula()
	{
		return kernel::persona()->datos()->permite_cedula();
	}

	function necesita_visa()
	{
		return kernel::persona()->datos()->necesita_visa();
	}

	function es_argentino()
	{
		return kernel::persona()->datos()->es_argentino();
	}

	//--------------------------------------------------------------------------

	function get_nombre()
	{
		return 'datos_principales';
	}

	function get_js_files()
	{
		$files = parent::get_js_files();
		$files[] = kernel::vinculador()->vinculo_recurso("js/jqueryui.extra.js");
		$files[] = kernel::vinculador()->vinculo_recurso("js/plugin_autosave.js");
		$files[] = kernel::vinculador()->vinculo_recurso("js/_comun/selector_localidad/selector_localidad.js");

		return $files;
	}

	function get_form($id_html = null)
	{
		if (!isset($this->form)) {
			$this->form = kernel::localizador()->instanciar('operaciones\censo\datos_principales\form_datos_principales');
			$this->form->set_autoset_activo(true);
		}
		return $this->form;
	}

	function get_form_localidad()
	{
		if (!isset($this->form_loc)) {
			$this->form_loc = kernel::localizador()->instanciar('operaciones\censo\_comun\selector_localidad\form_localidad');
		}
		return $this->form_loc;
	}

	function prepare()
	{
		$form = $this->get_form();
		$this->add_var_js('autosave', $form->is_autoset_activo());
		$this->add_var_js('msj_sin_cambios', kernel::traductor()->trans('form_sin_cambios'));
		$this->add_var_js('pueblo_originario_OTRO', preinscripcion::pueblo_originario_OTRO);
		$url_identidad_genero = kernel::proyecto()->get_url_identidad_genero();
		$this->data['url_identidad_genero'] = $url_identidad_genero;
		
		$this->add_mensaje_js('msj_nro_documento_dni_leyenda', kernel::traductor()->trans('nro_documento.dni.leyenda'));
		$this->add_mensaje_js('msj_nro_documento_dni_error', kernel::traductor()->trans('nro_documento.dni.error'));
		$this->add_mensaje_js('msj_nro_documento_cuil_leyenda', kernel::traductor()->trans('nro_documento.cuil.leyenda'));

		$this->add_var_js('nro_documento_dni_dnt_min', comunes::NRO_DOCUMENTO_DNI_DNT_MIN);
		$this->add_var_js('nro_documento_dni_dnt_max', comunes::NRO_DOCUMENTO_DNI_DNT_MAX);
		
		$this->add_var_js('tipo_documento_dni', comunes::TIPO_DOCUMENTO_DNI);
		$this->add_var_js('tipo_documento_dnt', comunes::TIPO_DOCUMENTO_DNT);
		$this->add_var_js('tipo_documento_cuil_cuit', comunes::TIPO_DOCUMENTO_CUIL_CUIT);
		$this->add_var_js('tipo_documento_cedula_diplomatica', comunes::TIPO_DOCUMENTO_CEDULA_DIPLOMATICA);
		$this->add_var_js('tipo_documento_pasaporte', comunes::TIPO_DOCUMENTO_PASAPORTE);
        $this->data['url_img_nro_tramite_dni'] = kernel::vinculador()->vinculo_img('img/nro_de_tramite_dni.jpeg');
        
        //obtengo el parametro de renaper
        $parametro = kernel::proyecto()->get_mat_validacion_renaper();
        $this->add_var_js('mat_validacion_renaper', $parametro);
        $this->data['mat_validacion_renaper'] = $parametro;
		
		$this->add_var_js('msg_guardado_datos_exitoso', false);
		if (kernel::sesion()->esta_seteada('msg_guardado_datos_exitoso')) {
			$this->add_var_js('msg_guardado_datos_exitoso', kernel::sesion()->get('msg_guardado_datos_exitoso'));
			kernel::sesion()->borrar('msg_guardado_datos_exitoso');
		}
		
		switch ($this->estado) {
			case self::FORM_ERROR_VALIDACION:
				kernel::renderer()->add_to_ajax_response('info_estado', array(
					'mensaje' => kernel::traductor()->trans('guardado_datos_error'),
					'exitoso' => false
				));
				$datos_doc = $this->get_datos_documento();
				// Se combinan los datos estáticos (de documento y nacionalidad)
				// con los datos con error del formulario.
				$form->set_datos(array_merge($datos_doc, $form->get_datos()));
				break;
			case self::FORM_CAMBIOS_DESDE_IMPRESION:
				kernel::renderer()->add_to_ajax_response('info_estado', array(
					'mensaje' => kernel::traductor()->trans('cambios_desde_impresion'),
					'exitoso' => true,
					'cambios_desde_impresion' => true
				));
				break;
			case self::FORM_GUARDADO:
				kernel::sesion()->set('msg_guardado_datos_exitoso', kernel::traductor()->trans('guardado_datos_exitoso'));
				kernel::renderer()->add_to_ajax_response('info_estado', array(
					'mensaje' => kernel::traductor()->trans('guardado_datos_exitoso'),
					'exitoso' => true,
					'cambios_desde_impresion' => false
				));
			default:
				$form->inicializar();
				$datos = kernel::persona()->datos()->get_datos_formularios_preinsc();
				$form->set_datos($datos);
                $this->add_var_js('validado_con_renaper',isset($datos['validado_con_renaper']) ? $datos['validado_con_renaper']: comunes::no);
		}

		if ($this->controlador->vista()->control_faltantes_activado()) {
			$this->marcar_faltantes();
		}

		$form->activar_csrf($this->generar_csrf());
		$this->add_form($form);

		$form_loc = $this->get_form_localidad();
		$form_loc->inicializar();
		$this->add_form($form_loc);

		$this->add_var_js('selector_localidad', kernel::load_template('_comun/selector_localidad/selector_localidad.twig')->render(array(
					'form_loc' => $form_loc
		)));

		$this->add_var_js('url_provincias', kernel::vinculador()->crear('censo', 'cascada_provincias'));
		$this->add_var_js('url_departamentos', kernel::vinculador()->crear('censo', 'cascada_departamentos'));
		$this->add_var_js('url_localidades', kernel::vinculador()->crear('censo', 'cascada_localidades'));
		$this->add_var_js('url_cod_postales', kernel::vinculador()->crear('censo', 'cascada_cod_postales'));
		$this->add_var_js('url_descr_localidad', kernel::vinculador()->crear('censo', 'get_descr_localidad'));
		$this->add_var_js('url_lista_localidades', kernel::vinculador()->crear('censo', 'lista_localidades'));
		$this->add_var_js('url_tipo_documentos', kernel::vinculador()->crear('censo', 'cascada_tipo_documento'));
		$this->add_var_js('url_cascada_nac', kernel::vinculador()->crear('censo', 'cascada_nacionalidad'));
		$this->add_var_js('url_tipo_documentos_validacion', kernel::vinculador()->crear('censo', 'datos_tipo_documento_validar'));

		$this->data['url_lista_localidades'] = kernel::vinculador()->crear('censo', 'lista_localidades');

		$this->add_var_js('error_vacio', kernel::traductor()->trans('form_validacion_error.vacio'));
		$this->add_var_js('ingresa_cp', false);
		
		$this->add_var_js('identidad_genero_especifique', comunes::GENERO_DIVERSIDAD_ESPECIFIQUE);
	}

	protected function get_datos_documento()
	{
		$datos = kernel::persona()->datos()->get_datos_formularios_preinsc();

		$datos_doc = [];
		$datos_doc['pais_documento_desc'] = $datos['pais_documento_desc'];
		$datos_doc['tipo_documento_desc'] = $datos['tipo_documento_desc'];
		$datos_doc['tipo_documento'] = $datos['tipo_documento'];
		$datos_doc['nacionalidad'] = $datos['nacionalidad'];
		$datos_doc['nacionalidad_desc'] = $datos['nacionalidad_desc'];
		$datos_doc['nro_documento'] = $datos['nro_documento'];

		return $datos_doc;
	}

}