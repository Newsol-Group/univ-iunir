<?php

class plan_elemento_gui_modulo_raiz extends plan_elemento_gui_modulo
{
	function get_utilerias()
	{
		if ($this->es_modo_correlativas()) return array();
		$id_objeto_js = $this->ei_arbol->get_id_objeto_js();
		$iconos = array();
		if( $this->mostrar_utilerias_edicion ) {
			if ($this->mostrar_utileria_asociacion) {
				$iconos[] = array(
					'imagen' => toba_recurso::imagen_proyecto("adjunto.png", false),
					'ayuda' => "Asociar actividades al módulo",
					'javascript' => "onclick=\"{$id_objeto_js}.modulo_asociar_elementos('{$this->id}')\""
				);	
			}
			$iconos[] = array(
				'imagen' => toba_recurso::imagen_proyecto("propuesta/agregar.gif", false),
				'ayuda' => "Agregar un módulo a la propuesta",
				'javascript' => "onclick=\"{$id_objeto_js}.modulo_agregar('{$this->id}')\""
			);
		}
		return $iconos;
	}
}
?>