<?php
class form_vincular extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------


	function extender_objeto_js()
	{
		echo " {$this->objeto_js}.set_evento(new evento_ei('curso'));";
		echo " {$this->objeto_js}.evt__curso__procesar = function(respuesta)
		{
		var curso = this.ef('curso').get_estado();
		this.ef('crear_grupo').set_solo_lectura(false);
		$( 'input#ef_form_89000025_form_vincularcrear_grupo' ).prop('checked', false);
		if (this.ef('curso').tiene_estado()){
			this.controlador.ajax('validar_curso_grupo', curso, this, this.respuesta_validacion_curso);
		}	
		};"
				. "{$this->objeto_js}.respuesta_validacion_curso = function(respuesta)
				{
						if (respuesta) {
							$( 'input#ef_form_89000025_form_vincularcrear_grupo' ).prop('checked', true);
							this.ef('crear_grupo').set_solo_lectura(true);
						}
					
		}"
		;
	}

}
?>