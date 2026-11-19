<?php
class filtro_actividad_academica extends guarani_pers_ei_filtro
{
    //-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__plan__procesar = function(es_inicial)
		{
            var vinculo = this.ef('actividades').get_id_vinculo();
            var propuesta = this.ef('propuesta').get_estado();
            var plan = this.ef('plan').get_estado();
            var actividades = this.ef('actividades').get_estado();
            var param = {'plan': plan, 'propuesta': propuesta, 'actividades': actividades};
            if (!es_inicial) {
                this.ef('actividades').resetear_estado();
            }
            vinculador.agregar_parametros(vinculo, param);
		}
        
		{$this->objeto_js}.evt__propuesta__procesar = function(es_inicial)
		{
            if (this.ef('propuesta').tiene_estado()) {
                var vinculo = this.ef('actividades').get_id_vinculo();
                var propuesta = this.ef('propuesta').get_estado();
                var plan = this.ef('plan').get_estado();
                var actividades = this.ef('actividades').get_estado();
                var param = {'plan': plan, 'propuesta': propuesta, 'actividades': actividades};
                
                vinculador.agregar_parametros(vinculo, param);
                this.ef('actividades').activar();                
                if (!es_inicial) {
                    this.ef('actividades').resetear_estado();
                }
            } else {
                this.ef('actividades').desactivar();
                this.ef('actividades').resetear_estado();
            }
		}
        
		";
	}
}

?>