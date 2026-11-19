<?php
namespace siu\operaciones\admin_actas_examen_fc;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\extension_kernel\formularios\guarani_form;
use siu\modelo\_comun\comunes;
use siu\operaciones\admin_actas_examen_fc\acta\form_acta;

class pagelet_acta extends pagelet
{
	/**
	 * @var form_acta
	 */
	protected $form_builder;

	const FORM_ERROR_VALIDACION = 'form_error_validacion';
	const FORM_ERROR_NRO_ACTA = 'form_error_nro_acta';
	const FORM_GUARDADO = 'form_guardado';

	function get_nombre()
	{
		return 'acta';
	}

	function get_js_files()
	{
		$archivos = parent::get_js_files();
		$archivos = array_merge($archivos, $this->get_vista_form()->get_js_files());
		$archivos[] = kernel::vinculador()->vinculo_recurso("js/lib/plugin.form.ml.js");
                $archivos[] = kernel::vinculador()->vinculo_recurso("js/utilidades.js");
		return $archivos;
	}

	/**
	 * @return guarani_form
	 */
	function get_form($id_html = null)
	{
		return $this->get_form_builder()->get_formulario();
	}

	function get_vista_form()
	{
		return $this->get_form_builder()->get_vista();
	}

	function get_form_builder()
	{
		if (!isset($this->form_builder)) {
			$this->form_builder = kernel::localizador()->instanciar('operaciones\admin_actas_examen_fc\acta\form_acta');
			$this->form_builder->set_id_acta($this->controlador->id_acta);
			$this->form_builder->set_hash_id_acta($this->controlador->hash);
		}
		return $this->form_builder;
	}

	protected function tiene_inscripciones($id_acta, $id_instancia)
	{
		return $this->controlador->modelo()->hay_alumnos($id_acta, $id_instancia);
	}

	function prepare()
	{
		$form = $this->get_form();
		
		$this->add_var_js('url_actual', kernel::vinculador()->crear(kernel::ruteador()->get_id_operacion()));
		$this->add_var_js('url_agregar_docente', kernel::vinculador()->crear(kernel::ruteador()->get_id_operacion(), 'agregar_docente'));
		$this->add_var_js('url_mostrar_nro_acta', kernel::vinculador()->crear(kernel::ruteador()->get_id_operacion(), 'mostrar_nro_acta'));
		$this->add_var_js('url_buscar_actividades', kernel::vinculador()->crear(kernel::ruteador()->get_id_operacion(), 'buscar_actividades'));
		
		$this->add_var_js('modo_edicion', $this->controlador->hay_var('id_acta'));

		$this->add_var_js('msg_valor', kernel::traductor()->trans('admin_actas_examen_fc.valor'));
		$this->add_var_js('msg_descripcion', kernel::traductor()->trans('admin_actas_examen_fc.descripcion'));
		$this->add_var_js('msg_resultado', kernel::traductor()->trans('admin_actas_examen_fc.resultado'));
		$this->add_var_js('msg_escala_vacia', kernel::traductor()->trans('admin_actas_examen_fc.msg_escala_vacia'));
		$this->add_var_js('msg_sin_instancia_activa', kernel::traductor()->trans('admin_actas_examen_fc.msg_sin_instancia_activa'));
		$this->add_var_js('msg_docente_existe', kernel::traductor()->trans('admin_actas_examen_fc.msg_docente_existe'));
		$this->add_var_js('msg_docentes_maximo', kernel::traductor()->trans('admin_actas_examen_fc.msg_docentes_maximo'));
		$this->add_var_js('msg_hora_invalida', kernel::traductor()->trans('admin_actas_examen_fc.msg_hora_invalida'));
		$this->add_var_js('msg_eliminar_docente', kernel::traductor()->trans('800_confirma_sacar_docente_acta'));
		$this->add_var_js('msg_confirmacion_acta', kernel::traductor()->trans('admin_actas_examen_fc.confirmacion_acta'));
		$this->add_mensaje_js('msg_campo_obligatorio', kernel::traductor()->trans('form_validacion_error.vacio'));
		$this->add_mensaje_js('msg_docentes_sin_rol', kernel::traductor()->trans('admin_actas_examen_fc.msg_docentes_sin_rol'));
		$this->add_mensaje_js('msg_docentes_sin_rol_asignado', kernel::traductor()->trans('admin_actas_examen_fc.msg_docentes_sin_rol_asignado'));
		$this->add_mensaje_js('msg_nro_acta', kernel::traductor()->trans('admin_actas_examen_fc.nro_acta'));
		$this->add_mensaje_js('msg_campo_actividad_obligatorio', kernel::traductor()->trans('admin_actas_examen_fc.msg_campo_obligatorio', ['%campo%' => 'Actividad']));
		$this->add_mensaje_js('msg_campo_nro_acta_obligatorio', kernel::traductor()->trans('admin_actas_examen_fc.msg_campo_obligatorio', ['%campo%' => 'Nro. Acta']));
		$this->add_mensaje_js('msg_campo_anio_academico_obligatorio', kernel::traductor()->trans('admin_actas_examen_fc.msg_campo_obligatorio', ['%campo%' => 'Año Académico']));
		$this->add_mensaje_js('msg_campo_ubicacion_obligatorio', kernel::traductor()->trans('admin_actas_examen_fc.msg_campo_obligatorio', ['%campo%' => 'Ubicación']));

		$cant_meses_sugeridos_antes_fecha_examen = $this->controlador->modelo()->get_cant_meses_sugeridos_antes_fecha_examen();

		$this->add_var_js('cant_meses_sugeridos_antes_fecha_examen', $cant_meses_sugeridos_antes_fecha_examen);

		$cant_meses_antes_txt_msg = $cant_meses_sugeridos_antes_fecha_examen;
		$cant_meses_antes_txt_msg .= ($cant_meses_sugeridos_antes_fecha_examen == 1) ? ' mes' : ' meses';

		$this->add_var_js('msg_fecha_sugerencia', kernel::traductor()->trans('admin_actas_examen_fc.fecha_sugerencia', array(
					'%cant_meses_antes%' => $cant_meses_antes_txt_msg,
		)));

		$operacion = kernel::ruteador()->get_id_operacion();
		$this->add_var_js('detalle_escala_url', kernel::vinculador()->crear($operacion, 'detalle_escala'));

		switch ($this->estado) {
			case self::FORM_ERROR_VALIDACION:
				$form->set_hubo_error(true);
				kernel::renderer()->add_to_ajax_response('info_estado', array(
					'mensaje' => kernel::traductor()->trans('admin_actas_examen_fc.guardado_datos_error'),
					'exitoso' => false
				));
				break;
			case self::FORM_ERROR_NRO_ACTA:
				$form->set_hubo_error(true);
				kernel::renderer()->add_to_ajax_response('info_estado', array(
					'mensaje' => kernel::traductor()->trans('admin_actas_examen_fc.nro_acta_obligatorio'),
					'exitoso' => false
				));
				break;
			case self::FORM_GUARDADO:
				kernel::renderer()->add_to_ajax_response('info_estado', array(
					'mensaje' => kernel::traductor()->trans('admin_actas_examen_fc.guardado_datos_exitoso'),
					'exitoso' => true
				));
				break;
			default:
				$form->inicializar();
				$datos = array();
				if ($this->controlador->hay_var('id_acta')) {
					// Se seleccionó un acta en la carga de notas de examen
					$id_acta = $this->controlador->get_var('id_acta');
					$hash = $this->controlador->get_var('hash');
					$datos = $this->controlador->get_datos_completos_acta($id_acta);
					$this->add_var_js('es_acta_examen_fc', $datos['datos_acta']['mesa_en_turno_examen'] == comunes::no);
					$this->data['url_editar_volver'] = kernel::vinculador()->crear('impresion_actas_examen', $hash);
				} else {
					// Creación de un acta nueva
					$datos['instancias'] = $this->controlador->modelo()->info__acta_instancias();
					$datos['docentes'][0] = array(
						'docente' => kernel::persona()->get_id_docente(),
						'legajo' => kernel::persona()->get_legajo_docente(),
						'apellido_nombre' => kernel::persona()->get_nombre(),
					);
					$this->add_var_js('es_acta_examen_fc', true);
				}
				kernel::log()->add_debug('########  SE SETEAN LOS DATOS DEL FORM ACTAS CON:  ######', $datos);
				$form->set_datos($datos);
				// Se bloquean filas activas y que tengan alumnos
				$campos = $form->get_campos();
				if ($this->controlador->hay_var('id_acta')) {
					$actividad = $campos['actividad_nombre'];
					$escala_nota_defecto = $this->controlador->modelo()->get_escala_nota_defecto($datos['datos_acta']['actividad']);
					$actividad->set_data_html5('escala_defecto', $escala_nota_defecto);
				}

				$ml = $campos['instancias'];
				$filas = $ml->get_filas();
				$msg_fila_deshabilitada = kernel::traductor()->trans('800EXA_no_modificar_escala');
				foreach ($filas as $fila) {
					if ($fila->es_template())
						continue;
					$columnas = $fila->get_columnas();
					if (isset($id_acta)) {
						if ($columnas['activo']->get_valor() == "S" && $this->tiene_inscripciones($id_acta, $columnas['instancia']->get_valor())) {
							$columnas['escala_nota']->set_disabled(true);
							$columnas['activo']->set_disabled(true);
							$fila->set_titulo($msg_fila_deshabilitada);
						}
					}
				}
		}

		$form->activar_csrf($this->generar_csrf()); //se activa en la vista.
		$this->add_form($form);
	}

}