<?php
class form_excep_perlect extends guarani_pers_ei_formulario
{
	//--------------------------------------------------------------------------
	//---- JAVASCRIPT ----------------------------------------------------------
	//--------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$datos_comision = $this->controlador()->cn()->entidad()->get_datos_generales();
		$datos_periodo = toba::consulta_php('co_periodos_lectivos')->get_datos_periodo_lectivo($datos_comision['periodo_lectivo']);

		// Períodos de inscripción
		$periodos_insc = $this->controlador()->cn()->entidad()->get_datos_periodos_inscripcion();
		echo "var periodos_insc = [];";
		foreach ($periodos_insc as $periodo_insc) {
			// Se obtiene la fecha de fin el período de inscripción (o su excepción)
			if (!empty($periodo_insc['fecha_fin_excep'])) {
				$temp = explode(' ', $periodo_insc['fecha_fin_excep']);
				$fecha_fin = $temp[0];
			} else {
				$temp = explode(' ', $periodo_insc['fecha_fin']);
				$fecha_fin = $temp[0];
			}
            echo "periodos_insc.push($fecha_fin);";
		}

		// Asignaciones BH
		$asignaciones = $asignaciones = $this->controlador()->cn()->entidad()->get_datos_asignaciones();
        echo "var asignaciones = [];";
		foreach ($asignaciones as $asignacion) {
			echo "var asignacion = [];";
			echo "asignacion['fecha_desde'] = '{$asignacion['fecha_desde']}';";
			echo "asignacion['fecha_hasta'] = '{$asignacion['fecha_hasta']}';";
			echo "asignaciones.push(asignacion);";
		}

		echo "
		
		//---- Eventos ---------------------------------------------------------
		
		{$this->objeto_js}.evt__confirmar = function()
		{
			notificacion.limpiar();
			var errores = false;
			var i;
		
			// Fechas ingresadas manualmente
			var nueva_fecha_inicio = new Date(this.ef('fecha_inicio_dictado').fecha());            
            nueva_fecha_inicio = nueva_fecha_inicio.getTime();

			var nueva_fecha_fin = new Date(this.ef('fecha_fin_dictado').fecha());
            nueva_fecha_fin = nueva_fecha_fin.getTime();

			//------------------------------------------------------------------
			//---- Validaciones que NO permiten continuar
			//------------------------------------------------------------------
			
			if (nueva_fecha_inicio > nueva_fecha_fin) {
				// Que la fecha nuevo inicio dictado sea menor que la fecha de 
				// nuevo fin de dictado. 
				errores = true;
			} else {
				// Que la fecha de nuevo inicio dictado sea mayor o igual que la fecha 
				// de fin del período de inscripción (o su excepcion). 
				for (i in periodos_insc) {
					if (nueva_fecha_inicio < periodos_insc[i]) {
						errores = true;
					}
				}
			}

			//------------------------------------------------------------------
			//---- Las validaciones que permiten continuar se ejecutan cuando no 
			//---- se produjeron errores críticos de fechas
			//------------------------------------------------------------------
			if (!errores) {
				var mensaje = '';

				// Fechas límite del período lectivo
				var fecha_inicio_periodo = new Date('{$datos_periodo['periodo_fecha_inicio']}').getTime();
				var fecha_fin_periodo = new Date('{$datos_periodo['periodo_fecha_fin']}').getTime();

				// Se controla que las fechas nuevo inicio dictado y nuevo fin dictado 
				// estén dentro del rango de inicio y fin del periodo lectivo. 
				if ( (nueva_fecha_inicio < fecha_inicio_periodo) || (nueva_fecha_fin > fecha_fin_periodo) ) { 
					mensaje += 'Atención, el rango de dictado está fuera del período lectivo. \\n';
				}

				msj_asignaciones = '';
                var asignacion_desde;
                var asignacion_hasta;
				for (i in asignaciones) {                
                    asignacion_desde = new Date(asignaciones[i]['fecha_desde']).getTime();
                    asignacion_hasta = new Date(asignaciones[i]['fecha_hasta']).getTime();
					if ( (nueva_fecha_inicio < asignacion_desde ) || ( nueva_fecha_fin > asignacion_hasta) ) {
						msj_asignaciones = 'Hay asignaciones horarias fuera del rango del nuevo inicio y fin de dictado del período lectivo. \\n';
					}
				}

				mensaje += msj_asignaciones;

				if (mensaje != '') {
					mensaje += '¿Desea continuar?';
					return confirm(mensaje);
				}
			}
		}
		";
	}

}
?>