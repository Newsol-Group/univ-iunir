<?php
/**
 *
 * 
 * @package		Unidades de Gestión
 * @subpackage	Actualizaciones
 * 
 */
class cn_ent_libro_actas extends cn_entidad
{
	/**
	 * Función especial para resolver los parámetros de libro de acta.
	 * 
	 * Como la selección de alcance por propuesta es opcional, se puede o no disponer
	 * de atributos para resolver los parámetros de nivel 4 (propuesta) involucrados 
	 * en la operación. 
	 * 
	 * Si existe alcance por propuestas, se lo resuelve por el mecanismo habitual,
	 * si no, se retorna el valor definido para "Toda la Institución".
	 * 
	 * @param string $parametro
	 */
	function get_valor_parametro($parametro)
	{
		try {
			return guarani::parametros()->get_valor($parametro);
		} catch (guarani_error_params $e) {
			return guarani::parametros()->get_default($parametro);
		}
	}
	
	function cargar_atributos_parametros()
	{
		$operacion = guarani::get_id_operacion();
		$propuestas = $this->get_propuestas();
		if (!empty($propuestas)) {
			$propuestas = aplanar_matriz($propuestas, 'propuesta');
			guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, $propuestas);
		} else {
			$this->resetear_atributos_parametros();
		}
		
		// Si la entidad es nueva, puede haber cambiado el valor de cant. máxima de 
		// folios en tomos, por lo que se recalcula.
		if ($this->es_nueva()) {
			$this->recalcular_cant_max_folios_primer_tomo();
		}
	}
	
	protected function recalcular_cant_max_folios_primer_tomo()
	{
		$cant_max_folios = $this->get_valor_parametro('ung_cant_max_folios_libro');
		$this->tabla('libros_tomos')->set_columna_valor('cant_max_folios', $cant_max_folios);
	}
	
	function cargar($id)
	{
		parent::cargar($id);
		$this->cargar_atributos_parametros();
	}
	
	/**
	 * Implementa la eliminacion de la entidad
	 */
	function eliminar()
	{
		$this->validar_eliminacion();
		parent::eliminar();
	}

	/**
	 * Determina si es posible eliminar el libro de actas.
	 */
	function validar_eliminacion()
	{
		$datos_libro = $this->get_datos();
		$libro = $datos_libro['libro'];

		if (toba::consulta_php('co_actas')->libro_tiene_actas($libro)) {
			$this->set_error('800_dato_no_eliminable', array('Libro de Actas', 'tiene actas asociadas'));
		}

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	//--------------------------------------------------------------------------
	//---- sga_libros_actas ----------------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Datos generales del libro
	 */
	function set_datos($datos, $tabla = null, $validar = true)
	{
		$this->validar_datos($datos);
		$this->tabla('libro_actas')->set($datos);

		// Se setean los alcances
		$alcances = $datos['tipo_libro'];
		$this->set_alcances($alcances);

		$tomos = $this->tabla('libros_tomos')->get_filas();
		if (empty($tomos)) {
			$this->generar_primer_tomo();
		}
	}

	function get_datos($tabla = null)
	{
		$datos = $this->tabla('libro_actas')->get();

		// Se integran los alcances
		$alcances = $this->get_alcances();
		$tipos = array();
		$i = 0;

		foreach ($alcances as $alcance) {
			$tipos[$i] = $alcances[$i]['origen'];
			$i++;
		}

		$datos['tipo_libro'] = $tipos;

		return $datos;
	}

	function validar_datos($datos)
	{
		if (isset($datos['fecha_fin_vigencia'])) {
			if (guarani_fecha::es_menor_igual($datos['fecha_fin_vigencia'], $datos['fecha_activacion'])) {
				$this->set_error('800_control_fechas', array('de fin de vigencia', 'menor o igual', 'de activación'));
			}

			if ((isset($datos['es_libro_activo']) && ($datos['es_libro_activo'] == comunes::si))) {
				// Si el libro es definido como vigente, se controla que la fecha de fin de vigencia sea mayor o igual a la fecha actual.
				if (guarani_fecha::es_menor_igual($datos['fecha_fin_vigencia'], guarani_fecha::get_hoy())) {
					$this->set_error('800_control_fechas', array('de fin de vigencia', 'menor o igual', 'actual'));
				}
			}
		}

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	//--------------------------------------------------------------------------
	//---- sga_libros_actas_alcance --------------------------------------------
	//--------------------------------------------------------------------------

	function set_alcances($datos)
	{
		$alcances = $this->tabla('alcances')->get_filas();
		$alcances_aplanados = aplanar_matriz($alcances, 'origen');
		$alcances_a_borrar = array_diff($alcances_aplanados, $datos);
		$alcances_a_insertar = array_diff($datos, $alcances_aplanados);

		$alcances_asociativos = rs_convertir_asociativo($alcances, array('origen'), apex_datos_clave_fila);
		foreach ($alcances_a_borrar as $alcance) {
			$this->tabla('alcances')->eliminar_fila($alcances_asociativos[$alcance]);
		}

		foreach ($alcances_a_insertar as $alcance) {
			$this->tabla('alcances')->nueva_fila(array('origen' => $alcance));
		}
	}

	function get_alcances()
	{
		return $this->tabla('alcances')->get_filas();
	}

	/**
	 * Permite agregar tomo si no hay más de dos tomos con folios físicos disponibles
	 */
	function puede_agregar_tomo()
	{
		$forma_asignacion_folio_fisico = $this->get_valor_parametro('exa_forma_asignacion_folio_fisico');
		if ((($forma_asignacion_folio_fisico == acta::forma_asignacion_folio_fisico_manual) && ($this->libro_tiene_alcance(acta::origen_examen)) && ($this->get_cantidad_tomos_con_lugar() < 2))) {
			return true;
		}

		$forma_asignacion_folio_fisico = $this->get_valor_parametro('cur_forma_asignacion_folio_fisico_regul');
		if ((($forma_asignacion_folio_fisico == acta::forma_asignacion_folio_fisico_manual) && ($this->libro_tiene_alcance(acta::origen_regulares)) && ($this->get_cantidad_tomos_con_lugar() < 2))) {
			return true;
		}

		$forma_asignacion_folio_fisico = $this->get_valor_parametro('cur_forma_asignacion_folio_fisico_promo');
		if ((($forma_asignacion_folio_fisico == acta::forma_asignacion_folio_fisico_manual) && ($this->libro_tiene_alcance(acta::origen_promociones)) && ($this->get_cantidad_tomos_con_lugar() < 2))) {
			return true;
		}

		return false;
	}

	protected function get_cantidad_tomos_con_lugar()
	{
		$tomos = $this->get_libros_tomos();
		$cantidad = 0;
		foreach ($tomos as $tomo) {
			if ($tomo['cant_folios_usados'] < $tomo['cant_max_folios']) {
				$cantidad++;
			}
		}
		return $cantidad;
	}

	protected function libro_tiene_alcance($alcance)
	{
		$datos = $this->get_alcances();
		foreach ($datos as $dato) {
			if ($dato['origen'] == $alcance) {
				return true;
			}
		}
		return false;
	}

	//--------------------------------------------------------------------------
	//---- sga_libros_actas_propuesta ------------------------------------------
	//--------------------------------------------------------------------------

	function set_propuestas($datos)
	{
		$this->tabla('propuestas')->procesar_filas($datos);
		$this->cargar_atributos_parametros();
	}

	function get_propuestas()
	{
		return $this->tabla('propuestas')->get_filas();
	}

	function asignar_propuesta($propuesta)
	{
		$this->tabla('propuestas')->nueva_fila($propuesta);
		$this->cargar_atributos_parametros();
	}

	function eliminar_propuesta($propuesta)
	{
		$this->tabla('propuestas')->eliminar_fila($propuesta);
		$this->cargar_atributos_parametros();
	}

	//--------------------------------------------------------------------------
	//---- sga_libros_actas_ubicacion ------------------------------------------
	//--------------------------------------------------------------------------

	function set_ubicaciones($datos)
	{
		$this->tabla('ubicaciones')->procesar_filas($datos);
	}

	function get_ubicaciones()
	{
		return $this->tabla('ubicaciones')->get_filas();
	}

	function asignar_ubicacion($ubicacion)
	{
		$this->tabla('ubicaciones')->nueva_fila($ubicacion);
	}

	function eliminar_ubicacion($ubicacion)
	{
		$this->tabla('ubicaciones')->eliminar_fila($ubicacion);
	}

	//--------------------------------------------------------------------------
	//---- sga_libros_actas_actividad ------------------------------------------
	//--------------------------------------------------------------------------

	function set_actividades($datos)
	{
		$this->tabla('actividades')->procesar_filas($datos);
	}

	function get_actividades()
	{
		return $this->tabla('actividades')->get_filas();
	}

	function asignar_actividad($actividad)
	{
		$this->tabla('actividades')->nueva_fila($actividad);
	}

	function eliminar_actividad($actividad)
	{
		$this->tabla('actividades')->eliminar_fila($actividad);
	}

	//--------------------------------------------------------------------------
	//---- sga_libros_tomos ----------------------------------------------------
	//--------------------------------------------------------------------------

	function get_libros_tomos()
	{
		$datos = $this->tabla('libros_tomos')->get_filas();
		foreach ($datos as $key => $dato) {
			if (empty($dato['libro_tomo'])) {
				$datos[$key]['cant_folios_usados'] = 0;
			} else {
				$datos[$key]['cant_folios_usados'] = toba::consulta_php('co_actas')->get_cantidad_folios_usados_tomo($dato['libro_tomo']);
			}
		}
		return rs_ordenar_por_columna($datos, 'nro_tomo');
	}

	function generar_primer_tomo()
	{
		$datos = array(
			'nro_tomo' => 1,
			'cant_max_folios' => $this->get_valor_parametro('ung_cant_max_folios_libro'),
			'cant_folios_usados' => '0'
		);

		$this->tabla('libros_tomos')->nueva_fila($datos);
	}

	function agregar_tomo($datos)
	{
		$this->tabla('libros_tomos')->nueva_fila($datos);
	}

}
?>