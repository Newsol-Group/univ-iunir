<?php

namespace siu\operaciones\perfil_datos;

use SIU\Chulupi\kernel;
use siu\modelo\lib\abm;
use siu\modelo\g3\g3;
use siu\modelo\util;

class modelo
{
	function get_configuracion_resultante()
	{
		$salida = array();
		$ras = g3::responsables_academicas()->get_opciones();
		foreach ($ras as $ra => $nombre_ra) {
			if (!is_null($ra) && ($ra != 'null')) {
				$salida[$ra] = array(
					'nombre' => $nombre_ra,
					'propuestas' => array(),
				);
				$propuestas = g3::propuestas()->get_propuestas_disponibles_preinscripcion($ra);
				foreach ($propuestas as $propuesta => $propuesta_nombre) {
					if (!is_null($propuesta) && ($propuesta != 'null')) {
						$salida[$ra]['propuestas'][$propuesta] = array(
							'nombre' => $propuesta_nombre,
							'ubicaciones' => array(),
						);
						$ubicaciones = g3::propuestas()->get_ubicaciones_propuesta($propuesta);
						foreach ($ubicaciones as $ubicacion => $nombre_ubicacion) {
							if (!is_null($ubicacion) && ($ubicacion != 'null')) {
								$salida[$ra]['propuestas'][$propuesta]['ubicaciones'][] = $nombre_ubicacion;
							}
						}
					}
				}
			}
		}
		return $salida;
	}
	
	//--------------------------------------------------------------------------
	//---- DATOS ---------------------------------------------------------------
	//--------------------------------------------------------------------------
	
	function get_responsables_academicas()
	{
		return g3::responsables_academicas()->get_opciones_sin_perfil_datos();
	}
	
	function get_tipos_propuestas()
	{
		return g3::propuestas()->get_tipos_propuesta_sin_perfil_datos();
	}
	
	function get_propuestas()
	{
		return g3::propuestas()->get_propuestas_sin_perfil_datos();
	}
	
	function get_ubicaciones()
	{
		return g3::entidad('ubicaciones')->get_opciones(null, false);
	}
	
	function get_datos_configuracion()
	{
		$config = util::consultas()->get_config_perfil_datos();
		$res = array();
		// Responsables Académicas seleccionadas
		$ras = $config['ra'];
		if (!empty($ras)) {
			foreach ($ras as $ra) {
				$clave = 'ra_' . $ra;
				$res[$clave] = 1;
			}
		}
		// Tipos de propuestas seleccionadas
		$propuestas_tipo = $config['propuesta_tipo'];
		if (!empty($propuestas_tipo)) {
			foreach ($propuestas_tipo as $propuesta_tipo) {
				$clave = 'propuesta_tipo_' . $propuesta_tipo;
				$res[$clave] = 1;
			}
		}
		// Propuestas seleccionadas
		$propuestas = $config['propuesta'];
		if (!empty($propuestas)) {
			foreach ($propuestas as $propuesta) {
				$clave = 'propuesta_' . $propuesta;
				$res[$clave] = 1;
			}
		}
		// Ubicaciones seleccionadas
		$ubicaciones = $config['ubicacion'];
		if (!empty($ubicaciones)) {
			foreach ($ubicaciones as $ubicacion) {
				$clave = 'ubicacion_' . $ubicacion;
				$res[$clave] = 1;
			}
		}
		return $res;
	}
	
	//--------------------------------------------------------------------------
	//---- PERSISTENCIA --------------------------------------------------------
	//--------------------------------------------------------------------------
	
	function guardar_config($datos)
	{
		try {
			kernel::db()->abrir_transaccion();

			$id_pto_acceso = $_SERVER['ID_PTO_ACCESO'];
			
			// Se eliminan configuraciones anteriores 
			$clave = array('id_pto_acceso' => $id_pto_acceso);
			abm::baja('cfg_perfiles_datos', $clave);
			
			// Se insertan las nuevas configuraciones
			$ras = $this->get_responsables_academicas();
			$this->procesar_grupo($datos, $ras, 'ra', $id_pto_acceso);
			
			$tipos_propuesta = $this->get_tipos_propuestas();
			$this->procesar_grupo($datos, $tipos_propuesta, 'propuesta_tipo', $id_pto_acceso);
			
			$propuestas = $this->get_propuestas();
			$this->procesar_grupo($datos, $propuestas, 'propuesta', $id_pto_acceso);
			
			$ubicaciones = $this->get_ubicaciones();
			$this->procesar_grupo($datos, $ubicaciones, 'ubicacion', $id_pto_acceso);

			kernel::db()->cerrar_transaccion();
		} catch (Exception $e) {
			kernel::db()->abortar_transaccion();
			throw $e;
		}
	}
	
	protected function procesar_grupo($datos, $opciones, $grupo, $id_pto_acceso)
	{
		$seleccion = array();
		foreach ($opciones as $id => $nombre) {
			$clave = $grupo . '_' . $id;
			if (!empty($datos[$clave])) {
				$seleccion[] = $id;
			}
		}
		if (!empty($seleccion)) {
			foreach ($seleccion as $clave) {
				$registro = array(
					'id_pto_acceso' => $id_pto_acceso,
					$grupo => $clave,
				);
				abm::alta('cfg_perfiles_datos', $registro);
			}
		}
	}

}
?>