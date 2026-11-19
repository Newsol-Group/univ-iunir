<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class form_ml_exceptuar extends guarani_pers_ei_formulario_ml
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

    function extender_objeto_js()
	{
		$formateo = new toba_formateo('html');
		$fecha = $formateo->formato_fecha(guarani_fecha::get_hoy());
        $datos = $this->get_datos();

		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__exceptuado__procesar = function(es_inicial, fila)
		{
        
            this.ef('propuesta_aspira').ir_a_fila(fila).ocultar();
            this.ef('requisito').ir_a_fila(fila).ocultar();
			var fecha = '$fecha';
			if({$this->objeto_js}.ef('exceptuado').ir_a_fila(fila).chequeado()) {
				if (!es_inicial) {
					{$this->objeto_js}.ef('fecha_excepcion').ir_a_fila(fila).set_estado(fecha);
				}
				
				{$this->objeto_js}.ef('fecha_excepcion').ir_a_fila(fila).set_solo_lectura(false);
				{$this->objeto_js}.ef('motivo_excepcion').ir_a_fila(fila).set_solo_lectura(false);
				{$this->objeto_js}.ef('documento_excepcion').ir_a_fila(fila).set_solo_lectura(false);
			} else {
				{$this->objeto_js}.ef('fecha_excepcion').ir_a_fila(fila).resetear_estado();
				{$this->objeto_js}.ef('motivo_excepcion').ir_a_fila(fila).resetear_estado();
				{$this->objeto_js}.ef('documento_excepcion').ir_a_fila(fila).resetear_estado();
				
				{$this->objeto_js}.ef('fecha_excepcion').ir_a_fila(fila).set_solo_lectura(true);
				{$this->objeto_js}.ef('motivo_excepcion').ir_a_fila(fila).set_solo_lectura(true);
				{$this->objeto_js}.ef('documento_excepcion').ir_a_fila(fila).set_solo_lectura(true);
			}
            
            var filas = {$this->objeto_js}.filas();
		";

        echo "
		}
		
		//---- Validacion de EFs -----------------------------------
		
		/**
		 * Si se exceptúa un requisito, se debe indicar la fecha.
		 */
		{$this->objeto_js}.evt__fecha_excepcion__validar = function(fila)
		{
			var requerido = this.ef('exceptuado').ir_a_fila(fila).chequeado();
			var fecha_excepcion = this.ef('fecha_excepcion').ir_a_fila(fila).get_estado();
			if (requerido && fecha_excepcion == '') {
				this.ef('fecha_excepcion').ir_a_fila(fila).set_error('es obligatorio.');
				return false;
			}
			return true;
		}
		
		/**
		 * Si se exceptúa un requisito, se debe indicar el motivo.
		 */
		{$this->objeto_js}.evt__motivo_excepcion__validar = function(fila)
		{
			var requerido = this.ef('exceptuado').ir_a_fila(fila).chequeado();
			var motivo_excepcion = this.ef('motivo_excepcion').ir_a_fila(fila).get_estado();
			if (requerido && motivo_excepcion == 'nopar') {
				this.ef('motivo_excepcion').ir_a_fila(fila).set_error('es obligatorio.');
				return false;
			}
			return true;
		}
		
		/**
		 * Si se exceptúa un requisito, se debe indicar el documento respaldatorio.
		 */
		{$this->objeto_js}.evt__documento_excepcion__validar = function(fila)
		{
			var requerido = this.ef('exceptuado').ir_a_fila(fila).chequeado();
			var documento_excepcion = this.ef('documento_excepcion').ir_a_fila(fila).get_estado();
			if (requerido && documento_excepcion == '') {
				this.ef('documento_excepcion').ir_a_fila(fila).set_error('es obligatorio.');
				return false;
			}
			return true;
		}
        
        ";
	}
    
}
?>