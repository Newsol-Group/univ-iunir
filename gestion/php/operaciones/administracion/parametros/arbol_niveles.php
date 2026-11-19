<?php
/**
 *
 *
 * @package 	Administración
 * @subpackage 	Parámetros
 * 
 */
class arbol_niveles extends arbol_sin_barras
{
	function mostrar_nodo(toba_nodo_arbol $nodo, $es_visible)
	{
		$salida = '';
		$salida .= $this->mostrar_utilerias($nodo);
		if ($this->_mostrar_propiedades_nodos && !$nodo->es_hoja()) {
			if ($es_visible) {
				$img_exp_contr = toba_recurso::imagen_toba('nucleo/contraer.gif', false);
			} else {
				$img_exp_contr = toba_recurso::imagen_toba('nucleo/expandir.gif', false);
			}
			$salida .= "<img src='$img_exp_contr' onclick='{$this->objeto_js}.cambiar_expansion(this);'
			class='ei-arbol-exp-contr' alt='' /> ";
		} else {
			$salida .= gif_nulo(14, 1);
		}
		$salida .= $this->mostrar_iconos($nodo);

		// Nombre y ayuda
		$id = $nodo->get_id();
		$largo = $nodo->get_nombre_largo();
		$corto = $largo;
		$extra = $nodo->get_info_extra();

		if ($this->_mostrar_ayuda && ($largo || $extra)) {
			$title = "<b>Nombre</b>: $largo<br />";
			if ($extra != '') {
				$title .= "<hr />$extra";
			}
			$ayuda = toba_recurso::ayuda(null, $title, 'ei-arbol-nombre');
			if (get_class($nodo) == 'toba_ci_pantalla_info') {
				$nombre = "<span $ayuda>$id</span>";
			} else {
				$nombre = "<span $ayuda>$corto</span>";
			}
		} else {
			$nombre = $corto;
		}
		if ($this->_mostrar_propiedades_nodos && $nodo->tiene_propiedades()) {
			$salida .= "<a href='#' onclick='{$this->objeto_js}.ver_propiedades(\"" . $nodo->get_id() . "\");' " .
					"class='ei-arbol-ver-prop'>$nombre</a>";
		} else {
			$salida .= $nombre;
		}
		return $salida;
	}

}
?>