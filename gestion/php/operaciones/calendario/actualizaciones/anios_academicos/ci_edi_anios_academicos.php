<?php
class ci_edi_anios_academicos extends ci_edicion_simple
{
    protected $s__datos;
    
    //--------------------------------------------------------------------------
	//---- Árbol ---------------------------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Construcción del árbol de la pantalla de selección de Comisiones y Subcomisiones.
	 */
	function construir_arbol()
	{
		//Configuración del árbol 
		$constructor = guarani::constructor_arbol();
		//Iconos de acciones
		$img_editar = toba_recurso::imagen_proyecto('acciones/editar.gif');
		$img_borrar = toba_recurso::imagen_proyecto('acciones/borrar.gif');
		// Nivel Año Academico
		$constructor->set_datos($this->get_datos_arbol());
        
		$nivel = guarani::arbol_nivel('id_fecha', 'clave', 'valor');
        $msj_elim = guarani_mensajes::get('800_confirmar_elimin', array('el registro'));
        
        
		$nivel->agregar_utileria($img_editar, 'editar', 'Editar la fecha inicio y fecha fin');
		$nivel->agregar_utileria($img_borrar, 'borrar', 'Borrar fecha', $msj_elim);
		$constructor->set_nivel($nivel);

		// Nivel Responsables Academicas
		$nivel = guarani::arbol_nivel('ra', 'clave_ra', 'ra_desc', 'Responsables Academicas');
		$constructor->set_nivel($nivel);

		//Carga de datos
		return $constructor;
	}

	function get_datos_arbol()
	{
		//$anios = $this->cn()->entidad()->get_datos();
        $fechas = $this->cn()->entidad()->tabla('fechas')->get_filas();
        $datos = array();
        foreach($fechas as $fecha) {
            $dato = array();            
            $dato['clave'] = $fecha['fecha_inicio'].','.$fecha['fecha_fin'];
            $dato['valor'] = 'Inicio: '. date("d/m/Y", strtotime($fecha['fecha_inicio'])).' - '.'Fin: '. date("d/m/Y", strtotime($fecha['fecha_fin']));
            $this->cn()->entidad()->set_cursor($fecha['x_dbr_clave']);
            $ras = $this->cn()->entidad()->get_desc_responsables_academicas();
            $dato['ra_desc'] = null;
            $fechas[] = $dato;
            foreach($ras as $ra) {
                $dato['ra_desc'] = $ra['descr'];
                $dato['clave_ra'] = $dato['clave'].','.$ra['valor'];
                $dato['ra'] = $ra['valor'];
                $fechas[] = $dato;
            }
            $this->cn()->entidad()->unset_cursor();
        }        
        return $fechas;
	}
    
    function nodo__id_fecha__editar($nodo)
	{
        $this->cn()->entidad()->seleccionar_fecha($nodo);
        $this->set_pantalla('pant_datos');
    }
    
    function nodo__id_fecha__borrar($nodo)
	{
        $this->cn()->entidad()->seleccionar_fecha($nodo);
        $this->cn()->entidad()->eliminar_fecha();        
        $this->cn()->entidad()->unset_cursor();        
    }
    
	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__volver()
	{
        $this->cn()->entidad()->unset_cursor();
        $this->set_pantalla('pant_inicial');
	}

	function evt__guardar()
	{
        $this->cn()->entidad()->set_datos_fechas($this->s__datos);
        $this->evt__volver();
	}

    function evt__agregar_fecha()
    {
        $this->set_pantalla('pant_datos');
    }
    
	//-----------------------------------------------------------------------------------
	//---- form_ra ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_ra(form_anios_academicos $form)
	{
        if ($this->cn()->entidad()->hay_cursor()) {
            $fecha = $this->cn()->entidad()->get_fecha_seleccionada();
            $fecha['responsables'] = aplanar_matriz($this->cn()->entidad()->get_responsables_academicas(), 'responsable_academica');
            $form->set_datos($fecha);
        }
	}

	function evt__form_ra__modificacion($datos)
	{
        $this->s__datos = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_datos(toba_ei_pantalla $pantalla)
	{
        if ($this->controlador()->pantalla()->existe_evento('eliminar')) {
            $this->controlador()->evento('eliminar')->desactivar();
        }
        $this->controlador()->evento('cancelar')->desactivar();        
        $this->controlador()->evento('guardar')->desactivar();
	}
    
    function get_responsables_academicas() 
    {
        return $this->cn()->entidad()->get_responsables_academicas_lista();
    }
}
?>