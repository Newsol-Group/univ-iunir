<?php
class ci_rep_alumnos_por_actividad_academica extends ci_rep_sin_listado_inicial
{
	function get_tabla_principal()
	{
		return 'sga_alumnos';
	}

	function get_datos($filtro)
	{
		return guarani::co('co_alumnos')->get_reporte_alumnos_promedio($filtro);
	}
	
	function get_planes_propuestas_activos($propuesta)
	{
		$activo = quote(planes::estado_activo);
		$vigente = quote(planes::estado_activo_vigente);
		$filtro = " sga_planes.estado IN ($activo, $vigente)";
		return guarani::co('co_planes')->get_planes_propuesta($propuesta, $filtro);
	}

	//---- cuadro -----------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		parent::conf__cuadro($cuadro);
		$cuadro->set_etiqueta_cantidad_filas_profundidad('Total Propuesta Formativa',0);
		$cuadro->set_etiqueta_cantidad_filas_profundidad('Total Ubicación',1);
	}
	
	function conf__filtro(\toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('cantidad')->set_condicion_fija('es_mayor_igual_que');
		$filtro->columna('prom_con_aplazos')->set_condicion_fija('es_mayor_que');
		$filtro->columna('prom_sin_aplazos')->set_condicion_fija('es_mayor_que');
		$filtro->columna('responsable_academica')->set_condicion_fija('es_igual_a');

        $bandera = false;
        if (isset($this->s__datos['propuesta'])) {
            $propuesta = $this->s__datos['propuesta']['valor'];
            $plan = '';
            $bandera = true;
        }
        if (isset($this->s__datos['plan'])) {
            $plan = $this->s__datos['plan']['valor'];
        }
        if ($bandera) {
            $filtro->columna('actividades')->get_ef()->vinculo()->set_parametros(array('propuesta' => $propuesta, 'plan' => $plan));
        }
	}
	
	function evt__filtro__filtrar($datos)
	{
		// RESPONSABLE ACADEMICA	
		$filtro_responsable = '';
		if (isset($datos['responsable_academica'])) {
			$filtro_responsable = $datos['responsable_academica']['condicion'] == 'es_igual_a' ? ' AND ' : ' AND NOT ';			
			$filtro_responsable .= " sga_alumnos.propuesta IN (SELECT propuesta FROM sga_propuestas_ra WHERE responsable_academica = '{$datos['responsable_academica']['valor']}')";
			$this->dep('filtro')->columna('responsable_academica')->resetear_estado();
		}
		
		// ULTIMA REINSCRIPCIÓN A PROPUESTA
		$filtro_ultima_reinscripcion = '';
		if (isset($datos['ultima_reinscripcion'])) {
			$condicion = $datos['ultima_reinscripcion']['condicion'] == 'es_igual_a' ? ' = ' : ' <> ';			
			$filtro_ultima_reinscripcion .= " AND COALESCE((SELECT r.anio_academico FROM sga_reinscripciones as r WHERE r.alumno = sga_alumnos.alumno ORDER BY r.anio_academico DESC   LIMIT 1), 0) $condicion {$datos['ultima_reinscripcion']['valor']}";			
			$this->dep('filtro')->columna('ultima_reinscripcion')->resetear_estado();
		}
        
        	// ACTIVIDADES
		//Si esta el popup con las actividades seleccionadas entonces agrego el filtro esté.
		$filtro_actividades = '';
		if (isset($datos['actividades'])) {            
			// Voy a contar la cantidad de actividades seleccionadas.
			$aux = explode(',', $datos['actividades']['valor']);
			$cantidad = count($aux);            
			
			if ($datos['actividades']['condicion'] == 'es_igual_a') {
				$filtro_actividades .= "AND $cantidad = (SELECT COUNT(DISTINCT elemento) FROM vw_hist_academica_basica as a WHERE a.alumno = sga_alumnos.alumno AND a.resultado = 'A' AND a.elemento IN ({$datos['actividades']['valor']}))";
			} else {
				// Condicion: es_distinto_de.
				// El alumno no aprobó ninguna de las actividades seleccionadas
				$filtro_actividades .= "AND 0 = (SELECT COUNT(DISTINCT elemento) FROM vw_hist_academica_basica as a WHERE a.alumno = sga_alumnos.alumno AND a.resultado = 'A' AND a.elemento IN ({$datos['actividades']['valor']}))";
			}

			$this->dep('filtro')->columna('actividades')->resetear_estado();
		}
		
		if (isset($datos) && !empty($datos)) {
			$this->s__datos = $datos;
			$this->s__filtro = " {$this->dep('filtro')->get_sql_where()} $filtro_responsable $filtro_ultima_reinscripcion $filtro_actividades";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}
    
    function get_actividades($variable)
    {
        //Voy a buscar las actividades con ese nombre.
        $where = " sga_elementos.elemento IN ($variable)";
        $datos = toba::consulta_php('co_elementos')->get_listado(elemento::tipo_actividad, null, $where);
        $datos = aplanar_matriz($datos, 'nombre');        
        return implode(', ', $datos);
    }

}
?>
