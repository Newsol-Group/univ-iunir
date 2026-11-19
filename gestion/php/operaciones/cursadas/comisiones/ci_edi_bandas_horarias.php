<?php
/**
 *
 * 
 * @package		Cursadas
 * @subpackage	Comisiones
 * 
 */
class ci_edi_bandas_horarias extends ci_edicion_entidad
{
	protected $indx_msg_eof = '800_eof_cuadro';
	protected $indx_msg_confirmar_modificar = '800CUR_confirmar_cambio_asignacion';
	protected $indx_msg_confirmar_eliminar = '800_confirmar_elimin';
	protected $datos_banda_horaria;
	protected $s__estoy_duplicando_banda = false;

	function post_configurar()
	{
		if (!$this->cn()->entidad()->es_editable()) {
			// Para el cuadro de bandas horarias.
			$this->componentes_eventos_a_inhabilitar['cuadro'] = array('agregar', 'compartir', 'duplicar');
			$this->inhabilitar_componentes_eventos();
			$this->inhabilitar_eventos_controlador();
			if ($this->cn()->entidad()->hay_banda_horaria_seleccionada()) {
				$this->evento('baja')->desactivar();
				$this->evento('modificacion')->desactivar();
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCIÓN BANDA HORARIA
	//-----------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- cuadro_bandas_horarias -------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_bandas_horarias(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('sga_comisiones_bh');
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof, array($etiqueta->get_enf('plural'))));
        $i=1;
		$datos = $this->cn()->entidad()->get_datos_bandas_horarias_asignaciones();
		$hay_clases_agregadas = false;
		foreach ($datos as $clave => $valor) {
			if ($valor['fecha_desde'] == $valor['fecha_hasta']) {
				// Se excluyen las clases sueltas, agregadas por fuera de las bandas horarias de la comisión.
				unset($datos[$clave]);
				$hay_clases_agregadas = true;
			} else {
				$datos[$clave]['dia_semana_nombre'] = toba::consulta_php('co_comunes')->get_nombre_dia_semana($valor['dia_semana']);
				// Formateo de horas, quitando segundos.
				$datos[$clave]['hora_inicio_visual'] = substr($valor['hora_inicio'], 0, 5);
				$datos[$clave]['hora_finalizacion_visual'] = substr($valor['hora_finalizacion'], 0, 5);
				$datos[$clave]['banda_horaria'] = isset($valor['banda_horaria']) ? $valor['banda_horaria'] : '';
				$i++;
			}
		}
		$cuadro->set_datos($datos);
		if ($hay_clases_agregadas) {
			$datos_comision = $this->cn()->entidad()->get_datos_generales();
			$comision = isset($datos_comision['comision']) ? $datos_comision['comision'] : '';
			$cuadro->evento('ver_clases')->vinculo()->agregar_parametro(utf8_encode('comision'), utf8_encode($comision));
		} else {
			$cuadro->evento('ver_clases')->anular();
		}
	}

	function evt__cuadro_bandas_horarias__seleccion($seleccion)
	{
		$this->cn()->entidad()->seleccionar_banda_horaria($seleccion);
		$this->cn()->entidad()->seleccionar_asignacion();
		$this->set_pantalla('edicion_banda_horaria');
		$this->s__estoy_duplicando_banda = false;
	}

	function evt__cuadro_bandas_horarias__duplicar($seleccion)
	{
		$this->cn()->entidad()->duplicar_banda_horaria($seleccion);
		$this->set_pantalla('edicion_banda_horaria');
		$this->s__estoy_duplicando_banda = true;
	}

	function evt__cuadro_bandas_horarias__agregar()
	{
		$this->set_pantalla('edicion_banda_horaria');
	}

	function evt__cuadro_bandas_horarias__compartir()
	{
		$this->set_pantalla('compartir_banda_horaria');
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA EDICIÓN BANDA HORARIA
	//-----------------------------------------------------------------------------------

	function conf__edicion_banda_horaria(toba_ei_pantalla $pantalla)
	{
		$ci_navegacion = $this->controlador()->controlador()->controlador();
		$ci_navegacion->evento('guardar')->desactivar();
		$ci_navegacion->evento('cancelar')->desactivar();
		$ci_navegacion->evento('limpiar')->desactivar();

		if ($this->cn()->entidad()->hay_banda_horaria_seleccionada()) {
			$this->evento('alta')->ocultar();
			$datos_banda_horaria = $this->cn()->entidad()->get_datos_banda_horaria_seleccionada();
			if (isset($datos_banda_horaria['compartida']) && $datos_banda_horaria['compartida']) {
				$this->evento('modificacion')->set_msg_confirmacion(guarani_mensajes::get($this->indx_msg_confirmar_modificar));
			}
			$etiqueta = guarani_etiquetas::get_tabla('sga_comisiones_bh');
			$this->evento('baja')->set_msg_confirmacion(guarani_mensajes::get($this->indx_msg_confirmar_eliminar, array($etiqueta->get('la'))));
		} else {
			$this->evento('baja')->ocultar();
			$this->evento('modificacion')->ocultar();
		}

		$anio_academico = $this->controlador()->controlador()->controlador()->get_anio_academico_seleccionado();
		$datos_comision = $this->cn()->entidad()->get_datos_generales();
	}

	//-----------------------------------------------------------------------------------
	//---- form_banda_horaria -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_banda_horaria(toba_ei_formulario $form)
	{
		// Valor por defecto para las fechas desde y hasta: inicio y fin de dictado del período lectivo.
		$datos_comision = $this->cn()->entidad()->get_datos_generales();
		$datos_periodo = toba::consulta_php('co_periodos_lectivos')->get_datos_periodo_lectivo($datos_comision['periodo_lectivo']);

		// Datos de la excepción
		$datos_excep_perlect = $this->cn()->entidad()->get_datos_excep_perlect();
		if (!empty($datos_excep_perlect)) {
			$datos_periodo['fecha_inicio_dictado'] = $datos_excep_perlect['fecha_inicio_dictado'];
			$datos_periodo['fecha_fin_dictado'] = $datos_excep_perlect['fecha_fin_dictado'];
		}

		$form->set_datos_defecto(array('fecha_desde' => $datos_periodo['fecha_inicio_dictado'],
			'fecha_hasta' => $datos_periodo['fecha_fin_dictado'],
			'periodicidad' => banda_horaria::periodicidad_semanal));

		if (isset($this->datos_banda_horaria)) {
			$datos = $this->datos_banda_horaria;
		} else {
			$datos_banda_horaria = $this->cn()->entidad()->get_datos_banda_horaria_seleccionada();
			$datos_banda_horaria = empty($datos_banda_horaria) ? array() : $datos_banda_horaria;
			$datos_asignacion = $this->cn()->entidad()->get_datos_asignacion_seleccionada();

			if (empty($datos_asignacion)) {
				$datos_asignacion = array();
			} else {
				unset($datos_asignacion['tipo_clase']);
			}

			$datos = array_merge($datos_banda_horaria, $datos_asignacion);
		}
		$form->set_datos($datos);

		if ($this->cn()->entidad()->hay_banda_horaria_seleccionada() && !$this->cn()->entidad()->banda_horaria_es_nueva()) {
			// La periodicidad y el día de la semana de una banda horaria no se pueden modificar.
			$form->set_solo_lectura(array('dia_semana', 'periodicidad'));

			$datos_banda_horaria = $this->cn()->entidad()->get_datos_banda_horaria_seleccionada();
			toba::consulta_php('co_bandas_horarias')->get_subcomisiones_bh($datos_banda_horaria['banda_horaria']);
			// Si la banda horaria está asociada a una subcomisión no se puede modificar el tipo de clase.
			$form->set_solo_lectura(array('tipo_clase'));
		}
	}

	function evt__form_banda_horaria__modificacion($datos)
	{
		$this->datos_banda_horaria = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA COMPARTIR BANDA HORARIA
	//-----------------------------------------------------------------------------------

	function conf__compartir_banda_horaria(toba_ei_pantalla $pantalla)
	{
		$ci_navegacion = $this->controlador()->controlador()->controlador();
		$ci_navegacion->evento('guardar')->desactivar();
		$ci_navegacion->evento('cancelar')->desactivar();
		$ci_navegacion->evento('limpiar')->desactivar();
	}

	//-----------------------------------------------------------------------------------
	//---- form_compartir_banda_horaria -------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_compartir_banda_horaria(toba_ei_formulario $form)
	{
		$anio_academico = $this->controlador()->controlador()->controlador()->get_anio_academico_seleccionado();
		$datos_comision = $this->cn()->entidad()->get_datos_generales();
		$comision = isset($datos_comision['comision']) ? $datos_comision['comision'] : '';
		$asignaciones_aux = aplanar_matriz($this->cn()->entidad()->get_datos_bandas_horarias(), 'asignacion');
        $asignaciones = isset($asignaciones_aux) ? implode('||', $asignaciones_aux) : '';        
		$parametros = array('anio_academico' => $anio_academico, 'periodo_lectivo' => $datos_comision['periodo_lectivo'], 'comision' => $comision, 'asignaciones' => $asignaciones);
        $form->ef('banda_horaria')->vinculo()->set_parametros($parametros);

		if (isset($this->datos_banda_horaria)) {
			$form->set_datos($this->datos_banda_horaria);
		}
	}

	function evt__form_compartir_banda_horaria__modificacion($datos)
	{
		$this->datos_banda_horaria = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__alta()
	{
		$this->cn()->entidad()->nueva_asignacion($this->datos_banda_horaria);
		$this->cn()->entidad()->nueva_banda_horaria($this->datos_banda_horaria);
		$this->cancelar();
	}

	function evt__modificacion()
	{
		$this->cn()->entidad()->set_datos_asignacion($this->datos_banda_horaria);
		$this->cn()->entidad()->set_datos_banda_horaria($this->datos_banda_horaria);
		$this->cancelar();
	}

	function evt__compartir()
	{
		$this->cn()->entidad()->compartir_banda_horaria($this->datos_banda_horaria);
		$this->cancelar();
	}

	function evt__baja()
	{
		// La validación se debe hacer antes de eliminar la asignación porque esa acción no se puede revertir.
		$this->cn()->entidad()->validar_eliminacion_banda_horaria();
		$this->cn()->entidad()->eliminar_asignacion_seleccionada();
		$this->cn()->entidad()->eliminar_banda_horaria_seleccionada();
		$this->cancelar();
	}

	function evt__cancelar()
	{
		if ($this->s__estoy_duplicando_banda) {
			$this->cn()->entidad()->eliminar_asignacion_seleccionada();
			$this->cn()->entidad()->eliminar_banda_horaria_seleccionada();
		}
		$this->cancelar();
	}

	function cancelar()
	{
		unset($this->datos_banda_horaria);
		$this->cn()->entidad()->deseleccionar_banda_horaria();
		$this->cn()->entidad()->deseleccionar_asignacion();
		$this->set_pantalla('seleccion_banda_horaria');
	}

	//-----------------------------------------------------------------------------------
	//---- AJAX -------------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ajax__datos_banda_horaria($params, toba_ajax_respuesta $respuesta)
	{
		$banda_horaria = current($params);
		$filtro = " sga_comisiones_bh.banda_horaria = $banda_horaria";
		$datos_banda_horaria = current(toba::consulta_php('co_bandas_horarias')->get_listado($filtro));
		if (!empty($datos_banda_horaria)) {
			$respuesta->set($datos_banda_horaria);
		} else {
			$respuesta->set(false);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{

		$msj_confirmar = guarani_mensajes::get('800CUR_rango_fuera_rango');
		$datos_comision = $this->cn()->entidad()->get_datos_generales();
		$datos_periodo = toba::consulta_php('co_periodos_lectivos')->get_datos_periodo_lectivo($datos_comision['periodo_lectivo']);

		echo "
		
		//---- Eventos ---------------------------------------------
		
		{$this->objeto_js}.evt__alta = function()
		{
			return confirmar_banda_horaria();
		}
		
		{$this->objeto_js}.evt__modificacion = function()
		{
			return confirmar_banda_horaria();
		}
		
		function confirmar_banda_horaria()
		{
			//Fechas a evaluar
			var fecha_inicio_periodo = new Date('{$datos_periodo['periodo_fecha_inicio']}T00:00:00');
			var fecha_fin_periodo = new Date('{$datos_periodo['periodo_fecha_fin']}T00:00:00');
			var fecha_desde = {$this->objeto_js}.dep('form_banda_horaria').ef('fecha_desde').fecha();
			var fecha_hasta = {$this->objeto_js}.dep('form_banda_horaria').ef('fecha_hasta').fecha();
			
			//Condiciones de las fechas
			//fecha_desde no puede ser menor al inicio del período ni mayor al fin del período
			fecha_desde_desfasada = (fecha_desde < fecha_inicio_periodo) || (fecha_desde > fecha_fin_periodo);
			//fecha_hasta no puede ser menor a la fecha desde ni mayor al fin del período
			fecha_hasta_desfasada = (fecha_desde > fecha_hasta) || (fecha_hasta > fecha_fin_periodo);

			//Se emite mensaje de confirmación
			if (fecha_desde_desfasada || fecha_hasta_desfasada) {
				if (!(confirm('$msj_confirmar'))) {
					return false;
				}
			}
			return true;
		}
		
		";
	}

}
?>