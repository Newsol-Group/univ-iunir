<?php

namespace iunir\operaciones\censo;

use SIU\Chulupi\kernel;
use siu\operaciones\_comun\pagelet_con_form;
use siu\modelo\propuesta;

use iunir\modelo\g3\consultas_bd\nsg_preinsc_facturacion;
use iunir\modelo\g3\consultas_bd\nsg_facturacion;

class pagelet_propuesta extends pagelet_con_form
{
	protected $mcp_key;
	protected $mcp_comercio;
	protected $facturacion;

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
	function get_css_files()
	{
		$files = parent::get_css_files();
		// recurso ubicado en pers: src/pers/<tu_personalizacion>/www/css/censo_propuesta.css
		$files[] = kernel::vinculador()->vinculo_recurso('css/censo_propuesta.css');
		return $files;
	}
	function get_action_eliminar()
	{
		return kernel::vinculador()->crear('censo', 'eliminar_preinscripcion');
	}

	function init_datos_preinscripciones()
	{
		$preinscripciones = kernel::persona()->datos()->get_datos_inscripciones();
		$this->data['preinscripciones'] = $preinscripciones;
	}


	function init_js_vars($form)
	{
		$this->add_var_js('url_propuestas', kernel::vinculador()->crear('censo', 'cascada_propuestas'));
		$this->add_var_js('url_ubicaciones', kernel::vinculador()->crear('censo', 'cascada_ubicaciones'));
		$this->add_var_js('url_modalidades', kernel::vinculador()->crear('censo', 'cascada_modalidades'));
		$this->add_var_js('error_vacio', kernel::traductor()->trans('form_validacion_error.vacio'));
		$this->add_var_js('msj_confirmar_baja', kernel::traductor()->trans('msj_confirmar_baja'));
		$this->add_var_js('msj_una_insc_posgrado_baja', kernel::traductor()->trans('msj_una_insc_posgrado_baja'));
		$this->add_var_js('msj_confirmar_finalizar', kernel::traductor()->trans('censo.msj_confirmar_finalizar'));
		$this->add_var_js('tipo_POSGRADO', propuesta::tipo_POSGRADO);
		$this->add_var_js('cantidad_insc_posgrado', kernel::persona()->datos()->get_cantidad_inscripciones_posgrado());
		$this->add_var_js('selector_propuesta', kernel::load_template('propuesta/selector_propuesta.twig')->render(array('form' => $form)));
		$this->add_var_js('accion_eliminar', $this->get_action_eliminar());
		$this->add_var_js('accion_finalizar', kernel::vinculador()->crear('censo', 'finalizar'));
		$this->add_var_js('msj_finalizando', kernel::traductor()->trans('censo.finalizando_inscripciones'));
		$this->add_var_js('msj_procesando_pago', kernel::traductor()->trans('censo.procesando_pago'));
		$this->add_var_js('msj_preparando_pago', kernel::traductor()->trans('censo.preparando_pago'));

		$this->add_var_js('msj_confirmar_pagar_mcp', kernel::traductor()->trans('censo.msj_confirmar_pagar_mcp'));
		$this->add_var_js('url_pagar_mcp', kernel::vinculador()->crear('censo', 'pagar_mcp'));

		$this->add_var_js('msj_confirmar_pagar_mp', kernel::traductor()->trans('censo.msj_confirmar_pagar_mp'));
		$this->add_var_js('url_pagar_mp', kernel::vinculador()->crear('censo', 'pagar_mp'));
	}
	function set_permitir_inscripciones()
	{
		$cant_max_propuestas_insc = kernel::proyecto()->get_cant_max_propuestas_insc();
		$cantidad_inscripciones = kernel::persona()->datos()->get_cantidad_inscripciones();
		$this->data['hay_inscripciones'] = $cantidad_inscripciones > 0;

		if ($cant_max_propuestas_insc == 0) {
			// No hay l�mite de propuestas a inscribirse
			$permitir_inscripciones = true;
		} else {
			$permitir_inscripciones = ($cantidad_inscripciones < $cant_max_propuestas_insc);
		}

		$this->data['cant_inscripciones'] = $cantidad_inscripciones;
		$this->data['permitir_inscripciones'] = $permitir_inscripciones;
	}

	function init_form()
	{
		$form = $this->get_form();
		$form->inicializar();
		$this->add_form($form);
		return $form;
	}

	function puede_finalizar()
	{
		$propuestas_aux = [21, 22, 23, 24];

		$puede = false;
		$preinscripciones = $this->data['preinscripciones'];
		foreach ($preinscripciones as $preinscripcion) {
			if (in_array($preinscripcion['propuesta'], $propuestas_aux)) {
				continue;
			}
			$matricula = $this->data['matriculas'][$preinscripcion['preinscripcion_propuesta']] ?? null;
			if (is_null($matricula)) return $puede;
			if ($matricula['estado_pago'] != 3) return $puede;
		}
		$puede = true;

		return $puede;
	}

	function get_propuestas_nombres()
	{
		$preinscripciones = $this->data['preinscripciones'];
		$nombres = [];
		foreach ($preinscripciones as $preinscripcion) {
			$nombres[] = $preinscripcion['propuesta_nombre'];
		}
		return $nombres;
	}

	function prepare()
	{
		$this->init_js_vars($this->init_form());
		$this->set_permitir_inscripciones();

		$this->facturacion = new nsg_preinsc_facturacion(true); // o FALSE

		$datos_preinscripciones = kernel::persona()->datos()->get_datos_inscripciones();
		$datos_matriculas = $this->facturacion->get_datosFacturacion(
			[
				'datos_preinscripciones' => $datos_preinscripciones,
				//'forzar_propuesta' => 1,
				//'forzar_ubicacion' => 1
			]
		);

		// kernel::log()->add_debug('Datos matriculas', $datos_matriculas);


		$this->data['preinscripciones'] = $datos_preinscripciones;
		$result = [];

		foreach ($datos_preinscripciones as $item) {
			$result[] = [
				'id_preinscripcion'       => $item['id_preinscripcion'],
				'preinscripcion_propuesta' => $item['preinscripcion_propuesta'],
			];
		}
		$this->add_var_js('preinscripciones', $result);

		foreach ($datos_preinscripciones as $key => $preinsc) {
			$this->data['preinscripciones'][$key]['ID_KEY'] = $key;
		}

		$this->data['matriculas'] = $datos_matriculas;

		$this->data['mcp_data'] = $this->facturacion->get_mcpData();

		if (kernel::sesion()->esta_seteada('mensaje_primer_acceso')) {
			$this->data['mensaje_primer_acceso'] = kernel::traductor()->trans(kernel::sesion()->get('mensaje_primer_acceso'));
			kernel::sesion()->borrar('mensaje_primer_acceso');
		}
	}
}
