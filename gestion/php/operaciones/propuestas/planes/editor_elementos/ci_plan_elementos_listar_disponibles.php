<?php
class ci_plan_elementos_listar_disponibles extends guarani_pers_ci
{
	protected $elementos_seleccionadas;
	protected $tipo_seleccion;
	protected $msj_eof = '';

	protected $s__filtro_materias;
	protected $s__datos_materias;
	
	protected $s__filtro_genericas;
	protected $s__datos_genericas;
	
	protected $s__filtro_modulos;
	protected $s__datos_modulos;
	
	protected $s__filtro_rtf;
	protected $s__datos_rtf;
	
	//--------------------------------------------------------------------------
	//---- COMBO ---------------------------------------------------------------
	//--------------------------------------------------------------------------

	function get_actividades_tipos_abm()
	{
		$tipos = toba::consulta_php('co_elementos')->get_actividades_tipos_abm_adminplan();
		foreach ($tipos as $key => $tipo) {
			$retorno[$key]['id'] = $tipo['tipo'] . '%-%' . $tipo['subtipo'];
			$retorno[$key]['nombre'] = $tipo['nombre'];
		}
		return $retorno;
	}

	//--------------------------------------------------------------------------
	//---- elementos  ----------------------------------------------------------
	//--------------------------------------------------------------------------

	function get_elementos_seleccionados()
	{
		return aplanar_matriz($this->elementos_seleccionadas, 'id');
	}

	function get_tipo_seleccionados()
	{
		return $this->tipo_seleccion;
	}
	
	//--------------------------------------------------------------------------
	//---- MATERIAS ------------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__actividades(toba_ei_cuadro $cuadro)
	{
		$datos = $this->controlador->get_elementos_disponibles('A', $this->s__filtro_materias);
		$cuadro->evento('seleccion')->set_seleccion_multiple();
		$cuadro->deseleccionar();
		$cuadro->set_datos($datos);
		$etiquetas = guarani_etiquetas::get_campo('actividad');
		$this->msj_eof = '800_eof_cuadro';
		if (isset($this->s__filtro_materias)) {
			$this->msj_eof .= '_filtrado';
		}
		$msj = guarani_mensajes::get($this->msj_eof, array($etiquetas->get_enf('plural')));
		$cuadro->set_eof_mensaje($msj);
	}

	function evt__actividades__seleccion($seleccion)
	{
		$this->elementos_seleccionadas = $seleccion;
		$this->tipo_seleccion = 'A';
	}

	//--------------------------------------------------------------------------
	//---- GENERICAS -----------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__materias_genericas(toba_ei_cuadro $cuadro)
	{
		$datos = $this->controlador->get_elementos_disponibles('MG', $this->s__filtro_genericas);
		$cuadro->evento('seleccion')->set_seleccion_multiple();
		$cuadro->deseleccionar();
		$cuadro->set_datos($datos);
	}

	//--------------------------------------------------------------------------
	//---- MODULOS -------------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__modulos(toba_ei_cuadro $cuadro)
	{

		$cuadro->evento('seleccion')->set_seleccion_multiple();
		$cuadro->deseleccionar();
		$plan_version = $this->cn()->entidad()->get_id_plan_version();

		$this->msj_eof = '800_eof_cuadro';
		if (!isset($this->s__filtro_modulos)) {
			$this->msj_eof .= '_sinfiltro';
		} else {
			if (isset($plan_version)) {
				$this->s__filtro_modulos = $this->s__filtro_modulos . " AND sga_planes_versiones.plan_version <> $plan_version";
			}
			$datos = $this->controlador->get_elementos_disponibles('M', $this->s__filtro_modulos);
			$cuadro->set_datos($datos);
			//Si el filtro no es vacío se agrega el mensaje de eof del cuadro con filtro.
			if (isset($this->s__filtro_modulos)) {
				$this->msj_eof .= '_filtrado';
			}
		}
		$etiquetas = $etiquetas = guarani_etiquetas::get_campo('modulo');
		$msj_cuadro = guarani_mensajes::get($this->msj_eof, array($etiquetas->get_enf('plural')));
		$cuadro->set_eof_mensaje($msj_cuadro);
	}

	function evt__modulos__seleccion($seleccion)
	{
		$this->elementos_seleccionadas = $seleccion;
		$this->tipo_seleccion = 'M';
	}

	function conf_evt__modulos__ver_componentes($evento, $fila)
	{
		$datos = $this->dep('modulos')->get_datos();
		$datos_fila = $datos[$fila];

		$info = array();
		$info['plan_version'] = $datos_fila['plan_version'];
		$info['id'] = $datos_fila['id'];

		foreach ($info as $dato => $valor) {
			$evento->vinculo()->agregar_parametro(utf8_encode($dato), utf8_encode($valor));
		}
	}

	//--------------------------------------------------------------------------
	//---- MODULOS RTF ---------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__modulos_rtf(toba_ei_cuadro $cuadro)
	{

		$cuadro->evento('seleccion')->set_seleccion_multiple();
		$cuadro->deseleccionar();
		
		$this->msj_eof = '800_eof_cuadro';
		if (!isset($this->s__filtro_rtf)) {
			$this->msj_eof .= '_sinfiltro';
		} else {
			$datos = $this->controlador->get_elementos_disponibles('RTF', $this->s__filtro_rtf);
			$cuadro->set_datos($datos);
			//Si el filtro no es vacío se agrega el mensaje de eof del cuadro con filtro.
			if (isset($this->s__filtro_rtf)) {
				$this->msj_eof .= '_filtrado';
			}
		}
		$etiquetas = guarani_etiquetas::get_campo('modulo');
		$msj_cuadro = guarani_mensajes::get($this->msj_eof, array($etiquetas->get_enf('plural')));
		$cuadro->set_eof_mensaje($msj_cuadro);
	}

	function evt__modulos_rtf__seleccion($seleccion)
	{
		$this->elementos_seleccionadas = $seleccion;
		$this->tipo_seleccion = 'RTF';
	}
	
	//--------------------------------------------------------------------------
	//------ Filtro ------------------------------------------------------------
	//--------------------------------------------------------------------------

	function evt__filtro_materias__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_materias = $datos;
			$filtro = array();
			$of = $this->dep('filtro_materias');
			foreach ($of->get_ids_columnas() as $key => $id) {
				if ($id == 'tipo') {
					$estado = $of->columna($id)->get_estado();
					if ($estado['valor']) {
						$t = explode('%-%', $estado['valor']);
						$where_temp = "((sga_g3entidades_subtipos.entidad_tipo = '" . $t[0] . "') AND (sga_g3entidades_subtipos.entidad_subtipo =" . $t[1] . "))";
						if ($estado['condicion'] != 'es_igual_a') {
							$where_temp = "NOT " . $where_temp;
						}
						$filtro[$id] = $where_temp;
					}
				} else {
					if ($of->columna($id)->get_sql_where()) {
						$filtro[$id] = $of->columna($id)->get_sql_where();
					}
				}
			}

			if (!($filtro == Array())) {
				$this->s__filtro_materias = "(" . $this->s__filtro_materias = implode(" AND ", $filtro) . ")";
			} else {
				$this->s__filtro_materias = "";
			}

			$this->s__datos_materias = $datos;
		} else {
			unset($this->s__datos_materias);
			$this->s__filtro_materias = "";
		}
	}

	function evt__filtro_materias__cancelar()
	{
		unset($this->s__datos_materias);
		$this->s__filtro_materias = "";
	}

	function conf__filtro_materias(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos_materias)) {
			$filtro->set_datos($this->s__datos_materias);
		}
	}
	
	//--------------------------------------------------------------------------
	//---- filtro_genericas ----------------------------------------------------
	//--------------------------------------------------------------------------
	
	function evt__filtro_genericas__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_genericas = $datos;
			$filtro = $this->dep('filtro_genericas')->get_sql_where();
			$this->s__filtro_genericas = $filtro;
		} else {
			unset($this->s__datos_genericas);
			$this->s__filtro_genericas = "";
		}
	}

	function evt__filtro_genericas__cancelar()
	{
		unset($this->s__datos_genericas);
		$this->s__filtro_genericas = "";
	}

	function conf__filtro_genericas(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos_genericas)) {
			$filtro->set_datos($this->s__datos_genericas);
		}
	}

	//--------------------------------------------------------------------------
	//---- filtro_modulos ------------------------------------------------------
	//--------------------------------------------------------------------------

	/**
	 * Api resetear los filtros 
	 * 
	 * @return 
	 */
	function resetear_filtros()
	{
		$this->evt__filtro_modulos__cancelar();
		$this->evt__filtro_genericas__cancelar();
		$this->evt__filtro_materias__cancelar();
	}

	function evt__filtro_modulos__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_modulos = $datos;
			$filtro = $this->dep('filtro_modulos')->get_sql_where();
			$this->s__filtro_modulos = $filtro;
		} else {
			unset($this->s__datos_modulos);
			$this->s__filtro_modulos = "";
		}
	}

	function evt__filtro_modulos__cancelar()
	{
		unset($this->s__datos_modulos);
		unset($this->s__filtro_modulos);
	}

	function conf__filtro_modulos(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos_modulos)) {
			$filtro->set_datos($this->s__datos_modulos);
		}
	}

	//--------------------------------------------------------------------------
	//---- filtro_mod_rtf ------------------------------------------------------
	//--------------------------------------------------------------------------
	
	function evt__filtro_mod_rtf__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_rtf = $datos;
			$filtro = $this->dep('filtro_mod_rtf')->get_sql_where();
			$this->s__filtro_rtf = $filtro;
		} else {
			unset($this->s__datos_rtf);
			$this->s__filtro_rtf = "";
		}
	}

	function evt__filtro_mod_rtf__cancelar()
	{
		unset($this->s__datos_rtf);
		unset($this->s__filtro_rtf);
	}

	function conf__filtro_mod_rtf(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos_rtf)) {
			$filtro->set_datos($this->s__datos_rtf);
		}
	}
}
?>