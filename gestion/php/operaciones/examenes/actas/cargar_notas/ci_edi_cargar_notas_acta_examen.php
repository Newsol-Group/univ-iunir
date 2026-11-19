<?php
/**
 *
 * @package Exámenes
 * @subpackage Actas
 */
class ci_edi_cargar_notas_acta_examen extends ci_edicion_entidad
{
	//Variables temporales para valores del form ml notas
	protected $datos_form_ml_notas_temp;
	
	//Datos para paginado
	protected $s__pagina_actual = 1;
	protected $s__tamanio_pagina = 20;
	protected $s__cantidad_paginas;

	//Elementos Visuales de información
	protected $ev_info_alumno;
	protected $ev_info_mesa;
	
	protected $s__alumno_seleccionado;
	
	function ini()
	{
		toba::memoria()->desactivar_reciclado();
		parent::ini();
		
		//Se setea el hecho de no volver al guardar en el ci padre.
		$this->controlador()->set_no_volver_al_guardar();	
	}
	
	function resetear_variables()
	{
		unset($this->s__pagina_actual);
		unset($this->s__tamanio_pagina);
		unset($this->s__cantidad_paginas);
		unset($this->s__alumno_seleccionado);
	}
	
	/**
	 * El combo de instancias para el detalle de Notas de Alumnos, se cargará con todas las instancias
	 * asociadas a la Mesa que es posible seleccionar para los mismos. El valor por defecto asignado
	 * será el que ya viene en el detalle de cada alumno, la instancia para la cual se incribió a la mesa.
	 */
	function get_instancias_asociadas_a_mesa()
	{
		$mesa_examen = $this->controlador()->get_mesa_acta_seleccionada();
		return toba::consulta_php('co_mesas_de_examen')->get_instancias_mesa_examen($mesa_examen);
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA INICIAL -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	//---- form_autocompletar -------------------------------------------------
	
	function conf__form_autocompletar(toba_ei_formulario $form)
	{
		//Se completa el valor del ef oculto mesa de examen para la carga del combo de instancias.
		$form->ef('mesa_examen')->set_estado($this->controlador()->get_mesa_acta_seleccionada());
	}
	
	//---- form_ml_notas ----------------------------------------------------------------
	
	function conf__form_ml_notas(toba_ei_formulario_ml $form_ml)
	{
		$datos_acta = $this->controlador()->get_datos_acta();
		$mesa = $this->controlador()->get_mesa_acta_seleccionada();
		if ($datos_acta['tipo_acta'] != acta::tipo_rectificativa) {
			$form_ml->ef('instancia')->set_solo_lectura();
		} 
		if (isset($this->datos_form_ml_notas_temp)) {
			$this->controlador()->set_hubo_cambios();
			$form_ml->set_datos($this->datos_form_ml_notas_temp);
		} else {
			$acta = $this->controlador()->get_acta_seleccionada();
			$datos = $this->cn()->get_detalles_en_orden_parametro(array('id_acta'=>$acta));
			$form_ml->set_datos($this->generar_paginado($datos));
		}
		if ($this->s__pagina_actual == 1) {
			$form_ml->evento('primera')->desactivar();
			$form_ml->evento('anterior')->desactivar();
		}
		if ($this->s__pagina_actual == $this->s__cantidad_paginas) {
			$form_ml->evento('siguiente')->desactivar();
			$form_ml->evento('ultima')->desactivar();
		}
	}
	
	function evt__form_ml_notas__primera($datos)
	{
		try {
			$this->cn()->set_datos_detalles($datos);
			$this->s__pagina_actual = 1;
		} catch (guarani_error $e) {
			$this->datos_form_ml_notas_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	function evt__form_ml_notas__anterior($datos)
	{
		try {
			$this->cn()->set_datos_detalles($datos);
			$this->s__pagina_actual = $this->s__pagina_actual - 1;
		} catch (guarani_error $e) {
			$this->datos_form_ml_notas_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	function evt__form_ml_notas__siguiente($datos)
	{
		try {
			$this->cn()->set_datos_detalles($datos);
			$this->s__pagina_actual = $this->s__pagina_actual + 1;
		} catch (guarani_error $e) {
			$this->datos_form_ml_notas_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	function evt__form_ml_notas__ultima($datos)
	{
		try {
			$this->cn()->set_datos_detalles($datos);
			$this->s__pagina_actual = $this->s__cantidad_paginas;
		} catch (guarani_error $e) {
			$this->datos_form_ml_notas_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}
	
	function evt__form_ml_notas__modificacion($datos)
	{
		try {
			$this->cn()->set_datos_detalles($datos);
		} catch (guarani_error $e) {
			$this->datos_form_ml_notas_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}
	
	function evt__form_ml_notas__detalle_nota($seleccion)
	{
		$this->s__alumno_seleccionado = $this->cn()->get_detalle($seleccion);
		$this->set_pantalla('pant_detalles');
	}
	
	/**
	 * Retorna una porción de los datos recibidos como parámetro, teniendo en cuenta la página actual, y calcula la cantidad de páginas posibles.
	 *
	 * @param array $datos arreglo asociativo de datos a paginar.
	 */
	function generar_paginado($datos)
	{
		$this->s__cantidad_paginas = ceil(guarani_arreglos::count($datos)/$this->s__tamanio_pagina);
		if ($this->s__pagina_actual > $this->s__cantidad_paginas) {
			$this->s__pagina_actual = 1;
		}
		$offset = ($this->s__pagina_actual - 1) * $this->s__tamanio_pagina;
		return array_slice($datos, $offset, $this->s__tamanio_pagina);
	}

	function get_pagina_actual()
	{
		return $this->s__pagina_actual;
	}

	function get_cantidad_paginas()
	{
		return $this->s__cantidad_paginas;
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA DETALLES ------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//---- form_ml_notas_eval -----------------------------------------------------------
	
	function conf__form_ml_notas_eval(toba_ei_formulario_ml $form_ml)
	{
		if (isset($this->datos_notas_eval_temp)) {
			$this->controlador()->set_hubo_cambios();
			$datos = $this->datos_notas_eval_temp;
		} else {
			$datos = $this->cn()->get_detalles_evaluaciones_manuales(array('alumno' => $this->s__alumno_seleccionado['alumno']));
			if (!empty($datos)) {
				foreach ($datos as $clave => $valor) {
					$datos[$clave]['resultado_nombre'] = escalas_notas::get_resultado_descripcion($valor['resultado']);
				}
			}
		}

		$icono_informacion = new icono_info_escala_nota();
		$icono_informacion->set_ef_referencia('escala_nota');
		
		$form_ml->ef('escala_nota_nombre')->agregar_icono_utileria($icono_informacion);
		$form_ml->set_solo_lectura(array('resultado_nombre', 'escala_nota_nombre'));
		$form_ml->set_datos($datos);
	}

	function evt__form_ml_notas_eval__modificacion($datos)
	{
		$this->datos_notas_eval_temp = $datos;
	}

	//---- form_nota_final --------------------------------------------------------------
	
	function conf__form_nota_final(toba_ei_formulario $form)
	{
		if (isset($this->datos_nota_final_temp)) {
			$this->controlador()->set_hubo_cambios();
			$datos = $this->datos_nota_final_temp;
		} else {
			$datos = $this->s__alumno_seleccionado;
		}

		if (!empty($datos)) {
			$filtro = "sga_escalas_notas_resultado.resultado = '{$datos['resultado']}'";
			$resultado = current(toba::consulta_php('co_escalas_notas')->get_resultados_instancia($datos['instancia'], $filtro));
			$datos['resultado_nombre'] = $resultado['descr'];
		}

		$icono_informacion = new icono_info_escala_nota();
		$icono_informacion->set_ef_referencia('escala_nota');
		
		$form->ef('escala_nota_nombre')->agregar_icono_utileria($icono_informacion);
		$form->set_solo_lectura(array('resultado_nombre', 'escala_nota_nombre'));
		$form->set_datos($datos);
	}

	function evt__form_nota_final__modificacion($datos)
	{
		$this->datos_nota_final_temp = $datos;
	}
	
	//---- Eventos ----------------------------------------------------------------------
	
	function evt__cancelar_detalle()
	{
		//Reseteo las variables y vuelvo a la pantalla de selección del alumno
		$this->limpiar_variables();
		$this->set_pantalla('pant_inicial');
	}

	function evt__aceptar_detalle()
	{
		try {
			if (!$this->es_alumno_sin_evaluaciones()) {
				
				//Guardo las notas y resultados de las evaluaciones manuales del alumno
				$this->cn()->set_carga_notas_eval_manual($this->datos_notas_eval_temp);
			}
			
			//Guardo la nota y resultado final del alumno, y redirijo la pantalla
			$this->cn()->set_carga_nota_final($this->s__alumno_seleccionado ,$this->datos_nota_final_temp);
			$this->set_pantalla('pant_inicial');
		} catch ( guarani_error $e ) {
			throw new guarani_error($e->getMessage());
		}
	}
	
	function limpiar_variables()
	{
		unset($this->s__alumno_seleccionado);
	}
	
	function conf__pant_detalles(toba_ei_pantalla $pantalla)
	{
		//Obtengo los datos para los evs
		$alumno = $this->get_datos_ev_alumno();
		$mesa 	= $this->get_datos_ev_mesa();
		$alumno['anio_academico'] = $mesa['anio_academico'];
		
		//Seteo los datos para los evs
		$this->ev_info_alumno()->set_datos($alumno);
		$this->ev_info_mesa()->set_datos($mesa);
		
		//Se ocultan los eventos generales de Guardar y Cancelar
		$this->controlador()->evento('cancelar')->set_en_botonera(false);
		$this->controlador()->evento('guardar')->set_en_botonera(false);
		
		//Si no existen evaluaciones elimino el formulario ml
		if ($this->es_alumno_sin_evaluaciones()) {
			$pantalla->eliminar_dep('form_ml_notas_eval');
		}
	}

	function es_alumno_sin_evaluaciones()
	{
		$detalles_eval = $this->cn()->get_detalles_evaluaciones_manuales(array('alumno' => $this->s__alumno_seleccionado['alumno']));
		return empty($detalles_eval);
	}
	
	function get_datos_ev_alumno()
	{
        $datos_cursada = toba::consulta_php('co_actas')->get_datos_ultima_cursada_alumno($this->s__alumno_seleccionado['alumno'], $this->s__alumno_seleccionado['evaluacion']);
        return array_merge($this->s__alumno_seleccionado, $datos_cursada);
	}
	
	function get_datos_ev_mesa()
	{
		$datos_mesa = $this->controlador()->get_datos_ev_mesa();
		$datos_acta = $this->controlador()->get_datos_ev_acta();
		return array_merge($datos_acta, $datos_mesa);
	}
		   
    function ev_info_alumno()
	{
		if (!isset($this->ev_info_alumno)) {
			$this->ev_info_alumno = guarani::ev_info_datos();
			$this->ev_info_alumno->set_controlador($this);
			$this->ev_info_alumno->set_titulo('Datos del Alumno y Regularidad');
			$this->ev_info_alumno->set_columnas_a_mostrar(array('legajo', 'apellido_y_nombre', 'anio_academico', 'periodo_lectivo', 'catedra', 'nota_regularidad', 'fecha_regular', 'fecha_vigencia', 'ubicacion'));
			$this->ev_info_alumno->set_max_columnas_por_fila(2);
			$style = 'width: 60%;';
			$this->ev_info_alumno->set_style($style);
			$this->ev_info_alumno->set_etiqueta('legajo', 'Legajo');
			$this->ev_info_alumno->set_etiqueta('apellido_y_nombre', 'Apellido y Nombre');
			$this->ev_info_alumno->set_etiqueta('catedra', 'Cátedra');
			$this->ev_info_alumno->set_etiqueta('periodo_lectivo', 'Período Lectivo');
			$this->ev_info_alumno->set_etiqueta('anio_academico', 'Año de Cursada');
			$this->ev_info_alumno->set_etiqueta('nota_regularidad', 'Nota de Aprobación');
			$this->ev_info_alumno->set_etiqueta('fecha_regular', 'Fecha Regularidad');
			$this->ev_info_alumno->set_etiqueta('fecha_vigencia', 'Venc. de Regularidad');
            $this->ev_info_alumno->set_etiqueta('ubicacion', 'Ubicación');

		}
		return $this->ev_info_alumno;
	}
	
	function ev_info_mesa()
	{
		if (!isset($this->ev_info_mesa)) {
			$this->ev_info_mesa = guarani::ev_info_datos();
			$this->ev_info_mesa->set_controlador($this);
			$this->ev_info_mesa->set_titulo('Datos de la Mesa');
			$this->ev_info_mesa->set_columnas_a_mostrar(array('nombre_turno_examen', 'nombre', 'nombre_llamado', 'fecha_formato_visual', 'cod_nombre_actividad', 'libro_nombre', 'nro_acta', 'observaciones', 'tipo_acta_descr'));
			$this->ev_info_mesa->set_columnas_fila(array('nombre_turno_examen', 'nombre', 'nombre_llamado'), 1);
			$this->ev_info_mesa->set_max_columnas_por_fila(2);
			$this->ev_info_mesa->set_etiqueta('nombre_turno_examen', 'Turno');
			$this->ev_info_mesa->set_etiqueta('nombre', 'Mesa');
			$this->ev_info_mesa->set_etiqueta('nombre_llamado', 'Llamado');
			$this->ev_info_mesa->set_etiqueta('fecha_formato_visual', 'Fecha');
			$this->ev_info_mesa->set_etiqueta('cod_nombre_actividad', 'Actividad');
			$this->ev_info_mesa->set_etiqueta('nro_acta', 'Acta');
			$this->ev_info_mesa->set_etiqueta('libro_nombre', 'Libro');
			$this->ev_info_mesa->set_etiqueta('observaciones', 'Observaciones');
			$this->ev_info_mesa->set_etiqueta('acta_referencia', 'Acta Original Nro');
			$this->ev_info_mesa->set_etiqueta('tipo_acta_descr', 'Tipo');
		}
		return $this->ev_info_mesa;
	}
	
	//-----------------------------------------------------------------------------------
	//---- JavaScript -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Genera un mapeo en JS que dado el id_resultado da la descripción del mismo
	 * se utiliza para autocompletar el resultado en la carga de notas de las evaluaciones manuales
	 * y nota final (en la pantalla de carga de notas evaluaciones manuales exámenes)
	 *
	 * @return string
	 */
	function generar_mapeo_resultados_valor_desc()
	{
		$resultados = toba::consulta_php('co_escalas_notas')->get_escalas_notas_resultados();
		$resultados = rs_convertir_asociativo($resultados, array('valor'), 'descr');
		$resultados_por_instancia = toba::consulta_php('co_escalas_notas')->get_resultados_instancia($this->s__alumno_seleccionado['instancia']);
		$resultados_por_instancia = rs_convertir_asociativo($resultados_por_instancia, array('valor'), 'descr');
		return array('resultados' => toba_js::arreglo($resultados, true), 'resultados_por_instancia' => toba_js::arreglo($resultados_por_instancia, true));
	}
	
	/**
	 * Genera un array en js que contiene un mapeo instancia mesa - escala de notas
	 *
	 * @return <type>
	 */
	function generar_mapeo_js_instancia_escala()
	{
		$filtro = 'sga_mesas_examen.mesa_examen = '.$this->controlador()->get_mesa_acta_seleccionada();
		$instancias = toba::consulta_php('co_mesas_de_examen')->get_instancias_mesas_examen($filtro);
		$instancias = rs_convertir_asociativo($instancias, array('instancia'), 'escala_nota');
		return toba_js::arreglo($instancias, true);
	}
	
	/**
	 * Retorna las fs js para validar las notas de las distintas escalas de la mesa
	 *
	 * @param <type> $escalas
	 * @return <type>
	 *
	 */
	function get_js_validar_nota_escalas()
	{
		$escalas = $this->cn()->get_escalas_notas_mesa($this->controlador()->get_mesa_acta_seleccionada());
		$validador = escalas_notas::validador_escalas_notas();
		return $validador->get_js_validar_nota_escalas($escalas);
	}

	/**
	 * Retorna el código JS necesario para validar las notas de las evaluaciones de la mesa.
	 *
	 * @return string
	 */
	function get_js_validar_notas_eval_escalas()
	{
		$escalas = $this->cn()->get_escalas_notas_de_evaluaciones_mesa();
		$validador = escalas_notas::validador_escalas_notas();
		return $validador->get_js_validar_nota_escalas($escalas);
	}
}
?>