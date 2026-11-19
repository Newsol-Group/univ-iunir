<?php
namespace siu\operaciones\censo\datos_adicionales;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use siu\operaciones\_comun\form;
use siu\operaciones\_comun\preinscripcion_form;
use siu\modelo\util;

class form_datos_adicionales extends preinscripcion_form
{
	function get_id_html()
	{
		return 'form_datos_adicionales';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('censo', 'guardar', array('datos_adicionales'));
	}

	protected function generar_definicion()
	{
		$this->set_campo('turno_preferido', array(
			form::label => 'turno_preferido',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_turnos(),
			form::valor_default => '',
			form::largo => 20
		));
		
		//---- Cómo conoció la institución -------------------------------------
		$this->set_campo('como_conocio_institucion', array(
			form::label => 'como_conocio_institucion',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 255,
			form::clase_css => 'input-xxlarge',
		));
		
		//---- Otras inscripciones ---------------------------------------------
		$this->set_campo('insc_otra_inst', array(
			form::label => 'insc_otra_inst',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));
		
		$this->set_campo('insc_otra_inst_descr', array(
			form::label => 'insc_otra_inst_descr',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 100,
			form::clase_css => 'input-xxlarge',
		));
		
		//---- Motivos de elección de la institución educativa -----------------
		$campos_motivos_inst = array('mot_inst_econom', 'mot_inst_prestigio', 
			'mot_inst_difusion', 'mot_inst_rec_estud', 'mot_inst_rec_amigos', 
			'mot_inst_sist_ing', 'mot_inst_ubicacion', 'mot_inst_otros');
		
		foreach ($campos_motivos_inst as $campo) {
			$this->set_campo($campo, array(
				form::label => $campo,
				form::filtro => validador::TIPO_TEXTO,
				form::obligatorio => false,
				form::elemento => array('tipo' => 'checkbox'),
				form::largo => 1,
				form::checked_value => 'S',
				form::unchecked_value => 'N',
			));
		}
		
		//---- Motivos de elección de la propuesta -----------------------------
		$campos_motivos_prop = array('mot_prop_vocacion', 'mot_prop_sug_fam', 
			'mot_prop_rec_social', 'mot_prop_ins_lab', 'mot_prop_perf_prof', 
			'mot_prop_mejora_ec', 'mot_prop_util_soc', 'mot_prop_otros', );
		
		foreach ($campos_motivos_prop as $campo) {
			$this->set_campo($campo, array(
				form::label => $campo,
				form::filtro => validador::TIPO_TEXTO,
				form::obligatorio => false,
				form::elemento => array('tipo' => 'checkbox'),
				form::largo => 1,
				form::checked_value => 'S',
				form::unchecked_value => 'N',
			));
		}
		
	}

}