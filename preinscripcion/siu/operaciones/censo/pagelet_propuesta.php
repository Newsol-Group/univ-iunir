<?php
namespace siu\operaciones\censo;

use SIU\Chulupi\kernel;
use siu\operaciones\_comun\pagelet_con_form;
use siu\modelo\propuesta;

class pagelet_propuesta extends pagelet_con_form
{

	function get_nombre()
	{
		return 'propuesta';
	}

	function get_form($id_html = null)
	{
		if (!isset($this->form)) {
			$this->form = kernel::localizador()->instanciar('operaciones\censo\propuesta\form_propuesta');
		}
		return $this->form;
	}

	function get_js_files()
	{
		$files = parent::get_js_files();
		$files[] = kernel::vinculador()->vinculo_recurso("js/plugin_autosave.js");
		return $files;
	}

	function get_action_eliminar()
	{
		return kernel::vinculador()->crear('censo', 'eliminar_preinscripcion');
	}

	function get_datos_inscripciones()
	{
		return kernel::persona()->datos()->get_datos_inscripciones();
	}

	function prepare()
	{
		$form = $this->get_form();
		$form->inicializar();
		$this->add_form($form);

		$this->add_var_js('url_propuestas', kernel::vinculador()->crear('censo', 'cascada_propuestas'));
		$this->add_var_js('url_ubicaciones', kernel::vinculador()->crear('censo', 'cascada_ubicaciones'));
		$this->add_var_js('url_modalidades', kernel::vinculador()->crear('censo', 'cascada_modalidades'));
		$this->add_var_js('error_vacio', kernel::traductor()->trans('form_validacion_error.vacio'));

		$this->add_var_js('msj_confirmar_baja', kernel::traductor()->trans('msj_confirmar_baja'));
		$this->add_var_js('msj_una_insc_posgrado_baja', kernel::traductor()->trans('msj_una_insc_posgrado_baja'));
		$this->add_var_js('tipo_POSGRADO', propuesta::tipo_POSGRADO);
		$this->add_var_js('cantidad_insc_posgrado', kernel::persona()->datos()->get_cantidad_inscripciones_posgrado());

		$this->add_var_js('selector_propuesta', kernel::load_template('propuesta/selector_propuesta.twig')->render(array('form' => $form)));

		$cant_max_propuestas_insc = kernel::proyecto()->get_cant_max_propuestas_insc();
		$cantidad_inscripciones = kernel::persona()->datos()->get_cantidad_inscripciones();
		if ($cant_max_propuestas_insc == 0) {
			// No hay límite de propuestas a inscribirse
			$permitir_inscripciones = true;
		} else {
			$permitir_inscripciones = ($cantidad_inscripciones < $cant_max_propuestas_insc);
		}

		$this->data['cant_inscripciones'] = $cantidad_inscripciones;
		$this->data['permitir_inscripciones'] = $permitir_inscripciones;

		if (kernel::sesion()->esta_seteada('mensaje_primer_acceso')) {
			$this->data['mensaje_primer_acceso'] = kernel::traductor()->trans(kernel::sesion()->get('mensaje_primer_acceso'));
			kernel::sesion()->borrar('mensaje_primer_acceso');
		}
		
		$this->add_var_js('accion_eliminar', $this->get_action_eliminar());
		
	}

}
?>