<?php
namespace siu\modelo\transacciones;

use acta;
use SIU\Chulupi\ErrorKernel;
use SIU\Chulupi\kernel;
use siu\errores\error_guarani;
use siu\errores\error_guarani_procesar_renglones;
use siu\modelo\datos\catalogo;
use siu\modelo\entidades\rs_arreglo;
use siu\modelo\entidades\rs_consulta;

class carga_notas_cursada_comision extends transaccion
{
	protected $operacion = 'cur00021';
	protected $evento = '14';
	protected $cache_request_acta_cabecera = array();

	function iniciar()
	{
		
	}

	//--------------------------------------------------------------------------
	//---- INFO 
	//--------------------------------------------------------------------------

	function info__comisiones()
	{
		$parametros = array('persona' => $this->persona);
		// Consumida en la zona (instanciado para distintas operaciones)
		$rs = new rs_consulta('carga_notas_cursada_comision', 'lista_comisiones_docente', $parametros);
		$rs->set_columna_id('comision');
		$rs->agregar_entidad('comision');

		return $rs;
	}

	function info__comisiones_docente($anio_academico = null, $periodo_lectivo = null, $actividad = null)
	{
		$rs = new rs_arreglo(catalogo::consultar('docente', 'lista_comisiones', array(
					'anio_academico' => $anio_academico,
					'docente' => kernel::persona()->get_id_docente(),
					'periodo_lectivo' => $periodo_lectivo,
					'actividad' => $actividad,
					'ubicacion' => null,
		)));
		$rs->set_columna_id('comision');
		$rs->agregar_entidad('comision');
		return $rs;
	}

	function info__comision_cabecera($datos)
	{	
		if (catalogo::consultar('carga_notas_cursada_comision', 'existe_evaluacion_automatica_comision', array('entidad' => $datos['entidad']))) {
			if (isset($datos['id_acta'])) {
				$parametros = array('comision' => $datos['comision'], 'id_acta' => $datos['id_acta']);
			} else {
				$parametros = array('comision' => $datos['comision'], 'id_acta' => null);
			}
			return catalogo::consultar('carga_notas_cursada_comision', 'comision_cabecera', $parametros);
		} else {
			$mensaje_error = kernel::traductor()->trans('800_sin_evaluacion');
			throw new error_guarani($mensaje_error);
		}
	}

	function info__alumnos_comision($datos, $pagina = null)
	{
                $parametros = [
                    'comision' => $datos['comision'],
                    'subcomision' => $datos['subcomision'] ?? null,
                    'id_acta' => $datos['id_acta'] ?? null,
                    'pagina' => $pagina
                ];

		$consulta = catalogo::consultar('carga_notas_cursada_comision', 'alumnos_comision', $parametros);
		$rs = new rs_arreglo($consulta);
		$rs->set_columna_id('renglon');
		return $rs;
	}

	function info__escala_notas($escala_notas)
	{
		$parametros = array('escala_notas' => $escala_notas);
		return catalogo::consultar('sistema', 'escala_notas', $parametros);
	}

	function info__resultados($instancia, $escala_notas)
	{
		$parametros = array('instancia' => $instancia, 'escala_notas' => $escala_notas);
		return catalogo::consultar('carga_notas_cursada_comision', 'resultados', $parametros);
	}
	
	function info__resultados_solo_ausente()
	{
		return catalogo::consultar('carga_notas_cursada_comision', 'get_escalas_notas_resultados_solo_ausente', []);
	}

	function info__condiciones()
	{
		return catalogo::consultar('carga_notas_cursada_comision', 'condiciones_regularidad');
	}

	function info__busqueda_alumno($datos, $term)
	{
		$parametros = [
                    'comision' => $datos['comision'],
                    'subcomision' => $datos['subcomision'] ?? null,
                    'term' => $term
		];
		return catalogo::consultar('carga_notas_cursada_comision', 'busqueda_alumno', $parametros);
	}

	/**
	 * @param mixed $datos array('alumno'=>,'comision'=>)
	 * @return mixed
	 */
	function info__parciales($datos)
	{
		$alumno = $datos['alumno'];
		$comision = $datos['comision'];

		$parametros = array('alumno' => $alumno,
			'comision' => $comision);
		$datos['parciales'] = catalogo::consultar('carga_notas_cursada_comision', 'get_datos_parciales', $parametros);

		$parametros = array('alumno' => $alumno);
		$datos['alumno'] = catalogo::consultar('alumno', 'get_datos_ficha', $parametros);
		return $datos;
	}
	
	function info__listado_instancias_comisiones()
	{
		return catalogo::consultar('carga_notas_cursada_comision', 'get_listado_instancias_comisiones');
	}

	function info__opciones_combo_para()
	{
		return catalogo::consultar('carga_notas_cursada_comision', 'get_opciones_combo_para');
	}
	
	function info__todas_opciones_combo_para($es_acta = false)
	{
                $parametros = ['es_acta' => $es_acta];
		return catalogo::consultar('carga_notas_cursada_comision', 'get_todas_opciones_combo_para', $parametros);
	}
	
	function info__escalas_notas_resultados()
	{
		return catalogo::consultar('carga_notas_cursada_comision', 'get_escalas_notas_resultados');
	}
	//--------------------------------------------------------------------------
	//---- EVENTOS
	//--------------------------------------------------------------------------

	function evt__procesar_pagina($seleccion, $renglones)
	{
		$comision_cabecera = $this->info__comision_cabecera($seleccion);

		$error = new error_guarani_procesar_renglones('Error cargando notas');

		$validador = acta::validador_actas_cursada();
		foreach ($renglones as $renglon) {
			$validador->validar_carga_notas($this->preparar_renglon_validacion($renglon), $comision_cabecera['fecha_inicio_periodo']);
			if ($validador->hay_errores()) {
				
				$error->add_renglon($renglon['renglon'], $this->get_mensaje_error($validador->get_errores()), $renglon);
				//::log()->add_debug("Errores de validacion en el renglon {$renglon['renglon']}:", $validador->get_errores());
				//kernel::log()->add_debug("ERROR: ", print_r($error, true));

			} else {
				try {
					$filas_afectadas = catalogo::consultar('carga_notas_cursada_comision', 'guardar_renglon', $renglon);
					
					kernel::log()->add_debug("Filas afectadas:", $filas_afectadas);

					if ($filas_afectadas > 0) {
						if ($renglon['hay_cambios_regularidad'] && !empty($renglon['id_acta_cursada'])) {
							$parametros = array('acta' => $renglon['id_acta_cursada'], 'version' => $renglon['version_regularidad'], 'version_impresa' => $renglon['version_impresa_regularidad']);
							catalogo::consultar('carga_notas_cursada_comision', 'actualizar_acta', $parametros);
						}
						if ($renglon['hay_cambios_promocion'] && !empty($renglon['id_acta_promocion'])) {
							$parametros = array('acta' => $renglon['id_acta_promocion'], 'version' => $renglon['version_promocion'], 'version_impresa' => $renglon['version_impresa_promocion']);
							catalogo::consultar('carga_notas_cursada_comision', 'actualizar_acta', $parametros);
						}
					} else {
						$error->add_renglon($renglon['renglon'], trans('error_concurrencia'), $renglon, error_guarani_procesar_renglones::TIPO_CONCURRENCIA);
					}
				} catch (ErrorKernel $e) {
					kernel::log()->add_error($e);
					$error->add_renglon($renglon['renglon'], kernel::traductor()->trans('notas_cursada_guardado_error'), $renglon);
				}
			}
		}
		if ($error->hay_renglones()) {
			throw $error;
		}
	}

	function preparar_renglon_validacion($renglon)
	{
		$renglon['apellido_y_nombre'] = "{$renglon['apellido']}, {$renglon['nombres']}";
		if (!empty($renglon['fecha_regular'])) {
			$fecha = date_parse_from_format('d/m/Y', $renglon['fecha_regular']);
			$renglon['fecha_regular'] = "{$fecha['year']}-{$fecha['month']}-{$fecha['day']}";
		}
		if (!empty($renglon['fecha_promocion'])) {
			$fecha = date_parse_from_format('d/m/Y', $renglon['fecha_promocion']);
			$renglon['fecha_promocion'] = "{$fecha['year']}-{$fecha['month']}-{$fecha['day']}";
		}
		return $renglon;
	}

	function get_mensaje_error($errores)
	{
		$mensaje_error = '';
		foreach ($errores as $error) {
			$params = array();
			$i = 1;
			foreach ($error['params'] as $param) {
				$params["%$i%"] = $param;
				$i++;
			}
			$mensaje_error .= "\n" . kernel::traductor()->trans($error['descripcion_corta'], $params);
		}
		return $mensaje_error;
	}
	
	function info__actas_abiertas($seleccion)
	{
		$parametros = [
			'persona' => $this->persona,
			'comision' => $seleccion['comision']
		];
		return catalogo::consultar('carga_notas_cursada', 'lista_actas_abiertas_docente', $parametros);

	}

       /**
        * Autocompleta notas, resultados, fechas u observaciones en la tabla sga_eval_detalle_cursadas.
        *
        * @param int $evaluacion
        * @param array $alumnos
        * @param array $columnas_completar
        * @param string $para
        * @param int|null $subcomision
        * @param int|null $id_acta
        * @param string|null $origen_acta
        *
        *
        * @return int Cantidad de filas afectadas (-1 en caso de error).
        */
	function evt__autocompletar_notas_cursada($evaluacion, $alumnos, $columnas_completar, $para, $subcomision = null, $id_acta = null, $origen_acta = null)
	{
		
		$parametros = [
			'evaluacion'            => $evaluacion,
			'alumnos'		=> $alumnos,
			'columnas_completar'	=> $columnas_completar,
			'para'			=> $para,
			'subcomision'           => $subcomision,
			'id_acta'               => $id_acta,
			'origen_acta'           => $origen_acta,
		];
		
		return catalogo::consultar('carga_notas_cursada_comision', 'autocompletar_notas_cursada', $parametros);
	}

       /**
        * Calcula notas y las guarda en la tabla sga_eval_detalle_cursadas.
        *
        * @param int $evaluacion
        * @param int $instancia
        * @param array $alumnos_completar
        *
        *
        * @return bool TRUE si hubo error, FALSE si no lo hubo.
        */
	function evt__calcular_notas_cursada($evaluacion, $instancia, $alumnos_completar)
	{
		
		$parametros = [
			'evaluacion'		=> $evaluacion,
			'instancia'             => $instancia,
			'alumnos_completar'	=> $alumnos_completar
		];
		
		return catalogo::consultar('carga_notas_cursada_comision', 'calcular_notas_cursada', $parametros);
	}
}