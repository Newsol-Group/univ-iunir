<?php

namespace siu\operaciones\perfil_datos;

use siu\operaciones\perfil_datos\config\form_config_pd;
use siu\operaciones\censo\_comun\pagelet_con_edicion;
use SIU\Chulupi\kernel;

class pagelet_config extends pagelet_con_edicion
{
	function ini()
	{
		
	}

	function get_nombre()
	{
		return 'config';
	}

	function get_form($id_html = null)
	{
		if (!isset($this->form)) {
			$this->form = new form_config_pd();
		}
		return $this->form;
	}
	
	function prepare()
	{
		$form = $this->get_form();
		$form->inicializar();
		$datos = $this->controlador->modelo()->get_datos_configuracion();
		$form->set_datos($datos);
		$this->add_form($form);
		$this->data['ras'] = $this->get_ras();
		$this->data['tipos_propuesta'] = $this->get_tipos_propuestas();
		$this->data['propuestas'] = $this->get_propuestas();
		$this->data['ubicaciones'] = $this->get_ubicaciones();

                $this->add_mensaje_js('todos', kernel::traductor()->trans('perfil_datos.todos'));
                $this->add_mensaje_js('ninguno', kernel::traductor()->trans('perfil_datos.ninguno'));
	}

	//--------------------------------------------------------------------------
	
	protected function get_ras()
	{
		$ras = $this->controlador->modelo()->get_responsables_academicas();
		$salida = array();
		foreach ($ras as $ra => $datos) {
			$salida[] = 'ra_' . $ra;
		}
		return $salida;
	}
	
	protected function get_tipos_propuestas()
	{
		$tipos_propuesta = $this->controlador->modelo()->get_tipos_propuestas();
		$salida = array();
		foreach ($tipos_propuesta as $tipo => $datos) {
			$salida[] = 'propuesta_tipo_' . $tipo;
		}
		return $salida;
	}
	
	protected function get_propuestas()
	{
		$propuestas = $this->controlador->modelo()->get_propuestas();
		$salida = array();
		foreach ($propuestas as $propuesta => $datos) {
			$salida[] = 'propuesta_' . $propuesta;
		}
		return $salida;
	}
	
	protected function get_ubicaciones()
	{
		$ubicaciones = $this->controlador->modelo()->get_ubicaciones();
		$salida = array();
		foreach ($ubicaciones as $ubicacion => $datos) {
			$salida[] = 'ubicacion_' . $ubicacion;
		}
		return $salida;
	}

}