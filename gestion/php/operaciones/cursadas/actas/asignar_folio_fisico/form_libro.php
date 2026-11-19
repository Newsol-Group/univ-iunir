<?php
/**
 *
 * 
 * @package 	Cursadas
 * @subpackage 	Actas
 * 
 */
class form_libro extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__libro__procesar = function(es_inicial)
		{
			if (!es_inicial) {
				if (this.ef('libro').tiene_estado()) {
					var params = [];
					params['libro'] = this.ef('libro').get_estado();
					this.controlador.ajax('siguiente_ff', params, this, this.respuesta_siguiente_ff);
				}
			}
		}
		
		{$this->objeto_js}.respuesta_siguiente_ff = function(respuesta) 
		{
			if (typeof respuesta == 'object') {
				this.ef('primer_ff').set_estado(respuesta['siguiente_ff']);
				this.controlador.dep('form_ml_folios').completar_datos(this.ef('libro').get_estado(), this.ef('primer_ff').get_estado());
			}
		}
		
		{$this->objeto_js}.evt__primer_ff__procesar = function(es_inicial)
		{
			if ((!es_inicial) && (this.ef('libro').tiene_estado()) && (this.ef('primer_ff').tiene_estado())) {
				if (this.evt__primer_ff__validar()) {
					this.controlador.dep('form_ml_folios').completar_datos(this.ef('libro').get_estado(), this.ef('primer_ff').get_estado());
				}
			}
		}
		
		//---- Validacion de EFs -----------------------------------
		
		{$this->objeto_js}.evt__primer_ff__validar = function()
		{
			if (this.ef('primer_ff').tiene_estado()) {
				var primer_ff = this.ef('primer_ff').get_estado();
				return primer_ff > 0;
			}
			return true;
		}
		
		//---- Validacion general ----------------------------------
		
		{$this->objeto_js}.evt__validar_datos = function()
		{
			if (!this.ef('primer_ff').tiene_estado()) {
				notificacion.agregar('<strong>Primer Folio Físico a Asignar al Acta</strong> es obligatorio');
				return false;
			}
			return true;
		}
		";
	}

}
?>