<?php
namespace siu\operaciones\inscripciones;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;

class pagelet_cursadas extends pagelet
{	
	
	const TIPO = 'cursada';

	function ini()
	{
	}
	
	function get_nombre()
	{
		return 'cursadas';
	}
	
	function get_alcance() 
	{
		return $this->controlador->get_alcance_cursadas();
	}

	/**
	 * Shortcut al modelo
	 * @return \siu\modelo\transacciones\inscripciones
	 */
	protected function modelo()
	{
		return $this->controlador->modelo();
	}
	
	function datos () 
	{
		$operacion = kernel::ruteador()->get_id_operacion();

		$this->datos_consulta = $this->modelo()->info__inscripciones_cursadas($this->get_alcance());
		foreach($this->datos_consulta as $key => $cursada)
		{
			$this->datos_consulta[$key]['url_comp'] = $this->get_vinculo_comp($cursada['estado'], $cursada['url_inscripcion'], kernel::persona()->es_perfil_cursos());
			if($cursada['estado'] == 'R')
			{
				$this->datos_consulta[$key]['motivo'] = catalogo::consultar('inscripcion_cursada', 'get_motivo_rechazo_inscripcion', array('inscripcion_rechazada' => $cursada['inscripcion']));
			}
			
			// Si la comisión tiene un curso asociado en Moodle y el estado de la inscripción es aceptada o pendiente, genero la URL del curso en Moodle
			if ($cursada['moodle_curso'] && in_array($cursada['estado'], [\inscripcion_cursada::estado_aceptada, \inscripcion_cursada::estado_pendiente])) {
				$this->datos_consulta[$key]['url_curso_moodle'] = kernel::vinculador()->crear($operacion, 'curso_moodle', [static::TIPO, $cursada['url_inscripcion']]);
			}
		}
		return $this->datos_consulta;
	}
	
	function prepare()
	{
		$operacion = kernel::ruteador()->get_id_operacion();
		$this->add_var_js('url_cursadas', kernel::vinculador()->crear($operacion, 'cursadas'));
		$this->data['cursadas'] = $this->datos();
		$this->add_var_js('url_ver_subcomisiones', kernel::vinculador()->crear($operacion, 'ver_subcomisiones'));
	}

	protected function get_vinculo_comp($estado, $inscripcion, $cursos)
	{
		return kernel::vinculador()->crear(
			'inscripciones',
			'comprobante',
			array(
				//CU: genero url a impresion de comprobantes de cursos
				//C: genero url a impresion de comprobante de cursadas
				'tipo' => $cursos ? 'CU' : 'C',
				'estado' => $estado,
				'inscripcion' => $inscripcion,
			)
		);
	}
}