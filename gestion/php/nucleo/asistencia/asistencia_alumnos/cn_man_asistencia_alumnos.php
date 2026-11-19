<?php
/**
 *
 * 
 * @package		Asistencias
 * @subpackage	-
 * 
 */
class cn_man_asistencia_alumnos extends cn_manejador_entidad
{
	protected $s__indica_cant_inasistencias;
	protected $s__carga_horas;
	protected $s__clase;
	protected $s__alumnos_asistencia = array();
	protected $s__mostrar_libres = 0;

	function cargar_atributos_parametros($comision)
	{
		$operacion = guarani::get_id_operacion();

		$propuestas = aplanar_matriz(toba::consulta_php('co_comisiones')->get_propuestas_comision($comision), 'propuesta');
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, $propuestas);
	}
	
	function set_asistencias_por_horas($valor)
	{
		// Indica si la inasistencia se carga en cantidad de horas.
		$this->s__carga_horas = $valor;
	}

	function set_cant_inasistencias($valor)
	{
		// Indica si la inasistencia se carga en presente/ausente.
		$this->s__indica_cant_inasistencias = $valor;
	}

	function set_clase_seleccionada($clase)
	{
		$this->s__clase = $clase;
	}

	function set_mostrar_libres($mostrar)
	{
		$this->s__mostrar_libres = $mostrar;
	}
	
	function get_mostrar_libres()
	{
		return $this->s__mostrar_libres;
	}
	
	function indica_cantidad_inasistencias()
	{
		return ($this->s__indica_cant_inasistencias == comunes::si);
	}

	function indica_cantidad_horas()
	{
		return ($this->s__carga_horas == comunes::si);
	}

	function get_minimo_inasistencias()
	{
		if ($this->s__carga_horas == comunes::si) {
			return 0;
		} else {
			return 0.5;
		}
	}

	function get_maximo_inasistencias()
	{
		if ($this->s__carga_horas == comunes::si) {
			$datos_clase = toba::consulta_php('co_clases')->get_datos_clase($this->s__clase);
			// Se retorna el valor entero
			return intval($datos_clase['cantidad_horas_dictadas']);
		} else {
			return 1;
		}
	}

	function etiquetas($dep = null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('sga_clases_asistencias');
		} else {
			parent::etiquetas($dep);
		}
	}

	/**
	 * Recupera las comisiones en estado "Activo" y sin acta de cursada/promoción cerrada, según el filtro dado.
	 * 
	 * @param	string $filtro
	 * @return	array
	 */
	function get_listado_preseleccion($filtro)
	{
		$filtro .= " AND NOT EXISTS (	SELECT	sga_actas.id_acta
										FROM	sga_actas
										WHERE	sga_actas.comision = sga_comisiones.comision AND
												sga_actas.estado = '".acta::estado_cerrada."')";
		$filtro .= " AND sga_comisiones.estado = '" . comunes::estado_activo . "'";
		return toba::consulta_php('co_comisiones')->get_listado($filtro);
	}

	/**
	 * Recupera las Subcomisiones 
	 * @param	int $comision
	 * @return	array
	 */
	function get_listado_subcomisiones($comision)
	{
		return toba::consulta_php('co_comisiones')->get_subcomisiones_comision_con_asignacion_horaria($comision);
	}

	/**
	 * Recupera todas las Clases de la Comisión o Subcomisión dada con cantidad de Presentes y Ausentes.
	 * 
	 * @param int $comision
	 * @param int $subcomision
	 * @return array
	 */
	function get_listado_clases($comision, $subcomision = null)
	{
		if (!is_null($subcomision)) {
			return toba::consulta_php('co_clases')->get_clases_subcomision_con_presentes_ausentes($subcomision);
		} else {
			return toba::consulta_php('co_clases')->get_clases_comision_con_presentes_ausentes($comision);
		}
	}

	/**
	 * Recupera el listado de Alumnos Activos a los que se les va a cargar o editar la asistencia. Si ya existe 
	 * algún registro de asistencia cargado para la Clase, se traen los datos de esta tabla. Si todavía no existen 
	 * registros de asistencia cargados para la misma, se traeran los datos de todos los Alumnos inscriptos en
	 * la Comisión o Subcomisión seleccionada, según corresponda, para dar de alta su asistencia en la Clase.
	 * Se exceptuan de este listado los alumnos Libres ya que a los mismos no se les registrará manualmente la
	 * asistencia a clases.
	 */
	function get_listado_alumnos_asistencias($comision, $subcomision, $clase)
	{
		// Filtro para exceptuar alumnos libres en la comisión.
		$filtro_libres = "(	SELECT	sga_clases_asistencia_acum.alumno 
							FROM	sga_clases_asistencia_acum 
							WHERE	sga_clases_asistencia_acum.comision = $comision AND
									sga_clases_asistencia_acum.libre = 1)";
		$filtro = "(sga_alumnos.alumno NOT IN $filtro_libres)";
		// Chequea el parámetro que indica cómo se cargan las inasistencias para ver si deben recuperarse con o sin decimales.
		$cant_inasist_con_decimales = ($this->s__indica_cant_inasistencias == comunes::si && $this->s__carga_horas == comunes::si) ? false : true;
		$asistencias_alumnos_clase = toba::consulta_php('co_asistencias')->get_registros_asistencias_alumnos_clase($clase, $cant_inasist_con_decimales, $filtro, $subcomision);				
		// Valor default para cantidad de inasistencias.
		$cant_inasistencias = ($this->s__indica_cant_inasistencias == comunes::si) ? $this->get_maximo_inasistencias() : 1;
		$default = (guarani::parametros()->get_valor('asist_estado_default_asistencia') == asistencia::asist_default_presente) ? 0 : $cant_inasistencias;
		// Se recorre el array obtenido para agregar los valores por defecto en cantidad de inasistencias (utilizado para el campo editable o el checkbox de presente).
		foreach ($asistencias_alumnos_clase as $clave => $valor) {
			if (! $valor['existe_asistencia_alumno_clase']) {
				$asistencias_alumnos_clase[$clave]['presente'] = $default;
				$asistencias_alumnos_clase[$clave]['cant_inasistencias'] = $default;
				$asistencias_alumnos_clase[$clave]['cant_inasistencias_combo'] = $default;
			}
		}
		$datos_libres = array();
		if ($this->get_mostrar_libres()) {			
			$filtro_activos = "sga_alumnos.calidad = '".alumno::calidad_activo."'";
			$datos_libres = isset($subcomision) ? toba::consulta_php('co_comisiones')->get_datos_alumnos_inscriptos_a_subcomision($subcomision, $filtro_activos . ' AND sga_alumnos.alumno IN' . $filtro_libres) : toba::consulta_php('co_comisiones')->get_datos_alumnos_inscriptos_a_comision($comision, $filtro_activos . ' AND sga_alumnos.alumno IN' . $filtro_libres);
			foreach ($datos_libres as $clave => $valor) {
				$datos_libres[$clave]['cant_inasistencias'] = $cant_inasistencias;
				$datos_libres[$clave]['cant_inasistencias_combo'] = '1';
				$datos_libres[$clave]['cant_justificadas'] = '0';
				$datos_libres[$clave]['observaciones'] = 'Libre';
			}
		}
		return array_merge($datos_libres, $asistencias_alumnos_clase);
	}

	function registrar_asistencia_alumnos()
	{
		try {
			toba::db()->abrir_transaccion();
			foreach ($this->s__alumnos_asistencia as $alumno_asistencia) {
				if (isset($alumno_asistencia['cant_inasistencias_combo'])) {
					$alumno_asistencia['cant_inasistencias'] = $alumno_asistencia['cant_inasistencias_combo'];
				}				
				$this->validar_cant_inasistencias($alumno_asistencia['cant_inasistencias']);
				// Se inserta o actualiza el registro de asistencia para el alumno a la clase.
				if (guarani::co('co_asistencias')->existe_alumno_clase($this->s__clase, $alumno_asistencia['alumno'])) {
					guarani::act('act_asistencias')->modificar_registro_asistencia_clase($this->s__clase, $alumno_asistencia['alumno'], $alumno_asistencia['cant_inasistencias']);
				} else {
					guarani::act('act_asistencias')->insertar_registro_asistencia_clase($this->s__clase, $alumno_asistencia['alumno'], $alumno_asistencia['cant_inasistencias']);
				}
			}
			toba::db()->cerrar_transaccion();
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			throw $e;
		}
	}

	/**
	 * Verifica que el valor de cantidad inasistencias esté dentro del rango permitido, de acuerdo al parámetro 
	 * asist_computa_inasist_por_horas.
	 * Si es N el valor permitido será entre 0.5 a 1.
	 * Si es S el valor permitido será mayor a 0 y menor a la cantidad de horas totales de la clase.
	 */
	function validar_cant_inasistencias($cant_inasistencias)
	{
		// Si se edita la cantidad de inasistencias debe verificarse que esté entre el mínimo y máximo posible.
		if ($this->s__indica_cant_inasistencias == comunes::si) {
			$minimo = $this->get_minimo_inasistencias();
			$maximo = $this->get_maximo_inasistencias();
			// El valor 0 se exceptúa de la comparación con el mínimo dado que representa el valor de Alumno Presente.
			if ($cant_inasistencias <> 0 && $cant_inasistencias < $minimo) {
				$msj_error = guarani_mensajes::get('800_valor_menor', array('Cantidad Inasistencias', $minimo));
				throw new guarani_error($msj_error);
			}
			if ($cant_inasistencias > $maximo) {
				$msj_error = guarani_mensajes::get('800_valor_mayor', array('Cantidad Inasistencias', $maximo));
				throw new guarani_error($msj_error);
			}
		}
	}
	
	function set_asistencia($datos)
	{
		$this->s__alumnos_asistencia = $datos;
	}
	
	function limpiar_variables()
	{
		$this->s__alumnos_asistencia = array();
		$this->s__mostrar_libres = 0;				
	}
}
?>