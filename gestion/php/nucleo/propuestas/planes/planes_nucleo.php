<?php
/**
 * Catálogo de Planes
 *
 * Atencion: esta clase se instancia con: guarani::catalogo_planes()
 *
 * @package		Propuestas
 * @subpackage	Planes
 * 
 */
class planes_nucleo
{
	// Estados
	const estado_nuevo = 'N';
	const estado_activo = 'A';
	const estado_vigente = 'V';
	const estado_activo_vigente = 'V';
	const estado_activo_no_vigente = 'A';
	const estado_baja = 'B';
	
	// Tipos
	const tipo_convenio = 'Convenio';
	const tipo_personalizado = 'Personalizado';
	const tipo_estructurado = 'Estructurado';
	const tipo_estruct_curso_descr = 'para administrar cursos';
	const tipo_vocacional_descr = 'correspondiente a una propuesta vocacional';
	
	// Operación de Copia de Planes/Versiones
	const copia_plan = 'P';
	const copia_version = 'V';
	
	// Código Operación de Administrar Planes.
	const cod_op_admin_planes = '800SIUPRO002';
	const cod_op_asignar_correlativas = '800SIUPRO003';

	// Identificador de la operación Activar Planes.
	const item_activacion_planes = '9000016';
	
	// Sincronización del plan con SQ
	const no_aplica_sincronizado_sq		= 0;
	const sincronizado_sq				= 1;
	const no_sincronizado_sq			= 2;

	// Tipos de resolución
	const resolucion_tipo_ministerial = 'MINISTERIAL';
	const resolucion_tipo_ministerial_descr = 'Resolución Ministerial';
	const resolucion_tipo_coneau = 'CONEAU';
	const resolucion_tipo_coneau_descr = 'Resolución CONEAU';
	const resolucion_tipo_spu = 'SPU';
	const resolucion_tipo_spu_descr = 'Resolución SPU';
	const resolucion_tipo_disposicion_spu = 'DISPOSICION SPU';
	const resolucion_tipo_disposicion_spu_descr = 'Disposición SPU';

	const tipo_plan_anterior = 'PLAN_ANTERIOR';
	const tipo_plan_actual = 'PLAN_ACTUAL';

	static protected $instancia;

	function __construct()
	{
		
	}

	static function instancia()
	{
		if (!isset(self::$instancia)) {
			self::$instancia = new planes();
		}
		return self::$instancia;
	}

	static function es_editable($plan_version)
	{
		$datos_plan = toba::consulta_php('co_planes')->get_plan_version_estado_edicion($plan_version);
		$editable = ($datos_plan['tipo_plan'] == planes::tipo_estructurado && $datos_plan['administra_cursos'] == comunes::no && $datos_plan['propuesta_tipo'] != propuesta::tipo_vocacional) ? $datos_plan['estado'] == planes::estado_nuevo : $datos_plan['estado'] != planes::estado_baja;
		return $editable;
	}

	/**
	 * Retorna el conjunto de componentes de un módulo dado en un plan-versión, teniendo en cuenta la reasignación de optativas del alumno.
	 * 
	 * @param int $modulo
	 * @param int $plan_version
	 * @param int $alumno
	 * @param string $filtro
	 * @return array
	 */
	static function get_componentes_modulo($modulo, $plan_version, $alumno, $filtro='true')
	{
		if (toba::consulta_php('co_elementos')->es_materia_generica($modulo)) {
			// Se excluyen las optativas compartidas tales que: el alumno no eligió para cuál de las genéricas se cumple o bien eligió una genérica que no es la que se está evaluando.
			$filtro .= " AND f_optativa_es_compartida(sga_elementos.elemento, $plan_version, $alumno, $modulo) IN (0, 1)";
		}
		return toba::consulta_php('co_planes')->get_componentes_modulo($modulo, $plan_version, $filtro);
	}

	/**
	 * Quita del conjunto de componentes de una materia genérica aquellas optativas compartidas tales que:
	 * - el alumno no asignó a ninguna genérica
	 * - el alumno asignó a otra genérica
	 * 
	 * @param array $componentes
	 * @param int $materia_generica
	 * @param int $plan_version
	 * @param int $alumno
	 * @return array
	 */
	static function excluir_optativas_compartidas($componentes, $materia_generica, $plan_version, $alumno)
	{
		foreach ($componentes as $clave => $valor) {
			$optativa_esta_compartida = toba::consulta_php('co_alumnos')->optativa_esta_compartida($valor['elemento'], $plan_version, $alumno, $materia_generica);
			if ($optativa_esta_compartida == -1 || $optativa_esta_compartida == 2) {
				unset($componentes[$clave]);
			}
		}
		return $componentes;
	}
}
?>