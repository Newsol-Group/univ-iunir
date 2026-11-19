<?php

class form_periodo_destino extends guarani_pers_ei_formulario
{
    //-----------------------------------------------------------------------------------
    //---- JAVASCRIPT -------------------------------------------------------------------
    //-----------------------------------------------------------------------------------
    function extender_objeto_js()
    {
        echo "
		
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__copiar_bandas__procesar = function(es_inicial)
		{
                
                    // Si el combo '¿Copiar bandas horarias?' es 'Sí'
                    if (this.ef('copiar_bandas').tiene_estado() && this.ef('copiar_bandas').get_estado() == '1') {

                        // Activo el combo '¿Copiar espacios físicos de clases?'
                        this.ef('copiar_espacios').activar();

                    } else { // Si el combo '¿Copiar bandas horarias?' es 'No'

                        // Al combo '¿Copiar espacios físicos de clases?' lo seteo en 'No'
                        this.ef('copiar_espacios').set_estado('0');
                        
                        // Desactivo el combo '¿Copiar espacios físicos de clases?'
                        this.ef('copiar_espacios').desactivar();

                    }
                    
                }	
	    ";
    }
}