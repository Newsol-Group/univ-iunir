<?php

namespace iunir\operaciones\censo\datos_principales;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use SIU\Chulupi\util\validador;
use siu\operaciones\_comun\preinscripcion_form;
use siu\modelo\opciones;
use siu\modelo\g3\g3;
use siu\modelo\util;
use siu\modelo\comunes;

class form_datos_principales extends \siu\operaciones\censo\datos_principales\form_datos_principales
{
	
	//--------------------------------------------------------------------------
	//---- Definici�n de formulario --------------------------------------------
	//--------------------------------------------------------------------------

	protected function generar_definicion()
	{
		//---- Datos Personales ------------------------------------------------
		$this->set_campo('apellido', array(
			form::label => 'apellido',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'text'),
			form::largo => 60
		));

		$this->set_campo('nombres', array(
			form::label => 'nombres',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'text'),
			form::largo => 60
		));

        $this->set_campo('pais_documento', array(
			form::label => 'pais_documento',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => false,
            form::valor_default => 'pais_documento',
			form::multi_options => util::opciones()->get_paises_nacionalidad(),
		));
                
		$this->set_campo('tipo_documento', array(
			form::label => 'tipo_documento',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => false,
			form::valor_default => 'tipo_documento',
			form::multi_options => util::opciones()->get_tipos_documentos_pais(),
			
		));
                
		$this->set_campo('nro_documento', array(
			form::label => 'nro_documento',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::readonly => false,
		));

		$this->set_campo('nacionalidad_desc', array(
			form::label => 'nacionalidad_desc',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'text'),
			form::readonly => true,
		));
		
		$this->set_campo('nacionalidad', array(
			form::label => '',
			form::filtro => validador::TIPO_INT,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'hidden'),
			form::readonly => true,
		));

		$this->set_campo('e_mail', array(
			form::label => 'e_mail',
			form::filtro => validador::TIPO_MAIL,
			form::filtro_params => array('allowempty' => true),
			form::obligatorio => true,
			form::elemento => array('tipo' => 'text'),
			form::readonly => true,
			form::largo => 100
		));

		$this->set_campo('numero_cuil', array(
			form::label => 'numero_cuil',
			form::filtro => validador::TIPO_INT,
			form::filtro_params => array('allowempty' => true),
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 15
		));

		$this->set_campo('sexo', array(
			form::label => 'sexo',
			form::obligatorio => false,
			form::filtro => validador::TIPO_TEXTO,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_generos(),
		));
        
        $this->set_campo('identidad_genero', array(
			form::label => 'identidad_genero',
			form::obligatorio => false,
			form::filtro => validador::TIPO_TEXTO,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_identidad_genero(),
		));
                
        $this->set_campo('identidad_genero_otro', array(
			form::label => 'identidad_genero_otro',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 100
		));
			   
		$this->set_campo('celular_caracteristica', array(
			form::label => 'celular_caracteristica',
			form::filtro => validador::TIPO_INT,
			form::filtro_params => array('allowempty' => true),
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 4,
			form::clase_css => 'input-mini',
		));

		$this->set_campo('celular_valor', array(
			form::label => 'celular_valor',
			form::filtro => validador::TIPO_INT,
			form::filtro_params => array('allowempty' => true),
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 8,
			form::clase_css => 'input-small',
		));

		//---- Datos de nacimiento ---------------------------------------------
		$this->set_campo('fecha_nacimiento', array(
			form::label => 'fecha_nacimiento',
			form::filtro => validador::TIPO_DATE,
			form::filtro_params => array('format' => 'd/m/Y',
				'allowempty' => true),
			form::clase_css => 'date input-small',
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text')
		));
		
		$this->set_campo('es_mayor_25_sin_secundario', array(
			form::label => '',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'hidden'),
			form::readonly => true,
		));

		$this->set_campo('loc_nacimiento', array(
			form::label => 'loc_nacimiento',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'hidden'),
			form::valor_default => ''
		));

		$this->set_campo('loc_nac_descr', array(
			form::label => 'loc_nacimiento',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::clase_css => 'input-localidad',
		));

		$this->set_campo('pais_nacimiento', array(
			form::label => 'pais_nacimiento',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_paises(false),
			form::clase_css => 'select-pais'
		));

		//---- C�dula de identidad ---------------------------------------------
		$this->set_campo('ent_emisora_ci', array(
			form::label => 'ent_emisora_ci',
			form::filtro => validador::TIPO_TEXTO,
			form::filtro_params => array('allowempty' => true),
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_entidades_emisoras_ci(),
			form::valor_default => '',
		));

		$this->set_campo('nro_ci', array(
			form::label => 'nro_ci',
			form::filtro => validador::TIPO_ALPHANUM,
			form::filtro_params => array('allowempty' => true),
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 15
		));

		//---- Pasaporte -------------------------------------------------------
		$this->set_campo('pais_emisor_pasap', array(
			form::label => 'pais_emisor_pasap',
			form::filtro => validador::TIPO_TEXTO,
			form::filtro_params => array('allowempty' => true),
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_paises(),
			form::valor_default => '',
		));

		$this->set_campo('nro_pasap', array(
			form::label => 'nro_pasap',
			form::filtro => validador::TIPO_ALPHANUM,
			form::filtro_params => array('allowempty' => true),
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 15
		));

		//---- Visa ------------------------------------------------------------
		$this->set_campo('tipo_visa', array(
			form::label => 'tipo_visa',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_tipos_visa(),
			form::valor_default => '',
			form::largo => 20
		));

		$this->set_campo('otorgamiento_visa', array(
			form::label => 'otorgamiento_visa',
			form::filtro => validador::TIPO_DATE,
			form::filtro_params => array(
				'format' => 'd/m/Y',
				'allowempty' => true
			),
			form::clase_css => 'date input-small',
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text')
		));

		$this->set_campo('vencimiento_visa', array(
			form::label => 'vencimiento_visa',
			form::filtro => validador::TIPO_DATE,
			form::filtro_params => array(
				'format' => 'd/m/Y',
				'allowempty' => true
			),
			form::clase_css => 'date input-small',
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text')
		));

		//---- Residencia ------------------------------------------------------
		$this->set_campo('residencia', array(
			form::label => 'residencia',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_residencia(),
			form::valor_default => '',
		));

		$this->set_campo('fecha_respre_otorg', array(
			form::label => 'fecha_respre_otorg',
			form::filtro => validador::TIPO_DATE,
			form::filtro_params => array(
				'format' => 'd/m/Y',
				'allowempty' => true
			),
			form::clase_css => 'date-residencia-otorg input-small',
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text')
		));

		$this->set_campo('fecha_respre_venc', array(
			form::label => 'fecha_respre_venc',
			form::filtro => validador::TIPO_DATE,
			form::filtro_params => array(
				'format' => 'd/m/Y',
				'allowempty' => true
			),
			form::clase_css => 'date-residencia-venc input-small',
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text')
		));

		//---- Pueblos originarios ---------------------------------------------
		$this->set_campo('pertenece_pueblo_originario', array(
			form::label => 'pertenece_pueblo_originario',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_si_no_no_desea_contestar(),
			form::valor_default => '',
		));

		$this->set_campo('pueblo_originario', array(
			form::label => 'pueblo_originario',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_pueblos_originarios(),
			form::valor_default => '',
		));

		$this->set_campo('pueblo_originario_otro', array(
			form::label => 'pueblo_originario_otro',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 100
		));
        
	}

}