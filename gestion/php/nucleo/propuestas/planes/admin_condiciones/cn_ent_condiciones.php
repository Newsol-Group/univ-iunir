<?php 
/**
 * Representa las Condiciones sobre un elemento del plan
 *
 *
 * @package Propuestas
 * @subpackage Plan
 */
class cn_ent_condiciones extends cn_entidad
{
	protected $s__plan_version;
	protected $s__tipo_plan;
	protected $s__administra_cursos;
	protected $s__estado_version;
	protected $s__propuesta_tipo;
	protected $s__plan_version_editable;
	protected $s__entidad;
	protected $grupo_orden =0;
	protected $requisito_orden=0;
	protected $s__guardado = true;

	function cargar($clave)
	{
		$datos_plan = toba::consulta_php('co_planes')->get_tipo_y_estado_plan_de_plan_version($this->get_id_plan_version());
		$this->s__tipo_plan = $datos_plan['tipo_plan'];
		$this->s__administra_cursos = $datos_plan['administra_cursos'];
		$this->s__estado_version = $datos_plan['estado'];
		$this->s__propuesta_tipo = $datos_plan['propuesta_tipo'];
		parent::cargar($clave);
	}

	function resetear()
    {
		toba::logger()->info("################ Resetear condiciones ELEMENTO ##################");
		parent::resetear();
		$this->grupo_orden =0;
		$this->requisito_orden=0;
		unset($this->s__plan_version);
		unset($this->s__tipo_plan);
		unset($this->s__administra_cursos);
		unset($this->s__estado_version);
		unset($this->s__propuesta_tipo);
		unset($this->s__plan_version_editable);
		unset($this->s__entidad);
		$this->s__guardado = true;
    } 


	function sincronizar()
	{
		parent::sincronizar();
		$this->s__guardado = true;
	}
	
	
	
	//---------------------------------------------------------------------------
	// ------------ API lectura --------------------------------------------
	//---------------------------------------------------------------------------

	function get_id_plan_version()
	{
		return $this->s__plan_version;	
	}
	
	function es_plan_version_editable()
	{	
		return $this->s__plan_version_editable;	
	}

	function es_plan_personalizado()
	{
		return ($this->s__tipo_plan == planes::tipo_personalizado || $this->s__tipo_plan == planes::tipo_convenio);
	}
	
	function es_plan_para_administrar_cursos()
	{
		return ($this->s__tipo_plan == planes::tipo_estructurado && $this->s__administra_cursos == comunes::si);
	}

	function es_propuesta_vocacional()
	{
		return ($this->s__propuesta_tipo == propuesta::tipo_vocacional);
	}

	function es_version_activa()
	{
		return ($this->s__estado_version == planes::estado_activo || $this->s__estado_version == planes::estado_vigente);
	}
	
	function get_arbol_condiciones()
	{
	    $arbol = Array(); 
        $condiciones = $this->get_condiciones();
        foreach ($condiciones as $condicion){
            $grupos = $this->get_grupos($condicion['condicion_tipo']);
            $arbol[$condicion['condicion_tipo']]=$condicion;
            foreach ($grupos as $grupo) {
                $requisitos = $this->get_requisitos($grupo['indice']);
                $arbol[$condicion['condicion_tipo']]['grupos'][$grupo['orden']]= $grupo;
                foreach ($requisitos as $requisito) {
                    $arbol[$condicion['condicion_tipo']]['grupos'][$grupo['orden']]['requisitos'][$requisito['orden']]= $requisito;
                }
            }
        }
	    return $arbol;
	}

	function get_condiciones()
	{
		toba::logger()->info("==== Cargo el arbol de condiciones");		
		$condiciones = array();
		$filas = $this->tabla('condiciones')->get_filas(null, false, false);
		foreach($filas as $f) {
			$datos['indice']=$f[apex_datos_clave_fila];
			$datos['condicion_tipo']=$f['condicion_tipo'];
			$datos['nombre']=$f['nombre'];
			$condiciones[]= $datos;
			
		}
		return $condiciones;
	}
	
	function get_grupos($condicion)
	{
		if (!$this->existe_condicion($condicion)){
		    throw new guarani_error("GET GRUPOS: No existe la condición {$condicion} seleccionada");
		}
		$grupos = array();
		$id_condicion = $this->get_id_interno_condicion($condicion);
		$this->tabla('condiciones')->set_cursor($id_condicion);
		$filas = $this->tabla('grupos')->get_filas();
		if (!empty($filas)){
            foreach($filas as $f) {
                $datos['orden'] = $f['orden'];
                $datos['indice'] = $f[apex_datos_clave_fila]; 
                $grupos[] = $datos;
                $orden[] = $f['orden'];
            }
            array_multisort($orden,$grupos);
            return $grupos;
		}else
            return $filas;
	}
	
	function get_requisitos($id_grupo)
	{
		try{		
			$this->tabla('grupos')->set_cursor($id_grupo);
		} catch (guarani_error $e) {
			throw new guarani_error("get requisitos: el grupo no existe ");
		}
		$requisitos = array();
		$filas = $this->tabla('requisitos')->get_filas();
		if (!empty($filas)){
            foreach($filas as $f) {
            	$datos = array();
            	if (isset($f['operador_not'])) $datos['operador_not']= $f['operador_not'];
            	if (isset($f['requisito'])) $datos['requisito'] = $f['requisito'];
            	if (isset($f['entidad'])) $datos['entidad'] = $f['entidad'];
				if (isset($f['tipo'])) $datos['tipo'] = $f['tipo'];
            	if (isset($f['origen'])) $datos['origen'] = $f['origen'];
            	if (isset($f['estado'])) $datos['estado'] = $f['estado'];
            	if (isset($f['regla'])) $datos['regla'] = $f['regla'];
            	if (isset($f['parametros'])) $datos['parametros'] = $f['parametros'];
            	$datos['orden'] = $f['orden'];
            	$datos['indice'] = $f[apex_datos_clave_fila];
            	$requisitos[] = $datos;
            	$orden[]= $f['orden'];
            }
            array_multisort($orden,$requisitos);
		}
        return $requisitos;
	}	
	
	function get_requisito($id_requisito)
	{
		try{		
			$this->tabla('requisitos')->set_cursor($id_requisito);
		} catch (guarani_error $e) {
				throw new guarani_error("get requisito: el requisito no existe ");
		}
		return $this->tabla('requisitos')->get_fila($id_requisito);
	}
	

	//---------------------------------------------------------------------------
	// ------------ API modificacion --------------------------------------------
	//---------------------------------------------------------------------------
		
	function agregar_condicion($condicion)
	{
		if (!$this->s__plan_version_editable) {
			throw new guarani_error("AGREGAR CONDICION: El plan no puede editarse");
		}
		if ($this->modo_correlativas() && $this->es_plan_personalizado() && $this->es_version_activa()) {
			$this->control_cambio_correlativas_plan_personalizado($condicion);
		}
		toba::logger()->info("==== INICIO Agregar CONDICION [$condicion]");
		//-- Controlo que la condicion no exista
		$filtro['condicion_tipo'] = $condicion;
		$id_filas = $this->tabla('condiciones')->get_id_fila_condicion($filtro);
		if(guarani_arreglos::count($id_filas) > 0) {
			throw new guarani_error("AGREGAR CONDICIÓN: El elemento ya posee una restricción para la condición {$condicion} seleccionada");
		}
		//-- Agrego la condicion
		$datos['entidad'] = $this->s__entidad;
		$datos['plan_version'] = $this->s__plan_version;
		$datos['condicion_tipo'] = $condicion;
		$condicion = $this->tabla('condiciones')->nueva_fila($datos);
		$this->s__guardado = false;
        toba::logger()->info("====  FIN Agregar CONDICIÓN [cursor: $condicion] *********************");
	}
	
	function eliminar_condicion($condicion)
	{
		if (!$this->existe_condicion($condicion)){
			throw new guarani_error("ELIMINAR CONDICIÓN: No existe la condición {$condicion} seleccionada");
		}
		$grupos = $this->get_grupos($condicion);
		foreach ($grupos as $grupo){
			$this->eliminar_grupo($grupo['indice']);
		}
		$this->eliminar_condicion_sin_grupos($condicion);
		$this->s__guardado = false;
	}
	
	function eliminar_condicion_sin_grupos($condicion)
	{
		if (!$this->s__plan_version_editable) {
			throw new guarani_error("ELIMINAR CONDICIÓN: El plan no puede editarse");
		}
		if (!$this->existe_condicion($condicion)){
			throw new guarani_error("ELIMINAR CONDICIÓN: No existe la condición {$condicion} seleccionada");
		}
		$id_condicion = $this->get_id_interno_condicion($condicion);
		$this->tabla('condiciones')->eliminar_fila($id_condicion);
		$this->s__guardado = false;
	}
	
	
	protected function proximo_orden_grupo($condicion)
	{
		$orden = 1;
		$grupos = $this->get_grupos($condicion);
        foreach ($grupos as $grupo){
        	if ($grupo['orden'] >= $orden){
        		$orden = $grupo['orden'] + 1; 
        	}
        }
		return $orden;		
	}
	
	function agregar_grupo($condicion)
	{
		if (!$this->s__plan_version_editable) {
			throw new guarani_error("Agregar Grupo: El plan no puede editarse");
		}
		if ($this->modo_correlativas() && $this->es_plan_personalizado() && $this->es_version_activa()) {
			$this->control_cambio_correlativas_plan_personalizado($condicion);
		}
		if ($this->existe_grupo_vacio_en_condicion($condicion)){
		 	throw new guarani_error('Ya existe una opción vacia');
		}
        toba::logger()->info("======== INICIO Agregar GRUPO [condicion: $condicion]");
        $orden = $this->proximo_orden_grupo($condicion);
        if ($this->existe_condicion($condicion)){
        	$id = $this->get_id_interno_condicion($condicion);	
        } else {
        	throw new guarani_error('Error interno: No existe la condición seleccionada');
        }
		$this->tabla('condiciones')->set_cursor($id);
		$grupo = $this->tabla('grupos')->nueva_fila(array('orden'=>$orden));
		$this->s__guardado = false;
        toba::logger()->info("======== FIN Agregar GRUPO [ cursor condicion: $id | nuevo cursor grupo: $grupo]  ========");
        return $grupo; 
	}
	
	function eliminar_grupo($id_grupo)
	{
		if (!$this->s__plan_version_editable) {
			throw new guarani_error("Eliminar Grupo: El plan no puede editarse");
		}
		$requisitos = $this->get_requisitos($id_grupo);
		foreach ($requisitos as $id_requisito => $datos){  
			$this->eliminar_requisito($datos['indice']);
		}
		$this->tabla('grupos')->eliminar_fila($id_grupo);
		$this->s__guardado = false;
	}
	
    function eliminar_grupo_ctrl_condicion($id_grupo)
    {
    	$id_condicion = $this->tabla('grupos')->get_id_fila_padre('condiciones',$id_grupo);
    	$this->tabla('condiciones')->set_cursor($id_condicion);
    	$condicion = $this->tabla('condiciones')->get_fila($id_condicion);
    	$condicion = $condicion['condicion_tipo']; 
        $this->eliminar_grupo($id_grupo);
        if ($this->tabla('grupos')->get_cantidad_filas() == 0){
        	 $this->eliminar_condicion_sin_grupos($condicion);
        }
        $this->s__guardado = false;
    }
    
	
    protected function proximo_orden_requisito($id_grupo)
    {
        $orden = 1;
        $requisitos = $this->get_requisitos($id_grupo);
        foreach ($requisitos as $datos){
            if ($datos['orden'] >= $orden){
                $orden = $datos['orden'] + 1; 
            }
        }
        return $orden;
    }
	
	function agregar_requisito($id_grupo, $requisito, $tipo)
	{
		if (!$this->s__plan_version_editable) {
			throw new guarani_error("Agregar Requisito: El plan no puede editarse");
		}
		if ($this->modo_correlativas() && $this->es_plan_personalizado() && $this->es_version_activa()) {
			$datos_condicion = $this->tabla('condiciones')->get();
			$this->control_cambio_correlativas_plan_personalizado($datos_condicion['condicion_tipo']);
		}
		if ($tipo == condicion::requisito_requisito) {
			if ($this->existe_requisito_en_grupo($id_grupo, $requisito, $tipo)){
				throw new guarani_error('Ya existe ese requisito en el grupo');
			}
			$datos_elemento = toba::consulta_php('co_requisitos')->get_requisito_datos($requisito);
		} else {	
			$elemento = $requisito;
			$datos_elemento = toba::consulta_php('co_elementos')->get_info_elemento_plan($this->get_id_plan_version(), $elemento);
		}	
			
		$orden = $this->proximo_orden_requisito($id_grupo);
        toba::logger()->info("=========================== INICIO Agregar REQUISITO [cursor grupo: $id_grupo]  --------------------");
		$this->tabla('grupos')->set_cursor($id_grupo);
		$datos['orden']=$orden;
		
		$datos['operador_not'] = null; //TODO: operador not por defecto
		$datos['origen'] = condicion::origen_interno;

		if ($tipo == condicion::requisito_requisito) {
			$datos['tipo'] = condicion::requisito_requisito;
			$datos['entidad'] 		= null;
			$datos['estado'] 		= null;
			$datos['regla'] 		= $datos_elemento['regla'];
			$datos['parametros'] 	= null;
			$datos['requisito'] 	= $requisito;
		} else {
			$datos['tipo'] = condicion::requisito_entidad;
			$datos['requisito'] 	= null;
			$datos['entidad'] 		= $datos_elemento['entidad']; 
			switch (true) {
				case ($tipo == 'M' || $tipo == 'P'):
					$datos['regla']			= $datos_elemento['regla'];
					$datos['parametros']	= $datos_elemento['parametros'];
				break;
				case ($tipo == 'A'):
					// Si es materia generica 
					if($datos_elemento['entidad_tipo'] == 1 && $datos_elemento['entidad_subtipo'] == 2) {
						$datos['regla']			= $datos_elemento['regla'];
						$datos['parametros']	= $datos_elemento['parametros'];
					} 
					// La actividad se tiene en cuenta una vez que se setea el estado para el certificado
					// en la fs modificar_requisito
				break;
			}
		}
		$requisito = $this->tabla('requisitos')->nueva_fila($datos);
		$this->s__guardado = false;
		toba::logger()->info("=========================== FIN Agregar REQUISITO [cursor requisito: $requisito]  --------------------");
        return $requisito;
	}
	
	
	/**
	 * Modifica la condición de cumplimiento de un requisito
	 * 
	 * @param $id_requisito
	 * @param $condicion
	 */
	function modificar_requisito($id_requisito, $datos)
	{	
		//Esto es porque al borrar un grupo, se disparaba el 
		//modificar datos del nodo después del borrado
		if (! $this->tabla('requisitos')->existe_fila($id_requisito)) {
			return;
		}
		
		$datos_requisito = $this->tabla('requisitos')->get_fila($id_requisito);
		if (isset($datos['estado'])) {
			$datos_entidad = toba::consulta_php('co_g3entidades')->get_datos_entidad_estado($datos_requisito['entidad'],$datos['estado']);
			$datos['regla'] = $datos_entidad['regla'];
		}

		if (!$this->s__plan_version_editable) {
			throw new guarani_error("Modificar Requisito: El plan no puede editarse");
		}
		if ($this->modo_correlativas() && $this->es_plan_personalizado() && $this->es_version_activa()) {
			$datos_condicion = $this->tabla('condiciones')->get();
			$this->control_cambio_correlativas_plan_personalizado($datos_condicion['condicion_tipo']);
		}
		// verifico que exista el requisito y que la condición sea válida
		try {
			$this->tabla('requisitos')->set_cursor($id_requisito);
		} catch (guarani_error $e){
			throw new guarani_error("Modificar Requisito: No existe el requisito seleccionado");
		}
		$this->validar_estado($id_requisito, $datos);
		$this->tabla('requisitos')->set($datos);
		$this->s__guardado = false;
    }

	/**
	 * Elimina un requisito, sin importar si el grupo queda vacio o no, 
	 * 
	 * @param $id_requisito
	 */
	function eliminar_requisito($id_requisito)
	{
		if (!$this->s__plan_version_editable) {
			throw new guarani_error("Eliminar Requisito: El plan no puede editarse");
		}
		if ($this->modo_correlativas() && $this->es_plan_personalizado() && $this->es_version_activa()) {
			$datos_condicion = $this->tabla('condiciones')->get();
			$this->control_cambio_correlativas_plan_personalizado($datos_condicion['condicion_tipo']);
		}
		try {
			$this->tabla('requisitos')->eliminar_fila($id_requisito);
		} catch (guarani_error $e){
			throw new guarani_error("Eliminar Requisito: No existe el requisito seleccionado");
		}
		$this->s__guardado = false;
	}
	
		
    /**
     * Elimina un requisito, si el grupo queda vacio, también elimina el grupo padre
     *  
     * @param $id_requisito
     * @return unknown_type
     */
	function eliminar_requisito_ctrl_grupo($id_requisito)
	{
		$id_grupo = $this->get_id_interno_grupo($id_requisito);
		$this->tabla('grupos')->set_cursor($id_grupo);
		$this->eliminar_requisito($id_requisito);
		if ($this->tabla('requisitos')->get_cantidad_filas() == 0){
			$this->eliminar_grupo_ctrl_condicion($id_grupo);
		}
		$this->s__guardado = false;
		
	}
	
	/**
	 * API para la eliminiación de todos los requisitos de un elemento
	 * 
	 * @return unknown_type
	 */
	
	function eliminar_condiciones()
	{
		if (!$this->s__plan_version_editable) {
			throw new guarani_error("Eliminar Requisito Elemento: El plan no puede editarse");
		}
		$condiciones = $this->get_condiciones();
		foreach ($condiciones as $condicion) {
				if ($this->existe_condicion($condicion['condicion_tipo'])){
					$this->eliminar_condicion($condicion['condicion_tipo']);
				}
		}
	}
	

	//------------------------------------------------------------------------------------

	function get_id_interno_grupo($id_requisito)
	{
		$id_grupo = $this->tabla('requisitos')->get_id_fila_padre('grupos',$id_requisito);
		if($id_grupo === null){
			throw new guarani_error('Error interno: No existe el grupo seleccionado');
		}
		return $id_grupo;
	}
	
	function get_id_interno_condicion($condicion)
	{
		if (!$this->existe_condicion($condicion)){
			throw new guarani_error('Error interno: No existe la condición seleccionada');
		}
		$filtro['condicion_tipo']=$condicion;
		$id_filas = $this->tabla('condiciones')->get_id_fila_condicion($filtro);
		return $id_filas[0];
	}
	
	//------------------------------------------------------------------------------------
	//							CONSULTAS
	//------------------------------------------------------------------------------------	

	function existe_condicion($condicion)
	{
		$filtro['condicion_tipo']= $condicion;
		$id_filas = $this->tabla('condiciones')->get_id_fila_condicion($filtro);
		if($id_filas) {
			if(guarani_arreglos::count($id_filas) != 1) {
				throw new guarani_error('Error interno: existe más de una instancia del mismo tipo de condición');
			}
			return true;
		} else {
			return false;
		}
	}

	function existe_requisito_en_grupo($id_grupo, $requisito, $tipo, $estado=null)
	{
		$requisitos_existentes = $this->get_requisitos($id_grupo);
		if ($tipo == condicion::requisito_requisito) { 
			$requisitos_existentes = aplanar_matriz($requisitos_existentes, 'requisito');
			return in_array($requisito, $requisitos_existentes);
		} else {
			foreach ($requisitos_existentes as $requisito_existente) {
				if (isset($requisito_existente['entidad']) &&
					$requisito_existente['entidad'] == $requisito &&
					isset($requisito_existente['estado']) &&
					$requisito_existente['estado'] == $estado) {
					return true;
				}
			}
			return false;
		}
	}

	function existe_grupo_vacio_en_condicion($condicion)
	{
		$grupos = $this->get_grupos($condicion);
		foreach ($grupos as $grupo){
			if (guarani_arreglos::count($this->get_requisitos($grupo['indice'])) == 0) {
				return true;
			}
		}
		return false;
	} 
	
	function get_elemento()
	{
		if (isset($this->s__entidad))
			return $this->s__entidad;
		else
			return null;	
	}
	
	function get_guardado()
	{
		return $this->s__guardado;
	}

	function get_datos_elemento()
	{
		if (isset($this->s__entidad) && isset($this->s__plan_version)) {
			$datos = toba::consulta_php('co_g3entidades')->get_datos_entidad($this->get_elemento());
		} else {
			$datos = Array();
		}
        return $datos;
	}
	
	function condicion_valida($condicion)
	{
        return in_array($condicion,condicion::get_condiciones_correlativas());
	}
	
	function estado_valido($entidad, $estado)
	{
        $subtipo = g3entidad::get_subtipo_entidad($entidad);
        $estados_validos = aplanar_matriz(g3entidad::get_subtipo_estados($subtipo),'valor');
        return in_array($estado,$estados_validos);
	}

	function recuperar_clave_db()
	{
		return array('entidad'=>$this->s__entidad,'plan_version'=>$this->s__plan_version);
	}

	function validar_estado($id_requisito, $datos) 
	{
		$error = false;
		$entidad = $this->tabla('requisitos')->get_columna('entidad');
		$tipo = $this->tabla('requisitos')->get_columna('tipo');
		if (! ($tipo == condicion::requisito_entidad)) return;
		$tipo_entidad = g3entidad::get_tipo_entidad($entidad);
		if ($tipo_entidad == g3entidad::tipo_modulo) return;
		$estado_actual = $this->tabla('requisitos')->get_columna('estado');
		if (array_key_exists('estado',$datos) && ! isset($datos['estado'])) {
			$error = true;
		}
		if (! $error && ! isset($estado_actual) && ! isset($datos['estado'])) {
			$error = true;
		}
		if ($error) {
			throw new guarani_error("Modificar Requisito: El Estado es obligatorio.");
		}
		if (isset($datos['estado']) && !$this->estado_valido($entidad,$datos['estado'])){
			throw new guarani_error("Modificar Requisito: No es un Estado válido.");
		}
		$grupo_condicion = $this->get_id_interno_grupo($id_requisito);
		if ($this->existe_requisito_en_grupo($grupo_condicion, $entidad, $tipo, $datos['estado'])){
			throw new guarani_error('Ya existe el requisito dentro del grupo con el mismo estado.');
		}
	}	

	function control_cambio_correlativas_plan_personalizado($condicion_tipo)
	{
		$plan_version = $this->get_id_plan_version();
		$datos_alumno = toba::consulta_php('co_alumnos')->get_alumno_de_plan_version($plan_version);
		$datos_elemento = $this->get_datos_elemento();

		$filtro = "vw_regularidades_basica.resultado = '".escalas_notas::resultado_aprobado."'";
		$actividad_en_regularidades = toba::consulta_php('co_alumnos')->tiene_actividad_en_regularidades_plan_version($plan_version, $datos_elemento['elemento'], $datos_alumno['alumno'], $filtro);
		// Si la actividad está cursada no se pueden modificar las correlativas de cursado.
		if ($actividad_en_regularidades && $condicion_tipo == condicion::tipo_cursar) {
			$msj = guarani_mensajes::get('800PRO_cambiar_correlativas_plan_pers_cursada');
			throw new guarani_error($msj);
		}

		$filtro = "vw_hist_academica_basica.resultado = '".escalas_notas::resultado_aprobado."'";
		$actividad_en_historia_academica = toba::consulta_php('co_alumnos')->tiene_actividad_en_historia_academica_plan_version($plan_version, $datos_elemento['elemento'], $datos_alumno['alumno'], $filtro);
		// Si la actividad está aprobada no se pueden modificar las correlativas de esa actividad.
		if ($actividad_en_historia_academica) {
			$msj = guarani_mensajes::get('800PRO_cambiar_correlativas_plan_pers_aprobada');
			throw new guarani_error($msj);
		}
	}
}

?>