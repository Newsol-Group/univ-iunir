<?php
namespace siu\operaciones\inscriptos_examenes\reporte;

use SIU\Chulupi\interfaz\componentes\forms\FormElementoConfig;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use siu\extension_kernel\formularios\builder_filtro;
use siu\extension_kernel\formularios\fabrica_formularios;
use siu\extension_kernel\formularios\guarani_form;
use siu\extension_kernel\formularios\guarani_form_elemento;
use siu\guarani;
use siu\modelo\datos\catalogo;
use siu\modelo\entidades\parametro;
use siu\modelo\_comun\comunes;

class builder_filtro_reporte extends builder_filtro
{
	protected $data;

	function get_id_html()
	{
		return '';
	}

	function get_action()
	{
		return kernel::vinculador()->crear('inscriptos_examenes', 'index');
	}

	/**
	 * El pl le envia data para configurarlo.
	 * @param $data
	 */
	function set_data($data)
	{
		$this->data = $data;

	}

	protected function get_turnos()
	{
		$turnos = array(NULL => 'Todos'); //no seleccion
		$docente = array('docente' => kernel::persona()->get_id_docente());

		foreach (catalogo::consultar('unidad_academica', 'turnos_examen_todos', $docente) as $aux) {
			// $turnos[$aux['turno_examen']] = $aux['turno_examen_nombre'];
			$turnos[$aux['turno_examen']] = $aux['anio_academico'] . ' - ' . $aux['turno_examen_nombre'];
		}
		
		return $turnos;
	}
	
	protected function get_ubicaciones()
	{
		$datos = catalogo::consultar('docente', 'get_lista_ubicaciones');
		return guarani_form_elemento::armar_combo_opciones($datos, 'ubicacion', 'ubicacion_nombre', true, true, 'Todos');
	}

	protected function generar_definicion(guarani_form $form, fabrica_formularios $fabrica)
	{
		//todo -Pasar a mensaje //-ucfirst(kernel::traductor()->trans('admin_actas_examen_fc.seleccionar_alumnos') ))

		$form->add_accion($fabrica->accion_boton_submit('btn_filtro', 'Filtrar'));

		//todo: mover el <i> a la fabrica o a accion_guarani
		$form->add_accion($fabrica->accion_boton_submit('btn_pdf', '<i class="g3w-icono-pdf"></i>'));

		//todo: mover el <i> a la fabrica o a accion_guarani
		$form->add_accion($fabrica->accion_boton_submit('btn_xls', '<i class="g3w-icono-xls"></i>'));


		//OBS: los labels estan "harcodeados", solamente los copio como estaban. todo - pasar a mensajes.
		$opciones = $this->get_turnos();
		$form->add_elemento($fabrica->elemento_select('turnos', $opciones, array(
			FormElementoConfig::label => ucfirst(kernel::traductor()->trans("form_filtro_turnos")),
			FormElementoConfig::filtro => validador::TIPO_INT,
			FormElementoConfig::obligatorio => false,
		)));
		//klog ('ua___ '. catalogo::consultar('unidad_academica', 'get_anio_academico_actual'));
//		$form->add_elemento($fabrica->elemento('anio_academico', array(
//			FormElementoConfig::filtro => validador::TIPO_INT,
//			FormElementoConfig::obligatorio => false,
//			FormElementoConfig::elemento => array('tipo' => 'hidden'),
//		)));


		//OBS: los labels estan "harcodeados", solamente los copio como estaban. todo - pasar a mensajes.
		$elemento = $form->add_elemento($fabrica->elemento_autocompletar('actividad',
			array(
				FormElementoConfig::label => ucfirst(kernel::traductor()->trans("form_filtro_actividad")),
				FormElementoConfig::obligatorio => false,
				FormElementoConfig::valor_default => '', //todos
				FormElementoConfig::clase_css => ''
			),
			function ($id) {
				if (!$id) return 'Todas';
				$actividad_info = catalogo::consultar('unidad_academica', 'actividad', array('actividad' => $id));
				return $actividad_info['nombre'] . ' (' . $actividad_info['codigo'] . ')';
			},
			kernel::vinculador()->crear('inscriptos_examenes', 'buscar_actividad')

		));
		$fabrica->elemento_decorar_boton_limpiar($elemento, 'limpiar_actividad');

		//OBS: los labels estan "harcodeados", solamente los copio como estaban. todo - pasar a mensajes.
		$elemento = $form->add_elemento($fabrica->elemento_autocompletar('docente',
			array(
				FormElementoConfig::label => ucfirst(kernel::traductor()->trans("form_filtro_docente")),
				FormElementoConfig::obligatorio => false,
				FormElementoConfig::valor_default => kernel::persona()->get_id_docente(),
				FormElementoConfig::clase_css => 'span12'
			),
			function ($id) {
				if (!$id) return 'Todos';
				$docente_info = catalogo::consultar('docente', 'datos_basicos', array('docente' => $id));
				return $docente_info['apellido'] . ', ' . $docente_info['nombres'] . ' (' . $docente_info['legajo'] . ')';
			},
			kernel::vinculador()->crear('inscriptos_examenes', 'buscar_docente')

		));

		//$fabrica->elemento_decorar_boton_limpiar($elemento, 'limpiar_docente');

		$form->add_elemento($fabrica->elemento_select('sede', $this->get_ubicaciones(), array(
			FormElementoConfig::label => ucfirst(kernel::traductor()->trans("form_filtro_ubicacion")),
			FormElementoConfig::filtro => validador::TIPO_INT,
			FormElementoConfig::obligatorio => false,
		)));
		
		$fecha_desde = new \DateTime();
		$fecha_desde->sub(new \DateInterval('P1M'));
		$form->add_elemento($fabrica->elemento_fecha('desde', array(
			FormElementoConfig::label => ucfirst(kernel::traductor()->trans("form_filtro_fecha_desde")),
			FormElementoConfig::filtro => validador::TIPO_DATE,
			FormElementoConfig::obligatorio => true,
			FormElementoConfig::valor_default => $fecha_desde->format("d/m/Y"),
			
		)));
		
		$fecha_hasta = new \DateTime();
		$fecha_hasta->add(new \DateInterval('P1M'));
		$form->add_elemento($fabrica->elemento_fecha('hasta', array(
			FormElementoConfig::label => ucfirst(kernel::traductor()->trans("form_filtro_fecha_hasta")),
			FormElementoConfig::filtro => validador::TIPO_DATE,
			FormElementoConfig::obligatorio => true,
			FormElementoConfig::valor_default => $fecha_hasta->format("d/m/Y"),
		)));
		
		$form->add_elemento($fabrica->elemento_checkbox('mesas_con_inscriptos', [
			FormElementoConfig::label => ucfirst(kernel::traductor()->trans("inscriptos_examenes.filtro.mesas_con_inscriptos")),
			FormElementoConfig::filtro => validador::TIPO_TEXTO,
			FormElementoConfig::obligatorio => false,
			FormElementoConfig::checked_value => \comunes::si,
			FormElementoConfig::unchecked_value => \comunes::no,
		]));
		
	}


	/**
	 * Por defecto los filtros de guarani se basan en el layout grilla. Se debe configurar en este metodo para poder
	 * instanciar el mismo
	 */
	function get_configuracion_layout_grilla()
	{

		// ------------------------   DATOS ACTA   ---------------------------
		return array(
			array(
				//'grupo'		=>	'datos_acta',
				'titulo' => ucfirst(kernel::traductor()->trans('filtro')), //todo - pasar a mensajes
				'filas' => array(
					array('desde' => array('span' => 6), 'hasta' => array('span' => 6)),
					array('turnos' => array('span' => 6), 'actividad' => array('span' => 6)),
					array('docente' => array('span' => 6), 'sede' => array('span' => 6)),
					array('mesas_con_inscriptos' => array('span' => 6)),
				),
			),

		);


	}
}
