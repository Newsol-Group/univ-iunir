<?php
/**
 *
 *
 * @package 	Matrícula
 * @subpackage 	Regularidad
 * 
 */
class pro_verificar_regularidad_alumno_nucleo extends guarani_procesos_bk
{
	protected $alumnos = array();
	protected $anio_academico_a_evaluar;
	protected $fecha_control_desde;
	protected $fecha_control_hasta;
	protected $nro_resolucion;
	protected $fecha_venc_readmision;

	const reporte_cantidades = 1;
	const reporte_perdieron_regularidad = 2;
	const reporte_recuperaron_regularidad = 3;
	const nombre = 'pro_verificar_regularidad_alumno';

	function set_datos($datos = array())
	{
		$this->alumnos = $datos['alumnos'];
		$this->anio_academico_a_evaluar = $datos['anio_academico'];
		$this->fecha_control_desde = $datos['fecha_control_desde'];
		$this->fecha_control_hasta = $datos['fecha_control_hasta'];
		$this->nro_resolucion = $datos['nro_resolucion'];
		$this->fecha_venc_readmision = $datos['fecha_venc_readmision'];
		
	}

	/**
	 * Registra la pérdida de regularidad del alumno con los datos dados y evalúa si debe realizarse una
	 * readmisión automática, retornando true si la misma fue insertada o false en caso contrario. Si no
	 * se realiza la readmisión para el alumno también se cambia correspondientemente la calidad de regular
	 * del mismo a N.
	 * @param int $alumno alumno al cual se le registra la pérdida de regularidad y evalúa readmisión o cambio 
	 * de calidad regular
	 * @param array $requisitos_no_cumplidos arreglo de requisitos que el alumno no cumple a la fecha deseada
	 * @param date $fecha_desde fecha desde con la que se corrieron los controles de requisitos
	 * @param date $fecha_hasta fecha hasta con la que se corrieron los controles de requisitos
	 * @param date $fecha_venc_readmision fecha seleccionada para que venza la nueva readmisión automática de
	 * insertarse.
	 * @return boolean retorna true en caso de que se haya registrado una readmisión automática para el alumno
	 * que perdió la regularidad o false en caso contrario.
	 * @throws guarani_error error si no se pudieron insertar correctamente los datos de la pérdida de 
	 * regularidad y la readmisión si corresponde.
	 */
	function registrar_perdida_regularidad_readmision($alumno, $requisitos_no_cumplidos, $fecha_desde, $fecha_hasta, $fecha_venc_readmision)
	{
		try {
			toba::db()->abrir_transaccion();

			// Parámetros del sistema
			$atributos = array($alumno['propuesta']);
			$cant_readmisiones_auto = guarani::parametros()->get_valor('mat_cnt_auto_readmisiones_alumno', $atributos);
			
			$cant_readmisiones = toba::consulta_php('co_alumnos')->get_cant_readmisiones($alumno['alumno']);

			// Se registra la pérdida de regularidad del alumno
			$cant_perdidas_reg = toba::consulta_php('co_alumnos')->get_cant_perdidas_regularidad($alumno['alumno'], $this->anio_academico_a_evaluar) + 1;
			$perdida_regularidad = guarani::act('act_vencimientos_regularidad')->insertar_perdida_regularidad($alumno['alumno'], $this->anio_academico_a_evaluar, guarani_fecha::get_hoy(), $this->nro_resolucion, $cant_perdidas_reg, $fecha_desde, $fecha_hasta);
			foreach ($requisitos_no_cumplidos as $requisito) {
				guarani::act('act_vencimientos_regularidad')->insertar_perdida_regularidad_det($perdida_regularidad, $requisito['causa_perdida_reg'], null);
			}
			// Se verifica si se debe readmitir automáticamente
			$readmision_auto = comunes::no_desc;
			if ($cant_readmisiones < $cant_readmisiones_auto) {
				// Se inserta readmisión del alumno
				guarani::act('act_alumnos')->insertar_readmision($perdida_regularidad, $this->anio_academico_a_evaluar, vencimiento_regularidad::readmision_tipo_reincorporacion, comunes::si, guarani_fecha::get_hoy(), $fecha_venc_readmision);
				guarani::act('act_alumnos')->actualizar_cant_readmisiones($alumno['alumno'], $cant_readmisiones + 1);
				$readmision_auto = comunes::si_desc;
			} else {
				// El alumno pierde le regularidad
				guarani::act('act_alumnos')->actualizar_regularidad($alumno['alumno'], comunes::no);
			}
			toba::db()->cerrar_transaccion();
			return $readmision_auto;
		} catch (guarani_error $e) {
			toba::db()->abortar_transaccion();
			throw new guarani_error($e->getMessage());
		}
	}
	
	function procesar()
	{
		$procesados = 0;
		$cant_perdieron_regularidad = 0;
		$cant_recuperaron_regularidad = 0;

		if (empty($this->alumnos)) {
			$this->log_estado->incrementar_progreso(100);
		} else {
			$paso = 100 / guarani_arreglos::count($this->alumnos);
			/**
			 * Para cada alumno se le corren las reglas para saber si cumple con los requisitos de regularidad.
			 * Luego si no cumple con estos requisitos se le registra la perdida de regularidad.
			 */
			foreach ($this->alumnos as $alumno) {
				// Control de aborto en pro
				$estado = $this->get_estado();
				if ((isset($estado['abortar'])) && ($estado['abortar'])) {
					$this->abortar = true;
					break;
				}
				$procesados++;	

				/**
				 * Dependiendo de si el alumno es regular y si cumple con los requisitos, se
				 * le cambia la regularidad y se lo registra en el reporte que corresponda.
				 */
				if ($alumno['regular'] == comunes::no) {
					//---- NO REGULAR ----------------------------------------------
					// Se verifica si el alumno cumple o no con todos los requisitos de regularidad a la fecha 
					// seleccionada en el proceso como 'fecha_hasta'.
					$filtro = "sga_propuestas_regularidad.para_controlar IN ('".vencimiento_regularidad::para_controlar_regularidad_readmisiones."', '".vencimiento_regularidad::para_controlar_regularidad."')";
					$causas_perdida_regularidad = toba::consulta_php('co_readmisiones')->get_causas_perdida_regularidad_propuesta_ubicacion($alumno['propuesta'], $alumno['ubicacion'], $filtro);
					$requisitos_no_cumplidos = vencimiento_regularidad::get_requisitos_no_cumplidos($alumno, $causas_perdida_regularidad, $this->anio_academico_a_evaluar, $this->fecha_control_desde, $this->fecha_control_hasta);
					if (empty($requisitos_no_cumplidos)) {
						//---- CUMPLE TODOS LOS REQUISITOS -------------------------
						try {
							toba::db()->abrir_transaccion();

							// El alumno recupera la regularidad
							guarani::act('act_alumnos')->actualizar_regularidad($alumno['alumno'], comunes::si);

							// Si existen registros de pérdida de regularidad para el alumno y el año 
							// académico que se están evaluando, se eliminan dichos registros.
							guarani::act('act_vencimientos_regularidad')->eliminar_perdida_regularidad_alumno($alumno['alumno'], $this->anio_academico_a_evaluar);

							// Se registra en el reporte de alumnos que recuperaron la regularidad
							$mensaje = array();
							$mensaje['alumno'] = $alumno['alumno'];
							$mensaje['apellido_nombres'] = $alumno['apellido_nombres'];
							$mensaje['tipo_nro_documento'] = $alumno['tipo_nro_documento'];
							$mensaje['propuesta_descr'] = $alumno['propuesta_descr'];
							$this->log->set($mensaje, array(self::reporte_recuperaron_regularidad));
							$cant_recuperaron_regularidad++;

							toba::db()->cerrar_transaccion();
						} catch (guarani_error $e) {
							toba::db()->abortar_transaccion();
							throw new guarani_error($e->getMessage());
						}
					}
					// Si no es regular y no cumple los requisitos a la fecha indicada en el proceso, no cambia nada.
					//---- FIN NO REGULAR ------------------------------------------
				} else {
					//---- ES REGULAR AHORA ----------------------------------------
					// Se obtiene (si existe) la última readmision del alumno en el año académico a evaluar (la de
					// mayor fecha de vencimiento).
					$readmision = toba::consulta_php('co_alumnos')->get_ultima_readmision($alumno['alumno'], $this->anio_academico_a_evaluar);
					if (empty($readmision)) {
						//---- NO TIENE NINGUNA READMISIÓN EN EL AÑO ACADÉMICO ACTUAL -------------------------
						// Se verifica si el alumno cumple o no con todos los requisitos de regularidad a la fecha 
						// seleccionada en el proceso como 'fecha_hasta'.
						$filtro = "sga_propuestas_regularidad.para_controlar IN ('".vencimiento_regularidad::para_controlar_regularidad_readmisiones."', '".vencimiento_regularidad::para_controlar_regularidad."')";
						$causas_perdida_regularidad = toba::consulta_php('co_readmisiones')->get_causas_perdida_regularidad_propuesta_ubicacion($alumno['propuesta'], $alumno['ubicacion'], $filtro);
						$requisitos_no_cumplidos = vencimiento_regularidad::get_requisitos_no_cumplidos($alumno, $causas_perdida_regularidad, $this->anio_academico_a_evaluar, $this->fecha_control_desde, $this->fecha_control_hasta);
						if (!empty($requisitos_no_cumplidos)) {
							//---- NO CUMPLE TODOS LOS REQUISITOS -------------------------
							// Debe registrarse la pérdida de regularidad ya que no tiene ninguna readmisión.
							// Adicionalmente se evalúa si corresponde generarle una readmisión automática.
							$readmision_auto = $this->registrar_perdida_regularidad_readmision($alumno, $requisitos_no_cumplidos, $this->fecha_control_desde, $this->fecha_control_hasta, $this->fecha_venc_readmision);
							// Se registra en el reporte de alumnos que perdieron la regularidad
							$mensaje = array();
							$mensaje['alumno'] = $alumno['alumno'];
							$mensaje['apellido_nombres'] = $alumno['apellido_nombres'];
							$mensaje['tipo_nro_documento'] = $alumno['tipo_nro_documento'];
							$mensaje['propuesta_descr'] = $alumno['propuesta_descr'];
							$causas = aplanar_matriz($requisitos_no_cumplidos, 'descripcion');
							$mensaje['causa_perdida'] = implode(', ', $causas);
							$mensaje['readmision_auto'] = $readmision_auto;

							// Mensaje para el log
							$this->log->set($mensaje, array(self::reporte_perdieron_regularidad));
							$cant_perdieron_regularidad++;
						}
						// Si es regular, no tiene ninguna readmisión en el año académico evaluado y cumple todos
						// requisitos a la fecha indicada como 'fecha_hasta' en el proceso, no cambia nada
					} else {
						//---- TIENE READMISIÓN EN EL AÑO ACADÉMICO ACTUAL. SE VERIFICA SI ESTÁ VENCIDA ---------
						// Si el alumno es regular y tiene una readmisión vigente no se hace nada
						if (guarani_fecha::es_menor_a_fecha_actual($readmision['fecha_vencimiento'])) {
							// Se verifica si el alumno tiene cumplidos los requisitos a la fecha de vencimiento 
							// de la readmisión.
							$filtro = "sga_propuestas_regularidad.para_controlar IN ('".vencimiento_regularidad::para_controlar_regularidad_readmisiones."', '".vencimiento_regularidad::para_controlar_readmisiones."')";
							$causas_perdida_regularidad = toba::consulta_php('co_readmisiones')->get_causas_perdida_regularidad_propuesta_ubicacion($alumno['propuesta'], $alumno['ubicacion'], $filtro);
							$requisitos_no_cumplidos = vencimiento_regularidad::get_requisitos_no_cumplidos($alumno, $causas_perdida_regularidad, $this->anio_academico_a_evaluar, $readmision['fecha_control_desde'], $readmision['fecha_vencimiento']);
							if (empty($requisitos_no_cumplidos)) {
								// La readmisión está vencida pero el alumno cumplió los requisitos a la fecha de 
								// vencimiento de la misma, por lo que sigue siendo regular y la readmisión se marca como 
								// controlada.
								guarani::act('act_vencimientos_regularidad')->actualizar_estado_readmision($readmision['readmision'], vencimiento_regularidad::readmision_controlada);
							} else {
								// La readmisión está vencida y el alumno no cumplió los requisitos en su fecha de vencimiento
								// Debe registrarse la pérdida de regularidad y evaluar si corresponde su readmisión automática.
								$readmision_auto = $this->registrar_perdida_regularidad_readmision($alumno, $requisitos_no_cumplidos, $readmision['fecha_control_desde'], $readmision['fecha_vencimiento'], $this->fecha_venc_readmision);
								// Se registra en el reporte de alumnos que perdieron la regularidad
								$mensaje = array();
								$mensaje['alumno'] = $alumno['alumno'];
								$mensaje['apellido_nombres'] = $alumno['apellido_nombres'];
								$mensaje['tipo_nro_documento'] = $alumno['tipo_nro_documento'];
								$mensaje['propuesta_descr'] = $alumno['propuesta_descr'];
								$causas = aplanar_matriz($requisitos_no_cumplidos, 'descripcion');
								$mensaje['causa_perdida'] = implode(', ', $causas);
								$mensaje['readmision_auto'] = $readmision_auto;

								// Mensaje para el log
								$this->log->set($mensaje, array(self::reporte_perdieron_regularidad));
								$cant_perdieron_regularidad++;
							}
						}
					}
				}

				// Se registra en el log
				$this->log->guardar();
				$this->log_estado->incrementar_progreso($paso);
			}
		}

		// Reporte de cantidades de alumnos
		$mensaje = array();
		$mensaje['procesados'] = $procesados;
		$mensaje['perdieron_regularidad'] = $cant_perdieron_regularidad;
		$mensaje['recuperaron_regularidad'] = $cant_recuperaron_regularidad;
		$this->log->set($mensaje, array(self::reporte_cantidades));
		$this->log->guardar();
	}
}
?>