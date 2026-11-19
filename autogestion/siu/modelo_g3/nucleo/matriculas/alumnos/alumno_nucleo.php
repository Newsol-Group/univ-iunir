<?php
/**
 * Catálogo de tipos de alumnos
 *
 *	Atencion: esta clase se instancia con: guarani::catalogo_alumnos()
 *
 * @package Matrículas
 * @subpackage Personas
 */
class alumno_nucleo extends persona
{
	//Calidades de alumno
	const calidad_activo	= 'A';
	const calidad_egresado	= 'E';
	const calidad_pasivo	= 'P';

	// Motivos de cambios de calidad de un alumno.
	const motivo_calidad_alta = 1;
	const motivo_calidad_egreso = 2;
	const motivo_calidad_pasivo = 3;
	const motivo_calidad_abandono = 4;
	const motivo_calidad_rechazado_requisitos = 5;
	const motivo_calidad_rechazado_cambio_carrera = 7;
	const motivo_calidad_rechazado_abandono_carrera = 8;
	const motivo_calidad_rechazo_administrativo = 9;
	const motivo_calidad_falta_pago = 10;

	// Motivos de cambios de plan de un alumno.
	const motivo_plan_alta = 1;
	const motivo_plan_cambio = 2;
	const motivo_plan_cambio_version = 3;

	// Tipos de cambio de plan de un alumno.
	const cambio_plan_tipo_inscripcion = 1;
	const cambio_plan_tipo_solicitado = 2;
	const cambio_plan_version_tipo_solicitado = 3;
	const cambio_plan_tipo_forzado = 4;
	const cambio_plan_version_tipo_forzado = 5;
	
	// Categorías de cambio de plan versión masivo de alumnos.
	const cat_cambio_plan = 'P';
	const cat_cambio_version = 'V';
	
	// Destinos cambio plan versión masivo de alumnos.
	const dest_planes_activos_vigentes = 'PN';
	const dest_plan_anterior = 'PA';
	const dest_ultima_version = 'UV';
	
	// Motivos de cambios de modalidad de un alumno.
	const motivo_modalidad_alta = 1;

	// Motivos de cambios de ubicación de un alumno.
	const motivo_ubicacion_alta = 1;

	// Motivos de movimiento de historia académica.
	const motivo_movimiento_ha_invalidar = 2;
	
	//Tipos de motivos de movimientos de la historia académica de un alumno
	const motivo_movimiento_ha_tipo_invalidar  = 'Invalidar';
	const motivo_movimiento_ha_tipo_convalidar = 'Convalidar';
	
	// Código de alumno ficticio.
	const id_alumno_ficticio = -1;

	// Tipo de Cambio de Plan/Plan-Versión
	const tipo_cambio_solicitado = 1;
	const tipo_cambio_forzado = 2;

	// Destinos de cambio de plan de un alumno.
	const cambio_plan_destino_activos_vigentes = 1;
	const cambio_plan_destino_activos_no_vigentes = 2;
	const cambio_plan_destino_anteriores = 3;

	// Destinos de cambio de plan-versión de un alumno.
	const cambio_plan_version_destino_activas_vigentes = 4;
	const cambio_plan_version_destino_activas_no_vigentes = 5;
	const cambio_plan_version_destino_anteriores = 6;
    
    //Constantes Alumnos en condiciones de cursar/rendir valor
    const cursar_rendir_todos_valor = 't';
    const cursar_rendir_cursar_valor = 'c';
    const cursar_rendir_rendir_valor = 'r';
    const cursar_rendir_recursante_valor = 're';
    const cursar_rendir_c_primera_vez_valor = 'c1v';
    const cursar_rendir_r_primera_vez_valor = 'r1v';
    //Constantes Alumnos en condiciones de cursar/rendir descr
    const cursar_rendir_todos_descr = 'Todos';
    const cursar_rendir_cursar_descr = 'Cursar';
    const cursar_rendir_rendir_descr = 'Rendir';
    const cursar_rendir_recursante_descr = 'Recursante';
    const cursar_rendir_c_primera_vez_descr = 'Cursa por 1ra vez';
    const cursar_rendir_r_primera_vez_descr = 'Rinde por 1ra vez';
    

	static protected $instancia;

	static function instancia()
	{
		if (!isset(static::$instancia)) {
			static::$instancia = new alumno();	
		}
		return static::$instancia;	
	}

	
	private function __construct()
	{
	
	}

	static function get_nombre_resultado_asp ($resultado)
	{
		switch ($resultado) {
			case 'A':
				return 'Aceptado';
				break;
			case 'P':
				return 'Pendiente';
				break;
			case 'R':
				return 'Rechazado';
				break;
			default:
				return $resultado;
				break;
		}
	}
	
	/**
	 * Determina si un alumno registra historia académica.
	 *
	 * @param integer $alumno
	 * @return boolean
	 */
	static function tiene_historia_academica($alumno)
	{
		return	toba::consulta_php('co_alumnos')->tiene_historia_academica($alumno) ||
			 	toba::consulta_php('co_alumnos')->tiene_regularidades($alumno) ||
			 	toba::consulta_php('co_alumnos')->tiene_certificados_otorgados($alumno);
	}

	/**
	 * Invalidación automática de la historia académica de un alumno.
	 * IMPORTANTE: enmarcar el llamado a esta función dentro de una transacción.
	 * 
	 * @param integer $alumno
	 */
	static function invalidar_historia_academica($alumno, $usuario_responsable=null)
	{
		// Invalidación de historia académica y regularidades.
		$cn_manejador_invalidar_ha = toba::cn('manejador_invalidar_ha');
		$cn_manejador_invalidar_ha->desactivar_transacciones();

		$historia_academica = $cn_manejador_invalidar_ha->get_actividades_aprobadas($alumno);
		$cn_manejador_invalidar_ha->set_seleccion_aprobadas($historia_academica);

		$regularidades = $cn_manejador_invalidar_ha->get_actividades_regularizadas($alumno);
		$cn_manejador_invalidar_ha->set_seleccion_regularizadas($regularidades);

		$cn_manejador_invalidar_ha->set_datos(array('motivo_movimiento' => alumno::motivo_movimiento_ha_invalidar,
													'documento' => null,
													'observaciones' => 'Invalidación automática.',
													'usuario_responsable' => $usuario_responsable));

		if (! (empty($historia_academica) && empty($regularidades))) {
			$cn_manejador_invalidar_ha->invalidar($alumno);
		}
	}

	/**
	 * Calcula el coeficiente de un alumno.
	 *
	 * @param int $alumno ID del alumno (tabla 'sga_alumnos').
	 *
	 * @return float Coeficiente del alumno.
	 */
	static function get_coeficiente_inscripcion($alumno)
	{
		// Personalizar (ver ejemplo debajo).
		return null;
	}
	
	/**
	 * Calcula el coeficiente de un alumno (200 si el DNI es par - 300 si el DNI es impar - 100 si es otro tipo de documento).
	 *
	 * @param int $alumno ID del alumno (tabla 'sga_alumnos').
	 *
	 * @return float Coeficiente del alumno.
	 */
	/*static function get_coeficiente_inscripcion($alumno)
	{
		$where = "sga_alumnos.alumno = $alumno";
		$datos_alumno = toba::consulta_php('co_alumnos')->get_listado($where);
		$datos_alumno = reset($datos_alumno);
		
		$coeficiente = null;
		
		// Si el tipo de documento es DNI
		if ($datos_alumno['tipo_documento'] == persona::doc_dni) {
			
			// Si el DNI es par
			if (($datos_alumno['nro_documento'] % 2) == 0) {
				$coeficiente = 200;
			} else { // si es impar
				$coeficiente = 300;
			}
			
		} else { // sino
			
			$coeficiente = 100;
			
		}
		
		return $coeficiente;
		
	}*/
	
	/*
	 * Devuelve el/los modulos rtf aprobados segun el filtro pasado por parametro
	 * @param string $filtro
	 * @return array
	 */
	static function get_suplementos_rtf_aprobados($filtro)
	{
		$rtfs = toba::consulta_php('co_planes')->get_modulos_rtf($filtro);
		$aprobadas = array();
		if (!empty($rtfs)) {			
			/*
			 * Voy a buscar si los modulos están aprobados, el que este aprobado
			 * lo agrego en el array aprobadas.
			 */
			foreach($rtfs as $rtf) {
				$contexto = array(
									'alumno' => $rtf['alumno'],
									'elemento' => $rtf['elemento'],
									'plan_version' => $rtf['plan_version'],
								 );
				$regla = guarani::regla($rtf['regla']);
				$regla->set_parametros($rtf['parametros']);			
				$regla->set_parametros_contexto($contexto);
				if ($regla->validar()) {
					$aprobadas[] = $rtf;					
				}
			}
		}
		return $aprobadas;
	}
	
	static function generar_suplementos($datos) // $datos = ['nro_solicitud' => X, 'alumno' => Y]
	{
		//Obtener tipos de suplementos ordenados
		$tipos_suplemento = toba::consulta_php('co_tramites_certificacion')->get_suplementos_tipos();
        foreach ($tipos_suplemento as $tipo) {
			$metodo = $tipo['metodo_php'];
			alumno::$metodo($datos['nro_solicitud'], $datos['alumno'], $tipo['tipo_suplemento']);
		}
	}
	
	static function obtener_suplemento_reconocimiento($alumno) 
	{	
		$resultado = null;
		
		$antecedentes = toba::consulta_php('co_alumnos')->get_suplemento_reconocimiento($alumno);
		
		if(guarani_arreglos::count($antecedentes) > 0) 
		{
			$texto = '';

			foreach($antecedentes as $antecedente) {
				$texto .= $antecedente['titulo'] . ': ';
				$texto .= $antecedente['observaciones'];
				$texto .= ". \n";
			}

			$resultado = [
				'texto' => $texto,
				'nro_disposicion' => 'NULL'
			];
		}
		
		return $resultado;
	}
	
	static function get_suplemento_reconocimiento($nro_solicitud, $alumno, $tipo_suplemento) 
	{
		$suplemento = alumno::obtener_suplemento_reconocimiento($alumno);
        
		if(isset($suplemento))
		{
			try {
            guarani::act('act_alumnos')->insertar_suplemento(
                $nro_solicitud,
                $tipo_suplemento,
					$suplemento['texto'],
					$suplemento['nro_disposicion']
            );
			} catch ( guarani_error $e ) {
				throw $e;
        }
	}
	}
	
	static function obtener_suplemento_docencia($alumno) 
	{	
		$resultado = null;
		
		$antecedentes = toba::consulta_php('co_alumnos')->get_suplemento_docencia($alumno);
		
		if(guarani_arreglos::count($antecedentes) > 0) 
		{
			$texto = 'El egresado se ha desempeñado como: '."\n";
		
			foreach($antecedentes as $antecedente) {
				$texto .= '- '.$antecedente['responsabilidad'] . ' de la actividad ';
				$texto .= $antecedente['actividad'] . ' en el período ';
				$texto .= guarani_fecha::convertir_fecha_formato_frontend($antecedente['inicio_dictado']);
				$texto .= ' - ';
				$texto .= guarani_fecha::convertir_fecha_formato_frontend($antecedente['fin_dictado']);
				$texto .= ". \n";
			}

			return [
				'texto' => $texto,
				'nro_disposicion' => 'NULL'
			];
		}
		
		return $resultado;
	}
	
	static function get_suplemento_docencia($nro_solicitud, $alumno, $tipo_suplemento) 
	{
		$suplemento = alumno::obtener_suplemento_docencia($alumno);
		
		if(isset($suplemento))
		{
			try {
				guarani::act('act_alumnos')->insertar_suplemento(
					$nro_solicitud,
					$tipo_suplemento,
					$suplemento['texto'],
					$suplemento['nro_disposicion']
				);
			} catch ( guarani_error $e ) {
				throw $e;
			}
		}
	}
	
	static function obtener_suplemento_rtf($alumno)
	{
		$resultado = null;
		
		$alumno = 'sga_alumnos.alumno = ' . toba::db()->quote($alumno);
		$modulos_rtf = alumno::get_suplementos_rtf_aprobados($alumno);        

        if(guarani_arreglos::count($modulos_rtf) > 0) 
		{
			$texto = 'El egresado ha cumplido con los siguientes Trayectos del Sistema Nacional de Reconocimiento Académico: '."\n";
			
			foreach($modulos_rtf as $modulo) {
				$texto .= '(' . $modulo['rtf_codigo'] . ') ';
				$texto .= $modulo['rtf_nombre'];
				$texto .= ' ('.$modulo['rtf_creditos'].' RFT obtenidos)';
				$texto .= ". \n";
			}
            
			$resultado = [
				'texto' => $texto,
				'nro_disposicion' => 'NULL'
			];
		}
        
		return $resultado;
	}
	
	static function get_suplemento_otros($nro_solicitud, $alumno, $tipo_suplemento) 
	{	
		$suplemento = alumno::obtener_suplemento_rtf($alumno);
			
		if(isset($suplemento))
		{
			try {
				guarani::act('act_alumnos')->insertar_suplemento(
					$nro_solicitud,
					$tipo_suplemento,
					$suplemento['texto'],
					$suplemento['nro_disposicion']
				);
			} catch ( guarani_error $e ) {
				throw $e;
			}
		}
	}
    
    /*
     * Se buscará el plan del alumno con la historia academica para mostrar y ordenar
     * que actividades tiene aprobadas y cuales le faltan.
     */
    static function get_contenido_plan_version_con_historia_academica($alumno, $plan_version, $params = [])
	{

		try {

			//Traigo el contenido del plan version pasado por parametro.
			$datos_plan = static::get_contenido_plan_version($alumno, $plan_version, $params);

			$con_optativas = false;
			if (isset($params['ver_optativas_en_estructura_plan']) && ($params['ver_optativas_en_estructura_plan'] == \comunes::si)) {
				$con_optativas = true;
			}
			static::merge_info_materias_con_historia_academica($plan_version, $alumno, $datos_plan['contenido_plan_version'], $datos_plan['detalle_materias_plan_version'], $con_optativas);

			$raiz = static::obtener_elemento_raiz($datos_plan['contenido_plan_version']);
			$arbol = static::buildTree($datos_plan['contenido_plan_version'], $raiz['elemento'], $params);
			ksort($arbol);
		} catch (guarani_error $e) {
			$arbol = [];
		}
		return $arbol;
	}

	// Agrega historia académica a una materia
	static function merge_info_materias_con_historia_academica($plan_version, $alumno, &$contenido_plan_version, $detalle_materias_plan_version, $con_optativas = false)
	{
		
		$puntaje_optativas_genericas_alumno = [];
		if ($con_optativas) {
			$puntaje_optativas_genericas_alumno = static::get_optativas_genericas_alumno($plan_version);
		}
		
		$info_act_acad_alumno = static::get_info_actividad_academica($alumno);

		foreach($contenido_plan_version as $key => $elemento){

			// Si se trata de una actividad
			if ($elemento['entidad_tipo'] == elemento::tipo_actividad) {

				$detalle_materia = $detalle_materias_plan_version[$elemento['elemento']];

				$info_act_acad_materia = [];
				// Solo mostrar resultado de la actividad si es una actividad y no una materia genérica.
				if ($elemento['entidad_subtipo'] != elemento::subtipo_materia_generica) {
					$info_act_acad_materia = ($info_act_acad_alumno[$elemento['elemento']] ?? []);
				}

				// Ticket #1530: Si es optativa, esta dada de baja y no tiene actividad académica la quito del reporte
				if ((isset($elemento['es_optativa']) && $elemento['es_optativa']) && ($detalle_materia['estado'] == elemento::estado_baja) && empty($info_act_acad_materia)) {
					unset($contenido_plan_version[$key]);
					continue;
				}
				$contenido_plan_version[$key]['actividad']				= "{$elemento['nombre']} ({$elemento['codigo']})";
				$contenido_plan_version[$key]['anio']					= $detalle_materia['anio'];
				$contenido_plan_version[$key]['periodo']				= $detalle_materia['periodo_cursada_nombre'];
				$contenido_plan_version[$key]['tipo']					= $detalle_materia['tipo_detalle'];
				$contenido_plan_version[$key]['credito']				= $detalle_materia['credito'];
				$contenido_plan_version[$key]['tiene_correlativas']		= ($detalle_materia['cant_correlativas'] > 0)? true : false;
				$contenido_plan_version[$key]['puntaje'] = '';
				if (isset($elemento['es_optativa']) && $elemento['es_optativa']) {
					$contenido_plan_version[$key]['puntaje'] = ($puntaje_optativas_genericas_alumno[$elemento['elemento']] ?? 0);
				}
				$nota = (isset($info_act_acad_materia['nota']) && $info_act_acad_materia['nota'])? $info_act_acad_materia['nota']." (".$info_act_acad_materia['resultado_descripcion'].")" : "";
				$contenido_plan_version[$key]['nota'] = $nota;
				
				$contenido_plan_version[$key]['origen'] = $info_act_acad_materia['origen_descripcion'] ?? "";
			}
		}
	}
	
    
    static function get_contenido_plan_version($alumno, $plan_version, $params = [])
    {
		$incluir_componentes_genericas = false;
		if (isset($params['ver_optativas_en_estructura_plan']) && ($params['ver_optativas_en_estructura_plan'] == \comunes::si)) {
			$incluir_componentes_genericas = true;
		}
		
		$params1 = [
			'conocer_optativas_compartidas' => true,
			'incluir_componentes_genericas' => $incluir_componentes_genericas,
			'alumno' => $alumno
		];
		$contenido_plan_version = toba::consulta_php('co_planes', true)->get_contenido_plan_version($plan_version, $params1);
		
		$params2 = [
			'incluir_componentes_genericas' => $incluir_componentes_genericas
		];
		$detalle_materias_plan_version = toba::consulta_php('co_planes', true)->get_detalle_materias_plan_version($plan_version, $params2);
		
		$arbol = [];
		$arbol['contenido_plan_version'] = $contenido_plan_version;
		$arbol['detalle_materias_plan_version'] = $detalle_materias_plan_version;
        
        return $arbol;
    }
    
	// Obtiene las materias optativas de una genérica con historia académica
	static function get_optativas_plan_version_con_historia_academica($alumno, $plan_version, $materia_generica)
	{
		// Traigo el contenido de las optativas del plan version pasado por parametro.
		$datos_plan = static::get_contenido_optativas_plan_version($alumno, $plan_version, $materia_generica);
		static::merge_info_materias_con_historia_academica($plan_version, $alumno, $datos_plan['contenido_plan_version'], $datos_plan['detalle_materias_plan_version'], true);
		return $datos_plan['contenido_plan_version'];
	}

	// Obtiene las materias optativas de una genérica
	static function get_contenido_optativas_plan_version($alumno, $plan_version, $materia_generica)
	{
		
		$optativas = toba::consulta_php('co_elementos', true)->get_optativas_generica($materia_generica, $plan_version, $alumno);

		$params = [
			'solo_optativas' => true,
			'materia_generica' => $materia_generica
		];
		$detalle_materias_plan_version = toba::consulta_php('co_planes', true)->get_detalle_materias_plan_version($plan_version, $params);

		$arbol = [];
		$arbol['contenido_plan_version'] = $optativas;
		$arbol['detalle_materias_plan_version'] = $detalle_materias_plan_version;

		return $arbol;
	}

	/*
     * Obtiene las genericas de un plan version
     */
    static function get_optativas_genericas_alumno($plan_version)
    {
        $materias_genericas_alumno = toba::consulta_php('co_alumnos', true)->get_materias_genericas_alumno($plan_version);
        $datos = [];
        foreach($materias_genericas_alumno as $dato){
              $datos[$dato['optativa_elemento']] = $dato['optativa_puntaje'];
        }
        return $datos;
    }
    
    /**
	 *  Retorna los resultados más importantes de la historia académica del alumno
	 *  Orden de importancia: Aprobacion (con resultado = 'A'), Eq. Parcial (con resultado = 'A'), Regularidad (con resultado = 'A' y vigente), En Curso
	 * @param int $alumno
	 * @return array 
	 * 
	 */
	static function get_info_actividad_academica($alumno)
	{
		$actividad_academica = toba::consulta_php('co_alumnos', true)->get_actividad_academica($alumno);
		
		$datos = [];
		foreach ($actividad_academica as $actividad) {
			
			// Datos que van para cualquier tipo
			$datos_comunes = [];
			$datos_comunes['tipo'] = $actividad['tipo'];
			$datos_comunes['anio'] = $actividad['anio'];
			$datos_comunes['materia_nombre_red'] = $actividad['materia_nombre_red'];
			$datos_comunes['origen'] = $actividad['origen'];
			$datos_comunes['origen_descripcion'] = $actividad['origen_descripcion'];
			$datos_comunes['plan_version'] = $actividad['plan_version'];
			$datos_comunes['anio_academico'] = $actividad['anio_academico'];
			$datos_comunes['catedra'] = $actividad['catedra'];
			
			$datos[$actividad['materia']]['aprobacion'] = ($datos[$actividad['materia']]['aprobacion'] ?? false);
			// Si esta aprobado en la materia salteo a la siguiente iteración
			if ($datos[$actividad['materia']]['aprobacion']) {
				continue;
			}
			
			if ($actividad['tipo'] == 'Aprobacion') {
				if ($actividad['resultado'] == 'A') {
					
					$datos[$actividad['materia']] = $datos_comunes;
					$datos[$actividad['materia']]['fecha_display'] = $actividad['fecha_display'];
					$datos[$actividad['materia']]['fecha_orden'] = $actividad['fecha_orden'];
					$datos[$actividad['materia']]['nota'] = $actividad['nota'];
					$datos[$actividad['materia']]['nota_descripcion'] = $actividad['nota_descripcion'];
					$datos[$actividad['materia']]['resultado'] = $actividad['resultado'];
					$datos[$actividad['materia']]['resultado_descripcion'] = $actividad['resultado_descripcion'];
					$datos[$actividad['materia']]['acta_resolucion'] = $actividad['acta_resolucion'];
					$datos[$actividad['materia']]['equivalencia'] = $actividad['equivalencia'];

					if ($datos[$actividad['materia']]['origen'] == 'Promocion') {
						$datos[$actividad['materia']]['comision'] = $actividad['comision'];
						$datos[$actividad['materia']]['comision_nombre'] = $actividad['comision_nombre'];
						$datos[$actividad['materia']]['periodo_lectivo'] = $actividad['periodo_lectivo'];
					}
					
					// lo marco como aprobado en la materia para que no siga iterando sobre la misma
					$datos[$actividad['materia']]['aprobacion'] = true;
					
				}
				continue;
			}

			$datos[$actividad['materia']]['eq_parcial'] = ($datos[$actividad['materia']]['eq_parcial'] ?? false);
			// Si tiene Eq. Parcial en la materia salteo a la siguiente iteración
			if ($datos[$actividad['materia']]['eq_parcial']) {
				continue;
			}
			
			if ($actividad['tipo'] == 'Eq. Parcial') {
				if ($actividad['resultado'] == 'A') {
					
					$datos[$actividad['materia']] = $datos_comunes;
					$datos[$actividad['materia']]['fecha_display'] = $actividad['fecha_display'];
					$datos[$actividad['materia']]['fecha_orden'] = $actividad['fecha_orden'];
					$datos[$actividad['materia']]['nota'] = $actividad['nota'];
					$datos[$actividad['materia']]['nota_descripcion'] = $actividad['nota_descripcion'];
					$datos[$actividad['materia']]['resultado'] = $actividad['resultado'];
					$datos[$actividad['materia']]['resultado_descripcion'] = $actividad['resultado_descripcion'];
					$datos[$actividad['materia']]['acta_resolucion'] = $actividad['acta_resolucion'];
					$datos[$actividad['materia']]['equivalencia'] = $actividad['equivalencia'];
					
					if ($datos[$actividad['materia']]['origen'] == 'Promocion') {
						$datos[$actividad['materia']]['comision'] = $actividad['comision'];
						$datos[$actividad['materia']]['comision_nombre'] = $actividad['comision_nombre'];
						$datos[$actividad['materia']]['periodo_lectivo'] = $actividad['periodo_lectivo'];
					}
					
					// lo marco como Eq. Parcial en la materia para que no siga iterando sobre la misma
					$datos[$actividad['materia']]['eq_parcial'] = true;
					
				}
				continue;
			}
			
			$datos[$actividad['materia']]['regularidad'] = ($datos[$actividad['materia']]['regularidad'] ?? false);
			// Si es regular en la materia salteo a la siguiente iteración
			if ($datos[$actividad['materia']]['regularidad']) {
				continue;
			}
			
			if ($actividad['tipo'] == 'Regularidad') {
				if (($actividad['resultado'] == 'A') && ($actividad['vigente'] == '1')) {

					$datos_a_setear = $datos_comunes;
					$datos_a_setear['fecha_display'] = $actividad['fecha_display'];
					$datos_a_setear['fecha_orden'] = $actividad['fecha_orden'];
					$datos_a_setear['nota'] = $actividad['nota'];
					$datos_a_setear['nota_descripcion'] = $actividad['nota_descripcion'];
					$datos_a_setear['resultado'] = $actividad['resultado'];
					$datos_a_setear['resultado_descripcion'] = $actividad['resultado_descripcion'];
					$datos_a_setear['acta_resolucion'] = $actividad['acta_resolucion'];
					$datos_a_setear['equivalencia'] = $actividad['equivalencia'];
					$datos_a_setear['comision'] = $actividad['comision'];
					$datos_a_setear['comision_nombre'] = $actividad['comision_nombre'];
					$datos_a_setear['periodo_lectivo'] = $actividad['periodo_lectivo'];
					$datos_a_setear['vigente'] = $actividad['vigente'];
					$datos_a_setear['cond_regularidad'] = $actividad['cond_regularidad'];
					$datos_a_setear['fin_vigencia_regul'] = $actividad['fin_vigencia_regul'];
					$datos_a_setear['turno_cursada'] = $actividad['turno_cursada'];

					if (!isset($datos[$actividad['materia']]['materia'])) {
						$datos[$actividad['materia']] = $datos_a_setear;
					} else {
						if ($datos[$actividad['materia']]['tipo'] != 'En Curso') {
							if ($datos[$actividad['materia']]['fecha_orden'] < $actividad['fecha_orden']) {
								$datos[$actividad['materia']] = $datos_a_setear;
							}
						}
					}
					
					// lo marco como regular en la materia para que no siga iterando sobre la misma
					$datos[$actividad['materia']]['regularidad'] = true;
				}
				continue;
			}
			
			// si esta en curso piso la cursada
			if ($actividad['tipo'] == 'En Curso') {
				$datos[$actividad['materia']] = $datos_comunes;
				$datos[$actividad['materia']]['calidad_insc'] = $actividad['calidad_insc'];
			}
		}

		return $datos;
	}

	static function obtener_elemento_raiz(array &$contenido_plan_version)
	{

		$elemento_raiz = null;

		foreach ($contenido_plan_version as $key => $elemento_plan) {
			if ($elemento_plan['entidad_subtipo'] == \elemento::subtipo_raiz) {
				$elemento_raiz = $elemento_plan;
				unset($contenido_plan_version[$key]);
				return $elemento_raiz;
			}
		}
	}

	//http://stackoverflow.com/questions/8840319/build-a-tree-from-a-flat-array-in-php
	static function buildTree(array &$elements, $parentId = 0, $params = [])
	{

		$tratar_generica_como_materia = false;
		if (isset($params['tratar_generica_como_materia'])) {
			$tratar_generica_como_materia = $params['tratar_generica_como_materia'];
		}
		
		$branch = array();

		foreach ($elements as &$element) {

			if ($element['elemento_padre'] == $parentId) {
				$children = static::buildTree($elements, $element['elemento'], $params);
				if (isset($children['modulos_genericas'])) {
					$element['modulos_genericas'] = $children['modulos_genericas'];
				}

				if (isset($children['materias'])) {
					$element['materias'] = $children['materias'];
				}

				// Si se trata de una actividad
				if ($element['entidad_tipo'] == \elemento::tipo_actividad) {
					$branch['materias'][$element['orden']] = $element;
				} else {
					// Si se trata de una Materia Genérica y el parámetro 'tratar_generica_como_materia' es true entonces la considero como una materia común
					if (($element['entidad_subtipo'] == \elemento::subtipo_materia_generica) && $tratar_generica_como_materia) {
						$element['actividad'] = "{$element['nombre']} ({$element['codigo']})";
						$element['tipo'] = "Materia Genérica";
						$element['anio'] = '';
						$element['periodo'] = '';
						$element['nota'] = '';
						$element['origen'] = '';
						$element['credito'] = '';
						$element['puntaje'] = '';
						$branch['materias'][$element['orden']] = $element;
					} else {// El resto se trata como agrupador de materias
						$branch['modulos_genericas'][$element['orden']] = $element;
					}
					
				}
				unset($element);
			}
		}
		if (isset($branch['modulos_genericas']) && is_array($branch['modulos_genericas']))
			ksort($branch['modulos_genericas']);
		if (isset($branch['materias']) && is_array($branch['materias']))
			ksort($branch['materias']);
		return $branch;
	}
    
}