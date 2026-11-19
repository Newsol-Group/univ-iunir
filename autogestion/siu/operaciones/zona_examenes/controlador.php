<?php
namespace siu\operaciones\zona_examenes;

use siu\guarani;
use SIU\Chulupi\util\validador;
use siu\operaciones\_comun\operaciones\reporte\controlador_con_filtro;
use SIU\Chulupi\kernel;
use SIU\Chulupi\ErrorKernelValidacion;
use siu\operaciones\zona_examenes\mesas\builder_filtro_mesas;

class controlador extends controlador_con_filtro
{
	public $acta_hash, $llamado_mesa_params;

	function get_clase_vista()
	{
		if ($this->accion == 'index' || $this->accion == 'limpiar_filtro') {
			// Se muestra la lista de mesas
			return 'vista_mesas';
		} else {
			return 'vista_home';
		}
	}

	function get_info_zona()
	{
		if (isset($this->llamado_mesa_params['llamado_mesa'])) { 
			// Si no hay selección, se desactiva la zona
			return array('zona' => 'examen', 'seleccion' => $this->llamado_mesa_params);
		}
	}

	/**
	 * @return \siu\modelo\transacciones\zona_examenes
	 */
	public function modelo()
	{
		return guarani::zona_examenes();
	}

	function accion__index()
	{
		try {

			// Si se hizo click al botón "Filtrar" (POST)
			if (kernel::request()->isPost()) {

				$filtrar_por = $this->validate_param('filtrar_por', 'post', validador::TIPO_TEXTO, array('default' => 'r'));
				if (!is_null($filtrar_por)) {
					kernel::sesion()->set('filtrar_por', $filtrar_por);
				}

				// Si se filtra por "Fecha"
				if ($filtrar_por == 'f') {
					$fecha_examen = $this->validate_param('fecha', 'post', validador::TIPO_DATE, array('format' => 'd/m/Y', 'default' => null));

					if (is_null($fecha_examen)) {
						kernel::sesion()->borrar('date_time_examen');
					} else {
						kernel::sesion()->set('date_time_examen', \DateTime::createFromFormat('d/m/Y', $fecha_examen)->format('Y-m-d'));
					}

				// Si se filtra por "Rango de fechas"
				} elseif ($filtrar_por == 'r') {
					$fecha_examen_desde = $this->validate_param('desde', 'post', validador::TIPO_DATE, array('format' => 'd/m/Y', 'default' => null));
					$fecha_examen_hasta = $this->validate_param('hasta', 'post', validador::TIPO_DATE, array('format' => 'd/m/Y', 'default' => null));

					if (is_null($fecha_examen_desde)) {
						kernel::sesion()->borrar('date_time_examen_desde');
					} else {
						kernel::sesion()->set('date_time_examen_desde', \DateTime::createFromFormat('d/m/Y', $fecha_examen_desde)->format('Y-m-d'));
					}

					if (is_null($fecha_examen_hasta)) {
						kernel::sesion()->borrar('date_time_examen_hasta');
					} else {
						kernel::sesion()->set('date_time_examen_hasta', \DateTime::createFromFormat('d/m/Y', $fecha_examen_hasta)->format('Y-m-d'));
					}

				}

			}

			parent::accion__index();

		} catch (ErrorKernelValidacion $e) {
			$this->vista()->pagelet('mesas')->add_var_js('hubo_error_validacion', true);
		}
	}

	function accion__limpiar_filtro() {

		kernel::sesion()->set('filtrar_por', 'r');

		$hoy = new \DateTime();
		kernel::sesion()->set('date_time_examen', $hoy->format('Y-m-d'));

		$desde = new \DateTime();
		$desde->add(\DateInterval::createFromDateString('-'.builder_filtro_mesas::CANT_DIAS_RANGO_FECHAS.' days'));
		kernel::sesion()->set('date_time_examen_desde', $desde->format('Y-m-d'));

		$hasta = new \DateTime();
		$hasta->add(\DateInterval::createFromDateString(builder_filtro_mesas::CANT_DIAS_RANGO_FECHAS.' days'));
		kernel::sesion()->set('date_time_examen_hasta', $hasta->format('Y-m-d'));
	}

	function accion__home()
	{
		$this->load_params_mesa();
	}

	protected function load_params_mesa()
	{
		$this->acta_hash = $this->validate_param(0, 'get', validador::TIPO_ALPHANUM);
		$this->llamado_mesa_params = $this->decodificar_hash($this->acta_hash, '*');
	}

	protected function instanciar_form_builder()
	{
		return kernel::localizador()->instanciar('operaciones\zona_examenes\mesas\builder_filtro_mesas');
	}

}
?>