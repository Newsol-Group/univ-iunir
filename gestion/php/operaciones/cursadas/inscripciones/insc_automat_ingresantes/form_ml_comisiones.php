<?php
class form_ml_comisiones extends guarani_pers_ei_formulario_ml
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$ubicacion = current($this->controlador()->get_seleccion('ubicacion'));
		$anio_academico = current($this->controlador()->get_seleccion('periodo_inscripcion_anio_academico'));

		echo "

		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__actividad__procesar = function(es_inicial,fila)
		{
			this.ef('actividad').ir_a_fila(fila).ocultar();
		}

		{$this->objeto_js}.evt__plan__procesar = function(es_inicial,fila)
		{
			this.ef('plan').ir_a_fila(fila).ocultar();
		}
		
		{$this->objeto_js}.modificar_vinculo__ef_comision = function(id_vinculo)
		{
			var fila = this.ef('comision').get_fila_actual();
			var parametros  = {
				'actividad'   	: this.ef('actividad').ir_a_fila(fila).get_estado(), 
				'plan' 			: this.ef('plan').ir_a_fila(fila).get_estado(),
				'ubicacion'    	: $ubicacion,
				'anio_academico': $anio_academico
			};
			vinculador.agregar_parametros(id_vinculo, parametros);  
		}
        
        {$this->objeto_js}.evt__cant_comisiones_disponibles__procesar = function(es_inicial,fila)
		{
            var estado = this.ef('cant_comisiones_disponibles').ir_a_fila(fila).get_estado();
            if (estado == 0){
                this.ef('cant_comisiones_disponibles').ir_a_fila(fila).set_estado('<center>Sin comisiones</center>');
            } else {
                this.ef('cant_comisiones_disponibles').ir_a_fila(fila).set_estado('<center>'+estado+'</center>');
            }			
		}

		";
	}

}
?>