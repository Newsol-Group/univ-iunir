<?php
/**
 * Catalogo de propuestas
 *
 * @package Propuestas
 * @subpackage Propuesta
 */


class propuesta_nucleo
{
	// Estados de Prupuesta
	const estado_activo = 'A';
	const estado_baja = 'B';

	// Tipos de propuesta.
    const tipo_vocacional = 207;
	const tipo_convenio = 209;
	const tipo_curso = 208;
	const tipo_grado = 200;
	const tipo_posgrado = 202;
	const tipo_curso_ingreso = 204;
			
	// Resultados de Situacion de Aspirantes
	const inscripcion_pendiente = 'P';  // Pendiente
	const inscripcion_aceptada  = 'A';  // Aceptada
	const inscripcion_rechazada = 'R';  // Rechazada
	const inscripcion_pendiente_descr = 'Pendiente';
	const inscripcion_aceptada_descr = 'Aceptada';
	const inscripcion_rechazada_descr = 'Rechazada';

        // Readmision
        const readmision_pendiente = 'P';  // Pendiente
        const readmision_aceptada = 'A';  // Aceptada
        const readmision_rechazada = 'R';  // Rechazada
        const readmision_pendiente_descr = 'Pendiente';
        const readmision_aceptada_descr = 'Aceptada';
        const readmision_rechazada_descr = 'Rechazada';
        
        // Situaciones de Aspirantes.
	const situacion_asp_insc_aspirante_a_prop = 1;
	const situacion_asp_insc_prop_aceptada = 2;
	const situacion_asp_insc_prop_rechazada_x_req = 4;

	// Tipos de Relaciones entre Propuestas.
	const relacion_tipo_propuestas = 1;
	const relacion_tipo_planes = 2;

	// Observaciones para inscripción automática a propuestas vinculadas.
	const obs_inscripcion_automatica = 'Inscripto automáticamente';
	
	// Código de área temática Araucano "Sin Área".
	const codigo_aracano_sin_area_tematica = 'SA';
	
	const debe_seleccionar_una_propuesta = "Debe seleccionar al menos una propuesta por la que desea solictar información al aspirante.";

	static function validar_relacion($persona, $relacion, $propuesta_tipo=null)
	{
		$grupos = toba::consulta_php('co_propuestas_relaciones')->get_grupos_por_relacion($relacion);
		foreach ($grupos as $grupo) {
			$grupo_cumplido = true;
			$certificado = null;
			$otro_alumno = null;
			$planes = toba::consulta_php('co_propuestas_relaciones')->get_planes_por_grupo($grupo['relacion_grupo'], $propuesta_tipo);
			foreach ($planes as $plan) {
				$datos_otro_alumno = toba::consulta_php('co_alumnos')->get_alumno($persona, $plan['propuesta']);
				if (empty($datos_otro_alumno)) { // Si el alumno no está inscripto en la propuesta...
					$grupo_cumplido = false;
				} else {
					$otro_alumno = $datos_otro_alumno['alumno'];
					list($grupo_cumplido, $certificado) = static::validar_plan($otro_alumno, $datos_otro_alumno['plan_version']);
				}
				if (! $grupo_cumplido) { // Si no se cumple alguna de las componentes del grupo, el grupo no está cumplido.
					break;
				}
			}
			if ($grupo_cumplido) { // Si se cumple alguno de los grupos, se cumple la relación.
				return array(true, $certificado, $otro_alumno);
			}
		}
		return array(false, null, null);
	}

	static function validar_plan($alumno, $plan_version)
	{
		$filtro = "	sga_certificados.estado = '" . comunes::estado_activo . "' AND
					sga_certificados.titulo_nivel = '" . tramite_certificacion::nivel_titulo_final . "'";
		$certificados = toba::consulta_php('co_certificados')->get_certificados_plan_version($plan_version, $filtro, true, false);
		$proceso_deteccion_egresado = toba::cn('cn_deteccion_egresado');
		foreach ($certificados as $certificado) {
			if ($proceso_deteccion_egresado->validar($alumno, $certificado['certificado'])) {
				return array(true, $certificado['certificado']);
			}
		}
		return array(false, null);
	}
}