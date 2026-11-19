<?php
namespace siu\operaciones\resumen_cursada\filtro;

use SIU\Chulupi\interfaz\componentes\forms\FormElementoConfig;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use \siu\extension_kernel\formularios\builder_formulario;
use \siu\extension_kernel\formularios\fabrica_formularios;
use \siu\extension_kernel\formularios\guarani_form;
use siu\modelo\datos\catalogo;
use siu\extension_kernel\formularios\guarani_form_elemento;

class builder_form_filtro extends builder_formulario
{
	function get_id_html() 
	{
		return 'formulario_filtro';
	}

	function get_action() 
	{
		return kernel::vinculador()->crear('resumen_cursada', 'index');
	}

	protected function generar_definicion(guarani_form $form, fabrica_formularios $fabrica) 
	{
		$form->add_elemento($fabrica->elemento('anio_academico', array(
				FormElementoConfig::label			=> ucfirst(kernel::traductor()->trans('resumen_cursada.filtro_anio_academico')),
				FormElementoConfig::filtro			=>  validador::TIPO_ALPHANUM,
				FormElementoConfig::obligatorio	=> true,
				FormElementoConfig::elemento		=> array('tipo' => 'select'),
				FormElementoConfig::multi_options => static::get_anios_academicos(),
				FormElementoConfig::validar_select => false,
				FormElementoConfig::clase_css => 'filtros_comunes',
		)));
		
		$form->add_elemento($fabrica->elemento('periodo', array(
				FormElementoConfig::label			=> ucfirst(kernel::traductor()->trans('resumen_cursada.filtro_periodos')),
				FormElementoConfig::filtro			=>  validador::TIPO_ALPHANUM,
				FormElementoConfig::obligatorio	=> false,
				FormElementoConfig::elemento		=> array('tipo' => 'select'),
				FormElementoConfig::multi_options => static::get_periodos_lectivos(),
				FormElementoConfig::validar_select => false,
				FormElementoConfig::clase_css => 'filtros_cursadas',
		)));
		
		$form->add_accion($fabrica->accion_boton_submit('boton_buscar', ucfirst(kernel::traductor()->trans('resumen_cursada.filtro_buscar'))));
	}

	/**
	 * Por defecto los filtros de guarani se basan en el layout grilla. Se debe configurar en este metodo para poder
	 * instanciar el mismo
	 */
	function get_configuracion_layout_grilla()
	{
		return array(
			array(
				'grupo' => 'filtros',
				'filas' => array(
					array(
						'anio_academico' => array('span' => 6),
						'periodo' => array('span' => 6),
					),
				)
			),
		);
	}
	
	function get_anios_academicos()
	{
		$datos = catalogo::consultar('unidad_academica', 'anios_academicos');
		return guarani_form_elemento::armar_combo_opciones($datos, '__ID__', 'anio_academico', false, false, ucfirst(kernel::traductor()->trans('resumen_cursada.filtro_seleccione')));
	}
	
	function get_periodos_lectivos()
	{
		return array(""=>  kernel::traductor()->trans('resumen_cursada.filtro_seleccione'));
	}

}