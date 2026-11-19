<?php

namespace siu\operaciones\censo;

use SIU\Chulupi\kernel;
use siu\operaciones\censo\_comun\pagelet_con_edicion;

class pagelet_turno_presentacion extends pagelet_con_edicion
{
	protected $mensaje_error;

	function get_nombre()
	{
		return 'turno_presentacion';
	}

	function get_form($id_html = null)
	{
		if (!isset($this->form)) {
			$this->form = kernel::localizador()->instanciar('operaciones\censo\turno_presentacion\form_turno');
		}
		$this->form->set_controlador($this->controlador);
		return $this->form;
	}

	function get_js_files()
	{
		$files = parent::get_js_files();
		$files[] = kernel::vinculador()->vinculo_recurso("js/jqueryui.extra.js");
		return $files;
	}

	function get_accion_modificar_turno()
	{
		return kernel::vinculador()->crear('censo', 'modificar_turno', array('turno_presentacion'));
	}

	function set_mensaje_error($mensaje)
	{
		$this->mensaje_error = $mensaje;
	}

	function get_listado_turnos()
	{
		$datos = kernel::persona()->datos()->get_listado_turnos();
		foreach ($datos as $key => $dato) {
			$datos[$key]['LINK_DEVOLVER'] = kernel::vinculador()->crear('censo', 'devolver_turno', array($dato['responsable_academica']));
			$datos[$key]['LINK_SELECCIONAR'] = kernel::vinculador()->crear('censo', 'seleccionar_turno', array($dato['responsable_academica']));
		}
		return $datos;
	}

	function get_accion_volver()
	{
		return kernel::vinculador()->crear('censo', 'volver_seleccionar_turno');
	}

	function prepare()
	{
		if ($this->pantalla == self::ESTADO_EDICION) {
			// Selección de turno 
			$form = $this->get_form();
			$form->activar_csrf($this->generar_csrf());
			$this->add_form($form);

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
			}

			$this->add_var_js('fecha_desde', $this->controlador->get_fecha_minima_turno());
			$this->add_var_js('fecha_hasta', $this->controlador->get_fecha_maxima_turno());
			$this->add_var_js('fechas_disponibles', $this->controlador->get_fechas_turnos_disponibles());
			$this->add_var_js('url_franjas', kernel::vinculador()->crear('censo', 'cascada_franjas_turnos'));
			$this->add_var_js('ra', $this->controlador->get_ra_turno());
		} else {
			// Listado de turnos por RA
			$this->data['turnos'] = $this->get_listado_turnos();
			$this->add_var_js('msj_confirmar_baja', kernel::traductor()->trans('turno.msj_confirmar_baja'));
		}

		$this->data['modo'] = $this->pantalla;
		$this->add_var_js('msj_confirmar_eliminar', kernel::traductor()->trans('msj_confirmar_eliminar'));
	}

}
?>