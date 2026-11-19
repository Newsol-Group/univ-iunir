<?php
/**
 * 
 *
 * @package		Propuestas
 * @subpackage	Validador Planes
 * 
 */
class actividad_validacion_nucleo extends elemento_validacion
{
	function validar($alumno, $opcion)
	{
		if (isset($this->regla) && array_key_exists($opcion, $this->regla)) {
			$this->regla[$opcion]->set_parametros_contexto(array('elemento' => $this->get_id(), 'alumno' => $alumno, 'plan_version' => $this->get_prop('plan_version'), 'fecha' => guarani_fecha::get_hoy()));
			$cumplido = $this->regla[$opcion]->validar();

			$propuesta = toba::consulta_php('co_alumnos')->get_alumno_propuesta($alumno);

			if (guarani::parametros()->get_valor('egr_configurar_ha', $propuesta, false) == comunes::no) {
				$this->cumplido[$opcion] = $cumplido;
			} else {
				$incluido_ha = true;
				if (toba::memoria()->existe_dato('__config_ha__')) {
					$config_ha = toba::memoria()->get_dato('__config_ha__');
					$incluido_ha = !empty($config_ha[$this->get_id()]['incluido_ha']) && ($config_ha[$this->get_id()]['incluido_ha']);
				}
				$this->cumplido[$opcion] = $incluido_ha && $cumplido;
			}
		}
	}

	function validar_caso_test($caso_test, $opcion)
	{
		if (isset($this->regla) && array_key_exists($opcion, $this->regla)) {
			$this->regla[$opcion]->set_parametros_contexto(array('elemento' => $this->get_id(), 'alumno' => alumno::id_alumno_ficticio, 'plan_version' => $this->get_prop('plan_version'), 'fecha' => guarani_fecha::get_hoy()));
			$this->cumplido[$opcion] = $this->regla[$opcion]->validar_caso_test($caso_test);
		}
	}

	function get_config_ha($opcion)
	{
		$texto = '<li>';
		$texto .= "<div style='font-weight: bold'>";

		// ¿Requisito cumplido? => se muestra el ícono correspondiente (tilde o cruz)
		if (isset($this->cumplido[$opcion])) {
			$texto .= $this->cumplido[$opcion] ? toba_recurso::imagen_toba('tilde.gif', true) : toba_recurso::imagen_toba('error.png', true);
		}

		// ¿Es factible de exclusión de la hist. acad.? => check 
		$config_ha = toba::memoria()->get_dato('__config_ha__');
		if (!empty($config_ha[$this->get_id()])) {
			$texto .= ' ';
			$elemento = $this->get_id();
			$class = 'ha_' . $elemento;
			$texto .= "<span class='js-actividad_toggle $class' onclick='config_ha_toggle($elemento)'>";
			if ($config_ha[$this->get_id()]['incluido_ha']) {
				$texto .= toba_recurso::imagen_proyecto('check-si.png', true, null, null, 'Incluido en la historia académica');
			} else {
				$texto .= toba_recurso::imagen_proyecto('check-no.png', true, null, null, 'NO incluido en la historia académica');
			}
			$texto .= "</span>";
		}

		// Nombre de la actividad
		$texto .= ' ' . $this->get_nombre();

		$texto .= '</div>';
		$texto .= '</li>';
		return $texto;
	}

}
?>