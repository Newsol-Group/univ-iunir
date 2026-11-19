<?php 

class arbol_plan extends guarani_pers_ei_arbol
{
    protected $_ancho_nombres = 50;

    function generar_html()
    {
        $ancho = "100%";
        $alto = "350px";
        echo "<div class='ei-cuadro-scroll' style='height: $alto; width: $ancho; '>\n";
        parent::generar_html();
        echo "</div>\n";
    }

    function extender_objeto_js()
	{
		$vinculo = toba::vinculador()->crear_autovinculo();
		
		echo "
		{$this->objeto_js}.seleccionar_actividad = function(nodo) {
			var id_actividad = nodo.getAttribute('id_nodo');
			var evento = new evento_ei('seleccion_nodo', true, '', [id_actividad] );
			this.controlador.set_evento(evento);
		}
		
		{$this->objeto_js}.copiar_correlativas = function(nodo) {
			var id_actividad = nodo.getAttribute('id_nodo');
			var evento = new evento_ei('copia_correlativas', false, '', [id_actividad] );
			this.controlador.set_evento(evento);
		}
		;
		";
	}
}

?>