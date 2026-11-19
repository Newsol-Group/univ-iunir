<?php
class ci_reasignar extends guarani_pers_ci
{
	
	protected $ev_info_generica;
	protected $s__datos;
	protected $s__filtro;
    protected $s__temp_creado = false;
    
	function conf__pant_inicial($pantalla)
	{
		$this->ev_info_generica()->set_datos($this->cn()->get_generica());
		if($this->cn()->get_generica()['certificado']==comunes::si){
            $this->dep('cuadro_asignadas')->evento('eliminar')->desactivar();
            $this->dep('filtro')->evento('filtrar')->desactivar();
            $this->dep('cuadro_disponibles')->evento('asignar')->desactivar();
            $pantalla->set_descripcion('La actividad genérica ya pertenece a un certificado, no debe modificarse');
		}
        $this->cn()->crear_temporal_ha();        
	}
	
	function ev_info_generica()
	{
		if (!isset($this->ev_info_generica)) {
			$this->ev_info_generica = guarani::ev_info_datos();
			$this->ev_info_generica->set_columnas_a_mostrar(array('forma_cumplimiento_generica', 'cumplida'));			
			$this->ev_info_generica->set_etiqueta('forma_cumplimiento_generica', 'Regla');			
			$this->ev_info_generica->set_etiqueta('cumplda', 'cumplida');			
		}
		return $this->ev_info_generica;
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_asignadas -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_asignadas(guarani_pers_ei_cuadro $cuadro)
	{		
		$cuadro->set_datos($this->cn()->get_asignadas());
	}

	function evt__cuadro_asignadas__eliminar($seleccion)
	{		
		$this->cn()->eliminar_optativa($seleccion['optativa_elemento']);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_disponibles -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_disponibles(guarani_pers_ei_cuadro $cuadro)
	{
		$disponibles = $this->cn()->get_disponibles($this->s__filtro);			
		$asignadas = $this->dep('cuadro_asignadas')->get_datos();
		foreach (array_keys($asignadas) as $key) {
			//Si en las asignadas tengo la misma key elimino el registro
			if (isset($disponibles[$key])) {
				unset($disponibles[$key]);
			}
		}
		return $disponibles;			
	}

	function evt__cuadro_disponibles__asignar($seleccion)
	{
		$this->cn()->asignar_optativa($seleccion['optativa_elemento']);
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__filtro__filtrar($datos)
	{		
		$filtro_estado = '';		
		$alumno = $this->cn()->get_alumno()['alumno'];
		
		if ((isset($datos['estado']))) {
			$filtro_estado = $datos['estado']['condicion'] == comunes::es_igual_a ? ' AND ' : ' AND NOT ';			
			$filtro_estado .= "
						CASE 
							WHEN (SELECT COUNT(1) FROM vw_hist_academica_basica 
								   WHERE alumno = $alumno  
									 AND elemento = vw_optativas_plan.elemento 
									 AND resultado = '".elemento::estado_aprobado."'
								  ) > 0 THEN '".elemento::estado_aprobado_descr."'
							WHEN (SELECT COUNT(*) FROM vw_regularidades_basica
								   WHERE alumno = $alumno  
									 AND elemento = vw_optativas_plan.elemento 
									 AND resultado = '".elemento::estado_aprobado."'
									 AND es_vigente = 1
								 ) > 0 THEN '".elemento::estado_regularizada_descr."'
                        ELSE '' END = '{$datos['estado']['valor']}'";
						
			$this->dep('filtro')->columna('estado')->resetear_estado();
		}
		if (isset($datos) && !empty($datos)) {
			$this->s__datos = $datos;
			$this->s__filtro = " {$this->dep('filtro')->get_sql_where()} $filtro_estado";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}

	function evt__filtro__cancelar()
	{
		unset($this->s__datos);
		$this->s__filtro = '';
	}

	function conf__filtro(toba_ei_filtro $filtro)
	{		
		if (isset($this->s__datos)) {
			$filtro->set_datos($this->s__datos);
		}		
	}

	//-----------------------------------------------------------------------------------
	//---- form_boton -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

    /*
     * Este boton se usa nuevamente para volver a verificar el cumplimiento de 
     * una generica 
     */
	function evt__verificar()
	{
        $this->cn()->recalcular_cumplimiento();
	}

}
?>