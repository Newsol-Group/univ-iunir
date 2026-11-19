<?php

class ci_popup_actividades extends guarani_pers_ci
{
    protected $s__datos;
    protected $s__filtro;
    protected $s__plan = null;
    protected $s__propuesta = null;
    protected $s__actividades = [];
    protected $ev_info;
        
            
    function ini()
    {
        //Traigo todos los parametros que envíe el filtro.
        $parametros = toba::memoria()->get_parametros();
        $propuesta_desc = '';
        $plan_desc = '';
        $aux = [];
        
        if (isset($parametros['propuesta']) && $parametros['propuesta'] != '' && $parametros['propuesta'] != apex_ef_no_seteado) {
            $propuesta_desc = current(toba::consulta_php('co_propuestas')->get_listado_nombres_propuestas(' sga_propuestas.propuesta = '.$parametros['propuesta']));
            $propuesta_desc = $propuesta_desc['descr'];
            $this->s__propuesta = $parametros['propuesta'];
            $aux['propuesta'] = $propuesta_desc;
        }        
        if (isset($parametros['plan']) && $parametros['plan'] != '' && $parametros['plan'] != apex_ef_no_seteado) {
            $plan_desc = current(toba::consulta_php('co_planes')->get_planes_nombres(' sga_planes.plan = '.$parametros['plan']));
            $plan_desc = $plan_desc['descr'];
            $this->s__plan = $parametros['plan'];
            $aux['plan'] = $plan_desc;
        }        
        // Si ya traia actividades seleccionadas las tengo que mostrar chequeadas
        if (isset($parametros['actividades']) && $parametros['actividades'] != '') {
            $this->s__actividades = explode(',', $parametros['actividades']);
        }
        $this->ev_info()->set_datos($aux);
    }

    //-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(guarani_pers_ei_cuadro $cuadro)
	{
        //Voy a traer las actividades dependiendo de la propuesta y el plan.
        //ei_arbol($this->s__filtro);
        $cuadro->set_datos(toba::consulta_php('co_elementos')->get_actividades_propuesta_plan($this->s__propuesta, $this->s__plan, $this->s__filtro));
	}

    public function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
    {
        $datos = $this->dep('cuadro')->get_datos();
        if (!empty($this->s__actividades)) {            
            if (in_array($datos[$fila]['valor'], $this->s__actividades)) {
                $evento->set_check_activo(true);
            } else {
                $evento->set_check_activo(false);
            }
        }
    }
    
	function evt__cuadro__seleccion($datos)
	{
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos) && !empty($datos)) {
			$this->s__datos = $datos;
			$this->s__filtro = $this->dep('filtro')->get_sql_where();
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

	function evt__filtro__cancelar()
	{
		unset($this->s__datos);
		unset($this->s__filtro);
	}

	function conf__filtro(toba_ei_filtro $filtro)
	{
		// Si el perfil de datos del usuario pertenece al dominio "Propuestas" es obligatorio filtrar los conceptos por ubicación.
		if (usuario::tiene_perfil_por_propuesta()) {
			$filtro->set_ubicacion_obligatoria();
		}
		if (isset($this->s__datos)) {
			$filtro->set_datos($this->s__datos);
		}		
	}
    
    /*
     * Voy a buscar entre lo seleccionado del cuadro las claves y las descripciones
     */
    function ajax__seleccionar($seleccionados, toba_ajax_respuesta $respuesta)
    {
        //Se pone aca por si dsps se cambia en el algun momento
        $clave_del_cuadro = 'valor';
        $desc_del_cuadro = 'descr';
        
        $datos_cuadro = $this->dep('cuadro')->get_datos();        
        $claves = [];
        $descripciones = [];
        
        //Recorro los seleccionados para buscar clave y descripcikon
        foreach ($seleccionados as $seleccion) {
            $claves[] = $datos_cuadro[$seleccion][$clave_del_cuadro];
            $descripciones[] = $datos_cuadro[$seleccion][$desc_del_cuadro];
        }
        
        //Paso a string las claves y descripciones
        $clavesSt = implode(',', $claves);
        $descripcionesSt = implode(', ', $descripciones);
        $respuesta->set(array($clavesSt, $descripcionesSt));
    }
    
    function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('propuesta', 'plan'));
			$this->ev_info->set_max_columnas_por_fila(1);
			$this->ev_info->set_td_etiqueta_width('130px');
			$this->ev_info->set_etiqueta('propuesta', 'Propuesta:');
			$this->ev_info->set_etiqueta('plan', 'Plan:');
		}
		return $this->ev_info;
	}
    
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		//---- Eventos ---------------------------------------------
		
        //El evento se va hacer por ajax porque tengo que buscar 
        // los string de las claves
		{$this->objeto_js}.evt__seleccionar = function()
		{
            var seleccion = this.dep('cuadro').get_ids_seleccionados('seleccion');
            this.ajax('seleccionar', seleccion, this, this.retorno_datos);
            return false;
		}
        
        {$this->objeto_js}.retorno_datos = function(datos)
        {
            //En la devolución tengo que pasar las claves y los string de los nombres.
            window.opener.popup_callback('ef_ei_37000966_filtroactividades', datos[0], datos[1]);
            window.close();
        }

		";
	}

}
?>