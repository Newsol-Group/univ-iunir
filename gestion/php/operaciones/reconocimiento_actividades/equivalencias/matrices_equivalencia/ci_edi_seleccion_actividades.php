<?php
/**
 *
 *
 * @package Reconocimiento de Actividades
 * @subpackage Generar Matriz de Equivalencia
 */
class ci_edi_seleccion_actividades extends guarani_pers_ci
{
	// Datos filtros
	protected $s__filtro_activ_origen;
	protected $s__datos_filtro_activ_origen;
	protected $s__filtro_activ_destino;
	protected $s__datos_filtro_activ_destino;
	
	// Datos de las selecciones previas y de la matriz padre.
	protected $s__plan_version;
	protected $s__datos_matriz_grupo;
	
	// Generales
	protected $indx_msj_eof = '800_eof_cuadro';

	function ini()
	{
		$this->s__plan_version = $this->controlador()->get_plan_version_seleccionado();
		$this->s__datos_matriz_grupo = $this->controlador()->get_datos_matriz_asociada();
	}
	
	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function resetear_vars()
	{
		unset($this->s__filtro_activ_origen);
		unset($this->s__datos_filtro_activ_origen);
		unset($this->s__filtro_activ_destino);
		unset($this->s__datos_filtro_activ_destino);
		unset($this->s__plan_version);
		unset($this->s__datos_matriz_grupo);
	}
	
	//-----------------------------------------------------------------------------------
	//---- filtro_actividades_origen ----------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_actividades_origen(guarani_ei_filtro $filtro)
	{
		if (isset($this->s__datos_filtro_activ_origen)) {
			$filtro->set_datos($this->s__datos_filtro_activ_origen);
		}
	}

	function evt__filtro_actividades_origen__filtrar($datos)
	{
		if (isset($datos) && !empty($datos)) {
			$this->s__datos_filtro_activ_origen = $datos;
			$this->s__filtro_activ_origen = $this->dep('filtro_actividades_origen')->get_sql_where();
		} else {
			unset($this->s__datos_filtro_activ_origen);
			$this->s__filtro_activ_origen = '';
		}
	}

	function evt__filtro_actividades_origen__cancelar()
	{
		unset($this->s__datos_filtro_activ_origen);
		unset($this->s__filtro_activ_origen);
	}

	//-----------------------------------------------------------------------------------
	//---- filtro_actividades_destino ---------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_actividades_destino(guarani_ei_filtro $filtro)
	{
		if (isset($this->s__datos_filtro_activ_destino)) {
			$filtro->set_datos($this->s__datos_filtro_activ_destino);
		} 
	}

	function evt__filtro_actividades_destino__filtrar($datos)
	{
		if (isset($datos) && !empty($datos)) {
			$this->s__datos_filtro_activ_destino = $datos;
			$this->s__filtro_activ_destino = $this->dep('filtro_actividades_destino')->get_sql_where();
		} else {
			unset($this->s__datos_filtro_activ_destino);
			$this->s__filtro_activ_destino = '';
		}
	}

	function evt__filtro_actividades_destino__cancelar()
	{
		unset($this->s__datos_filtro_activ_destino);
		unset($this->s__filtro_activ_destino);
	}
	
	//-----------------------------------------------------------------------------------
	//---- seleccion_activ_origen -------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__seleccion_activ_origen(toba_ei_cuadro $cuadro)
	{
		// Inicialmente se trae el listado vacío hasta que el usuario presione filtrar.
		$indx_msj = $this->indx_msj_eof;
		if (!isset($this->s__filtro_activ_origen)) {
			$indx_msj .= '_sinfiltro';
			$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj));
		} else {
			//El usuario presiono filtrar (ya sea con datos o con el filtro vacío).
			if (isset($this->s__datos_filtro_activ_origen)) {
				$indx_msj .= '_filtrado';
			}
			$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj, array('Actividades del Origen seleccionado')));
			// Las actividades a recuperar dependen del Alcance de la Matriz padre del Grupo actual.
			$alcance = $this->s__datos_matriz_grupo['alcance'];
			$actividades_origen = array();
			
			/**
			 * Se deben eliminar de las actividades posibles las que ya se encuentran actualmente asociadas como 
			 * actividades origen del grupo
			 */
			$filtro_activ_origen = 'true';
			if (trim($this->s__filtro_activ_origen) != '') {
				$filtro_activ_origen .= ' AND ' . $this->s__filtro_activ_origen;
			}
			$activ_origen_asociadas = $this->controlador()->get_actividades_origen_asociadas();
			// Arma la condición not in para el filtro.
			if (!empty($activ_origen_asociadas)) {
				$cond_not_in = guarani_sql::armar_condicion_in($activ_origen_asociadas, 'sga_elementos', 'elemento', 'elemento', true);
				$filtro_activ_origen .= ' AND ' . $cond_not_in; 
			}
			
			switch ($alcance) {
				case equivalencia::matriz_alcance_general: 
					$actividades_origen = toba::consulta_php('co_elementos')->get_elementos($filtro_activ_origen);				
					break;
				case equivalencia::matriz_alcance_entre_propuestas:
					$prop_origen = $this->s__datos_matriz_grupo['propuesta_origen'];
					$filtro_activ_origen .= " AND vw_elementos_plan.propuesta = $prop_origen"; 
					$actividades_origen = toba::consulta_php('co_elementos')->get_elementos($filtro_activ_origen);
					break;
				case equivalencia::matriz_alcance_entre_planes_otra_prop:
				case equivalencia::matriz_alcance_entre_planes_misma_prop:
					// Para el caso de alcance entre planes de la misma propuesta la propuesta origen será igual a la destino.
					$prop_origen = $this->s__datos_matriz_grupo['propuesta_origen'];
					$filtro_activ_origen .= " AND vw_elementos_plan.propuesta = $prop_origen"; 
					if (isset($this->s__datos_matriz_grupo['plan_origen'])) {
						// Por opción 'Todos' plan origen puede ser nulo.
						$plan_origen = $this->s__datos_matriz_grupo['plan_origen'];
						$filtro_activ_origen .= " AND vw_elementos_plan.plan = $plan_origen";
					}
					$actividades_origen = toba::consulta_php('co_elementos')->get_elementos($filtro_activ_origen);
					break;
				case equivalencia::matriz_alcance_entre_versiones:
					// La propuesta y plan origen son los mismos que los seleccionados como destino de la matriz.
					$prop_origen = $this->s__datos_matriz_grupo['propuesta_origen'];
					$plan_origen = $this->s__datos_matriz_grupo['plan_origen'];	
					$filtro = $filtro_activ_origen . " AND vw_elementos_plan.propuesta = $prop_origen" . " AND vw_elementos_plan.plan = $plan_origen"; 
					if (isset($this->s__datos_matriz_grupo['plan_version_origen'])) {
						// Por opción 'Todas' puede ser nulo.
						$version_origen = $this->s__datos_matriz_grupo['plan_version_origen'];
						$filtro .= " AND vw_elementos_plan.plan_version = $version_origen";
					}
					$actividades_origen = toba::consulta_php('co_elementos')->get_elementos($filtro);
			}		
			$cuadro->set_datos($actividades_origen);
		}
	}

	function evt__seleccion_activ_origen__seleccion($seleccion)
	{
		$this->activ_origen_seleccionadas = $seleccion;
	}

	function evt__seleccion_activ_origen__agregar()
	{
		$this->controlador()->agregar_actividades_origen($this->activ_origen_seleccionadas);
	}

	//-----------------------------------------------------------------------------------
	//---- seleccion_activ_destino ------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__seleccion_activ_destino(toba_ei_cuadro $cuadro)
	{
		// Inicialmente se trae el listado vacío hasta que el usuario presione filtrar.
		$indx_msj = $this->indx_msj_eof;
		if (!isset($this->s__filtro_activ_destino)) {
			$indx_msj .= '_sinfiltro';
			$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj));
		} else {
			//El usuario presiono filtrar (ya sea con datos o con el filtro vacío).
			if (isset($this->s__datos_filtro_activ_destino)) {
				$indx_msj .= '_filtrado';
			}
			$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj, array('Actividades del Destino seleccionado')));
			/**
			 * Se traen las actividades del plan versión destino de la matriz padre del grupo.
			 * Se deben eliminar de las actividades posibles las que ya se encuentran actualmente asociadas como 
			 * actividades destino del grupo.
			 */
			$filtro_activ_destino = 'true';
			if (trim($this->s__filtro_activ_destino) != '') {
				$filtro_activ_destino .= ' AND ' . $this->s__filtro_activ_destino;
			}
			$activ_destino_asociadas = $this->controlador()->get_actividades_destino_asociadas();
			if (!empty($activ_destino_asociadas)) {
				// Arma la condición not in para el filtro.
				$cond_not_in = guarani_sql::armar_condicion_in($activ_destino_asociadas, 'sga_elementos', 'elemento', 'elemento', true);
				$filtro_activ_destino .= ' AND ' . $cond_not_in;
			}
			$actividades_destino = toba::consulta_php('co_elementos')->get_actividades_plan_version($this->s__datos_matriz_grupo['plan_version_destino'], $filtro_activ_destino);
			$cuadro->set_datos($actividades_destino);
		}
	}

	function evt__seleccion_activ_destino__seleccion($seleccion)
	{
		$this->activ_destino_seleccionadas = $seleccion;
	}

	function evt__seleccion_activ_destino__agregar()
	{
		$this->controlador()->agregar_actividades_destino($this->activ_destino_seleccionadas);
	}
	
}
?>