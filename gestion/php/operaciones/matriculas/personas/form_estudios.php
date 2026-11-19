<?php
/**
 *
 *
 * @package 	Matriculas
 * @subpackage 	Personas
 * 
 */
class form_estudios extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo "	<table>
					<tbody>
						<tr>
							<td colspan='2'>";
								$this->generar_html_ef('nivel_estudio');
		echo "				</td>
						</tr>
						<tr>
							<td>";
								$this->generar_html_ef('titulo');
		echo "				</td>
							<td>";
								$this->generar_html_ef('titulo_otro', '70px');
		echo "				</td>
						</tr>
						<tr>
							<td>";
								$this->generar_html_ef('institucion');
		echo "				</td>
							<td>";
								$this->generar_html_ef('institucion_otra', '70px');
		echo "				</td>
						</tr>
						<tr>
							<td>";
								$this->generar_html_ef('institucion_otra_origen', '130px');
		echo "				</td>
							<td>";
								$this->generar_html_ef('institucion_otra_pais', '130px');
		echo "				</td>";
		echo "			</tr>
						<tr>
							<td>";
								$this->generar_html_ef('titulo_ext_revalidado');
		echo "				</td>
							<td>";
								$this->generar_html_ef('titulo_ext_resol_ministerial', '170px');
		echo "				</td>
						</tr>
						<tr>
							<td colspan=2>";
								$this->generar_html_ef('estado');
		echo "				</td>
						</tr>
						<tr>
							<td>";
								$this->generar_html_ef('razon_abandono');
		echo "				</td>
							<td>";
								$this->generar_html_ef('razon_abandono_otros', '70px');
		echo "				</td>
						</tr>
						<tr>
							<td>";
								$this->generar_html_ef('fecha_ingreso');
		echo "				</td>
							<td>";
								$this->generar_html_ef('fecha_egreso', '70px');
		echo "				</td>
						</tr>
						<tr>
							<td colspan='2'>";
								$this->generar_html_ef('orientacion_recibida', '130px');
		echo "				</td>
						</tr>
					</tbody>
				</table>
				
				<div id='colegio'>
					<fieldset>
						<legend>
							<strong>Colegio Secundario</strong>
						</legend>
						<table>
							<tbody>
								<tr>
									<td>";
										$this->generar_html_ef('colegio', '70px');
		echo "						</td>
									<td>";
										$this->generar_html_ef('colegio_otro', '70px');
		echo "						</td>
								</tr>
								<tr>
									<td>";
										$this->generar_html_ef('colegio_otro_origen', '115px');
		echo "						</td>
									<td>";
										$this->generar_html_ef('colegio_otro_pais', '115px');
		echo "						</td> 
								</tr>
								<tr>
									<td>";
										$this->generar_html_ef('anio_egreso', '70px');
		echo "						</td>
									<td>";
										$this->generar_html_ef('adeuda_materias_secundario');
		echo "						</td>
								</tr>
							</tbody>
						</table>
					</fieldset>
				</div>
				
				<div id='tesis'>
					<fieldset>
						<legend>
							<strong>Tesis</strong>
						</legend>";
						$this->generar_html_ef('titulo_tesis');
						$this->generar_html_ef('director_tesis');
		echo "		</fieldset>
				</div>
			";
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$current_date = guarani_fecha::get_hoy_js();
		
		$nivel_sin_estudio = persona::nivel_sin_estudio;
		$nivel_secundario = persona::nivel_secundario;
		
		$nivel_superior = persona::nivel_superior;
		$nivel_universitario = persona::nivel_universitario;
		$nivel_posgrado = persona::nivel_posgrado;
		
		$tit_convalidado = persona::tit_convalidado;
		$tit_revalidado = persona::tit_revalidado;
		$tit_no_convalidado = persona::tit_no_conv_rev;
		
		$orig_extranjero = persona::orig_extranjero;
		
		$estado_abandono = persona::estudio_estado_abandono;
		$estado_finalizo = persona::estudio_estado_termino;
		$razon_abandono_otras = persona::estudio_razon_abandono_otras;
		
		$mensaje_fecha_ingreso = guarani_mensajes::get('800_control_fechas', array('de ingreso', 'mayor', 'actual'));
		$mensaje_fecha_egreso = guarani_mensajes::get('800_control_fechas', array('de egreso', 'menor', 'de ingreso'));
		$mensaje_tit_convalidado_oblig = guarani_mensajes::get('800_dato_dep_obligatorio', array('Título Convalidado', 'Origen de la Institución/Colegio'));
		$mensaje_resol_ministerial_oblig = guarani_mensajes::get('800_dato_dep_obligatorio', array('Nro Resolución Convalidación', 'Título Convalidado'));;
		$mensaje_colegio_oblig = guarani_mensajes::get('800_dato_dep_obligatorio', array('Colegio/Otro', 'Nivel de Estudios'));
		$mensaje_institucion_oblig = guarani_mensajes::get('800_dato_dep_obligatorio', array('Institución/Otra', 'Nivel de Estudios'));
		$mensaje_titulo_oblig = guarani_mensajes::get('800_dato_dep_obligatorio', array('Título/Otro', 'Nivel de Estudios'));
		
		echo "
		
		//---- Procesamiento de EFs ------------------------------
		
		var origen_institucion = '';
		var origen_colegio = '';
		
		{$this->objeto_js}.evt__institucion__procesar = function(es_inicial)
		{
			if (this.ef('institucion').tiene_estado()) {
				//Si institución tiene un valor, se oculta y se resetea el valor para otra institución.
				this.ef('institucion_otra').resetear_estado();
				this.ef('institucion_otra').desactivar();
				this.ef('institucion_otra_origen').resetear_estado();
				this.ef('institucion_otra_origen').desactivar();
				this.ef('institucion_otra_pais').ocultar(true);
				// Consulta ajax para verificar la nacionalidad de la institucion seleccionada.
				var params = [];
				params['institucion'] = this.ef('institucion').get_estado();
				this.controlador.ajax('get_origen_institucion', params, this, this.respuesta_set_origen_institucion);
			} else {
				if (this.ef('nivel_estudio').tiene_estado() && this.ef('nivel_estudio').get_estado() != $nivel_sin_estudio
					&& this.ef('nivel_estudio').get_estado() != $nivel_secundario) {
					this.ef('institucion_otra').activar();
					this.ef('institucion_otra_origen').activar();
					nivel = this.ef('nivel_estudio').get_estado();
					if (!this.ef('institucion_otra').tiene_estado()) {
						this.ef('titulo_ext_revalidado').ocultar(true);
						this.ef('titulo_ext_resol_ministerial').ocultar(true);
					}
				}
			}
		}
		
		{$this->objeto_js}.evt__institucion_otra_origen__procesar = function(es_inicial)
		{
			if (this.ef('nivel_estudio').tiene_estado() && 
				this.ef('nivel_estudio').get_estado() != $nivel_sin_estudio && this.ef('nivel_estudio').get_estado() != $nivel_secundario) {
				if (this.ef('institucion_otra_origen').chequeado()) {
					this.ef('institucion_otra_pais').mostrar();
					// Si la institucion es extranjera se activan los campos de convalidación del título
					this.ef('titulo_ext_revalidado').mostrar();
					this.ef('titulo_ext_resol_ministerial').mostrar();
				} else if (!this.ef('institucion').tiene_estado()) {
					this.ef('institucion_otra_pais').ocultar(true);
					this.ef('titulo_ext_revalidado').ocultar(true);
					this.ef('titulo_ext_resol_ministerial').ocultar(true);
				}
			}
		}
		
		{$this->objeto_js}.evt__titulo__procesar = function(es_inicial)
		{
			if (this.ef('titulo').tiene_estado()) {
				//Si título tiene un valor, se oculta y se resetea el valor para otro título.
				this.ef('titulo_otro').resetear_estado();
				this.ef('titulo_otro').desactivar();
			} else {
				if (this.ef('nivel_estudio').tiene_estado() && this.ef('nivel_estudio').get_estado() != $nivel_sin_estudio) {
					this.ef('titulo_otro').activar();
				}
			}
		}
		
		{$this->objeto_js}.evt__colegio__procesar = function(es_inicial)
		{
			if (this.ef('colegio').tiene_estado()) {
				//Si colegio tiene un valor, se oculta y se resetea el valor para otro colegio.
				this.ef('colegio_otro').resetear_estado();
				this.ef('colegio_otro').desactivar();
				this.ef('colegio_otro_origen').resetear_estado();
				this.ef('colegio_otro_origen').desactivar();
				this.ef('colegio_otro_pais').ocultar(true);
				// Consulta ajax para verificar la nacionalidad del colegio seleccionado.
				var params = [];
				params['colegio'] = this.ef('colegio').get_estado();
				this.controlador.ajax('get_origen_colegio', params, this, this.respuesta_set_origen_colegio);
			} else {
				if (this.ef('nivel_estudio').tiene_estado() && this.ef('nivel_estudio').get_estado() == $nivel_secundario) {
					this.ef('colegio_otro').activar();
					this.ef('colegio_otro_origen').activar();
					if (!this.ef('colegio_otro').tiene_estado()) {
						this.ef('titulo_ext_revalidado').ocultar(true);
						this.ef('titulo_ext_resol_ministerial').ocultar(true);
					}
				}
			}
		}
		
		{$this->objeto_js}.evt__colegio_otro_origen__procesar = function(es_inicial)
		{
			if (this.ef('nivel_estudio').tiene_estado() && this.ef('nivel_estudio').get_estado() == $nivel_secundario) {
				if (this.ef('colegio_otro_origen').chequeado()) {
					this.ef('colegio_otro_pais').mostrar();
					// Si el colegio es extranjero se activan los campos de convalidación del título
					this.ef('titulo_ext_revalidado').mostrar();
					this.ef('titulo_ext_resol_ministerial').mostrar();
				} else if (!this.ef('colegio').tiene_estado()) {
					this.ef('colegio_otro_pais').ocultar(true);
					this.ef('titulo_ext_revalidado').ocultar(true);
					this.ef('titulo_ext_resol_ministerial').ocultar(true);
				}
			}
		}
		
		{$this->objeto_js}.evt__titulo_ext_revalidado__procesar = function(es_inicial)
		{
			if (this.ef('titulo_ext_revalidado').tiene_estado()
				&& this.ef('titulo_ext_revalidado').get_estado() != '$tit_no_convalidado') {
				this.ef('titulo_ext_resol_ministerial').activar();
			} else {
				this.ef('titulo_ext_resol_ministerial').resetear_estado();
				this.ef('titulo_ext_resol_ministerial').desactivar();
			}
		}
		
		{$this->objeto_js}.evt__nivel_estudio__procesar = function(es_inicial)
		{
			if (this.ef('nivel_estudio').tiene_estado()) {
				var nivel = this.ef('nivel_estudio').get_estado();
				if (nivel == $nivel_sin_estudio) {
					//Desactivo los efs
					this.ef('titulo').desactivar();
					this.ef('titulo_otro').desactivar();
					this.ef('institucion').desactivar();
					this.ef('institucion_otra').desactivar();
					this.ef('institucion_otra_origen').desactivar();
					this.ef('institucion_otra_pais').ocultar(true);
					this.ef('fecha_ingreso').desactivar();
					this.ef('fecha_egreso').desactivar();
					this.ef('orientacion_recibida').desactivar();
					this.ef('estado').desactivar();
					this.ef('titulo_tesis').desactivar();
					this.ef('director_tesis').desactivar();
					this.ef('colegio').desactivar();
					this.ef('colegio_otro').desactivar();
					this.ef('colegio_otro_origen').desactivar();
					this.ef('colegio_otro_pais').ocultar(true);
					this.ef('anio_egreso').desactivar();
					this.ef('adeuda_materias_secundario').desactivar();
		
					//Reseteo los efs
					this.ef('titulo')._get_combo().setComboText('');
					this.ef('institucion')._get_combo().setComboText('');
					this.ef('titulo_otro').resetear_estado();
					this.ef('institucion_otra').resetear_estado();
					this.ef('institucion_otra_origen').resetear_estado();
					this.ef('fecha_ingreso').resetear_estado();
					this.ef('fecha_egreso').resetear_estado();
					this.ef('orientacion_recibida').resetear_estado();
					this.ef('estado').resetear_estado();
					this.ef('titulo_tesis').resetear_estado();
					this.ef('director_tesis').resetear_estado();
					this.ef('colegio').resetear_estado();
					this.ef('colegio_otro').resetear_estado();
					this.ef('colegio_otro_origen').resetear_estado();
					this.ef('anio_egreso').resetear_estado();
					this.ef('titulo_ext_revalidado').ocultar();
					this.ef('titulo_ext_revalidado').resetear_estado();
					this.ef('titulo_ext_resol_ministerial').ocultar();
					this.ef('titulo_ext_resol_ministerial').resetear_estado();
					this.ef('adeuda_materias_secundario').resetear_estado();
		
					$('#colegio').hide();
					$('#tesis').hide();
				} else if (nivel == $nivel_secundario) {
					this.ef('titulo').activar();
					this.ef('titulo_otro').activar();
					this.ef('fecha_ingreso').activar();
					this.ef('fecha_egreso').activar();
					this.ef('orientacion_recibida').activar();
					this.ef('estado').activar();
					
					this.ef('titulo_tesis').activar();
					this.ef('director_tesis').activar();
		
					this.ef('institucion').ocultar(true);
					this.ef('institucion_otra').ocultar(true);
					this.ef('institucion_otra_origen').ocultar(true);
					this.ef('institucion_otra_pais').ocultar(true);
					if (!es_inicial) { 
						this.ef('titulo_ext_revalidado').ocultar(true);
						this.ef('titulo_ext_resol_ministerial').ocultar(true);
					}
					
					this.ef('colegio').activar();
					this.ef('colegio_otro').activar();
					this.ef('colegio_otro_origen').activar();
					this.ef('anio_egreso').activar();
					
					this.ef('titulo_tesis').resetear_estado();
					this.ef('director_tesis').resetear_estado();
					this.ef('adeuda_materias_secundario').activar();
					$('#tesis').hide();
					
					$('#colegio').show();
				} else {
					//Activo los efs
					this.ef('titulo').activar();
					this.ef('titulo_otro').activar();
					this.ef('institucion').activar();
					this.ef('institucion_otra').activar();
					this.ef('institucion_otra_origen').activar();
					this.ef('fecha_ingreso').activar();
					this.ef('fecha_egreso').activar();
					this.ef('orientacion_recibida').activar();
					this.ef('estado').activar();
					
					this.ef('colegio').desactivar();
					this.ef('colegio_otro').desactivar();
					this.ef('colegio_otro_origen').desactivar();
					this.ef('colegio_otro_pais').ocultar(true);
					this.ef('anio_egreso').desactivar();
					this.ef('colegio').resetear_estado();
					this.ef('colegio_otro').resetear_estado();
					this.ef('colegio_otro_origen').resetear_estado();
					this.ef('adeuda_materias_secundario').resetear_estado();
					this.ef('anio_egreso').resetear_estado();
		
					this.ef('institucion').mostrar();
					this.ef('institucion_otra').mostrar();
					this.ef('institucion_otra_origen').mostrar();
		
					$('#colegio').hide();
					
					if ((nivel == $nivel_superior) || (nivel == $nivel_universitario) || (nivel == $nivel_posgrado)) {
						this.ef('titulo_tesis').activar();
						this.ef('director_tesis').activar();
						$('#tesis').show();
					} else {
						this.ef('titulo_tesis').resetear_estado();
						this.ef('director_tesis').resetear_estado();
						$('#tesis').hide();
					}
				}
			}
		}
		
		//---- Validacion de EFs -----------------------------------
		
		{$this->objeto_js}.evt__fecha_ingreso__validar = function()
		{
			var hoy = new $current_date;
			if (this.ef('fecha_ingreso').tiene_estado()) {
				if (this.ef('fecha_ingreso').fecha() > hoy) {
					this.ef('fecha_ingreso').set_error('$mensaje_fecha_ingreso');
					return false;
				}
			}
			return true;
		}
		
		//---- Validacion general ----------------------------------
		
		{$this->objeto_js}.evt__validar_datos = function()
		{
			if (this.ef('fecha_ingreso').tiene_estado() && this.ef('fecha_egreso').tiene_estado()) {
				if (this.ef('fecha_egreso').fecha() < this.ef('fecha_ingreso').fecha()) {
					notificacion.agregar('$mensaje_fecha_egreso');
					return false;
				}
			}
			// Deben completarse los campos institucion/institucion otra o colegio/colegio otro según el nivel.
			if (this.ef('nivel_estudio').tiene_estado() && this.ef('nivel_estudio').get_estado() != $nivel_sin_estudio) {
				if (!this.ef('titulo').tiene_estado() && !this.ef('titulo_otro').tiene_estado()) {
						notificacion.agregar('$mensaje_titulo_oblig');
						return false;
				}
				if (this.ef('nivel_estudio').get_estado() == '$nivel_secundario') {
					if (!this.ef('colegio').tiene_estado() && !this.ef('colegio_otro').tiene_estado()) {
						notificacion.agregar('$mensaje_colegio_oblig');
						return false;
					}
				} else {
					if (!this.ef('institucion').tiene_estado() && !this.ef('institucion_otra').tiene_estado()) {
						notificacion.agregar('$mensaje_institucion_oblig');
						return false;
					}
				}
			}
			/**
			 * Si se indicó que el colegio o la institución seleccionada es extranjera, debe indicarse la 
			 * convalidación del título
			*/
			if ((this.ef('colegio_otro_origen').chequeado() || (this.ef('colegio').tiene_estado() && origen_colegio == '$orig_extranjero'))
				&& !this.ef('titulo_ext_revalidado').tiene_estado()) {
				notificacion.agregar('$mensaje_tit_convalidado_oblig');
				return false;
			}
			if ((this.ef('institucion_otra_origen').chequeado() || (this.ef('institucion').tiene_estado() && origen_institucion == '$orig_extranjero'))
				&& !this.ef('titulo_ext_revalidado').tiene_estado()) {
				notificacion.agregar('$mensaje_tit_convalidado_oblig');
				return false;
			}
			// Si el título es de una institución/colegio extranjero y está convalidado, debe indicarse la resolución ministerial.
			if (this.ef('titulo_ext_revalidado').tiene_estado() 
				&& (this.ef('titulo_ext_revalidado').get_estado() == '$tit_convalidado' || this.ef('titulo_ext_revalidado').get_estado() == '$tit_revalidado') 
				&& !this.ef('titulo_ext_resol_ministerial').tiene_estado())	{
				notificacion.agregar('$mensaje_resol_ministerial_oblig');
				return false;
			}
			return true;
		}
		
		//---- Respuestas Ajax ------------------------------
		
		{$this->objeto_js}.respuesta_set_origen_institucion = function(respuesta) 
		{
			if (typeof respuesta == 'object') {
				origen_institucion = respuesta['origen'];	
				if (origen_institucion == '$orig_extranjero') {
					// Si la institucion es extranjera se activan los campos de convalidación del título
					this.ef('titulo_ext_revalidado').mostrar();
					this.ef('titulo_ext_resol_ministerial').mostrar();
				} else {
					this.ef('titulo_ext_revalidado').ocultar(true);
					this.ef('titulo_ext_resol_ministerial').ocultar(true);
				}
			}
		}
		
		{$this->objeto_js}.respuesta_set_origen_colegio = function(respuesta) 
		{
			if (typeof respuesta == 'object') {
				origen_colegio = respuesta['origen'];
				if (origen_colegio == '$orig_extranjero') {
					// Si el colegio es extranjero se activan los campos de convalidación del título
					this.ef('titulo_ext_revalidado').mostrar();
					this.ef('titulo_ext_resol_ministerial').mostrar();
				} else {
					this.ef('titulo_ext_revalidado').ocultar(true);
					this.ef('titulo_ext_resol_ministerial').ocultar(true);
				}
			}
		}
		
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__estado__procesar = function(es_inicial)
		{
			if (this.ef('estado').tiene_estado()) {
				var estado = this.ef('estado').get_estado();
				if (estado == '$estado_abandono') {
					this.ef('razon_abandono').mostrar();
					this.ef('fecha_egreso').ocultar(true);
				} else {
					if (estado == '$estado_finalizo') {
						this.ef('fecha_egreso').mostrar();
					} else {
						this.ef('fecha_egreso').ocultar(true);
					}
					this.ef('razon_abandono').ocultar(true);
					this.ef('razon_abandono_otros').ocultar(true);
				}
			} else {
				this.ef('razon_abandono').ocultar(true);
				this.ef('razon_abandono_otros').ocultar(true);
			}
		}
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__razon_abandono__procesar = function(es_inicial)
		{
			if (this.ef('razon_abandono').tiene_estado()) {
				var razon_abandono = this.ef('razon_abandono').get_estado();
				if (razon_abandono == '$razon_abandono_otras') {
					this.ef('razon_abandono_otros').mostrar();
				} else {
					this.ef('razon_abandono_otros').ocultar(true);
				}
			} else {
				this.ef('razon_abandono_otros').ocultar(true);
			}
		}
		";
	}

}
?>