<?php
class form_conf_periodo extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		$this->generar_html_ef('nombre');
		$this->generar_html_ef('modalidad');
		$this->generar_html_ef('ubicacion');

		echo '<fieldset><legend>Fechas</legend><table width=100%><tr><td>';
		$this->generar_html_ef('fecha_inicio', '60px');
		echo '</td></tr><tr><td>';
		$this->generar_html_ef('fecha_fin', '60px');
		echo '</td><td>';
		$this->generar_html_ef('fecha_tope_bajas', '200px');
		echo '</td></tr></table>';
		echo "</fieldset>";
		echo '<fieldset><legend>Habilitación</legend><table width=100%><tr><td>';
		$this->generar_html_ef('habilitado');
		echo '</td><td>';
		$this->generar_html_ef('tipo_alumno', '45px');
		echo '</td><td>';
		$this->generar_html_ef('habilitado_interfaz', '45px');
		echo '</td><td>';
		$this->generar_html_ef('tipo_cursado', '45px');
		echo '</td></tr></table>';
		echo "</fieldset>";
		
		$this->generar_html_ef('entidad_tipo_nuevo', '130px');
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__entidad_tipo_nuevo__validar = function(es_inicial)
		{
			this.submit();
			return true;
		}

		{$this->objeto_js}.evt__habilitado__procesar = function(es_inicial)
		{
			if (this.ef('habilitado').get_estado() == '".comunes::si."') {
				this.ef('habilitado_interfaz').activar();
				this.ef('tipo_alumno').activar();
				this.ef('tipo_cursado').activar();
			} else {
				this.ef('habilitado_interfaz').set_estado(".comunes::interfaz_todas.");
				this.ef('habilitado_interfaz').desactivar();
				this.ef('tipo_alumno').set_estado('".periodo_de_inscripcion::tipo_alumno_todos."');
				this.ef('tipo_alumno').desactivar();
				this.ef('tipo_cursado').set_estado('".periodo_de_inscripcion::tipo_cursado_todos."');
				this.ef('tipo_cursado').desactivar();
			}
		}
		";
	}

}
?>