<?php
class cn_man_reasignar_optativas extends guarani_pers_cn
{
	protected $s__alumno;
	protected $s__nodo;
	protected $s__genericas = null;
    protected $s__temp_creada = false;
    protected $s__aprobadas = array();
    protected $s__regularizadas = array();
            
	function set_alumno($datos)
	{
		$this->s__alumno = $datos;
	}

	function get_alumno()
	{
		return $this->s__alumno;
	}

	function set_nodo($nodo)
	{
		$this->s__nodo = $nodo;
	}

	function get_nodo()
	{
		return $this->s__nodo;
	}

	function get_listado($filtro)
	{
		return guarani::co('co_inscripciones')->get_inscriptos_activos($filtro);
	}

	function get_indice_instancia()
	{
		return 'reasignar_optativas';
	}

	function get_generica()
	{
		return $this->s__genericas[$this->get_nodo()];
	}

	function limpiar_variables()
	{
        $this->s__temp_creada = false;
        unset($this->s__aprobadas);
        unset($this->s__regularizadas);
		unset($this->s__genericas);
	}

	/*
	 * Obtengo las actividades genericas que poseen optativas compartidas
	 */
	function get_actividades_genericas()
	{
        //Si las genericas ya fueron validadas con sus reglas, no se vuelve a hacer.
        if (!isset($this->s__genericas)) {
            $alumno = $this->get_alumno();
            $genericas = guarani::co('co_planes')->get_actividades_genericas_con_optativas_compartidas($alumno['plan_version']);
            $aux = array();
            $parametros_contexto = array('alumno' => $alumno['alumno'], 'plan_version' => $alumno['plan_version']);
            //traigo los certificados del alumno no anulados
            $generica_del_certificado = guarani::co('co_alumnos')->get_generica_certificado( $alumno['alumno']);

            foreach ($genericas as $generica) {

                $aux[$generica['elemento']] = $generica;
                $aux[$generica['elemento']]['cumplida'] = 'No';
                if ( ! in_array($generica['elemento'], array_column($generica_del_certificado, 'elemento') ) ){
                    /*
                     * Verifico cual es la regla para cumplimentar con la genérica, y valido si es true o false para informar al usuario.
                     */
                    $regla = guarani::regla($generica['regla']);
                    $parametros_contexto['elemento'] = $generica['elemento'];
                    $regla->set_parametros_contexto($parametros_contexto);
                    $regla->set_parametros($generica['parametros']);
                    if ($regla->validar()) {
                        $aux[$generica['elemento']]['cumplida'] = 'Sí';
                    }
                    $aux[$generica['elemento']]['certificado'] = comunes::no;
                } else {
                    $aux[$generica['elemento']]['certificado'] = comunes::si;
                }
            }
            $this->s__genericas = $aux;
        }
		return $this->s__genericas;
	}

    function recalcular_cumplimiento()
    {
        unset($this->s__genericas);
        $this->get_actividades_genericas();
    }
    
	/*
	 * Trae las optativas compartidas del plan de estudio
	 */
	function get_optativas($filtro, $inner = 'LEFT')
	{
		$alumno = $this->get_alumno();
		$optativas = guarani::co('co_elementos')->get_optativas_de_genericas($alumno['alumno'], $alumno['plan_version'], $filtro, $inner);
		
		$optativas_final = array();
		
		foreach ($optativas as $optativa) {
			if ($optativa['estado'] == 'B' && $optativa['optativa_estado'] == '') {
				//no muestra optativa dada de baja y sin cursar 
			} else {
				if (!isset($optativas_final[$optativa['optativa_elemento']])) {
					$optativas_final[$optativa['optativa_elemento']]['optativa_elemento'] = $optativa['optativa_elemento'];
					$optativas_final[$optativa['optativa_elemento']]['optativa_codigo'] = $optativa['optativa_codigo'];
					$optativas_final[$optativa['optativa_elemento']]['optativa_nombre'] = $optativa['optativa_nombre'];
					$optativas_final[$optativa['optativa_elemento']]['optativa_estado'] = '';
					$optativas_final[$optativa['optativa_elemento']]['puntaje'] = $optativa['puntaje'];
					$optativas_final[$optativa['optativa_elemento']]['creditos'] = $optativa['creditos'];
					$optativas_final[$optativa['optativa_elemento']]['generica_asignada'] = $optativa['generica_asignada'];
                    
                    //Verifico en mi array de aprobadas y de regularizadas si esta la actividad.
                    if (in_array($optativa['optativa_elemento'], $this->s__aprobadas)) {
                        $optativas_final[$optativa['optativa_elemento']]['optativa_estado'] = 'Aprobada';
                    } elseif(in_array($optativa['optativa_elemento'], $this->s__regularizadas)) {
                        $optativas_final[$optativa['optativa_elemento']]['optativa_estado'] = 'Regularizada';
                    }
				}
			}
		}
		return $optativas_final;		
	}

    /*
     * Recupero solo optativas compartidas de la generica seleccionada.
     */
	function get_disponibles($filtro)
	{
        $generica = $this->get_nodo();		
		$filtro = !empty($filtro) ? ($filtro . " AND vw_optativas_plan.elemento_generica = $generica ") : (" vw_optativas_plan.elemento_generica = $generica ");		
		return $this->get_optativas($filtro);
	}

    /*
     * Recupero solo optativas compartidas asignadas en la generica seleccionada.
     */
	function get_asignadas()
	{
		$generica = $this->get_nodo();
		$filtro = " vw_optativas_plan.elemento_generica = $generica ";
		return $this->get_optativas($filtro, 'INNER');
	}

	/*
	 * Asigna la optativa a la generica compartida seleccionada
	 */
	function asignar_optativa($optativa)
	{
		$this->eliminar_optativa($optativa);
		$alumno = $this->get_alumno();
		$nodo = $this->get_nodo();
		return guarani::act('act_alumnos')->insertar_optativa($alumno['alumno'], $alumno['plan_version'], $optativa, $nodo);
	}

	/*
	 * Elimina la optativa de la generica que estaba seleccionada
	 */
	function eliminar_optativa($optativa)
	{
		$alumno = $this->get_alumno();
		$nodo = $this->get_nodo();
		return guarani::act('act_alumnos')->eliminar_optativa($alumno['alumno'], $alumno['plan_version'], $optativa);
	}
    
    /*
     * Se crea una temporal de la ha del alumno para no tener que hacer la query
     * cada vez que se llenan los cuadros.
     */
    function crear_temporal_ha()
    {
        if (!$this->s__temp_creada) {
            $this->s__temp_creada = true;
            $alumno = $this->get_alumno();
            $resultado_aprobado = quote(escalas_notas::resultado_aprobado);

            //Voy a buscar las actividades aprobadas del alumno
            $aprobadas = toba::consulta_php('co_alumnos')->get_actividades_aprobadas_regularizadas($alumno['alumno'], 'A');
            $this->s__aprobadas = array_column($aprobadas, 'elemento');            
                        
            //Voy a buscar las actividades regularizadas de un alumno
            $regularizadas = toba::consulta_php('co_alumnos')->get_actividades_aprobadas_regularizadas($alumno['alumno'], 'R');
            $this->s__regularizadas = array_column($regularizadas, 'elemento');            
        }
    }

}