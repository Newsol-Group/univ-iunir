<?php
/**
 *
 *
 * @package Administración
 */
class form_clave extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		// Ver "vincula_arai_usuarios" en "instalacion/instalacion.ini"
		if (toba::instalacion()->vincula_arai_usuarios()) {
			
			// Recupero la URL al perfil de SIU-Huarpe
			$atributos_usuario = toba::manejador_sesiones()->get_autenticacion()->get_atributos_usuario();
			$atributos_usuario['appLauncherData'] = ($atributos_usuario['appLauncherData'] ?? []);
			$appLauncherData = (empty($atributos_usuario['appLauncherData']))? [] : array_a_latin1(json_decode(current($atributos_usuario['appLauncherData']), true));
			$perfil_url = ($appLauncherData['perfil_url'] ?? '#');
			
			// Desactiva los efs para "Cambiar Contraseña"
			$this->desactivar_efs($this->get_nombres_ef());
			// Desactiva el botón "Guardar" para "Cambiar Contraseña"
			$this->evento('guardar')->anular();
			$html = '';
			$html .= '<div style="border: 1px solid black; padding: 10px;">';
			$html .= "<b>Para cambiar su contraseña puede hacerlo desde <a href='$perfil_url' target='_blank'>aquí.</a></b>";
			$html .= '</div>';
			echo $html;
		}
		parent::generar_layout();
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$mje_clave_confirmar = guarani_mensajes::get('800_clave_confirmar_incorrecta');

		echo "
		//---- Validacion de EFs -----------------------------------
		
		{$this->objeto_js}.evt__clave_confirmar__validar = function()
		{
			if (this.ef('clave').tiene_estado() &&
				this.ef('clave').get_estado() != this.ef('clave_confirmar').get_estado()) {
				this.ef('clave_confirmar').set_error('$mje_clave_confirmar');
				return false;
			}
			return true;
		}
		";
	}
}