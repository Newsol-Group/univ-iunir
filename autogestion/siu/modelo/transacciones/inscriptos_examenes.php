<?php
namespace siu\modelo\transacciones;

use SIU\Chulupi\kernel;

use siu\modelo\datos\catalogo;
use siu\modelo\entidades\rs_arreglo;

class inscriptos_examenes extends transaccion
{

	//---------------------------------------------
	//	INFO
	//---------------------------------------------
	function info__mesa_examen($datos)
	{
		$data = catalogo::consultar('docente', 'datos_mesa_examen', array('llamado_mesa' => $datos['llamado_mesa']));
		return $data;
	}

	function info__alumnos_examen($datos)
	{
                $parametros = [
                    'llamado_mesa' => $datos['llamado_mesa'],
                    'incluir_otros_alumnos' => true,
                    'evaluacion' => $datos['evaluacion']
                ];
                $datos = catalogo::consultar('docente', 'alum_inscriptos_examen', $parametros);
                
                $msj_sin_definir = kernel::traductor()->trans('inscriptos_examen.sin_definir');

		foreach ($datos as $key => $dato) {
			$datos[$key]['legajo'] = $dato['legajo'] ?? $msj_sin_definir;
			$datos[$key]['nombre_alumno'] = $dato['nombre_alumno'] ?? $msj_sin_definir;
			$datos[$key]['instancia_nombre'] = $dato['instancia_nombre'] ?? $msj_sin_definir;
			$datos[$key]['estado_inscripcion'] = $dato['estado_inscripcion'] ?? $msj_sin_definir;
			$datos[$key]['email'] = $dato['email'] ?? $msj_sin_definir;
			$datos[$key]['telefono'] = $dato['telefono'] ?? $msj_sin_definir;
		}

                return $datos;
	}
	
	function info__examenes($actividad, $alumno)
	{
		$datos = array();
		$datos['alumno'] = catalogo::consultar('alumno', 'get_datos_ficha', array('alumno' => $alumno));
		$datos['ficha'] = catalogo::consultar('alumno', 'get_datos_ultima_cursada_actividad', array('alumno' => $alumno, 'actividad' => $actividad));
		return $datos;
	}	

	function info__lista_mesas($datos)
	{
		$docente = !empty($datos['docente']) ? $datos['docente'] : kernel::persona()->get_id_docente();
		$turno = !empty($datos['turnos']) ? $datos['turnos'] : null;
		$actividad = isset($datos['actividad']) ? $datos['actividad'] : null;
		$sede = isset($datos['sede']) ? $datos['sede'] : null;
		$desde = isset($datos['desde']) ? $datos['desde'] : null;
		$hasta = isset($datos['hasta']) ? $datos['hasta'] : null;
		$mesas_con_inscriptos = isset($datos['mesas_con_inscriptos']) ? $datos['mesas_con_inscriptos'] : null;
		
		$data = catalogo::consultar('docente', 'lista_mesas', array(
			'docente' => $docente,
			'turno_examen' => $turno === false ? null : $turno,
			'actividad' => empty($actividad) ? null : $actividad,
			'ubicacion' => empty($sede) ? null : $sede,
			'fecha_desde' => empty($desde) ? date('Y-m-d') : date('Y-m-d', strtotime(str_replace('/', '-', $desde))),
			'fecha_hasta' => empty($hasta) ? date('Y-m-d') : date('Y-m-d', strtotime(str_replace('/', '-', $hasta))),
			'anio_academico' => null,
			'mostrar_mesas_fuera_calendario' => null,
			'mesas_con_inscriptos' => $mesas_con_inscriptos
		));
		
		$rs = new rs_arreglo($data);
		$rs->set_columna_id('llamado_mesa');
		return $rs;
	}
}