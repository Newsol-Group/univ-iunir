<?php 

class arbol_periodos_x_nivel extends guarani_pers_ei_arbol
{
	function extender_objeto_js()
	{
		echo "
		{$this->objeto_js}.evento_editar_periodo = function(nodo) {
			var evento = new evento_ei('editar_periodo', true, '', [nodo]);
			this.controlador.set_evento(evento);
		};
		{$this->objeto_js}.evento_borrar_periodo = function(nodo) {
			var evento = new evento_ei('borrar_periodo', true, '', [nodo]);
			this.controlador.set_evento(evento);
		};
		";
	}	
}

?>