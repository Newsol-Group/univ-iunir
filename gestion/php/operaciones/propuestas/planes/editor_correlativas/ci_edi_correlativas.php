<?php 
class ci_edi_correlativas extends ci_edi_condiciones
{
	
	function ini()
	{
		$this->dep('arbol_correlativas')->desactivar_chequeo_nodos();
		parent::ini();
	}

	function conf__correlativas()
	{	
		//Elimino dependencia segun el modo de edicion
		if ($this->get_modo() == 'estructura') {
			$this->dep('arbol_correlativas')->mostrar_boton_editar(false);
			$this->pantalla('correlativas')->eliminar_dep('propiedades');
		} else {
			$this->dep('arbol_correlativas')->mostrar_boton_editar();
			$this->pantalla('correlativas')->eliminar_dep('disponibles');
		}	
	}
	
	function conf__datos_actividad(toba_ei_formulario $form)
	{
		$datos_entidad = $this->cn()->entidad()->get_datos_elemento();
		$datos_elemento = guarani::co('co_elementos')->get_info_elemento_plan($this->cn()->entidad()->get_id_plan_version(), $datos_entidad['elemento']);
		$form->set_datos($datos_elemento);
	}
	
	//--- Arbol de restricciones ---------------------

	function conf__arbol_correlativas(toba_ei_arbol $arbol)
	{
        $elementos = $this->get_arbol_correlativas();
		$arbol->set_datos($elementos, true);
        $arbol->set_nivel_apertura(true);
		$arbol->set_ancho_nombres(50);
	}
	
	function get_arbol_correlativas()
	{
		$elementos = $this->cn()->entidad()->get_arbol_condiciones();
		// Agrego las acciones y grupos que no estan cargados
        $condiciones = toba::consulta_php('co_correlativas')->get_condiciones_tipos();
		foreach ($condiciones as $condicion) {
			if (!isset($elementos[$condicion['condicion_tipo']])) {
        		$elementos[$condicion['condicion_tipo']]['condicion_tipo'] = $condicion['condicion_tipo'];
         		$elementos[$condicion['condicion_tipo']]['nombre'] = $condicion['nombre'];
        		$elementos[$condicion['condicion_tipo']]['indice'] = null;
        		$elementos[$condicion['condicion_tipo']]['grupos'] = null;
        	}
        }
        ksort($elementos);
        return $this->construir_arbol($elementos);
	}

}

?>