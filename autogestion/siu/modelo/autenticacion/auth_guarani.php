<?php

namespace siu\modelo\autenticacion;

use SIU\Chulupi\acceso\AuthenticationProvider;
use SIU\Chulupi\kernel;
use siu\errores\error_guarani;
use siu\modelo\datos\catalogo;
use siu\modelo\entidades\persona;

abstract class auth_guarani extends AuthenticationProvider {

	protected function get_clase_usuarios()
	{
		$clase = kernel::proyecto()->get_fuente_usuarios();
		if(!isset($clase)) {
			throw new error_guarani('Autenticacion: no se seteo el parámetro "fuente_usuarios" en la configuracion.');
		}
		return kernel::localizador()->instanciar($clase);
	}

	/**
	 * El kernel instancia la clase de persona. Se pasa a esta clase para realizar configuraciones necesarias, como
	 * la carga de perfiles. Puede ser que no esté autenticada (persona anonima)
	 * @param $persona
	 * @param $autenticada
	 */
	public function evt_persona_instanciada($persona, $autenticada)
	{
		$this->get_clase_usuarios()->registrar_login($this);
	}

	/*
	 * Ver ticket #34224 (https://redmine.siu.edu.ar/redmine/issues/34224)
	 */
	protected function registrar_propuesta_inicial_logout()
	{
		$persona = kernel::sesion()->get('__persona');
		$id_perfil_activo = kernel::sesion()->get(persona::SESION_ID_PERSONA_PERFIL);
		$id_propuesta_activa = kernel::sesion()->get('__persona_carrera');
		$propuesta_inicial = catalogo::consultar('persona', 'get_propuesta_inicial', ['persona' => $persona]);
		// Si es perfil Alumno, registra la propuesta de inicio por defecto si no tiene seteada ninguna.
		if (($id_perfil_activo == \persona::perfil_alumno) && is_null($propuesta_inicial)) {
			catalogo::consultar('persona', 'registrar_propuesta_inicial', ['persona' => $persona, 'propuesta' => $id_propuesta_activa]);
		}
                // Se limpia siempre la cache en el logout por el ticket #46184 (https://redmine.siu.edu.ar/redmine/issues/46184)
		catalogo::limpiar_cache('persona', 'get_propuesta_inicial', ['persona' => $persona]);
	}

}