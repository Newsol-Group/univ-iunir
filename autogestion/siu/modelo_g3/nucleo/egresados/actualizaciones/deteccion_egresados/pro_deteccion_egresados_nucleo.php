<?php
/**
 *
 * 
 * @package 	Egresados
 * @subpackage 	Actualizaciones
 * 
 */
class pro_deteccion_egresados_nucleo extends guarani_procesos_bk
{
	protected $plan_version;
	protected $certificado;
	protected $anio_desde;
	protected $anio_hasta;
	protected $alumnos;

	protected $arbol_certificado;

	protected $historia_academica;
	protected $regularidades;
	protected $inscripciones_cursada;
	protected $aprobaciones_por_resolucion;

	const reporte_en_condiciones = 1;
	const reporte_resultados = 2;

	const nombre = 'pro_deteccion_egresados';

	function set_datos($datos=array())
	{
		$this->plan_version = $datos['plan_version'];
		$this->certificado = $datos['certificado'];
		$this->anio_desde = $datos['anio_desde'];
		$this->anio_hasta = $datos['anio_hasta'];
		$this->alumnos = $datos['alumnos'];
		if (isset($datos['fecha'])) {
			$this->fecha = guarani_fecha::convertir_fecha_formato_base($datos['fecha']);
		} else {
			$this->fecha = null;
		}
	}

	function procesar()
	{
		$paso = 100/guarani_arreglos::count($this->alumnos);
		$cant_en_condiciones = 0;

		$this->arbol_certificado = $this->get_arbol_certificado();

		foreach ($this->alumnos as $alumno) {
			$estado = $this->get_estado();
			if ((isset($estado['abortar'])) && ($estado['abortar'])) {
				$this->abortar = true;
				break;
			}
			if ($this->validar($alumno)) {
				$cant_en_condiciones++;
				$this->log->set($alumno, array(self::reporte_en_condiciones));
				$this->log->guardar();
			}
			$this->log_estado->incrementar_progreso($paso);
		}

		$mensaje_resultado['plan_version'] = $this->plan_version;
		$mensaje_resultado['certificado'] = $this->certificado;
		$mensaje_resultado['anio_desde'] = $this->anio_desde;
		$mensaje_resultado['anio_hasta'] = $this->anio_hasta;
		$mensaje_resultado['alumnos_en_condiciones'] = $cant_en_condiciones;
		$this->log->set($mensaje_resultado, array(self::reporte_resultados));
		$this->log->guardar();
	}

	function get_arbol_certificado()
	{
		$arbol = array();
		$grupos = toba::consulta_php('co_condiciones')->get_opciones_certificado_plan_version($this->certificado, $this->plan_version);
		foreach ($grupos as $grupo) {
			$requisitos = toba::consulta_php('co_condiciones')->get_requisitos_opcion($grupo['grupo_condicion']);
			foreach ($requisitos as $requisito) {
				$arbol['grupos'][$grupo['orden']]['requisitos'][$requisito['orden']] = $requisito;
				$arbol['grupos'][$grupo['orden']]['requisitos'][$requisito['orden']]['componentes'] = $this->get_componentes_requisito($requisito);
			}
		}
		return $arbol;
	}

	function get_componentes_requisito($requisito)
	{
		$componentes = null;
		if ($requisito['entidad_tipo'] == g3entidad::tipo_modulo) {
			$componentes = toba::consulta_php('co_planes')->get_componentes_modulo($requisito['elemento'], $this->plan_version);
			foreach ($componentes as $clave => $valor) {
				$componentes[$clave]['componentes'] = $this->get_componentes_requisito($valor);
			}
		}
		return $componentes;
	}

	function validar($alumno)
	{
		$this->completar_historia_academica($alumno['alumno']);
		$this->completar_regularidades($alumno['alumno']);
		$this->completar_inscripciones_cursada($alumno['alumno']);
		$this->completar_aprobaciones_por_resolucion($alumno['alumno']);

		foreach ($this->arbol_certificado['grupos'] as $grupo) {
			$grupo_cumplido = true;
			foreach ($grupo['requisitos'] as $requisito) {
				$grupo_cumplido = ($requisito['tipo'] == condicion::requisito_entidad) ? $this->validar_entidad($alumno['alumno'], $requisito) : $this->validar_requisito($alumno, $requisito);
				if (! $grupo_cumplido) break;
			}
			if ($grupo_cumplido) return true;
		}

		return false;
	}

	function completar_historia_academica($alumno)
	{
		$historia_academica = toba::consulta_php('co_alumnos')->get_historia_academica_basica_alumno($alumno);
		$this->historia_academica = rs_convertir_asociativo_matriz($historia_academica, array('elemento'), array('creditos', 'horas'));
	}

	function completar_regularidades($alumno)
	{
		$regularidades = toba::consulta_php('co_alumnos')->get_regularidades_basica_alumno($alumno);
		$this->regularidades = rs_convertir_asociativo_matriz($regularidades, array('elemento'), array('creditos', 'horas'));
	}

	function completar_inscripciones_cursada($alumno)
	{
		$inscripciones_cursada = toba::consulta_php('co_alumnos')->get_inscripciones_cursada_alumno($alumno);
		$this->inscripciones_cursada = rs_convertir_asociativo($inscripciones_cursada, array('elemento'), 'elemento');
	}

	function completar_aprobaciones_por_resolucion($alumno)
	{
		$aprobaciones_por_resolucion = toba::consulta_php('co_alumnos')->get_modulos_reconocidos_por_resolucion_alumno($alumno);
		$this->aprobaciones_por_resolucion = rs_convertir_asociativo_matriz($aprobaciones_por_resolucion, array('elemento'));
	}

	function validar_entidad($alumno, $entidad)
	{
		return ($entidad['entidad_tipo'] == g3entidad::tipo_actividad) ? $this->validar_actividad($entidad) : $this->validar_modulo($alumno, $entidad);
	}

	function validar_actividad($entidad)
	{
		$elemento = $entidad['elemento'];
		switch ($entidad['estado']) {
			case g3entidad::estado_aprobado:
				$resultado = isset($this->historia_academica[$elemento]);
				break;
			case g3entidad::estado_cursado:
				$resultado = (isset($this->regularidades[$elemento]) || isset($this->historia_academica[$elemento]));
				break;
			case g3entidad::estado_inscripto:
				$resultado = isset($this->inscripciones_cursada[$elemento]);
				break;
			case g3entidad::estado_cursado_simultaneo:
				$resultado = (isset($this->inscripciones_cursada[$elemento]) || isset($this->regularidades[$elemento]));
				break;
		}
		return $entidad['operador_not'] ? !$resultado : $resultado;
	}

	function validar_modulo($alumno, $entidad)
	{
		$regla = guarani::regla($entidad['regla']);
		$regla->set_parametros($entidad['parametros']);
		$regla->set_operador_not($entidad['operador_not']);
		$regla->set_parametros_contexto(array(	'alumno' => $alumno,
			'plan_version' => $this->plan_version,
			'elemento' => $entidad['elemento']));
		$componentes_modulo = ($entidad['entidad_subtipo'] == g3entidad::subtipo_materia_generica) ? planes::excluir_optativas_compartidas($entidad['componentes'], $entidad['elemento'], $this->plan_version, $alumno) : $entidad['componentes'];
		return $regla->validar_cache($componentes_modulo, $this->historia_academica, $this->regularidades, $this->aprobaciones_por_resolucion);
	}

	function validar_requisito($alumno, $requisito)
	{
		if (!isset($this->fecha)) {
			$fecha = guarani_fecha::get_hoy();
		} else {
			$fecha = $this->fecha;
		}
		$regla = guarani::regla($requisito['regla']);
		$regla->set_parametros($requisito['parametros']);
		$regla->set_operador_not($requisito['operador_not']);
		$regla->set_parametros_contexto(array(	'alumno' => $alumno['alumno'],
			'persona' => $alumno['persona'],
			'propuesta' => $alumno['propuesta'],
			'plan_version' => $this->plan_version,
			'requisito' => $requisito['requisito'],
			'fecha' => $fecha));
		return $regla->validar();
	}
}