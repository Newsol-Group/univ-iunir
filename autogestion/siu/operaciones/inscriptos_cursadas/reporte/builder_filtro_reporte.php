<?php
namespace siu\operaciones\inscriptos_cursadas\reporte;

use SIU\Chulupi\interfaz\componentes\forms\FormElementoConfig;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use siu\extension_kernel\formularios\builder_filtro;
use siu\extension_kernel\formularios\fabrica_formularios;
use siu\extension_kernel\formularios\guarani_form;
use siu\extension_kernel\formularios\guarani_form_elemento;
use siu\modelo\datos\catalogo;

class builder_filtro_reporte extends builder_filtro
{
	protected $data;

	function get_id_html()
	{
		return '';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('inscriptos_cursadas', 'filtrar_comisiones');
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
		$docente = array('docente' => kernel::persona()->get_id_docente());
		foreach (catalogo::consultar('unidad_academica', 'periodos_lectivos_todos', $docente) as $aux) {
			$periodos[$aux['periodo_lectivo']] = $aux['anio_academico'] . ' - ' . $aux['periodo_lectivo_nombre'];
		}
		return $periodos;
		//@todo: migrar a algo como lo de abajo
		//return guarani_form_elemento::armar_combo_opciones($datos, 'anio_academico', 'anio_academico', false);
	}

	protected function get_ubicaciones()
	{
		$datos = catalogo::consultar('docente', 'get_lista_ubicaciones');
		return guarani_form_elemento::armar_combo_opciones($datos, 'ubicacion', 'ubicacion_nombre', true, true, 'Todos');
	}

	protected function generar_definicion(guarani_form $form, fabrica_formularios $fabrica)
	{
		//@todo -Pasar a mensaje //-ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.seleccionar_alumnos') ))

		$form->add_accion($fabrica->accion_boton_submit('btn_filtro', 'Filtrar'));

		//@todo: mover el <i> a la fabrica o a accion_guarani
		$form->add_accion($fabrica->accion_boton_submit('btn_pdf', '<i class="g3w-icono-pdf"></i>'));

		//@todo: mover el <i> a la fabrica o a accion_guarani
		$form->add_accion($fabrica->accion_boton_submit('btn_xls', '<i class="g3w-icono-xls"></i>'));

		//OBS: los labels estan "harcodeados", solamente los copio como estaban. todo - pasar a mensajes.
		$opciones = $this->get_peridos_lectivos();
		$form->add_elemento($fabrica->elemento_select('periodo_lectivo', $opciones, array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans("form_filtro_periodo_lectivo")),
					FormElementoConfig::filtro => validador::TIPO_INT,
					FormElementoConfig::obligatorio => false,
						//FormElementoConfig::elemento		=> array('tipo' => 'select'),
						//FormElementoConfig::multi_options => ,
						//@todo - esta mal-->esta consulta trae el año, no la clave (antes tampoco andaba)
						//FormElementoConfig::valor_default => catalogo::consultar('unidad_academica', 'get_anio_academico_actual')
		)));
		$form->add_elemento($fabrica->elemento('anio_academico', array(
					FormElementoConfig::filtro => validador::TIPO_INT,
					FormElementoConfig::obligatorio => false,
					FormElementoConfig::elemento => array('tipo' => 'hidden'),
		)));
		
		//OBS: los labels estan "harcodeados", solamente los copio como estaban. todo - pasar a mensajes.
		$elemento = $form->add_elemento($fabrica->elemento_autocompletar('actividad', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans("form_filtro_actividad")),
					FormElementoConfig::obligatorio => false,
					FormElementoConfig::valor_default => '', //todos
					FormElementoConfig::clase_css => ''
						), function ($id) {
					if (!$id)
						return 'Todas';
					$actividad_info = catalogo::consultar('unidad_academica', 'actividad', array('actividad' => $id));
					return $actividad_info['nombre'] . ' (' . $actividad_info['codigo'] . ')';
				}, kernel::vinculador()->crear('inscriptos_cursadas', 'buscar_actividad')
		));
		$fabrica->elemento_decorar_boton_limpiar($elemento, 'limpiar_actividad');

		//OBS: los labels estan "harcodeados", solamente los copio como estaban. todo - pasar a mensajes.
		$elemento = $form->add_elemento($fabrica->elemento_autocompletar('docente', array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans("form_filtro_docente")),
					FormElementoConfig::obligatorio => false,
					FormElementoConfig::valor_default => kernel::persona()->get_id_docente(),
					FormElementoConfig::clase_css => 'span12'
						), function ($id) {
					if (!$id)
						return 'Todos';
					$docente_info = catalogo::consultar('docente', 'datos_basicos', array('docente' => $id));
					return $docente_info['apellido'] . ', ' . $docente_info['nombres'] . ' (' . $docente_info['legajo'] . ')';
				}, kernel::vinculador()->crear('inscriptos_cursadas', 'buscar_docente')
		));
				
		$form->add_elemento($fabrica->elemento_select('ubicacion', $this->get_ubicaciones(), array(
					FormElementoConfig::label => ucfirst(kernel::traductor()->trans("form_filtro_ubicacion")),
					FormElementoConfig::filtro => validador::TIPO_INT,
					FormElementoConfig::obligatorio => false,
		)));
	}

	/**
	 * Por defecto los filtros de guarani se basan en el layout grilla. Se debe configurar en este metodo para poder
	 * instanciar el mismo
	 */
	function get_configuracion_layout_grilla()
	{
		//---- DATOS ACTA ------------------------------------------------------
		return array(
			array(
				'titulo' => ucfirst(kernel::traductor()->trans('filtro')), //@todo - pasar a mensajes
				'filas' => array(
					array('periodo_lectivo' => array('span' => 6), 'actividad' => array('span' => 6), 'anio_academico' => array()),
					array('docente' => array('span' => 6), 'ubicacion' => array('span' => 6))
				),
			),
		);
	}

}