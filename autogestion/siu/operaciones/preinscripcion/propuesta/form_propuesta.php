<?php

namespace siu\operaciones\preinscripcion\propuesta;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use SIU\Chulupi\util\form;
use siu\guarani;

class form_propuesta extends form
{
	protected $modelo;
	
	function get_id_html()
	{
		return 'form_propuesta';
	}
	
	function __construct()
	{
		$this->modelo = guarani::preinscripcion();
	}

	function get_action()
	{
		return kernel::vinculador()->crear('preinscripcion', 'guardar_propuesta', array('propuesta'));
	}

	protected function generar_definicion()
	{
		$this->set_campo('responsable_academica', array(
			form::label => 'preinscripcion.responsable_academica',
			form::obligatorio => true,
			form::filtro => validador::TIPO_TEXTO,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => true,
			form::multi_options => $this->modelo->get_responsables_academicas(),
			form::clase_css => 'js-unidad_academica input-xxlarge',
			form::largo => 5
		));

		$this->set_campo('propuesta', array(
			form::label => 'preinscripcion.propuesta_bis',
			form::obligatorio => true,
			form::filtro => validador::TIPO_TEXTO,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => false,
			form::multi_options => array(), // Las opciones se cargan por AJAX en cascada
			form::readonly => true,
			form::clase_css => 'input-xxlarge',
			form::largo => 5
		));

		$this->set_campo('ubicacion', array(
			form::label => 'preinscripcion.ubicacion',
			form::obligatorio => true,
			form::filtro => validador::TIPO_TEXTO,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => false,
			form::multi_options => array(), // Las opciones se cargan por AJAX en cascada
			form::readonly => true,
			form::clase_css => 'input-xxlarge',
			form::largo => 5
		));

		$this->set_campo('modalidad', array(
			form::label => 'preinscripcion.modalidad',
			form::obligatorio => true,
			form::filtro => validador::TIPO_TEXTO,
			form::elemento => array('tipo' => 'select'),
			form::validar_select => false,
			form::multi_options => array(), // Las opciones se cargan por AJAX en cascada
			form::clase_css => 'input-xxlarge',
			form::readonly => true
		));
	}

	function validar()
	{
		$valido = parent::validar();

		// Se validan los datos de los selects
		if ($valido) {
			$ra = $this->get_dato('responsable_academica');
			$propuestas = array_keys($this->modelo->get_propuestas_disponibles_preinscripcion($ra));
			$propuesta = $this->get_dato('propuesta');
			if (!in_array($propuesta, $propuestas)) {
				$valido = false;
			} else {
				$ubicaciones = array_keys($this->modelo->get_ubicaciones_propuesta($propuesta));
				$ubicacion = $this->get_dato('ubicacion');
				if (!in_array($ubicacion, $ubicaciones)) {
					$valido = false;
				} else {
					$modalidades = array_keys($this->modelo->get_modalidades_propuesta($propuesta, $ubicacion));
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