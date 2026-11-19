<?php
namespace nsg01\operaciones\datos_censales\datos_laborales;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use SIU\Chulupi\util\validador;
use siu\guarani;
use siu\operaciones\datos_censales\_comun\form_censo;

class form_datos_laborales extends \siu\operaciones\datos_censales\datos_laborales\form_datos_laborales
{	
	protected function generar_definicion() 
	{
		//---- Datos Personales ------------------------------------------------
		
		$this->set_campo('trabajo_existe', array(
			form::label			=> 'datos_censales.trabajo_existe',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__trabajo_existe(),
			form::clase_css		=> 'select-largo',
			form::valor_default => ''
		));
		
		$this->set_campo('trabajo_hace', array(
			form::label			=> 'datos_censales.trabajo_hace',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__trabajo_hace(),
			form::clase_css		=> 'select-largo',
			form::valor_default => ''
		));
		
		if (kernel::persona()->es_perfil_docente()) {
		$this->set_campo('trabajo_ocupacion', array(
			form::label			=> 'Condición de IVA',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__trabajo_ocupacion(),
			form::clase_css		=> 'select-largo',
			form::valor_default => ''
		));
		}
		else {
		$this->set_campo('trabajo_ocupacion', array(
			form::label			=> 'datos_censales.trabajo_ocupacion',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__trabajo_ocupacion(),
			form::clase_css		=> 'select-largo',
			form::valor_default => ''
		));
		}
		
		$this->set_campo('trabajo_hora_sem', array(
			form::label			=> 'datos_censales.trabajo_hora_sem',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__trabajo_hora_sem(),
			form::valor_default => ''
		));
		
		$this->set_campo('trabajo_carrera', array(
			form::label			=> 'datos_censales.trabajo_carrera',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__trabajo_carrera(),
			form::valor_default => ''
		));
		
		if (kernel::persona()->es_perfil_docente()) {
		$this->set_campo('trabajo_tarea_descripcion', array(
			form::label			=> 'CBU',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::largo			=> 22
		));
		}
		else {
		$this->set_campo('trabajo_tarea_descripcion', array(
			form::label			=> 'datos_censales.trabajo_tarea_descripcion',
			form::filtro		=> validador::TIPO_TEXTO,
			form::obligatorio	=> false,
			form::elemento		=> array('tipo' => 'text'),
			form::clase_css		=> 'input-largo',
			form::largo			=> 255
		));
		}

		
		//---- Datos adicionales -----------------------------------------------
		
		$this->set_campo('trabajo_familiar', array(
			form::label				=> 'datos_censales.trabajo_familiar',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('trabajo_con_descuentos_jubilatorios', array(
			form::label				=> 'datos_censales.trabajo_con_descuentos_jubilatorios',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
		
		$this->set_campo('trabajo_recibe_pago', array(
			form::label				=> 'datos_censales.trabajo_recibe_pago',
			form::filtro			=> validador::TIPO_TEXTO,
			form::obligatorio		=> false,
			form::elemento			=> array('tipo' => 'checkbox'),
			form::largo				=> 1,
			form::checked_value		=> 'S',
			form::unchecked_value	=> 'N',
		));
	}
}
?>