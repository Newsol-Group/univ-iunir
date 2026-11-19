<?php
namespace siu\operaciones\horarios_cursadas\filtro;

use SIU\Chulupi\interfaz\componentes\forms\FormElementoConfig;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use \siu\extension_kernel\formularios\builder_formulario;
use \siu\extension_kernel\formularios\fabrica_formularios;
use \siu\extension_kernel\formularios\guarani_form;
use siu\modelo\datos\catalogo;
use siu\extension_kernel\formularios\guarani_form_elemento;
use siu\operaciones\horarios_cursadas\controlador;

class builder_form_filtro extends builder_formulario
{
	function get_id_html()
	{
		return 'formulario_filtro';
	}

	function get_action()
	{
		// se posteará a la acción index del controlador
		return kernel::vinculador()->crear('horarios_cursadas', 'index');
	}

	protected function generar_definicion(guarani_form $form, fabrica_formularios $fabrica)
	{

		$form->add_elemento($fabrica->elemento('periodo', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('horarios_cursadas.filtro_periodos')),
					FormElementoConfig::filtro => validador::TIPO_ALPHANUM,
					FormElementoConfig::obligatorio => true,
					FormElementoConfig::elemento => array('tipo' => 'select'),
					FormElementoConfig::multi_options => static::get_periodos_lectivos(),
					FormElementoConfig::validar_select => false,
					FormElementoConfig::clase_css => 'filtros_comunes',
		)));

		$form->add_elemento($fabrica->elemento('carrera', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('horarios_cursadas.filtro_carrera')),
					FormElementoConfig::filtro => validador::TIPO_ALPHANUM,
					FormElementoConfig::obligatorio => true,
					FormElementoConfig::elemento => array('tipo' => 'select'),
					FormElementoConfig::multi_options => static::get_carreras(),
					FormElementoConfig::validar_select => false,
					FormElementoConfig::clase_css => 'filtros_comunes',
		)));

		$form->add_elemento($fabrica->elemento('anio_cursada', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('horarios_cursadas.filtro_anio_cursada')),
					FormElementoConfig::filtro => validador::TIPO_ALPHANUM,
					FormElementoConfig::obligatorio => false,
					FormElementoConfig::elemento => array('tipo' => 'select'),
					FormElementoConfig::multi_options => static::get_anios_cursada(),
					FormElementoConfig::validar_select => false,
		)));

		$materia = $form->add_elemento($fabrica->elemento('materia_descr', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('horarios_cursadas.filtro_materia')),
					FormElementoConfig::filtro => validador::TIPO_TEXTO,
					FormElementoConfig::obligatorio => false,
					FormElementoConfig::elemento => array('tipo' => 'text'),
					FormElementoConfig::valor_default => '',
		)));
		$fabrica->elemento_decorar_boton_limpiar($materia, 'limpiar_materia');

		$form->add_elemento($fabrica->elemento('materia', array(
					FormElementoConfig::filtro => validador::TIPO_ALPHANUM,
					FormElementoConfig::obligatorio => false,
					FormElementoConfig::elemento => array('tipo' => 'hidden'),
					FormElementoConfig::valor_default => '',
		)));

		$form->add_elemento($fabrica->elemento('ubicacion', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('horarios_cursadas.filtro_sede')),
					FormElementoConfig::filtro => validador::TIPO_ALPHANUM,
					FormElementoConfig::obligatorio => false,
					FormElementoConfig::elemento => array('tipo' => 'select'),
					FormElementoConfig::multi_options => static::get_ubicaciones(),
					FormElementoConfig::validar_select => false,
					FormElementoConfig::clase_css => 'filtros_comunes',
		)));

		$form->add_elemento($fabrica->elemento('dia_semana', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('horarios_cursadas.filtro_dia_semana')),
					FormElementoConfig::filtro => validador::TIPO_ALPHANUM,
					FormElementoConfig::obligatorio => false,
					FormElementoConfig::elemento => array('tipo' => 'select'),
					FormElementoConfig::multi_options => static::get_dias_semana(),
					FormElementoConfig::validar_select => false,
					FormElementoConfig::clase_css => 'filtros_comunes',
		)));

		$form->add_elemento($fabrica->elemento('turno', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('horarios_cursadas.filtro_turno')),
					FormElementoConfig::filtro => validador::TIPO_ALPHANUM,
					FormElementoConfig::obligatorio => false,
					FormElementoConfig::elemento => array('tipo' => 'select'),
					FormElementoConfig::multi_options => static::get_turnos(),
					FormElementoConfig::validar_select => false,
					FormElementoConfig::clase_css => 'filtros_comunes',
		)));
		$form->add_elemento($fabrica->elemento('ra', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('fecha_examen.filtro_responsable_academica')),
					FormElementoConfig::filtro => validador::TIPO_ALPHANUM,
					FormElementoConfig::obligatorio => true,
					FormElementoConfig::elemento => array('tipo' => 'select'),
					FormElementoConfig::multi_options => static::get_ra(),
					FormElementoConfig::validar_select => false,
					FormElementoConfig::clase_css => 'filtros_comunes',
		)));
		


		$form->add_accion($fabrica->accion_boton_submit('boton_buscar', ucfirst(kernel::traductor()->trans('horarios_cursadas.filtro_buscar'))));
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
						'ra' => array('span' => 12),
					),
					array(
						'carrera' => array('span' => 8),
						'anio_cursada' => array('span' => 4),
					),
                                        array(
						'periodo' => array('span' => 12),
					),
					array(
						'materia_descr' => array('span' => 12),
						'materia' => array('span' => 4),
					),
					array(
						'ubicacion' => array('span' => 4),
						'dia_semana' => array('span' => 4),
						'turno' => array('span' => 4),
					),
				)
			),
		);
	}

	/* valores de filtrado */
	function get_dias_semana()
	{
		return array(
			'' => ucfirst(kernel::traductor()->trans('horarios_cursadas.filtro_seleccione')),
			'Lunes' => ucfirst(kernel::traductor()->trans('horarios_cursadas.filtro_dias.lunes')),
			'Martes' => ucfirst(kernel::traductor()->trans('horarios_cursadas.filtro_dias.martes')),
			'Miercoles' => ucfirst(kernel::traductor()->trans('horarios_cursadas.filtro_dias.miercoles')),
			'Jueves' => ucfirst(kernel::traductor()->trans('horarios_cursadas.filtro_dias.jueves')),
			'Viernes' => ucfirst(kernel::traductor()->trans('horarios_cursadas.filtro_dias.viernes')),
			'Sabado' => ucfirst(kernel::traductor()->trans('horarios_cursadas.filtro_dias.sabado')),
			'Domingo' => ucfirst(kernel::traductor()->trans('horarios_cursadas.filtro_dias.domingo')),
		);
	}

	function get_ubicaciones()
	{
		$datos = catalogo::consultar('sistema', 'ubicaciones');
		return guarani_form_elemento::armar_combo_opciones($datos, 'ubicacion', 'nombre', true, false, ucfirst(kernel::traductor()->trans('horarios_cursadas.filtro_todas')));
	}

	function get_turnos()
	{
		return array("" => kernel::traductor()->trans('horarios_cursadas.filtro_seleccione'),
			1 => ucfirst(kernel::traductor()->trans('horarios_cursadas.filtro_turnos.m')),
			2 => ucfirst(kernel::traductor()->trans('horarios_cursadas.filtro_turnos.t')),
			3 => ucfirst(kernel::traductor()->trans('horarios_cursadas.filtro_turnos.n'))
		);
	}

	function get_ra()
	{
		$datos = catalogo::consultar('usuario_anonimo', 'get_responsables_academicas');
		return guarani_form_elemento::armar_combo_opciones($datos, '_ID_', 'nombre', true, false, ucfirst(kernel::traductor()->trans('horarios_cursadas.filtro_todas')));
	}

	function get_anios_cursada()
	{
		return array("" => kernel::traductor()->trans('horarios_cursadas.filtro_seleccione'),
			"1" => ucfirst(kernel::traductor()->trans('horarios_cursadas.filtro_anios.1')),
			"2" => ucfirst(kernel::traductor()->trans('horarios_cursadas.filtro_anios.2')),
			"3" => ucfirst(kernel::traductor()->trans('horarios_cursadas.filtro_anios.3')),
			"4" => ucfirst(kernel::traductor()->trans('horarios_cursadas.filtro_anios.4')),
			"5" => ucfirst(kernel::traductor()->trans('horarios_cursadas.filtro_anios.5')),
			"6" => ucfirst(kernel::traductor()->trans('horarios_cursadas.filtro_anios.6')),
		);
	}

	function get_periodos_lectivos()
	{
		$formulario_filtro = kernel::request()->get('formulario_filtro');
		$ra = "";
		if (isset($formulario_filtro['ra']) && $formulario_filtro['ra'] != "")
			$ra = $this->decodificar_ra($formulario_filtro['ra']);
		if ($ra) {
			$datos = catalogo::consultar('unidad_academica', 'periodos_lectivos_vigentes_por_ra', array('responsable_academica' => $ra));
			if (!empty($datos)) {
				return guarani_form_elemento::armar_combo_opciones($datos, 'periodo_lectivo', 'periodo_lectivo_nombre', true, false, ucfirst(kernel::traductor()->trans('horarios_cursadas.filtro_seleccione')));
			} else {

				return array('00' => 'No hay periodos asociados a la responsable académica');
			}
		} else {
			return array('' => ucfirst(kernel::traductor()->trans('horarios_cursadas.filtro_todos')));
		}
	}

	function get_carreras()
	{

		$formulario_filtro = kernel::request()->get('formulario_filtro');
		$ra = "";
		if (isset($formulario_filtro['ra']) && $formulario_filtro['ra'] != "")
			$ra = $this->decodificar_ra($formulario_filtro['ra']);
		if ($ra) {
			$datos = catalogo::consultar('usuario_anonimo', 'get_carreras', $ra);
			if (!empty($datos)) {
				return guarani_form_elemento::armar_combo_opciones($datos, '_ID_', 'nombre', true, false, ucfirst(kernel::traductor()->trans('horarios_cursadas.filtro_seleccione')));
			} else {
				return array('' => 'No hay carreras asociadas a la responsable académica');
			}
		} else {
			return array('' => ucfirst(kernel::traductor()->trans('horarios_cursadas.filtro_seleccione')));
		}
	}

	function decodificar_ra($ra_hash)
	{
		$datos = catalogo::consultar('usuario_anonimo', 'get_responsables_academicas');

		if (!empty($datos)) {
			foreach ($datos as $value) {
				if ($value['_ID_'] == $ra_hash) {
					return $value['responsable_academica'];
				}
			}
		}
		return false;
	}

}