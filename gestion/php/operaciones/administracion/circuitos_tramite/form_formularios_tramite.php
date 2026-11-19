<?php

/**
 *
 *
 * @package Administración
 * @subpackage Circuitos
 */
class form_formularios_tramite extends guarani_pers_ei_formulario
{

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$tipo_egreso = circuito_tramite::tipo_egreso;
		$tipo_tesis = circuito_tramite::tipo_tesis;
		$no = comunes::no;

		echo "
		
		//---- Procesamiento de EFs -----------------------------------


        {$this->objeto_js}.evt__tipo__procesar = function(es_inicial)
		{
            if (this.ef('tipo').tiene_estado() && this.ef('tipo').get_estado() == '$tipo_egreso') {
            	this.ef('incluir_en_expediente').activar();
            } else {
            	this.ef('incluir_en_expediente').cambiar_valor('$no');
            	this.ef('incluir_en_expediente').desactivar();
            }
        }
		
		";
	}

}
