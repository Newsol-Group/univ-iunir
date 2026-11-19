<?php
/*
  asistencia a cursos
  Actividad
  Curso
  Docentes
  Fecha Desde
  ...
  Fecha Hasta */

namespace siu\operaciones\solicitudes\nueva_solicitud;

use SIU\Chulupi\interfaz\componentes\forms\FormElementoConfig;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use \siu\extension_kernel\formularios\builder_formulario;
use \siu\extension_kernel\formularios\fabrica_formularios;
use siu\extension_kernel\formularios\guarani_form_elemento;
use \siu\extension_kernel\formularios\guarani_form;
use siu\operaciones\solicitudes\controlador;
use siu\modelo\datos\catalogo;
use siu\operaciones\_comun\util\forms;

class builder_form_filtro_14000020 extends builder_formulario
{
	protected $controlador;

	/**
	 * @return controlador
	 */
	function get_controlador()
	{
		if (!isset($this->controlador)) {
			$this->controlador = kernel::localizador()->instanciar('operaciones\solicitudes\controlador');
		}
		return $this->controlador;
	}

	function get_id_html()
	{
		return 'formulario_filtro';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('solicitudes', 'index', array(0 => '$constancia'));
	}

	protected function generar_definicion(guarani_form $form, fabrica_formularios $fabrica)
	{
		// segun la constancia 
		//
		$form->add_elemento($fabrica->elemento('actividad', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('solicitudes.nueva_solicitud.actividad')),
					FormElementoConfig::filtro => validador::TIPO_ALPHANUM,
					FormElementoConfig::obligatorio => false,
					FormElementoConfig::elemento => array('tipo' => 'text'),
					FormElementoConfig::clase_css => 'filtros_comunes',
					FormElementoConfig::validar_select => false,
		)));
		$form->add_elemento($fabrica->elemento('curso', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('solicitudes.nueva_solicitud.curso')),
					FormElementoConfig::filtro => validador::TIPO_ALPHANUM,
					FormElementoConfig::obligatorio => false,
					FormElementoConfig::elemento => array('tipo' => 'text'),
					FormElementoConfig::clase_css => 'filtros_comunes',
					FormElementoConfig::validar_select => false,
		)));
		$form->add_elemento($fabrica->elemento('docentes', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('solicitudes.nueva_solicitud.docentes')),
					FormElementoConfig::filtro => validador::TIPO_ALPHANUM,
					FormElementoConfig::obligatorio => false,
					FormElementoConfig::elemento => array('tipo' => 'text'),
					FormElementoConfig::clase_css => 'filtros_comunes',
					FormElementoConfig::validar_select => false,
		)));
		$form->add_elemento($fabrica->elemento_fecha('fecha_desde', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans("solicitudes.nueva_solicitud.fecha_desde")),
					FormElementoConfig::filtro => validador::TIPO_DATE,
					FormElementoConfig::obligatorio => false,
					FormElementoConfig::filtro_params => array('format' => 'd/m/Y', 'allowempty' => false),
					FormElementoConfig::clase_css => 'date input-small',
					FormElementoConfig::placeholder => kernel::traductor()->trans('solicitudes.nueva_solicitud.filtro_seleccione'),
		)));

		$form->add_elemento($fabrica->elemento_fecha('fecha_hasta', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans("solicitudes.nueva_solicitud.fecha_hasta")),
					FormElementoConfig::filtro => validador::TIPO_DATE,
					FormElementoConfig::obligatorio => false,
					FormElementoConfig::filtro_params => array('format' => 'd/m/Y', 'allowempty' => false),
					FormElementoConfig::clase_css => 'date input-small',
					FormElementoConfig::placeholder => kernel::traductor()->trans('solicitudes.nueva_solicitud.filtro_seleccione'),
		)));
		$form->add_accion($fabrica->accion_boton_submit('boton_buscar_14000020', ucfirst(kernel::traductor()->trans('asistencias.planilla.buscar'))));
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
						'actividad' => array('span' => 5),
						'curso' => array('span' => 5)),
					array(
						'docentes' => array('span' => 5)),
					array(
						'fecha_desde' => array('span' => 5),
						'fecha_hasta' => array('span' => 5),
					),
				)
			),
		);
	}

}