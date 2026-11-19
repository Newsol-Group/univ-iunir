<?php
/**
 * 
 *
 * @package 	Egresados
 * @subpackage 	Actualizaciones
 * 
 */

use SIU\DocumentosCli\Entities\Autorizacion;

class form_modelo_firma extends guarani_pers_ei_formulario
{

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
        
        $modelo_autorizacion_simple = Autorizacion::MODELO_SIMPLE;
        $modelo_autorizacion_mutiple_cadena = Autorizacion::MODELO_MULTIPLE_CADENA;
        
		echo "

		//---- Procesamiento de EFs --------------------------------
		
		// Si se modifica también adaptar en 'php/operaciones/egresados/actualizaciones/adjuntar_documentacion_expediente_egreso/form_formularios.php'
		{$this->objeto_js}.evt__modelo_firma__procesar = function(es_inicial)
		{
            // Obtengo el modelo de firma seleccionado
            var modelo_firma = this.ef('modelo_firma').get_estado();
            
            // Si Modelo de Firma es 'Simple'
            if (modelo_firma == '$modelo_autorizacion_simple') {
                // Oculto la botonera de ml_firmantes_seleccionados
                $('#cuerpo_js_form_89000062_ml_firmantes_seleccionados .ei-ml-botonera').hide();
                // Oculto la columna con el orden
                $('#cuerpo_js_form_89000062_ml_firmantes_seleccionados tbody .ei-ml-fila-numero').hide();
            // Si Modelo de Firma es 'Cadena / Escalonada'
            } else if (modelo_firma == '$modelo_autorizacion_mutiple_cadena') {
                // Muestro la botonera de ml_firmantes_seleccionados
                $('#cuerpo_js_form_89000062_ml_firmantes_seleccionados .ei-ml-botonera').show();
                // Muestro la columna con el orden
                $('#cuerpo_js_form_89000062_ml_firmantes_seleccionados tbody .ei-ml-fila-numero').show();
            }
		}
        
		";
	}
}