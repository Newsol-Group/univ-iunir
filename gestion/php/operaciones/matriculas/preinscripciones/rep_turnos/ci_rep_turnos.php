<?php
class ci_rep_turnos extends ci_rep_sin_listado_inicial
{

    function get_datos($filtro)
    {
		//Trae los turnos asignado a los filtros cargados
        $preinscripcion_con_autogestion = toba::consulta_php('co_turnos_preinscripcion')->rep_listado_turnos_config($filtro);
		//Divido y completo los datos dependiendo de la fuente 3w o preinscripcion
		return toba::consulta_php('co_turnos_preinscripcion')->rep_listado_turnos_pre_y_autogestion($preinscripcion_con_autogestion);
    }

    function get_tabla_principal()
    {
        return 'pre_turnos_config';
    }

    //--------------------------------------------------------------------------
    //----- filtro -------------------------------------------------------------
    //--------------------------------------------------------------------------

    function conf__filtro(toba_ei_filtro $filtro)
    {
        parent::conf__filtro($filtro);
		$filtro->columna('fecha')->borrar_condicion('es_distinto_de');
    }

    function evt__filtro__filtrar($datos)
    {
		$filtro_configuracion = '';
		if(isset($datos['configuracion'])){
			$config = toba::db()->quote($datos['configuracion']['valor']);
			$filtro_configuracion .= "AND pre_turnos_config.turno_config = $config";
			$this->dep('filtro')->columna('configuracion')->resetear_estado();
		}
		$filtro_racademica = '';
        if (isset($datos['responsables_academicas'])) {
            $ra = toba::db()->quote($datos['responsables_academicas']['valor']);
            $filtro_racademica .= " AND pre_turnos_config_ra.responsable_academica = $ra";
			$this->dep('filtro')->columna('responsables_academicas')->resetear_estado();
        }
		$filtro_ubicaciones = '';
        if (isset($datos['ubicaciones'])) {
            $ubicaciones = toba::db()->quote($datos['ubicaciones']['valor']);
            $filtro_ubicaciones .= " AND pre_turnos_config_ubicacion.ubicacion = $ubicaciones ";
			$this->dep('filtro')->columna('ubicaciones')->resetear_estado();
        }
		
        if (isset($datos) && !empty($datos)) {
			$this->s__datos = $datos;
			$this->s__filtro = " {$this->dep('filtro')->get_sql_where()} $filtro_configuracion $filtro_racademica $filtro_ubicaciones";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
    }
	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

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
			$this->indx_msg_eof .= '_sinfiltro';
		} else {
			//El usuario presiono filtrar (ya sea con datos o con el filtro vacío).
			$datos = $this->get_datos($this->s__filtro);
			$cuadro->set_datos($datos);
		}
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof, array("turnos de Preinscripción asignados con el filtro especificado")));
	}
}
?>