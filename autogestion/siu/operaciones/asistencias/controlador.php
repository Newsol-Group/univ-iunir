<?php
namespace siu\operaciones\asistencias;

use siu\guarani;
use siu\extension_kernel\controlador_g3w2;
use SIU\Chulupi\util\validador;
use SIU\Chulupi\kernel;
use siu\modelo\entidades\parametro;
use siu\modelo\datos\catalogo;

class controlador extends controlador_g3w2
{
	public $clase = null;
	public $comision;
	protected $propuesta;
	public $hash_comision;
	public $valor_default = null;
	public $tipo_carga = null;

	/**
	 * 
	 * @return \siu\modelo\transacciones\carga_asistencias
	 */
	function modelo()
	{
		return guarani::carga_asistencias();
	}

	function get_info_zona()
	{
		if (isset($this->params_comision['comision'])) {
			return array('zona' => 'clase', 'seleccion' => $this->params_comision);
		} return null;
	}

	function accion__index()
	{
		$this->load_params();
		$this->comision = $this->params_comision['comision'];
		if (kernel::request()->isPost()) {
			$alumnos = $_POST['alumnos'];

			if ($this->get_tipo_carga() == $this->modelo()->tipo_presente()) {
				foreach ($alumnos as $key => $alumno) {
					$alumnos[$key]['PRESENTE'] = ($alumno['PRESENTE'] == 'on') ? 0 : 1;
				}
			}

			if ($this->get_tipo_carga() == $this->modelo()->tipo_horas() | $this->get_tipo_carga() == $this->modelo()->tipo_fracciones()) {
				// valor entre 0 y la cantidad de horas dictadas
				// si no valor por defecto
				$cantidad_horas_dictadas = $this->get_cant_horas_dictadas();

				foreach ($alumnos as $key => $alumno) {
					$alumnos[$key]['PRESENTE'] = ($alumno['PRESENTE'] >= 0 && $alumno['PRESENTE'] <= $cantidad_horas_dictadas) ? $alumno['PRESENTE'] : $this->get_valor_defecto();
				}
			}

			kernel::log()->add_debug('CLASE', $this->clase);
			kernel::log()->add_debug('ALUMNOS', $alumnos);

			$this->modelo()->grabar($this->clase, $this->comision, $alumnos);

			$this->render_ajax(kernel::traductor()->trans('asistencia_guardado_exitoso'));
		}
	}

	function get_clase_encabezado()
	{
		if (!isset($this->clase)) {
			return false;
		}
		return $this->modelo()->info__clase_cabecera($this->clase);
	}

	function get_clase_detalle()
	{
		if (!isset($this->clase)) {
			return false;
		}
		$alumnos = $this->modelo()->info__clase_detalle($this->comision, $this->clase, $this->params_comision['subcomision']);
		foreach ($alumnos as $key => $alumno) {
			// Si no tiene asistencia cargada en 'sga_clases_asistencia' le pongo el valor por defecto (asist_estado_default_asistencia).
			if ($alumno['cant_inasistencias'] == -1) {
				$alumnos[$key]['cant_inasistencias'] = $this->get_valor_defecto($alumno['libre']);
			}
		}
		return $alumnos;
	}

	protected function load_params()
	{
		$this->hash_comision = $this->validate_param(0, 'get', validador::TIPO_TEXTO);
		$this->params_comision = $this->decodificar_hash($this->hash_comision);

		if (!empty($this->params_comision['comision'])) {
			$propuestas = guarani::zona_clases()->get_propuesta($this->params_comision['comision']);

			if (!empty($propuestas)) {
				if (\guarani_arreglos::count($propuestas) > 1) {
					foreach ($propuestas as $propuesta) {
						$this->propuesta[] = $propuesta['propuesta'];
					}
				} else {
					$this->propuesta = $propuestas[0]['propuesta'];
				}
			}
		}

		$param_clase = $this->validate_param(1, 'get', validador::TIPO_INT, array('default' => 0));

		if ($param_clase != 0) {
			$this->clase = $param_clase;
		} else {
			$clases = guarani::zona_clases()->get_listado_clases_asistencia($this->params_comision['comision'], $this->params_comision['subcomision']);
			if (\guarani_arreglos::count($clases) > 0) {
				$this->clase = $clases[0]['clase'];
			}
		}
	}

	function get_clases_comision()
	{
		if (!isset($this->clase)) {
			return false;
		}
		$clases = guarani::zona_clases()->get_listado_clases_asistencia($this->params_comision['comision'], $this->params_comision['subcomision']);

		$hoy = date("Y-m-d");

		$combo = array();
		$operacion = kernel::ruteador()->get_id_operacion();
		foreach ($clases as $clase) {
			if ($clase['fecha_clase'] <= $hoy) {
				$combo[] = array(
					'url' => kernel::vinculador()->crear($operacion, 'index', array($this->params_comision[\siu\modelo\datos\catalogo::id], $clase['clase'])),
					'desc' => $clase['dia_semana_nombre'] . " " . $clase['fecha'] . " de " . $clase['hora_inicio'] . " a " . $clase['hora_finalizacion'] . " - " . $clase['espacio_nombre'],
					'selected' => ($this->clase == $clase['clase']) ? true : false,
				);
			}
		}
		return $combo;
	}

	function get_tipo_carga()
	{
		if (!isset($this->tipo_carga)) {
			$tipo_carga = $this->modelo()->info__tipo_carga($this->comision);
			$asist_indicar_cantidad_inasistencias = $tipo_carga['asistencia_cantidad_inasist'];
			$asist_computa_inasis_por_horas = $tipo_carga['asistencia_por_horas'];

			if ($asist_indicar_cantidad_inasistencias === "N") {
				$this->tipo_carga = $this->modelo()->tipo_presente();
			} else {
				if ($asist_computa_inasis_por_horas === "N") {
					$this->tipo_carga = $this->modelo()->tipo_fracciones();
				} else {
					$this->tipo_carga = $this->modelo()->tipo_horas();
				}
			}
		}
		return $this->tipo_carga;
	}

	protected function get_valor_defecto($libre = null)
	{
		if (empty($this->valor_default)) {
			$atributos = catalogo::consultar('atributos_params', 'get_propuestas_comision', array('comision' => $this->comision));
			$asist_estado_default_asistencia = parametro::get_valor('asist_estado_default_asistencia', $atributos);

			// Libres ausentes por defecto
			if ($libre == 1) {
				$this->valor_default = 0;
			}

			// Presente / Ausente
			$this->valor_default = ($asist_estado_default_asistencia == "Presente") ? 0 : $this->get_cant_horas_dictadas();
		}
		return $this->valor_default;
	}

	function get_cant_horas_dictadas()
	{
		if (!isset($this->cant_horas_dictadas)) {
			$encabezado = $this->get_clase_encabezado();
			$this->cant_horas_dictadas = ($encabezado['cantidad_horas_dictadas'] <= 0) ? 1 : $encabezado['cantidad_horas_dictadas'];
		}
		return $this->cant_horas_dictadas;
	}

}
?>