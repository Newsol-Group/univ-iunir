<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class form_ml_prorrogar extends guarani_pers_ei_formulario_ml
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$formateo = new toba_formateo('html');
		$fecha = $formateo->formato_fecha(guarani_fecha::get_hoy());

		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__prorrogado__procesar = function(es_inicial, fila)
		{
			var fecha = '$fecha';
			if({$this->objeto_js}.ef('prorrogado').ir_a_fila(fila).chequeado()) {
				if (!es_inicial) {
					{$this->objeto_js}.ef('fecha_prorroga').ir_a_fila(fila).set_estado(fecha);
				}
				
				{$this->objeto_js}.ef('fecha_prorroga').ir_a_fila(fila).set_solo_lectura(false);
				{$this->objeto_js}.ef('documento_prorroga').ir_a_fila(fila).set_solo_lectura(false);
			} else {
				{$this->objeto_js}.ef('fecha_prorroga').ir_a_fila(fila).resetear_estado();
				{$this->objeto_js}.ef('documento_prorroga').ir_a_fila(fila).resetear_estado();
				
				{$this->objeto_js}.ef('fecha_prorroga').ir_a_fila(fila).set_solo_lectura(true);
				{$this->objeto_js}.ef('documento_prorroga').ir_a_fila(fila).set_solo_lectura(true);
			}
		}
		
		//---- Validacion de EFs -----------------------------------
		
		/**
		 * Si se exceptúa un requisito, se debe indicar la fecha.
		 */
		{$this->objeto_js}.evt__fecha_prorroga__validar = function(fila)
		{
			var requerido = this.ef('prorrogado').ir_a_fila(fila).chequeado();
			var fecha_prorroga = this.ef('fecha_prorroga').ir_a_fila(fila).get_estado();
			if (requerido && fecha_prorroga == '') {
				this.ef('fecha_prorroga').ir_a_fila(fila).set_error('es obligatorio.');
				return false;
			}
			return true;
		}
		
		/**
		 * Si se exceptúa un requisito, se debe indicar el documento respaldatorio.
		 */
		{$this->objeto_js}.evt__documento_prorroga__validar = function(fila)
		{
			var requerido = this.ef('prorrogado').ir_a_fila(fila).chequeado();
			var documento_prorroga = this.ef('documento_prorroga').ir_a_fila(fila).get_estado();
			if (requerido && documento_prorroga == '') {
				this.ef('documento_prorroga').ir_a_fila(fila).set_error('es obligatorio.');
				return false;
			}
			return true;
		}
		";
	}

}
?>