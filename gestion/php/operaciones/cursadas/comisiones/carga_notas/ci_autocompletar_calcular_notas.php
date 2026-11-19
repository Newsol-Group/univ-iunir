<?php
class ci_autocompletar_calcular_notas extends guarani_pers_ci
{
	
	function conf()
	{
		$opciones_instancias = $this->get_opciones_instancias();
		
		// Si no hay instancias para calcular notas deshabilito la pantalla/tab
		if (empty($opciones_instancias)) {
			$this->pantalla()->tab('calcular_notas')->anular();
		}
		
	}
	
	function get_instancias_comision()
	{
		$datos_comision = $this->controlador()->controlador()->get_datos_comision();
		return explode(",", $datos_comision['id_instancias']);
	}
	
	function get_opciones_combo_completar()
	{
		
		$instancias_comision = $this->get_instancias_comision();
		$tiene_instancia_regularidad = in_array(comision::inst_cursar, $instancias_comision);
		$tiene_instancia_promocion = in_array(comision::inst_promocionar, $instancias_comision);
		
		$cur_asentar_notas_actas_regulares = guarani::parametros()->get_valor('cur_asentar_notas_actas_regulares');
		$cur_asentar_notas_actas_promocion = guarani::parametros()->get_valor('cur_asentar_notas_actas_promocion');
		
		$opciones = [];
		
		// Si tiene la instancia de 'Regularidad' y el parámetro 'cur_asentar_notas_actas_regulares' tiene valor distinto a 'Sin Nota' entonces agrego 'Nota de Regularidad'
		if ($tiene_instancia_regularidad && ($cur_asentar_notas_actas_regulares != acta::ingreso_notas_sin_notas)) {
			$opciones[] = ['valor' => comision::autocompletar_campo_nota_cursada, 'descr' => 'Nota de Regularidad'];
		}
		
		// Si tiene la instancia de 'Promoción' y el parámetro 'cur_asentar_notas_actas_promocion' tiene valor distinto a 'Sin Nota' entonces agrego 'Nota de Promoción'
		if ($tiene_instancia_promocion && ($cur_asentar_notas_actas_promocion != acta::ingreso_notas_sin_notas)) {
			$opciones[] = ['valor' => comision::autocompletar_campo_nota_promocion, 'descr' => 'Nota de Promoción'];
		}
		
		// Si tiene la instancia de 'Regularidad' entonces agrego 'Resultado de Regularidad'
		if ($tiene_instancia_regularidad) {
			$opciones[] = ['valor' => comision::autocompletar_campo_resultado_cursada, 'descr' => 'Resultado de Regularidad'];
		}
		
		// Si tiene la instancia de 'Promoción' entonces agrego 'Resultado de Promoción'
		if ($tiene_instancia_promocion) {
			$opciones[] = ['valor' => comision::autocompletar_campo_resultado_promocion, 'descr' => 'Resultado de Promoción'];
		}
		
		$opciones[] = ['valor' => comision::autocompletar_campo_fecha, 'descr' => 'Fecha'];
		$opciones[] = ['valor' => comision::autocompletar_campo_observaciones, 'descr' => 'Observaciones'];

		return $opciones;
	}
	
	/*
	 * @see Ticket #43482 (https://redmine.siu.edu.ar/redmine/issues/43482)
	 */
	function get_opciones_resultados_regularidad()
	{
		$instancias_comision = $this->get_instancias_comision();
		$tiene_instancia_regularidad = in_array(comision::inst_cursar, $instancias_comision);
		
		$resultados_regularidad = [];
		
		// Si tiene instancia de Regularidad
		if ($tiene_instancia_regularidad) {
			// Si el parámetro 'cur_asentar_notas_actas_regulares' tiene el valor 'Sin Nota'
			if (guarani::parametros()->get_valor('cur_asentar_notas_actas_regulares') == acta::ingreso_notas_sin_notas) {
				$escala_nota_regularidad = $this->controlador()->get_escala_nota_regularidad();
				// Devuelvo todos los resultados posibles para la instancia de regularidad
				$resultados_regularidad = toba::consulta_php('co_escalas_notas')->get_resultados_posibles_instancia_escala_nota(comision::inst_cursar, $escala_nota_regularidad['escala_nota']);
			} else {// Si el parámetro 'cur_asentar_notas_actas_regulares' es distinto a 'Sin Nota'
				// Devuelvo solo resultado 'Ausente' 
				$resultados_regularidad = toba::consulta_php('co_escalas_notas')->get_escalas_notas_resultados_solo_ausente();
			}
		}
		
		return $resultados_regularidad;
	}
	
	/*
	 * @see Ticket #43482 (https://redmine.siu.edu.ar/redmine/issues/43482)
	 */
	function get_opciones_resultados_promocion()
	{
		$instancias_comision = $this->get_instancias_comision();
		$tiene_instancia_promocion = in_array(comision::inst_promocionar, $instancias_comision);
		
		$resultados_promocion = [];
		
		// Si tiene instancia de Promoción
		if ($tiene_instancia_promocion) {
			// Si el parámetro 'cur_asentar_notas_actas_promocion' tiene el valor 'Sin Nota'
			if (guarani::parametros()->get_valor('cur_asentar_notas_actas_promocion') == acta::ingreso_notas_sin_notas) {
				$escala_nota_promocion = $this->controlador()->get_escala_nota_promocion();
				// Devuelvo todos los resultados posibles para la instancia de promoción
				$resultados_promocion = toba::consulta_php('co_escalas_notas')->get_resultados_posibles_instancia_escala_nota(comision::inst_promocionar, $escala_nota_promocion['escala_nota']);
			} else {// Si el parámetro 'cur_asentar_notas_actas_promocion' es distinto a 'Sin Nota'
				// Devuelvo solo resultado 'Ausente' 
				$resultados_promocion = toba::consulta_php('co_escalas_notas')->get_escalas_notas_resultados_solo_ausente();
			}
		}
		
		return $resultados_promocion;
	}
	
	/*
	 * @see Ticket #43482 (https://redmine.siu.edu.ar/redmine/issues/43482)
	 */
	function get_opciones_instancias()
	{
		
		$instancias_comision = $this->get_instancias_comision();
		$tiene_instancia_regularidad = in_array(comision::inst_cursar, $instancias_comision);
		$tiene_instancia_promocion = in_array(comision::inst_promocionar, $instancias_comision);
		
		$cur_asentar_notas_actas_regulares = guarani::parametros()->get_valor('cur_asentar_notas_actas_regulares');
		$cur_asentar_notas_actas_promocion = guarani::parametros()->get_valor('cur_asentar_notas_actas_promocion');
		
		$instancias_comisiones = toba::consulta_php('co_comisiones')->get_listado_instancias_comisiones();
		
		foreach ($instancias_comisiones as $key => $instancia) {
			
			// Si la instancia es "Regularidad", 
			// y la comisión no tiene dicha instancia o el parámetro 'cur_asentar_notas_actas_regulares' tiene el valor 'Sin Nota' la elimino de la lista
			if (($instancia['instancia'] == comision::inst_cursar) &&
					(!$tiene_instancia_regularidad || $cur_asentar_notas_actas_regulares == acta::ingreso_notas_sin_notas)
				) 
			{
				unset($instancias_comisiones[$key]);
			}
			
			// Si la instancia es "Promoción",
			//  y la comisión no tiene dicha instancia o el parámetro 'cur_asentar_notas_actas_promocion' tiene el valor 'Sin Nota' la elimino de la lista
			if (($instancia['instancia'] == comision::inst_promocionar) && 
					(!$tiene_instancia_promocion || $cur_asentar_notas_actas_promocion == acta::ingreso_notas_sin_notas)
				) 
			{
				unset($instancias_comisiones[$key]);
			}
		}
		return $instancias_comisiones;
	}

	//-----------------------------------------------------------------------------------
	//---- AJAX -------------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ajax__calcular_notas($params, toba_ajax_respuesta $respuesta)
	{
		$php_clase_regla = guarani::co('co_reglas_calculo')->get_php_clase_regla($params['regla_calculo']);
		$clase = new $php_clase_regla();

		$datos_comision = $this->controlador()->controlador()->get_datos_comision();

		$alumnos = explode(',', $params['alumnos']);
		$notas = [];
		foreach ($alumnos as $alumno) {
			if (!empty($alumno)) {
				$notas[$alumno] = $clase->get_promedio($alumno, $datos_comision['comision'], $params['instancia']);
			}
		}

		$respuesta->set($notas);
	}
	
	function ajax__calcular_notas_todos($params, toba_ajax_respuesta $respuesta)
	{
		
		$para_todos = ($params['aplicar_a'] == comision::autocompletar_para_todos);
		
		$php_clase_regla = guarani::co('co_reglas_calculo')->get_php_clase_regla($params['regla_calculo']);
		$clase = new $php_clase_regla();

		// Si es Instancia de Regularidad
		if ($params['instancia'] == comision::inst_cursar) {
			$campo_nota = 'nota_cursada';
			$campo_acta_cerrada = 'acta_cursada_cerrada';
		} else {// Si es Instancia de Promoción
			$campo_nota = 'nota_promocion';
			$campo_acta_cerrada = 'acta_promocion_cerrada';
		}
			
		$datos_comision = $this->controlador()->controlador()->get_datos_comision();

		$notas = [];
		$datos = $this->controlador()->get_detalles();
		foreach ($datos as $dato) {
			
			// Si el acta esta cerrada no le modifico la nota al alumno
			if ($dato[$campo_acta_cerrada]) {
				continue;
			}

			// Si "aplicar a" igual "Alumnos sin datos" y la nota no es vacía entonces no le modifico la nota al alumno
			if (!$para_todos && !empty($dato[$campo_nota])) {
				continue;
			}
					
			$nota = $clase->get_promedio($dato['alumno'], $datos_comision['comision'], $params['instancia']);
			
			$notas[$dato['alumno']] = $nota;
			
			// Si la nota es null o menor a cero entonces no modifico al alumno
			if (is_null($nota) || $nota < 0) {
				continue;
			}
			
			$columnas_completar = [];
			
			// Si es Instancia de Regularidad
			if ($params['instancia'] == comision::inst_cursar) {
				$validador = escalas_notas::validador_escalas_notas();
				$resultado_cursada = $validador->get_resultado_escala_nota($params['escala'], $nota);
				$cond_regularidad = toba::consulta_php('co_condiciones_regularidad')->get_condicion_defecto_resultado($resultado_cursada);
				
				$columnas_completar[] = [
					'columna' => 'resultado_cursada',
					'columna_instancia' => 'instancia_cursada',
					'columna_acta_cerrada' => 'acta_cursada_cerrada',
					'valor' => $resultado_cursada
				];
				$columnas_completar[] = [
					'columna' => 'cond_regularidad',
					'columna_instancia' => 'instancia_cursada',
					'columna_acta_cerrada' => 'acta_cursada_cerrada',
					'valor' => $cond_regularidad
				];
				$columnas_completar[] = [
					'columna' => 'nota_cursada',
					'columna_instancia' => 'instancia_cursada',
					'columna_acta_cerrada' => 'acta_cursada_cerrada',
					'valor' => $nota
				];
				
			} else {// Si es Instancia de Promoción
				$validador = escalas_notas::validador_escalas_notas();
				$resultado_promocion = $validador->get_resultado_escala_nota($params['escala'], $nota);
				
				$columnas_completar[] = [
					'columna' => 'resultado_promocion',
					'columna_instancia' => 'instancia_promocion',
					'columna_acta_cerrada' => 'acta_promocion_cerrada',
					'valor' => $resultado_promocion];
				$columnas_completar[] = [
					'columna' => 'nota_promocion',
					'columna_instancia' => 'instancia_promocion',
					'columna_acta_cerrada' => 'acta_promocion_cerrada',
					'valor' => $nota];
			}
			
			foreach ($columnas_completar as $columna_completar) {
				if ($dato[$columna_completar['columna_instancia']] &&
					!$dato[$columna_completar['columna_acta_cerrada']] &&
					($para_todos || empty($dato[$columna_completar['columna']]))) {
					$this->controlador()->cn()->modificar_columna_detalle($dato['x_dbr_clave'], $columna_completar['columna'], $columna_completar['valor']);
				}
			}
			
		}
		
		$respuesta->set($notas);
	}

	/**
	 * Se autocompletan valores en el DT para todos los alumnos agregados, no sólo los cargados en la página actual del ML.
	 * Se tiene en cuenta el caso de columnas cuyo valor "depende" de otra.
	 * Los valores de la página actual se van a volver a autocompletar en JavaScript, para refrescar los cambios en pantalla.
	 * Se hacen algunas validaciones básicas en JavaScript (fechas y notas), las restantes en el CN.
	 * 
	 * @param array $params
	 * @param toba_ajax_respuesta $respuesta
	 */
	function ajax__completar_todos($params, toba_ajax_respuesta $respuesta)
	{
		$para_todos = ($params['para'] == comision::autocompletar_para_todos || $params['para'] == comision::autocompletar_para_todos_pagina);
		$columnas_completar = array();
		switch ($params['campo_completar']) {
			case comision::autocompletar_campo_nota_cursada:
				if (!empty($params['valor_nota_cursada'])) {
					$validador = escalas_notas::validador_escalas_notas();
					$resultado_cursada = $validador->get_resultado_escala_nota($params['escala_cursada'], $params['valor_nota_cursada']);
					$cond_regularidad = toba::consulta_php('co_condiciones_regularidad')->get_condicion_defecto_resultado($resultado_cursada);
					$columnas_completar[] = array(	'columna' => 'resultado_cursada',
													'columna_instancia' => 'instancia_cursada',
													'columna_acta_cerrada' => 'acta_cursada_cerrada',
													'valor' => $resultado_cursada);
					$columnas_completar[] = array(	'columna' => 'cond_regularidad',
													'columna_instancia' => 'instancia_cursada',
													'columna_acta_cerrada' => 'acta_cursada_cerrada',
													'valor' => $cond_regularidad);
					$columnas_completar[] = array(	'columna' => 'nota_cursada',
													'columna_instancia' => 'instancia_cursada',
													'columna_acta_cerrada' => 'acta_cursada_cerrada',
													'valor' => $params['valor_nota_cursada']);
				}
				break;
			case comision::autocompletar_campo_nota_promocion:
				if (!empty($params['valor_nota_promocion'])) {
					$validador = escalas_notas::validador_escalas_notas();
					$resultado_promocion = $validador->get_resultado_escala_nota($params['escala_promocion'], $params['valor_nota_promocion']);
					$columnas_completar[] = array(	'columna' => 'resultado_promocion',
													'columna_instancia' => 'instancia_promocion',
													'columna_acta_cerrada' => 'acta_promocion_cerrada',
													'valor' => $resultado_promocion);
					$columnas_completar[] = array(	'columna' => 'nota_promocion',
													'columna_instancia' => 'instancia_promocion',
													'columna_acta_cerrada' => 'acta_promocion_cerrada',
													'valor' => $params['valor_nota_promocion']);
				}
				break;
			case comision::autocompletar_campo_resultado_cursada:
				if (!empty($params['valor_resultado_cursada'])) {
					$cond_regularidad = toba::consulta_php('co_condiciones_regularidad')->get_condicion_defecto_resultado($params['valor_resultado_cursada']);
					$columnas_completar[] = array(	'columna' => 'cond_regularidad',
													'columna_instancia' => 'instancia_cursada',
													'columna_acta_cerrada' => 'acta_cursada_cerrada',
													'valor' => $cond_regularidad);
					$columnas_completar[] = array(	'columna' => 'resultado_cursada',
													'columna_instancia' => 'instancia_cursada',
													'columna_acta_cerrada' => 'acta_cursada_cerrada',
													'valor' => $params['valor_resultado_cursada']);
					// Sólo se puede autocompletar con resultado "Ausente" y en dicho caso la nota debe ser vacía.
					$columnas_completar[] = array(	'columna' => 'nota_cursada',
													'columna_instancia' => 'instancia_cursada',
													'columna_acta_cerrada' => 'acta_cursada_cerrada',
													'valor' => null);
				}
				break;
			case comision::autocompletar_campo_resultado_promocion:
				$columnas_completar[] = array(	'columna' => 'resultado_promocion',
												'columna_instancia' => 'instancia_promocion',
												'columna_acta_cerrada' => 'acta_promocion_cerrada',
												'valor' => $params['valor_resultado_promocion']);
				// Sólo se puede autocompletar con resultado "Ausente" y en dicho caso la nota debe ser vacía.
				$columnas_completar[] = array(	'columna' => 'nota_promocion',
												'columna_instancia' => 'instancia_promocion',
												'columna_acta_cerrada' => 'acta_promocion_cerrada',
												'valor' => null);
				break;
			case comision::autocompletar_campo_fecha:
				$columnas_completar[] = array(	'columna' => 'fecha_regular',
												'columna_instancia' => 'instancia_cursada',
												'columna_acta_cerrada' => 'acta_cursada_cerrada',
												'valor' => guarani_fecha::convertir_fecha_formato_base($params['valor_fecha']));
				$columnas_completar[] = array(	'columna' => 'fecha_promocion',
												'columna_instancia' => 'instancia_promocion',
												'columna_acta_cerrada' => 'acta_promocion_cerrada',
												'valor' => guarani_fecha::convertir_fecha_formato_base($params['valor_fecha']));
				break;
			case comision::autocompletar_campo_observaciones:
				$columnas_completar[] = array(	'columna' => 'observacion_cursada',
												'columna_instancia' => 'instancia_cursada',
												'columna_acta_cerrada' => 'acta_cursada_cerrada',
												'valor' => $params['valor_observaciones']);
				$columnas_completar[] = array(	'columna' => 'observacion_promocion',
												'columna_instancia' => 'instancia_promocion',
												'columna_acta_cerrada' => 'acta_promocion_cerrada',
												'valor' => $params['valor_observaciones']);
				break;
		}
		$datos = $this->controlador()->get_detalles();
		foreach ($datos as $dato) {
			foreach ($columnas_completar as $columna_completar) {
				if ($dato[$columna_completar['columna_instancia']] &&
					!$dato[$columna_completar['columna_acta_cerrada']] &&
					($para_todos || empty($dato[$columna_completar['columna']]))) {
					$this->controlador()->cn()->modificar_columna_detalle($dato['x_dbr_clave'], $columna_completar['columna'], $columna_completar['valor']);
				}
			}
		}
	}
}