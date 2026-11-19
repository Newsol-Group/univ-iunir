<?php
class ci_rep_eval_parciales extends ci_reporte
{

    protected $s__filtro;

    //-----------------------------------------------------------------------------------
    //---- Configuraciones --------------------------------------------------------------
    //-----------------------------------------------------------------------------------

    function conf()
    {
    }

    function conf__pant_inicial(toba_ei_pantalla $pantalla)
    {
        if (!isset($this->s__filtro)) {
            $pantalla->eliminar_dep('cuadro');
        }
    }
    
    function get_propuestas_persona()
    {
        $persona = $this->controlador()->controlador()->get_persona_seleccionada();
        return toba::consulta_php('co_personas')->get_propuestas_persona($persona);
    }
    
    function get_actividades_propuesta($propuesta)
    {
        $persona = $this->controlador()->controlador()->get_persona_seleccionada();
        return toba::consulta_php('co_alumnos')->get_actividades_planes_versiones_alumno(null, $persona, $propuesta);
    }

    //-----------------------------------------------------------------------------------
    //---- filtro_evaluaciones ----------------------------------------------------------
    //-----------------------------------------------------------------------------------

    function conf__filtro_evaluaciones(guarani_pers_ei_formulario $form)
    {
    if (isset($this->s__filtro)) {
        return $this->s__filtro;
    }
    }

    function evt__filtro_evaluaciones__filtrar($datos)
    {
        $this->s__filtro = $datos;
    }

    function evt__filtro_evaluaciones__cancelar()
    {
        unset($this->s__filtro);
    }

    //-----------------------------------------------------------------------------------
    //---- cuadro -----------------------------------------------------------------------
    //-----------------------------------------------------------------------------------

    function conf__cuadro(toba_ei_cuadro $cuadro)
    {
        $cuadro->set_filtro_asociado($this->dep('filtro_evaluaciones'));
        $datos = $this->get_datos($this->s__filtro);
        $cuadro->set_datos($datos);
        $cuadro->set_eof_mensaje($this->get_msg_eof());
    }
    
    public function get_datos($filtro) 
    {
        $persona = $this->controlador()->controlador()->get_persona_seleccionada();
        $propuesta = $this->s__filtro['propuesta'];
        $actividad = $this->s__filtro['actividad'];
        $anio_academico = $this->s__filtro['anio_academico'];        
        
        return toba::consulta_php('co_alumnos')->get_evaluaciones_parciales($persona, $propuesta, $actividad, $anio_academico);
    }

    public function get_tabla_principal() 
    {
        return 'sga_evaluaciones';
    }

}

?>