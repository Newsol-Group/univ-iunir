<?php
class guarani_ei_arbol extends toba_ei_arbol
{

        /** @var boolean $_mostrar_botonera_expandir_contraer Si debe mostrar los botones 'Expandir' y 'Contraer'. */
        protected $_mostrar_botonera_expandir_contraer = false;
        /** @var string $_margen_botonera_expandir_contraer */
        protected $_margen_botonera_expandir_contraer = '0';
        /** @var boolean $_colapsado_todo Si se debe mostrar todo el arbol contraído. */
        protected $_colapsado_todo = false;
        /** @var string $_altura_arbol Altura del árbol, por defecto el alto del árbol. */
        protected $_altura_arbol = 'auto';

        function generar_html()
        {
            $this->generar_css();

            // Si se muestran los botones Expandir y Contraer, y hay datos
            if ($this->_mostrar_botonera_expandir_contraer && $this->hay_datos()) {
                $this->generar_html_expandir_contraer_todo();
            }

            parent::generar_html();
        }

        protected function generar_html_expandir_contraer_todo()
        {
            echo "<div id='{$this->objeto_js}_botonera_expandir_contraer' class='arbol_botonera_expandir_contraer'>";
                echo "<a href='#' class='arbol_expandir_todo'>".guarani_mensajes::get('800_arbol_expandir')."</a>";
                echo " / ";
                echo "<a href='#' class='arbol_contraer_todo'>".guarani_mensajes::get('800_arbol_contraer')."</a>";
            echo "</div>";
        }

        protected function generar_css()
        {

            // Si hay datos le doy al árbol la altura configurada por la operación, sino le doy 'auto' (default height value)
            $height = ($this->hay_datos())? $this->_altura_arbol : 'auto';

            echo "<style>";
                echo "#cuerpo_{$this->objeto_js} {
                        height: {$height};
                        overflow: auto;
                    }";

                echo "#{$this->objeto_js}_botonera_expandir_contraer {
                        margin: {$this->_margen_botonera_expandir_contraer};
                    }";
            echo "</style>";
        }

        function extender_objeto_js()
	{

            // Si se muestran los botones Expandir y Contraer, y hay datos
            if ($this->_mostrar_botonera_expandir_contraer && $this->hay_datos()) {
		echo "

                // Al hacer click en 'Expandir' expande todos los elementos cerrados del árbol
		$('#{$this->objeto_js}_botonera_expandir_contraer .arbol_expandir_todo').on('click', function (e) {
                    e.preventDefault();
                    $('#cuerpo_{$this->objeto_js} .ei-arbol-exp-contr[src*=expandir]').trigger('click');
                });

                // Al hacer click en 'Contraer' colapsa todos los elementos abiertos del árbol
		$('#{$this->objeto_js}_botonera_expandir_contraer .arbol_contraer_todo').on('click', function (e) {
                    e.preventDefault();
                    $('#cuerpo_{$this->objeto_js} .ei-arbol-exp-contr[src*=contraer]').trigger('click');
                });
                
		";
            }

            // Si se debe mostrar todo el arbol contraído y hay datos
            if ($this->_colapsado_todo && $this->hay_datos()) {
                echo "

                $('#cuerpo_{$this->objeto_js} .ei-arbol-exp-contr[src*=contraer]').trigger('click');

		";
            }
	}

        protected function hay_datos()
        {
            $datos = $this->get_datos();
            return !empty($datos);
        }

        protected function disparar_eventos_nodo($nodo)
	{
		//Le paso al nodo una referencia al js del arbol que lo contiene
		if (method_exists($nodo, 'set_ei_arbol')) {
			$nodo->set_ei_arbol($this);
		}
		$id = $this->_submit.'_'.$nodo->get_id();
		// Se le pide al nodo que cargue su estado a partir del post
		$nodo->cargar_estado_post($id);
		// Se le comunica al nodo si esta abierto o no
		if (isset($this->_datos_apertura) && isset($this->_datos_apertura[$nodo->get_id()])) {
			$nodo->set_apertura($this->_datos_apertura[$nodo->get_id()]);
		}
		foreach ($nodo->get_hijos() as $hijo) {
			$this->disparar_eventos_nodo($hijo);
		}
	}

	public function recorrer_recursivo($nodo, $es_raiz = false, $nivel = 0, $solo_contenido=false)
	{
		//Le paso al nodo una referencia al js del arbol que lo contiene
		if (method_exists($nodo, 'set_ei_arbol')) {
			$nodo->set_ei_arbol($this);
		}
		$id_nodo = $nodo->get_id();
		$this->_ids_enviados[] = $id_nodo;
		//Verifico que no haya un mismo id perteneciente a clases diferentes
		if ($this->chequear_ids_unicos) {			
			if (isset($this->ids[$id_nodo])) {
				$clase = get_class($nodo);
				$clase_vieja = $this->ids[$id_nodo];
				throw new toba_error("Error al procesar el nodo '$id_nodo' de clase '$clase'. Ya existe el mismo id de clase '$clase_vieja'");
			}
			$this->ids[$id_nodo] = get_class($nodo);
		}
		
		//Genero el html para el nodo
		$salida_generada = $this->generar_fila_nodo($nodo, $nivel);
		
		//Diferencio la salida segun corresponda		
		if (! $solo_contenido) { 
			$estilo_li = '';							//Configuracion del estilo del nodo
			$clase_li = 'ei-arbol-nodo ';			
			if (method_exists($nodo, 'get_estilo_css_li')) {
				$estilo_li .= $nodo->get_estilo_css_li();
			}
			if (method_exists($nodo, 'get_clase_css_li')) {
				$clase_li .= $nodo->get_clase_css_li();
			}
			
			$salida = "\n\t<li class='$clase_li' id_nodo='{$nodo->get_id()}' style='$estilo_li' >";					
			$salida .= $salida_generada;
			$salida .= "</li>\n";
		} else {
			$salida = $salida_generada;
		}
		
		return $salida;
	}
}