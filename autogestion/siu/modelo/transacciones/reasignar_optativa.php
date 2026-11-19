<?php

namespace siu\modelo\transacciones;

use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;

class reasignar_optativa extends transaccion_alumno
{
	protected $operacion = '800SIUMAT047';
	protected $optativas_en_mas_de_una_generica;

	function get_optativas_en_mas_de_una_generica()
	{

		if (!isset($this->optativas_en_mas_de_una_generica)) {

			$optativas_con_genericas = catalogo::consultar('reasignar_optativa', 'lista_optativas_con_genericas', array(
						'plan_version' => $this->plan_version,
						'alumno' => $this->alumno
			));
			$generica_del_certificado = \toba::consulta_php('co_alumnos')->get_generica_certificado($this->alumno);
			$optativas_final = array();


			foreach ($optativas_con_genericas as $optativa) {
				if ($optativa['estado'] == 'B' && $optativa['optativa_estado'] == '') {
					//no muestra optativa dada de baja y sin cursar
				} else {

					if (!isset($optativas_final[$optativa['optativa_elemento']])) {
						$optativas_final[$optativa['optativa_elemento']]['elemento'] = $optativa['optativa_elemento'];
						$optativas_final[$optativa['optativa_elemento']]['codigo'] = $optativa['optativa_codigo'];
						$optativas_final[$optativa['optativa_elemento']]['nombre'] = $optativa['optativa_nombre'];
						$optativas_final[$optativa['optativa_elemento']]['estado'] = $optativa['optativa_estado'];
						$optativas_final[$optativa['optativa_elemento']]['generica_asignada'] = kernel::traductor()->trans('reasignar_optativa.sin_asignacion');
					}
					$optativas_final[$optativa['optativa_elemento']]['certificado'] = \comunes::no;

					if ($optativa['generica_asignada'] == \comunes::si) {
						$optativas_final[$optativa['optativa_elemento']]['generica_asignada'] = $optativa['generica_nombre'];
						if (in_array($optativa['generica_elemento'], array_column($generica_del_certificado, 'elemento'))) {
							$optativas_final[$optativa['optativa_elemento']]['certificado'] = \comunes::si;
						}
					}

					$datos_generica = array(
						'elemento' => $optativa['generica_elemento'],
						'codigo' => $optativa['generica_codigo'],
						'nombre' => $optativa['generica_nombre'],
						'asignada' => ($optativa['generica_asignada'] == \comunes::si) ? true : false,
					);
					$optativas_final[$optativa['optativa_elemento']]['genericas'][$optativa['generica_elemento']] = $datos_generica;
					if (!in_array($optativa['generica_elemento'], array_column($generica_del_certificado, 'elemento'))) {
						$optativas_final[$optativa['optativa_elemento']]['genericas'][$optativa['generica_elemento']] ['certificado'] = \comunes::no;
					} else {
						$optativas_final[$optativa['optativa_elemento']]['genericas'][$optativa['generica_elemento']] ['certificado'] = \comunes::si;
					}
				}
			}
			$this->optativas_en_mas_de_una_generica = $optativas_final;
			kernel::log()->add_debug('=== reasignar_optativa/get_optativas_en_mas_de_una_generica ===', $this->optativas_en_mas_de_una_generica);
		}


		return $this->optativas_en_mas_de_una_generica;
	}

	function limpiar_lista_optativas_con_genericas()
	{

		return catalogo::limpiar_cache('reasignar_optativa', 'lista_optativas_con_genericas', array(
					'plan_version' => $this->plan_version,
					'alumno' => $this->alumno
		));
	}

	function optativa_tiene_generica_asignada($optativa)
	{

		return catalogo::consultar('reasignar_optativa', 'optativa_tiene_generica_asignada', array(
					'alumno' => $this->alumno,
					'plan_version' => $this->plan_version,
					'optativa' => $optativa
		));
	}

	function limpiar_optativa_tiene_generica_asignada($optativa)
	{

		return catalogo::limpiar_cache('reasignar_optativa', 'optativa_tiene_generica_asignada', array(
					'alumno' => $this->alumno,
					'plan_version' => $this->plan_version,
					'optativa' => $optativa
		));
	}

	function limpiar_plan_estudio()
	{

		return catalogo::limpiar_cache('alumno', 'get_contenido_plan_version', array(
					'plan_version' => $this->plan_version,
					'alumno' => $this->alumno
		));
	}

	function agregar_asignacion($optativa, $generica)
	{

		return catalogo::consultar('reasignar_optativa', 'agregar_asignacion', array(
					'alumno' => $this->alumno,
					'plan_version' => $this->plan_version,
					'optativa' => $optativa,
					'generica' => $generica,
		));
	}

	function modificar_asignacion($optativa, $generica)
	{

		return catalogo::consultar('reasignar_optativa', 'modificar_asignacion', array(
					'alumno' => $this->alumno,
					'plan_version' => $this->plan_version,
					'optativa' => $optativa,
					'generica' => $generica,
		));
	}

	function guardar_asignaciones_optativas($optativas)
	{

		$hubo_error = false;

		try {

			$cant_cambios = 0;

			// Obtengo el listado de optativas con sus respectivas genericas
			$optativas_en_mas_de_una_generica = $this->get_optativas_en_mas_de_una_generica();

			kernel::db()->abrir_transaccion();

			foreach ($optativas as $optativa => $data) {

				$generica = $data['generica'];

				// Verifico si el alumno no seleccionó una generica del select
				$generica_no_seleccionada = empty($generica);

				// Verifico si la optativa existe para ese plan
				$existe_optativa = array_key_exists($optativa, $optativas_en_mas_de_una_generica);

				$genericas_de_la_optativa = $optativas_en_mas_de_una_generica[$optativa]['genericas'];
				// Verifico si la generica existe para ese plan y optativa en cuestión
				$existe_generica = array_key_exists($generica, $genericas_de_la_optativa);

				// Si el alumno no seleccionó una generica, o seleccionó una combinación de optativa/generica inexistente no guardo
				if ($generica_no_seleccionada || (!$existe_optativa) || (!$existe_generica)) {
					kernel::log()->add_debug('=== reasignar_optativa/ignorando optativa ===', $optativa);
					continue;
				}

				if ($this->optativa_tiene_generica_asignada($optativa)) {
					$this->modificar_asignacion($optativa, $generica);
				} else {
					$this->agregar_asignacion($optativa, $generica);
					$this->limpiar_optativa_tiene_generica_asignada($optativa);
				}

				$cant_cambios++;
			}

			kernel::db()->cerrar_transaccion();

			// Si hubo cambios
			if ($cant_cambios > 0) {
				// Limpio cache del listado y plan de estudio
				$this->limpiar_lista_optativas_con_genericas();
				$this->limpiar_plan_estudio();
			}
		} catch (\Exception $e) {
			kernel::db()->abortar_transaccion();
			kernel::log()->add_error($e);
			$hubo_error = true;
		}

		kernel::log()->add_debug('=== reasignar_optativa/hubo_error ===', $hubo_error);
		kernel::log()->add_debug('=== reasignar_optativa/cant_cambios ===', $cant_cambios);

		return array($hubo_error, $cant_cambios);
	}

}