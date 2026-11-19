<?php
class filtro_alumnos extends guarani_pers_ei_filtro
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Procesamiento de EFs --------------------------------

		{$this->objeto_js}.evt__estado_propuesta__procesar = function(es_inicial)
		{
            if (this.ef('estado_propuesta').tiene_estado()) {
                var estado_propuesta = this.ef('estado_propuesta').get_estado();
				if(estado_propuesta == '".propuesta::inscripcion_rechazada."'){
					this.ef('motivo_rechazo').mostrar();
				} else {
					this.ef('motivo_rechazo').ocultar();
				}
            }
		}
		{$this->objeto_js}.evt__columnas_opc__procesar = function(es_inicial)
		{
            if (!this.ef('titulo_obtenido').tiene_estado()) {
				cambiar_estado_checkbox('disabled');				
			} else {
				cambiar_estado_checkbox('');
			}
		}
		{$this->objeto_js}.evt__titulo_obtenido__procesar = function(es_inicial)
		{
            if (!this.ef('titulo_obtenido').tiene_estado()) {
				cambiar_estado_checkbox('disabled');	
			} else {
				cambiar_estado_checkbox('');	
			}
		}
		function cambiar_estado_checkbox(estado)
		{
			var checkboxes = document.querySelectorAll('input[type=".'checkbox'."]');
			for (var i = 0; i < checkboxes.length; i++) {
				if(estado === 'disabled'){
					if (checkboxes[i].value === 'fecha_egreso') {
						checkboxes[i].checked = false;
						checkboxes[i].setAttribute('disabled', 'disabled');
					}
				} else {
					if (checkboxes[i].value === 'fecha_egreso') {
						checkboxes[i].removeAttribute('disabled');
					}
				}
			}
		}
		";
	}
}