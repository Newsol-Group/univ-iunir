<?php
/**
 *
 * @todo 		Manejo de errores en controles configurables.
 * @package 	Matricula
 * @subpackage 	Actualizaciones
 * 
 */
class pro_modificar_mesas_masivamente_nucleo extends guarani_procesos_bk
{
	protected $datos_modificacion;
	protected $llamados_mesa;

	const reporte_modificadas = 1;
	const reporte_no_modificadas = 2;
	const reporte_resultados = 3;

	const nombre = 'pro_modificar_mesas_masivamente';

	function set_datos($datos=array())
	{
		$this->datos_modificacion = $datos['datos_modificacion'];
		$this->llamados_mesa = $datos['mesas_a_modificar'];
	}

	function procesar()
	{
		$paso = 100 / guarani_arreglos::count($this->llamados_mesa);
		$cant_modificadas = 0;
		$cant_no_modificadas = 0;
		
		$filtro = guarani_sql::armar_condicion_in($this->llamados_mesa, 'sga_llamados_mesa', 'llamado_mesa');
		$datos_llamados = guarani::co('co_mesas_de_examen')->get_listado($filtro);
		$datos_llamados = rs_convertir_asociativo_matriz($datos_llamados, array('llamado_mesa'));

		foreach ($datos_llamados as $llamado) {
            // Si desde la aplicación se mandó señal de corte, no se continúa con 
			// el procesamiento (lo procesado hasta el momento no se revierte).
			
			$estado = $this->get_estado();
			if ((isset($estado['abortar'])) && ($estado['abortar'])) {
				$this->abortar = true;
				break;
			}
                    
			$llamado_id = $llamado['llamado_mesa'];
			$cambio = $this->datos_modificacion['tipo_modificacion'];
			$motivo = $this->datos_modificacion['motivo'];
			try {
				toba::db()->abrir_transaccion();
				// Se recuperan las personas a notificar en caso de realizar la modificación de la mesa con éxito,
				// ya que en caso de suspensión las inscripciones de los alumnos serán eliminadas.
				$personas = toba::consulta_php('co_mesas_de_examen')->get_personas_a_notificar_modificacion_mesa_examen($llamado['llamado_mesa']);
				
				switch ($cambio) {
					case mesa_de_examen::cambio_suspension:
						// Se marca como suspendido el llamado mesa.
						guarani::act('act_mesas_de_examen')->suspender_llamado_mesa($llamado_id, $motivo);
						// Se obtiene el nro de transacción para la tabla de log de inscripciones.
						$nro_transaccion_log = toba::db()->recuperar_nuevo_valor_secuencia('aud_nro_transaccion_seq');
						// Se pasan al log las inscripciones a examen para el llamado, marcándolas como suspendidas
						guarani::act('act_inscripcion_examenes')->insertar_logs_inscripciones_llamado_mesa($llamado_id, $nro_transaccion_log);
						// Se eliminan las inscripciones de sga_insc_examen.
						guarani::act('act_inscripcion_examenes')->eliminar_inscripciones_llamado_mesa($llamado_id);
						break;
					case mesa_de_examen::cambio_fecha:
						$fecha = $this->datos_modificacion['fecha'];
						guarani::act('act_mesas_de_examen')->cambiar_fecha_llamado_mesa($llamado_id, $motivo, $fecha);
						// Se modifica el dato de fecha en los datos del llamado para el reporte de Mesas modificadas
						$llamado['fecha'] = $fecha;
						$formateo = new toba_formateo('html');
						$llamado['fecha_formato_visual'] = $formateo->formato_fecha($fecha);
						break;
					case mesa_de_examen::cambio_horario:
						$hora_inicio = $this->datos_modificacion['hora_inicio'];
						// Se modifican los horarios en los datos del llamado para el reporte de Mesas modificadas
						$llamado['hora_inicio'] = $hora_inicio;
						if (isset($this->datos_modificacion['hora_finalizacion'])) {
							$hora_fin = $this->datos_modificacion['hora_finalizacion'];
							$llamado['hora_finalizacion'] = $hora_fin;
						} else {
							$hora_fin = null;
						}
						guarani::act('act_mesas_de_examen')->cambiar_horario_llamado_mesa($llamado_id, $motivo, $hora_inicio, $hora_fin);
						break;
				}
			
				$this->log->set($llamado, array(self::reporte_modificadas));
				$cant_modificadas++;
				toba::db()->cerrar_transaccion();
				
				// Invoco al notificador para que envíe mensaje/email según corresponda.
				// Se invoca por cada Mesa de Examen que se modificó con exito, dado que las transacciones se
				// abren y cierran por cada llamado a modificar.
				$parametros = [
					'llamado_mesa' => $llamado['llamado_mesa'],
					'personas' => $personas,
					'tipo_modificacion' => $this->datos_modificacion['tipo_modificacion']
				];
				guarani::notificador()->enviar(notificador::modificar_mesas_masivamente, $parametros);
			} catch (toba_error $e) {
				$this->log_estado->set_error();
				$llamado['observaciones'] = $e->get_mensaje();
				$this->log->set($llamado, array(self::reporte_no_modificadas));
				$cant_no_modificadas++;
				toba::db()->abortar_transaccion();
			}

			$this->log->guardar();
			if (!$this->abortar) {
				$this->log_estado->incrementar_progreso($paso);
			}
		}

		$mensaje_resultado['cant_mesas_modificadas'] = $cant_modificadas;
		$mensaje_resultado['cant_mesas_no_modificadas'] = $cant_no_modificadas;
		$this->log->set($mensaje_resultado, array(self::reporte_resultados));
		$this->log->guardar();
	}
}
?>

