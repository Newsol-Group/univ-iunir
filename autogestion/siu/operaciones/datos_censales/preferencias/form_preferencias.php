<?php
namespace siu\operaciones\datos_censales\preferencias;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use SIU\Chulupi\util\validador;
use siu\guarani;
use siu\operaciones\datos_censales\_comun\form_censo;

class form_preferencias extends form_censo
{
	protected $modelo;

	function __construct()
	{
		$this->modelo = guarani::datos_censales();
	}

	function get_id_html()
	{
		return 'form_preferencias';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('datos_censales', 'guardar_preferencias', array('preferencias'));
	}

	protected function generar_definicion()
	{
		//---- Cómo conoció la institución -------------------------------------
		$this->set_campo('como_conocio_institucion', array(
			form::label => 'datos_censales.preferencias.como_conocio_institucion',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 255,
			form::clase_css => 'input-xxlarge',
		));

		//---- Otras inscripciones ---------------------------------------------
		$this->set_campo('insc_otra_institucion', array(
			form::label => 'datos_censales.preferencias.insc_otra_institucion',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'checkbox'),
			form::largo => 1,
			form::checked_value => 'S',
			form::unchecked_value => 'N',
		));

		$this->set_campo('insc_otra_institucion_desc', array(
			form::label => 'datos_censales.preferencias.insc_otra_institucion_desc',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 100,
			form::clase_css => 'input-xxlarge',
		));

		//---- Motivos de elección de la institución educativa -----------------
		$campos_motivos_inst = array('mot_economico', 'mot_prestigio', 'mot_difusion',
			'mot_recomendacion_estudiantes', 'mot_recomendacion_amigos', 'mot_sistema_ingreso',
			'mot_ubicacion', 'mot_otros_i');

		foreach ($campos_motivos_inst as $campo) {
			$this->set_campo($campo, array(
				form::label => 'datos_censales.preferencias.inst.' . $campo,
				form::filtro => validador::TIPO_TEXTO,
				form::obligatorio => false,
				form::elemento => array('tipo' => 'checkbox'),
				form::largo => 1,
				form::checked_value => 'S',
				form::unchecked_value => 'N',
			));
		}

		//---- Motivos de elección de la propuesta -----------------------------
		$campos_motivos_prop = array('mot_vocacion', 'mot_sugerencia_familiar',
			'mot_reconocimiento_social', 'mot_insercion_laboral', 'mot_perfil_profesional',
			'mot_mejora_economica', 'mot_util_sociedad', 'mot_otros_p');

		foreach ($campos_motivos_prop as $campo) {
			$this->set_campo($campo, array(
				form::label => 'datos_censales.preferencias.prop.' . $campo,
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
?>