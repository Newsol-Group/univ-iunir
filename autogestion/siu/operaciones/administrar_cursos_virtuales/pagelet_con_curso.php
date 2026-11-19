<?php
namespace siu\operaciones\administrar_cursos_virtuales;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_con_curso extends pagelet
{
	
	function get_nombre()
	{
		return 'con_curso';
	}

	function prepare()
	{
		$this->data = [];
		
		$params_comision = $this->controlador->get_params_comision();
		$comision = $params_comision['comision'];
		$this->controlador->modelo()->cargar_contexto_comision($comision);
		
		$datos_curso_virtual = $this->controlador->modelo()->get_datos_curso_virtual();
		
		$info_curso_virtual = $this->controlador->modelo()->get_info_curso_virtual();
		$this->data['info_curso_virtual'] = $info_curso_virtual;
		
		// Si se pudo obtener el curso desde Moodle
		if (!is_null($info_curso_virtual) && ($info_curso_virtual != -1)) {
			
			$fecha_inicio = null;
			// Si se definió la Fecha de Inicio del curso en Moodle
			if (!empty($info_curso_virtual['startdate'])) {
				$startdate = new \DateTime;
				$startdate->setTimestamp($info_curso_virtual['startdate']);
				$fecha_inicio = $startdate->format('d/m/Y');
			}
			
			$fecha_fin = null;
			// Si se definió la Fecha de finalización del curso en Moodle
			if (!empty($info_curso_virtual['enddate'])) {
				$enddate = new \DateTime;
				$enddate->setTimestamp($info_curso_virtual['enddate']);
				$fecha_fin = $enddate->format('d/m/Y');
			}
			
			$this->data['info_curso_virtual']['fecha_inicio'] = $fecha_inicio;
			$this->data['info_curso_virtual']['fecha_fin'] = $fecha_fin;
			
			$this->data['coinciden_docentes_moodle'] = $this->controlador->modelo()->coinciden_docentes_moodle();
			$this->data['coinciden_alumnos_moodle'] = $this->controlador->modelo()->coinciden_alumnos_moodle();
			
			$operacion = kernel::ruteador()->get_id_operacion();
			$hash_comision = $this->controlador->get_hash_comision();
			$this->add_var_js('url_desvincular', kernel::vinculador()->crear($operacion, 'desvincular', $hash_comision));
			$this->add_var_js('url_actualizar_alumnos', kernel::vinculador()->crear($operacion, 'actualizar_alumnos', $hash_comision));
			$this->add_var_js('url_actualizar_docentes', kernel::vinculador()->crear($operacion, 'actualizar_docentes', $hash_comision));
		
			$this->add_mensaje_js('confirmacion_desvincular', kernel::traductor()->trans('moodle.con_curso.confirmacion_desvincular'));
			$this->add_mensaje_js('confirmacion_actualizar_alumnos', kernel::traductor()->trans('moodle.con_curso.confirmacion_actualizar_alumnos'));
			$this->add_mensaje_js('confirmacion_actualizar_docentes', kernel::traductor()->trans('moodle.con_curso.confirmacion_actualizar_docentes'));
			$this->add_mensaje_js('aceptar', kernel::traductor()->trans('aceptar'));
			$this->add_mensaje_js('cancelar', kernel::traductor()->trans('cancelar'));
			
			$persona = kernel::persona()->get_id();
			$this->data['url_curso_moodle'] = $this->controlador->modelo()->get_moodle_obj()->getUrlCurso($persona, $datos_curso_virtual['id_curso_externo']);
			
		}
		
		
		$this->data['mensajes'] = [];
		// Si hay mensajes a mostrar
		if (kernel::sesion()->esta_seteada('mensajes_con_curso')) {
			$this->data['mensajes'] = kernel::sesion()->get('mensajes_con_curso');
			kernel::sesion()->borrar('mensajes_con_curso');
		}

		// Si se trata de una subcomisión
		$this->data['es_subcomision'] = $this->controlador->es_subcomision();
	}
}