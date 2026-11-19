<?php
namespace nsg01\operaciones\notas_cursada_comision;

use SIU\Chulupi\kernel;
use siu\errores\error_guarani;
use siu\modelo\_comun\comunes;
use \siu\modelo\entidades\parametro;
use siu\modelo\datos\catalogo;
use siu\modelo\entidades\persona;

// Newsol - Se agregaron el porcentaje de asistencia de cursada y promoci�n

class pagelet_renglones extends \siu\operaciones\notas_cursada_comision\pagelet_renglones
{
	protected $precondiciones;
	
	// Newsol - 06/2025 Se agregaron los campos de % de asistencia de cursada y promoci�n para poder saber qu� alumnos no cumplen con el % de asistencia de la comisi�n
	protected function get_renglones()
	{
		// Se pide al controlador para hacer validaci�n con los datos enviados al guardar
		$renglones = $this->controlador->get_pagina();
		// var_dump($renglones); // IGNORE
		if ($this->errores !== false && $this->errores->hay_renglones()) {
			$renglones_error = $this->errores->get_renglones();
			foreach ($renglones_error as $id => $renglon) {
				$renglones[$id]['error'] = $renglon['msg'];
				if ($renglon['error_concurrencia']) { // no se actualizan los renglones xq el cambio se invalid�
					$renglones[$id]['error_concurrencia'] = true;	
				} else {
					$renglones[$id]['nota_cursada'] = $renglon['renglon']['nota_cursada'];
					$renglones[$id]['cond_regularidad'] = $renglon['renglon']['cond_regularidad'];
					$renglones[$id]['fecha_regular'] = $renglon['renglon']['fecha_regular'];
					$renglones[$id]['resultado_cursada'] = $renglon['renglon']['resultado_cursada'];
					$renglones[$id]['nota_promocion'] = $renglon['renglon']['nota_promocion'];
					$renglones[$id]['fecha_promocion'] = $renglon['renglon']['fecha_promocion'];
					$renglones[$id]['resultado_promocion'] = $renglon['renglon']['resultado_promocion'];	
					$renglones[$id]['porc_asistencia'] = $renglon['renglon']['porc_asistencia'];
					$renglones[$id]['porc_asistencia_cursada'] = $renglon['renglon']['porc_asistencia_cursada'];	
					$renglones[$id]['porc_asistencia_promocion'] = $renglon['renglon']['porc_asistencia_promocion'];	
					$renglones[$id]['observacion_cursada'] = $renglon['renglon']['observacion_cursada'];
					$renglones[$id]['observacion_promocion'] = $renglon['renglon']['observacion_promocion'];
				}
				}
		}
		else {
			foreach ($renglones as $id => $renglon) {
				if (!is_null($renglones[$id]['porc_asistencia']) && ($renglones[$id]['porc_asistencia'] < $renglones[$id]['porc_asistencia_cursada'])){
					$renglones[$id]['resultado_cursada'] = 'R';		
					$renglones[$id]['cond_regularidad'] = 1;
				}

			}
			
		}

		return $renglones;
	}

}
?>