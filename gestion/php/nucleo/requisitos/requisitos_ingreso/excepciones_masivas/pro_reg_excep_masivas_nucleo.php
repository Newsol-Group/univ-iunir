<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class pro_reg_excep_masivas_nucleo extends guarani_procesos_bk
{
	protected $requisito;
	protected $requisitos_aspirante;
	protected $parametros_tabla;
	
	const nombre = 'pro_reg_excep_masivas';
	
	function set_datos($datos = [])
	{
		$this->requisito = $datos['requisito'];
		$this->requisitos_aspirante = $datos['requisitos_aspirante'];
		$this->parametros_tabla = $datos['parametros_tabla'];
	}
	
	function procesar()
	{
		$paso  = 100 / guarani_arreglos::count($this->requisitos_aspirante);
		$array = [];

		//Se recorren las Propuestas Aspira
		foreach ($this->requisitos_aspirante as $clave => $valor) {
                    
                    $estado = $this->get_estado();
                    if ((isset($estado['abortar'])) && ($estado['abortar'])) {
                        $this->abortar = true;
                        break;
                    }
                    
                    $es_inscrip_pendiente = $this->es_inscripcion_pendiente($valor['propuesta_aspira']);

                    //Si la Inscripción a Propuesta esta en estado Pendiente entonces lo agrego al array
                    if ($es_inscrip_pendiente){

                            //Creo el registro
                            $registro = [];
                            $registro['requisito_aspirante'] = $valor['requisito_aspirante'];
                            $registro['propuesta_aspira'] = $valor['propuesta_aspira'];
                            //Agrego el registro al array
                            array_push($array, $registro);
                    }

                    //Incremento el progreso para reflejarlo en la barra
                    $this->log_estado->incrementar_progreso($paso);
		}

		// Si hay excepciones a requisitos de ingreso
		if (!empty($array)) {

                    try {

                        toba::db()->abrir_transaccion();

                        //Registro las excepciones a requisitos
                        $condicion_in = guarani_sql::armar_condicion_in($array, 'sga_requisitos_aspirante', 'requisito_aspirante', 'requisito_aspirante');
                        guarani::act('act_requisitos_aspirante')->registrar_excepciones($condicion_in, $this->parametros_tabla);

                        // Recorro cada alumno para notificar a SQ si corresponde
                        foreach ($array as $alumno) {
                            // Decide si encola en Redis para notificar a SQ las inscripciones a Propuesta vía Rest-Hooks
                            cobro::notificar_inscripcion_propuesta_SQ($alumno['propuesta_aspira']);
                        }
                        
                        toba::db()->cerrar_transaccion();

                    } catch (Exception $e) {
                        toba::db()->abortar_transaccion();
                        // Si hubo algún error
                        echo "Error: {$e->getMessage()}\n";
                    }
		}
	}
	
	function es_inscripcion_pendiente($propuesta_aspira)
	{
		$datos_prop = toba::consulta_php('co_propuestas')->get_datos_propuesta_aspira($propuesta_aspira);
		return $datos_prop[0]['resultado_asp'] == propuesta::inscripcion_pendiente;
	}
	
}