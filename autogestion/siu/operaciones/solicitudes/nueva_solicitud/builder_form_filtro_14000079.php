<?php
/* asistencia a clases
 * Año Académico
  Período Lectivo
  Actividad (*)
  Comisión
  Fecha Desde */

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

class builder_form_filtro_14000079 extends builder_formulario
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
		$form->add_elemento($fabrica->elemento('anio_academico', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('solicitudes.nueva_solicitud.filtro_anio_academico')),
					FormElementoConfig::filtro => validador::TIPO_ALPHANUM,
					FormElementoConfig::obligatorio => true,
					FormElementoConfig::elemento => array('tipo' => 'select'),
					FormElementoConfig::multi_options => static::get_anios_academicos(),
					FormElementoConfig::validar_select => false,
					FormElementoConfig::clase_css => 'filtros_comunes',
		)));
		$form->add_elemento($fabrica->elemento('periodo_lectivo', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('solicitudes.nueva_solicitud.periodo_lectivo')),
					FormElementoConfig::filtro => validador::TIPO_ALPHANUM,
					FormElementoConfig::obligatorio => false,
					FormElementoConfig::elemento => array('tipo' => 'select'),
					FormElementoConfig::multi_options => static::get_periodo_lectivo(),
					FormElementoConfig::clase_css => 'filtros_comunes',
					FormElementoConfig::validar_select => false,
		)));
		$form->add_elemento($fabrica->elemento('actividad', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('solicitudes.nueva_solicitud.actividad')),
					FormElementoConfig::filtro => validador::TIPO_ALPHANUM,
					FormElementoConfig::obligatorio => true,
					FormElementoConfig::elemento => array('tipo' => 'select'),
					FormElementoConfig::multi_options => static::get_actividades_anio_get_periodo_lectivo(),
					FormElementoConfig::clase_css => 'filtros_comunes',
					FormElementoConfig::validar_select => false,
		)));
		$form->add_elemento($fabrica->elemento('comision', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('solicitudes.nueva_solicitud.comision')),
					FormElementoConfig::filtro => validador::TIPO_ALPHANUM,
					FormElementoConfig::obligatorio => false,
					FormElementoConfig::elemento => array('tipo' => 'select'),
					FormElementoConfig::multi_options => static::get_comision(''),
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

		$form->add_accion($fabrica->accion_boton_submit('boton_buscar_14000079', ucfirst(kernel::traductor()->trans('asistencias.planilla.buscar'))));
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
						'anio_academico' => array('span' => 5),
						'periodo_lectivo' => array('span' => 5)),
					array(
						'actividad' => array('span' => 5),
						'comision' => array('span' => 5)),
					array(
						'fecha_desde' => array('span' => 5),
						'fecha_hasta' => array('span' => 5),
					),
				)
			),
		);
	}

	function get_anios_academicos()
	{
		$datos = catalogo::consultar('unidad_academica', 'anios_academicos');
		return guarani_form_elemento::armar_combo_opciones($datos, 'anio_academico', 'anio_academico', ucfirst(kernel::traductor()->trans('solicitud.filtro_seleccione')), false, false, ucfirst(kernel::traductor()->trans('solicitud.filtro_seleccione')));
	}

	function get_periodo_lectivo()
	{
		$turnos = array(null => "seleccione");
		return $turnos;
	}

	function get_actividades_anio_get_periodo_lectivo()
	{
		$actividades = array(null => "seleccione");
		return $actividades;
	}

	function get_comision()
	{
		$comision = array(null => "seleccione");
		return $comision;
	}

}