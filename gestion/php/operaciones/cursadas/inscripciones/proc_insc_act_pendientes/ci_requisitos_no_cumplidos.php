<?php
class ci_requisitos_no_cumplidos extends guarani_pers_ci
{
	protected $s__requisitos_no_cumplidos;
	protected $s__datos_inscripcion;
	protected $ev_info;

	function ini__operacion()
	{
		$this->s__requisitos_no_cumplidos = toba::memoria()->get_dato('requisitos_no_cumplidos');
		$this->s__datos_inscripcion = toba::memoria()->get_parametros();
		$this->ev_info()->set_datos($this->s__datos_inscripcion);
	}

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('apellido_y_nombre', 'documento_principal', 'legajo', 'actividad_nombre', 'instancia_nombre'));
			$this->ev_info->set_max_columnas_por_fila(3);
			$this->ev_info->set_columnas_fila(array('apellido_y_nombre', 'documento_principal', 'legajo'), 1);
			$this->ev_info->set_columnas_fila(array('actividad_nombre', 'instancia_nombre'), 2);
			$this->ev_info->set_etiqueta('apellido_y_nombre', 'Alumno');
			$this->ev_info->set_etiqueta('documento_principal', 'Identificación');
			$this->ev_info->set_etiqueta('legajo', 'Legajo');
			$this->ev_info->set_etiqueta('actividad_nombre', 'Actividad');
			$this->ev_info->set_etiqueta('instancia_nombre', 'Instancias');
		}
		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		if (isset($this->s__datos_inscripcion['inscripcion']) && isset($this->s__requisitos_no_cumplidos[$this->s__datos_inscripcion['inscripcion']])) {
			$inscripciones = explode('||', $this->s__requisitos_no_cumplidos[$this->s__datos_inscripcion['inscripcion']]['observaciones']);
			$datos = array();
			foreach ($inscripciones as $i) {
				$datos[] = array('requisito' => $i);
			}
			$cuadro->set_datos($datos);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//----------------------------------------------------------------------------------- 

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