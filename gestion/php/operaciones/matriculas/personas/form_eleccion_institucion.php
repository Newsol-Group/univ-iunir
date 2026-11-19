<?php
class form_eleccion_institucion extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		$this->generar_html_ef('como_conocio_institucion');
		echo '<hr/>';
		$this->generar_html_ef('insc_otra_institucion', '450px');
		$this->generar_html_ef('insc_otra_institucion_desc');
		echo '<hr/>';
		echo "<fieldset>
				<legend>
					<strong>Motivos por los que eligió esta Institución educativa</strong>
				</legend>";
				$this->generar_html_ef('mot_economico', '300px');
				$this->generar_html_ef('mot_prestigio', '300px');
				$this->generar_html_ef('mot_difusion', '300px');
				$this->generar_html_ef('mot_recomendacion_estudiantes', '300px');
				$this->generar_html_ef('mot_recomendacion_amigos', '300px');
				$this->generar_html_ef('mot_sistema_ingreso', '300px');
				$this->generar_html_ef('mot_ubicacion', '300px');
				$this->generar_html_ef('mot_otros_i', '300px');
		echo "</fieldset>";
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$si = comunes::si;
		
		echo "
		//---- Procesamiento de EFs --------------------------------
		{$this->objeto_js}.evt__insc_otra_institucion__procesar = function(es_inicial)
		{
			if (this.ef('insc_otra_institucion').tiene_estado()) {
				var insc_otra_institucion = this.ef('insc_otra_institucion').get_estado();
				if (insc_otra_institucion == '$si') {
					this.ef('insc_otra_institucion_desc').mostrar();
				} else {
					this.ef('insc_otra_institucion_desc').ocultar(true);
				}
			} else {
				this.ef('insc_otra_institucion_desc').ocultar(true);
			}
		}
		";
	}

}
?>