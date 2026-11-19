<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos por Acción
 * 
 */
class form_req_a_agregar extends guarani_pers_ei_formulario
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$req_existente = requisito::req_existente;
		$ent_estado = requisito::ent_estado;
		
		echo "
		
		//---- Validacion general ----------------------------------
		
		//---- Procesamiento de EFs --------------------------------		
		
		/**
		 * Ante la seleccion de una opción en el combo de Requisito a agregar, se ocultan o se muestran los componentes 
		 * adecuados en pantalla.
		 */
		{$this->objeto_js}.evt__req_a_agregar__procesar = function(es_inicial)
		{
			var mostrar_comp_ent_est = false;
			var mostrar_comp_req = false;
				
			if (this.ef('req_a_agregar').tiene_estado()) {
				var agregado = this.ef('req_a_agregar').get_estado();
				
				if (agregado == '$req_existente') {
					mostrar_comp_req = true;
				} else if (agregado == '$ent_estado') {
					mostrar_comp_ent_est = true;
				}
			}

			this.controlador.dependencia('filtro_reqexistentes').mostrar(mostrar_comp_req);
			this.controlador.dependencia('cuadro_reqexistentes').mostrar(mostrar_comp_req);
			this.controlador.dependencia('form_entidad_estado').mostrar(mostrar_comp_ent_est);
		}
					
		";
	}

}
?>