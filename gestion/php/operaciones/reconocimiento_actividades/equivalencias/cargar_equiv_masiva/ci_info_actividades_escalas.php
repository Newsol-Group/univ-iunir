<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage Detalle Instancias
 */
class ci_info_actividades_escalas extends guarani_pers_ci
{
	protected $s__elementos;
	protected $s__plan_version;
	protected $s__datos = array();
	protected $mostrar_detalle = false;
	
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	function ini()
	{
		$parametro = toba::memoria()->get_parametro('elementos');
		if (!empty($parametro)) {
			$this->s__elementos = $parametro;
		}
		$parametro = toba::memoria()->get_parametro('plan_version');
		if (!empty($parametro)) {
			$this->s__plan_version = $parametro;
		}
		$parametro = toba::memoria()->get_parametro('mostrar_detalle');
		if (!empty($parametro)) {
			$this->mostrar_detalle = $parametro;
		}
		$parametro = toba::memoria()->get_dato('datos_cuadro_escalas');
		if (!is_null($parametro)) {
			$this->s__datos = $parametro;
		}
	}
	

	//-----------------------------------------------------------------------------------
	//---- PANTALLA: inicial
	//-----------------------------------------------------------------------------------
	
	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		if ($this->mostrar_detalle) {
			$pantalla->eliminar_dep('cuadro_datos_escalas');
		} else {
			$pantalla->eliminar_dep('cuadro_datos_escalas_detalle');
		}
	}

	//---- cuadro_datos_escalas ------------------------------------------------------
	
	function conf__cuadro_datos_escalas(toba_ei_cuadro $cuadro)
	{
		if (!empty($this->s__datos)) {
			$detalles_escalas = $this->s__datos;
		} else { 
			if (!empty($this->s__elementos)) {
				$filtro = 'sga_elementos.elemento IN (' . $this->s__elementos . ')';
				$detalles_escalas = toba::consulta_php('co_elementos')->get_escala_nota_equivalencia_elementos_plan_version($this->s__plan_version, $filtro);
			}
		}
		$cuadro->set_datos($detalles_escalas);
	}

	//---- cuadro_datos_escalas_detalle ------------------------------------------------------

	function conf__cuadro_datos_escalas_detalle(toba_ei_cuadro $cuadro)
	{
		if (!empty($this->s__datos)) {
			$detalles_escalas = $this->s__datos;
		} else { 
			if (!empty($this->s__elementos)) {
				$filtro = 'sga_elementos.elemento IN (' . $this->s__elementos . ')';
				$detalles_escalas = toba::consulta_php('co_elementos')->get_escala_nota_equivalencia_elementos_plan_version($this->s__plan_version, $filtro);
			}
		}
		$cuadro->set_datos($detalles_escalas);
	}
	
	//-----------------------------------------------------------------------------------
	//---- JavaScript -------------------------------------------------------------------
	//----------------------------------------------------------------------------------
	function extender_objeto_js()
	{
        //Se atiende el evento Cerrar, para provocar que se cierre la ventana popup.
          echo "
			{$this->objeto_js}.evt__cerrar = function() {
				window.close();
			}
		";
	}
	
}
?>