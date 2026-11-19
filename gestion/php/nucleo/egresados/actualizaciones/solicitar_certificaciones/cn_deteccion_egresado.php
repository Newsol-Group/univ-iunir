<?php
class cn_deteccion_egresado extends guarani_pers_cn
{
	protected $s__conjunto;

	/*
	 * Se contruye un árbol central (del plan de estudios).
	 * Se crea el árbol del certificado a evaluar, que se "engancha" del arbol del plan de estudios y setea reglas (por opción de cumplimiento) a los elementos que debe evaluar.
	 * La validación se lanza sobre el árbol del certificado.
	 */
	function validar($alumno, $certificado)
	{
		$datos_alumno = current(toba::consulta_php('co_alumnos')->get_datos_alumno($alumno, false));
		$this->s__conjunto = new conjunto_validable($datos_alumno['plan_version']);
		$arbol_certificado = $this->s__conjunto->get_arbol_certificado($datos_alumno['plan_version'], $certificado);
		$this->s__conjunto->set_certificado($certificado, $arbol_certificado);
		return $this->s__conjunto->validar($alumno);
	}

	/**
	 * 
	 * Si esta activada la configuración de historia académica (para permitir 
	 * excluir actividades de un certificado), se inicializa una estructura de
	 * control a partir de las actividades aprobadas por el alumno
	 * 
	 * @param int $alumno
	 */
	function inicializar_config_ha($alumno)
	{
		if (!toba::memoria()->existe_dato('__config_ha__')) {
			$config_ha = array();
			$datos = toba::consulta_php('co_alumnos')->get_historia_academica_basica_alumno($alumno);
			foreach ($datos as $elemento) {
				$config_ha[$elemento['elemento']]['aprobado'] = true;
				$config_ha[$elemento['elemento']]['incluido_ha'] = true;
			}

			// Se chequea si se trata de una edición
			$nro_solicitud = toba::cn('solicitar_certificaciones')->get_columna('nro_solicitud');
			if (!empty($nro_solicitud)) {
				$excluidas = toba::consulta_php('co_tramites_certificacion')->get_hist_acad_excluida($nro_solicitud);
				if (!empty($excluidas)) {
					foreach ($excluidas as $actividad) {
						if (!isset($config_ha[$actividad]['edicion'])) {
							$config_ha[$actividad]['edicion'] = true;
							$config_ha[$actividad]['incluido_ha'] = false;
						}
					}
				}
			}

			toba::memoria()->set_dato('__config_ha__', $config_ha);
		}
	}

	function get_observaciones()
	{
		return $this->s__conjunto->get_observaciones();
	}

	function get_config_ha()
	{
		return $this->s__conjunto->get_config_ha();
	}

}
?>