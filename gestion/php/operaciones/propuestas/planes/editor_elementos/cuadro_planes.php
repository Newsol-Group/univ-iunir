<?php
class cuadro_planes extends guarani_pers_ei_cuadro
{
	function conf_evt__versionar($evento, $fila)
	{
		// No se permite versionar Planes de Tipo Personalizado.
		if ($this->datos[$fila]['tipo_plan'] == planes::tipo_personalizado ||
				$this->datos[$fila]['tipo_plan'] == planes::tipo_convenio ||
				($this->datos[$fila]['tipo_plan'] == planes::tipo_estructurado &&
				$this->datos[$fila]['administra_cursos'] == comunes::si)) {
				$evento->anular();
		} else {
			if ($this->datos[$fila]['versionable'] == comunes::si) {
				$plan = $this->datos[$fila]['plan_nombre'];
				$version = $this->datos[$fila]['version_nombre'];
				$evento->set_msg_confirmacion('¿Desea crear una nueva versión del plan ' . $plan . ' basada en la versión ' . $version . '?');
				$evento->set_msg_ayuda('Generar una nueva versión del Plan ' . $plan);
			} else {
				$evento->anular();
			}
		}
	}
}
?>