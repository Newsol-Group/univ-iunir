<?php
namespace siu\operaciones\inscripciones;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;

class pagelet_examenes extends pagelet
{	
	
	const TIPO = 'examen';
	
	function ini()
	{
	}
	
	function get_nombre()
	{
		return 'examenes';
	}
	
	function get_alcance() 
	{
		return $this->controlador->get_alcance_examenes();
	}
	
	/**
	 * Shortcut al modelo
	 * @return \siu\modelo\transacciones\inscripciones
	 */
	protected function modelo()
	{
		return $this->controlador->modelo();
	}
	
	function datos()
	{
		
		$operacion = kernel::ruteador()->get_id_operacion();

		$examenes = $this->datos_consulta = $this->modelo()->info__inscripciones_examenes($this->get_alcance());
		foreach($examenes as $key => $examen)
		{
			$examenes[$key]['url_comp'] = $this->get_vinculo_comp(substr($examen['estado_nombre'], 0, 1), $examen['url_inscripcion']);
			if($examen['estado'] == 'R')
			{
				$examenes[$key]['motivo'] = catalogo::consultar('inscripcion_examen', 'get_motivo_rechazo_inscripcion', array('inscripcion_rechazada' => $examen['inscripcion']));
			}
			
			// Si el exámen tiene un curso asociado en Moodle y el estado de la inscripción es aceptada o pendiente, genero la URL del curso en Moodle
			if ($examen['moodle_curso'] && in_array($examen['estado'], [\inscripcion_examen::estado_aceptada, \inscripcion_examen::estado_pendiente])) {
				$examenes[$key]['url_curso_moodle'] = kernel::vinculador()->crear($operacion, 'curso_moodle', [static::TIPO, $examen['url_inscripcion']]);
			}
		}

		return $examenes;
	}

	protected function get_vinculo_comp($estado, $inscripcion)
	{
		return kernel::vinculador()->crear(
			'inscripciones',
			'comprobante',
			array(
				'tipo' => 'E',
				'estado' => $estado,
				'inscripcion' => $inscripcion,
			)
		);
	}

	function prepare()
	{
		$operacion = kernel::ruteador()->get_id_operacion();
		$this->add_var_js('url_examenes', kernel::vinculador()->crear($operacion, 'examenes'));
		$this->data['examenes'] = $this->datos();
	}
}