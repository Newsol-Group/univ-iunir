<?php
class form_crear extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
                $nombre_maxleght = moodle::NOMBRE_COMPLETO_CURSO_MAXLENGTH;
                $nombre_abrev_maxleght = moodle::NOMBRE_CORTO_CURSO_MAXLENGTH;

                $msj_nombre_maxleght = guarani_mensajes::get('800_no_debe_exceder_cant_caracteres', [$nombre_maxleght]);
                $msj_nombre_abrev_maxleght = guarani_mensajes::get('800_no_debe_exceder_cant_caracteres', [$nombre_abrev_maxleght]);
                
		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__importar__procesar = function(es_inicial)
		{
			var importar = this.ef('importar').get_estado();
			
			// Si se seleccionó 'Importar Contenido de otro Curso'
			if (importar) {
				// Muestro 'Copiar contenido de'
				this.ef('copiar_de').set_obligatorio(true);
				this.ef('copiar_de').mostrar();
			} else { // sino
				// Oculto 'Copiar contenido de'
				this.ef('copiar_de').set_obligatorio(false);
				this.ef('copiar_de').ocultar();
			} 
		}
		
		{$this->objeto_js}.evt__fecha_fin__procesar = function(es_inicial)
		{
			// Si ingresó 'Fecha Fin' debe ingresar 'Fecha Inicio'
			if (this.ef('fecha_fin').tiene_estado()) {
				this.ef('fecha_inicio').set_obligatorio(true);
			} else { // sino la 'Fecha Inicio' pasa a ser opcional 
				this.ef('fecha_inicio').set_obligatorio(false);
			}
		}
                
		//---- Validacion de EFs -----------------------------------
		
		/**
		 * Validación puntual de un ef en el cliente
		 * Retornar true/false para controlar la validación
		 * Usar this.ef(id).set_error(mensaje) para mostrar un error contextual al campo
		 */
		{$this->objeto_js}.evt__nombre__validar = function()
		{
                    var ok = true;
                    var nombre = this.ef('nombre').get_estado();

                    if (nombre.length > $nombre_maxleght) {
                        this.ef('nombre').set_error('$msj_nombre_maxleght');
                        ok = false;
                    }

                    return ok;
		}

		/**
		 * Validación puntual de un ef en el cliente
		 * Retornar true/false para controlar la validación
		 * Usar this.ef(id).set_error(mensaje) para mostrar un error contextual al campo
		 */
		{$this->objeto_js}.evt__nombre_abrev__validar = function()
		{
                    var ok = true;
                    var nombre_abrev = this.ef('nombre_abrev').get_estado();

                    if (nombre_abrev.length > $nombre_abrev_maxleght) {
                        this.ef('nombre_abrev').set_error('$msj_nombre_abrev_maxleght');
                        ok = false;
                    }

                    return ok;
		}
                
		";
	}


}