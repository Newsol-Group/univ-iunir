<?php
/**
 *
 * 
 * @package 	Examenes
 * @subpackage 	Mesas de Examen
 * 
 */
class ci_edi_per_inscr extends guarani_pers_ci
{
	protected $indx_msg_eof = '800_eof_cuadro';
	protected $indx_msg_confirmar_eliminar = '800_confirmar_elimin';
	
	protected $datos_periodo_insc_temp;
	protected $s__per_fecha_selec;

	/**
	 * Verifica si un periodo de inscripcion fecha dado tiene una excepción asociada al Llamado de la Mesa que se está 
	 * editando. Si es así, retorna los datos de esa excepción en formato Recordset. En caso contrario retorna array vacío.
	 */
	function es_periodo_asociado($per, $excep_asociadas)
	{
		foreach ($excep_asociadas as $excep) {
			if ($excep['periodo_insc'] == $per) {
				return $excep;
			}
		}
		return array();
	}

	function conf__edicion_per_inscr(toba_ei_pantalla $pantalla)
	{
		$ci_navegacion = $this->controlador()->controlador()->controlador();
		$ci_navegacion->evento('guardar')->desactivar();
		$ci_navegacion->evento('cancelar')->desactivar();
		$ci_navegacion->evento('limpiar')->desactivar();

		if ($this->cn()->entidad()->hay_per_insc_excep_seleccionada()) {
			$etiqueta = guarani_etiquetas::get_tabla('sga_llamados_mesa_excep_perinsc');
			$this->dep('datos_excepcion_periodo')->evento('eliminar')->set_msg_confirmacion(guarani_mensajes::get($this->indx_msg_confirmar_eliminar, array($etiqueta->get('la'))));
		} else {
			$this->dep('datos_excepcion_periodo')->evento('eliminar')->ocultar();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- seleccion_periodos -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__seleccion_periodos(toba_ei_cuadro $cuadro)
	{
		$indx_msj = $this->indx_msg_eof;
		$etiqueta = guarani_etiquetas::get_tabla('sga_periodos_inscripcion_fechas');
		$param = $etiqueta->get_enf('plural') . ' asociadas al Llamado ';
		$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj, array($param)));

		// Se obtienen los datos de ubicación, modalidades, y planes (propuestas) asociados a la mesa correspondiente al llamado que se está editando.
		$datos_llamado = $this->cn()->entidad()->get_datos_llamado();
		$datos_mesa = toba::consulta_php('co_mesas_de_examen')->get_datos_mesa($datos_llamado['mesa_examen']);
		$propuestas_planes_mesa = toba::consulta_php('co_mesas_de_examen')->get_propuestas_planes_mesa($datos_llamado['mesa_examen']);
		$modalidades_mesa = toba::consulta_php('co_mesas_de_examen')->get_modalidades_mesa($datos_llamado['mesa_examen']);
		$periodos = toba::consulta_php('co_mesas_de_examen')->get_datos_per_insc_fechas_para_llamado_mesa($datos_llamado['llamado_mesa'], $propuestas_planes_mesa, $datos_mesa['ubicacion'], $modalidades_mesa);

		// Si existen actualmente excepciones definidas para los Períodos Fechas del Llamado en edición,
		// se deben asociar los datos de las mismas a los períodos fechas obtenidos.
		foreach ($periodos as $clave => $valor) {
			$excep_perinsc = $this->cn()->entidad()->existe_per_insc_excep($valor['periodo_insc']);
			if (!is_null($excep_perinsc)) {
				$this->cn()->entidad()->seleccionar_per_insc_excep($excep_perinsc);
				$datos_excep_perinsc = $this->cn()->entidad()->get_datos_per_insc_excep_seleccionada();
				$periodos[$clave]['fecha_inicio_excep'] = $datos_excep_perinsc['fecha_inicio'];
				$periodos[$clave]['fecha_fin_excep'] = toba::consulta_php('co_mesas_de_examen')->get_fecha_fin_examen($datos_llamado['llamado_mesa'], $datos_excep_perinsc['fecha_inicio'], $datos_excep_perinsc['fecha_fin'], $datos_excep_perinsc['tipo_fin'], $datos_excep_perinsc['dias_previos_fin'], $datos_excep_perinsc['hs_previas_fin']);
				$periodos[$clave]['fecha_tope_bajas_excep'] = toba::consulta_php('co_mesas_de_examen')->get_fecha_fin_examen($datos_llamado['llamado_mesa'], $datos_excep_perinsc['fecha_inicio'], $datos_excep_perinsc['fecha_tope_bajas'], $datos_excep_perinsc['tipo_tope_bajas'], $datos_excep_perinsc['dias_previos_tope_bajas'], $datos_excep_perinsc['hs_previas_tope_bajas']);
			}
		}

		$cuadro->set_datos($periodos);
	}

	function evt__seleccion_periodos__seleccion($seleccion)
	{
		$this->s__per_fecha_selec = current($seleccion);
		$excep = $this->cn()->entidad()->existe_per_insc_excep($this->s__per_fecha_selec);
		if (!is_null($excep)) {
			$this->cn()->entidad()->seleccionar_per_insc_excep($excep);
		}
		$this->set_pantalla('edicion_per_inscr');
	}

	//-----------------------------------------------------------------------------------
	//---- datos_excepcion_periodo ------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__datos_excepcion_periodo(toba_ei_formulario $form)
	{
		if (isset($this->datos_periodo_insc_temp)) {
			$this->controlador()->controlador()->controlador()->set_hubo_cambios();
			$datos = $this->datos_periodo_insc_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos_per_insc_excep_seleccionada();
		}
		if (!empty($datos['fecha_inicio'])) {
			$datos['fecha_inicio'] = guarani_fecha::separar_fecha_hora($datos['fecha_inicio']);
		}
		if (!empty($datos['fecha_fin'])) {
			$datos['fecha_fin'] = guarani_fecha::separar_fecha_hora($datos['fecha_fin']);
		}
		if (!empty($datos['fecha_tope_bajas'])) {
			$datos['fecha_tope_bajas'] = guarani_fecha::separar_fecha_hora($datos['fecha_tope_bajas']);
		}
		$form->set_datos($datos);
	}

	function evt__datos_excepcion_periodo__confirmar($datos)
	{
		try {
			$datos['fecha_inicio'] = "{$datos['fecha_inicio'][0]} {$datos['fecha_inicio'][1]}";
			if (isset($datos['fecha_fin'])) {
				$datos['fecha_fin'] = "{$datos['fecha_fin'][0]} {$datos['fecha_fin'][1]}";
			}
			if (isset($datos['fecha_tope_bajas'])) {
				$datos['fecha_tope_bajas'] = "{$datos['fecha_tope_bajas'][0]} {$datos['fecha_tope_bajas'][1]}";
			}

			$datos['periodo_insc'] = $this->s__per_fecha_selec;
			$this->cn()->entidad()->set_datos_per_insc_excep($datos);
			$this->evt__datos_excepcion_periodo__cancelar();
		} catch (guarani_error $e) {
			$this->datos_periodo_insc_temp = $datos;
			throw $e;
		}
	}

	function evt__datos_excepcion_periodo__cancelar()
	{
		$this->cn()->entidad()->deseleccionar_per_insc_excep();
		$this->set_pantalla('seleccion_per_inscr');
	}

	function evt__datos_excepcion_periodo__eliminar()
	{
		$this->cn()->entidad()->eliminar_per_insc_excep();
		$this->evt__datos_excepcion_periodo__cancelar();
	}
}
?>