<?php

namespace siu\operaciones\censo\formacion_acad;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use SIU\Chulupi\util\validador;
use siu\modelo\util;
use siu\modelo\lib\catalogo;
use siu\operaciones\_comun\preinscripcion_form;

class form_formacion_acad extends preinscripcion_form
{
	function get_id_html()
	{
		return 'form_formacion_acad';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('censo', 'guardar_formacion_acad', array('formacion_acad'));
	}

	protected function generar_definicion()
	{
		$this->set_campo(catalogo::id, array(
			form::label => 'id',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'hidden'),
			form::valor_default => ''
		));

		$this->set_campo('titulo_sup_prein', array(
			form::label => 'antec_fa_titulo_sup_prein',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'text'),
			form::largo => 255
		));

		$this->set_campo('institucion_prein', array(
			form::label => 'antec_fa_institucion_prein',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'text'),
			form::largo => 255
		));

		$this->set_campo('fecha_ingreso', array(
			form::label => 'antec_fa_fecha_ingreso',
			form::filtro => validador::TIPO_DATE,
			form::filtro_params => array('format' => 'd/m/Y',
				'allowempty' => true),
			form::clase_css => 'date input-small',
			form::obligatorio => true,
			form::elemento => array('tipo' => 'text')
		));

		$this->set_campo('fecha_egreso', array(
			form::label => 'antec_fa_fecha_egreso',
			form::filtro => validador::TIPO_DATE,
			form::filtro_params => array('format' => 'd/m/Y',
				'allowempty' => true),
			form::clase_css => 'date input-small',
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text')
		));

		$this->set_campo('titulo_tesis', array(
			form::label => 'antec_fa_titulo_tesis',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 255
		));

		$this->set_campo('director_tesis', array(
			form::label => 'antec_fa_director_tesis',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 255
		));

		$this->set_campo('categoria_coneau', array(
			form::label => 'antec_fa_categoria_coneau',
			form::obligatorio => false,
			form::filtro => validador::TIPO_TEXTO,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_categorias_coneau()
		));

		$this->set_campo('duracion_teorica', array(
			form::label => 'antec_fa_duracion_teorica',
			form::filtro => validador::TIPO_INT,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::filtro_params => array('allowempty' => true),
			form::largo => 2
		));

		$this->set_campo('nivel_estudio', array(
			form::label => 'antec_fa_nivel_estudio',
			form::obligatorio => true,
			form::filtro => validador::TIPO_TEXTO,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_niveles_estudio_fa()
		));

		$this->set_campo('estado', array(
			form::label => 'antec_fa_estado',
			form::obligatorio => false,
			form::filtro => validador::TIPO_TEXTO,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_estados_antecedente_fa()
		));
	}

	//--------------------------------------------------------------------------

	function validar()
	{
		$form_valido = parent::validar();

		$fecha_ingreso = $this->get_dato('fecha_ingreso');
		$fecha_egreso = $this->get_dato('fecha_egreso');

		if (!empty($fecha_ingreso)) {
			$fecha_ingreso = convertir_fecha_formato_base($fecha_ingreso);
			// Se valida que la fecha no sea anterior a 1900
			$fecha_limite = '1901-01-01';
			if (fecha_es_menor($fecha_ingreso, $fecha_limite)) {
				$form_valido = false;
				$msg = kernel::traductor()->trans('error_fecha_antigua');
				$this->agregar_error('fecha_ingreso', $msg);
			}

			// Se valida que la fecha de ingreso sea anterior a la fecha actual
			if ($form_valido) {
				if (!(fecha_es_menor_a_fecha_actual($fecha_ingreso, true))) {
					$form_valido = false;
					$msg = kernel::traductor()->trans('error_fecha_posterior');
					$this->agregar_error('fecha_ingreso', $msg);
				}
			}

			// Se valida que la fecha de egreso sea anterior a la fecha de ingreso
			if (($form_valido) && (!empty($fecha_egreso))) {
				$fecha_egreso = convertir_fecha_formato_base($fecha_egreso);
				if (fecha_es_menor($fecha_egreso, $fecha_ingreso)) {
					$form_valido = false;
					$msg = kernel::traductor()->trans('error_fecha_egreso_posterior');
					$this->agregar_error('fecha_egreso', $msg);
				}
			}
		}

		return $form_valido;
	}

}
?>