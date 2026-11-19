<?php
namespace siu\modelo\entidades;
use siu\modelo\datos\catalogo;
use SIU\Chulupi\kernel;
use siu\modelo_g3\cobro;

/**
 * Valida cosas relacionadas con estado global:
 *		- superposicion horaria entre selecciones
 *		- tope de inscripciones
 * 
 * Provee informacion a la barra de estado de inscripciones
 * Provee informacion a los certificados y pantallas de inscripcion
 *
 * Esto no se maneja con el cache normal a sesion porque
 * es un bloque que se crea/limpia junto
 * 
 */
class alumno_estado_insc_cursada
{
	protected $persona;
	protected $inscripciones;

	function __construct($persona)
	{
		$this->persona = $persona;
	}

	function reset()
	{
		unset($this->inscripciones);
		catalogo::limpiar_cache('inscripcion_cursada','asignaciones',
							array('persona' => $this->persona));
		catalogo::limpiar_cache('inscripcion_cursada','inscripciones',
							array('persona' => $this->persona));
		
		// Limpiar reporte de inscripciones
		// estado = T - Todos los estados (Aceptado, Pendiente, Rechazadas, Bajas) / A - Aceptado / P - Pendientes / AP - Aceptadas y Pendientes / R - Rechazadas / B - Baja
		$estados = [
			'T',
			\inscripcion_cursada::estado_aceptada,
			\inscripcion_cursada::estado_pendiente,
			'AP',
			'R'
			];
		// vigentes = S - Solo de periodos lectivos actuales o futuros /  N - Vigentes e historicas
		$vigente = [\comunes::si, \comunes::no];
		foreach ($estados as $estado) {
			foreach ($vigente as $v) {
				$parametros_cursada = [
					'persona' => $this->persona,
					'estado' => $estado,
					'vigentes' => $v,
					'cursos' => false];
				catalogo::limpiar_cache('alumno', 'inscripciones_cursadas', $parametros_cursada);
			}
		}
	}

	//--------------------------------------------
	//
	//--------------------------------------------
	
	function info__asignaciones()
	{
		return catalogo::consultar('inscripcion_cursada','asignaciones',
							array('persona' => $this->persona));
	}

	function info__inscripciones()
	{
		if(!isset($this->inscripciones)) {
			$this->inscripciones = catalogo::consultar('inscripcion_cursada','inscripciones',
												array('persona' => $this->persona));
		}
		return $this->inscripciones;
	}
	
	function info__inscripcion($id_comision)
	{
		foreach($this->info__inscripciones() as $insc) {
			if($insc['comision']==$id_comision){
				return $insc;
			}
		}
		return array();
	}

	//--------------------------------------------
	// INFO
	//--------------------------------------------

	function get_ids_materias_inscripto()
	{
		$materias = array();
		foreach($this->info__inscripciones() as $inscripcion) {
			$materias[] = $inscripcion['actividad'];
		}
		return $materias;
	}

	function existe_inscripcion_materia($materia)
	{
		foreach($this->info__inscripciones() as $inscripcion) {
			if ( $materia == $inscripcion['actividad'] ) {
				return $inscripcion['comision'];
			}
		}
		return false;
	}
	
	/**
	 *
	 * @param type $actividad
	 * @param type $propuesta_tipo
	 * @return array devuelve un arreglo de comisiones a las que está inscripto
	 */
	function get_inscripciones($propuesta = null, $actividad = null, $propuesta_tipo = null) {
        $inscripciones = $this->info__inscripciones();

        $rs = array();
        foreach ($inscripciones as $inscripcion) {

            $inscripcion['error_btn_pagar'] = false;
            $inscripcion['pago_pendiente'] = false;

            //Si no se filtra ni por propuesta ni por actividad devuelvo todas las inscipciones
            $devolver =  \comunes::no;
            if (!isset($propuesta) && !isset($actividad) && !isset($propuesta_tipo)) {
                $devolver = \comunes::si;
            }
            //sino devuelvo solo las inscipciones de esa propuesta o actividad
            elseif ((isset($propuesta) && $propuesta == $inscripcion['propuesta']) ||
                    (isset($actividad) && $actividad == $inscripcion['actividad']) ||
                    (isset($propuesta_tipo) && $propuesta_tipo == $inscripcion['propuesta_tipo'])) {
                $devolver =  \comunes::si;
            }


            //Si la inscripción tiene token de SQ asociado
            if ($inscripcion['sq_token'] && $devolver ==  \comunes::si) {

                //Si el estado de la inscripción es PENDIENTE
                if ($inscripcion['estado'] == \inscripcion_cursada::estado_pendiente) {
                    $token_SQ = $inscripcion['sq_token'];

                    $query = array(
                        "token" => $token_SQ,
                    );

                    try {
                        $solicitud_consumo_externo = cobro::get_solicitudes_consumos_externos($query);
                        $solicitud_consumo_externo = current($solicitud_consumo_externo);

                        //Si el consumo externo es NUEVO (no fue procesado)
                        if ($solicitud_consumo_externo['codigo_procesamiento'] == cobro::CODIGO_PROCESAMIENTO_CONSUMO_EXTERNO_NUEVO) {
                            $inscripcion['pago_pendiente'] = true;
                        }
                    } catch (\Exception $e) {
                        $inscripcion['error_btn_pagar'] = true;
                        kernel::log()->add_debug("Error GET /solicitudes-consumos-externos", $e->getMessage());
                    }
                }
            } // Cobros.

            if ($devolver ==  \comunes::si) {
                $rs[] = $inscripcion;
            }
        } // Inscripciones

        kernel::log()->add_debug("Comisiones inscriptas:", $rs);
        return $rs;
    }

}