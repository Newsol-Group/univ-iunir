<?php

class pro_prueba extends guarani_procesos_bk
{
	protected $dato1;
	protected $ultimo_elemento;
	protected $actividades;

	const reporte_copiadas  = 1; // Comisiones Copiadas
	const reporte_no_copiadas  = 2; // Comisiones No Copiadas
	const reporte_errores  = 3; // Errores de Proceso

	const reporte_resultados = 4; // Reporte para mostrar cantidades, errores, exitos , procesados

	const nombre = 'pro_prueba';

	function set_datos($datos=array())
	{
		$this->dato1 = $datos['dato1'];
	}


	function copiar_comision($i)
	{
		if ($i > 3) {
			throw new toba_error('error con la sql');
		}
	}

	function procesar()
	{
		/****************************************************************************************************************************/
		/* Esto se agrega solamente si es necesario ver bajo la barra de progreso informacion sobre lo que esta haciendo el proceso */
		$this->log_estado->set('Proceso de copia iniciado', 'aviso');
		$this->log_estado->guardar();
		/****************************************************************************************************************************/
		$cant_elementos = $this->dato1;
		$paso = 100 / $cant_elementos;
		$cant_procesados	= 0;
		$copiadas			= 0;
		$no_copiadas		= 0;
		$errores			= 0;
		for ($i = 1; $i <= $cant_elementos; $i++) {
			// se abre la transaccion

			/****************************************************************************************************************************/
			/* Esto se agrega solamente si es necesario ver bajo la barra de progreso informacion sobre lo que esta haciendo el proceso */
			$this->log_estado->set('Procesando comisiones de la Actividad act_'.$i, 'aviso');
			$this->log_estado->guardar();
			/****************************************************************************************************************************/
			
			for ($j = 1; $j <= 2; $j++) {
				// proceso a
				$mensaje['actividad'] = 'act_'.$i;
				$mensaje['comision'] = 'com_'.$j;
				$mensaje['catedra'] = 'catedra';
				$mensaje['instancia'] = 'instancia';
				$mensaje['promocion'] = 'promo';
				$mensaje['escala'] = 'escala';

				//Columnas anexas para el Reporte de Comisiones con observaciones
				$mensaje['turno'] = 'Tarde';
				$mensaje['letra_desde'] = 'A';
				$mensaje['letra_hasta'] = 'Z';
				$mensaje['observaciones'] = "Actividad: ". $mensaje['actividad'] . " - Comision: ". $mensaje['comision'];
				$cant_procesados++;
				try {
					switch (true) {
						case ((1 <= $i) && ($i <= round($cant_elementos / 3, 0))):
							$this->copiar_comision($i);
							$this->log->set($mensaje, array(self::reporte_copiadas));
							$copiadas++;
							break;
						case ((round($cant_elementos / 3, 0) + 1 <= $i) && ($i <= round(2 * $cant_elementos / 3, 0))):
							$this->log->set($mensaje, array(self::reporte_no_copiadas));
							$no_copiadas++;
							break;
						case ((round(2 * $cant_elementos / 3, 0) + 1 <= $i) && ($i <= $cant_elementos)):
							$this->log->set($mensaje, array(self::reporte_no_copiadas));	//$this->log->set($mensaje, array(self::reporte_copiadas,self::reporte_no_copiadas));
							$no_copiadas++;
							break;
					}
					
				} catch (toba_error $e) {
					/****************************************************************************************************************************/
					/* Esto se agrega solamente si es necesario ver bajo la barra de progreso informacion sobre lo que esta haciendo el proceso */
					$this->log_estado->set('Error en la copia: '.$mensaje['observaciones'], 'error');
					$this->log_estado->guardar();
					/****************************************************************************************************************************/
					
					// se setea el error en el log de estados
					$this->log_estado->set_error();

					$mensaje['observaciones'] = 'Error en la copia ' . $mensaje['observaciones'];
					$this->log->set($mensaje, array(self::reporte_errores));
					$errores++;
					$this->log->guardar();
				}
			}
			// se cierra la transaccion

			$this->log->guardar();
			$this->log_estado->incrementar_progreso($paso);
			
		}
		$mensaje_resultado['cantidad_procesados']		= $cant_procesados;
		$mensaje_resultado['errores']					= $errores;
		$mensaje_resultado['comisiones_copiadas']		= $copiadas;
		$mensaje_resultado['comisiones_no_copiadas']	= $no_copiadas;
		$this->log->set($mensaje_resultado, array(self::reporte_resultados));
		$this->log->guardar();
		/****************************************************************************************************************************/
		/* Esto se agrega solamente si es necesario ver bajo la barra de progreso informacion sobre lo que esta haciendo el proceso */
		$this->log_estado->set('Proceso de copia terminado', 'aviso');
		$this->log_estado->guardar();
		/****************************************************************************************************************************/
	}

}

?>