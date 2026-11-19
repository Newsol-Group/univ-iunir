<?php

namespace siu\operaciones\admin_derechos_mesa\filtro;

use SIU\Chulupi\interfaz\componentes\forms\FormElementoConfig;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use siu\extension_kernel\formularios\builder_filtro;
use siu\extension_kernel\formularios\fabrica_formularios;
use siu\modelo\datos\catalogo;
use siu\extension_kernel\formularios\elementos;
use siu\extension_kernel\formularios\elementos\guarani_elemento_autocomplete;
use siu\extension_kernel\formularios\elementos\guarani_elemento_botonera;
use siu\extension_kernel\formularios\guarani_form;
use siu\extension_kernel\formularios\guarani_form_elemento;


class builder_filtro_mesas extends builder_filtro
{


	function get_id_html()
	{
		return 'filtder';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('admin_derechos_mesa', 'filtrar_mesas');
	}


	protected function generar_definicion(guarani_form $form, fabrica_formularios $fabrica)
	{

		$form->add_elemento(
			$fabrica->elemento_autocompletar('actividad', array(
					//FormElementoConfig::group			=> 'datos_acta',
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('admin_derechos_mesa.filtro_actividad')),
					FormElementoConfig::obligatorio => true,
					FormElementoConfig::validar_select => true,
					FormElementoConfig::valor_default => '',
					FormElementoConfig::clase_css => 'span12',

				),
				function () {
					return "";
				},
				kernel::vinculador()->crear(kernel::ruteador()->get_id_operacion(), 'buscar_actividades')
			)
		);
	}

	function get_configuracion_layout_grilla()
	{
		return array(
			array(
				'grupo' => '',
				'filas' => array(
					array('actividad' => array('span' => 12)),
				)
			),
		);
	}
}

?>