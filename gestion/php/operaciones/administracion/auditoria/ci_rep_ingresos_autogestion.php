<?php
class ci_rep_ingresos_autogestion extends ci_rep_sin_listado_inicial
{
	function get_datos($filtro)
	{
        $datos_cuadro = [];
		$datos = toba::consulta_php('co_alumnos')->get_listado_reporte_ingresos_autogestion($filtro);
        //Recorro los datos para quitar los ingresos donde los valores de ingreso son mayores a los egresos, orden de testing
        foreach ($datos as $val) {
            if(!empty($val['fecha_egreso'])){
                //debe estar dentro de las fechas, porque si viene con fecha de ingreso 2024 y egreso 2022 por error se mostraba
                $fecha_1 = guarani_fecha::es_mayor_timestamp($val['fecha_egreso'], $val['fecha_desde']);
                $fecha_2 = guarani_fecha::es_menor_timestamp($val['fecha_desde'], $val['fecha_egreso']);
                if($fecha_1 && $fecha_2){
                    $datos_cuadro [] = $val;
                }
            }else{
                $datos_cuadro [] = $val;
            }
            
        }
        return $datos_cuadro;
	}

	function get_tabla_principal()
	{
		return 'aud_login_autogestion';
	}
	
	/**
	*	Permite recuperar el mensaje toba actualmente asociado al eof del cuadro.
	*/	
	function get_msg_eof($tabla=null)
	{
		
		$indx_msg = $this->indx_msg_eof;
		return guarani_mensajes::get($indx_msg, array('logs en autogestión'));
	}	
		//---- cuadro -----------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_filtro_asociado($this->dep('filtro'));
		$cuadro->set_usar_ordenamiento_con_cortes();
		/**
 		 *	Si no está seteado el filtro entonces es la carga inicial del cuadro (el usuario no presionó filtrar).
		 *	No se traen datos (no se ejecuta la consulta) y se pone un mensaje en el cuadro que indica al usuario
		 *	que debe filtrar para obtener datos.
		*/
		$this->msj_eof = '800_eof_cuadro';
		if (!isset($this->s__filtro)) {
		} else {
			$this->indx_msg_eof= '800_no_log_autogestion';
			//El usuario presiono filtrar (ya sea con datos o con el filtro vacío).
			$datos = $this->get_datos($this->s__filtro);
			$cuadro->set_datos($datos);
		}
		$cuadro->set_eof_mensaje($this->get_msg_eof());
	}
	
	//---- filtro -----------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('ip')->set_condicion_fija('es_igual_a');
		$filtro->columna('fecha_desde')->set_condicion_fija('es_mayor_igual_que');
		$filtro->columna('fecha_egreso')->set_condicion_fija('es_menor_igual_que');
	}
	
	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$filtros = '';
			if ((isset($datos['ip']['valor']))) {
				$ip = toba::db()->quote($datos['ip']['valor']);
				$filtros .= " AND l.ip = $ip ";
				$this->dep('filtro')->columna('ip')->resetear_estado();
			}
            
            $filtros_fecha          = '';
//            $filtros_fecha_egreso   = '';
            if(isset($datos['fecha_desde']['valor']) && isset($datos['fecha_egreso']['valor'])){
                $filtros_fecha = ' AND l.ingreso::timestamp <= l.egreso::timestamp ';
                $filtros_fecha .= ' AND l.egreso::timestamp >= l.ingreso::timestamp ';
            } 
            
			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtros $filtros_fecha";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}
	
	function datos_encabezado() 
	{
		return array();
	}
	
	
	

}