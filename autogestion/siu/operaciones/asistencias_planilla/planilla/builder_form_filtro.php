<?php
namespace siu\operaciones\asistencias_planilla\planilla;

use SIU\Chulupi\interfaz\componentes\forms\FormElementoConfig;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use \siu\extension_kernel\formularios\builder_formulario;
use \siu\extension_kernel\formularios\fabrica_formularios;
use \siu\extension_kernel\formularios\guarani_form;
use siu\operaciones\asistencias_planilla\controlador;
use siu\modelo\datos\catalogo;
use siu\operaciones\_comun\util\forms;

class builder_form_filtro extends builder_formulario
{
	protected $controlador;
	public $hash_comision;
	public $comision = null;
	public $subcomision = null;

	/**
	 * @return controlador
	 */
	function get_controlador()
	{
		if (!isset($this->controlador)) {
			$this->controlador = kernel::localizador()->instanciar('operaciones\asistencias_planilla\controlador');
		}
		return $this->controlador;
	}

	function get_id_html()
	{
		return 'formulario_filtro';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('asistencias_planilla', 'index', array(0 => $this->hash_comision));
	}

	protected function generar_definicion(guarani_form $form, fabrica_formularios $fabrica)
	{
		$form->add_elemento($fabrica->elemento('imprime_libres', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('asistencias.planilla.imprime_libres')),
					FormElementoConfig::filtro => validador::TIPO_TEXTO,
					FormElementoConfig::obligatorio => false,
					FormElementoConfig::elemento => array('tipo' => 'checkbox'),
					FormElementoConfig::largo => 1,
					FormElementoConfig::checked_value => 'S', // Imprime planilla con Todos los alumnos, incluyendo los Libres
					FormElementoConfig::unchecked_value => 'N', //Imprime planilla sin incluir alumnos Libres
		)));

		$form->add_elemento($fabrica->elemento('tipo', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('asistencias.planilla.tipo')),
					FormElementoConfig::filtro => validador::TIPO_ALPHANUM,
					FormElementoConfig::obligatorio => false,
					FormElementoConfig::elemento => array('tipo' => 'select'),
					FormElementoConfig::multi_options => static::get_tipos(),
					FormElementoConfig::clase_css => 'input-small',
					FormElementoConfig::validar_select => false,
		)));

		$form->add_elemento($fabrica->elemento_fecha('fecha', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans("asistencias.planilla.fecha")),
					FormElementoConfig::filtro => validador::TIPO_DATE,
					FormElementoConfig::obligatorio => true,
					FormElementoConfig::filtro_params => array('format' => 'd/m/Y', 'allowempty' => false),
					FormElementoConfig::clase_css => 'date input-small',
					FormElementoConfig::placeholder => kernel::traductor()->trans('asistencias.planilla.filtro_seleccione'),
		)));

		$form->add_elemento($fabrica->elemento('flg_pdf', array(
					FormElementoConfig::filtro => validador::TIPO_TEXTO,
					FormElementoConfig::obligatorio => false,
					FormElementoConfig::elemento => array('tipo' => 'hidden'),
		)));

		$form->add_accion($fabrica->accion_boton_submit('boton_buscar', ucfirst(kernel::traductor()->trans('asistencias.planilla.buscar'))));
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
						'fecha' => array('span' => 3),
						'tipo' => array('span' => 3),
						'imprime_libres' => array('span' => 3),
						'flg_pdf' => array('span' => 0),
					),
				)
			),
		);
	}

	function get_tipos()
	{

		$datos = catalogo::consultar('carga_asistencias', 'get_tipos_clase_comision', array('comision' => $this->comision, 'subcomision' => $this->subcomision));
		return forms::rs_to_select($datos, 'Todos');
	}

}