<?php
/**
 *
 *
 * @package		Cursadas
 * @subpackage	Comisiones
 * 
 */
class ci_seleccion_comision_destino extends guarani_pers_ci
{
	protected $s__distribucion;
	protected $s__actividad;
	protected $s__periodo;
	protected $s__comision;
	protected $s__clase;
	protected $s__alumnos;
	protected $s__seleccion_subcos;
	protected $s__claves_seleccion_subcos;
	protected $respuesta = '';
	protected $msj_tipos_todos = '800CUR_falta_seleccion_tipo_subcomision';
	protected $msj_tipos_unicos = '800CUR_seleccion_tipos_unicos';
	protected $msj_comision_unica = '800CUR_distribuir_a_comision_unica';
	protected $indx_msj_eof = '800_eof_cuadro';

	function ini()
	{
		// Tipo de la Distribución de Comisiones seleccionada.
		if (!isset($this->s__distribucion)) {
			$this->s__distribucion = toba::memoria()->get_parametro('distribucion');
		}
		// Se recuperan la Actividad y Período Lectivo para los cuales se buscarán las comisiones/subcomisiones.
		if (!isset($this->s__actividad)) {
			$this->s__actividad = toba::memoria()->get_parametro('actividad');
		}
		if (!isset($this->s__periodo)) {
			$this->s__periodo = toba::memoria()->get_parametro('periodo_lectivo');
		}
		if ($this->s__distribucion == comision::dist_com_subcom || $this->s__distribucion == comision::dist_com_subcom_otra_com) {
			if (!isset($this->s__comision)) {
				$this->s__comision = toba::memoria()->get_parametro('comision');
			}
			if ($this->s__distribucion == comision::dist_com_subcom) {
				if (!isset($this->s__alumnos)) {
					$this->s__alumnos = toba::memoria()->get_parametro('alumnos');
				}
				if (!isset($this->s__clase)) {
					$this->s__clase = toba::memoria()->get_parametro('clase');
				}
			}
		}
	}

	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		if ($pantalla->existe_dependencia('cuadro_comision_destino')) {
			$pantalla->eliminar_dep('cuadro_comision_destino');
		}
		if ($pantalla->existe_dependencia('cuadro_subcomisiones_destino')) {
			$pantalla->eliminar_dep('cuadro_subcomisiones_destino');
		}
		// Chequea el tipo de distribucion seleccionado para saber qué comisiones/subcomisiones mostrar.
		if ($this->s__distribucion == comision::dist_entre_com) {
			$pantalla->agregar_dep('cuadro_comision_destino');
		} elseif ($this->s__distribucion == comision::dist_com_subcom || $this->s__distribucion == comision::dist_com_subcom_otra_com) {
			$pantalla->agregar_dep('cuadro_subcomisiones_destino');
		}
	}

	function get_datos_comisiones()
	{
		$filtro_actividad = "sga_comisiones.elemento = $this->s__actividad";
		return toba::consulta_php('co_comisiones')->get_comisiones_periodo_lectivo($this->s__periodo, $filtro_actividad);
	}

	function get_datos_subcomisiones()
	{
		if ($this->s__distribucion == comision::dist_com_subcom) {
			$filtro = "sga_comisiones.comision = {$this->s__comision}";
			if (isset($this->s__clase)) {
				$filtro .= " AND sga_clases_tipos.tipo_clase = $this->s__clase";
			}
			return toba::consulta_php('co_comisiones')->get_subcomisiones_cupo($filtro);
		} else {
			$filtro = "sga_comisiones.elemento = $this->s__actividad AND sga_comisiones.comision <> $this->s__comision";
			return toba::consulta_php('co_comisiones')->get_subcom_com_periodo_lectivo($this->s__periodo, $filtro);
		}
	}

	//--------------------------------------------------------------------------
	//---- comision_destino_distribucion ---------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro_comision_destino(toba_ei_cuadro $cuadro)
	{
		$cuadro->desactivar_modo_clave_segura();
		$mje = guarani_mensajes::get($this->indx_msj_eof, array('Comisiones para la Actividad y Período seleccionados'));
		$cuadro->set_eof_mensaje($mje);
		// Se recuperan las comisiones según la Actividad y el Período Lectivo.
		$cuadro->set_datos($this->get_datos_comisiones());
	}

	//--------------------------------------------------------------------------
	//---- subcomisiones_destino_distribucion ----------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro_subcomisiones_destino(toba_ei_cuadro $cuadro)
	{
		$cuadro->desactivar_modo_clave_segura();
		$mje = guarani_mensajes::get($this->indx_msj_eof, array('Subcomisiones para las Comisiones de la Actividad seleccionada'));
		$cuadro->set_eof_mensaje($mje);
		$subcomisiones = $this->get_datos_subcomisiones();
		$cuadro->set_datos($subcomisiones);
		if (empty($subcomisiones)) {
			$cuadro->evento('confirmar')->desactivar();
		}
	}

	function evt__cuadro_subcomisiones_destino__seleccion($seleccion)
	{
		$this->s__seleccion_subcos = $seleccion;
		$this->s__claves_seleccion_subcos = aplanar_matriz($this->s__seleccion_subcos, 'subcomision');
	}

	function conf_evt__cuadro_subcomisiones_destino__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dependencia('cuadro_subcomisiones_destino')->get_datos();
		if (isset($this->s__claves_seleccion_subcos)) {
			if (array_search($datos[$fila]['subcomision'], $this->s__claves_seleccion_subcos) === false) {
				$evento->set_check_activo(false);
			} else {
				$evento->set_check_activo(true);
			}
		}
	}

	function evt__cuadro_subcomisiones_destino__confirmar()
	{
		if (!isset($this->s__seleccion_subcos)) {
			throw guarani::error('Debe seleccionar al menos una subcomision para continuar');
		}
		$i = 0;
		foreach ($this->s__seleccion_subcos as $sel) {
			$nombres[$i] = $sel['subcomision_nombre'];
			$this->tipos_subcos_sel[$i] = $sel['tipo_clase'];
			$i++;
		}
		try {
			$this->validar();
			$this->respuesta = implode('$$&$$', $this->s__claves_seleccion_subcos) . '||' . implode(' / ', $nombres);
		} catch (guarani_error $e) {
			throw $e;
		}
	}

	function validar()
	{
		// Valida que todas las subcomisiones pertenezcan a la misma comision
		$comision = toba::consulta_php('co_comisiones')->get_comisiones_de_subcomisiones($this->s__claves_seleccion_subcos);
		if (guarani_arreglos::count($comision) > 1) {
			$msj = guarani_mensajes::get($this->msj_comision_unica);
			throw guarani::error($msj);
		} else {
			// Valida que se haya seleccionado al menos una subcomision de cada tipo
			if ($this->s__distribucion == comision::dist_com_subcom && $this->s__alumnos == comision::alumno_con_subcomision) {
				$tipos = toba::consulta_php('co_comisiones')->get_tipos_subcos_de_comision($comision[0]['comision']);
				if (guarani_arreglos::count($this->tipos_subcos_sel) > 1) {
					$msj = guarani_mensajes::get($this->msj_tipos_unicos);
					throw guarani::error($msj);
				}
			} else {
				$tipos = toba::consulta_php('co_comisiones')->get_tipos_subcos_de_comision($comision[0]['comision']);
				foreach ($tipos as $tipo) {
					if (!in_array($tipo['tipo_clase'], $this->tipos_subcos_sel)) {
						$msj = guarani_mensajes::get($this->msj_tipos_todos);
						throw guarani::error($msj);
					}
				}
				// Si se verificó que se seleccionó al menos un Subcomisión de cada Tipo, valida que se haya seleccionado 
				// sólo una subcomisión de cada tipo, es decir no se repitan tipos.
				$cant_tipos_clase = guarani_arreglos::count($tipos);
				$cant_subcom_seleccionadas = guarani_arreglos::count($this->tipos_subcos_sel);
				if ($cant_tipos_clase != $cant_subcom_seleccionadas) {
					$msj = guarani_mensajes::get($this->msj_tipos_unicos);
					throw guarani::error($msj);
				}
			}
		}
	}

	//--------------------------------------------------------------------------
	//---- JAVASCRIPT ----------------------------------------------------------
	//--------------------------------------------------------------------------

	function extender_objeto_js()
	{
		if (!empty($this->respuesta)) {
			echo "this.respuesta_ef_popup('" . $this->respuesta . "');";
		}

		echo "
		{$this->objeto_js}.evt__cerrar = function()	{
			window.close();
		}
		";
	}

}
?>