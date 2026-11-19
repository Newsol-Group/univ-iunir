<?php

namespace siu\extension_kernel;

use siu\preinscripcion;
use SIU\Chulupi\kernel;
use SIU\Chulupi\acceso\PersonaConDerechos as persona_con_derechos;
use SIU\Chulupi\acceso\FactoryPerfil as factory_perfil;
use siu\modelo\manejador_datos;

class persona extends persona_con_derechos
{
	protected $perfil;
	protected $id_usuario;
	protected $manejador_datos;

	function __construct($id_usuario)
	{
		$this->set_perfil_activo($this->get_perfil());
		$this->id_usuario = $id_usuario;
	}

	function get_perfil()
	{
		return $_SERVER['PERFIL'];
	}

	function get_id()
	{
		return $this->id_usuario;
	}

	function set_email($email)
	{
		kernel::sesion()->set('__preinscripcion_email', $email);
	}

	function get_mail()
	{
		return kernel::sesion()->get('__preinscripcion_email');
	}

	function get_locale()
	{
		return 'es_AR';
	}

	function get_perfiles()
	{
		return array($this->get_perfil());
	}

	function perfil()
	{
		return $this->perfil;
	}

	function set_perfil_activo($id_perfil)
	{
		$this->perfil = factory_perfil::instancia()->construir($id_perfil);
	}

	//--------------------------------------------------------------------------
	//---- Datos preinscripción ------------------------------------------------
	//--------------------------------------------------------------------------

	function get_id_persona()
	{
		$perfil = $this->get_perfil();
		if ($perfil == preinscripcion::perfil_alumno) {
			return $this->datos()->get_id_persona();
		}
		return null;
	}

	function get_nombre()
	{
		$perfil = $this->get_perfil();
		if ($perfil == preinscripcion::perfil_alumno) {
			$datos = $this->datos()->get_datos_preinscripcion();
			if ((!empty($datos['apellido'])) && (!empty($datos['nombres']))) {
				$nombre = $datos['apellido'] . ' ' . $datos['nombres'];
			} else {
				$nombre = '';
			}
			return $nombre;
		}
		return '';
	}

	/**
	 * 
	 * @return manejador_datos
	 */
	function datos()
	{
		if (!isset($this->manejador_datos)) {
			$this->manejador_datos = kernel::localizador()->instanciar('modelo\manejador_datos', $this->id_usuario);
		}
		return $this->manejador_datos;
	}

}
?>