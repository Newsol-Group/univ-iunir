<?php

namespace siu\operaciones\mensajes;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\guarani;
use \siu\modelo\datos\catalogo;
use siu\modelo\transacciones\mensajes;

class pagelet_envio_mensajes extends pagelet
{
	var $mensaje = '';

	function ini()
	{
		
	}

	function get_nombre()
	{
		return 'envio_mensajes';
	}

	function get_js_files()
	{
		$archivos = parent::get_js_files();
		$archivos[] = kernel::vinculador()->vinculo_recurso("js/plugin_autocompletar.js");
		return $archivos;
	}

	/**
	 * @return \siu\modelo\transacciones\mensajes
	 */
	function modelo()
	{
		return $this->controlador->modelo();
	}

	function set_resultado($mensaje)
	{
		$this->mensaje = $mensaje;
	}

	function prepare()
	{
		$anio_academico = guarani::anios_academicos();
		$this->data['anio_academico'] = array();
		if (isset($anio_academico))
			$this->data['anio_academico'] = $anio_academico;

		$operacion = kernel::ruteador()->get_id_operacion();
		$this->data['url_envio'] = kernel::vinculador()->crear($operacion, 'enviar_mensaje');
		$this->data['url_enviados'] = kernel::vinculador()->crear($operacion, 'enviados');
		$this->data['url_recibidos'] = kernel::vinculador()->crear($operacion);
		$this->data['mensaje'] = $this->mensaje;
		$operacion = kernel::ruteador()->get_id_operacion();
		$this->add_var_js('url', kernel::vinculador()->crear($operacion, 'leer_mensaje'));
		$this->data['es_docente'] = kernel::persona()->perfil()->es('Docente');

		$fecha_actual = catalogo::consultar('sistema', 'fecha_actual');
		$default_fecha_desde = date("d/m/Y", strtotime($fecha_actual[0]));
		$default_fecha_hasta = date("d/m/Y", strtotime($fecha_actual[0] . " +7 day"));

		$this->add_var_js('default_fecha_desde', $default_fecha_desde);
		$this->add_var_js('default_fecha_hasta', $default_fecha_hasta);
		$this->data['default_fecha_desde'] = $default_fecha_desde;
		$this->data['default_fecha_hasta'] = $default_fecha_hasta;

		$this->data['mensaje_guardado'] = $this->controlador->get_var('mensaje_guardado');
		if ($this->controlador->hay_var('comision'))
			$this->add_var_js('comision', $this->controlador->get_var('comision'));

		$this->add_var_js('url_buscar_alumnos', kernel::vinculador()->crear($operacion, 'buscar_alumnos'));
		$this->add_var_js('url_combos', kernel::vinculador()->crear($operacion, 'combos'));

		$max = ini_get('upload_max_filesize');
		$this->data['max_filesize'] = is_numeric($max) ? ($max / 1048576) . 'MB' : $max;
		
		$this->add_var_js('url_subcomisiones', kernel::vinculador()->crear($operacion, 'subcomisiones'));
		$this->add_var_js('TODA_COMISION', mensajes::TODA_COMISION);
		$this->add_var_js('TODAS_SUBCOMISIONES', mensajes::TODAS_SUBCOMISIONES);
		$this->add_var_js('todas_msj', kernel::traductor()->trans('todas'));
		$this->add_var_js('error_subcomisiones', kernel::traductor()->trans('error_subcomisiones'));
		$this->data['TODA_COMISION'] = mensajes::TODA_COMISION;
		$this->data['TODAS_SUBCOMISIONES'] = mensajes::TODAS_SUBCOMISIONES;
		$this->data['todas_msj'] = kernel::traductor()->trans('todas');
	}

}
?>