<?php

namespace nsg01\operaciones\datos_censales;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use siu\operaciones\datos_censales\_comun\pagelet_con_form_dc;

class controlador extends \siu\operaciones\datos_censales\controlador
{
	function accion__guardar()
	{
		if (!kernel::request()->isPost()) {
			return;
		}

		$formulario = $this->validate_param(
			0,
			'get',
			validador::TIPO_TEXTO,
			array('default' => 'datos_contacto')
		);

		if ($formulario !== 'datos_laborales' || !kernel::persona()->es_perfil_docente()) {
			return parent::accion__guardar();
		}

		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_form_activo($pagelet);
		$form = $pagelet->get_form();

		if ($form->procesar()) {
			$datos = $form->get_datos();
			$cbu = trim((string) $datos['trabajo_tarea_descripcion']);

			if ($cbu !== '' && (!ctype_digit($cbu) || strlen($cbu) !== 22)) {
				$form->agregar_error(
					'trabajo_tarea_descripcion',
					'El CBU debe contener exactamente 22 números.'
				);
				$pagelet->set_estado_info(pagelet_con_form_dc::FORM_ERROR_VALIDACION);
			} else {
				$this->modelo()->evt__guardar($formulario, $datos);
				$pagelet->set_estado_info(pagelet_con_form_dc::FORM_GUARDADO);
			}
		} else {
			$pagelet->set_estado_info(pagelet_con_form_dc::FORM_ERROR_VALIDACION);
		}

		kernel::renderer()->add($pagelet);
	}
}
