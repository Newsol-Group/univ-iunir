<?php
namespace siu\operaciones\datos_censales\actuacion_profesional;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use siu\operaciones\datos_censales\_comun\form_edicion;
use SIU\Chulupi\util\validador;
use siu\guarani;
use siu\modelo\datos\catalogo;

class form_actuacion_profesional extends form_edicion
{
	protected $modelo;
	
	function __construct()
	{
		$this->modelo = guarani::datos_censales();
	}
	
	function get_id_html() 
	{
		return 'form_actuacion_profesional';
	}
	
	function get_action() 
	{
		return kernel::vinculador()->crear('datos_censales', 'guardar_edicion', array('actuacion_profesional'));
	}
	
	protected function generar_definicion() 
	{
		$this->set_campo(catalogo::id, array(
			form::label			=> 'id',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'hidden'),
			form::valor_default => ''
		));
		
		$this->set_campo('empresa', array(
			form::label			=> 'datos_censales.ap.empresa',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> true,
			form::elemento		=> array('tipo' => 'text'),
			form::clase_css		=> 'js-input',
			form::largo			=> 255
		));
		
		$this->set_campo('cargo', array(
			form::label			=> 'datos_censales.ap.cargo',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> true,
			form::elemento		=> array('tipo' => 'text'),
			form::clase_css		=> 'js-input',
			form::largo			=> 255
		));
		
		$this->set_campo('area', array(
			form::label			=> 'datos_censales.ap.area',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> true,
			form::elemento		=> array('tipo' => 'text'),
			form::clase_css		=> 'js-input',
			form::largo			=> 255
		));
		
		$this->set_campo('trabajo_hora_sem', array(
			form::label			=> 'datos_censales.ap.trabajo_hora_sem',
			form::obligatorio	=> false,
			form::filtro		=> validador::TIPO_TEXTO,
			form::elemento		=> array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__trabajo_hora_sem()
		));
		
		$this->set_campo('fecha_desde', array(
			form::label			=> 'datos_censales.ap.fecha_desde',
			form::filtro		=> validador::TIPO_DATE,
			form::filtro_params => array('format' => 'd/m/Y', 
										 'allowempty' => true),
			form::clase_css		=> 'date input-small',
			form::obligatorio	=> true,
			form::elemento		=> array('tipo' => 'text')
		));
		
		$this->set_campo('fecha_hasta', array(
			form::label			=> 'datos_censales.ap.fecha_hasta',
			form::filtro		=> validador::TIPO_DATE,
			form::filtro_params => array('format' => 'd/m/Y', 
										 'allowempty' => true),
			form::clase_css		=> 'date input-small',
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text')
		));
	}
	
	function validar()
	{
		$form_valido = parent::validar();
		
		$fecha_desde = $this->get_dato('fecha_desde');
		$fecha_hasta = $this->get_dato('fecha_hasta');
		if ((!empty($fecha_desde)) && (!empty($fecha_hasta))) {
			$fecha_desde = convertir_fecha_formato_base($fecha_desde);
			$fecha_hasta = convertir_fecha_formato_base($fecha_hasta);
			if (fecha_es_menor($fecha_hasta, $fecha_desde)) {
				$form_valido = false;
				$msg = kernel::traductor()->trans('datos_censales.error_fechas');
				$this->agregar_error('fecha_hasta', $msg);
			}
		}

		return $form_valido;
	}
}
?>