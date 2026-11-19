<?php 
/**
 * 
 * Representa las restricciones aplicadas para realizar una Acción.
 * 
 *
 * @package 	Requisitos
 * 
 */
class arbol_requisitos extends guarani_pers_ei_arbol
{

        /** @var boolean $_mostrar_botonera_expandir_contraer Si debe mostrar los botones 'Expandir' y 'Contraer'. */
        protected $_mostrar_botonera_expandir_contraer = true;
        /** @var string $_margen_botonera_expandir_contraer */
        protected $_margen_botonera_expandir_contraer = '10px 0';
        /** @var boolean $_colapsado_todo Si se debe mostrar todo el arbol contraído. */
        protected $_colapsado_todo = true;
        /** @var string $_altura_arbol Altura del árbol, por defecto el alto del árbol. */
        protected $_altura_arbol = '200px';

	function extender_objeto_js()
	{
		//Se definen dos funciones para ser ejecutadas ante el click en js de los diferentes íconos a la derecha de un nodo.
		//Las mismas reciben el id del nodo y lo único que hacen es invocar al evento correspondiente definido en el controlador del arbol.

                // Necesario para que funcionen los botones 'Expandir' y 'Contraer'.
                parent::extender_objeto_js();
                
		echo "
		
		{$this->objeto_js}.evento_eliminar = function(id) {
			var evento = new evento_ei('eliminar', true, '', [id] );
			this.controlador.set_evento(evento);		
		}
		
		{$this->objeto_js}.evento_seleccionar = function(id) {
			var evento = new evento_ei('seleccion', true, '', [id] );
			this.controlador.set_evento(evento);
		}

		";
	}
}