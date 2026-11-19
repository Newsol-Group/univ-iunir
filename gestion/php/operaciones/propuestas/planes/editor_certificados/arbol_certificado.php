<?php 
class arbol_certificado extends guarani_pers_ei_arbol
{
	protected $mostrar_boton_editar = true;
	
	function ini()
	{
		$this->set_ancho_nombres(85);
	}
	
	function mostrar_boton_editar($estado=true)
	{
		$this->mostrar_boton_editar = $estado;	
	}

	function generar_html_barra_especifica()
	{
		if($this->mostrar_boton_editar) {
			echo "<div style='height:25px; text-align:right;'>";
			$imagen = toba_recurso::imagen_proyecto("propuesta/modo_edicion.png", true);
			echo "<button title='Editar la estructura de correlatividad' style='float:left;' onclick='{$this->objeto_js}.modo_editar_estructura()'>$imagen</button>";
		} else {
			echo "<div style='height:25px; background-color: #e7e7e7; background: text-align:right;'>";
			$imagen = toba_recurso::imagen_proyecto("propuesta/modo_edicion.png", true);
			echo "<button DISABLED style='float:left;'>$imagen</button>";
		}
		echo "</div>";
	}

	function generar_html()
	{
		echo toba_form::hidden($this->_submit, '');
		echo toba_form::hidden($this->_submit."__apertura_datos", '');
		echo toba_form::hidden($this->_submit."__seleccion", '');
        $id = "id='{$this->objeto_js}_nodo_raiz'";
        $ancho = "100%";
        $alto = "350px";
		echo "<div class='ei-cuadro-scroll' style='width: $ancho; '>\n";
        echo "<div class='ei-base ei-arbol-base'>";
		echo $this->get_html_barra_editor();
		$this->generar_html_barra_sup(null, true,"ei-arbol-barra-sup");
		if ($this->_mostrar_filtro_rapido) {
			$this->generar_html_filtro_rapido();
		}
		$this->generar_html_barra_especifica();
		echo "<div id='cuerpo_{$this->objeto_js}'>";
		if (isset($this->_nodos_inicial)) {
			//--- Se incluye la barrita que contiene el path actual
			$barra = "";
			if (guarani_arreglos::count($this->_nodos_inicial) > 0) {
				$nodo = $this->_nodos_inicial[0];
				while ($nodo->get_padre() != null) {echo 'll';
					$nodo = $nodo->get_padre();
					$nodo_barra = "<a href='#' onclick='{$this->objeto_js}.ver_propiedades(\"";
					$nodo_barra .= $nodo->get_id()."\");' ";
					$nodo_barra .= "class='ei-arbol-ver-prop'>". $this->acortar_nombre($nodo->get_nombre_corto(),20)."</a>";
					$barra = $nodo_barra . " > ". $barra;
				}
				if ($barra != '') {
					$barra = "<div class='ei-arbol-barra-path'>$barra</div>";
				}
				echo $barra;
			}
			$id_div = '';
			if (guarani_arreglos::count($this->_nodos_inicial) > 1) {
				$id_div = $id;
				$id = '';		
			}			
			echo "<div class='ei-cuerpo ei-arbol-cuerpo' $id_div>\n";

			foreach ($this->_nodos_inicial as $nodo_inicial) {
				echo "\n<ul $id class='ei-arbol-raiz'>";
				echo $this->recorrer_recursivo($nodo_inicial, true);
				echo "</ul>";
				echo "<br><br><br><br>";
				$id = null;	//El id lo tiene s?lo el primer nodo
			}
			echo "</div>";
		}
		echo "</div>";
		echo "</div>";
        echo "</div>\n";
	}
	
	
	
	//TODO la funcion javascript cambiar_botones no cambia el valor del boton volver por cancelar.
	function extender_objeto_js()
	{
		$vinculo = toba::vinculador()->crear_autovinculo();
		echo "

		{$this->objeto_js}.agregar_requisitos = function(id_input) {
			// Busco los requisitos seleccionados
            var requisitos = [];
            var ci_correlativas = this.controlador.dep('disponibles');
            // me fijo si existe la dependencia y obtengo los requisitos si es que existe
            if (ci_correlativas.dep('actividades')){
                var tipo = 'actividades';
            }
            if (ci_correlativas.dep('modulos')){
                var tipo = 'modulos';
            }
            if (ci_correlativas.dep('requisitos')){
                var tipo = 'requisitos';
            }
            requisitos = requisitos.concat(ci_correlativas.dep(tipo).get_ids_seleccionados('seleccion'));
			if(requisitos.length > 0) {
				$$(id_input).value = requisitos;
				this.submit();
			} else {
				alert('Seleccione los ítems a agregar');
			}
		};

		{$this->objeto_js}.eliminar_requisito = function(id_input) {
		    if (confirm('¿Desea eliminar el ítem?')) {
			    $$(id_input).value = 1;
			    this.submit();
		    } 
		};
		
		{$this->objeto_js}.eliminar_grupo = function(id_input) {
		    if (confirm('¿Desea eliminar la Opción y su contenido?')) {
			    $$(id_input).value = 1;
			    this.submit();
		    } 
		};
		
		{$this->objeto_js}.cambiar_botones = function() {
			this.controlador.controlador.get_boton('guardar').disabled = false;
			this.controlador.controlador.get_boton('cancelar').setValue = '&Cancelar';
			hay_algun_cambio = true;
		};
		
		{$this->objeto_js}.seleccionar_modulo = function(nodo) {
			var id_modulo = nodo.getAttribute('id_nodo');
			this.controlador.set_evento(new evento_ei('seleccion_nodo', false, '', [id_modulo] ));
		};
		
		{$this->objeto_js}.seleccionar_requisito = function(nodo) {
			var id_requisito = nodo.getAttribute('id_nodo');
			this.controlador.set_evento(new evento_ei('seleccion_nodo', false, '', [id_requisito] ));
		};

		{$this->objeto_js}.modo_editar_estructura = function(nodo) {
			this.controlador.set_evento(new evento_ei('editar_estructura', false, '', true ));
		};
		
		";
	}		
}

?>