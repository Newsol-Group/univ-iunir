<?php

namespace siu\operaciones\solicitudes;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_nueva_solicitud extends pagelet
{
	function get_nombre()
	{
		return 'nueva_solicitud';
	}

	function prepare()
	{
		$this->data['constancias'] = $this->controlador->get_constancias();
		$this->data['url_volver'] = kernel::vinculador()->crear('solicitudes', 'index');
		$this->data['url_nueva_solicitud'] = kernel::vinculador()->crear('solicitudes', 'nueva_solicitud');
		$this->add_var_js('url_cant_copias', kernel::vinculador()->crear('solicitudes', 'get_cantidad_copias'));
		$this->add_var_js('url_listado', kernel::vinculador()->crear('solicitudes', 'index'));
		$this->add_var_js('url_formulario_filtro', kernel::vinculador()->crear('solicitudes', 'get_formulario_filtro'));
		$this->add_var_js('url_actividad_anio', kernel::vinculador()->crear('solicitudes', 'get_actividad_anio'));
		$this->add_var_js('url_llamado_examen', kernel::vinculador()->crear('solicitudes', 'get_llamado_examen'));
		$this->add_var_js('url_turno_examen', kernel::vinculador()->crear('solicitudes', 'turno_examen'));
		$this->add_var_js('url_evaluacion_parcial', kernel::vinculador()->crear('solicitudes', 'get_evaluacion_parcial'));
		$this->add_var_js('url_buscar_periodo_lectivo', kernel::vinculador()->crear('solicitudes', 'buscar_periodo_lectivo'));
		$this->add_var_js('url_buscar_actividad_comisiones', kernel::vinculador()->crear('solicitudes', 'buscar_actividad_comisiones'));
		$this->add_var_js('url_buscar_inscripciones_comision_alumno', kernel::vinculador()->crear('solicitudes', 'inscripciones_comision_alumno'));
		$this->add_var_js('url_comisiones_de_actividad', kernel::vinculador()->crear('solicitudes', 'buscar_comisiones_de_actividad'));
		$this->add_var_js('url_seleccion_actividad', kernel::vinculador()->crear('solicitudes', 'seleccion_actividad'));
		$this->add_var_js('url_seleccion_actividad_clases', kernel::vinculador()->crear('solicitudes', 'seleccion_actividad_clases'));
		$this->add_var_js('url_seleccion_actividad_aprobada', kernel::vinculador()->crear('solicitudes', 'seleccion_cursos_aprobados'));
		$this->add_var_js('url_seleccion_clase', kernel::vinculador()->crear('solicitudes', 'seleccion_clase'));
		$this->add_var_js('nueva_solicitud_error', kernel::traductor()->trans('nueva_solicitud_error'));
		//mensajes
		$this->add_mensaje_js('msg_seleccionar_anio_academico', kernel::traductor()->trans('solicitudes.seleccionar_anio_academico'));
		$this->add_mensaje_js('msg_seleccionar_actividad', kernel::traductor()->trans('solicitudes.seleccionar_actividad'));
		$this->add_mensaje_js('msg_seleccionar_opcion', kernel::traductor()->trans('solicitudes.seleccionar_opcion'));
		$this->add_mensaje_js('msg_buscar', kernel::traductor()->trans('solicitudes.buscar'));
		$this->add_mensaje_js('msg_presentar', kernel::traductor()->trans('solicitudes.mensaje_presentar_obligatorio'));
		$this->add_mensaje_js('msg_constancia', kernel::traductor()->trans('solicitudes.mensaje_constanciar_obligatorio'));
		
	}
}