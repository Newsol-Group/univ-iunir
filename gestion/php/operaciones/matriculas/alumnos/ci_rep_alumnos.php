<?php
class ci_rep_alumnos extends ci_rep_sin_listado_inicial
{
	// Manejo de filtros especiales
	protected $s__calcular_promedio = false;//Esta variable se asigna en true, cuando se asigna una columna opcional que requiera del calculo del promedio
	protected $s__columnas = [];	
	
	/*
	 * El metodo devuelve un array con los valores de las columnas optativas que queremos mostrar en el reporte
	 * Lo interesante del array es la columna "metodo", que tiene el nombre de la funcion definida en co_alumnos.php.
	 */
	function get_opciones_columnas_optativas() 
	{
		return [
			['valor' => 'fecha_ingreso', 'descr' => 'Fecha Ingreso','metodo'=>'get_columna_dinamica_fecha_ingreso'],
			['valor' => 'fecha_ultimo_examen', 'descr' => 'Último Examen','metodo'=>'get_columna_dinamica_fecha_ultimo_examen'],
			['valor' => 'ultima_reinscripcion', 'descr' => 'Última Reinscripción','metodo'=>'get_columna_dinamica_ultima_reinscripcion'],
			['valor' => 'promedio', 'descr' => 'Prom. con Aplazos','metodo'=>'get_columna_dinamica_promedio'],
			['valor' => 'promedio_sin_aplazos', 'descr' => 'Prom. sin Aplazos','metodo'=>'get_columna_dinamica_promedio_sin_aplazos'],
			['valor' => 'cant_actividades_aprobadas', 'descr' => 'Actividades Aprobadas','metodo'=>'get_columna_dinamica_cant_actividades_aprobadas'],
			['valor' => 'cant_actividades_plan', 'descr' => 'Total Actividades','metodo'=>'get_columna_dinamica_cant_actividades_plan'],
			['valor' => 'nro_telefono', 'descr' => 'Teléfono','metodo'=>'get_columna_dinamica_nro_telefono'],
			['valor' => 'email', 'descr' => 'Email','metodo'=>'get_columna_dinamica_email'],
			['valor' => 'fecha_nacimiento', 'descr' => 'Fecha de Nacimiento','metodo'=>'get_columna_dinamica_fecha_nacimiento'],
			['valor' => 'fecha_egreso', 'descr' => 'Fecha de egreso','metodo'=>'get_columna_dinamica_fecha_egreso'],
			['valor' => 'nombre_pais', 'descr' => 'Pais de Origen','metodo'=>'get_columna_dinamica_pais_origen'],
			['valor' => 'coeficiente', 'descr' => 'Coeficiente','metodo'=>'get_columna_dinamica_coeficiente'],
		];
	}
	
	function get_columna_optativa($valor)
	{
		$columna = '';
		$columnas_optativas = $this->get_opciones_columnas_optativas();
		foreach ($columnas_optativas as $key => $col) {
			if($col['valor'] == $valor){
				$columna = $col;				
			}
		}
		return $columna;
	}
	
	function get_tabla_principal()
	{
		return 'sga_alumnos';
	}

	function get_datos($filtro)
	{
		$i = 0;
		$valor_y_metodos = [];
		// En este while se arma el arreglo con las columnas seleccionas del filtro, con ellas van el valor de la columna y el metodo usado en co_alumnos->get_reporte_alumnos
		while($i < count($this->s__columnas)){
			$columna	=	$this->get_columna_optativa($this->s__columnas[$i]);
			$valor		=	isset($columna['valor']) ? $columna['valor'] : '';
			$metodo		=	isset($columna['metodo']) ? $columna['metodo'] : '';
			
			$valor_y_metodos[] = ['valor'	=>	$valor, 'metodo'	=>	$metodo];
			$i++;
		}
			
		return guarani::co('co_alumnos')->get_reporte_alumnos($filtro,$valor_y_metodos, $this->s__calcular_promedio);
	}

	//---- cuadro -----------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		parent::conf__cuadro($cuadro);
		$cuadro->set_etiqueta_cantidad_filas_profundidad('Total Propuesta Formativa', 0);
		$cuadro->set_etiqueta_cantidad_filas_profundidad('Total Ubicación', 1);
		$columnas_optativas = aplanar_matriz($this->get_opciones_columnas_optativas(), 'valor');
		$columnas_eliminar = array_diff($columnas_optativas, $this->s__columnas);
		$cuadro->eliminar_columnas($columnas_eliminar);
	}

	function conf__filtro(\toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('prom_con_aplazos')->set_condicion_fija('es_mayor_que');
		$filtro->columna('prom_sin_aplazos')->set_condicion_fija('es_mayor_que');
		$filtro->columna('responsable_academica')->set_condicion_fija('es_igual_a');
		
	}

	function evt__filtro__filtrar($datos)
	{
		$filtros_para_promedio = ['prom_con_aplazos', 'prom_sin_aplazos', 'ultima_reinscripcion', 'cantidad'];
		foreach ($filtros_para_promedio as $f) {
			if(array_key_exists($f, $datos)){
				$this->s__calcular_promedio = true;
			}
		}
		//---- Manejo de columnas optativas ------------------------------------
		if (isset($datos)) {
			if (isset($datos['columnas_opc'])) {
				$this->s__columnas = $datos['columnas_opc']['valor'];
				$this->dep('filtro')->columna('columnas_opc')->resetear_estado();
			} else {
				$this->s__columnas = array();
			}
		}
		$filtro_responsable = '';
		if ((isset($datos['responsable_academica']))) {
			$filtro_responsable = $datos['responsable_academica']['condicion'] == 'es_igual_a' ? ' AND ' : ' AND NOT ';
			$filtro_responsable .= " sga_alumnos.propuesta IN (SELECT propuesta FROM sga_propuestas_ra WHERE responsable_academica = '{$datos['responsable_academica']['valor']}')";
			$this->dep('filtro')->columna('responsable_academica')->resetear_estado();
		}

		$filtro_ultima_reinscripcion = '';
		if ((isset($datos['ultima_reinscripcion']))) {
			$condicion = $datos['ultima_reinscripcion']['condicion'] == 'es_igual_a' ? ' = ' : ' <> ';
			$filtro_ultima_reinscripcion .= " AND COALESCE((SELECT r.anio_academico FROM sga_reinscripciones as r WHERE r.alumno = sga_alumnos.alumno ORDER BY r.anio_academico DESC   LIMIT 1), 0) $condicion {$datos['ultima_reinscripcion']['valor']}";
			$this->dep('filtro')->columna('ultima_reinscripcion')->resetear_estado();
		}

		if ((isset($datos['estado_propuesta']))) {
			if ($datos['estado_propuesta']['valor'] !== propuesta::inscripcion_rechazada) {
				$this->dep('filtro')->columna('motivo_rechazo')->resetear_estado();
			}
		}
		$filtro_grupo_propuesta = '';
		if (isset($datos['grupo_propuesta'])) {
			$filtro_grupo_propuesta = " AND sga_propuestas_aspira.propuesta IN (SELECT propuesta FROM sga_propuestas_x_grupo WHERE sga_propuestas_x_grupo.grupo_propuesta = '{$datos['grupo_propuesta']['valor']}')";
			$this->dep('filtro')->columna('grupo_propuesta')->resetear_estado();
		}
		$filtro_estado_propuesta = "";
		if (isset($datos['estado_propuesta'])) {
			$valor = quote($datos['estado_propuesta']['valor']);
			$condicion = quote(propuesta::inscripcion_aceptada . '-' . propuesta::inscripcion_pendiente);
			if ($valor != $condicion) {
				$filtro_estado_propuesta = " AND sga_situacion_aspirante.resultado_asp = {$valor}";
			} else {
				$a = quote(propuesta::inscripcion_aceptada);
				$p = quote(propuesta::inscripcion_pendiente);
				$filtro_estado_propuesta = " AND sga_situacion_aspirante.resultado_asp IN (SELECT resultado_asp FROM sga_situacion_aspirante WHERE sga_situacion_aspirante.resultado_asp = $a OR sga_situacion_aspirante.resultado_asp = $p)";
			}
			$this->dep('filtro')->columna('estado_propuesta')->resetear_estado();
		}

		if (isset($datos['motivo_rechazo'])) {
			$valor = $datos['motivo_rechazo']['valor'];
			if ($valor == -1) {
				$this->dep('filtro')->columna('motivo_rechazo')->resetear_estado();
			}
		}
		$filtro_estado_legajo = "";
		if (isset($datos['legajo'])) {
			$valor = $datos['legajo']['valor'];
			if ($valor == comunes::si) {
				$filtro_estado_legajo = " AND sga_alumnos.legajo is not null";
			} else {
				$filtro_estado_legajo = " AND sga_alumnos.legajo is null";
				
			}
			$this->dep('filtro')->columna('legajo')->resetear_estado();
		}
		$filtro_motivo_calidad = "";
		if(isset($datos['motivo_calidad'])){
			$motivo_calidad = $datos['motivo_calidad']['valor'];
			$filtro_motivo_calidad = "AND EXISTS (SELECT 1
				FROM sga_alumnos_hist_calidad as ha
				WHERE ha.cambio_calidad = (SELECT MAX ( ha2.cambio_calidad ) FROM sga_alumnos_hist_calidad as ha2 WHERE ha2.alumno = sga_alumnos.alumno)
				AND ha.motivo_calidad = $motivo_calidad
				)";
			$this->dep('filtro')->columna('motivo_calidad')->resetear_estado();
		}
		$filtro_titulo = "";
		if(isset($datos['titulo_obtenido'])){
			$certificado = $datos['titulo_obtenido']['valor'];
			$filtro_titulo = "AND EXISTS (SELECT 1 FROM sga_certificados_otorg WHERE alumno = sga_alumnos.alumno "
					. "AND certificado = $certificado AND anulado = 0)";
			$this->dep('filtro')->columna('titulo_obtenido')->resetear_estado();
		}
		$filtro_cant_regularizadas = '';
		if ((isset($datos['cant_regularizadas']))) {
			$valor = $datos['cant_regularizadas']['valor'];
			switch ($datos['cant_regularizadas']['condicion']) {
				case 'es_igual_a':
					$condicion = '=';
					break;
				case 'es_distinto_de':
					$condicion = '!=';
					break;
				case 'es_mayor_que':
					$condicion = '>';
					break;
				case 'es_mayor_igual_que':
					$condicion = '>=';
					break;
				case 'es_menor_que':
					$condicion = '<';
					break;
				case 'es_menor_igual_que':
					$condicion = '<=';
					break;
				case 'entre':
					$condicion = 'BETWEEN';
					$valor = "{$datos['cant_regularizadas']['valor']['desde']} AND {$datos['cant_regularizadas']['valor']['hasta']}";
					break;
			}
			$filtro_cant_regularizadas .= "AND (SELECT	COUNT(1)
												FROM	vw_regularidades_basica
												WHERE	vw_regularidades_basica.alumno = sga_alumnos.alumno AND
														vw_regularidades_basica.resultado = '".comunes::resultado_nota_aprobado."' AND
														(vw_regularidades_basica.fecha_vigencia IS NULL OR vw_regularidades_basica.fecha_vigencia >= CURRENT_DATE)) $condicion $valor";
			$this->dep('filtro')->columna('cant_regularizadas')->resetear_estado();
		}
		$filtro_nro_documento = '';
		if ((isset($datos['nro_documento']))) {
			$expresion_filtro_nro_documento = $this->dep('filtro')->columna('nro_documento')->get_sql_where();
			$filtro_nro_documento = "AND EXISTS (SELECT mdp_personas_documentos.documento FROM mdp_personas_documentos WHERE mdp_personas_documentos.persona = vw_personas.persona AND $expresion_filtro_nro_documento)";
			$this->dep('filtro')->columna('nro_documento')->resetear_estado();
		}

		if (isset($datos) && !empty($datos)) {
			$this->s__datos = $datos;
			$this->s__filtro = " {$this->dep('filtro')->get_sql_where()} $filtro_responsable $filtro_ultima_reinscripcion $filtro_grupo_propuesta $filtro_estado_propuesta $filtro_estado_legajo $filtro_motivo_calidad $filtro_titulo $filtro_cant_regularizadas $filtro_nro_documento";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

}