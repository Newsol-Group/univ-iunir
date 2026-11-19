<?php

namespace siu\operaciones\evaluaciones;

use \SIU\Chulupi\kernel;
use SIU\Chulupi\util\modelo\DecoradorIds;
use \siu\modelo\datos\catalogo;

class pagelet_editar_evaluacion extends \SIU\Chulupi\interfaz\pagelet
{
	function get_nombre()
	{
		return 'editar_evaluacion';
	}

	function get_js_files()
	{
		$archivos = parent::get_js_files();
		$archivos = array_merge($this->get_vista_form()->get_js_files(), $archivos);
		return $archivos;
	}

	/**
	 * @return \siu\extension_kernel\formularios\guarani_vista_form
	 */
	function get_vista_form()
	{
		$vista = $this->controlador->get_vista_form();
		return $vista;
	}

	function prepare()
	{
		$form = $this->controlador->get_form();
		$this->data['form'] = $form;

		$params_consulta = array(
			'anio_academico' => null,
			'docente' => kernel::persona()->get_id_docente(),
			'periodo_lectivo' => null,
			'actividad' => null,
			'ubicacion' => null,
		);
		$lista_comisiones = catalogo::consultar('docente', 'lista_comisiones', $params_consulta);
		foreach ($lista_comisiones as $comision) {
			if ($comision['comision'] == $this->controlador->params_comision['comision']) {
				$this->data['comision'] = $comision;
				break;
			}
		}

		$rs = $this->controlador->modelo()->info__evaluacion_cabecera($this->controlador->params_comision);
		$this->data['evaluacion']['CABECERA'] = $rs;
		$datos_form = array();
		$datos_form['fecha'] = $rs['evaluacion_fecha'];
		$datos_form['hora_inicio'] = $rs['evaluacion_hora_inicio'];
		$datos_form['hora_fin'] = $rs['evaluacion_hora_fin'];
		$datos_form['nombre'] = $rs['evaluacion_nombre'];
		$datos_form['descripcion'] = $rs['descripcion'];
		$datos_form['evaluacion_tipo'] = $rs['evaluacion_tipo'];
		$datos_form['visible_al_alumno'] = $rs['visible_al_alumno'];
		$datos_form['promediable'] = $rs['promediable'];
		$datos_form['escala_nota'] = $rs['escala_nota'];
		$datos_form['instancias'] = $this->controlador->modelo()->info__evaluacion_instancias($this->controlador->params_comision);

		$relacionadas = $this->controlador->modelo()->info__evaluaciones_relacionadas_para_checklist($rs['evaluacion']);
		$datos_form['eval_relacionadas'] = empty($relacionadas) ? '' : $relacionadas;

		$form->inicializar();
		$form->set_datos($datos_form);

		$id_comision = $this->controlador->params_comision[DecoradorIds::$nombre_columna_generada];
		$this->data['url_cancelar'] = $this->controlador->get_index_url();
		$this->add_var_js('url_actualizar_evaluacion', kernel::vinculador()->crear('evaluaciones', 'actualizar_evaluacion', $id_comision));

		$this->add_var_js('con_alumnos_cargados', false);
		if ($rs['cant_alumnos'] > 0) {
			$this->add_var_js('con_alumnos_cargados', true);
			$this->add_mensaje_js('fecha_evaluacion_no_modificable', kernel::traductor()->trans('fecha_evaluacion_no_modificable'));
		}
	}

}
?>