<?php

namespace siu\operaciones\censo\actuacion_profesional;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use siu\operaciones\_comun\form;
use siu\modelo\util;
use siu\operaciones\_comun\preinscripcion_form;
use siu\modelo\lib\catalogo;

class form_actuacion_profesional extends preinscripcion_form
{
	function get_id_html()
	{
		return 'form_actuacion_profesional';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('censo', 'guardar_actuacion_profesional', array('actuacion_profesional'));
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

		$this->set_campo('antec_empresa', array(
			form::label => 'antec_empresa',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'text'),
			form::largo => 255
		));

		$this->set_campo('cargo', array(
			form::label => 'antec_cargo',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'text'),
			form::largo => 255
		));

		$this->set_campo('area', array(
			form::label => 'antec_area',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => true,
			form::elemento => array('tipo' => 'text'),
			form::largo => 255
		));

		$this->set_campo('hora_sem_trab', array(
			form::label => 'antec_hs_semanales',
			form::obligatorio => false,
			form::filtro => validador::TIPO_TEXTO,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_hora_sem_trab_alum()
		));

		$this->set_campo('fecha_desde', array(
			form::label => 'antec_fecha_desde',
			form::filtro => validador::TIPO_DATE,
			form::filtro_params => array('format' => 'd/m/Y',
				'allowempty' => true),
			form::clase_css => 'date input-small',
			form::obligatorio => true,
			form::elemento => array('tipo' => 'text')
		));

		$this->set_campo('fecha_hasta', array(
			form::label => 'antec_fecha_hasta',
			form::filtro => validador::TIPO_DATE,
			form::filtro_params => array('format' => 'd/m/Y',
				'allowempty' => true),
			form::clase_css => 'date input-small',
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text')
		));
	}

	//--------------------------------------------------------------------------

	function validar()
	{
		$form_valido = parent::validar();

		$fecha_desde = $this->get_dato('fecha_desde');
		$fecha_hasta = $this->get_dato('fecha_hasta');

		if (!empty($fecha_desde)) {
			$fecha_desde = convertir_fecha_formato_base($fecha_desde);
			// Se valida que la fecha no sea anterior a 1900
			$fecha_limite = '1901-01-01';
			if (fecha_es_menor($fecha_desde, $fecha_limite)) {
				$form_valido = false;
				$msg = kernel::traductor()->trans('error_fecha_antigua');
				$this->agregar_error('fecha_desde', $msg);
			}

			// Se valida que la fecha desde sea anterior a la fecha actual
			if ($form_valido) {
				if (!(fecha_es_menor_a_fecha_actual($fecha_desde, true))) {
					$form_valido = false;
					$msg = kernel::traductor()->trans('error_fecha_posterior');
					$this->agregar_error('fecha_desde', $msg);
				}
			}

			// Se valida que la fecha hasta sea anterior a la fecha desde
			if (($form_valido) && (!empty($fecha_hasta))) {
				$fecha_hasta = convertir_fecha_formato_base($fecha_hasta);
				if (fecha_es_menor($fecha_hasta, $fecha_desde)) {
					$form_valido = false;
					$msg = kernel::traductor()->trans('error_fechas');
					$this->agregar_error('fecha_hasta', $msg);
				}
			}
		}

		return $form_valido;
	}

}