<?php

namespace siu\operaciones\censo\propuesta;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use siu\operaciones\_comun\form;
use siu\modelo\util;
use siu\modelo\g3\g3;

class form_propuesta extends form
{
	function get_id_html()
	{
		return 'form_propuesta';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('censo', 'guardar_propuesta', array('propuesta'));
	}

	protected function generar_definicion()
	{
		$this->set_campo('responsable_academica', array(
			form::label => 'responsable_academica',
			form::obligatorio => true,
			form::filtro => validador::TIPO_TEXTO,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => util::opciones()->get_responsables_academicas(),
			form::clase_css => 'js-unidad_academica',
			form::largo => 5
		));

		$this->set_campo('propuesta', array(
			form::label => 'propuesta',
			form::obligatorio => true,
			form::filtro => validador::TIPO_TEXTO,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => false,
			form::multi_options => array(), // Las opciones se cargan por AJAX en cascada
			form::readonly => true,
			form::largo => 5
		));

		$this->set_campo('ubicacion', array(
			form::label => 'ubicacion',
			form::obligatorio => true,
			form::filtro => validador::TIPO_TEXTO,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => false,
			form::multi_options => array(), // Las opciones se cargan por AJAX en cascada
			form::readonly => true,
			form::largo => 5
		));

		$this->set_campo('modalidad', array(
			form::label => 'modalidad',
			form::obligatorio => true,
			form::filtro => validador::TIPO_TEXTO,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => false,
			form::multi_options => array(), // Las opciones se cargan por AJAX en cascada
			form::readonly => true
		));
	}

	function validar()
	{
		$valido = parent::validar();

		// Se validan los datos de los selects
		if ($valido) {
			$ra = $this->get_dato('responsable_academica');
			$propuestas = array_keys(g3::propuestas()->get_propuestas_disponibles_preinscripcion($ra));
			$propuesta = $this->get_dato('propuesta');
			if (!in_array($propuesta, $propuestas)) {
				$valido = false;
			} else {
				$ubicaciones = array_keys(g3::propuestas()->get_ubicaciones_propuesta($propuesta));
				$ubicacion = $this->get_dato('ubicacion');
				if (!in_array($ubicacion, $ubicaciones)) {
					$valido = false;
				} else {
					$modalidades = array_keys(g3::propuestas()->get_modalidades_propuesta($propuesta, $ubicacion));
					$modalidad = $this->get_dato('modalidad');
					if (!in_array($modalidad, $modalidades)) {
						$valido = false;
					}
				}
			}
		}

		return $valido;
	}

}
?>