<?php
/**
 * 
 *
 * @package		Matrícula
 * @subpackage 	Alumnos
 */

class pro_pasar_a_calidad_pasivo_nucleo extends guarani_procesos_bk
{
	protected $alumnos = array();
	protected $motivo;
	protected $documento;
	protected $observaciones;

	const nombre = 'pro_pasar_a_calidad_pasivo';
	const reporte_alumnos_pasados_a_pasivo = 1;
	const reporte_resultados = 2;
	const reporte_alumnos_fallidos = 3;

	function set_datos($datos=array())
	{
		$this->alumnos = $datos['alumnos'];
		$this->motivo = $datos['motivo'];
		$this->documento = $datos['documento'];
		$this->observaciones = $datos['observaciones'];
	}

	function procesar()
	{
		if (!empty($this->alumnos)) {
			$paso = 100/guarani_arreglos::count($this->alumnos);
		} else {
			$this->log_estado->incrementar_progreso(100);
		}

		$cant_alumnos = 0;
		foreach ($this->alumnos as $alumno) {
			
            $estado = $this->get_estado();
            if ((isset($estado['abortar'])) && ($estado['abortar'])) {
                $this->abortar = true;
                break;
            }
			
			$mensaje = array();
			$mensaje['alumno']				= $alumno['alumno'];
			$mensaje['legajo']				= $alumno['legajo'];
			$mensaje['apellido_y_nombre']	= $alumno['apellido_y_nombre'];
			$mensaje['propuesta_descr']		= $alumno['propuesta_descr'];
			$mensaje['ubicacion_nombre']	= $alumno['ubicacion_nombre'];
			$mensaje['documento_principal'] = $alumno['documento_principal'];

			toba::db()->abrir_transaccion();
			
			try {
				
				// Se actualiza la calidad del alumno se inserta el registro histórico de cambio de calidad.
				guarani::act('act_alumnos')->actualizar_calidad_alumno($alumno['alumno'], alumno::calidad_pasivo);
				guarani::act('act_alumnos')->insertar_cambio_calidad($alumno['alumno'], alumno::calidad_pasivo, $this->motivo, 'now()', $this->observaciones, $this->documento);

				/**
                                 * Desde aquí se encola en Redis para notificar a SQ vía Rest-Hooks
				 * @see https://redmine.siu.edu.ar/redmine/projects/guarani3/wiki/Casos_de_UsoPasivo_a_Alumnos_Activos Caso de uso.
				 */
				$propuesta_aspira = guarani::co('co_inscripciones')->get_id_ultima_propuesta_aspira($alumno['persona'], $alumno['propuesta']);
                                // Hay casos que viene en null, quizas debido a que se da de baja la inscripción
                                if (!is_null($propuesta_aspira)) {
                                    cobro::post_suscripciones_novedades_rest_hooks(cobro::TIPO_POSGRADO, $propuesta_aspira, cobro::TIPO_NOVEDAD_INACTIVA, inscripcion::operacion_cambio_calidad_pasivo);
                                }

				toba::db()->cerrar_transaccion();
				
				$cant_alumnos++;
				$this->log->set($mensaje, array(self::reporte_alumnos_pasados_a_pasivo));
				
			} catch (toba_error $e) {
				
				toba::db()->abortar_transaccion();
				// Si hubo algún error -> continua como antes
				echo "Error: {$e->get_mensaje()}\n";
				$this->log->set($mensaje, array(self::reporte_alumnos_fallidos));
				
			}
			
			$this->log->guardar();
			$this->log_estado->incrementar_progreso($paso);
				
		}

		$mensaje = array();
		$mensaje['cant_alumnos'] = $cant_alumnos;
		$this->log->set($mensaje, array(self::reporte_resultados));
		$this->log->guardar();
	}
}