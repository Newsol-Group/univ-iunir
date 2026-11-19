<?php
class ci_rep_optativas extends ci_reporte_ficha_alumno
{	
	
    function conf__cuadro(toba_ei_cuadro $cuadro)
    {		
        $datos = $this->get_datos('');
        $cuadro->set_datos($datos);
        if (empty($datos)) {
            $msg = guarani_mensajes::get('800_eof_cuadro', array('Optativas'));
            $cuadro->set_eof_mensaje($msg);
            $this->pantalla('pant_inicial')->eliminar_evento('pdf');
        }
    }
	
    public function get_datos($filtro)
    {
        $persona = $this->controlador()->controlador()->get_persona_seleccionada();
        $where = " sga_alumnos.persona = $persona";		
        $optativas = toba::consulta_php('co_alumnos')->get_reporte_optativas($where);

        $historia_basica = guarani::co('co_alumnos')->get_hist_academica_basica($persona);	

        //Voy a guardar las genericas que ya evalue.
        $genericas_evaluadas = [];
        $optativas_sin_generica = [];
        
        foreach($optativas as $clave => $optativa) {
            
            if($optativa['optativa_compartida'] == 1 || $optativa['optativa_compartida'] == 0) {
                            
                /*
                 * Si la generica está evaluada no valido sino que busco su valor en
                 * el array de genericas evaluadas
                 */
                if (!in_array($optativa['generica_elemento'], array_keys($genericas_evaluadas))) {
                                        
                    $regla = guarani::regla($optativa['regla']);
                    $regla->set_parametros($optativa['parametros_regla']);
                    $regla->set_parametros_contexto(array(  'alumno' => $optativa['alumno'],
                                                            'persona' =>  $optativa['persona'],
                                                            'plan_version' => $optativa['plan_version'],
                                                            'elemento' => $optativa['generica_elemento'],
                                                            'fecha' => date('Y-m-d')));
                    
                    $cumplida = $regla->validar();
                    $genericas_evaluadas[$optativa['generica_elemento']]['cumplida'] = 'No';
                    $genericas_evaluadas[$optativa['generica_elemento']]['cumplida_descr'] = 'No';                
                    if ($cumplida) {
                       $genericas_evaluadas[$optativa['generica_elemento']]['cumplida'] = 'Sí';
                        $genericas_evaluadas[$optativa['generica_elemento']]['cumplida_descr'] = 'Sí';
                    }
                }
                $optativas[$clave]['cumplida'] = $genericas_evaluadas[$optativa['generica_elemento']]['cumplida'];
                $optativas[$clave]['cumplida_descr'] = $genericas_evaluadas[$optativa['generica_elemento']]['cumplida_descr'];

                $optativas[$clave]['corte_desc'] = $optativas[$clave]['generica_descripcion'].'<br>'.
                                                  'Forma de Cumplimiento: '.$optativas[$clave]['forma_cumplimiento'].'<br>'.                                              
                                                  'Cumplida: '.$optativas[$clave]['cumplida'];
                /*
                 * Verifico si está aprobada, regularizada o cursando
                 */			
                if (in_array($optativa['optativa'], array_column($historia_basica, 'elemento')))
                {
                    $key = array_search($optativa['optativa'], array_column($historia_basica, 'elemento'));
                    $optativas[$clave]['resultado'] = $historia_basica[$key]['tipo'];
                }
            } else {
                //Si no esta asignada a nada tengo que mostrar ese CC
                
                if ($optativa['optativa_compartida'] == -1 && !in_array($optativa['optativa'], $optativas_sin_generica)) {
                    $optativas_sin_generica[] = $optativa['optativa'];                    
                    $optativas[$clave]['generica_elemento'] = '0';
                    $optativas[$clave]['corte_desc'] = 'Actividades Optativas Compartidas sin Genérica Asignada';
                    
                } else {
                    //Tiene el valor 2.
                    unset($optativas[$clave]);
                }
            }
        }

        return $optativas;
    }
	
    public function get_actividades($persona)
    {

    }
}