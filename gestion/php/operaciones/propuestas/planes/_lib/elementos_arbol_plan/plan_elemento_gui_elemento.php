<?php

class plan_elemento_gui_elemento extends plan_elemento_gui 
{
	protected $opciones_operador_not;
	
	function __construct($id, $nombre, $datos)
	{
		if (! isset($datos['operador_not'])) $datos['operador_not'] = comunes::operador_not_defecto;
		parent::__construct($id, $nombre, $datos);
	}
	
	function inicializar()
	{
		$this->nombre_largo = $this->nombre_corto;
		$opciones_operador_not = comunes::get_opciones_combo('operador_not');
		foreach( $opciones_operador_not as $opcion ) {
			$this->opciones_operador_not[$opcion['valor']] = $opcion['descr'];
		}
	}
	
	function get_iconos()
	{
		$id_objeto_js = $this->ei_arbol->get_id_objeto_js();
		$iconos[] = array(
				'imagen' => toba_recurso::imagen_proyecto( 'propuesta/g_elemento.png', false),
				'ayuda' => "Columnas",
				'javascript' => "onclick=\"{$id_objeto_js}.seleccionar_elemento(this)\""
			);			
		return $iconos;
	}		

	function cargar_estado_post($id)
	{
		// Controlo la eliminacion
		$id_input = $id.'_eliminar_requisito';
		if (isset($_POST[$id_input])) {
			if ($_POST[$id_input]) {
				$this->controlador()->eliminar_requisito($this->datos['indice']);
				return;
			}
		}
		//-- Controlo la modificacion		
		$datos_enviar = array();
		if (isset($_POST[$id.'_combo_estados'])) {
			if ($_POST[$id.'_combo_estados'] == apex_ef_no_seteado) $_POST[$id.'_combo_estados'] = null;
			if($this->datos['estado'] != $_POST[$id.'_combo_estados']){
				$datos_enviar['estado'] = $_POST[$id.'_combo_estados'];
			}
		}
		if (isset($_POST[$id.'_combo_operador_not'])) {
			if ($_POST[$id.'_combo_operador_not'] == apex_ef_no_seteado) $_POST[$id.'_combo_operador_not'] = comunes::operador_not_defecto;			
			if($this->datos['operador_not'] != $_POST[$id.'_combo_operador_not']){
				$datos_enviar['operador_not'] = $_POST[$id.'_combo_operador_not'];
			}
		}
		if ($datos_enviar != array()) {
			// Envio de datos al CI
			$this->controlador()->modificar_requisito($this->datos['indice'], $datos_enviar);
		}	
	}

}
	
?>