<?php
class ci_rep_evaluaciones_parciales extends ci_rep_sin_listado_inicial
{
    protected $s__seleccion;

    public function get_tabla_principal() 
    {
        return 'sga_evaluaciones';
    }
    
    //-----------------------------------------------------------------------------------
    //---- Cuadro -----------------------------------------------------------------------
    //-----------------------------------------------------------------------------------
    
    public function get_datos($filtro) 
    {
        return toba::consulta_php('co_evaluaciones')->get_reporte_evaluaciones_parciales($filtro);
    }

    public function evt__cuadro__seleccion($seleccion)
    {
        $this->s__seleccion = $seleccion;
        $this->set_pantalla('pant_detalle');
    }
    
    function conf__cuadro_detalle(guarani_pers_ei_cuadro $cuadro)
    {
        return toba::consulta_php('co_evaluaciones')->get_alumnos_evaluacion($this->s__seleccion['evaluacion']);
    }
    
    //-----------------------------------------------------------------------------------
    //---- Filtro -----------------------------------------------------------------------
    //-----------------------------------------------------------------------------------    
    
    function conf__filtro(\toba_ei_filtro $filtro) {
        parent::conf__filtro($filtro);
        $filtro->columna('propuesta')->set_condicion_fija('es_igual_a');
        $filtro->columna('responsable_academica')->set_condicion_fija('es_igual_a');
    }


    function evt__filtro__filtrar($datos)
    {
        if (isset($datos)) {
            $this->s__datos = $datos;
            $filtro_propuesta = '';
            if (isset($datos['propuesta'])) {
                    $filtro_propuesta .= "AND vw_comisiones.comision IN (SELECT comision FROM sga_comisiones_propuestas WHERe propuesta = '{$datos['propuesta']['valor']}')";
                    $this->dep('filtro')->columna('propuesta')->resetear_estado();
            }
            $filtro_ra = '';
            if (isset($datos['responsable_academica'])) {
                    $filtro_propuesta .= " AND vw_comisiones.elemento IN (SELECT elemento FROM sga_elementos_ra WHERE responsable_academica = '{$datos['responsable_academica']['valor']}')";
                    $this->dep('filtro')->columna('responsable_academica')->resetear_estado();
            }
            $this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro_propuesta $filtro_ra";
        } else {
            unset($this->s__datos);
            $this->s__filtro = '';
        }
        //JOIN 	sga_elementos_ra ON sga_elementos_ra.elemento = vw_comisiones.elemento
        //JOIN	sga_comisiones_propuestas ON sga_comisiones_propuesta.comision = vw_comisiones.comision        
    }
    //-----------------------------------------------------------------------------------
    //---- Eventos ----------------------------------------------------------------------
    //-----------------------------------------------------------------------------------

     function ev_info()
    {
        if (!isset($this->ev_info)) {
            $this->ev_info = guarani::ev_info_datos();
            $this->ev_info->set_columnas_a_mostrar(['anio_academico', 'periodo_lectivo_nombre', 'ubicacion_nombre', 
                                                    'actividad_nombre', 'comision_nombre', 'evaluacion_nombre', 'fecha_display']);

            $this->ev_info->set_columnas_fila(['anio_academico', 'periodo_lectivo_nombre'], 1);
            $this->ev_info->set_columnas_fila(['actividad_nombre', 'comision_nombre', 'ubicacion_nombre'], 2);
            $this->ev_info->set_columnas_fila(['evaluacion_nombre', 'fecha_display'], 3);
            
            $this->ev_info->set_etiqueta('anio_academico', 'Año Académico');
            $this->ev_info->set_etiqueta('periodo_lectivo_nombre', 'Período Lectivo');
            $this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
            $this->ev_info->set_etiqueta('actividad_nombre', 'Actividad');
            $this->ev_info->set_etiqueta('comision_nombre', 'Comisión');
            $this->ev_info->set_etiqueta('evaluacion_nombre', 'Evaluación');
            $this->ev_info->set_etiqueta('fecha_display', 'Fecha');

        }
        return $this->ev_info;
    }
    
    public function conf__pant_detalle(guarani_ei_pantalla $pantalla)
    {
        $filtro = ' evaluacion = '.$this->s__seleccion['evaluacion'];
        $datos = current($this->get_datos($filtro));
        $this->ev_info()->set_datos($datos);        
    }
    
    function evt__volver()
    {
        $this->set_pantalla('pant_inicial');
    }

    function cuadro() 
    {
        return $this->dep('cuadro_detalle');
    }
    
}
?>