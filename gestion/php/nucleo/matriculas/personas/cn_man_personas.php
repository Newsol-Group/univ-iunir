<?php
/**
 *
 *
 * @package		Matrícula
 * @subpackage	Personas
 * 
 */
class cn_man_personas extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'persona';
	}

	function get_listado($filtro)
	{
		if ($this->usa_perfil_de_datos()) {
			return toba::consulta_php('co_personas')->get_personas_perfil_de_datos($filtro);
		} else {
			return toba::consulta_php('co_personas')->get_listado($filtro, false);
		}
	}

	function usa_perfil_de_datos()
	{
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		$mat_personas_aplica_perfil = guarani::parametros()->get_valor('mat_personas_aplica_perfil');
		return (!empty($perfil_datos) && $mat_personas_aplica_perfil == comunes::si);
	}

	function guardar()
	{
		// Seteo de usuario defecto.
		$datos_persona = $this->entidad()->get_datos_personas();		
		if (!isset($datos_persona['usuario'])) {
			//Si usa arai voy a dar informacion de que no tiene usuario por defecto asignado
			if (toba::instalacion()->vincula_arai_usuarios()) {
				//Mostrar msj que no tiene usuario de arai asignado
				$mje = guarani_mensajes::get('800_usuario_no_asignado');
				$this->informar_msg($mje, 'info');
			}
		}
		return parent::guardar();
	}

}