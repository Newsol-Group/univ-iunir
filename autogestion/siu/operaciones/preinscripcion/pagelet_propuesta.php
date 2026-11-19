<?php

namespace siu\operaciones\preinscripcion;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\modelo\entidades\parametro;

class pagelet_propuesta extends pagelet
{
	protected $form_propuesta;
        protected $mensaje_error;
	
        function ini()
	{
		
	}

	function get_nombre()
	{
		return 'propuesta';
	}

	function get_form_propuesta()
	{
		if (!isset($this->form_propuesta)) {
			$this->form_propuesta = kernel::localizador()->instanciar('operaciones\preinscripcion\propuesta\form_propuesta');
		}
		return $this->form_propuesta;
	}

	function get_action_eliminar()
	{
		return kernel::vinculador()->crear('preinscripcion', 'eliminar_preinscripcion');
	}
        function get_action_cambiar_estado()
	{
		return kernel::vinculador()->crear('preinscripcion', 'cambiar_estado_preinscripcion');
	}

	function prepare()
	{
                $controlador = kernel::ruteador()->get_id_operacion();
		$form_propuesta = $this->get_form_propuesta();
		$form_propuesta->inicializar();
		$this->add_form($form_propuesta);

		$this->add_var_js('url_propuestas', kernel::vinculador()->crear('preinscripcion', 'cascada_propuestas'));
		$this->add_var_js('url_ubicaciones', kernel::vinculador()->crear('preinscripcion', 'cascada_ubicaciones'));
		$this->add_var_js('url_modalidades', kernel::vinculador()->crear('preinscripcion', 'cascada_modalidades'));
		$this->add_var_js('error_vacio', kernel::traductor()->trans('preinscripcion.form_error_vacio'));
		$this->add_var_js('error_requisitos',kernel::sesion()->esta_seteada('mensaje_error'));
		$this->add_var_js('msj_confirmar_baja', kernel::traductor()->trans('preinscripcion.msj_confirmar_baja'));
		$this->add_var_js('msj_confirmar_finalizar', kernel::traductor()->trans('preinscripcion.msj_confirmar_finalizar'));

		$this->add_var_js('selector_propuesta', kernel::load_template('propuesta/selector_propuesta.twig')->render(array('form' => $form_propuesta)));
		$this->add_var_js('accion_eliminar', $this->get_action_eliminar());
		$this->add_var_js('accion_finalizar', $this->get_action_cambiar_estado());

		//----------------------------------------------------------------------

		$hay_periodo_preinsc_vigente = $this->controlador->modelo()->hay_periodo_preinsc_vigente();
		$cant_max_propuestas_preinsc = parametro::get_valor('pre_cant_max_propuestas_preinsc');
		$cantidad_preinscripciones_vigentes = $this->controlador->modelo()->get_cant_preinscripciones_vigentes();
		$preinscripciones = $this->controlador->modelo()->get_datos_preinscripciones();

		// Se permiten preinscripciones cuando existe al menos un período de insc. 
		// vigente de tipo "Preinscripción" o "Todos", y el alumno no supera la 
		// cantidad máxima de preinscripciones vigentes (en caso de que se haya
		// definido dicho límite)
		$permitir_inscripciones = $hay_periodo_preinsc_vigente && (($cant_max_propuestas_preinsc == 0) || ($cantidad_preinscripciones_vigentes < $cant_max_propuestas_preinsc));

		$this->data['hay_periodo_preinsc_vigente'] = $hay_periodo_preinsc_vigente;
		$this->data['permitir_inscripciones'] = $permitir_inscripciones;
		$this->data['cant_preinscripciones'] = \guarani_arreglos::count($preinscripciones);
		$this->data['preinscripciones'] = $preinscripciones;        
                $this->data['url_pdf'] = kernel::vinculador()->crear($controlador, 'generar_pdf');
           
                $this->data['error'] =  (kernel::sesion()->esta_seteada('mensaje_error'))? kernel::sesion()->get('mensaje_error' ) :'';
                $this->data['mensaje'] = (kernel::sesion()->esta_seteada('mensaje_ok'))?kernel::sesion()->get('mensaje_ok' ):'';
                
	}

}