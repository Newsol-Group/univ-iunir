<?php
namespace siu\operaciones\zona_comisiones;

use siu\guarani;
use SIU\Chulupi\kernel;
use SIU\Chulupi\ErrorKernelValidacion;
use SIU\Chulupi\util\validador;
use siu\operaciones\_comun\operaciones\reporte\controlador_con_filtro;
use siu\operaciones\_comun\zonas\zona_comision;

class controlador extends controlador_con_filtro
{
	public $params_comision, $pagina, $hash_comision;

	function get_clase_vista()
	{
		if ($this->accion == 'index') {
			return 'vista_comisiones';
		} else {
			return 'vista_home';
		}
	}

	/**
	 * @return \siu\modelo\transacciones\zona_comisiones
	 */
	function modelo()
	{
		return guarani::zona_comisiones();
	}

	function get_info_zona()
	{
		if (isset($this->params_comision['comision'])) { //si no esta seleccionado nada, no activo la zona
			return array('zona' => 'comision', 'seleccion' => $this->params_comision);
		}
	}

	function accion__index()
	{
		try {
			parent::accion__index();
			$periodos_lectivos = $this->validate_param('periodo_lectivo', 'get', validador::TIPO_INT, array('default' => 0));
		} catch (ErrorKernelValidacion $e) {
			$this->vista()->pagelet('comisiones')->add_var_js('hubo_error_validacion', true);
		}

		if ($periodos_lectivos) {
			$periodos_lectivos = array($periodos_lectivos);
		} else {
			$periodos_lectivos = $this->modelo()->get_ids_periodos_lectivos_vigentes();
		}
		kernel::sesion()->borrar($this->get_clave_sesion_periodo());
		kernel::sesion()->set($this->get_clave_sesion_periodo(), $periodos_lectivos);

		$this->vista()->pagelet('comisiones')->data['renglones'] = $this->get_comisiones(null, $periodos_lectivos, null);
	}

	function accion__home()
	{
		$this->load_params();
	}

	function get_encabezado()
	{
		return $this->modelo()->info__comision_zona($this->params_comision);
	}

	protected function get_comisiones($anio_academico = null, $periodo_lectivo = null, $actividad = null /* , $propuesta = null */)
	{

		$comisiones = $this->codificador_modelo()->info__comisiones_docente($anio_academico, $periodo_lectivo, $actividad);
		$comisiones->link('LINK', kernel::ruteador()->get_id_operacion() /* 'zona_comisiones' */, 'home');
		$columnas = array('comision_nombre', 'subcomision_nombre', 'ubicacion_nombre', 'responsabilidad_nombre', 'turno_nombre', 'cant_inscriptos_comision', 'resp_docente');
		$datos = array();
		foreach ($comisiones as $fila) {
			$periodo_key = $fila['periodo_lectivo_nombre_full'];
			$actividad_key = $fila['actividad_orden'];
			foreach ($columnas as $campo) {
				$nuevo[$campo] = isset($fila[$campo]) ? $fila[$campo] : null;
			}
			$nuevo['cant_inscriptos'] = $fila['es_subcomision'] ? $fila['cant_inscriptos_subcomision'] : $fila['cant_inscriptos_comision'];
			$nuevo['link'] = $fila['LINK'];
			$datos[$periodo_key][$actividad_key][] = $nuevo;
		}
		return $datos;
	}

	//--------------------------------------------------------------------------
	//---- UTIL ----------------------------------------------------------------
	//--------------------------------------------------------------------------
	
	protected function load_params()
	{
		$this->hash_comision = $this->validate_param(0, 'get', validador::TIPO_TEXTO);
		$this->params_comision = $this->decodificar_hash($this->hash_comision, 'home');

		$this->pagina = $this->validate_param(1, 'get', validador::TIPO_INT, array('default' => 1));
	}

	protected function instanciar_form_builder()
	{
		return kernel::localizador()->instanciar('operaciones\zona_comisiones\comisiones\builder_filtro_comisiones');
	}

	protected function get_clave_sesion_periodo()
	{
		return zona_comision::$clave_sesion_periodo;
	}

}
?>