<?php
class ci_agregados_disp_activ extends guarani_pers_ci
{
	protected $s__filtro_asoc;
	protected $s__datos_asoc;
	protected $datos_asoc_temp;
	
	protected $s__filtro_disp;
	protected $s__datos_disp;
	
	protected $actividades_seleccionadas;

	//-----------------------------------------------------------------------------------
	//---- form_ml_asociados ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_ml_asociados(toba_ei_formulario $form)
	{
		if (isset($this->datos_asoc_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_asoc_temp);		
		} else {
			$datos = $this->cn()->entidad()->get_datos_actividades_asoc();
			foreach ($datos as $key => $dato) {
				if (isset($dato['hora_inicio_examen'])) {
					$datos[$key]['hora_inicio_examen'] = substr($dato['hora_inicio_examen'], 0, 5);
				}
				if (isset($dato['hora_fin_examen'])) {
					$datos[$key]['hora_fin_examen'] = substr($dato['hora_fin_examen'], 0, 5);
				}
			}
			$form->set_datos($datos);
		}
		$form->set_solo_lectura(array('legajo_actividad', 'nombre_actividad'));
	}
	
	function evt__form_ml_asociados__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_actividades($datos);
		} catch ( guarani_error $e ) {
			$this->datos_asoc_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	function evt__form_ml_asociados__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_fila_actividad_asociada($seleccion);
	}
	
	//-----------------------------------------------------------------------------------
	//---- disponibles ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__disponibles(toba_ei_cuadro $cuadro)
	{
		if (!isset($this->s__filtro_disp)) {
			$msg_eof = guarani_mensajes::get('800_eof_cuadro_sinfiltro');
			$cuadro->set_eof_mensaje($msg_eof);
		} else {	
			$actividades = $this->cn()->entidad()->get_datos_actividades_asoc();
			$filtro = 'true';
			
			if (!empty($actividades)) {
		    	$filtro .= ' AND ' . guarani_sql::armar_condicion_in($actividades, 'sga_elementos', 'elemento', 'elemento', true);
			}
			
			if (!((is_null($this->s__filtro_disp)) || ($this->s__filtro_disp == ''))) {
				$filtro .= " AND $this->s__filtro_disp";
			}		
			
			$datos = $this->cn()->get_listado_actividades($filtro);		
			$cuadro->set_datos($datos);
		}
	}

	function evt__disponibles__agregar($seleccion)
	{
		if (!empty($this->actividades_seleccionadas)) {
			foreach ($this->actividades_seleccionadas as $seleccion) {
				$this->cn()->entidad()->agregar_fila_actividad_asociada($seleccion);
			}
		}		
	}
	
	function evt__disponibles__seleccion($seleccion)
	{
		$this->actividades_seleccionadas = $seleccion;
	}
	
	//-----------------------------------------------------------------------------------
	//---- filtro_disp ------------------------------------------------------------------
	//-----------------------------------------------------------------------------------


	function evt__filtro_disp__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_disp = $datos;
			$this->s__filtro_disp = $this->dep('filtro_disp')->get_sql_where();			
		} else {
			unset($this->s__datos_disp);
			$this->s__filtro_disp = '';
		}
	}


	function evt__filtro_disp__cancelar()
	{
		unset($this->s__datos_disp);
		$this->s__filtro_disp = '';
	}


	function conf__filtro_disp(toba_ei_filtro $filtro)
	{
		if (isset($this->s__datos_disp)) {
			$filtro->set_datos($this->s__datos_disp);
		}
	}
		
}
?>