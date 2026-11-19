<?php
/**
 *
 *
 * @package 	Matrícula
 * @subpackage 	Alumnos
 * 
 */
class ci_nav_cambio_plan extends ci_nav_sin_listado_inicial 
{
	function get_alumno_seleccionado()
	{
		return $this->s__seleccion['alumno'];
	}

	//------ Cuadro ----------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$this->indx_msj_eof = '800_eof_cuadro';
		if (!isset($this->s__filtro)) {
			$this->indx_msg_eof .= '_sinfiltro';
		} else {
			$filtro = empty($this->s__filtro) ? 'true' : $this->s__filtro;
			// Sólo los alumnos de calidad "Activo" pueden cambiar de plan/plan-versión.
			$filtro .= " AND sga_alumnos.calidad = '".alumno::calidad_activo."'";
			$datos = $this->cn()->get_listado($filtro);
			$cuadro->set_datos($datos);
		}
		$cuadro->set_eof_mensaje($this->get_msg_eof());
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__confirmar()
	{
		try {
			$datos = $this->dep('editor')->get_datos();
			$seleccion = $this->dep('editor')->get_seleccion();

			if (empty($seleccion['plan_version'])) {
				$mje = guarani_mensajes::get('800_no_seleccion_unica', array('un plan de estudios'));
				throw guarani::error($mje);
			}

			$this->cn()->entidad()->set_alumno_plan_version($seleccion['plan_version']);
			$this->cn()->entidad()->nuevo_cambio_plan($datos);

			$this->cn()->guardar();
			$this->dep('editor')->set_pantalla('informacion');
			toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');
		} catch (guarani_error $e) {
			throw $e;
		}
	}
}
?>