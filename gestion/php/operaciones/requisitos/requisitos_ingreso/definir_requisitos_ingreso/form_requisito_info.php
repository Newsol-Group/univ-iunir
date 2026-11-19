<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class form_requisito_info extends guarani_pers_ei_formulario
{
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function extender_objeto_js()
	{
		$es_requisito_documental	= toba_js::bool($this->controlador()->es_requisito_documental());
		$usa_repositorio_digital	= toba_js::bool($this->controlador()->usa_repositorio_digital());
		
		echo "
			//---- Procesamiento de EFs ------------------------------------------------------
			
			var es_requisito_documental = $es_requisito_documental;
			var usa_repositorio_digital = $usa_repositorio_digital;
				
			function manejador_doc_digital_obligatorio_preinscripcion()
			{
				if (es_requisito_documental && usa_repositorio_digital && this.ef('visible_en_preinscripcion').get_estado() == '".comunes::si."') {
					this.ef('doc_digital_obligatorio_preinscripcion').mostrar(true, false);
				} else {
					this.ef('doc_digital_obligatorio_preinscripcion').ocultar(false);
				}
				
				return true;
			}
				
			{$this->objeto_js}.evt__visible_en_preinscripcion__procesar = function(es_inicial)
			{
				if (es_requisito_documental) {
					this.ef('visible_en_preinscripcion').mostrar(true, false);
				} else {
					this.ef('visible_en_preinscripcion').ocultar(false);
				}
				
			}
			
			{$this->objeto_js}.evt__doc_digital_obligatorio_preinscripcion__procesar = manejador_doc_digital_obligatorio_preinscripcion;
			
			//---- Validacion de EFs -----------------------------------

			{$this->objeto_js}.evt__visible_en_preinscripcion__validar = manejador_doc_digital_obligatorio_preinscripcion;
		
		";
	}
	
}
