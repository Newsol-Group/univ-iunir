<?php

namespace siu\operaciones\admin_derechos_comision\filtro;

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


class builder_filtro_comisiones extends builder_filtro
{


	function get_id_html()
	{
		return 'filtder';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('admin_derechos_comision', 'filtrar_comisiones');
	}


	protected function generar_definicion(guarani_form $form, fabrica_formularios $fabrica)
	{

		$form->add_elemento(
			$fabrica->elemento_autocompletar('actividad', array(
					//FormElementoConfig::group			=> 'datos_acta',
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('admin_derechos_comision.filtro_actividad')),
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


		$elemento = $form->add_elemento($fabrica->elemento('docente', array(
			//FormElementoConfig::group			=> 'datos_acta',
			FormElementoConfig::label => ucfirst(kernel::traductor()->trans('admin_derechos_comision.filtro_docente')),
			FormElementoConfig::filtro => validador::TIPO_INT,
			FormElementoConfig::obligatorio => false,
			FormElementoConfig::elemento => array('tipo' => 'text'),
			FormElementoConfig::validar_select => true,
			FormElementoConfig::clase_css => '',
			FormElementoConfig::valor_default => '', //static::get_anio_academico_actual(),
		)));
		$fabrica->elemento_decorar_boton_texto($elemento, 'id-btn', 'Texto');

		$form->add_elemento($fabrica->elemento_botonera_dias('dias', array(
			//FormElementoConfig::group			=> 'datos_acta',
			FormElementoConfig::label => "Día:", //ucfirst(kernel::traductor()->trans('admin_derechos_comision.filtro_docente')),
			FormElementoConfig::obligatorio => false,
		)));


		$form->add_elemento($fabrica->elemento_botonera_turnos('turnos', array(
			//FormElementoConfig::group			=> 'datos_acta',
			FormElementoConfig::label => "Turno:", //ucfirst(kernel::traductor()->trans('admin_derechos_comision.filtro_docente')),
			FormElementoConfig::obligatorio => false,

		)));

		//$form->add_accion($fabrica->accion_boton_submit('accion', 'Filtrar'));

	}


	function get_configuracion_layout_grilla()
	{
		//$submit = new accion_su
		return array(
			array(
				'grupo' => '',
				//'titulo' => 'titulo grupo',
				'filas' => array(
					array('actividad' => array('span' => 12)),
//					array('actividad' => array('span'=> 6 ), 'docente' => array('span' =>5 )),

					//array('dias'   => array( 'span' => 6 ), 'turnos' => array( 'span' => 4)),
				)

			),
		);
	}
}

?>