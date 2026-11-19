<?php

/**
 * Métodos relacionados al armado de información para mostrar en pantalla
 * 
 * @package Extensión Toba
 * @subpackage Componentes
 */

class arbol_sin_barras extends guarani_pers_ei_arbol
{
	protected $mostrar_barra = true;
	protected $mostrar_boton_editar = true;

	function generar_html()
	{
		echo toba_form::hidden($this->_submit, '');
		echo toba_form::hidden($this->_submit."__apertura_datos", '');
		echo toba_form::hidden($this->_submit."__seleccion", '');
		$id = "id='{$this->objeto_js}_nodo_raiz'";
		echo "<div class='ei-base ei-arbol-base'>";
		//echo $this->get_html_barra_editor();
		//$this->generar_html_barra_sup(null, true,'');
		if ($this->_mostrar_filtro_rapido) {
			$this->generar_html_filtro_rapido();
		}
		//$this->generar_html_barra_especifica();
		echo "<div style='width: 100%' id='cuerpo_{$this->objeto_js}'>";
		if (isset($this->_nodos_inicial)) {
			//--- Se incluye la barrita que contiene el path actual
			$barra = "";
			if (guarani_arreglos::count($this->_nodos_inicial) > 0) {
				$nodo = $this->_nodos_inicial[0];
				while ($nodo->get_padre() != null) {
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
				$id = null;	//El id lo tiene s?lo el primer nodo
			}
			echo "</div>";
		}
		echo "</div>";
		echo "</div>";
	}

	public function mostrar_nodo(toba_nodo_arbol $nodo, $es_visible)
	{
		$salida = '';
		$salida .= $this->mostrar_utilerias($nodo);
		if ($this->_mostrar_propiedades_nodos && ! $nodo->es_hoja()) {
			if ($es_visible) {
				$img_exp_contr = toba_recurso::imagen_toba('nucleo/contraer.gif', false);
			} else {
				$img_exp_contr = toba_recurso::imagen_toba('nucleo/expandir.gif', false);
			}
			$salida .= "<img src='$img_exp_contr' onclick='{$this->objeto_js}.cambiar_expansion(this);'
			class='ei-arbol-exp-contr' alt='' /> ";
		} else {
			$salida .= gif_nulo(14,1);
		}
		$salida .= $this->mostrar_iconos($nodo);

		//Nombre y ayuda
		$corto = $this->acortar_nombre($nodo->get_nombre_largo());
		$id = $nodo->get_id();
		$largo = $nodo->get_nombre_largo();
		$extra = $nodo->get_info_extra();

		if($this->_mostrar_ayuda && ($largo || $extra)) {
			$title= "<b>Nombre</b>: $largo<br />";
			if ($extra != '') {
				$title .= "<hr />$extra";
			}
			$ayuda = toba_recurso::ayuda(null,  $title, 'ei-arbol-nombre');
			if (get_class($nodo) == 'toba_ci_pantalla_info'){
					$nombre= "<span $ayuda>$id</span>";
			}else{
					$nombre= "<span $ayuda>$corto</span>";
			}
		} else {
			$nombre = $corto;
		}
		if ($this->_mostrar_propiedades_nodos && $nodo->tiene_propiedades()) {
			$salida .= "<a href='#' onclick='{$this->objeto_js}.ver_propiedades(\"".$nodo->get_id()."\");' ".
						"class='ei-arbol-ver-prop'>$nombre</a>";
		} else {
			$salida .= $nombre;
		}
		return $salida;
	}

	function mostrar_barra($estado=true)
	{
		$this->mostrar_barra = $estado;
	}

	function mostrar_boton_editar($estado=true)
	{
		$this->mostrar_boton_editar = $estado;
	}

	function generar_html_barra_especifica()
	{
		if($this->mostrar_barra) {
			if($this->mostrar_boton_editar) {
				echo "<div style='height:25px; background: rgb(220,220,220);text-align:right;'>";
				$imagen = toba_recurso::imagen_proyecto("propuesta/modo_edicion.png", true);
				echo "<button title='Editar la estructura del Plan de Estudios' style='float:left;' onclick='{$this->objeto_js}.modo_asociar()'>$imagen</button>";
			} else {
				echo "<div style='height:25px; background: rgb(190,190,190);text-align:right;'>";
				$imagen = toba_recurso::imagen_proyecto("propuesta/modo_edicion.png", true);
				echo "<button DISABLED style='float:left;' onclick='{$this->objeto_js}.modo_asociar()'>$imagen</button>";
			}
			echo "</div>";
		}
	}

}
?>
