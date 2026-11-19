<?php
namespace siu\modelo\transacciones;

use SIU\Chulupi\kernel;
use SIU\Chulupi\ErrorKernel;
use siu\modelo\datos\catalogo;
use siu\errores\error_guarani_procesar_renglones;
use siu\modelo\entidades\rs_arreglo;
use siu\modelo\entidades\rs_consulta;

class carga_notas_mesa_examen extends transaccion
{
	protected $operacion = 'exa00033';
	protected $evento = '14';
	protected $request_cabecera;
	protected $error;
	protected $validador;

	function iniciar()
	{
		
	}

	//--------------------------------------------------------------------------
	//----	INFO ---------------------------------------------------------------
	//--------------------------------------------------------------------------

	function info__mesas_docente()
	{
		$parametros = array('persona' => $this->persona);
		$datos = catalogo::consultar('carga_notas_mesa_examen', 'lista_mesas_docente', $parametros);
		$rs = new rs_arreglo($datos);
		$rs->set_columna_id('llamado_mesa');
		$rs->agregar_entidad('llamado_mesa');
		return $rs;
	}

	function info__mesa_cabecera($datos)
	{
		if (!isset($this->request_cabecera)) {
			$parametros = array('llamado_mesa' => $datos['llamado_mesa']);
			$this->request_cabecera = catalogo::consultar('carga_notas_mesa_examen', 'get_datos_encabezado_mesa', $parametros);
		}
		return $this->request_cabecera;
	}

	function info__alumnos_mesa($datos, $pagina)
	{
		$parametros = array('evaluacion' => $datos['evaluacion'], 'pagina' => $pagina);
		return catalogo::consultar('carga_notas_mesa_examen', 'get_alumnos_mesa', $parametros);
	}

	function info__escala_notas($datos)
	{
		$parametros = array('llamado_mesa' => $datos['llamado_mesa']);
		return catalogo::consultar('carga_notas_mesa_examen', 'escala_notas', $parametros);
	}

	function info__escala($escala_id)
	{
		$parametros = array('escala_nota' => $escala_id);
		return catalogo::consultar('carga_notas_mesa_examen', 'get_detalles_escala_nota', $parametros);
	}

	function info__datos_escalas_notas($datos)
	{
		$parametros = array('llamado_mesa' => $datos['llamado_mesa']);
		return catalogo::consultar('carga_notas_mesa_examen', 'datos_escalas_notas', $parametros);
	}

	function info__escalas_notas_usadas($datos)
	{
		$parametros = array('llamado_mesa' => $datos['llamado_mesa']);
		return catalogo::consultar('carga_notas_mesa_examen', 'escalas_usadas', $parametros);
	}

	function info__resultados($datos)
	{
		$parametros = array('llamado_mesa' => $datos['llamado_mesa']);
		return catalogo::consultar('carga_notas_mesa_examen', 'resultados', $parametros);
	}

	function info__busqueda_alumno($datos, $term)
	{
		$parametros = array('llamado_mesa' => $datos['llamado_mesa'], 'term' => $term);
		return catalogo::consultar('carga_notas_mesa_examen', 'busqueda_alumno', $parametros);
	}

	function info__opciones_autocompletar_campo($datos)
	{
		$parametros = array('llamado_mesa' => $datos['llamado_mesa']);
		$res = catalogo::consultar('carga_notas_mesa_examen', 'get_instancias_llamado_mesa', $parametros);
		$salida = array();
		foreach ($res as $valor) {
			// Para las distintas instancias, se genera una clave para el combo ["instancia","escala_nota"]
			$salida[] = array('valor' => $valor['instancia'] . ',' . $valor['escala_nota'], 'descr' => kernel::traductor()->trans('carga_notas_mesa_examen.nota_de_instancia') . $valor['instancia_nombre']);
		}
		$salida[] = array('valor' => 'fecha', 'descr' => kernel::traductor()->trans('carga_notas_mesa_examen.fecha'));
		$salida[] = array('valor' => 'resultado', 'descr' => ucwords(kernel::traductor()->trans('resultado')));
		return $salida;
	}

	function info__cabecera_escala_notas($datos)
	{
		$parametros = array('llamado_mesa' => $datos['llamado_mesa']);
		return catalogo::consultar('carga_notas_mesa_examen', 'get_instancias_llamado_mesa', $parametros);
	}

	function info__cabecera_actas($datos)
	{
		$parametros = array('llamado_mesa' => $datos['llamado_mesa']);
		$rs = new rs_consulta('acta', 'get_actas_llamado_mesa', $parametros);
		$rs->set_columna_id('id_acta');
		$rs->agregar_entidad('llamado_mesa');
		return $rs;
	}

	function info__examenes($actividad, $alumno)
	{
		$datos = array();
		$datos['alumno'] = catalogo::consultar('alumno', 'get_datos_ficha', array('alumno' => $alumno));
		$datos['ficha'] = catalogo::consultar('alumno', 'get_datos_ultima_cursada_actividad', array('alumno' => $alumno, 'actividad' => $actividad));
		return $datos;
	}

	//--------------------------------------------------------------------------
	//---- EVENTOS -------------------------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Verifica que la nota ingresada sea válida de acuerdo a la escala de notas de la instancia correspondiente al alumno.
	 */
	protected function validar_nota($renglon)
	{
		if (!$this->validador->es_nota_valida($renglon['nota'], $renglon['escala_nota'])) {
			$mensaje_error = kernel::traductor()->trans('800_nota_invalida');
			$this->error->add_renglon($renglon['renglon'], $mensaje_error, $renglon);
			return false;
		}
		return true;
	}

	/**
	 * Verifica que el resultado seteado para el alumno sea el que se corresponde con la escala de notas asignada al
	 * mismo (según su instancia) y la nota que se seteó para él, o en su defecto que el resultado sea Ausente y al
	 * alumno no se le haya seteado nota.
	 * Además se verifica que si hay cargado un resultado debe haberse cargado una nota y viceversa.
	 */
	protected function validar_resultado($renglon)
	{
		$ok = true;
		if (!empty($renglon['resultado'])) {
			if ($renglon['resultado'] == \escalas_notas::resultado_ausente && isset($renglon['nota'])) {
				$mensaje_error = kernel::traductor()->trans('800EXA_error_nota_ausente');
				$this->error->add_renglon($renglon['renglon'], $mensaje_error, $renglon);
				$ok = false;
			} elseif (isset($renglon['nota']) && !$this->validador->es_resultado_valido($renglon['nota'], $renglon['escala_nota'], $renglon['resultado'])) {
				$mensaje_error = kernel::traductor()->trans('800EXA_error_nota_resultado');
				$this->error->add_renglon($renglon['renglon'], $mensaje_error, $renglon);
				$ok = false;
			} elseif (!isset($renglon['nota']) && ($renglon['resultado'] != \escalas_notas::resultado_ausente)) {
				$mensaje_error = kernel::traductor()->trans('800EXA_error_nota_faltante');
				$this->error->add_renglon($renglon['renglon'], $mensaje_error, $renglon);
				$ok = false;
			}
		} elseif (isset($renglon['nota'])) {
			$mensaje_error = kernel::traductor()->trans('800EXA_error_nota_resultado');
			$this->error->add_renglon($renglon['renglon'], $mensaje_error, $renglon);
			$ok = false;
		}
		return $ok;
	}

	/**
	 * Verifica que la fecha de la carga de nota para el alumno sea válida, es decir, mayor o igual a la fecha del
	 * llamado-mesa y menor o igual a la actual.
	 */
	function validar_fecha($fecha_nota, $fecha_mesa, $llamado_mesa, $renglon)
	{
		if (!empty($fecha_nota) && !\toba::consulta_php('co_mesas_de_examen')->es_fecha_valida_de_carga_nota_examen($fecha_nota, $llamado_mesa)) {
			$mensaje_error = kernel::traductor()->trans('carga_notas_mesa_examen.fecha_invalida', array('%1%' => $fecha_mesa, '%2%' => date('d/m/Y')));
			$this->error->add_renglon($renglon['renglon'], $mensaje_error, $renglon);
			return false;
		}
		return true;
	}

	function validar_renglon($renglon, $llamado_mesa, $fecha_mesa)
	{
		$renglon = $this->preparar_renglon_validacion($renglon);

		// Se validan la nota y el resultado de examen.
		$valido = $this->validar_nota($renglon);
		if ($valido) {
			$valido = $this->validar_resultado($renglon);
		}

		// Se valida la fecha ingresada
		$valido = $valido & $this->validar_fecha($renglon['fecha'], $fecha_mesa, $llamado_mesa, $renglon);

		return $valido;
	}

	function evt__procesar_pagina($datos, $renglones)
	{
		$llamado_mesa = $datos['llamado_mesa'];
		$fecha_mesa = \convertir_fecha_formato_frontend($datos['fecha']);

		$this->error = new error_guarani_procesar_renglones('Error cargando notas');
		$this->validador = \escalas_notas::validador_escalas_notas();

		foreach ($renglones as $renglon) {
			if ($this->validar_renglon($renglon, $llamado_mesa, $fecha_mesa)) {
				// Validaciones exitosas -> se actualiza el registro
				try {
					$filas_afectadas = catalogo::consultar('carga_notas_mesa_examen', 'guardar_renglon', $renglon);
					if ($filas_afectadas > 0) {
						if (!empty($renglon['id_acta'])) {
							$parametros = array('acta' => $renglon['id_acta'], 'version' => $renglon['version'], 'version_impresa' => $renglon['version_impresa']);
							catalogo::consultar('carga_notas_mesa_examen', 'actualizar_acta', $parametros);
						}
					} else {
						$this->error->add_renglon($renglon['renglon'], trans('error_concurrencia'), $renglon, error_guarani_procesar_renglones::TIPO_CONCURRENCIA);
					}
				} catch (ErrorKernel $e) {
					kernel::log()->add_error($e);
					$this->error->add_renglon($renglon['renglon'], kernel::traductor()->trans('notas_cursada_guardado_error'), $renglon);
				}
			}
		}

		if ($this->error->hay_renglones()) {
			throw $this->error;
		}
	}

	function preparar_renglon_validacion($renglon)
	{
		if (!empty($renglon['fecha'])) {
			$renglon['fecha'] = \convertir_fecha_formato_base($renglon['fecha']);
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

}
?>