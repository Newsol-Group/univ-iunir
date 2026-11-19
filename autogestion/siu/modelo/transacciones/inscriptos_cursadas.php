<?php
namespace siu\modelo\transacciones;

use SIU\Chulupi\kernel;

use siu\guarani;
use siu\modelo\controles\controles;
use siu\modelo\datos\estrategias;
use siu\modelo\datos\catalogo;
use siu\modelo\entidades\rs_arreglo;

use siu\modelo\entidades\parametro;
use siu\modelo\controles\proceso_control;
use siu\modelo\datos\util;
use siu\modelo\_comun\comunes;

class inscriptos_cursadas extends transaccion
{

	//---------------------------------------------
	//	INFO
	//---------------------------------------------
	function info__lista_comisiones($datos)
	{

		//$datos = isset($this->datos_filtro)? $this->datos_filtro: array();
		$anio_academico = isset($datos['anio_academico']) ? $datos['anio_academico'] : null; //todo: poner default
		$docente = isset($datos['docente']) ? $datos['docente'] : kernel::persona()->get_id_docente();
		$periodo_lectivo = isset($datos['periodo_lectivo']) && $datos['periodo_lectivo'] != '' ? array($datos['periodo_lectivo']) : null;
		$actividad = isset($datos['actividad']) ? $datos['actividad'] : null;
		$ubicacion = isset($datos['ubicacion']) ? $datos['ubicacion'] : null;

		
		$data = catalogo::consultar('docente', 'lista_comisiones', array(
			'anio_academico' => $anio_academico,
			'docente' => $docente,
			'periodo_lectivo' => $periodo_lectivo,
			'actividad' => $actividad,
			'ubicacion' => $ubicacion,
		));
		$rs = new rs_arreglo($data);
		$rs->set_columna_id('id');
		return $rs;
	}

	function info__comision_cabecera($comision)
	{
		$parametros = array('comision' => $comision);
		$datos = catalogo::consultar('docente', 'comision_cabecera', $parametros);
		return $datos[0];
	}


	function info__comision($comision)
	{
		$parametros = array('comision' => $comision);
		return catalogo::consultar('docente', 'alum_inscriptos_comision', $parametros);
	}

	function info__subcomision($comision, $subcomision)
	{
		$parametros = array('subcomision' => $subcomision, 'comision' => $comision);
		return catalogo::consultar('docente', 'alum_inscriptos_subcomision', $parametros);
	}

	function info__lista_comisiones_rech($datos)
	{
		$docente = isset($datos['docente']) ? $datos['docente'] : kernel::persona()->get_id_docente();
		$periodo_lectivo = isset($datos['periodo_lectivo']) ? $datos['periodo_lectivo'] : null;
		$actividad = isset($datos['actividad']) ? $datos['actividad'] : null;
		$ubicacion = isset($datos['ubicacion']) ? $datos['ubicacion'] : null;

		$data = catalogo::consultar('docente', 'lista_comisiones_rech', array(
			'docente' => $docente,
			'periodo_lectivo' => $periodo_lectivo,
			'actividad' => $actividad,
			'ubicacion' => $ubicacion,
		));
		$rs = new rs_arreglo($data);
		$rs->set_columna_id('comision');
		return $rs;
	}
}