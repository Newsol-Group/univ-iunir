<?php

namespace siu\operaciones\evaluaciones\nueva_evaluacion;

use SIU\Chulupi\interfaz\componentes\forms\FormElementoConfig;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\modelo\DecoradorIds;
use SIU\Chulupi\util\validador;
use siu\extension_kernel\formularios\builder_formulario;
use siu\extension_kernel\formularios\fabrica_formularios;
use siu\extension_kernel\formularios\guarani_form;
use siu\extension_kernel\formularios\guarani_form_elemento;
use siu\modelo\datos\catalogo;

class builder_nueva_evaluacion extends builder_formulario
{
	public $modelo;
	public $comision;
	protected $data;

	function get_id_html()
	{
		return 'nueva_eval';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('evaluaciones', 'crear_evaluacion', $this->comision[DecoradorIds::$nombre_columna_generada]);
	}

	function set_modelo($modelo)
	{
		$this->modelo = $modelo;
	}

	function set_comision_seleccionada($com)
	{
		$this->comision = $com;
	}
	
	protected function get_opciones_tipo_evaluacion()
	{
		$opciones = $this->modelo->info__tipo_evaluacion();
		return guarani_form_elemento::armar_combo_opciones($opciones, 'evaluacion_tipo', 'nombre');
	}

	protected function get_opciones_escala_notas()
	{
		if (isset($this->comision['evaluacion_escala_nota'])) {
			$escala_nota = $this->comision['evaluacion_escala_nota'];
		} else {
			// Nueva evaluación
			$escala_nota = 0;
		}
		
		$parametros = [
			'comision' => $this->comision['comision'],
			'escala_asignada' => $escala_nota
		];
		$opciones = $this->modelo->info__lista_escala_notas($parametros);
		return guarani_form_elemento::armar_combo_opciones($opciones, 'valor', 'descr');
	}

	protected function get_opciones_relacionadas()
	{
		if (isset($this->comision['evaluacion'])) {
			$eval_actual = $this->comision['evaluacion'];
		} else {
			// Nueva evaluación
			$eval_actual = null;
		}
		$datos = $this->modelo->info__evaluaciones_relacionadas($this->comision['entidad'], $eval_actual);
		return guarani_form_elemento::armar_combo_opciones($datos, 'evaluacion', 'nombre', false);
	}

	protected function get_opciones_instancias()
	{
		$datos = catalogo::consultar('carga_evaluaciones_parciales', 'lista_instancias_comision', array('comision' => $this->comision['comision']));
		return guarani_form_elemento::armar_combo_opciones($datos, 'instancia', 'instancia_nombre', false);
	}

	protected function generar_definicion(guarani_form $form, fabrica_formularios $fabrica)
	{
		$form->add_accion($fabrica->accion_boton_submit('btn-crear', 'Crear'));

		$form->add_elemento($fabrica->elemento_fecha('fecha', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('fecha')),
					FormElementoConfig::obligatorio => true,
		)));

		$form->add_elemento($fabrica->elemento_timepicker('hora_inicio', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('hora_inicio')),
					FormElementoConfig::obligatorio => true,
		)));

		$form->add_elemento($fabrica->elemento_timepicker('hora_fin', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('hora_fin')),
					FormElementoConfig::obligatorio => true,
		)));

		$form->add_elemento($fabrica->elemento_texto('nombre', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('nombre')),
					FormElementoConfig::obligatorio => true,
					FormElementoConfig::largo => 255,
		)));

		$form->add_elemento($fabrica->elemento_textarea('descripcion', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('descripcion')),
					FormElementoConfig::obligatorio => false,
					FormElementoConfig::largo => 255,
		)));

		$opciones = $this->get_opciones_tipo_evaluacion();
		$form->add_elemento($fabrica->elemento_select('evaluacion_tipo', $opciones, array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('tipo')),
					FormElementoConfig::obligatorio => true,
		)));

		$opciones = array('S' => 'Si', 'N' => 'No');

		$form->add_elemento($fabrica->elemento_select('visible_al_alumno', $opciones, array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('visible')),
					FormElementoConfig::obligatorio => true,
					FormElementoConfig::elemento => array('tipo' => 'radio'),
					FormElementoConfig::clase_css => 'inline',
					FormElementoConfig::valor_default => 'S'
		)));

		$opciones = $this->get_opciones_escala_notas();
		$form->add_elemento($fabrica->elemento_select('escala_nota', $opciones, array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('escala')),
					FormElementoConfig::obligatorio => true,
		)));

		$opciones = array('S' => 'Si', 'N' => 'No');
		$form->add_elemento($fabrica->elemento_select('promediable', $opciones, array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('promediable')),
					FormElementoConfig::obligatorio => true,
					FormElementoConfig::elemento => array('tipo' => 'radio'),
					FormElementoConfig::clase_css => 'inline',
					FormElementoConfig::valor_default => 'S'
		)));

		$opciones = $this->get_opciones_relacionadas();
		if (!empty($opciones)) {
			$form->add_elemento($fabrica->elemento('eval_relacionadas', array(
						FormElementoConfig::label => ucfirst(kernel::traductor()->trans('relacionadas')),
						FormElementoConfig::obligatorio => false,
						FormElementoConfig::multi_options => $opciones,
						FormElementoConfig::elemento => array('tipo' => 'multicheckbox'),
						FormElementoConfig::validar_select => false,
						FormElementoConfig::filtro => validador::TIPO_ARRAY_INT,
			)));
		} else {
			$form->add_elemento($fabrica->elemento_fijo('eval_relacionadas', array(
						FormElementoConfig::label => ucfirst(kernel::traductor()->trans('relacionadas')),
						FormElementoConfig::obligatorio => false,
			)));
		}

		$opciones = $this->get_opciones_instancias();
		$form->add_elemento($fabrica->elemento('instancias', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans('instancias')),
					FormElementoConfig::obligatorio => true,
					FormElementoConfig::multi_options => $opciones,
					FormElementoConfig::elemento => array('tipo' => 'multicheckbox'),
					FormElementoConfig::validar_select => false,
					FormElementoConfig::filtro => validador::TIPO_ARRAY_INT,
		)));
	}

	function get_configuracion_layout_grilla()
	{
		return array(
			array(
				'titulo' => kernel::traductor()->trans('nueva_evaluacion'),
				'filas' => array(
					array(
						'fecha' => array('span' => 6),
						'hora_inicio' => array('span' => 3),
						'hora_fin' => array('span' => 3)
					),
					array('nombre' => array('span' => 12)),
					array('descripcion' => array('span' => 12)),
					array('evaluacion_tipo' => array('span' => 12)),
					array('visible_al_alumno' => array('span' => 6), 'promediable' => array('span' => 6)),
					array('escala_nota' => array('span' => 12)),
					array('eval_relacionadas' => array('span' => 6), 'instancias' => array('span' => 6)),
				)
			)
		);
	}

}