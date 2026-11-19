<?php
class form_plan_personalizado extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table><tr><td colspan=2>';
		$this->generar_html_ef('propuesta');
		echo '</td></tr><tr><td colspan=2>';
		$this->generar_html_ef('persona');
		echo '</td></tr><tr><td>';
		$this->generar_html_ef('ubicacion');
		echo '</td><td>';
		$this->generar_html_ef('modalidad', '130px');
		echo '</td></tr><tr><td>';
		$this->generar_html_ef('nombre');
		echo '</td><td>';
		$this->generar_html_ef('codigo');
		echo '</td></tr><tr><td>';
		$this->generar_html_ef('cobrable');
		echo '</td></tr><tr><td>';
		$this->generar_html_ef('documento_alta');
		echo '</td><td>';
		$this->generar_html_ef('fecha_entrada_vigencia', '130px');
		if ($this->existe_ef('convenio')) {
			echo '</td></tr><tr><td colspan=2>';
			$this->generar_html_ef('convenio');
		}
		echo '</td></tr></table>';
		echo '<fieldset><legend>Período de Inscripción</legend><table width=100%><tr><td>';
		$this->generar_html_ef('anio_academico', '150px');
		echo '</td><td>';
		$this->generar_html_ef('periodo_insc', '170px');
		echo '</td></tr></table>';
		echo "</fieldset>";
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$msj_periodo = guarani_mensajes::get('800MAT_sin_pi_preinscripcion');

		echo "
		
		//---- Validacion general ----------------------------------

		{$this->objeto_js}.evt__validar_datos = function()
		{
			if (this.ef('propuesta').tiene_estado() && this.ef('modalidad').tiene_estado() &&
				this.ef('ubicacion').tiene_estado() && this.ef('anio_academico').tiene_estado() &&
				!this.ef('periodo_insc').tiene_estado()) {
				notificacion.agregar('$msj_periodo');
				return false;
			}
			return true;
		}

		//---- Procesamiento de EFs --------------------------------        
		
		{$this->objeto_js}.evt__persona__procesar = function(es_inicial)
		{
			//Cuando el usuario modifica el ef del Alumno asociado al Plan, si el nombre de Plan no tiene estado
			//se setea un nombre default.
			if (!es_inicial) {
				if (this.ef('persona').tiene_estado() && !this.ef('nombre').tiene_estado()) {
					// Dato defecto para el Nombre del Plan.
					var params = [];
					params['persona'] = this.ef('persona').get_estado();            
					this.controlador.ajax('nombre_plan_defecto', params, this, this.respuesta_nombre_plan_defecto);
				}
			}
		}
		
		{$this->objeto_js}.respuesta_nombre_plan_defecto = function(respuesta) 
		{
			if (typeof respuesta == 'object') {
				this.ef('nombre').set_estado(respuesta['nombre']);
			}
		}
		
		/**
			* Ante la seleccion de un Convenio, se setean los datos de Documento y Fecha de Alta del Plan Personalizado, 
			* con los datos del mismo.
			*/
		{$this->objeto_js}.evt__convenio__procesar = function(es_inicial)
		{    
			if (!es_inicial && this.ef('convenio').tiene_estado()) {
				var params = [];
				params['convenio'] = this.ef('convenio').get_estado();            
				//Si el usuario seleccionó o cambió el convenio se realiza una consulta ajax para traer sus datos asociados y setearlos.
				this.controlador.ajax('cambio_convenio', params, this, this.respuesta_cambio_convenio);
			}
		}
		
		{$this->objeto_js}.respuesta_cambio_convenio = function(respuesta) 
		{
			if (respuesta['documento']) {
			//    if (!this.controlador.dep('form_plan_personalizado').ef('documento_alta').tiene_estado()) {
					this.controlador.dep('form_plan_personalizado').ef('documento_alta').set_estado(respuesta['documento'],respuesta['doc_desc']);
			//    } 
			}
			if(respuesta['fecha']) {
			//    if (!this.controlador.dep('form_plan_personalizado').ef('fecha_entrada_vigencia').tiene_estado()) {
					this.controlador.dep('form_plan_personalizado').ef('fecha_entrada_vigencia').set_estado(respuesta['fecha']);
			//    } 
			} 
		}
		

		{$this->objeto_js}.validar = function()
		{    
			if (!this.controlador.get_validar_todo()) {
				return true;
			}
			var ok = true;
			var validacion_particular = 'evt__validar_datos';
			if(this._evento && this._evento.validar) {
					if (existe_funcion(this, validacion_particular)) {
						ok = this[validacion_particular]();        
					}
					for (id_ef in this._efs) {
						ok = this.validar_ef(id_ef) && ok;
					}
			} else {
				this.resetear_errores();
			}

			if (!ok) {
					this.reset_evento();
			}
			return ok;
			};
		
		";
	}

}
?>