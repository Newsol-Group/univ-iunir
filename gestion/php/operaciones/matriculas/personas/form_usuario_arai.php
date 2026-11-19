<?php
class form_usuario_arai extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$form_usuario = $this->controlador()->dep('form_usuario')->get_objeto_js();
                $msj_arai_identificador_sso_placeholder = guarani_mensajes::get('800_arai_identificador_sso_placeholder', [arai_usuarios::MIN_LENGTH_AUTOCOMPLETE_USUARIOS]);
                
		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__arai_identificador_sso__procesar = function(es_inicial)
		{
                    // Le cambio el mensaje del placeholder, ya que no permite (*) para ver todo
                    $('#cont_ef_form_14000160_form_usuario_araiarai_identificador_sso input.dhx_combo_input').attr('placeholder', '$msj_arai_identificador_sso_placeholder');
                    
                    if (this.ef('arai_identificador_sso').tiene_estado()) {
                        $form_usuario.ef('usuario').set_solo_lectura(true);
                    } else {
                        $form_usuario.ef('usuario').set_solo_lectura(false);
                    }
		}
		";
	}

}