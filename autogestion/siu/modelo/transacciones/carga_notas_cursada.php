<?php
namespace siu\modelo\transacciones;

use SIU\Chulupi\kernel;
use SIU\Chulupi\ErrorKernel;
use siu\modelo\datos\catalogo;
use siu\errores\error_guarani_procesar_renglones;
use siu\errores\error_guarani_acta_cursada_cerrada;
use siu\modelo\_comun\comunes;

class carga_notas_cursada extends transaccion
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

	function info__actas_abiertas()
	{
		$parametros = array('persona' => $this->persona);
		return catalogo::consultar('carga_notas_cursada', 'lista_actas_abiertas_docente', $parametros);
	}

	function info__acta_cabecera($seleccion)
	{
		$datos = $this->get_opcion_enviada($seleccion, 'info__actas_abiertas');
		$parametros = array('acta' => $datos['id_acta']);
		return catalogo::consultar('carga_notas_cursada', 'acta_cabecera', $parametros);
	}

	function info__acta_folio($seleccion, $folio)
	{
		$datos = $this->get_opcion_enviada($seleccion, 'info__actas_abiertas');
		$parametros = array(
			'acta' => $datos['id_acta'],
			'renglones_folio' => $datos['renglones_folio'],
			'folio' => $folio
		);
		return catalogo::consultar('carga_notas_cursada', 'acta_folio', $parametros);
	}

	function info__escala_notas($escala_notas)
	{
		$parametros = array('escala_notas' => $escala_notas);
		return catalogo::consultar('sistema', 'escala_notas', $parametros);
	}

	function info__resultados($escala_notas)
	{
		$parametros = array('escala_notas' => $escala_notas);
		return catalogo::consultar('carga_notas_cursada', 'resultados', $parametros);
	}

	function info__condiciones()
	{
		return catalogo::consultar('carga_notas_cursada', 'condiciones_regularidad');
	}

	function info__parciales($alumno, $comision)
	{
		$parametros = array('alumno' => $alumno, 'comision' => $comision);
		$datos['parciales'] = catalogo::consultar('carga_notas_cursada', 'get_datos_parciales', $parametros);

		$parametros = array('alumno' => $alumno);
		$datos['alumno'] = catalogo::consultar('carga_notas_cursada', 'get_datos_ficha', $parametros);

		return $datos;
	}

	//--------------------------------------------------------------------------
	//---- EVENTOS
	//--------------------------------------------------------------------------

	function evt__procesar_folio($seleccion, $renglones)
	{
		$acta_cab = $this->info__acta_cabecera($seleccion);

		$error = new error_guarani_procesar_renglones('Error cargando notas');
		$error_acta = new error_guarani_acta_cursada_cerrada('Error cargando notas - Acta no disponible');

		if ($this->acta_abierta($acta_cab['id_acta'])) {
			$hay_renglones_actualizados = false;
			$validador = \acta::validador_actas_cursada();
			foreach ($renglones as $renglon) {
				$validador->validar_carga_notas_cursada($this->preparar_renglon_validacion($renglon), $acta_cab['fecha_inicio']);
				if ($validador->hay_errores()) {
					$error->add_renglon($renglon['renglon'], $this->get_mensaje_error($validador->get_errores()), $renglon);
				} else {
					try {
						$ok = catalogo::consultar('carga_notas_cursada', 'guardar_renglon', $renglon);
						if ($ok) {
							$hay_renglones_actualizados = true;
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
			if ($hay_renglones_actualizados) {
				$parametros = array('acta' => $acta_cab['id_acta'], 'version' => $acta_cab['version'], 'version_impresa' => $acta_cab['version_impresa']);
				$ok = catalogo::consultar('carga_notas_cursada', 'actualizar_acta', $parametros);
			}
		} else {
			throw $error_acta;
		}
	}

	function acta_abierta($acta)
	{
		$estado = catalogo::consultar('carga_notas_cursada', 'acta_estado', array('acta' => $acta));
		return ($estado == comunes::estado_abierto);
	}

	function preparar_renglon_validacion($renglon)
	{
		$renglon['apellido_y_nombre'] = "{$renglon['apellido']}, {$renglon['nombres']}";
		if (!empty($renglon['fecha'])) {
			$fecha = date_parse_from_format('d/m/Y', $renglon['fecha']);
			$renglon['fecha_regular'] = "{$fecha['year']}-{$fecha['month']}-{$fecha['day']}";
		} else {
			$renglon['fecha_regular'] = null;
		}
		$renglon['nota_cursada'] = $renglon['nota'];
		$renglon['escala_nota_cursada'] = $renglon['escala_nota'];
		$renglon['resultado_cursada'] = $renglon['resultado'];
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