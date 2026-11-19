<?php
/**
 * 
 *
 * @package 	Egresados
 * @subpackage 	Actualizaciones
 * 
 */

use SIU\DocumentosCli\Entities\Autorizacion;

class form_formularios extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
        echo "<div id='js-formulario-wrapper'>";
        
            echo "<div style='float: left;'>";
                $this->generar_html_ef('formulario');
            echo "</div>";
            
            echo "<div style='float: left; padding-top: 3px; margin-left: 5px;'>";
                echo "<a id='js-formulario-ver' href='#'>Ver</a>";
            echo "</div>";
            
            echo "<div style='clear: both;'></div>";
        echo "</div>";
        
        // Obtengo los links de la impresión de los formularios
        $linksImpresionFormularios = $this->controlador()->cn()->getLinksImpresionFormularios();
        
        foreach ($linksImpresionFormularios as $formulario => $link) {
            echo "<input type='hidden' id='js-link-formulario-{$formulario}' value='{$link}'>"; 
        }
        
		$this->generar_html_ef('modelo_firma');
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
        
        $modelo_autorizacion_simple = Autorizacion::MODELO_SIMPLE;
        $modelo_autorizacion_mutiple_cadena = Autorizacion::MODELO_MULTIPLE_CADENA;
        
		echo "

		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__formulario__procesar = function(es_inicial)
		{
            // Obtengo el formulario seleccionado
            var formulario = this.ef('formulario').get_estado();
            // Obtengo el link de la impresión del formulario
            var link = $('#js-link-formulario-'+formulario).val();
            
            // Si no se puede obtener el link oculto el botón 'Ver'
            if (typeof link == 'undefined') {
                $('#js-formulario-ver').attr('href', '#').prop('href', '#').hide();
            } else {// Si puedo obtener el link lo seteo al botón 'Ver'
                $('#js-formulario-ver').attr('href', link).prop('href', link).show();
            }
            
		}
        
        // Si se modifica también adaptar en 'php/operaciones/egresados/actualizaciones/diploma_analitico_digital/form_modelo_firma.php'
		{$this->objeto_js}.evt__modelo_firma__procesar = function(es_inicial)
		{
            // Obtengo el modelo de firma seleccionado
            var modelo_firma = this.ef('modelo_firma').get_estado();
            
            // Si Modelo de Firma es 'Simple'
            if (modelo_firma == '$modelo_autorizacion_simple') {
                // Oculto la botonera de ml_firmantes_seleccionados
                $('#cuerpo_js_form_89000058_ml_firmantes_seleccionados .ei-ml-botonera').hide();
                // Oculto la columna con el orden
                $('#cuerpo_js_form_89000058_ml_firmantes_seleccionados tbody .ei-ml-fila-numero').hide();
            // Si Modelo de Firma es 'Cadena / Escalonada'
            } else if (modelo_firma == '$modelo_autorizacion_mutiple_cadena') {
                // Muestro la botonera de ml_firmantes_seleccionados
                $('#cuerpo_js_form_89000058_ml_firmantes_seleccionados .ei-ml-botonera').show();
                // Muestro la columna con el orden
                $('#cuerpo_js_form_89000058_ml_firmantes_seleccionados tbody .ei-ml-fila-numero').show();
            }
		}
        
		";
	}
}