<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class cn_ent_requisito_de_persona extends cn_entidad
{
	// Persona seleccionada.
	protected $s__persona;
	
	// Requisitos presentados sincronizados en BD.
	protected $s__req_ya_presentados;
	
	// Requisitos presentados no sincronizados en BD.
	protected $s__req_presentados_seleccionados;
	
	// Requisitos marcados para aceptar desde Draft
	protected $s__req_aceptados_draft;
	
	// Requisitos marcados para rechazar desde Draft
	protected $s__req_rechazados_draft;
	
	// Requisitos de tipo proceso
	protected $requisitos_tipo_proceso;
	
	// Requisitos de tipo proceso a exceptuar
	protected $s__req_exceptuar;
	
	// Requisitos presentados y vencidos
	protected $s__requisitos_vencidos;
	
	//--------------------------------------------------------------------------
	//---- Variables y constantes para manejo de documentos digitales
	//--------------------------------------------------------------------------
	
	// Estructura temporal para el manejo de documentos digitales a sincronizar con RDI
	// Representa un "datos_tabla" ad-hoc para el manejo de requisitos digitales, 
	// respecto de su situación en el repositorio
	protected $s__datos_tabla_rdi;
	
	// Descargas temporales de RDI
	protected $s__temp_docs_guarani;
	
	// Descargas temporales de RDI de Draft
	protected $s__temp_doc_draft;

	// Constantes para establecer las acciones que seguir con un documento digital cargado
	const temp_rdi_alta = 'A';
	const temp_rdi_baja = 'B';
	const temp_rdi_modificacion = 'M';
	
	// Estados de un documento digital asociado a un requisito
	const estado_rdi_NO_CARGADO = 1; // No cargado
	const estado_rdi_ACEPTADO = 2;	// Aceptado
	const estado_rdi_RECHAZADO = 3;	// Rechazado
	const estado_rdi_PEND_VAL = 4;	// Pendiente de validación

	protected $descr_estados_rdi = array(
		self::estado_rdi_NO_CARGADO => 'No cargado',
		self::estado_rdi_ACEPTADO => 'Aceptado',
		self::estado_rdi_RECHAZADO => 'Rechazado',
		self::estado_rdi_PEND_VAL => 'Pendiente de validación',
	);

	//--------------------------------------------------------------------------

	function resetear()
	{
		parent::resetear();
		unset($this->s__persona);
		unset($this->s__req_exceptuar);
		unset($this->s__datos_tabla_rdi);
		// Reset y unlink de descargas temporales de RDI
		if (isset($this->s__temp_docs_guarani)) {
			foreach ($this->s__temp_docs_guarani as $path) {
				if ((!empty($path['path'])) && (file_exists($path['path']))) {
					unlink($path['path']);
				}
			}
			unset($this->s__temp_docs_guarani);
		}
		// Reset y unlink de descargas temporales de RDI de Draft
		if (isset($this->s__temp_doc_draft)) {
			foreach ($this->s__temp_doc_draft as $path) {
				if ((!empty($path['path'])) && (file_exists($path['path']))) {
					unlink($path['path']);
				}
			}
			unset($this->s__temp_doc_draft);
		}
		$this->s__req_ya_presentados = array();
		$this->s__req_presentados_seleccionados = array();
		$this->s__req_aceptados_draft = array();
		$this->s__req_rechazados_draft = array();
		unset($this->s__requisitos_vencidos);
	}

	function get_persona_seleccionada()
	{
		return $this->s__persona;
	}

	function get_requisitos_presentados()
	{
		return $this->s__req_ya_presentados;
	}
	
	function hay_cambios()
	{
		return $this->dep('datos')->hay_cambios() || isset($this->s__datos_tabla_rdi);
	}

	/**
	 * Configuración parametrizada que determina si se utiliza Repositorio Digital 
	 * Integrado (RDI) para almacenar los archivos asociados a requisitos documentales.
	 * 
	 * @return boolean
	 */
	function usa_repositorio_digital()
	{
		return guarani::parametros()->get_valor('usa_repositorio_digital') == comunes::si;
	}
	
	/**
	 * Determina si hay presentaciones anteriores vencidas para un requisito
	 * 
	 * @param int $requisito
	 * @return boolean
	 */
	function hay_presentaciones_vencidas($requisito)
	{
		if (!isset($this->s__requisitos_vencidos)) {
			$datos = toba::consulta_php('co_requisitos')->get_requisitos_vencidos_persona($this->s__persona);
			$this->s__requisitos_vencidos = aplanar_matriz($datos, 'requisito');
		}
		return in_array($requisito, $this->s__requisitos_vencidos);
	}

	//-----------------------------------------------------------------------------------
	//---- Carga de datos
	//-----------------------------------------------------------------------------------

	function cargar($id)
	{
		$this->resetear();
		$this->s__persona = $id['persona'];
		$this->cargar_requisitos();
		$this->cargar_requisitos_presentados();
		$this->cargar_requisitos_aspirante();
		$this->cargar_requisitos_certificado();
		if ($this->usa_repositorio_digital()) {
			$this->cargar_requisitos_digitales();
			$this->cargar_requisitos_digitales_draft_detalle();
		}
	}

	function cargar_requisitos()
	{
		$datos = guarani::co('co_requisitos')->get_requisitos_persona($this->s__persona);
		// Los requisitos vencidos se dejan como "no presentados"
		foreach ($datos as $key => $dato) {
			if ($dato['estado'] == requisito::estado_vencido) {
				$datos[$key]['estado'] = requisito::estado_pendiente;
				$datos[$key]['presentado'] = 0;
				$datos[$key]['presentado_bd'] = 0;
				$datos[$key]['estado_doc_digital_id'] = static::estado_rdi_NO_CARGADO;
				$datos[$key]['estado_doc_digital'] = $this->descr_estados_rdi[static::estado_rdi_NO_CARGADO];
			}
		}
		$this->dep('datos')->tabla('requisitos')->persistidor()->cargar_con_datos($datos);
	}

	/**
	 * Carga el DT de requisitos presentados de los requisitos de la persona en estado 'VIGENTE'
	 */
	function cargar_requisitos_presentados()
	{
		$requisitos = $this->get_datos_requisitos(array('presentado' => 1));
		
		$requisitos_presentados = guarani::co('co_requisitos')->get_requisitos_presentados_vigentes_persona($this->s__persona, $requisitos);
		
		$this->dep('datos')->tabla('requisito_presentado')->persistidor()->cargar_con_datos($requisitos_presentados);
		$this->s__req_ya_presentados = aplanar_matriz($requisitos_presentados, 'requisito');
	}

	function cargar_requisitos_aspirante()
	{
		$requisitos = $this->get_datos_requisitos();
		$sql = toba::consulta_php('co_requisitos')->requisitos_aspirante($this->s__persona, $requisitos, '', true);
		$this->tabla('requisito_aspirante')->persistidor()->cargar_con_sql($sql);
	}

	function cargar_requisitos_certificado()
	{
		$requisitos = $this->get_datos_requisitos();
		$sql = toba::consulta_php('co_requisitos')->requisitos_certificado($this->s__persona, $requisitos, true);
		$this->tabla('requisito_certificado')->persistidor()->cargar_con_sql($sql);
	}

	function cargar_requisitos_digitales()
	{
		$req_presentados = $this->get_datos_requisitos_presentados();
		
		// Se cargan únicamente los requisitos digitales de presentaciones no vencidas
		$ids_requisitos_digitales = array();
		foreach ($req_presentados as $req_presentado) {
			if (!empty($req_presentado['requisito_digital'])) {
				$ids_requisitos_digitales[] = $req_presentado['requisito_digital'];
			}
		}
		
		if (!empty($ids_requisitos_digitales)) {
			$requisitos_digitales = guarani::co('co_requisitos')->get_datos_requisitos_digitales($ids_requisitos_digitales);
			$this->dep('datos')->tabla('requisitos_digitales')->persistidor()->cargar_con_datos($requisitos_digitales);
		}
	}

	function cargar_requisitos_digitales_draft_detalle()
	{
		$requisitos = $this->get_datos_requisitos();
		$sql = toba::consulta_php('co_requisitos')->requisitos_digitales_draft_detalle($this->s__persona, $requisitos, true);
		$this->tabla('requisitos_digitales_draft_detalle')->persistidor()->cargar_con_sql($sql);
	}

	//-----------------------------------------------------------------------------------
	//---- Obtención de datos
	//-----------------------------------------------------------------------------------

	function get_datos($tabla = null)
	{
		$condicion = array('requisito_tipo' => requisito::tipo_documentales);
		$datos = $this->get_datos_requisitos($condicion);
		$req_presentados = $this->get_datos_requisitos_presentados();
				
		foreach ($datos as $key => $dato) {
			if ((isset($dato['agregado'])) && ($dato['agregado']) && (empty($dato['fecha_presentacion']))) {
				// Requisito recién agregado -> sugiere fecha actual como fecha de presentación
				$datos[$key]['fecha_presentacion'] = guarani_fecha::get_hoy();
			}

			$ind_presentado = $this->get_indice_req_presentado($dato['requisito']);
			if ($ind_presentado >= 0) {
				$datos[$key]['presentado'] = 1;
				$datos[$key]['fecha_presentacion'] = $req_presentados[$ind_presentado]['fecha_presentacion'];
				$datos[$key]['fecha_vencimiento'] = $req_presentados[$ind_presentado]['fecha_vencimiento'];
				$datos[$key]['doc_digital_cargado'] = !empty($req_presentados[$ind_presentado]['requisito_digital']);
			}

			$req_aspirante = $this->get_datos_requisitos_aspirante($dato['requisito']);
			$cant_req_aspirante = guarani_arreglos::count($req_aspirante);

			$alguno = '';
			$cant_exceptuadas = 0;
			foreach ($req_aspirante as $requi) {
				if ($requi['exceptuado'] == comunes::si) {
					$cant_exceptuadas++;
				}
			}

			$exceptuado_todas = ($cant_req_aspirante != 0 && $cant_req_aspirante == $cant_exceptuadas);
			$exceptuado_algunas = false;
			if ($exceptuado_todas) {
				$msj = 'El requisito está exceptuado para todas las Propuestas';
				//Si el requisito esta solo para una propuesta y esta exceptuado cambio mensaje.
				if (guarani_arreglos::count($req_aspirante) == 1) {
					$msj = 'El requisito está exceptuado';
				}
				$js = "onmouseover='return window.tipclick.show(\"$msj\",this,event, 1000)'; onmouseout='return window.tipclick.hide()'";
				$alguno = toba_recurso::imagen_toba('info_chico.gif', true, null, null, null, null, $js);
			} elseif ($cant_exceptuadas > 0) {
				$js = "onmouseover='return window.tipclick.show(\"El requisito está exceptuado para algunas de las Propuestas del alumno\",this,event, 1000)'; onmouseout='return window.tipclick.hide()'";
				$alguno = toba_recurso::imagen_toba('warning.gif', true, null, null, null, null, $js);
				$exceptuado_algunas = true;
			}

			$datos[$key]['exceptuado_algunas'] = $exceptuado_algunas;
			$datos[$key]['exceptuado_todas'] = $exceptuado_todas;
			$datos[$key]['exceptuado_texto'] = $alguno;
			$datos[$key]['hay_doc_temp_cargado'] = $this->hay_doc_temp_cargado($dato['requisito']);
			$datos[$key]['estado_doc_digital_id'] = $this->get_estado_requisito_digital($dato['requisito']);
			$datos[$key]['estado_doc_digital'] = $this->get_descr_estado_rdi($dato['requisito']);
		}

		return $datos;
	}

	function get_indice_req_presentado($requisito)
	{
		$req_presentados = $this->get_datos_requisitos_presentados();
		foreach ($req_presentados as $key => $dato) {
			if ($dato['requisito'] == $requisito) {
				return $key;
			}
		}
		return -1;
	}

	function get_datos_requisitos($condicion = null)
	{
		return $this->tabla('requisitos')->get_filas($condicion);
	}

	function get_datos_requisitos_disponibles_agregar()
	{
		$where = "sga_requisitos.requisito_tipo = '" . requisito::tipo_documentales . "' AND ";
		$where .= guarani_sql::armar_condicion_not_in($this->get_datos(), 'sga_requisitos', 'requisito', '');
		// Sólo están disponibles los requisitos en estado Activo.
		$where .= " AND sga_requisitos.activo = '" . comunes::si . "'";
		return guarani::co('co_requisitos')->get_listado($where);
	}

	//-----------------------------------------------------------------------------------
	//---- sga_requisitos
	//-----------------------------------------------------------------------------------

	function agregar_requisito($requisito)
	{
		$datos_req = guarani::co('co_requisitos')->get_requisito_datos($requisito);
		$datos_req['presentado'] = 1;
		$datos_req['agregado'] = 1;
		$datos_req['estado'] = requisito::estado_vigente;
		$datos_req['solicitado_para'] = requisito::requisito_para_ninguno_descr;
		$this->tabla('requisitos')->nueva_fila($datos_req);
	}

	function get_id_requisito($condiciones)
	{
		return current($this->tabla('requisitos')->get_id_fila_condicion($condiciones));
	}

	function set_cursor_requisitos($id_fila)
	{
		$this->tabla('requisitos')->set_cursor($id_fila);
	}

	function unset_cursor_requisitos()
	{
		$this->tabla('requisitos')->resetear_cursor();
	}

	function eliminar_requisito($requisito)
	{
		$this->eliminar_requisito_digital($requisito);
		$this->eliminar_requisito_presentado($requisito);
		$this->eliminar_documento($requisito);

		foreach ($this->s__req_ya_presentados as $key => $dato) {
			if ($dato == $requisito) {
				unset($this->s__req_ya_presentados[$key]);
				break;
			}
		}
		
		foreach ($this->s__req_presentados_seleccionados as $key => $dato) {
			if ($dato == $requisito) {
				unset($this->s__req_presentados_seleccionados[$key]);
				break;
			}
		}

		if (isset($this->s__datos_tabla_rdi[$requisito]['archivo']['tmp_name'])) {
			unlink($this->s__datos_tabla_rdi[$requisito]['archivo']['tmp_name']);
		}
		if (isset($this->s__datos_tabla_rdi[$requisito])) {
			unset($this->s__datos_tabla_rdi[$requisito]);
		}

		if (!$this->tabla('requisito_aspirante')->existe_fila_condicion(array('requisito' => $requisito), false) &&
			!$this->tabla('requisito_certificado')->existe_fila_condicion(array('requisito' => $requisito), false)) {
			$this->tabla('requisitos')->eliminar_fila($this->get_id_requisito(array('requisito' => $requisito)));
		} else {
			$this->tabla('requisitos')->set_fila_columna_valor($this->get_id_requisito(array('requisito' => $requisito)), 'presentado', 0);
			$this->tabla('requisitos')->set_fila_columna_valor($this->get_id_requisito(array('requisito' => $requisito)), 'estado', requisito::estado_pendiente);
		}
		
		$this->deshacer_acciones_draft($requisito);
	}

	//-----------------------------------------------------------------------------------
	//---- sga_requisitos_digitales
	//-----------------------------------------------------------------------------------
	
	/**
	 * Obtiene los datos de los requisitos digitales para todos los requisitos de la persona.
	 * 
	 * @param array $condiciones condiciones sobre los requisitos a recuperar.
	 * @return array filas de requisitos digitales de la persona que cumplen con la condicion dada.
	 */
	function get_datos_requisitos_digitales($condiciones = null)
	{
		return $this->tabla('requisitos_digitales')->get_filas($condiciones, false, false);
	}

	/**
	 * Obtiene los datos del requisito digital asociado al requisito y persona dados, si es
	 * que existe.
	 * 
	 * @param int $requisito id del requisito para el cual se desea obtener el requisito digital
	 * @param int $persona id de la persona para la cual se desea obtener el requisito digital
	 * @return array datos del requisito digital
	 */
	function get_datos_req_digital($requisito, $persona)
	{
		$requisito_dig = $this->get_datos_requisitos_digitales(array('requisito' => $requisito, 'persona' => $persona));
		if (!empty($requisito_dig)) {
			return current($requisito_dig);
		}
	}

	function eliminar_requisito_digital($requisito)
	{
		$fila = $this->tabla('requisitos_digitales')->get_id_fila_condicion(array('requisito' => $requisito, 'persona' => $this->s__persona), false);
		if (!empty($fila)) {
			$this->tabla('requisitos_digitales')->eliminar_fila(current($fila));
		}
	}

	/**
	 * Obtiene el atributo nombre asociado al documento almacenado en el repositorio digital,
	 * asociado al requisito digital dado.
	 * 
	 * @param array $datos datos del requisito digital para el que se quieren recuperar los datos
	 * @return string nombre asociado al documento almacenado en el repositorio digital
	 */
	function obtener_nombre_archivo_requisito_digital($datos)
	{
		$serv = $datos['tipo_documento_digital'];
		$nombre = guarani_cliente_rdi::get_nombre_documento($datos['id_documento'], $serv);
		return $nombre;
	}

	//-----------------------------------------------------------------------------------
	//---- sga_requisitos_presentados
	//-----------------------------------------------------------------------------------

	function get_datos_requisitos_presentados($condicion = null)
	{
		return $this->tabla('requisito_presentado')->get_filas($condicion, false, false);
	}

	function agregar_requisito_presentado($requisito, $fecha_presentacion, $fecha_vencimiento = null)
	{
		$fila = array(
			'requisito' => $requisito,
			'persona' => $this->s__persona,
			'fecha_presentacion' => $fecha_presentacion,
			'fecha_vencimiento' => $fecha_vencimiento
		);

		$id_fila_padre = $this->get_id_requisito(array('requisito' => $requisito));
		$this->set_cursor_requisitos($id_fila_padre);
		$id = $this->tabla('requisito_presentado')->nueva_fila($fila);
		$this->unset_cursor_requisitos();

		if (!isset($this->s__req_presentados_seleccionados)) {
			$this->s__req_presentados_seleccionados = array();
		}
		$this->s__req_presentados_seleccionados[] = $requisito;
		return $id;
	}

	function modificar_requisito_presentado($requisito, $fecha_presentacion, $fecha_vencimiento = null)
	{
		$id_fila = current($this->tabla('requisito_presentado')->get_id_fila_condicion(array('requisito' => $requisito), false));
		if (isset($id_fila)) {
			$this->tabla('requisito_presentado')->set_fila_columna_valor($id_fila, 'fecha_presentacion', $fecha_presentacion);
			$this->tabla('requisito_presentado')->set_fila_columna_valor($id_fila, 'fecha_vencimiento', $fecha_vencimiento);
		}
	}

	function eliminar_requisito_presentado($requisito)
	{
		/**
		 * Observar que aunque se elimina el req presentado, se sigue conservando el requisito
		 * digital asociado, ya que este puede cargarse independientemente de su presentación.
		 * Debería verificarse el control de vigencia. Incluso al eliminar el requisito digital,
		 * se definió que no se eliminen, en ningún caso, los documentos de nuxeo.
		 */
		if ($this->tabla('requisito_presentado')->existe_fila_condicion(array('requisito' => $requisito), false)) {
			$id_fila = current($this->tabla('requisito_presentado')->get_id_fila_condicion(array('requisito' => $requisito), false));
			$this->tabla('requisito_presentado')->eliminar_fila($id_fila);
			// Se elimina de lista de requisitos presentados agregados
			foreach ($this->s__req_presentados_seleccionados as $key => $dato) {
				if (isset($dato['requisito']) && ($dato['requisito'] == $requisito)) {
					unset($this->s__req_presentados_seleccionados[$key]);
				}
			}
		}
	}

	/**
	 * Asocia la pk del requisito digital al requisito presentado asociado.
	 * @param array $req_digital datos del requisito digital asociado al requisito marcado como presentado.
	 * @param integer $id_presentado id de fila del requisito presentado a actualizar.
	 */
	function asociar_req_digital_a_req_presentado($req_digital, $id_presentado)
	{
		$pk_req_digital = $req_digital['requisito_digital'] ?? null;
		$this->tabla('requisito_presentado')->set_fila_columna_valor($id_presentado, 'requisito_digital', $pk_req_digital);
	}

	//-----------------------------------------------------------------------------------
	//---- sga_requisitos_aspirante
	//-----------------------------------------------------------------------------------

	function get_datos_requisitos_aspirante($requisito)
	{
		return $this->tabla('requisito_aspirante')->get_filas(array('requisito' => $requisito), false, false);
	}

	function procesar_datos_requisitos_aspirante($datos)
	{
		$this->validar_requisitos_aspirantes($datos);
		$this->tabla('requisito_aspirante')->procesar_filas($datos);
	}

	//-----------------------------------------------------------------------------------
	//---- sga_requisitos_digitales_draft_detalle
	//-----------------------------------------------------------------------------------

	function get_datos_requisitos_digitales_draft_detalle($condiciones = null)
	{
		return $this->tabla('requisitos_digitales_draft_detalle')->get_filas($condiciones, false, false);
	}

	function get_datos_requisito_digital_draft_detalle($requisito, $persona)
	{
		$requisito_digital_draft_detalle = $this->get_datos_requisitos_digitales_draft_detalle(array('requisito' => $requisito, 'persona' => $persona));
		if (!empty($requisito_digital_draft_detalle)) {
			return current($requisito_digital_draft_detalle);
		}
	}

	function agregar_requisito_digital_draft_detalle($requisito, $observaciones='')
	{
		$fila = array('persona' => $this->s__persona, 'requisito' => $requisito, 'observaciones' => $observaciones);
		$id_fila_padre = $this->get_id_requisito(array('requisito' => $requisito));
		$this->set_cursor_requisitos($id_fila_padre);
		$id = $this->tabla('requisitos_digitales_draft_detalle')->nueva_fila($fila);
		$this->unset_cursor_requisitos();
		return $id;
	}

	function eliminar_requisito_digital_draft_detalle($requisito)
	{
		$fila = $this->tabla('requisitos_digitales_draft_detalle')->get_id_fila_condicion(array('requisito' => $requisito, 'persona' => $this->s__persona), false);
		if (!empty($fila)) {
			$this->tabla('requisitos_digitales_draft_detalle')->eliminar_fila(current($fila));
		}
	}

	function modificar_requisito_digital_draft_detalle($requisito, $observaciones='')
	{
		$id_fila = current($this->tabla('requisitos_digitales_draft_detalle')->get_id_fila_condicion(array('requisito' => $requisito, 'persona' => $this->s__persona), false));
		if (isset($id_fila)) {
			$this->tabla('requisitos_digitales_draft_detalle')->set_fila_columna_valor($id_fila, 'observaciones', $observaciones);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Procesamiento de datos
	//-----------------------------------------------------------------------------------

	function set_datos($datos, $tabla = null, $validar = true)
	{
		$this->validar_datos($datos);
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
		
		// Si las validaciones fueron exitosas, se distribuyen los datos
		foreach ($datos as $key => $dato) {
			if ($dato['presentado']) {
				if (!in_array($dato['requisito'], $this->s__req_ya_presentados)) {
					if (!in_array($dato['requisito'], $this->s__req_presentados_seleccionados)) {
						// Si no estaba agregado, se lo agrega como presentado (el req digital ya fue agregado).
						$this->agregar_requisito_presentado($dato['requisito'], $dato['fecha_presentacion'], $dato['fecha_vencimiento']);
					} else {
						$this->modificar_requisito_presentado($dato['requisito'], $dato['fecha_presentacion'], $dato['fecha_vencimiento']);
					}
				} else {
					$this->modificar_requisito_presentado($dato['requisito'], $dato['fecha_presentacion'], $dato['fecha_vencimiento']);
				}
			} else {
				if (in_array($dato['requisito'], $this->s__req_presentados_seleccionados)) {
					$this->eliminar_requisito_presentado($dato['requisito']);
				}
			}
		}
	}

	function validar_datos($datos)
	{
		foreach ($datos as $key => $dato) {
			if ($dato['presentado']) {
				if (!in_array($dato['requisito'], $this->s__req_ya_presentados)) {
					// Fecha de Presentación <= Fecha Actual
					if (!guarani_fecha::es_menor_igual_a_fecha_actual($dato['fecha_presentacion'])) {
						$this->set_error('800_control_fechas', array('de Presentación', 'mayor', 'actual para el requisito <strong>' . $dato['nombre'] . '</strong>'));
					}
					if (!empty($dato['fecha_vencimiento'])) {
						// Fecha de presentación < Fecha de Vencimiento
						if (guarani_fecha::es_menor_igual($dato['fecha_vencimiento'], $dato['fecha_presentacion'])) {
							$this->set_error('800_control_fechas', array('de Presentación', 'mayor o igual', 'de Vencimiento del requisito <strong>' . $dato['nombre'] . '</strong>'));
						}
					}
				}
			}
		}
	}

	function validar_requisitos_aspirantes($datos)
	{
		foreach ($datos as $key => $dato) {
			// Validaciones Excepción
			if (!empty($dato['fecha_excepcion'])) {
				// Fecha Excepcion <= Fecha Actual
				if (guarani_fecha::es_mayor($dato['fecha_excepcion'], guarani_fecha::get_hoy())) {
					$this->set_error('800_control_fechas', array('de Excepción', 'mayor', 'actual para la propuesta <strong>' . $dato['propuesta_nombre'] . '</strong>'));
				}

				// Controles en modo Advertencia
				toba::notificacion()->vaciar();

				//  * Fecha Excepción > Fecha Tope de Presentación
				$fecha_limite = guarani::co('co_requisitos')->get_fecha_limite_presentacion($dato['propuesta_aspira'], $dato['requisito']);
				if (guarani_fecha::es_mayor($dato['fecha_excepcion'], $fecha_limite)) {
					$formateo = new toba_formateo('html');
					$fecha_limite_formateada = $formateo->formato_fecha($fecha_limite);
					$this->set_notificacion('800_control_fechas', array('de Excepción', 'mayor', "Tope de Presentación ($fecha_limite_formateada) para la propuesta <strong>" . $dato['propuesta_nombre'] . '</strong>'));
				}

				//  * Fecha Excepción < Fecha de Inscripción 
				$fecha_inscripcion = guarani::co('co_inscripciones')->get_fecha_inscripcion($dato['propuesta_aspira']);
				if (guarani_fecha::es_menor($dato['fecha_excepcion'], $fecha_inscripcion)) {
					$formateo = new toba_formateo('html');
					$fecha_inscripcion_formateada = $formateo->formato_fecha($fecha_inscripcion);
					$this->set_notificacion('800_control_fechas', array('de Excepción', 'mayor', "de Inscripción ($fecha_inscripcion_formateada) para la propuesta <strong>" . $dato['propuesta_nombre'] . '</strong>'));
				}
			}

			// Validaciones Prórroga
			if (!empty($dato['fecha_prorroga'])) {
				// Fecha Prórroga >= Fecha Actual
				if (guarani_fecha::es_menor_a_fecha_actual($dato['fecha_prorroga'])) {
					$this->set_error('800_control_fechas', array('de Prórroga', 'menor', 'actual para la propuesta <strong>' . $dato['propuesta_nombre'] . '</strong>'));
				}
				// Fecha Prórroga > Fecha Tope de Presentación 
				$fecha_limite = guarani::co('co_requisitos_ingreso')->get_menor_fecha_tope_presentacion($dato['propuesta_aspira'], $dato['requisito']);
				if (!guarani_fecha::es_mayor($dato['fecha_prorroga'], $fecha_limite)) {
					$formateo = new toba_formateo('html');
					$fecha_limite_formateada = $formateo->formato_fecha($fecha_limite);
					$this->set_error('800_control_fechas', array('de Prórroga', 'menor o igual', "Tope de Presentación ($fecha_limite_formateada) para la propuesta <strong>" . $dato['propuesta_nombre'] . '</strong>'));
				}
			}
		}

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	function sincronizar()
	{
		try {

			if ($this->usa_repositorio_digital()) {
				$this->sincronizar_documentos_digitales();
				$this->eliminar_drafts_de_rdi();
			}

			/**
			 * @todo: analizar resultado final de procesamiento con RDI
			 */

			// Si usa RDI se sincronizan los req. digitales y su asociación con los presentados.
			if ($this->usa_repositorio_digital()) {
				$filas_eliminadas = $this->tabla('requisito_presentado')->get_id_filas_a_sincronizar(array('d'));
				$this->tabla('requisito_presentado')->sincronizar_filas($filas_eliminadas);
				$this->tabla('requisitos_digitales')->sincronizar();
				$this->tabla('requisitos_digitales_draft_detalle')->sincronizar();

				$ids_presentados = $this->tabla('requisito_presentado')->get_id_filas(false);
				foreach ($ids_presentados as $req) {
					$datos_req = $this->tabla('requisito_presentado')->get_fila($req);
					if (!isset($datos_req['requisito_digital'])) {
						$digital_asociado = $this->get_datos_req_digital($datos_req['requisito'], $this->s__persona);
						$this->asociar_req_digital_a_req_presentado($digital_asociado, $req);
					}
				}
			}
			$this->tabla('requisito_presentado')->sincronizar();
			$this->tabla('requisito_aspirante')->sincronizar();
			
			// Los datos-tabla abren y cierran transacciones constantemente, por eso se movió el abrir transacciones aquí
			toba::db()->abrir_transaccion();

			$this->sinconizar_excepciones_req_procesos();

			$this->verificar_cumplimiento_requisitos();

			$this->eliminar_drafts($this->s__req_aceptados_draft);
			$this->eliminar_drafts($this->s__req_rechazados_draft);

			toba::db()->cerrar_transaccion();
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			throw new guarani_error($e->get_mensaje());
		}
	}

	//---- Documentos digitales ------------------------------------------------

	function sincronizar_documentos_digitales()
	{
		if (!empty($this->s__datos_tabla_rdi)) {
			foreach ($this->s__datos_tabla_rdi as $requisito => $datos) {
				switch ($datos['accion']) {
					case static::temp_rdi_alta :
						$condiciones = array('requisito' => $requisito);
						// Tipo de requisito digital para invocar servicio
						$datos_requisito = current($this->get_datos_requisitos($condiciones));
						$serv = $datos_requisito['tipo_documento_digital'];
						// Archivo a enviar
						$archivo = $datos['archivo'];
						$datos['tipo_documento_digital'] = $serv;
						// Atributos RDI
						$datos_persona = toba::consulta_php('co_personas')->get_datos_persona($this->s__persona);
						$atributos = requisito_digital::get_atributos_documento_digital_requisito_documental($datos, $datos_persona, $archivo);
						// Alta de archivo
						$id_rdi = guarani_cliente_rdi::crear_documento($archivo, $serv, $atributos);
						// Se agrega el requisito digital a la tabla con el id de RDI asociado.
						$fila = array(
							'requisito' => $requisito,
							'persona' => $this->s__persona,
							'id_documento' => $id_rdi
						);
						$id_fila_padre = $this->get_id_requisito(array('requisito' => $requisito));
						$this->set_cursor_requisitos($id_fila_padre);
						$this->tabla('requisitos_digitales')->nueva_fila($fila);
						$this->unset_cursor_requisitos();

						// Se deja la marca de transacción exitosa para control posterior
						$this->s__datos_tabla_rdi[$requisito]['ok'] = true;
						break;
					case static::temp_rdi_modificacion :
						$condiciones = array('requisito' => $requisito);
						// Tipo de requisito digital para invocar servicio
						$datos_requisito = current($this->get_datos_requisitos($condiciones));
						$serv = $datos_requisito['tipo_documento_digital'];
						// ID de documento digital a modificar
						$req_dig_existente = current($this->get_datos_requisitos_digitales($condiciones));
						$id_rdi = $req_dig_existente['id_documento'];
						// Se modifica documento en repositorio
						guarani_cliente_rdi::modificar_contenido_documento($id_rdi, $serv, $datos['archivo']);

						// Se deja la marca de transacción exitosa para control posterior
						$this->s__datos_tabla_rdi[$requisito]['ok'] = true;
						break;
					case static::temp_rdi_baja :
						// No se elimina del repositorio (el recurso puede estar 
						// siendo utilizado por otro módulo, ej: SIU-Mapuche)
						// Se elimina el registro de requisitos digitales
						$condiciones = array(
							'requisito' => $requisito,
							'persona' => $this->s__persona
						);
						$fila = $this->tabla('requisitos_digitales')->get_id_fila_condicion($condiciones, false);
						if (!empty($fila)) {
							$this->tabla('requisitos_digitales')->eliminar_fila(current($fila));
						}

						$fila = $this->tabla('requisito_presentado')->get_id_fila_condicion($condiciones, false);
						$this->tabla('requisito_presentado')->set_fila_columna_valor(current($fila), 'requisito_digital', null);

						// Se deja la marca de transacción exitosa para control posterior
						$this->s__datos_tabla_rdi[$requisito]['ok'] = true;
						break;
				}
			}
		}
	}
	
	function eliminar_drafts_de_rdi()
	{
		foreach ($this->s__req_rechazados_draft as $requisito) {
			$condiciones = array('requisito' => $requisito);
			// Tipo de requisito digital para invocar servicio
			$datos_requisitos = $this->get_datos_requisitos($condiciones);
			$datos_requisito = $datos_requisitos[0];
			$serv = $datos_requisito['tipo_documento_digital'];
			// Datos del registro en draft
			$datos_req_draft = toba::consulta_php('co_requisitos')->get_draft_requisito_digital($requisito, $this->s__persona);
			guarani_cliente_rdi::eliminar_documento($datos_req_draft['id_documento'], $serv);		
		}
	}

	//--------------------------------------------------------------------------

	function sinconizar_excepciones_req_procesos()
	{
		if (!empty($this->s__req_exceptuar)) {
			foreach ($this->s__req_exceptuar as $datos_requisito) {
				$propuesta_aspira = toba::db()->quote($datos_requisito['propuesta_aspira']);
				$requisito = toba::db()->quote($datos_requisito['requisito']);
				$condicion = "propuesta_aspira = $propuesta_aspira AND requisito = $requisito";

				$parametros = array(
					'fecha_excepcion' => $datos_requisito['fecha_excepcion'],
					'motivo_excepcion' => $datos_requisito['motivo_excepcion'],
					'documento_resp' => $datos_requisito['documento_excepcion'],
					'observaciones' => null
				);

				guarani::act('act_requisitos_aspirante')->registrar_excepciones($condicion, $parametros);
			}
		}
	}

	/**
	 * Elimina de la tabla de drafts requisitos aceptados o rechazados
	 */
	function eliminar_drafts($requisitos)
	{
		if (!empty($requisitos)) {
			foreach ($requisitos as $requisito) {
				$datos_req = toba::consulta_php('co_requisitos')->get_draft_requisito_digital($requisito, $this->s__persona);
				guarani::act('act_requisitos_ingreso_nucleo')->eliminar_draft_requisito_digital($datos_req['requisito_digital']);
			}
		}
	}

	function verificar_cumplimiento_requisitos()
	{
		$propuestas_aspira = guarani::co('co_personas')->get_propuestas_aspira_control_requisitos($this->s__persona);
		foreach ($propuestas_aspira as $key => $dato) {
			preinscripcion::actualizar_estado_inscripcion($dato['propuesta_aspira']);
		}
	}

	//--------------------------------------------------------------------------
	//---- Manejo de requisitos de tipo proceso --------------------------------
	//--------------------------------------------------------------------------

	function get_datos_tipo_proceso()
	{
		if (!isset($this->requisitos_tipo_proceso)) {
			$this->requisitos_tipo_proceso = toba::consulta_php('co_requisitos_ingreso')->get_datos_requisitos_tipo_proceso($this->s__persona);
		}

		if (!empty($this->s__req_exceptuar)) {
			foreach ($this->requisitos_tipo_proceso as $key => $requisito) {
				if ($this->requisito_exceptuado($requisito)) {
					$this->requisitos_tipo_proceso[$key]['exceptuado_ahora'] = true;
					$this->requisitos_tipo_proceso[$key]['cumplido_descr'] = 'Excep.';
				}
			}
		}

		return $this->requisitos_tipo_proceso;
	}

	function procesar_excepcion_req_proceso($datos)
	{
		if (empty($this->s__req_exceptuar)) {
			$this->s__req_exceptuar = array();
		}
		$this->s__req_exceptuar[] = current($datos);
	}

	function requisito_exceptuado($requisito)
	{
		if (empty($this->s__req_exceptuar)) {
			return false;
		}
		foreach ($this->s__req_exceptuar as $requitiso_excep) {
			if (($requitiso_excep['requisito'] == $requisito['requisito']) && ($requitiso_excep['propuesta_aspira'] == $requisito['propuesta_aspira'])) {
				return true;
			}
		}
		return false;
	}

	function eliminar_excepcion_tipo_proceso($requisito)
	{
		foreach ($this->s__req_exceptuar as $key => $requitiso_excep) {
			if (($requitiso_excep['requisito'] == $requisito['requisito']) && ($requitiso_excep['propuesta_aspira'] == $requisito['propuesta_aspira'])) {
				unset($this->s__req_exceptuar[$key]);
				unset($this->requisitos_tipo_proceso);
				break;
			}
		}
	}

	function hay_requisitos_a_exceptuar()
	{
		return !empty($this->s__req_exceptuar);
	}

	//--------------------------------------------------------------------------
	//---- Manejo de documentos digitales
	//--------------------------------------------------------------------------

	function hay_doc_temp_cargado($requisito)
	{
		return (!empty($this->s__datos_tabla_rdi[$requisito]));
	}

	function cargar_documento($requisito, $archivo)
	{
		if (!isset($this->s__datos_tabla_rdi)) {
			$this->s__datos_tabla_rdi = array();
		}
		if (!isset($this->s__datos_tabla_rdi[$requisito])) {
			$this->s__datos_tabla_rdi[$requisito] = array();
		}

		$condiciones = array('requisito' => $requisito);
		$requisito_existente = !empty($this->get_datos_requisitos_digitales($condiciones));
		$accion = ($requisito_existente) ? static::temp_rdi_modificacion : static::temp_rdi_alta;

		$this->s__datos_tabla_rdi[$requisito]['accion'] = $accion;
		$this->s__datos_tabla_rdi[$requisito]['archivo'] = $archivo;

		if (isset($this->s__temp_docs_guarani[$requisito])) {
			unset($this->s__temp_docs_guarani[$requisito]);
		}
		
		if (!$this->tabla('requisito_presentado')->existe_fila_condicion(array('requisito' => $requisito), false)) {
			// Marco al requisito como "¿Presentado?" y completo con "Fecha Presentación" la de hoy
			$this->agregar_requisito_presentado($requisito, guarani_fecha::get_hoy());
		}
	}

	function eliminar_documento($requisito)
	{
		$condiciones = array('requisito' => $requisito);
		$requisito_existente = !empty($this->get_datos_requisitos_digitales($condiciones));

		if ($requisito_existente) {
			// El documento está en RDI y asociado a un requisito: marcar para baja
			$this->s__datos_tabla_rdi[$requisito] = array('accion' => static::temp_rdi_baja);
		} else {
			// El documento no está asociado a un requisito pero se tiene una carga 
			// sin sincronizar: se elimina de la estructura temporal
			if (!empty($this->s__datos_tabla_rdi[$requisito])) {
				unset($this->s__datos_tabla_rdi[$requisito]);
			}
		}
		
		if (!empty($this->s__temp_docs_guarani[$requisito])) {
			unset($this->s__temp_docs_guarani[$requisito]);
		}
	}

	/**
	 * Inicializa una estructura desde la cual obtener, para un requisito, la URL 
	 * de visualización y el path de descarga de un documento cargado en Guaraní
	 * 
	 * @param int $requisito
	 */
	function ini_doc_digital_guarani($requisito)
	{
		// Si fue marcado para eliminar, no se inicializa repositorio
		if ((isset($this->s__datos_tabla_rdi[$requisito]['accion'])) && ($this->s__datos_tabla_rdi[$requisito]['accion'] == static::temp_rdi_baja)) {
			return;
		}
		
		if (empty($this->s__temp_docs_guarani)) {
			$this->s__temp_docs_guarani = array();
		}
		
		// Si hay una carga temporal (sin sincronizar), se entrega ese documento
		if (isset($this->s__datos_tabla_rdi[$requisito]['archivo']['tmp_name'])) {
			$path = $this->s__datos_tabla_rdi[$requisito]['archivo']['tmp_name'];

			$nombre = toba::proyecto()->get_www_temp($this->s__datos_tabla_rdi[$requisito]['archivo']['filename']);
			$this->s__temp_docs_guarani[$requisito] = array(
				'temp' => $path,
				'url' => $nombre['url']
			);
		} else {
			// Si no hay carga temporal, se continúa buscando en el Repositorio sólo
			// si no fue descargado anteriormente
			if (empty($this->s__temp_docs_guarani[$requisito])) {

				// Si no hay carga temporal pero existe en repositorio, se hace una descarga
				// temporal desde RDI y se almacena el path en sesión
				$condiciones = array('requisito' => $requisito);
				$requisito_existente = $this->get_datos_requisitos_digitales($condiciones);
				if (!empty($requisito_existente[0]['id_documento'])) {
					$id_documento = $requisito_existente[0]['id_documento'];

					$datos_requisito = current($this->get_datos_requisitos($condiciones));
					$tipo_doc_digital = $datos_requisito['tipo_documento_digital'];
					$archivo = guarani_cliente_rdi::get_contenido_documento($id_documento, $tipo_doc_digital);
					$nombre = toba::proyecto()->get_www_temp(md5(time()));

					file_put_contents($nombre['path'], $archivo['contents']);

					if ($this->es_imagen($nombre['path'])) {
						$url = $nombre['url'];
					} else {
						$generico = toba::proyecto()->get_www("img/doc_cargado.png");
						$url = $generico['url'];
					}
					$this->s__temp_docs_guarani[$requisito] = array(
						'temp' => $nombre['path'],
						'url' => $url
					);
				}
			}
		}
	}

	function get_path_descarga_doc_digital($requisito)
	{
		if (empty($this->s__temp_docs_guarani[$requisito])) {
			$this->ini_doc_digital_guarani($requisito);
		}
		if (!empty($this->s__temp_docs_guarani[$requisito]['temp'])) {
			return $this->s__temp_docs_guarani[$requisito]['temp'];
		} else {
			// Si no hay documento digital para ese requisito, se retorna un string vacío
			return '';
		}
	}

	function get_url_preview_doc_digital($requisito)
	{
		if (empty($this->s__temp_docs_guarani[$requisito])) {
			$this->ini_doc_digital_guarani($requisito);
		}
		if (!empty($this->s__temp_docs_guarani[$requisito]['url'])) {
			return $this->s__temp_docs_guarani[$requisito]['url'];
		} else {
			// Si no hay documento digital para ese requisito, se retorna un string vacío
			return '';
		}
	}

	//---- Doc. digital en Draft -----------------------------------------------
	/**
	 * Da por aceptado y presentado un requisito cargado en Draft
	 * 
	 * PRECONDICIÓN: el requisito fue exhibido en previsualización o descargado. 
	 * Eso implica que fue inicializada la estrucutra $s__temp_doc_draft[$requisito],
	 * desde donde se obtendrá el ID de recurso RDI.
	 * 
	 * @param int $requisito
	 */
	function aceptar_requisito_draft($requisito)
	{
		$id_rdi = $this->s__temp_doc_draft[$requisito]['id_recurso_rdi'];
		$fecha_presentacion = $this->s__temp_doc_draft[$requisito]['fecha_carga'];
		
		$fila = array(
			'requisito' => $requisito,
			'persona' => $this->s__persona,
			'id_documento' => $id_rdi
		);

		$id_fila_padre = $this->get_id_requisito(array('requisito' => $requisito));
		$this->set_cursor_requisitos($id_fila_padre);
		$this->tabla('requisitos_digitales')->nueva_fila($fila);
		$this->unset_cursor_requisitos();

		if (!$this->tabla('requisito_presentado')->existe_fila_condicion(array('requisito' => $requisito), false)) {
			// Se agrega requisito como presentado con la fecha de carga del documento digital realizado por el alumno
			$this->agregar_requisito_presentado($requisito, $fecha_presentacion);
		}

		// Se eliminan las observaciones asociadas al draft.
		$this->eliminar_requisito_digital_draft_detalle($requisito);

		if (empty($this->s__req_aceptados_draft)) {
			$this->s__req_aceptados_draft = array();
		}
		$this->s__req_aceptados_draft[] = $requisito;
	}

	function rechazar_requisito_draft($requisito, $observaciones='')
	{
		if (empty($this->s__req_rechazados_draft)) {
			$this->s__req_rechazados_draft = array();
		}
		$this->s__req_rechazados_draft[] = $requisito;

		if (!$this->tabla('requisitos_digitales_draft_detalle')->existe_fila_condicion(array('requisito' => $requisito, 'persona' => $this->s__persona), false)) {
			$this->agregar_requisito_digital_draft_detalle($requisito, $observaciones);
		} else {
			$this->modificar_requisito_digital_draft_detalle($requisito, $observaciones);
		}
	}

	/**
	 * Revierte las acciones preparadas para ejecutar en Draft (aceptar / rechazar)
	 * 
	 * @param int $requisito
	 */
	function deshacer_acciones_draft($requisito)
	{
		// Aceptados
		foreach ($this->s__req_aceptados_draft as $key => $valor) {
			if ($valor == $requisito) {
				unset($this->s__req_aceptados_draft[$key]);
				break;
			}
		}
		// Rechazados
		foreach ($this->s__req_rechazados_draft as $key => $valor) {
			if ($valor == $requisito) {
				unset($this->s__req_rechazados_draft[$key]);
				break;
			}
		}
	}

	/**
	 * Determina si un requisito dado ha sido aceptado desde Draft en la sesión actual
	 * 
	 * @param int $requisito
	 * @return boolean
	 */
	function aceptado_draft($requisito)
	{
		if (empty($this->s__req_aceptados_draft)) {
			$this->s__req_aceptados_draft = array();
		}
		return in_array($requisito, $this->s__req_aceptados_draft);
	}

	/**
	 * Determina si un requisito dado ha sido rechazado en Draft en la sesión actual
	 * 
	 * @param int $requisito
	 * @return boolean
	 */
	function rechazado_draft($requisito)
	{
		if (empty($this->s__req_rechazados_draft)) {
			$this->s__req_rechazados_draft = array();
		}
		return in_array($requisito, $this->s__req_rechazados_draft);
	}

	function ini_doc_digital_draft($requisito)
	{
		if (empty($this->s__temp_doc_draft)) {
			$this->s__temp_doc_draft = array();
		}

		$condiciones = array('requisito' => $requisito);
		$datos_requisito = current($this->get_datos_requisitos($condiciones));
		$tipo_doc_digital = $datos_requisito['tipo_documento_digital'];

		$datos_draft  = toba::consulta_php('co_requisitos')->get_draft_requisito_digital($requisito, $this->s__persona);
		$id_documento = $datos_draft['id_documento'];
		$fecha_carga  = $datos_draft['fecha_carga'];

		$archivo = guarani_cliente_rdi::get_contenido_documento($id_documento, $tipo_doc_digital);
		$nombre = toba::proyecto()->get_www_temp(md5(time()));

		file_put_contents($nombre['path'], $archivo['contents']);
 
		if ($this->es_imagen($nombre['path'])) {
			$url = $nombre['url'];
		} else {
			$generico = toba::proyecto()->get_www("img/doc_cargado.png");
			$url = $generico['url'];
		}

		$this->s__temp_doc_draft[$requisito] = array(
			'id_recurso_rdi' => $id_documento,
			'temp' => $nombre['path'],
			'url' => $url,
			'fecha_carga' => $fecha_carga
		);
}

	function get_url_preview_doc_digital_draft($requisito)
	{
		if (empty($this->s__temp_doc_draft[$requisito])) {
			$this->ini_doc_digital_draft($requisito);
		}
		return $this->s__temp_doc_draft[$requisito]['url'];
	}

	function get_path_descarga_doc_digital_draft($requisito)
	{
		if (empty($this->s__temp_doc_draft[$requisito])) {
			$this->ini_doc_digital_draft($requisito);
		}
		return $this->s__temp_doc_draft[$requisito]['temp'];
	}

	//--------------------------------------------------------------------------
	/**
	 * Determina si un archivo en un path consignado es una imagen
	 * 
	 * @param string $path
	 * @return boolean
	 */
	function es_imagen($path)
	{
		return (@is_array(getimagesize($path)));
	}

	/**
	 * Determina si un documento digital asociado a un requisito se encuentra cargado
	 * (ya sea en el Repositorio o de manera temporal)
	 * 
	 * @param int $requisito
	 * @return boolean
	 */
	function doc_digital_cargado($requisito)
	{
		$estado = $this->get_estado_requisito_digital($requisito);
		return in_array($estado, [static::estado_rdi_ACEPTADO]);
	}

	/**
	 * Obtiene un código de estado de la situación de un documento digital en la 
	 * sesión corriente con la operación
	 * 
	 *  + 1: No cargado
	 *  + 2: Cargado y sincronizado en Repositorio (sin cambios en esta sesión)
	 *  + 3: Cargado, listo para dar de alta en Repositorio (alta en esta sesión)
	 *  + 4: Cargado, listo para reemplazar en Repositorio (modificado en esta sesión)
	 *  + 5: Marcado para eliminar en Repositorio
	 * 
	 * @param int $requisito
	 * @return int
	 */
	function get_estado_requisito_digital($requisito)
	{
		$condiciones = ['requisito' => $requisito];
		$datos_requisito = $this->get_datos_requisitos_digitales($condiciones);
		$requisito_existente = !empty($datos_requisito);
		$requisito_existente_datos_tabla = !empty($this->s__datos_tabla_rdi[$requisito]);

		// Es Doc. Draft
		if ($this->hay_doc_en_draft($requisito)) {
			
			// Es Doc. Draft aceptado
			if ($this->aceptado_draft($requisito)) {
				$estado = static::estado_rdi_ACEPTADO;
			// Es Doc. Draft rechazado
			} elseif ($this->rechazado_draft($requisito)) {
				// Reviso si antes de guardar se cargo un nuevo requisito
				if ($requisito_existente_datos_tabla) {
					$estado = static::estado_rdi_ACEPTADO;
				} else {
					$estado = static::estado_rdi_RECHAZADO;
				}
			// Es Doc. Draft pendiente de validación
			} else {
				$estado = static::estado_rdi_PEND_VAL;
			}
			
		// Si es Doc. Digital
		} else {
			
			$estado = static::estado_rdi_NO_CARGADO;
			
			// Existe en la base de datos como requisito digital
			if ($requisito_existente) {
				$estado = static::estado_rdi_ACEPTADO;
			// No existe en la base pero si en el datos tabla
			} elseif($requisito_existente_datos_tabla) {
				$estado = static::estado_rdi_ACEPTADO;
			} else {
				
				$datos_requisitos_aspirante = $this->get_datos_requisitos_aspirante($requisito);
				// Recorro y veo si tiene requisitos rechazados en la tabla sga_requisitos_aspirante
				foreach ($datos_requisitos_aspirante as $requisito_aspirante) {
					if (!empty($requisito_aspirante['observaciones']) && $requisito_aspirante['exceptuado'] == comunes::no) {
						$estado = static::estado_rdi_RECHAZADO;
						break;
					}
				}
				
			}
		}
		
		return $estado;
	}
	
	function get_descr_estado_rdi($requisito)
	{
		$estado = $this->get_estado_requisito_digital($requisito);
		return $this->descr_estados_rdi[$estado];
	}

	function hay_doc_en_draft($requisito)
	{
		return toba::consulta_php('co_requisitos')->existe_draft_requisito_digital($requisito, $this->s__persona);
	}

}