<?php

namespace nsg01\operaciones\datos_censales\datos_personales;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use SIU\Chulupi\util\validador;
use siu\guarani;
use siu\operaciones\datos_censales\_comun\form_censo;

class form_datos_personales extends \siu\operaciones\datos_censales\datos_personales\form_datos_personales
{
	protected function generar_definicion()
	{
		$this->set_campo('apellido', array(
			form::label => 'datos_censales.apellido',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 60,
			form::readonly => true
		));
		$this->set_campo('nombres', array(
			form::label => 'datos_censales.nombres',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 60,
			form::readonly => true
		));
		//tipo y nro documento

		$this->set_campo('tipo_documento_desc', array(
			form::label => 'datos_censales.tipo_documento',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::validar_select => true,
			form::readonly => true
		));

		$this->set_campo('numero_documento', array(
			form::label => 'datos_censales.numero_documento',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 20,
			form::readonly => true
		));
		//CUIL
		$this->set_campo('cuil', array(
			form::label => 'datos_censales.cuil',
			form::filtro => validador::TIPO_INT,
			form::filtro_params => array('allowempty' => true),
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 20,
			form::readonly => false
		));

		//genero
		$this->set_campo('sexo', array(
			form::label => 'datos_censales.sexo',
			form::obligatorio => false,
			form::filtro => validador::TIPO_TEXTO,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::readonly => true,
			form::multi_options => $this->modelo->opciones__generos(), //opciones::get_generos()
		));
                
		$this->set_campo('identidad_genero', array(
			form::label => 'datos_censales.identidad_genero',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__identidad_genero(),
			form::valor_default => '',
		));//Identidad de Género y Diversidad
		$this->set_campo('identidad_genero_otro', array(
			form::label => 'datos_censales.identidad_genero_otro',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 100
		));
		//---- Situación familiar ----------------------------------------------
		$this->set_campo('estado_civil', array(
			form::label => 'datos_censales.estado_civil',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__estados_civiles(),
			form::valor_default => ''
		));

		$this->set_campo('unido_hecho', array(
			form::label => 'datos_censales.unido_hecho',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('cantidad_hijos', array(
			form::label => 'datos_censales.cantidad_hijos',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__cantidades_hijos(),
		));

		$this->set_campo('cantidad_familia', array(
			form::label => 'datos_censales.cantidad_familia',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__cantidades_familia(),
		));

		$this->set_campo('situacion_padre', array(
			form::label => 'datos_censales.situacion_padre',
			form::obligatorio => false,
			form::filtro => validador::TIPO_ALPHA,
			form::filtro_params => array('allowempty' => true),
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__situacion_padres(),
		));

		$this->set_campo('situacion_madre', array(
			form::label => 'datos_censales.situacion_madre',
			form::obligatorio => false,
			form::filtro => validador::TIPO_ALPHA,
			form::filtro_params => array('allowempty' => true),
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__situacion_padres(),
		));

		//---- Turno preferido -------------------------------------------------
		$this->set_campo('turno_preferido', array(
			form::label => 'datos_censales.turno_preferido',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__turnos(),
			form::valor_default => '',
			form::largo => 20
		));

		//---- Pueblos originarios ---------------------------------------------
		$this->set_campo('pertenece_pueblo_originario', array(
			form::label => 'datos_censales.pertenece_pueblo_originario',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => ['tipo' => 'select'],
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__si_no_no_desea_contestar(),
			form::valor_default => '',
		));

		$this->set_campo('pueblo_originario', array(
			form::label => 'datos_censales.pueblo_originario',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => ['tipo' => 'select'],
			form::validar_select => true,
			form::multi_options => $this->modelo->opciones__pueblos_originarios(),
			form::valor_default => '',
		));

		$this->set_campo('pueblo_originario_otro', array(
			form::label => 'datos_censales.pueblo_originario_otro',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => ['tipo' => 'text'],
			form::largo => 100
		));
	}

     }