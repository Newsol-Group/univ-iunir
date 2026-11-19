<?php
class gadget_operaciones extends toba_gadget {

	protected $proyecto;
	protected $item;	
	
	function set_proyecto($proyecto) {
		$this->proyecto = $proyecto;
	}
	
	function set_item($item) {
		$this->item = $item;
	}
	
	function generar_html() {
		toba::logger()->seccion('== Inicio de gadget interno '.$this->get_titulo().' ==','toba');
		$solicitud = toba_constructor::get_runtime(array('proyecto'=>$this->proyecto, 'componente'=>$this->item), 'toba_item');
		echo "<div id='gadget-chrome-{$this->get_orden()}' class='gadgets-gadget-chrome'>";
		echo "<div id='gadgets-gadget-title-bar-{$this->get_orden()}' class='gadgets-gadget-title-bar'>";
		echo "<span class='gadgets-gadget-title' id='remote_iframe_{$this->get_orden()}_title'>{$this->get_titulo()}</span> | ";
		echo "<span class='gadgets-gadget-title-button-bar' onclick='var c=document.getElementById(\"gadget-content-{$this->get_orden()}\");if(c.style.display==\"none\") {c.style.display=\"block\";this.innerHTML=\"Minimizar\";} else {c.style.display=\"none\";this.innerHTML=\"Maximizar\";}' style='text-decoration: underline; cursor:pointer'>Minimizar</span>";
		if ($this->es_eliminable()) {
		echo "<img style='float:right; margin-right: 5px; margin-left: 5px; margin-top: 1px; margin-bottom: 1px; width: 15px; height: 15px' onClick='this.parentNode.parentNode.parentNode.removeChild(this.parentNode.parentNode)' src='".toba_recurso::imagen_toba('finalizar_sesion.gif', false)."'/>";
		}
		echo "</div>\n";
		echo "<div id='gadget-content-{$this->get_orden()}' class='gadgets-gadget-content'>";
		echo "<div style='width: 270px; padding: 3px;'>";
		if($solicitud->get_datos_item('tipo_pagina_clase') == 'toba_tipo_pagina') {
			/*
			 * bug en la linea 231, de la función servicio__generar_html de toba_solicitud_web
			 * aunque se le indique que el componente no tiene salida (clase 'toba_tipo_pagina') en toba editor,
			 * lo mismoincluye el cierre de dos divs (barra superior y encabezado) aunque no hayan sido creados.
			 *
			 *  Se debería chequear el tipo de página.
			 */
			echo "<div style='display: none'><div>";
		}
		$solicitud->procesar();
		echo "</div>\n";
		echo "</div>\n";
		echo "</div>\n";
		toba::logger()->seccion('== Fin de gadget interno '.$this->get_titulo().' ==','toba');
	}
}
?>