<?php
/**
 * Datos para combos básicos y comunes
 *
 *
 * @package Centrales
 * @subpackage ArquitecturaBase
 */

class co_comunes
{
	/* --------------------------------------------------------------------------------------
	/* ----  Catálogo de campos que no tienen una tabla de valores  -------------------------
	/* --------------------------------------------------------------------------------------

	/**
     * Retorna las opciones Sí / No 
     */
    function get_si_no()
    {
		return array(array('valor' => 'S','descr' => 'Sí'),
		             array('valor' => 'N','descr' => 'No')
		            );
    }
	
    /**
     * Retorna las opciones Sí / No / Desconoce 
     */
    function get_si_no_desconoce()
    {
		return array(array('valor' => 'S','descr' => 'Sí'),
		             array('valor' => 'N','descr' => 'No'),
		             array('valor' => 'D','descr' => 'Desconoce')
		             );
    }
	
    /**
     * Retorna las opciones Sí / No / Desconoce 
     */
    function get_si_no_no_desea_responder()
    {
		return array(array('valor' => 'S','descr' => 'Sí'),
		             array('valor' => 'N','descr' => 'No'),
		             array('valor' => 'X','descr' => 'No desea responder')
		             );
    }
    
    /**
     *  Retorna las opciones Sí / No, con 0 y 1 como valores.
     */
    function get_si_no_binario()
    {
		return array(array('valor' => '1','descr' => 'Sí'),
		             array('valor' => '0','descr' => 'No')
		            );
    }
    
	/**
        Retorna las opciones Vive / No Vive / Desconoce 
    */
    function get_vive_novive_desconoce()
    {
		return array(array('valor' => 'V','descr' => 'Vive'),
		             array('valor' => 'N','descr' => 'No Vive'),
		             array('valor' => 'D','descr' => 'Desconoce')
		             );
    }
    
    /**
        Retorna las opciones Desde / Hasta / Entre
    */
    function get_desde_hasta_entre()
    {
		return array(array('valor' => 'D','descr' => 'desde'),
		             array('valor' => 'H','descr' => 'hasta'),
		             array('valor' => 'E','descr' => 'entre')
		             );
    }

	/**
	 * Retorna el conjunto de opciones: Mayor a, Menor a, Entre.
	 * 
	 * @return array
	 */
    function get_mayor_menor_entre()
    {
		return array(	array('valor' => comunes::mayor, 'descr' => comunes::mayor_descr),
						array('valor' => comunes::menor, 'descr' => comunes::menor_descr),
						array('valor' => comunes::entre, 'descr' => comunes::entre_descr));
    }

	/**
        Retorna las opciones para el combo del campo Operador_not
    */
    function get_opciones_combo_operador_not()
    {
		return array(	array('valor' => '0','descr' => '-----'),
						array('valor' => '1','descr' => 'Negar')
					);
    }
    
    /**
        Retorna las opciones para el combo del campo resultado_asp
    */
    function get_opciones_combo_resultado_asp()
    {
		return array(	array('valor' => 'P','descr' => 'Pendiente'),
						array('valor' => 'A','descr' => 'Aceptado'),
						array('valor' => 'R','descr' => 'Rechazado')
					);
    }
    
    /**
        Retorna las opciones para el combo del campo aplica_a
    */
    function get_opciones_combo_aplica_a()
    {
		return array(	array('valor' => 'C','descr' => 'Comisiones'),
						array('valor' => 'M','descr' => 'Mesas de Examen'),
						array('valor' => 'O','descr' => 'Otros')
					);
    }
	
	/**
        Retorna las opciones para el combo del campo estado
    */
    function get_opciones_combo_estado()
    {
		return $this->get_estado_activo_baja();
    }
    
	/**
        Retorna las opciones Sí / No 
        Campo: requiere_res_exp
    */
    function get_opciones_combo_requiere_res_exp()
    {
		return $this->get_si_no();
    }
	/**
        Retorna las opciones Sí / No 
        Campo: sga_planes.inscripcion_habilitada
    */
    function get_opciones_combo_inscripcion_habilitada()
    {
		return $this->get_si_no();
    }
	/**
        Retorna Activo/Baja
        Campo: requiere_res_exp
    */
    function get_opciones_combo_estado_catedra()
    {
		return $this->get_estado_activo_baja();
    }

	/**
        Retorna las opciones A-Activo / B-Baja 
    */
    function get_estado_activo_baja()
    {
		return array(array('valor' => comunes::estado_activo,'descr' => comunes::estado_activo_desc),
		             array('valor' => comunes::estado_baja,'descr' => comunes::estado_baja_desc)
		            );
    }
	
	function get_estado_activo_baja_pendiente()
	{
		return array(array('valor' => comunes::estado_activo,'descr' => comunes::estado_activo_desc),
					 array('valor' => comunes::estado_baja,'descr' => comunes::estado_baja_desc),
					 array('valor' => comunes::estado_pendiente, 'descr' => comunes::estado_pendiente_desc)
					);
	}
	
	 /**
        Retorna las opciones Activo / Baja
    */
    function get_estado_activo()
    {
		return $this->get_estado_activo_baja();
    }
	
    /**
        Retorna las opciones Finalizado / Abandonado
    */
    function get_estado_finalizado_abandonado()
    {
		return array(array('valor' => 'F','descr' => 'Finalizado'),
                     array('valor' => 'A','descr' => 'Abandonado'),
                     array('valor' => 'C','descr' => 'En Curso'));
    }

	/**
        Retorna las opciones Activo / Inactivo
    */
	function get_estado_activo_inactivo()
	{
		return array(	array('valor' => 'A','descr' => 'Activo'),
						array('valor' => 'I','descr' => 'Inactivo')
					);
	}
	
	/**
        Retorna las opciones Todas / Algunas
    */
    function get_todas_algunas()
    {
		return array(	array('valor' => 'A','descr' => 'Algunas'), 
						array('valor' => 'T','descr' => 'Todas')
					);
    }
    
    /**
        Retorna las opciones A-Activo / C-Cumplido
    */
    function get_estado_activo_cumplido()
    {
		return array(array('valor' => 'A','descr' => 'Activo'),
		             array('valor' => 'C','descr' => 'Cumplido')
		            );
    }
  
  	/**
        Retorna los días de la semana
    */
    function get_dias_semana()
    {
    	return array(	array('valor' => comunes::lunes, 'descr' => 'Lunes'),
    					array('valor' => comunes::martes, 'descr' => 'Martes'),
    					array('valor' => comunes::miercoles, 'descr' => 'Miércoles'),
    					array('valor' => comunes::jueves, 'descr' => 'Jueves'),
    					array('valor' => comunes::viernes, 'descr' => 'Viernes'),
    					array('valor' => comunes::sabado, 'descr' => 'Sábado'),
		        	    array('valor' => comunes::domingo, 'descr' => 'Domingo')
		            );
    }

    /**
     * Retorna el nombre correspondiente a un día de la semana dado.
     * Los únicos casos en que la clave y el nombre difieren son los días con acento: miércoles y sábado.
     *
     * @param string $dia_semana
     * @return string
     */
	function get_nombre_dia_semana($dia_semana)
	{
		switch ($dia_semana) {
			case comunes::miercoles:
				return 'Miércoles';
			case comunes::sabado:
				return 'Sábado';
			default:
				return $dia_semana;
		}
	}

	function get_nombres_interfaces($interfaces=null)
	{
		$where = "WHERE ";
		if (isset($interfaces)) {
			if (is_array($interfaces)) {
				$where .= guarani_sql::armar_condicion_in($interfaces, 'acc_interfaces', 'interfaz');
			} else {
				$interfaces = toba::db()->quote($interfaces);
				$where .= "acc_interfaces.interfaz = $interfaces";
			}
		} else {
			$where = '';
		}
		
		$sql = "SELECT	interfaz,
						nombre as interfaz_nombre,
						descripcion
				FROM acc_interfaces
				$where;
				";
		
		return guarani_db::consultar($sql);
	}
	
	function get_interfaces()
	{
		$sql = "SELECT	interfaz as valor,
						nombre as descr
				FROM acc_interfaces;
				";
		
		return guarani_db::consultar($sql);
	}
	
	function get_interfaces_todas()
	{
		$aux = array(array('valor' => comunes::interfaz_todas, 'descr' => 'Todas las interfaces'));		
		$datos = static::get_interfaces();
		return array_merge($aux, $datos);
	}
	function get_interfaces_todas_no_preinscripcion() {
        $aux = array(array('valor' => comunes::interfaz_todas, 'descr' => 'Todas las interfaces'));
        $datos = static::get_interfaces();

        $valor_a_quitar = 'Preinscripcion';
        foreach ($datos as $clave => $elemento) {
            if ($elemento['descr'] == $valor_a_quitar) {
                unset($datos[$clave]);
            }
        }

        $datos = array_values($datos);
        return array_merge($aux, $datos);
    }
	function get_interfaces_no_preinscripcion() {
        $datos = static::get_interfaces();

        $valor_a_quitar = 'Preinscripcion';
        foreach ($datos as $clave => $elemento) {
            if ($elemento['descr'] == $valor_a_quitar) {
                unset($datos[$clave]);
            }
        }

        $datos = array_values($datos);
        return $datos;
    }
    function get_anios($cantidad='70')
	{		
		if ($cantidad < 0) {
			return array();
		}
		$anio = date('Y');
		$salida = array();
		for ($i = 0; $i < $cantidad; $i++) {
			$salida[] = array('valor' => $anio, 'descr' => $anio);
			$anio--;
		}
		return $salida;
	}
	
	function get_tipos_estudios_superiores()
	{
		$opciones = [
			'1' => 'Pública',
			'2' => 'Privada'
		];
		return $opciones;
	}
	
	function get_areas_estudios_sup()
	{
		$opciones = [
			'1' => 'Educación',
			'2' => 'Ciencias Básicas',
			'3' => 'Ciencias Aplicadas',
			'4' => 'Ciencias Sociales',
			'5' => 'Ciencias Jurídicas',
			'6' => 'Ciencias Humanas',
			'7' => 'Ciencias de la Salud',
			'8' => 'Sin especificación'
		];
		return $opciones;
	}
	
}
?>