<?php

namespace siu\operaciones\censo\datos_estudios;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use SIU\Chulupi\util\validador;
use siu\modelo\util;
use siu\operaciones\_comun\preinscripcion_form;
use siu\modelo\comunes;

class form_datos_estudios extends preinscripcion_form
{
	function get_id_html()
	{
		return 'form_datos_estudios';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('censo', 'guardar', array('datos_estudios'));
	}

	protected function generar_definicion()
	{
		//---- Orientación vocacional ------------------------------------------
		$this->set_campo('orientacion_recibida', array(
			form::label => 'orientacion_recibida',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_orientacion_vocacional(),
			form::valor_default => '',
			form::clase_css => 'combo_medio'
		));		
		
		//---- Datos estudios secundarios / polimodal --------------------------
		$this->set_campo('anio_egreso_sec', array(
			form::label => 'anio_egreso_sec',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_anios(),
			form::valor_default => ''
		));
		
		$this->set_campo('adeuda_materias', array(
			form::label => 'adeuda_materias',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('colegio_secundario', array(
			form::label => 'colegio_secundario',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'hidden'),
			form::valor_default => ''
		));

		$this->set_campo('colegio_secundario_descr', array(
			form::label => 'colegio_secundario',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::readonly => true,
			form::clase_css => 'input_largo',
		));

		$this->set_campo('titulo_secundario', array(
			form::label => 'titulo_secundario',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'hidden'),
			form::valor_default => ''
		));

		$this->set_campo('titulo_secundario_descr', array(
			form::label => 'titulo_secundario',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::readonly => true,
			form::clase_css => 'input_largo',
		));

		//---- Otros estudios superiores ---------------------------------------
		$this->set_campo('alu_otestsup_uni', array(
			form::label => 'alu_otestsup_uni',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 255
		));

		$this->set_campo('alu_otestsup_tipo', array(
			form::label => 'alu_otestsup_tipo',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_tipos_estudios_superiores(),
			form::valor_default => ''
		));

		$this->set_campo('alu_otestsup_carr', array(
			form::label => 'alu_otestsup_carr',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 255
		));

		$this->set_campo('alu_otestsup_esta', array(
			form::label => 'alu_otestsup_esta',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_estados_estudios_sup(),
			form::valor_default => ''
		));

		$this->set_campo('alu_otestsup_area', array(
			form::label => 'alu_otestsup_area',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_areas_estudios_sup(),
			form::valor_default => ''
		));

		$this->set_campo('alu_otestsup_niv', array(
			form::label => 'alu_otestsup_niv',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_niveles_otestsup(),
			form::valor_default => ''
		));

		$this->set_campo('alu_otestsup_egre', array(
			form::label => 'alu_otestsup_egre',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_anios(),
			form::valor_default => ''
		));
		
		//---- Razones de abandono de estudio ----------------------------------
		$this->set_campo('alu_otestsup_razon_abandono', array(
			form::label => 'alu_otestsup_razon_abandono',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_razones_abandono_estudio(),
			form::valor_default => ''
		));
		
		$this->set_campo('alu_otestsup_razon_abandono_otros', array(
			form::label => 'alu_otestsup_razon_abandono_otros',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 200
		));
                
		$this->set_campo('es_mayor_25_sin_secundario', array(
			form::label => 'es_mayor_25_sin_secundario',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => comunes::si,
			form::unchecked_value => comunes::no,
		));
	}
    
    function validar() {
        $form_valido = parent::validar();
        $datos_db = kernel::persona()->datos()->get_datos_formularios_preinsc();
		$datos_form = $this->get_datos();
		
        if (!empty($datos_db['fecha_nacimiento']) && ($datos_form['es_mayor_25_sin_secundario'] == comunes::si)) {
		   
		   $edad = comunes::calcular_edad($datos_db['fecha_nacimiento']);
		   
           if ($edad < 25) {
                $form_valido = false;
                $this->agregar_error('es_mayor_25_sin_secundario', kernel::traductor()->trans('error_es_mayor_25_sin_secundario'));
            }
        }


        return $form_valido;
    }

}