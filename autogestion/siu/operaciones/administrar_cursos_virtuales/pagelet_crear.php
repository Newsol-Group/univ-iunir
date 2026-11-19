<?php
namespace siu\operaciones\administrar_cursos_virtuales;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_crear extends pagelet
{
	
	function get_nombre()
	{
		return 'crear';
	}

	function prepare()
	{
		
		$this->data = [];
		
		$operacion = kernel::ruteador()->get_id_operacion();
		$hash_comision = $this->controlador->get_hash_comision();
		$this->data['url_crear'] = kernel::vinculador()->crear($operacion, 'crear', $hash_comision);
		$this->add_var_js('url_volver', kernel::vinculador()->crear($operacion, 'home', $hash_comision));
		
		$params_comision = $this->controlador->get_params_comision();
		$comision = $params_comision['comision'];
		$this->controlador->modelo()->cargar_contexto_comision($comision);
		$datos_plataforma = $this->controlador->modelo()->get_datos_plataforma();
		$this->data['categorias'] = $this->controlador->modelo()->get_categorias_cursos_moodle();
		$this->data['categoria_default'] = $datos_plataforma['categoria_curso_default'];
		
		$this->data['cursos'] = $this->controlador->modelo()->get_cursos_moodle_de_docentes();
		
		$datos_comision = $this->controlador->modelo()->get_datos_comision();
		$this->data['nombre_default'] = "{$datos_comision['periodo_nombre']} - ({$datos_comision['elemento_codigo']}) {$datos_comision['elemento_nombre']} - {$datos_comision['comision_nombre']}";
		$this->data['nombre_abrev_default'] = "{$datos_comision['periodo_nombre']} - ({$datos_comision['elemento_codigo']}) {$datos_comision['elemento_nombre']} - {$datos_comision['comision_nombre']}";

                $this->data['props']['nombre']['maxlength'] = \moodle::NOMBRE_COMPLETO_CURSO_MAXLENGTH;
                $this->data['props']['nombre_abrev']['maxlength'] = \moodle::NOMBRE_CORTO_CURSO_MAXLENGTH;

		$fecha_inicio_dictado = new \DateTime($datos_comision['fecha_inicio_dictado']);
		$fecha_fin_dictado = new \DateTime($datos_comision['fecha_fin_dictado']);
		$this->data['fecha_inicio_default'] = $fecha_inicio_dictado->format('d/m/Y');
		$this->data['fecha_fin_default'] = $fecha_fin_dictado->format('d/m/Y');
		
		$url_calendar_img = kernel::vinculador()->vinculo_recurso('img/calendar.png');
		$this->add_var_js('url_calendar_img', $url_calendar_img);
		$this->add_mensaje_js('calenda_button_text', kernel::traductor()->trans('moodle.crear.seleccione_fecha'));
		
		$this->add_mensaje_js('confirmacion_salir', kernel::traductor()->trans('moodle.confirmacion_salir'));
		$this->add_mensaje_js('aceptar', kernel::traductor()->trans('aceptar'));
		$this->add_mensaje_js('cancelar', kernel::traductor()->trans('cancelar'));
		
		$this->data['mensajes'] = [];
		// Si hay mensajes a mostrar
		if (kernel::sesion()->esta_seteada('mensajes_crear')) {
			$this->data['mensajes'] = kernel::sesion()->get('mensajes_crear');
			kernel::sesion()->borrar('mensajes_crear');
		}
		
		$this->data['valores'] = [];
		// Si hay valores a repopular
		if (kernel::sesion()->esta_seteada('valores_campos')) {
			$this->data['valores'] = kernel::sesion()->get('valores_campos');
			kernel::sesion()->borrar('valores_campos');
		}
		
		$this->data['errores'] = [];
		// Si hay errores a mostrar
		if (kernel::sesion()->esta_seteada('errores_campos')) {
			$this->data['errores'] = kernel::sesion()->get('errores_campos');
			kernel::sesion()->borrar('errores_campos');
		}

		// Si se trata de una subcomisión
		$this->data['es_subcomision'] = $this->controlador->es_subcomision();
	}
}