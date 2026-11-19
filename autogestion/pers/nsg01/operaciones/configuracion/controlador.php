<?php

namespace nsg01\operaciones\configuracion;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use siu\guarani;
use siu\modelo\datos\catalogo;

class controlador extends \siu\operaciones\configuracion\controlador
{
	function accion__guardar_checks()
	{
		if (!kernel::persona()->es_perfil_docente()) {
			return parent::accion__guardar_checks();
		}

		$persona = guarani::persona()->get_id();
		$parametros = array('persona' => $persona);
		catalogo::limpiar_cache('persona', 'configuracion', $parametros);
		$configuracion = guarani::persona()->get_configuracion();
		$recibe_mail_mensajes = $this->validate_param(
			'recibe_mail_mensajes',
			'post',
			validador::TIPO_INT,
			array('default' => '0')
		);

		if ($recibe_mail_mensajes != $configuracion['recibe_mail_mensajes']) {
			guarani::persona()->actualizar_configuracion_checks($persona, $recibe_mail_mensajes);
		}

		$respuesta = array('cont' => 'Guardado', 'cod' => 1);
		$this->render_ajax('Guardado', $respuesta);
	}

	function accion__cambiar_mail()
	{
		if (kernel::persona()->es_perfil_docente()) {
			return;
		}

		return parent::accion__cambiar_mail();
	}
}

