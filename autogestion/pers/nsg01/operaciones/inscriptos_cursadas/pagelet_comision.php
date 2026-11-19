<?php
namespace nsg01\operaciones\inscriptos_cursadas;

use SIU\Chulupi\kernel;
use siu\operaciones\_comun\operaciones\reporte\pagelet_reporte;
use siu\modelo\entidades\docente;

class pagelet_comision extends \siu\operaciones\inscriptos_cursadas\pagelet_comision
{
	
	public function get_columnas()
	{
		$titulo = array();
		$titulo['orden'] = array("", array('justification' => 'center', 'width' => 70));
		$titulo['legajo'] = array(kernel::traductor()->trans('inscriptos_cursadas.legajo'), array('justification' => 'left', 'width' => 25));
		$titulo['apellido_nombres'] = array(kernel::traductor()->trans('inscriptos_cursadas.alumno'), array('justification' => 'left', 'width' => 25));
		$titulo['estado_inscripcion'] = array(kernel::traductor()->trans('inscriptos_cursadas.estado'), array('justification' => 'left', 'width' => 25));
		$titulo['instancias'] = array(kernel::traductor()->trans('inscriptos_cursadas.instancias'), array('justification' => 'left', 'width' => 25));

                $comision = $this->controlador->getComision();
                $visualiza_datos_contacto = docente::visualiza_datos_contacto_por_comision($comision);
                
                // Si el docente puede visualizar los datos de contacto de los alumnos (email y teléfono)
                if ($visualiza_datos_contacto) {
                    $titulo['email'] = array(kernel::traductor()->trans('inscriptos_cursadas.email'), array('justification' => 'left', 'width' => 25));
                    $titulo['telefono'] = array(kernel::traductor()->trans('inscriptos_cursadas.telefono'), array('justification' => 'left', 'width' => 25));
		    $titulo['moroso'] = array(kernel::traductor()->trans('inscriptos_cursadas.moroso'), array('justification' => 'left', 'width' => 25));

                }

		return $titulo;
	}

	public function get_data()
	{

		if (isset($this->subcomision)) {
			$datos = $this->controlador->get_info_subcomision();
		} else {
			$datos = $this->controlador->get_info_comision();
		}

                $i = 1;

                $msj_sin_definir = kernel::traductor()->trans('inscriptos_cursadas.sin_definir');

		foreach ($datos as $key => $dato) {
			$datos[$key]['legajo'] = $dato['legajo'] ?? $msj_sin_definir;
			$datos[$key]['apellido_nombres'] = $dato['apellido_nombres'] ?? $msj_sin_definir;
			$datos[$key]['estado_inscripcion'] = $dato['estado_inscripcion'] ?? $msj_sin_definir;
			$datos[$key]['instancias'] = $dato['instancias'];
			$datos[$key]['email'] = $dato['email'] ?? $msj_sin_definir;
			$datos[$key]['telefono'] = $dato['telefono'] ?? $msj_sin_definir;
			$datos[$key]['moroso'] = $dato['moroso'] ?? $msj_sin_definir;
			$datos[$key]['orden'] = $i++;
		}

                return $datos;
	}

}