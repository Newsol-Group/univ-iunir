<?php

namespace siu\extension_kernel;

use SIU\Chulupi\kernel;
use SIU\Chulupi\interfaz\pagina as pagina_base;

class pagina extends pagina_base
{
	function get_titulo_completo()
	{
		$nombre = kernel::traductor()->trans('nombre_sistema');
		$titulo = $this->get_etiqueta('titulo');
		if (!empty($titulo)) {
			$nombre .= ' :: ' . $this->get_etiqueta('titulo');
		}
		return $nombre;
	}

	protected function get_contexto_template()
	{
		$contexto = parent::get_contexto_template();

		// Logo
		$logo = kernel::proyecto()->get_parametro('logo_pagina');
		$contexto['url_img_logo'] = kernel::vinculador()->vinculo_img($logo);

		$contexto['url_inicio'] = kernel::vinculador()->crear_home();
		$contexto['urls_preinscripcion_css'] = kernel::vinculador()->vinculo_css('preinscripcion');
		$contexto['personalizacion'] = kernel::proyecto()->get_personalizacion_activa();
		$contexto['personalizaciones'] = kernel::proyecto()->get_personalizaciones();
		$contexto['url_img_logo_celular'] = kernel::vinculador()->vinculo_recurso('img/logo-celular.png');
		$contexto['mash_personalizado'] = count($contexto['urls_preinscripcion_css']) > 1;
		$contexto['url_favicon'] = kernel::vinculador()->vinculo_img('favicon.ico');
		$contexto['version'] = kernel::traductor()->trans('version') . ' ' . kernel::proyecto()->get_version_sistema();

		if (!kernel::sesion()->abierta()) {
			return $contexto;
		}
		
		$contexto['persona']['email'] = kernel::persona()->get_mail();
		$contexto['persona']['url_logout'] = kernel::vinculador()->crear('acceso', 'logout');
		$contexto['es_produccion'] = kernel::proyecto()->get_produccion();
		$contexto['log_activo'] = kernel::log()->esta_activo();
		$contexto['barra_dev'] = kernel::proyecto()->get('barra_dev');
		$contexto['log_id'] = kernel::log()->get_id();
		$contexto['url_log'] = kernel::vinculador()->crear_sin_chequeo('logger');
		$contexto['url_log_info'] = kernel::vinculador()->crear_sin_chequeo('logger', 'ajax_info_log');
		$contexto['ga_params'] = kernel::proyecto()->get_ga_params();

		return $contexto;
	}

	function get_etiqueta($id)
	{
		if (isset($this->etiquetas[$id])) {
			return $this->etiquetas[$id];
		} else {
			return '';
		}
	}

}