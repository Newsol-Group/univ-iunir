<?php
namespace siu\operaciones\fecha_examen\filtro;

use SIU\Chulupi\interfaz\componentes\forms\FormElementoConfig;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use \siu\extension_kernel\formularios\builder_formulario;
use \siu\extension_kernel\formularios\fabrica_formularios;
use \siu\extension_kernel\formularios\guarani_form;
use siu\modelo\datos\catalogo;
use siu\extension_kernel\formularios\guarani_form_elemento;
use siu\operaciones\fecha_examen\controlador;

class builder_form_filtro extends builder_formulario
{
	protected $controlador;

	/**
	 * @return controlador
	 */
	function get_controlador()
	{
		if (!isset($this->controlador)) {
			$this->controlador = kernel::localizador()->instanciar('operaciones\fecha_examen\controlador');
		}

		return $this->controlador;
	}

	function get_id_html()
	{
		return 'formulario_filtro';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('fecha_examen');
	}

	protected function generar_definicion(guarani_form $form, fabrica_formularios $fabrica)
	{
		
		$form->add_elemento($fabrica->elemento('ra', array(
			FormElementoConfig::label => kernel::traductor()->trans('fecha_examen.filtro_responsable_academica'),
			FormElementoConfig::filtro => validador::TIPO_ALPHANUM,
			FormElementoConfig::obligatorio => true,
			FormElementoConfig::elemento => array('tipo' => 'select'),
			FormElementoConfig::multi_options => static::get_ra(),
			FormElementoConfig::validar_select => false,
		)));
			
		$form->add_elemento($fabrica->elemento('ubicacion', array(
			FormElementoConfig::label => kernel::traductor()->trans('fecha_examen.filtro_ubicacion'),
			FormElementoConfig::filtro => validador::TIPO_ALPHANUM,
			FormElementoConfig::obligatorio => true,
			FormElementoConfig::elemento => array('tipo' => 'select'),
			FormElementoConfig::multi_options => static::get_ubicacion(),
			FormElementoConfig::validar_select => false,
		)));
			
		$form->add_elemento($fabrica->elemento('carrera', array(
			FormElementoConfig::label => kernel::traductor()->trans('fecha_examen.filtro_carrera'),
			FormElementoConfig::filtro => validador::TIPO_ALPHANUM,
			FormElementoConfig::obligatorio => true,
			FormElementoConfig::elemento => array('tipo' => 'select'),
			FormElementoConfig::multi_options => static::get_carreras(),
			FormElementoConfig::validar_select => false,
		)));

		$form->add_elemento($fabrica->elemento('plan', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('fecha_examen.filtro_plan')),
					FormElementoConfig::filtro => validador::TIPO_ALPHANUM,
					FormElementoConfig::obligatorio => false,
					FormElementoConfig::elemento => array('tipo' => 'select'),
					FormElementoConfig::multi_options => static::get_planes(),
					FormElementoConfig::validar_select => false,
		)));

		$materia = $form->add_elemento($fabrica->elemento('materia_descr', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('fecha_examen.filtro_materia')),
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

		$form->add_elemento($fabrica->elemento_fecha('fecha_desde', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans("fecha_examen.filtro_fecha_desde")),
					FormElementoConfig::filtro => validador::TIPO_DATE,
					FormElementoConfig::obligatorio => false,
					FormElementoConfig::placeholder => kernel::traductor()->trans('fecha_examen.filtro_seleccione'),
					FormElementoConfig::clase_css => 'date filtros_fechas',
		)));

		$form->add_elemento($fabrica->elemento_fecha('fecha_hasta', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans("fecha_examen.filtro_fecha_hasta")),
					FormElementoConfig::filtro => validador::TIPO_DATE,
					FormElementoConfig::obligatorio => false,
					FormElementoConfig::placeholder => kernel::traductor()->trans('fecha_examen.filtro_seleccione'),
					FormElementoConfig::clase_css => 'date filtros_fechas',
		)));

		$form->add_elemento($fabrica->elemento('tipo_mesa', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('fecha_examen.filtro_tipo_de_mesa')),
					FormElementoConfig::filtro => validador::TIPO_ALPHANUM,
					FormElementoConfig::obligatorio => false,
					FormElementoConfig::elemento => array('tipo' => 'select'),
					FormElementoConfig::multi_options => static::get_tipos_mesa(),
					FormElementoConfig::validar_select => false,
		)));

		$form->add_accion($fabrica->accion_boton_submit('boton_buscar', ucfirst(kernel::traductor()->trans('fecha_examen.filtro_buscar'))));
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
						'ubicacion' => array('span' => 12),
					),
					array(
						'carrera' => array('span' => 12),
					),
					array(
						'plan' => array('span' => 12),
					),
					array(
						'materia_descr' => array('span' => 12),
						'materia' => array('span' => 0)
					),
					array(
						'fecha_desde' => array('span' => 4),
						'fecha_hasta' => array('span' => 4),
						'tipo_mesa' => array('span' => 4)
					),
				)
			),
		);
	}

	function get_carreras()
	{
		$formulario_filtro = kernel::request()->get('formulario_filtro');
		$ra = "";
		$ubicacion = "";
		
		// Si viene ra y ubicacion en el query string (filtro)
		if ((isset($formulario_filtro['ra']) && $formulario_filtro['ra'] != "") && (isset($formulario_filtro['ra']) && $formulario_filtro['ubicacion'] != "")) {
			$ra = $this->get_controlador()->decodificar_ra($formulario_filtro['ra']);
			$ubicacion = $this->get_controlador()->decodificar_ubicacion($formulario_filtro['ubicacion']);
			
			$parametros = [
				'ra' => $ra,
				'ubicacion' => $ubicacion
			];
			$datos = catalogo::consultar('usuario_anonimo', 'get_carreras_filtro', $parametros);
			
			if (!empty($datos)) {
				return guarani_form_elemento::armar_combo_opciones($datos, '_ID_', 'nombre', true, false, ucfirst(kernel::traductor()->trans('fecha_examen.filtro_seleccione')));
			} else {
				return ['' => kernel::traductor()->trans('fecha_examen.no_hay_propuestas_ra_ubicacion')];
			}
		} else {
			return ['' => ucfirst(kernel::traductor()->trans('fecha_examen.filtro_seleccione'))];
		}
	}

	function get_ubicacion(){
		$formulario_filtro = kernel::request()->get('formulario_filtro');
		if ((isset($formulario_filtro['ra']) && $formulario_filtro['ra'] != "") ) {
		$ra = $this->get_controlador()->decodificar_ra($formulario_filtro['ra']);
		$datos = catalogo::consultar('usuario_anonimo', 'get_ubicacion', ['ra' => $ra]);
		return guarani_form_elemento::armar_combo_opciones($datos, '_ID_', 'nombre', true, false, ucfirst(kernel::traductor()->trans('fecha_examen.filtro_seleccione')));
	}else{
		return ['' => ucfirst(kernel::traductor()->trans('fecha_examen.filtro_seleccione'))];}
	}

	function get_ra()
	{
		$datos = catalogo::consultar('usuario_anonimo', 'get_responsables_academicas');
		return guarani_form_elemento::armar_combo_opciones($datos, '_ID_', 'nombre', true, false, ucfirst(kernel::traductor()->trans('fecha_examen.filtro_seleccione')));
	}

	function get_planes()
	{
		$formulario_filtro = kernel::request()->get('formulario_filtro');
		$carrera = "";
		if ((isset($formulario_filtro['carrera']) && $formulario_filtro['carrera'] != "")) {
			$carrera = $this->get_controlador()->decodificar_carrera($formulario_filtro['carrera']);
		}
		
		if ($carrera) {
			$datos = catalogo::consultar('usuario_anonimo', 'get_planes_propuesta', array('propuesta' => $carrera));
			return guarani_form_elemento::armar_combo_opciones($datos, '_ID_', 'nombre', true, false, ucfirst(kernel::traductor()->trans('fecha_examen.filtro_todos')));
		} else {
			return ['' => ucfirst(kernel::traductor()->trans('fecha_examen.filtro_todos'))];
		}
	}

	function get_tipos_mesa()
	{
		$datos = catalogo::consultar('unidad_academica', 'get_instancias_mesas_examen');
		return guarani_form_elemento::armar_combo_opciones($datos, 'instancia', 'nombre', true, false, ucfirst(kernel::traductor()->trans('fecha_examen.filtro_todas')));
	}

}