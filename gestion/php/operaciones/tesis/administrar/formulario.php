<?php
/**
 *
 *
 * @package 	Tesis
 * @subpackage 
 */
class formulario extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo '<table cellspacing="1" cellpadding="1" border="0" width="100%">';
			echo'<tbody>';
				echo '<tr>';
					echo '<td colspan=3>';
						$this->generar_html_ef('titulo');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=3>';
						$this->generar_html_ef('descripcion');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=3>';
						$this->generar_html_ef('actividad');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=1>';
						$this->generar_html_ef('expediente');
					echo '</td>';
					echo '<td colspan=2>';
						$this->generar_html_ef('resolucion_tesis');
					echo '</td>';
					echo '<td>';
						$this->generar_html_ef('fecha_defensa');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
						$this->generar_html_ef('fecha_aprobacion');
					echo '</td>';
					echo '<td>';
						$this->generar_html_ef('fecha_vencimiento');
					echo '</td>';
					echo '<td>';
						$this->generar_html_ef('prorrogada');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=1>';
						$this->generar_html_ef('resolucion_tutores');
					echo '</td>';
					echo '<td colspan=2>';
						$this->generar_html_ef('resolucion_evaluadores');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
		
		echo '<hr>';
		
		echo '<table cellspacing="1" cellpadding="1" border="0" width="100%">';
			echo'<tbody>';
				echo '<tr>';
					echo '<td>';
						$this->generar_html_ef('circuito');
					echo '</td>';
					echo '<td>';
						$this->generar_html_ef('estado_inicial_nombre');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
						$this->generar_html_ef('accion');
					echo '</td>';
					echo '<td>';
						$this->generar_html_ef('estado_final_nombre');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=2>';
						$this->generar_html_ef('formularios');
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan=2>';
						$this->generar_html_ef('observaciones');
					echo '</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$hoy_js = guarani_fecha::get_hoy_js();
		$formateo = new toba_formateo('html');
		$hoy_formato_visual = $formateo->formato_fecha(guarani_fecha::get_hoy());
		$observaciones	= $this->controlador()->get_observaciones_estado_actual();
        $estado_tesis	= $this->controlador()->get_estado_incial();
		$tesis_aprobada = tesis_nucleo::estado_tesis_aprobada;
		
		$mje_fecha_aprobacion = guarani_mensajes::get('800_control_fechas', array('aprobación', 'menor', 'alta'));
		$mje_fecha_aprobacion_vs_vencimiento = guarani_mensajes::get('800_control_fechas', array('aprobación', 'mayor', 'vencimiento'));
        
        $mje_fecha_vencimiento = guarani_mensajes::get('800_control_fechas', array('vencimiento', 'menor', 'actual'));
		$mje_fecha_defensa = guarani_mensajes::get('800_control_fechas', array('defensa', 'menor', 'actual'));
		$mje_fecha_defensa_vs_aprobacion = guarani_mensajes::get('800_control_fechas', array('defensa', 'mayor', 'aprobación'));
        $mje_fecha_defensa_vs_vencimiento = guarani_mensajes::get('800_control_fechas', array('defensa', 'mayor', 'vencimiento'));
        
		echo "
		
		var estado_tesis	= '';
		var fecha_alta		= '';

		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__accion__procesar = function(es_inicial)
		{
			//Desactivo efs
			this.ef('fecha_aprobacion').desactivar();
			
			if (!this.ef('accion').tiene_estado()) {
				this.ef('observaciones').desactivar();
			}
			//Seteo el estado de los forms de acuerdo a la acción
			if (!es_inicial) {
				if (this.ef('circuito').tiene_estado()) {
					if (this.ef('accion').tiene_estado()) {
						
						//Estado final de la acción y los formularios						
						var params = [];
						params['circuito'] = this.ef('circuito').get_estado();
						params['accion'] = this.ef('accion').get_estado();
						this.controlador.ajax('datos_accion_tesis', params, this, this.respuesta_set_datos_accion_tesis);												
						//Activo campo observaciones y fuerzo el foco visual al elemento
						this.ef('observaciones').activar();
						this.ef('observaciones').resetear_estado();
						this.ef('observaciones').seleccionar();
					} else {
						
						//Reseteo el estado los formularios
						this.ef('estado_final_nombre').resetear_estado();
						this.ef('formularios').resetear_estado();
					}
				}
				
				if (!this.ef('accion').tiene_estado()) {
					this.ef('observaciones').set_estado('$observaciones');
				}
			}
		}
		
		{$this->objeto_js}.respuesta_set_datos_accion_tesis = function(respuesta) 
		{
			if (typeof respuesta == 'object') {
				
				//Seteo estado final y formularios a imprimir
				this.ef('estado_final_nombre').set_estado(respuesta['nombre']);
				this.ef('formularios').set_estado(respuesta['formularios']);
				if (respuesta['habilitar_fecha_aprob']) {
					estado_tesis = respuesta['estado_final'];
					//Activo fecha aprobación y seteo por defecto la actual
					fecha = '$hoy_formato_visual';
					fecha_alta	= respuesta['fecha_alta'];
					this.ef('fecha_aprobacion').activar();
					this.ef('fecha_aprobacion').set_estado(fecha);
				}
			}
		}
		
		{$this->objeto_js}.evt__circuito__procesar = function(es_inicial)
		{
			if (!this.ef('circuito').tiene_estado()) {
				
				//Si al crear una tesis se resetea el circuito, reseteo las observaciones y el estado final
				this.ef('observaciones').set_estado('$observaciones');
				this.ef('observaciones').desactivar();
				this.ef('estado_final_nombre').resetear_estado();
			}
		}
		
		//---- Validacion de EFs -----------------------------------
		
		{$this->objeto_js}.evt__fecha_aprobacion__validar = function()
		{
			var hoy = new $hoy_js;
			//FECHA ALTA DE TESIS
			var fa = new Date(fecha_alta);
			if (this.ef('fecha_aprobacion').activo()) {
				
				//La fecha de aprobacion no puede ser menor a la fecha del alta de la tesis
				if (this.ef('fecha_aprobacion').tiene_estado() && (this.ef('fecha_aprobacion').fecha() < fa)) {
					var fecha = fa.toLocaleDateString();
					var fecha_aux_msj = '$mje_fecha_aprobacion';
					var fecha_alta_msj = fecha_aux_msj.concat(': ', fecha);
                    this.ef('fecha_aprobacion').set_error(fecha_alta_msj);
					return false;
				}				
			}
			if(estado_tesis != $tesis_aprobada){
				if(this.ef('fecha_vencimiento').tiene_estado()){
					if (this.ef('fecha_aprobacion').tiene_estado() && (this.ef('fecha_aprobacion').fecha() > this.ef('fecha_vencimiento').fecha())) {
						this.ef('fecha_aprobacion').set_error('$mje_fecha_aprobacion_vs_vencimiento');
						return false;
					}
				}				
			}
            
			return true;
		}
		
		{$this->objeto_js}.evt__fecha_vencimiento__validar = function()
		{
			var hoy = new $hoy_js;
			if (this.ef('fecha_vencimiento').tiene_estado() && this.ef('fecha_vencimiento').fecha() < hoy) {
				this.ef('fecha_vencimiento').set_error('$mje_fecha_vencimiento');
				return false;
			}
			return true;
		}
		{$this->objeto_js}.evt__fecha_defensa__validar = function()
		{
			if(estado_tesis != $tesis_aprobada){
                var hoy = new $hoy_js;
                if (this.ef('fecha_defensa').tiene_estado() && this.ef('fecha_defensa').fecha() < hoy) {
                    this.ef('fecha_defensa').set_error('$mje_fecha_defensa');
                }
				if(this.ef('fecha_aprobacion').tiene_estado()){
					if (this.ef('fecha_defensa').tiene_estado() && (this.ef('fecha_defensa').fecha() > this.ef('fecha_aprobacion').fecha())) {
						this.ef('fecha_defensa').set_error('$mje_fecha_defensa_vs_aprobacion');
						return false;
					}
				}
            }
            
            if(this.ef('fecha_vencimiento').tiene_estado()){
                if (this.ef('fecha_defensa').tiene_estado() && (this.ef('fecha_defensa').fecha() > this.ef('fecha_vencimiento').fecha())) {
                    this.ef('fecha_defensa').set_error('$mje_fecha_defensa_vs_vencimiento');
                    return false;
                }
            }
			return true;
		}
		";
	}

}
?>