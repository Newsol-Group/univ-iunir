<?php
class pant_acceso_sistema extends guarani_ei_pantalla
{
	/**
	 * Permite modificar la forma en que se grafica la pantalla, por defecto un componente sobre el otro
	 */
	function generar_layout()
	{

            $this->dep('form_usuario')->generar_html();

            // Si vincula usuarios de SIU-Araí muestro el formulario pertinente
            if (toba::instalacion()->vincula_arai_usuarios()) {
                $this->dep('form_usuario_arai')->generar_html();
            }

            $this->generar_boton('desvincular_usuario_arai', true);
			if ($this->existe_dependencia('ml_perfiles')) {
				$this->dep('ml_perfiles')->generar_html();
			}
	}

}