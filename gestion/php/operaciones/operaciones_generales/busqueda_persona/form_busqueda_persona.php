<?php
class form_busqueda_persona extends guarani_pers_ei_formulario
{

	function generar_layout()
	{
		$this->generar_html_ef('apellidos');
		$this->generar_html_ef('nombres');
		echo "<fieldset title='Datos Documento'><legend>Datos Documento</legend>";
		$this->generar_html_ef('pais');
		$this->generar_html_ef('tipo_dni');
		$this->generar_html_ef('numero_dni');
		echo '</fieldset>';
	}


	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__tipo_dni__procesar = function(es_inicial)
		{
			var tipo_dni = this.ef('tipo_dni').get_estado();
		
			if (tipo_dni != 'nopar') {
				this.ef('numero_dni')._obligatorio = true;
			} else {
				this.ef('numero_dni')._obligatorio = false;
			}
		}
		";
	}


}
?>