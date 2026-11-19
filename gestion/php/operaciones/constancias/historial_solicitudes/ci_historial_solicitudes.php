<?php
class ci_historial_solicitudes extends guarani_pers_ci
{
	protected $s__alumno;
	protected $s__solicitud;

	function ini()
	{
		$parametro_alumno = toba::memoria()->get_parametro('alumno');
		if (!empty($parametro_alumno)) {
			$this->s__alumno = $parametro_alumno;
		}
		$parametro_solicitud = toba::memoria()->get_parametro('solicitud');
		if (!empty($parametro_solicitud)) {
			$this->s__solicitud = $parametro_solicitud;
		}
	}

	//---- cuadro -----------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		if (!empty($this->s__alumno) && $this->s__alumno != apex_ef_no_seteado &&
			!empty($this->s__solicitud) && $this->s__solicitud != apex_ef_no_seteado) {
			$filtro = "	sga_constancias_solicitud.alumno = {$this->s__alumno} AND
						sga_constancias_solicitud.solicitud <> {$this->s__solicitud}";
			$order = 'sga_constancias_solicitud.fecha_solicitud DESC, sga_constancias.nombre';
			$datos = toba::consulta_php('co_constancias')->get_listado_const_solicitadas($filtro, $order);
			$cuadro->set_datos($datos);
		}
	}

	function extender_objeto_js()
	{
		echo "
		{$this->objeto_js}.evt__cerrar = function()	{
			window.close();
		}
		";
	}
}
?>