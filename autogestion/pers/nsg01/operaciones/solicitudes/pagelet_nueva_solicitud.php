<?php

namespace nsg01\operaciones\solicitudes;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use \siu\modelo\datos\catalogo;
use SIU\Chulupi\util\u;

class pagelet_nueva_solicitud extends pagelet
{

	function get_nombre()
	{
		return 'nueva_solicitud';
	}


	function prepare()
	{

		$parametros['persona'] = kernel::persona()->get_id();
		$es_perfil_cursos = kernel::persona()->es_perfil_cursos();

		if (!$es_perfil_cursos) {
			$alumno = kernel::persona()->get_id_alumno_activo();
			$parametros['alumno'] = array('alumno' => $alumno);
		}
		else {

			$propuestas = catalogo::consultar('cursos', 'get_propuestas_academica', array('persona' => $parametros['persona']));
			$pers_prop_param = ['persona' => $parametros['persona'], 'propuesta' => $parametros['propuesta']];

			foreach($propuestas as &$propuesta) {
				$propuesta['cursos'] = catalogo::consultar('cursos', 'get_cursos_academica', $pers_prop_param);
			}

			$parametros['propuesta'] = $propuesta['propuesta'];
			$alumno = catalogo::consultar('alumno', 'get_alumno', $pers_prop_param);
			$parametros['alumno'] = $alumno;
		}

		$this->data['constancias'] = $this->controlador->get_constancias();
		$this->data['url_volver'] = kernel::vinculador()->crear('solicitudes', 'index');
		$this->data['url_nueva_solicitud'] = kernel::vinculador()->crear('solicitudes', 'nueva_solicitud');
		$this->add_var_js('url_cant_copias', kernel::vinculador()->crear('solicitudes', 'get_cantidad_copias'));
		$this->add_var_js('url_get_info_solicitud', kernel::vinculador()->crear('solicitudes', 'get_info_solicitud'));

		$this->data['motivos_baja'] = catalogo::consultar('solicitudes', 'get_motivos_baja');
		$this->data['motivo_baja'] = $this->data['motivos_baja'][0]['motivo_baja'];
		$parametros['motivo_baja'] = $this->data['motivo_baja'];

		$this->data['tipos_beneficio'] = catalogo::consultar('solicitudes', 'get_tipos_beneficios');
		$this->data['tipo_beneficio'] = $this->data['tipos_beneficio'][0]['tipo_beneficio'];
		$parametros['tipo_beneficio'] = $this->data['tipo_beneficio'];

		$this->data['comisiones'] = catalogo::consultar('solicitudes', 'get_materias_inscripto', $parametros['alumno']);
		$this->data['comision'] = $this->data['comisiones'][0]['comision'];
		$parametros['comision'] = $this->data['comision'];

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
		$this->prepare_extensiones_tamanio_permitidos();
	}

	function prepare_extensiones_tamanio_permitidos()
	{
		// Extensiones permitidas de RDI
		$extensiones = \toba::consulta_php('co_configuracion', true)->get_rdi_extensiones_permitidas();
		$this->data['extensiones'] = implode(", ", $extensiones);
		
		// Tama�a m�ximo permitido RDI
		$upload_max_filesize = u::return_bytes(ini_get('upload_max_filesize'));
		$post_max_size = u::return_bytes(ini_get('post_max_size'));
		// https://stackoverflow.com/questions/7754133/php-post-max-size-overrides-upload-max-filesize
		$tamanio_max = ($upload_max_filesize < $post_max_size) ? $upload_max_filesize : $post_max_size;
		$this->data['tamanio_maximo_numero'] = $tamanio_max;
		$this->data['tamanio_maximo'] = u::formatBytes($tamanio_max);
		$this->add_var_js('tamanio_maximo', $tamanio_max);
	}
}