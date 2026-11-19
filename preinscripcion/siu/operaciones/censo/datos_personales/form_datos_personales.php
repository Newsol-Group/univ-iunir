<?php

namespace siu\operaciones\censo\datos_personales;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\form;
use SIU\Chulupi\util\validador;
use siu\modelo\util;
use siu\operaciones\_comun\preinscripcion_form;

class form_datos_personales extends preinscripcion_form
{
	function get_id_html()
	{
		return 'form_datos_personales';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('censo', 'guardar', array('datos_personales'));
	}

	protected function generar_definicion()
	{
		//---- Domicilio durante el período de clases --------------------------
		$this->set_campo('calle_per_lect', array(
			form::label => 'calle_per_lect',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 50
		));

		$this->set_campo('numero_per_lect', array(
			form::label => 'numero_per_lect',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 10,
			form::clase_css => 'input-medium',
		));

		$this->set_campo('piso_per_lect', array(
			form::label => 'piso_per_lect',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 3,
			form::clase_css => 'input-medium',
		));

		$this->set_campo('dpto_per_lect', array(
			form::label => 'dpto_per_lect',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 5,
			form::clase_css => 'input-medium',
		));

		$this->set_campo('unidad_per_lect', array(
			form::label => 'unidad_per_lect',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 3,
			form::clase_css => 'input-medium',
		));

		$this->set_campo('pais_per_lect', array(
			form::label => 'pais_per_lect',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_paises(false),
			form::clase_css => 'select-pais'
		));

		$this->set_campo('loc_per_lect', array(
			form::label => 'loc_per_lect',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'hidden'),
			form::valor_default => ''
		));

		$this->set_campo('loc_per_lect_descr', array(
			form::label => 'loc_per_lect',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::clase_css => 'input-localidad',
		));

		$this->set_campo('cp_per_lect', array(
			form::label => 'cp_per_lect',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::readonly => true,
			form::clase_css => 'input-small',
			form::largo => 15
		));

		$this->set_campo('te_per_lect', array(
			form::label => 'te_per_lect',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 18
		));

		$this->set_campo('tipo_res_per_lect', array(
			form::label => 'tipo_res_per_lect',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_tipos_vivienda(),
		));

		$this->set_campo('vive_actual_con', array(
			form::label => 'vive_actual_con',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_vive_con(),
			form::valor_default => '',
			form::clase_css => 'combo_medio'
		));

		$this->set_campo('barrio_per_lec', array(
			form::label => 'barrio_per_lec',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 60
		));

		//---- Domicilio de procedencia ----------------------------------------
		$this->set_campo('calle_proc', array(
			form::label => 'calle_proc',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 50
		));

		$this->set_campo('numero_proc', array(
			form::label => 'numero_proc',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 10,
			form::clase_css => 'input-medium',
		));

		$this->set_campo('piso_proc', array(
			form::label => 'piso_proc',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 3,
			form::clase_css => 'input-medium',
		));

		$this->set_campo('dpto_proc', array(
			form::label => 'dpto_proc',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 5,
			form::clase_css => 'input-medium',
		));

		$this->set_campo('unidad_proc', array(
			form::label => 'unidad_proc',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 3,
			form::clase_css => 'input-medium',
		));

		$this->set_campo('pais_proc', array(
			form::label => 'pais_proc',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_paises(false),
			form::clase_css => 'select-pais'
		));

		$this->set_campo('loc_proc', array(
			form::label => 'loc_proc',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'hidden'),
			form::valor_default => ''
		));

		$this->set_campo('loc_proc_descr', array(
			form::label => 'loc_proc',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::clase_css => 'input-localidad',
		));

		$this->set_campo('cp_proc', array(
			form::label => 'cp_proc',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::readonly => true,
			form::clase_css => 'input-small',
			form::largo => 15
		));

		$this->set_campo('te_proc', array(
			form::label => 'te_proc',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 18
		));

		$this->set_campo('barrio_proc', array(
			form::label => 'barrio_proc',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 60
		));

		//---- Datos de persona allegada ---------------------------------------
		$this->set_campo('apellido_pers_alleg', array(
			form::label => 'apellido_pers_alleg',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 60
		));

		$this->set_campo('nombre_pers_alleg', array(
			form::label => 'nombre_pers_alleg',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 60
		));

		$this->set_campo('tipo_allegado', array(
			form::label => 'tipo_allegado',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_tipos_allegado(),
			form::valor_default => '',
		));

		$this->set_campo('calle_pers_alleg', array(
			form::label => 'calle_pers_alleg',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 50
		));

		$this->set_campo('nro_pers_alleg', array(
			form::label => 'nro_pers_alleg',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 10,
			form::clase_css => 'input-medium',
		));

		$this->set_campo('piso_pers_alleg', array(
			form::label => 'piso_pers_alleg',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 3,
			form::clase_css => 'input-medium',
		));

		$this->set_campo('dpto_pers_alleg', array(
			form::label => 'dpto_pers_alleg',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 5,
			form::clase_css => 'input-medium',
		));

		$this->set_campo('unidad_pers_alleg', array(
			form::label => 'unidad_pers_alleg',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 3,
			form::clase_css => 'input-medium',
		));

		$this->set_campo('pais_pers_alleg', array(
			form::label => 'pais_pers_alleg',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_paises(false),
			form::clase_css => 'select-pais'
		));

		$this->set_campo('loc_pers_alleg', array(
			form::label => 'loc_pers_alleg',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'hidden'),
			form::valor_default => ''
		));

		$this->set_campo('loc_pers_alleg_descr', array(
			form::label => 'loc_pers_alleg',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::readonly => false,
			form::clase_css => 'input-localidad',
		));

		$this->set_campo('cp_pers_alleg', array(
			form::label => 'cp_pers_alleg',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::readonly => true,
			form::clase_css => 'input-small',
			form::largo => 15
		));

		$this->set_campo('te_pers_alleg', array(
			form::label => 'te_pers_alleg',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 18
		));

		$this->set_campo('barrio_alleg', array(
			form::label => 'barrio_alleg',
			form::filtro => validador::TIPO_TEXTO,
			form::obligatorio => false,
			form::elemento => array('tipo' => 'text'),
			form::largo => 60
		));
	}

	function control_group_localidad($campo_localidad, $campo_descr, $campo_pais)
	{
		if ($this->def_visible($campo_localidad)) {
			$label = $this->label($campo_localidad);

			if (($this->get_obligatorio($campo_localidad)) || ($this->def_obligatorio($campo_localidad))) {
				$label = str_replace('class="optional"', 'class="label-obligatorio"', $label);
				$label = str_replace('</label>', ' (*)</label>', $label);
			}

			$elemento_localidad = $this->elemento($campo_localidad);
			$elemento_pais = $this->elemento($campo_pais);
			$elemento_descr = $this->elemento($campo_descr);

			$tiene_error = $this->tiene_error($campo_localidad) || $this->tiene_error($campo_descr);
			$clase_error = ($tiene_error) ? 'error' : '';

			$html = "<div class='control-group $clase_error'>";
			$html .= $label;
			$html .= $elemento_localidad;
			$html .= $elemento_pais;
			$html .= $elemento_descr;
			$html .= $this->ayuda($campo_localidad);
			if ($tiene_error) {
				$html .= "<br/>";
				$html .= "<span class='help-inline'>{$this->error($campo_descr)}</span>";
			}
			$html .= "</div>";

			return $html;
		}
	}

}
?>