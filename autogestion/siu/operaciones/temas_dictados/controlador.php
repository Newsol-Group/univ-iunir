<?php
namespace siu\operaciones\temas_dictados;

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
	 * @return \siu\modelo\transacciones\carga_temas
	 */
	function modelo()
	{
		return guarani::carga_temas();
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
                    
                    $clase = $_POST['clase'];
                    $tema_dictado = $_POST['tema_dictado'];
                    $tema_planificado = $_POST['tema_planificado'];

			$this->modelo()->grabar($clase,$tema_dictado, $tema_planificado);

			$this->render_ajax(kernel::traductor()->trans('cargar_temas_guardado_exitoso'));
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
                $clases = $this->modelo()->info__clase_detalle($this->comision, $this->clase, $this->params_comision['subcomision']);
		
                return $clases;
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
                	//guarani::zona_clases()->get_listado_clases_temas($this->params_comision['comision'], $this->params_comision['subcomision']);
			
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


}
?>
