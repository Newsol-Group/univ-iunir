<?php

namespace siu\operaciones\perfil_datos\config;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use SIU\Chulupi\util\form as form_base;
use siu\operaciones\_comun\form;
use siu\modelo\g3\g3;

class form_config_pd extends form_base
{
	function get_id_html()
	{
		return 'form_config_pd';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('perfil_datos', 'guardar', array('config'));
	}
	
	function inicializar($habilitado = true)
	{
		$this->generar_definicion(!$habilitado);
		$this->init_zend_form();
	}

	protected function generar_definicion()
	{
		$this->generar_definicion_ras();
		$this->generar_definicion_tipos_propuesta();
		$this->generar_definicion_propuestas();
		$this->generar_definicion_ubicaciones();
	}
	
	function generar_definicion_ras()
	{
		$ras = g3::responsables_academicas()->get_opciones_sin_perfil_datos();
		
		foreach ($ras as $ra => $nombre) {
			$this->set_campo('ra_' . $ra, array(
				form::label => $nombre,
				form::filtro => validador::TIPO_TEXTO,
				form::obligatorio => false,
				form::elemento => array('tipo' => 'checkbox'),
				form::largo => 1,
				form::checked_value => 1,
				form::unchecked_value => 0,
			));
		}
	}
	
	function generar_definicion_tipos_propuesta()
	{
		$tipos_propuesta = g3::propuestas()->get_tipos_propuesta_sin_perfil_datos();
		
		foreach ($tipos_propuesta as $tipo => $nombre) {
			$this->set_campo('propuesta_tipo_' . $tipo, array(
				form::label => $nombre,
				form::filtro => validador::TIPO_TEXTO,
				form::obligatorio => false,
				form::elemento => array('tipo' => 'checkbox'),
				form::largo => 1,
				form::checked_value => 1,
				form::unchecked_value => 0,
			));
		}
	}
	
	function generar_definicion_propuestas()
	{
		$propuestas = g3::propuestas()->get_propuestas_sin_perfil_datos();
		
		foreach ($propuestas as $propuesta => $nombre) {
			$this->set_campo('propuesta_' . $propuesta, array(
				form::label => $nombre,
				form::filtro => validador::TIPO_TEXTO,
				form::obligatorio => false,
				form::elemento => array('tipo' => 'checkbox'),
				form::largo => 1,
				form::checked_value => 1,
				form::unchecked_value => 0,
			));
		}
	}
	
	function generar_definicion_ubicaciones()
	{
		$ubicaciones = g3::entidad('ubicaciones')->get_opciones(null, false);
		
		foreach ($ubicaciones as $ubicacion => $nombre) {
			$this->set_campo('ubicacion_' . $ubicacion, array(
				form::label => $nombre,
				form::filtro => validador::TIPO_TEXTO,
				form::obligatorio => false,
				form::elemento => array('tipo' => 'checkbox'),
				form::largo => 1,
				form::checked_value => 1,
				form::unchecked_value => 0,
			));
		}
	}

}
?>