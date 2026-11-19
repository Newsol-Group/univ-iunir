<?php

namespace nsg01\operaciones\preinscripcion;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\modelo\entidades\parametro;

use nsg01\modelo\facturacion\nsg_g3w3pre_facturacion;

class pagelet_propuesta extends pagelet
{
	protected $form_propuesta;
	protected $mensaje_error;
	protected $facturacion;

	function ini() {}

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

	function init_js_vars($form_propuesta)
	{
		$this->add_var_js('url_propuestas', kernel::vinculador()->crear('preinscripcion', 'cascada_propuestas'));
		$this->add_var_js('url_ubicaciones', kernel::vinculador()->crear('preinscripcion', 'cascada_ubicaciones'));
		$this->add_var_js('url_modalidades', kernel::vinculador()->crear('preinscripcion', 'cascada_modalidades'));
		$this->add_var_js('error_vacio', kernel::traductor()->trans('preinscripcion.form_error_vacio'));
		$this->add_var_js('error_requisitos', kernel::sesion()->esta_seteada('mensaje_error'));
		$this->add_var_js('msj_confirmar_baja', kernel::traductor()->trans('preinscripcion.msj_confirmar_baja'));
		$this->add_var_js('msj_confirmar_finalizar', kernel::traductor()->trans('preinscripcion.msj_confirmar_finalizar'));
		$this->add_var_js('selector_propuesta', kernel::load_template('propuesta/selector_propuesta.twig')->render(array('form' => $form_propuesta)));
		$this->add_var_js('accion_eliminar', $this->get_action_eliminar());
		$this->add_var_js('accion_finalizar', $this->get_action_cambiar_estado());
		$this->add_var_js('accion_reabrir', kernel::vinculador()->crear('preinscripcion', 'cambiar_estado_preinscripcion'));
		$this->add_var_js('url_generate_mcp', kernel::vinculador()->crear('preinscripcion', 'generate_mcp'));

		$this->add_var_js('msj_finalizando', kernel::traductor()->trans('preinscripcion.finalizando_inscripcion'));

		$this->add_var_js('msj_confirmar_pagar_mcp', kernel::traductor()->trans('preinscripcion.msj_confirmar_pagar_mcp'));
		$this->add_var_js('url_pagar_mcp', kernel::vinculador()->crear('preinscripcion', 'pagar_mcp'));

		$this->add_var_js('msj_confirmar_pagar_mp', kernel::traductor()->trans('preinscripcion.msj_confirmar_pagar_mp'));
		$this->add_var_js('url_pagar_mp', kernel::vinculador()->crear('preinscripcion', 'pagar_mp'));
	}

	function permitir_inscripciones($hay_periodo_preinsc_vigente)
	{
		$cant_max_propuestas_preinsc = parametro::get_valor('pre_cant_max_propuestas_preinsc');
		$cantidad_preinscripciones_vigentes = $this->controlador->modelo()->get_cant_preinscripciones_vigentes();
		return $hay_periodo_preinsc_vigente && (($cant_max_propuestas_preinsc == 0) || ($cantidad_preinscripciones_vigentes < $cant_max_propuestas_preinsc));
	}

	function init_form_propuesta()
	{
		$form_propuesta = $this->get_form_propuesta();
		$form_propuesta->inicializar();
		$this->add_form($form_propuesta);
		return $form_propuesta;
	}

	function init_preinscripciones()
	{
		$preinscripciones = $this->controlador->modelo()->get_datos_preinscripciones();
		foreach ($preinscripciones as $key => $preinsc) {
			$preinscripciones[$key]['ID'] = $key;
		}
		
		$hay_periodo_preinsc_vigente = $this->controlador->modelo()->hay_periodo_preinsc_vigente();
		$this->data['hay_periodo_preinsc_vigente'] = $hay_periodo_preinsc_vigente;
		$this->data['permitir_inscripciones'] = $this->permitir_inscripciones($hay_periodo_preinsc_vigente);
		$this->data['cant_preinscripciones'] = \guarani_arreglos::count($preinscripciones);
		$this->data['preinscripciones'] = $preinscripciones;
	}

	function prepare()
	{
		$this->init_js_vars($this->init_form_propuesta());
		$this->init_preinscripciones();

		$parametros['persona'] = kernel::persona()->get_id();
		$es_perfil_cursos = kernel::persona()->es_perfil_cursos();
	
		if (!$es_perfil_cursos) {
			$alumno = kernel::persona()->get_id_alumno_activo();
			$parametros['alumno'] = ['alumno' => $alumno];
		}
		else {
			$pers_param = ['persona' => $parametros['persona']];
			$pers_prop_param = ['persona' => $parametros['persona'], 'propuesta' => $parametros['propuesta']];
			
			$propuestas = catalogo::consultar('cursos', 'get_propuestas_academica', $pers_param);
			
			foreach($propuestas as &$propuesta){	
				$propuesta['cursos']= catalogo::consultar('cursos', 'get_cursos_academica', $pers_prop_param);
			}
			
			$parametros['propuesta'] = $propuesta['propuesta'];
			$pers_prop_param = ['persona' => $parametros['persona'], 'propuesta' => $parametros['propuesta']];

			$alumno = catalogo::consultar('alumno', 'get_alumno', $pers_prop_param);
			// $alumno = catalogo::consultar('alumno', 'get_alumno', array('persona' => $parametros['persona'], 'propuesta' => $parametros['propuesta']));
			$parametros['alumno'] = $alumno;
		}

		$this->facturacion = new nsg_g3w3pre_facturacion(true);
		$datos_matriculas = $this->facturacion->get_datosFacturacion
		(
			[
				'datos_preinscripciones' => $this->data['preinscripciones'],
				'alumno' => $parametros['alumno']
			]
		);
		$this->data['propuestas_sin_arancel_inscripcion'] = $this->facturacion->get_propuestas_sin_arancel_insc();
		$preinscripciones = array_column($this->data['preinscripciones'], 'preinscripcion');
		$this->add_var_js('datos_preinscripciones', $preinscripciones);
		$this->data['matriculas'] = $datos_matriculas;
		$this->data['mcp_data'] = $this->facturacion->get_mcpData();

		$this->data['url_pdf'] = kernel::vinculador()->crear(kernel::ruteador()->get_id_operacion(), 'generar_pdf');
		$this->data['error'] =  (kernel::sesion()->esta_seteada('mensaje_error')) ? kernel::sesion()->get('mensaje_error') : '';
		$this->data['mensaje'] = (kernel::sesion()->esta_seteada('mensaje_ok')) ? kernel::sesion()->get('mensaje_ok') : '';
	}
}
