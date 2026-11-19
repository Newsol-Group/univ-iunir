<?php
/**
 *
 *
 * @package     Matrícula
 * @subpackage  Alumnos
 * 
 */
class form_vencimiento_masivo extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo "<table>
				<tr>
					<td width='30%'>";
						$this->generar_html_ef('vencimiento');
		echo "		</td>
					<td width='70%'>";
						$this->generar_html_ef('fecha_vigencia_nuevo');
		echo "		</td>
				</tr>
				<tr>
					<td colspan='2'>";
						$this->generar_html_ef('documento');
		echo "		</td>
				</tr>
				<tr>
					<td colspan='2'>";
						$this->generar_html_ef('observaciones');
		echo "		</td>
				</tr>
			  </table>";
	}

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		parent::extender_objeto_js();
		$venc_fecha = vencimiento_regularidad::fecha;
		$id_js_controlador = $this->controlador()->get_id_objeto_js();
		$cantidad = $this->controlador()->get_cantidad_alumnos_regularidades();
		$fecha = toba::db()->quote(guarani_fecha::get_hoy(true));
		echo "
		//---- Procesamiento de EFs --------------------------------
		var fecha = $fecha;

		{$this->objeto_js}.evt__vencimiento__procesar = function(es_inicial)
		{
			var params = [];
			var vencimiento = this.ef('vencimiento').get_estado();
			if (vencimiento == '$venc_fecha') {
				this.ef('fecha_vigencia_nuevo').mostrar(true, false);
				this.ef('fecha_vigencia_nuevo').set_estado($fecha);
				params['fecha_vigencia_nuevo'] = this.ef('fecha_vigencia_nuevo').get_estado();
				params['vencimiento'] = this.ef('vencimiento').get_estado();
			} else {
				this.ef('fecha_vigencia_nuevo').ocultar(true);
				params['fecha_vigencia_nuevo'] = null;
				params['vencimiento'] = this.ef('vencimiento').get_estado();
			}
		}

		{$this->objeto_js}.evt__fecha_vigencia_nuevo__procesar = function(es_inicial)
		{
			var fecha_vigencia_nuevo = this.ef('fecha_vigencia_nuevo').get_estado();
			var params = [];
			if (fecha_vigencia_nuevo == '') {
				this.ef('fecha_vigencia_nuevo').set_estado($fecha);							
			}
			params['fecha_vigencia_nuevo'] = this.ef('fecha_vigencia_nuevo').get_estado();
			params['vencimiento'] = this.ef('vencimiento').get_estado();
		}

		//-----------------------------------------------------------------------------------
		//---- JAVASCRIPT CONTROLADOR -------------------------------------------------------------------
		//-----------------------------------------------------------------------------------

		{$id_js_controlador}.evt__procesar = function() 
		{
			var cant = $cantidad;
			if (cant){
				var resp = confirm('Se procesará ' + cant + ' cambios en las regularidades, ¿desea continuar?');
			} else {
				var resp = confirm('No existen regularidades en las fechas indicadas para ser modificadas ¿desea continuar?');
			}
			if (!resp){
				return false;
			}
		}
		";
	}
}