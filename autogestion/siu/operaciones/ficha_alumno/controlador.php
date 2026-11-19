<?php
namespace siu\operaciones\ficha_alumno;

use siu\guarani;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use siu\modelo\datos\catalogo;
use siu\operaciones\_comun\operaciones\reporte\controlador_con_filtro;

class controlador extends controlador_con_filtro
{
	// comision / mesa / acta
	protected $tipo_seleccion;
	
	protected $seleccion_hash;
	protected $seleccion;
	
	protected $persona_hash;
	protected $persona;
	
	// Registro de bitácora a operar
	protected $registro_bitacora_hash;
	protected $registro_bitacora;

	/**
	 * @return \siu\modelo\transacciones\ficha_alumno
	 */
	function modelo()
	{
		return guarani::ficha_alumno();
	}
	
	function get_clase_vista()
	{
		if ($this->accion == 'index') {
			$vista = 'vista_home';
		} elseif ($this->accion == 'alumnos') {
			$vista ='vista_alumnos';
		} else {
			$vista ='vista_ficha';
		}
		return $vista;
	}
	
	function tiene_derechos_bitacora()
	{
		$tiene_derecho = false;
		
		if (empty($this->tipo_seleccion)) {
			// Selección directa, sin preselección -> se determina procedencia
			$origen = $this->modelo()->info__origen_alumno($this->persona);
			if ($origen['comision']) {
				$tiene_derecho = kernel::persona()->tiene_derecho_entidad('comision_bitacora_administrar', 'comision', $origen['comision']);
			} 
			if ((!$tiene_derecho) && ($origen['llamado_mesa'])) {
				// Si no tiene derecho en la comisión, pero el alumno también está en 
				// una mesa del docente, se chequea si tiene derechos sobre la misma
				$tiene_derecho = kernel::persona()->tiene_derecho_entidad('mesa_bitacora_administrar', 'llamado_mesa', $origen['llamado_mesa']);
			}
			if ((!$tiene_derecho) && ($origen['acta'])) {
				// Si no tiene derecho en comisiones ni mesas, pero el alumno también está en 
				// una mesa del docente, se chequea si tiene derechos sobre la misma
				$llamado_mesa = $this->modelo()->info__llamado_mesa_de_acta($origen['acta']);
				$tiene_derecho = kernel::persona()->tiene_derecho_entidad('mesa_bitacora_administrar', 'llamado_mesa', $llamado_mesa);
			}
		} elseif ($this->tipo_seleccion == 'comision') {
			// Preselección: Comisión
			$tiene_derecho = kernel::persona()->tiene_derecho_entidad('comision_bitacora_administrar', 'comision', $this->seleccion);
		} elseif ($this->tipo_seleccion == 'mesa') {
			// Preselección: Mesa
			$tiene_derecho = kernel::persona()->tiene_derecho_entidad('mesa_bitacora_administrar', 'llamado_mesa', $this->seleccion);
		} elseif ($this->tipo_seleccion == 'acta') {
			// Preselección: Acta FC
			$llamado_mesa = $this->modelo()->info__llamado_mesa_de_acta($this->seleccion);
			$tiene_derecho = kernel::persona()->tiene_derecho_entidad('mesa_bitacora_administrar', 'llamado_mesa', $llamado_mesa);
		} 
		
		return $tiene_derecho;
	}
		
	//--------------------------------------------------------------------------
	//---- Filtro --------------------------------------------------------------
	//--------------------------------------------------------------------------
	
	protected function instanciar_form_builder()
	{
		return kernel::localizador()->instanciar('operaciones\ficha_alumno\home\builder_filtro_home');
	}
	
	public function accion__buscar_actividad()
	{
		$json = array();
		$actividades = catalogo::consultar('unidad_academica', 'actividades', array(
			'term' => $this->getAutocompleteTerm(),
			'plan_version' => null
		));
		$json[] = array('id' => '', 'label' => 'Todas');
		foreach ($actividades as $actividad) {
			$json[] = array(
				'id' => $actividad['elemento'],
				'label' => $actividad['nombre'] . ' (' . $actividad['codigo'] . ')'
			);
		}
		$this->render_raw_json($json);
	}
	
	/**
	 * Autocompletado de alumno 
	 */
	function accion__auto_alumno()
	{
		$term = $this->getAutocompleteTerm();
		
		if (empty($term)) {
			$this->render_raw_json(array());
			return;
		}
		
		$parametros = array(
			'term' => $term,
			'comisiones_activas' => true, // fecha de inactivación comisión >= fecha actual
			'turnos_examen_no_finalizados' => true, // fecha fin de turno de examen >= fecha actual
		);
		$datos = catalogo::consultar('docente', 'buscar_alumno', $parametros);
		
		kernel::sesion()->set('__lista_personas__', $datos);
		
		$data = array();
		foreach ($datos as $alumno) {
			$data[] = array(
				'id' => kernel::vinculador()->crear('ficha_alumno', 'ficha', array('p' => $alumno[catalogo::id])),
				'label' => $alumno['persona_descr']
			);
		}
		$this->render_raw_json($data);
	}
	
	function get_filtros()
	{
		$datos = isset($this->datos_filtro) ? $this->datos_filtro : array();
		return $datos;
	}
	
	//--------------------------------------------------------------------------
	//---- Acciones ------------------------------------------------------------
	//--------------------------------------------------------------------------
	
	function load_params()
	{
		// Tipo de seleccion (comision / mesa / acta)
		$this->tipo_seleccion = $this->validate_param('t', 'get', validador::TIPO_TEXTO); 
		
		// Selección
		$this->seleccion_hash = $this->validate_param('s', 'get', validador::TIPO_TEXTO);
		switch($this->tipo_seleccion) {
			case 'comision' :
				$this->seleccion = $this->decodificar_comision($this->seleccion_hash);
				break;
			case 'mesa' :
				$this->seleccion = $this->decodificar_mesa($this->seleccion_hash);
				break;
			case 'acta' :
				$this->seleccion = $this->decodificar_acta($this->seleccion_hash);
				break;
		}
		
		// Persona
		$this->persona_hash = $this->validate_param('p', 'get', validador::TIPO_TEXTO);
		if (!empty($this->persona_hash)) {
			$this->persona = $this->decodificar_persona($this->persona_hash);
		}
		
		// Registro de bitácora a operar
		$this->registro_bitacora_hash = $this->validate_param('r', 'get', validador::TIPO_TEXTO);
		if (!empty($this->registro_bitacora_hash)) {
			$this->registro_bitacora = $this->decodificar_registro_bitacora($this->registro_bitacora_hash);
		}
	}
	
	function accion__alumnos()
	{
		$this->load_params();
	}
	
	function accion__ficha()
	{
		$this->load_params();
		
		// Reporte
		$formulario = $this->validate_param('i', 'get', validador::TIPO_TEXTO, array('default' => 'datos_personales')); 
		
		if (($formulario == 'bitacora') && (! $this->tiene_derechos_bitacora())) {
			return;
		}
		
		$pagelet = $this->vista()->pagelet($formulario);
		$this->vista()->set_rep_activo($pagelet);
		
		kernel::renderer()->add($pagelet);
	}
	
	function accion__bitacora_editar()
	{
		$this->load_params();
		
		if (! $this->tiene_derechos_bitacora()) {
			return;
		}
		
		// Reporte
		$formulario = $this->validate_param('i', 'get', validador::TIPO_TEXTO, array('default' => 'bitacora')); 
		$pagelet = $this->vista()->pagelet($formulario);
		$pagelet->set_pantalla(pagelet_bitacora::ESTADO_EDICION);
		$this->vista()->set_rep_activo($pagelet);
		
		kernel::renderer()->add($pagelet);
	}
	
	function accion__bitacora_eliminar()
	{
		$this->load_params();
		
		if (! $this->tiene_derechos_bitacora()) {
			return;
		}
		
		$this->modelo()->evt__eliminar_bitacora($this->registro_bitacora);
		
		$formulario = $this->validate_param('i', 'get', validador::TIPO_TEXTO, array('default' => 'bitacora')); 
		$pagelet = $this->vista()->pagelet($formulario);
		$pagelet->set_pantalla(pagelet_bitacora::ESTADO_LISTADO);
		$this->vista()->set_rep_activo($pagelet);
		
		kernel::renderer()->add($pagelet);
	}
	
	function accion__bitacora_guardar()
	{
		$this->load_params();
		
		if (! kernel::request()->isPost()) {
			return;
		}
		
		if (! $this->tiene_derechos_bitacora()) {
			return;
		}
		
		// Reporte
		$formulario = $this->validate_param('i', 'get', validador::TIPO_TEXTO, array('default' => 'bitacora')); 
		$pagelet = $this->vista()->pagelet($formulario);
		$pagelet->set_pantalla(pagelet_bitacora::ESTADO_EDICION);
		$this->vista()->set_rep_activo($pagelet);
		
		$form = $pagelet->get_form();
		if ($form->procesar()) {
			$datos = $form->get_datos();
			$datos['persona'] = $this->persona;
			$this->modelo()->evt__guardar_bitacora($datos);
			$pagelet->set_estado_info(pagelet_bitacora::FORM_GUARDADO);
			$pagelet->set_pantalla(pagelet_bitacora::ESTADO_LISTADO);
			$this->redirigir();
		} else {
			$pagelet->set_estado_info(pagelet_bitacora::FORM_ERROR_VALIDACION);
			$pagelet->set_pantalla(pagelet_bitacora::ESTADO_EDICION);
		}
		
		kernel::renderer()->add($pagelet);
	}
	
	function get_registro_bitacora()
	{
		if (!empty($this->registro_bitacora_hash)) {
			$datos = $this->get_registros_bitacora();
			return $datos[$this->registro_bitacora_hash];
		} else {
			return null;
		}
	}
	
	function puede_editar_bitacora()
	{
		$datos = $this->get_registro_bitacora();
		if (!empty($datos)) {
			return $datos['usuario_alta'] == kernel::persona()->get_id();
		} else {
			// Es un alta, puede editar
			return true;
		}
	}
	
	function redirigir()
	{
		$accion = 'ficha';
		$cont = 0;
		foreach ($_GET as $param => $valor) {
			if (($param != '__o') && ($param != '__a')) {
				if ($cont == 0) {
					$accion .= '?';
				} else {
					$accion .= '&';
				}
				$accion .= $param . '=' . $valor;
				$cont++;
			}
		}
		$this->nuevo_request('ficha_alumno', $accion);
	}
	
	//--------------------------------------------------------------------------
	//---- Construcción de URLs ------------------------------------------------
	//--------------------------------------------------------------------------
	
	function get_url_item_menu($item, $accion='ficha', $params_extra=array())
	{
		$operacion = kernel::ruteador()->get_id_operacion();
		$params = array(
			'p' => $this->persona_hash,
			'i' => $item
		);
		if (!empty($this->tipo_seleccion)) {
			$params['t'] = $this->tipo_seleccion;
		}
		if (!empty($this->seleccion_hash)) {
			$params['s'] = $this->seleccion_hash;
		}
		if (!empty($params_extra)) {
			$params = array_merge($params, $params_extra);
		}
		return kernel::vinculador()->crear($operacion, $accion, $params);
	}
	
	function get_accion_volver_ficha()
	{
		$operacion = kernel::ruteador()->get_id_operacion();
		$params = array();
		if (empty($this->tipo_seleccion)) {
			$accion = 'home';
		} else {
			$accion = 'alumnos';
			$params['t'] = $this->tipo_seleccion;
			$params['s'] = $this->seleccion_hash;
		}
		return kernel::vinculador()->crear($operacion, $accion, $params);
	}
	
	//--------------------------------------------------------------------------
	//---- Listados ------------------------------------------------------------
	//--------------------------------------------------------------------------
	
	function get_listado_alumnos()
	{
		if ($this->tipo_seleccion == 'comision') {
			$listado = $this->get_alum_inscriptos_comision();
		} elseif ($this->tipo_seleccion == 'mesa') {
			$listado = $this->get_alum_inscriptos_mesa();
		} elseif ($this->tipo_seleccion == 'acta') {
			$listado = $this->get_alum_inscriptos_acta_fc();
		}
		return $listado;
	}
	
	//---- Comisiones ----------------------------------------------------------
	
	function get_lista_comisiones() 
	{
		$filtros = $this->get_filtros();
		
		$parametros = array(
			'comisiones_activas' => true, // fecha de inactivación >= fecha actual
			'docente' => kernel::persona()->get_id_docente(),
			'periodo_lectivo' => (!empty($filtros['periodo_lectivo'])) ? array($filtros['periodo_lectivo']) : null, // $this->modelo()->get_ids_periodos_lectivos_vigentes(),
			'actividad' => (!empty($filtros['actividad'])) ? $filtros['actividad'] : null,
			'ubicacion' => (!empty($filtros['ubicacion'])) ? $filtros['ubicacion'] : null,
		);
		
		return $this->get_comisiones($parametros);
	}
	
	private function get_comisiones($parametros)
	{
		$comisiones = $this->modelo()->info__comisiones_docente($parametros);
		$columnas = array('comision_nombre', 'subcomision_nombre', 'ubicacion_nombre', 'responsabilidad_nombre', 'turno_nombre', 'cant_inscriptos_comision', 'comision_id', 'comision');
		$datos = array();
		foreach ($comisiones as $fila) {
			$operacion = kernel::ruteador()->get_id_operacion();
			$link = kernel::vinculador()->crear($operacion, 'alumnos', array(
				't' => 'comision', 
				's' => $fila['comision_id'])
			);
			$periodo_key = $fila['periodo_lectivo_nombre_full'];
			$actividad_key = $fila['actividad_orden'];
			foreach ($columnas as $campo)
				$nuevo[$campo] = isset($fila[$campo]) ? $fila[$campo] : null;
			$nuevo['cant_inscriptos'] = $fila['es_subcomision'] ? $fila['cant_inscriptos_subcomision'] : $fila['cant_inscriptos_comision'];
			$nuevo['link'] = $link;
			$datos[$periodo_key][$actividad_key][] = $nuevo;
		}
		
		return $datos;
	}
	
	protected function get_alum_inscriptos_comision()
	{
		$datos = $this->modelo()->info__alum_inscriptos_comision($this->seleccion);
		
		foreach ($datos as $key => $dato) {
			$id_persona = catalogo::generar_id($dato['persona']);
			$datos[$key][catalogo::id] = $id_persona;
			$operacion = kernel::ruteador()->get_id_operacion();
			$link = kernel::vinculador()->crear($operacion, 'ficha', array(
				'p' => $id_persona, 
				't' => $this->tipo_seleccion, 
				's' => $this->seleccion_hash)
			);
			$datos[$key]['link'] = $link;
		}
		
		kernel::sesion()->set('__lista_personas__', $datos);
		
		return $datos;
	}
	
	//---- Mesas dentro de calendario ------------------------------------------
	
	function get_lista_mesas() 
	{
		$filtros = $this->get_filtros();
		$parametros = array(
			'docente' => kernel::persona()->get_id_docente(),
			'turno' => (!empty($filtros['turno'])) ? $filtros['turno'] : null, 
			'actividad' => (!empty($filtros['actividad'])) ? $filtros['actividad'] : null,
			'ubicacion' => (!empty($filtros['ubicacion'])) ? $filtros['ubicacion'] : null,
			'fuera_calendario' => false,
			'turnos_examen_no_finalizados' => true, // fecha fin de turno de examen >= fecha actual
		);
		$mesas = $this->modelo()->info__mesas_docente($parametros);
		
		$datos = array();
		
		foreach ($mesas as $key => $mesa) {
			$operacion = kernel::ruteador()->get_id_operacion();
			$link = kernel::vinculador()->crear($operacion, 'alumnos', array(
				't' => 'mesa', 
				's' => $key,
			));
			$actividad_key = $mesa['actividad_descr'];
            $mesa['link'] = $link;
			
			$inscriptos = $this->modelo()->info__alum_inscriptos_examen($mesa['llamado_mesa']);
			$mesa['cant_inscriptos'] = \guarani_arreglos::count($inscriptos);
			
            $datos[$actividad_key][$key] = $mesa;
		}
		
		return $datos;
	}
	
	protected function get_alum_inscriptos_mesa()
	{
		$datos = $this->modelo()->info__alum_inscriptos_examen($this->seleccion);
		
		foreach ($datos as $key => $dato) {
			$id_persona = catalogo::generar_id($dato['persona']);
			$datos[$key][catalogo::id] = $id_persona;
			$operacion = kernel::ruteador()->get_id_operacion();
			$link = kernel::vinculador()->crear($operacion, 'ficha', array(
				'p' => $id_persona, 
				't' => $this->tipo_seleccion, 
				's' => $this->seleccion_hash)
			);
			$datos[$key]['link'] = $link;
			$datos[$key]['apellido_nombres'] = $dato['nombre_alumno'];
		}
		
		kernel::sesion()->set('__lista_personas__', $datos);
		
		return $datos;
	}
	
	//---- Actas fuera de calendario ------------------------------------------
	
	function get_lista_actas_fc()
	{
		$filtros = $this->get_filtros();
		$parametros = array(
			'docente' => kernel::persona()->get_id_docente(),
			'actividad' => (!empty($filtros['actividad'])) ? $filtros['actividad'] : null,
			'ubicacion' => (!empty($filtros['ubicacion'])) ? $filtros['ubicacion'] : null,
			'id_acta' => null,
			'turnos_examen_no_finalizados' => true, // fecha fin de turno de examen >= fecha actual
		);
		$actas = $this->modelo()->info__actas_docente_fc($parametros);
		
		$datos = array();
		
		foreach ($actas as $key => $acta) {
			$operacion = kernel::ruteador()->get_id_operacion();
			$link = kernel::vinculador()->crear($operacion, 'alumnos', array(
				't' => 'acta', 
				's' => $key,
			));
            $acta['link'] = $link;
			
			$inscriptos = $this->modelo()->info__alum_inscriptos_acta_fc($acta['id_acta']);
			$acta['cant_inscriptos'] = \guarani_arreglos::count($inscriptos);
			
            $datos[$key] = $acta;
		}
		
		return $datos;
	}
	
	protected function get_alum_inscriptos_acta_fc()
	{
		$datos = $this->modelo()->info__alum_inscriptos_acta_fc($this->seleccion);
		
		foreach ($datos as $key => $dato) {
			$id_persona = catalogo::generar_id($dato['persona']);
			$datos[$key][catalogo::id] = $id_persona;
			$operacion = kernel::ruteador()->get_id_operacion();
			$link = kernel::vinculador()->crear($operacion, 'ficha', array(
				'p' => $id_persona, 
				't' => $this->tipo_seleccion, 
				's' => $this->seleccion_hash)
			);
			$datos[$key]['link'] = $link;
		}
		
		kernel::sesion()->set('__lista_personas__', $datos);
		
		return $datos;
	}

	//--------------------------------------------------------------------------
	//---- Encabezados ---------------------------------------------------------
	//--------------------------------------------------------------------------
	
	function encabezado_persona()
	{
		return $this->modelo()->info__datos_cabecera_persona($this->persona);
	}
	
	function encabezado_comision()
	{
		if ($this->tipo_seleccion == 'comision') {
			return $this->modelo()->info__cabecera_comision($this->seleccion);
		}
		return null;
	}
	
	function encabezado_mesa()
	{
		if ($this->tipo_seleccion == 'mesa') {
			return $this->modelo()->info__cabecera_mesa($this->seleccion);
		}
		return null;
	}
	
	function encabezado_acta_fc()
	{
		if ($this->tipo_seleccion == 'acta') {
			return $this->modelo()->info__cabecera_acta_fc($this->seleccion);
		}
		return null;
	}

	//--------------------------------------------------------------------------
	//---- Reportes ------------------------------------------------------------
	//--------------------------------------------------------------------------
	
	function get_rep_datos_personales()
	{
		return $this->modelo()->info__rep_datos_personales($this->persona);
	}
	
	function get_rep_datos_personales_propuestas()
	{
		return $this->modelo()->info__rep_datos_personales_propuestas($this->persona);
	}
	
	function get_registros_bitacora()
	{
		return $this->modelo()->info__registros_bitacora($this->persona);
	}
	
	//--------------------------------------------------------------------------
	//---- Utilidades ----------------------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Retorna el ID de comisión a partir del hash
	 */
	protected function decodificar_comision($hash)
	{
		$datos = $this->get_lista_comisiones();
		foreach ($datos as $periodos) {
			foreach ($periodos as $comisiones) {
				foreach ($comisiones as $comision)
				if ($comision['comision_id'] == $hash) {
					return $comision['comision'];
				}
			}
		}
		return null;
	}
	
	/**
	 * Retorna el ID de mesa a partir del hash
	 */
	protected function decodificar_mesa($hash)
	{
		$datos = $this->get_lista_mesas();
		foreach ($datos as $actividades) {
			foreach ($actividades as $key => $mesa) {
				if ($key == $hash) {
					return $mesa['llamado_mesa'];
				}
			}
		}
		return null;
	}
	
	/**
	 * Retorna el ID de acta a partir del hash
	 */
	protected function decodificar_acta($hash)
	{
		$datos = $this->get_lista_actas_fc();
		foreach ($datos as $key => $acta) {
			if ($key == $hash) {
				return $acta['id_acta'];
			}
		}
		return null;
	}
	
	/**
	 * Retorna el ID de registro de bitácora a operar a partir del hash
	 */
	protected function decodificar_registro_bitacora($hash)
	{
		$datos = $this->get_registros_bitacora();
		return $datos[$hash]['bitacora'];
	}
	
	/**
	 * Retorna el ID de persona a partir del hash
	 */
	protected function decodificar_persona($hash)
	{
		// Se obtienen el listado de personas de sesión (independientemente del origen del listado)
		$datos = kernel::sesion()->get('__lista_personas__');
		foreach ($datos as $dato) {
			if ($dato[catalogo::id] == $hash) {
				return $dato['persona'];
			}
		}
		return null;
	}
}
?>