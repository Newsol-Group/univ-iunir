<?php 

class ci_plan_correlativas_listar_disponibles extends ci_plan_elementos_listar_disponibles
{
	function conf()
	{
		//Fuerzo la llamada porque en la primera entrada no está pasando por los mismos
		$invocar = "evt__".$this->get_id_pantalla()."__entrada";
		$this->$invocar();
		parent::conf();
		
	}
	
	//-- MODULOS -------------------------------------------------

	function conf__modulos(toba_ei_cuadro $cuadro)
	{
		$cuadro->desactivar_modo_clave_segura();
		$cuadro->evento('seleccion')->set_seleccion_multiple();
		$cuadro->deseleccionar();
		$plan_version = $this->cn()->entidad()->get_id_plan_version();
		$this->msj_eof = '800_eof_cuadro';
		if (!isset($this->s__filtro_modulos)) {
			$this->msj_eof .= '_sinfiltro';
		} else {
			$datos = $this->controlador->get_elementos_disponibles('M',$this->s__filtro_modulos);
			$cuadro->set_datos($datos);
			//Si el filtro no es vacío se agrega el mensaje de eof del cuadro con filtro.
			if (isset($this->s__filtro_modulos)) {
				$this->msj_eof .= '_filtrado';
			}
		}
		$etiquetas = $etiquetas = guarani_etiquetas::get_campo('modulo');
		$msj_cuadro = guarani_mensajes::get($this->msj_eof,array($etiquetas->get_enf('plural')));
		$cuadro->set_eof_mensaje($msj_cuadro);
	}
	
	//-- REQUISITOS -------------------------------------------------

	function conf__requisitos(toba_ei_cuadro $cuadro)
	{
		$cuadro->desactivar_modo_clave_segura();
		// Sólo se muestran requisitos en estado Activo
		$filtro_activos = "sga_requisitos.activo = '" . comunes::si . "'";
		$this->s__filtro_requisitos = empty($this->s__filtro_requisitos) ? $filtro_activos : $this->s__filtro_requisitos . ' AND ' . $filtro_activos;
		$datos = $this->controlador->get_elementos_disponibles('R',$this->s__filtro_requisitos);
		$cuadro->evento('seleccion')->set_seleccion_multiple();
		$cuadro->deseleccionar();
		$cuadro->set_datos($datos);
		$etiquetas = guarani_etiquetas::get_campo('requisito');
		$this->msj_eof = '800_eof_cuadro';
		if (isset($this->s__filtro_requisitos)) {
			$this->msj_eof .= '_filtrado';
		}
		$msj = guarani_mensajes::get($this->msj_eof,array($etiquetas->get_enf('plural')));
		$cuadro->set_eof_mensaje($msj);
	}
	
	//-- ACTIVIDADES ---------------------------------------------------
	
	function conf__actividades(toba_ei_cuadro $cuadro)
	{
		$cuadro->desactivar_modo_clave_segura();
		parent::conf__actividades($cuadro);
	}

	//-- FILTRO requisitos ------------------------------------------
	protected $s__filtro_requisitos;
	protected $s__datos_requisitos;

	function evt__filtro_requisitos__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_requisitos = $datos;
			$filtro = $this->dep('filtro_requisitos')->get_sql_where();
			$this->s__filtro_requisitos = $filtro;
		} else {
			unset($this->s__datos_requisitos);
			$this->s__filtro_requisitos = "";
		}
	}

	function evt__filtro_requisitos__cancelar()
	{
		unset($this->s__datos_requisitos);
		$this->s__filtro_requisitos = "";
	}

	function conf__filtro_requisitos(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos_requisitos)) {
			$filtro->set_datos($this->s__datos_requisitos);
		}		
	}	
	
	/**
	 * Api resetear los filtros 
	 * 
	 * @return 
	 */
	function resetear_filtros()
	{
		$this->evt__filtro_requisitos__cancelar();
		parent::resetear_filtros();
	}
	
	
	//-----------------------------------------------------------------------------------
    //---- Configuraciones --------------------------------------------------------------
    //-----------------------------------------------------------------------------------
 
    function evt__p_actividades__entrada()
    {
    	$this->controlador->set_tipo_elementos_seleccionables('A');
    }
 
    function evt__p_modulos__entrada()
    {
    	$this->controlador->set_tipo_elementos_seleccionables('M');
    }
 
    function evt__p_requisitos__entrada()
    {
    	$this->controlador->set_tipo_elementos_seleccionables('R');
    }

}
?>