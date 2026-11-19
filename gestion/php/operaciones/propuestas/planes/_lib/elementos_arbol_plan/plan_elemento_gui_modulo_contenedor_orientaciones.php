<?php

class plan_elemento_gui_modulo_contenedor_orientaciones extends plan_elemento_gui_modulo
{
	function get_utilerias()
	{
		if ($this->es_modo_correlativas()) return array();
		$id_objeto_js = $this->ei_arbol->get_id_objeto_js();
		$iconos = array();
		if( $this->mostrar_utilerias_edicion ) {
			$iconos[] = array(
				'imagen' => toba_recurso::imagen_proyecto("propuesta/agregar.gif", false),
				'ayuda' => "Agregar un módulo a la propuesta",
				'javascript' => "onclick=\"{$id_objeto_js}.modulo_orientacion_agregar('{$this->id}')\""
			);
			if( $this->mostrar_utileria_eliminacion ) {
				$iconos[] = array(
					'imagen' => toba_recurso::imagen_proyecto("propuesta/borrar.gif", false),
					'ayuda' => "Quitar el módulo",
					'javascript' => "onclick=\"{$id_objeto_js}.eliminar_elemento('{$this->id}','{$this->id_padre}','{$this->nombre_corto}')\""
				);
			}
		}
		return $iconos;
	}
}
?>