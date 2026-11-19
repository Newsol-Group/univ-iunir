<?php
/**
 * 
 *
 * @package		Propuestas
 * @subpackage	Validador Planes
 * 
 */
class modulo_validacion_nucleo extends elemento_validacion
{
	protected $hijos;

	function agregar_hijo($e)
	{
		$this->hijos[] = $e;
	}

	function get_hijos()
	{
		return $this->hijos;
	}

	function validar($alumno, $opcion)
	{
		if (isset($this->regla) && array_key_exists($opcion, $this->regla)) {
			$this->regla[$opcion]->set_parametros_contexto(array('elemento' => $this->get_id(), 'alumno' => $alumno, 'plan_version' => $this->get_prop('plan_version')));
			
			$propuesta = toba::consulta_php('co_alumnos')->get_alumno_propuesta($alumno);
			
			if (guarani::parametros()->get_valor('egr_configurar_ha', $propuesta, false) == comunes::no) {
				$this->cumplido[$opcion] = $this->regla[$opcion]->validar();
			} else {
				$excluidas = $this->get_actividades_excluidas_ha();
				$this->cumplido[$opcion] = $this->regla[$opcion]->validar_con_config_ha($excluidas);
			}
		}
	}

	function validar_caso_test($caso_test, $opcion)
	{
		if (isset($this->regla) && array_key_exists($opcion, $this->regla)) {
			$this->regla[$opcion]->set_parametros_contexto(array('elemento' => $this->get_id(), 'alumno' => alumno::id_alumno_ficticio, 'plan_version' => $this->get_prop('plan_version')));
			$this->cumplido[$opcion] = $this->regla[$opcion]->validar_caso_test($caso_test);
		}
	}

	function search($id)
	{
		$ret = parent::search($id);
		if (is_null($ret)) {
			foreach ($this->get_hijos() as $hijo) {
				$ret = $hijo->search($id);
				if (!is_null($ret)) break;
			}
		}
		return $ret;
	}

	function get_observaciones($opcion)
	{
		$texto = '<li>';
		$texto .= "<div style='font-weight: bold'>";
		if (isset($this->cumplido[$opcion])) {
			$texto .= $this->cumplido[$opcion] ? toba_recurso::imagen_toba('tilde.gif',true) : toba_recurso::imagen_toba('error.png',true);
		}
		$texto .= ' '.$this->get_nombre().'</div>';
		if (isset($this->regla[$opcion])) {
			$texto .= '<div>'. $this->regla[$opcion]->get_mensaje().'</div>';	
		}
		if (guarani_arreglos::count($this->hijos) > 0) {
			$texto .= '<ul>';
			foreach ($this->hijos as $hijo) {
				$texto .= $hijo->get_observaciones($opcion);
			}
			$texto .= '</ul>';
		}
		$texto .= '</li>';
		return $texto;
	}
	
	function get_config_ha($opcion)
	{
		$texto = '<li>';
		$texto .= "<div style='font-weight: bold'>";
		if (isset($this->cumplido[$opcion])) {
			$texto .= $this->cumplido[$opcion] ? toba_recurso::imagen_toba('tilde.gif',true) : toba_recurso::imagen_toba('error.png',true);
		}
		$texto .= ' '.$this->get_nombre().'</div>';
		if (isset($this->regla[$opcion])) {
			$texto .= '<div>'. $this->regla[$opcion]->get_mensaje().'</div>';	
		}
		if (guarani_arreglos::count($this->hijos) > 0) {
			$texto .= '<ul>';
			foreach ($this->hijos as $hijo) {
				$texto .= $hijo->get_config_ha($opcion);
			}
			$texto .= '</ul>';
		}
		$texto .= '</li>';
		return $texto;
	}
	
	//--------------------------------------------------------------------------
	
	/**
	 * Obtiene las actividades a excluir de la Hist. Acad. (si las hay)
	 */
	function get_actividades_excluidas_ha()
	{
		if (toba::memoria()->existe_dato('__config_ha__')) {	
			$config_ha = toba::memoria()->get_dato('__config_ha__');
			$excluidas = array();
			foreach ($config_ha as $id => $actividad) {
				if (isset($actividad['incluido_ha']) && (!$actividad['incluido_ha'])) {
					$excluidas[] = $id;
				}
			}
			return $excluidas;
		} else {
			return array();
		}
	}
}
?>