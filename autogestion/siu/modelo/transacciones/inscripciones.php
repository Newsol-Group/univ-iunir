<?php

namespace siu\modelo\transacciones;

use siu\modelo\datos\catalogo;
use SIU\Chulupi\kernel;
use siu\errores\error_guarani_seguridad;

class inscripciones extends transaccion
{
	
	protected $inscripciones_cursadas;
	protected $inscripciones_examenes;
	
	function info__inscripciones_cursadas($alcance)
	{
		if (!isset($this->inscripciones_cursadas)) {
			
			$parametros = [];
			$parametros['persona'] = $this->persona;
			$parametros['estado'] = "T";
			$parametros['cursos'] = kernel::persona()->es_perfil_cursos();
			$parametros['vigentes'] = ($alcance == "T")? \comunes::no : \comunes::si;

			$this->inscripciones_cursadas = catalogo::consultar('alumno', 'inscripciones_cursadas', $parametros);
		}
		
		return $this->inscripciones_cursadas;
	}
	
	/**
	 * Busca inscripción a cursada por hash de inscripción
	 *
	 * @param string $hash_inscripcion Hash de inscripción a buscar, ej: "70976874d56bbd9574e42b84e5a14e328fbd5e69"
	 * @param string $alcance "T" para "Inscripciones históricas", "A" para "Inscripciones de Períodos lectivos vigentes" 
	 *
	 * @throws error_guarani_seguridad si no se encuentra la inscripción a cursada
	 *
	 * @return array Datos de la inscripción a cursada
	 */
	function info__inscripcion_cursada_from_hash($hash_inscripcion, $alcance)
	{
		$inscripciones_cursadas = $this->info__inscripciones_cursadas($alcance);
		
		foreach ($inscripciones_cursadas as $inscripcion_cursada) {
			if ($inscripcion_cursada['url_inscripcion'] === $hash_inscripcion) {
				return $inscripcion_cursada;
			}
		}
		
		throw new error_guarani_seguridad('OPCIONES ENVIADAS: No existe la inscripción');
	}
	
	function info__inscripciones_examenes($alcance)
	{
		if (!isset($this->inscripciones_examenes)) {
			
			$parametros = [];
			$parametros['persona'] = $this->persona;
			$parametros['estado'] = "T";
			$parametros['vigentes'] = $alcance;

			$this->inscripciones_examenes = catalogo::consultar('alumno', 'inscripciones_examenes', $parametros);
		}
		
		return $this->inscripciones_examenes;
	}
	
	/**
	 * Busca inscripción a examen por hash de inscripción
	 *
	 * @param string $hash_inscripcion Hash de inscripción a buscar, ej: "70976874d56bbd9574e42b84e5a14e328fbd5e69"
	 * @param string $alcance "T" para "Inscripciones históricas", "S" para "Inscripciones de Turnos de exámenes vigentes " 
	 *
	 * @throws error_guarani_seguridad si no se encuentra la inscripción a examen
	 *
	 * @return array Datos de la inscripción a examen
	 */
	function info__inscripcion_examen_from_hash($hash_inscripcion, $alcance)
	{
		$inscripciones_examenes = $this->info__inscripciones_examenes($alcance);
		
		foreach ($inscripciones_examenes as $inscripcion_examen) {
			if ($inscripcion_examen['url_inscripcion'] === $hash_inscripcion) {
				return $inscripcion_examen;
			}
		}
		
		throw new error_guarani_seguridad('OPCIONES ENVIADAS: No existe la inscripción');
	}
}