<?php
class ci_edi_certificado_plan extends ci_edi_condiciones
{
	protected $datos_resoluciones_temp;

	function ini()
	{
		$this->dep('arbol_certificado')->desactivar_chequeo_nodos();
		parent::ini();
	}

	function evt__cancelar() {
		$this->cn()->resetear_certificado();
		parent::evt__cancelar();
	}
	
	function conf__certificado_plan(pant_edi_certificado_plan $pantalla)
	{	
		if (!$this->cn()->es_certificado_editable()) {
			$msj = $this->cn()->condicion_certif_no_editable();
			$pantalla->set_descripcion($msj);
		}	
		//Elimino dependencia segun el modo de edicion
		if ($this->get_modo() == 'estructura') {
			$this->dep('arbol_certificado')->mostrar_boton_editar(false);
			$this->pantalla('certificado_plan')->eliminar_dep('propiedades');
		} else {
			$this->dep('arbol_certificado')->mostrar_boton_editar();
			$this->pantalla('certificado_plan')->eliminar_dep('disponibles');
		}	
	}
	
	function conf__datos_certificado(toba_ei_formulario $form)
	{
		$datos_entidad = $this->cn()->entidad()->get_datos_elemento();
		$datos_certificado = guarani::co('co_certificados')->get_certificado($datos_entidad['certificado']);
		$form->set_datos($datos_certificado);
	}

	//--- Arbol de restricciones ---------------------

	function conf__arbol_certificado(toba_ei_arbol $arbol)
	{
        $elementos = $this->get_arbol_certificado();
		$arbol->set_datos($elementos, true);
        $arbol->set_nivel_apertura(true);
	}
	
	function get_arbol_certificado()
	{
		$elementos = $this->cn()->entidad()->get_arbol_condiciones();
		// Agrego las acciones y grupos que no estan cargados
        $condiciones = toba::consulta_php('co_condiciones')->get_condiciones_certificados();
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
	
	function get_elementos_disponibles($tipo, $filtro)
	{
		if ($tipo == 'A') {
			$clave = 'id';
			//Quito de la lista la Actividad a la que le estoy seteando las correlatividades
			$datos_entidad_seleccionada = $this->cn()->entidad()->get_datos_elemento();
			if ($datos_entidad_seleccionada) {
				if ($filtro) $filtro .= ' AND ';
				$filtro .= ' sga_elementos.entidad <> '.$datos_entidad_seleccionada['entidad'].' 
							AND sga_planes_versiones.plan_version = '.$this->cn()->entidad()->get_id_plan_version().' ';
			}	
			$datos = toba::consulta_php('co_elementos')->get_actividades_y_materias_genericas_certificados($filtro);
		} elseif ($tipo == 'P') {//Modulos incluyendo módulos raíz
			$clave = 'id';
			if ($filtro) $filtro .= ' AND ';
			$filtro .= ' sga_planes_versiones.plan_version = '.$this->cn()->entidad()->get_id_plan_version().' ';
			$datos = toba::consulta_php('co_elementos')->get_modulos_certificados($filtro);
		} elseif ($tipo == 'R') {
			$clave = 'id';
			$datos = toba::consulta_php('co_requisitos')->get_requisitos_certificados($filtro);		
		} 
		$mem = array();
		foreach(array_keys($datos) as $id) {
			$mem[] = $datos[$id][$clave];
			$url = toba_recurso::url_proyecto() . "/img/" . $datos[$id]['icono'];
			if ($tipo == 'R') {
				$ayuda = 'Tipo: ' . 'Requisito';
			} else {
				$ayuda = 'Tipo: ' . $datos[$id]['subtipo_nombre'];
			}
			$img = "<img title='$ayuda' src='$url'/>";			
			$datos[$id]['icono'] = $img;
		}
		toba::memoria()->set_array_operacion('requisitos_disponibles', $mem);
		return $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- ml_resoluciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__ml_resoluciones(guarani_pers_ei_formulario_ml $form_ml)
	{
		if (isset($this->datos_resoluciones_temp)) {
			$this->controlador()->controlador()->set_hubo_cambios();
			$form_ml->set_datos($this->datos_resoluciones_temp);
		} else {
			$form_ml->set_datos($this->cn()->entidad()->get_datos_resoluciones());
		}
	}

	function evt__ml_resoluciones__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_resoluciones($datos);
		} catch (guarani_error $e) {
			$this->datos_resoluciones_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}
}
?>