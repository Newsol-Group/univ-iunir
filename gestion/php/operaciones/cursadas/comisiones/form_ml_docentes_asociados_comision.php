<?php
/**
 *
 *
 * @package		Cursadas
 * @subpackage	Comisiones
 * 
 */
class form_ml_docentes_asociados_comision extends guarani_pers_ei_formulario_ml
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$msj_existencia_fechas = guarani_mensajes::get('800CUR_completar_fechas');
		$msj_consistencia_fechas = guarani_mensajes::get('800_control_fechas', array('Hasta', 'menor o igual', 'Desde para el docente '));

		$msj_confirmar_fechas = guarani_mensajes::get('800CUR_rango_fuera_rango_doc');
		$datos_comision = $this->controlador()->cn()->entidad()->get_datos_generales();
		$datos_periodo = toba::consulta_php('co_periodos_lectivos')->get_datos_periodo_lectivo($datos_comision['periodo_lectivo']);

		echo "
			{$this->objeto_js}.evt__responsabilidad__procesar = function(es_inicial, fila)
			{
				if (!es_inicial && this.ef('responsabilidad').ir_a_fila(fila).tiene_estado()) {
					var params = [];
					params['fila'] = fila;
					params['responsabilidad'] = this.ef('responsabilidad').ir_a_fila(fila).get_estado();
					this.controlador.ajax('datos_responsabilidad', params, this, this.respuesta_set_datos_responsabilidad);
				}
			}

			{$this->objeto_js}.respuesta_set_datos_responsabilidad = function(respuesta)
			{
				if (typeof respuesta == 'object') {
					if (respuesta['firma'] == '".comunes::si."') {
						this.ef('firma').ir_a_fila(respuesta['fila']).chequear(true);
					} else {
						this.ef('firma').ir_a_fila(respuesta['fila']).chequear(false);
					}
				}
			}

			//-------------------- Validación de efs ---------------------------------------
			
			{$this->objeto_js}.evt__validar_datos = function()
			{
				//Se verifica que se hayan seteado ambas o ninguna de las fechas de las asignaciones docentes a la comisión.
				var ok = true;
				var filas = this.filas();
				for (id_fila in filas) {
					if ((this.ef('fecha_desde').ir_a_fila(filas[id_fila]).tiene_estado() && !this.ef('fecha_hasta').ir_a_fila(filas[id_fila]).tiene_estado())
					   || (!this.ef('fecha_desde').ir_a_fila(filas[id_fila]).tiene_estado() && this.ef('fecha_hasta').ir_a_fila(filas[id_fila]).tiene_estado())) {
						var docente = this.ef('nombre_persona').ir_a_fila(filas[id_fila]).get_estado();
						notificacion.agregar('$msj_existencia_fechas'+docente);
						ok = false;
					} else {
						// Si se setearon ambas fechas se verifica que sean consistentes (fecha desde <= fecha hasta).
						if (this.ef('fecha_desde').ir_a_fila(filas[id_fila]).tiene_estado() && this.ef('fecha_hasta').ir_a_fila(filas[id_fila]).tiene_estado()) {
							fecha_desde = this.ef('fecha_desde').ir_a_fila(filas[id_fila]).fecha();
							fecha_hasta = this.ef('fecha_hasta').ir_a_fila(filas[id_fila]).fecha();
							fechas_desfazadas = fecha_desde > fecha_hasta;
							if (fechas_desfazadas) {
								var docente = this.ef('nombre_persona').ir_a_fila(filas[id_fila]).get_estado();
								notificacion.agregar('$msj_consistencia_fechas'+docente);
								ok = false;
							}

							// Si hubo cambios en en el formulario, se evalua que la asignacion de fechas de los docentes se 
							// encuentre dentro del período lectivo de la comisión, y en caso de no ser así se emite un msj
							// de advertencia al usuario para ver si desea continuar con los cambios.
							if (ok && this.hay_cambios()) {
								return confirmar_fechas_asignacion(fecha_desde, fecha_hasta);
							}
						}
					}
				}
				
				return ok;
			}
			
			function confirmar_fechas_asignacion(fecha_desde, fecha_hasta)
			{
				//Fechas a evaluar
				var fecha_inicio_periodo = new Date('{$datos_periodo['periodo_fecha_inicio']}T00:00:00');
				var fecha_fin_periodo = new Date('{$datos_periodo['periodo_fecha_fin']}T00:00:00');

				//Condiciones de las fechas
				//fecha_desde no puede ser menor al inicio del período ni mayor al fin del período
				fecha_desde_desfasada = (fecha_desde < fecha_inicio_periodo) || (fecha_desde > fecha_fin_periodo);
				//fecha_hasta no puede ser menor a la fecha desde ni mayor al fin del período
				fecha_hasta_desfasada = (fecha_desde > fecha_hasta) || (fecha_hasta > fecha_fin_periodo);

				//Se emite mensaje de confirmación
				if (fecha_desde_desfasada || fecha_hasta_desfasada) {
					if (!(confirm('$msj_confirmar_fechas'))) {
						return false;
					}
				}
				return true;
			}
			
			//-------------------- Eventos ------------------------------------------------
			
			mensaje_confirm = '';
			params = [];
			
			//En caso de que se elimine una asignación de docente y haya dedicaciones horarias cargadas para dicho docente, 
			//se deberá mostrar un mensaje de confirmación y en caso de decidir la eliminación del docente, se eliminará 
			//también la dedicación horaria.
			
			{$this->objeto_js}.evt__verificar_eliminacion = function(id_docente) {
				params = [id_docente];
				this.controlador.ajax('verificar_dedicacion_docente', params, this, this.respuesta_verificar_dedicacion_docente);
				return false;
			}

			{$this->objeto_js}.respuesta_verificar_dedicacion_docente = function(respuesta) 
			{
				if (typeof respuesta == 'object') {
					mensaje_confirm = respuesta['mensaje'];
					//Si el docente a eliminar tiene dedicaciones horarias asociadas, se pide confirmar la eliminación.
					this.confirmacion_eliminacion();
				} else {
					this.set_evento(new evento_ei('eliminar', false, false, params), true);
				}
			}
			
			{$this->objeto_js}.confirmacion_eliminacion = function()
			{
				if (confirmar()) {
					//La persona confirma que desea eliminar el docente seleccionado y sus dedicaciones horarias asociadas.
	          		this.set_evento(new evento_ei('eliminar', false, false, params), true);
	      		} else {
	      			return false;
	      		}
			}
			
			function confirmar(proyecto, operacion, url, es_popup) {
				if (! es_popup || typeof(es_popup) == 'undefined') {
					resp = confirm(mensaje_confirm);
					if (resp) {
						return true;
					} else {
						return false;
					}
				}
				return true;
			}

			toba.set_callback_menu(confirmar);
			
		";
	}

}
?>