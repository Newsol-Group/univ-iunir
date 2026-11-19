<?php
namespace siu\operaciones\ficha_alumno\bitacora;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use siu\operaciones\datos_censales\_comun\form_edicion;
use SIU\Chulupi\util\validador;
use siu\guarani;
use siu\modelo\datos\catalogo;

class form_bitacora extends form_edicion
{
	protected $modelo;
	protected $controlador;
	
	function __construct($controlador)
	{
		$this->modelo = guarani::ficha_alumno();
		$this->controlador = $controlador;
	}
	
	function get_id_html() 
	{
		return 'form_bitacora';
	}
	
	function get_action() 
	{
		return $this->controlador->get_url_item_menu('bitacora', 'bitacora_guardar');
	}
	
	protected function generar_definicion() 
	{
		$disabled = !$this->controlador->puede_editar_bitacora();
		
		$this->set_campo(catalogo::id, array(
			form::label			=> 'id',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'hidden'),
			form::disabled		=> $disabled,
			form::valor_default => ''
		));
		
		$this->set_campo('fecha', array(
			form::label			=> 'ficha_alumno.bitacora.fecha',
			form::filtro		=> validador::TIPO_DATE,
			form::filtro_params => array('format' => 'd/m/Y', 
										 'allowempty' => true),
			form::clase_css		=> 'date input-small',
			form::obligatorio	=> true,
			form::elemento		=> array('tipo' => 'text'),
			form::disabled		=> $disabled,
			form::valor_default => date("d/m/Y", time())
		));
		
		$this->set_campo('id_seguimiento', array(
			form::label			=> 'ficha_alumno.bitacora.seguimiento',
			form::obligatorio	=> false,
			form::filtro		=> validador::TIPO_TEXTO,
			form::elemento		=> array('tipo' => 'select'),
			form::validar_select => true,
			form::obligatorio	=> true,
			form::disabled		=> $disabled,
			form::multi_options => $this->modelo->opciones__seguimientos()
		));
		
		$this->set_campo('visibilidad', array(
			form::label			=> 'ficha_alumno.bitacora.visibilidad',
			form::obligatorio	=> false,
			form::filtro		=> validador::TIPO_TEXTO,
			form::elemento		=> array('tipo' => 'select'),
			form::validar_select => true,
			form::obligatorio	=> false,
			form::clase_css		=> 'combo_small',
			form::disabled		=> $disabled,
			form::multi_options => $this->modelo->opciones__visibilidad()
		));
		
		$this->set_campo('titulo', array(
			form::label			=> 'ficha_alumno.bitacora.titulo',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> true,
			form::elemento		=> array('tipo' => 'text'),
			form::clase_css		=> 'titulo',
			form::disabled		=> $disabled,
			form::largo			=> 100,
		));
		
		$this->set_campo('observaciones', array(
			form::label			=> 'ficha_alumno.bitacora.observaciones',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> true,
			form::elemento		=> array('tipo' => 'textarea'),
			form::rows			=> 3,
			form::disabled		=> $disabled,
			form::clase_css		=> 'observaciones'
		));
	}
}
?>