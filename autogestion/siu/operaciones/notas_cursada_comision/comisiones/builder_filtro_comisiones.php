<?php
namespace siu\operaciones\notas_cursada_comision\comisiones;

use SIU\Chulupi\interfaz\componentes\forms\FormElementoConfig;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use siu\extension_kernel\formularios\builder_filtro;
use siu\extension_kernel\formularios\fabrica_formularios;
use siu\extension_kernel\formularios\guarani_form;
use siu\modelo\datos\catalogo;

class builder_filtro_comisiones extends builder_filtro
{
	protected $data;

	function get_id_html()
	{
		return '';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('notas_cursada_comision', 'filtrar_comisiones');
	}

	/**
	 * El pl le envia data para configurarlo.
	 * @param $data
	 */
	function set_data($data)
	{
		$this->data = $data;
	}

	protected function get_peridos_lectivos()
	{
		$periodos = array(NULL => 'Todos'); //no seleccion
		foreach (catalogo::consultar('unidad_academica', 'periodos_lectivos_todos') as $aux) {
			$periodos[$aux['periodo_lectivo']] = $aux['anio_academico'] . ' - ' . $aux['periodo_lectivo_nombre'];
		}
		return $periodos;
		//todo: migrar a algo como lo de abajo
		//return guarani_form_elemento::armar_combo_opciones($datos, 'anio_academico', 'anio_academico', false);
	}

	protected function generar_definicion(guarani_form $form, fabrica_formularios $fabrica)
	{
		$form->add_accion($fabrica->accion_boton_submit('btn_filtro', 'Filtrar'));

		$form->add_elemento($fabrica->elemento('periodo_lectivo', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('notas_cursada_comision.form_filtro_periodo_lectivo')),
					FormElementoConfig::filtro => validador::TIPO_INT,
					FormElementoConfig::obligatorio => false,
					FormElementoConfig::elemento => array('tipo' => 'select'),
					FormElementoConfig::multi_options => $this->get_peridos_lectivos(),
		)));

		$elemento = $form->add_elemento($fabrica->elemento_autocompletar(
					'actividad', 
					array(
						FormElementoConfig::label => ucfirst(kernel::traductor()->trans('notas_cursada_comision.form_filtro_actividad')),
						FormElementoConfig::obligatorio => false,
						FormElementoConfig::valor_default => '',
					), 
					function($id) {
						if (!$id) {
							return 'Todas';
						}
						$actividad_info = catalogo::consultar('unidad_academica', 'actividad', array('actividad' => $id));
						return $actividad_info['nombre'] . ' (' . $actividad_info['codigo'] . ')';
					}, 
					kernel::vinculador()->crear('notas_cursada_comision', 'buscar_actividad')
		));
		$fabrica->elemento_decorar_boton_limpiar($elemento, 'limpiar_actividad');
	}

	function get_configuracion_layout_grilla()
	{
		//---- DATOS ACTA ------------------------------------------------------
		return array(
			array(
				'titulo' => ucfirst(kernel::traductor()->trans('filtro')),
				'filas' => array(
					array('periodo_lectivo' => array('span' => 6), 'actividad' => array('span' => 6))
				),
			),
		);
	}

}