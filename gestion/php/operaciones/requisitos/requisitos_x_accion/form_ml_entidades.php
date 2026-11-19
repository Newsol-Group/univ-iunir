<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos por Acción
 * 
 */
class form_ml_entidades extends guarani_pers_ei_formulario_ml
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$req_positivo = requisito::requisito;
		$req_negativo = requisito::not_requisito;
		
		echo "
		
		//---- Eventos ---------------------------------------------
		
		{$this->objeto_js}.evt__seleccion_todos = function()
		{
			var entidades = this.filas();
			for (id_fila in entidades) {
				this.ef('operador_not').ir_a_fila(entidades[id_fila]).set_estado('$req_positivo');
			}
			return false;
		}
		
		{$this->objeto_js}.evt__seleccion_ninguno = function()
		{
			var entidades = this.filas();
			for (id_fila in entidades) {
				this.ef('operador_not').ir_a_fila(entidades[id_fila]).resetear_estado();
			}
			return false;
		}
		
		";
	}
}
?>