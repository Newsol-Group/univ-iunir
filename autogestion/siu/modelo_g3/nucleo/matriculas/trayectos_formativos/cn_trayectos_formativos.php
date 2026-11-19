<?php
class cn_trayectos_formativos extends guarani_pers_cn
{	
	protected $s__rtf_tiene;
	protected $s__datos;
			
	function get_datos($filtro)
	{
		$this->s__datos = array();
		$this->s__rtf_tiene = true;
		
		$aprobadas = alumno_nucleo::get_suplementos_rtf_aprobados($filtro);
		if (!empty($aprobadas)) {
			//Itero las aprobadas y busco sus actividades aprobadas
			$aux = array();
			foreach($aprobadas as $aprobada) {
				$actividades = toba::consulta_php('co_planes')->get_actividades_de_rtf($aprobada['alumno'], $aprobada['elemento_revision']);
				foreach($actividades as $actividad) {
					$actividad['rtf_codigo'] = $aprobada['rtf_codigo'];
					$actividad['rtf_nombre'] = $aprobada['rtf_codigo']. ' - '.$aprobada['rtf_nombre'].' (RTF Otorgados: '.$aprobada['rtf_creditos'].')';
					$actividad['propuesta'] = $aprobada['propuesta'];
					$actividad['propuesta_nombre'] = $aprobada['propuesta_nombre'];						
					$actividad['resultado'] = 'Aprobado';
					$aux[] = $actividad;
				}
			}
			$this->s__datos = $aux;
		} else {
			//Mostrar mensaje de que no tiene rtfs aprobados.
			$this->s__rtf_tiene = false;
		}
		
		return $this->s__datos;
	}
	
	function get_rtf_tiene()
	{
		return $this->s__rtf_tiene;
	}
	
}

?>