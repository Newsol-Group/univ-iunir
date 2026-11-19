<?php
class ci_administrar_plan_elementos_listar_disponibles extends ci_plan_elementos_listar_disponibles
{
	// Se redefinen las funciones del filtro para agregar el filtro sobre 
	// Actividades del Plan Personalizado por Convenio.

	//--------------------------------------------------------------------------
	//---- filtro --------------------------------------------------------------
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
					if (isset($estado['valor']) && $estado['valor']) {
						$t = explode('%-%', $estado['valor']);
						$where_temp = "((sga_g3entidades_subtipos.entidad_tipo = '" . $t[0] . "') AND (sga_g3entidades_subtipos.entidad_subtipo =" . $t[1] . "))";
						if ($estado['condicion'] != 'es_igual_a') {
							$where_temp = "NOT " . $where_temp;
						}
						$filtro[$id] = $where_temp;
					}
				} elseif ($id != 'convenio') {
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

			// FILTRO PARA LAS ACTIVIDADES DISPONIBLES ASOCIADAS AL CONVENIO SELECCIONADO PARA EL PLAN.
			$filtro_convenio = '';
			if (isset($datos['convenio'])) {
				$filtro_convenio .= "sga_elementos.elemento IN 
																(SELECT	sga_convenios_actividades.elemento 
																 FROM	sga_convenios_actividades
																 WHERE	sga_convenios_actividades.convenio = '{$datos['convenio']['valor']}')";
				$this->dep('filtro_materias')->columna('convenio')->resetear_estado();
			}
			if (!empty($filtro_convenio)) {
				$this->s__filtro_materias = empty($this->s__filtro_materias) ? $filtro_convenio : $this->s__filtro_materias . ' AND ' . $filtro_convenio;
			}
			$this->s__datos_materias = $datos;
		} else {
			unset($this->s__datos_materias);
			$this->s__filtro_materias = "";
		}
	}

	function conf__filtro_materias(toba_ei_filtro $filtro)
	{
		// Para el caso de Plan Personalizado de Tipo Convenio, se agrega una columna para filtrar por Convenio las Actividades Disponibles.
		$tipo_plan = $this->cn()->entidad()->get_tipo_plan();
		if ($tipo_plan != planes::tipo_convenio) {
			$filtro->eliminar_columnas(array('convenio'));
		}
		if (isset($this->s__datos_materias)) {
			$filtro->set_datos($this->s__datos_materias);
		} elseif ($tipo_plan == planes::tipo_convenio && !isset($this->s__filtro_materias)) {
			// Para el caso de Plan Personalizado de Tipo Convenio, el filtro inicial se setea por defecto como el Convenio asociado al Plan.
			$convenio_id = $this->cn()->entidad()->get_convenio_plan_pers();
			$filtro->set_datos(array('convenio' => array('valor' => $convenio_id, 'condicion' => 'es_igual_a')));
			$this->s__filtro_materias = "sga_elementos.elemento IN 
										(SELECT	sga_convenios_actividades.elemento 
										 FROM	sga_convenios_actividades
										 WHERE	sga_convenios_actividades.convenio = '$convenio_id')";
		}
	}

}