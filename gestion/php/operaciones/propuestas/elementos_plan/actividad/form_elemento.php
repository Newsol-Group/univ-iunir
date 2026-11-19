<?php
class form_elemento extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__entidad_subtipo__procesar = function(es_inicial)
		{
			var subtipo = this.ef('entidad_subtipo').get_estado();
			if (subtipo != ".elemento::subtipo_actividad_extracurricular." && subtipo != 'nopar') {
				this.controlador.dep('materia').mostrar();
				this.ef('disponible_para').mostrar();
				this.set_obligatoriedad_escala_notas(true);
				if (! (this.controlador.dep('extracurricular')== undefined)) {
					this.controlador.dep('extracurricular').ocultar();
				}
				return;
			} 
			
			if (subtipo == ".elemento::subtipo_actividad_extracurricular.") {
				this.controlador.dep('extracurricular').mostrar();
				this.ef('disponible_para').ocultar();	
				if (! (this.controlador.dep('materia')== undefined)) {
					this.controlador.dep('materia').ocultar();
					this.set_obligatoriedad_escala_notas(false);
				}					
				return;		
			}
			
			if (! (this.controlador.dep('materia') == undefined)) {
				this.controlador.dep('materia').ocultar();
				this.set_obligatoriedad_escala_notas(false);
			}
			if (! (this.controlador.dep('extracurricular') == undefined)) {
				this.controlador.dep('extracurricular').ocultar();
			}	
		}
		
		{$this->objeto_js}.set_obligatoriedad_escala_notas = function(obligatorio)
		{
			this.controlador.dep('materia').ef('escala_nota_cursada_regular').set_obligatorio(obligatorio);
			this.controlador.dep('materia').ef('escala_nota_cursada_promocion').set_obligatorio(obligatorio);
			this.controlador.dep('materia').ef('escala_nota_equivalencia').set_obligatorio(obligatorio);
			this.controlador.dep('materia').ef('escala_nota_examen').set_obligatorio(obligatorio);
		}
		
		{$this->objeto_js}.evt__estado__procesar = function(es_inicial)
		{
			if (this.ef('estado').get_estado() == '".elemento::estado_activo."') {
				this.ef('disponible_para').activar();
			} else {
				this.ef('disponible_para').set_estado('".elemento::para_mesas_de_examen_y_comisiones."');
				this.ef('disponible_para').desactivar();
			}
		}
		
		";
	}

}
?>