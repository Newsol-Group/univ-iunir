<?php
/**
 *
 *
 * @package     Matrícula
 * @subpackage  Alumnos
 * 
 */
class form_vencimiento extends guarani_pers_ei_formulario
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
		$venc_fecha = vencimiento_regularidad::fecha;
		echo "
		//---- Procesamiento de EFs --------------------------------
		
		{$this->objeto_js}.evt__vencimiento__procesar = function(es_inicial)
		{
			var vencimiento = this.ef('vencimiento').get_estado();
			if (vencimiento == '$venc_fecha') {
				this.ef('fecha_vigencia_nuevo').mostrar(true, false);
			} else {
				this.ef('fecha_vigencia_nuevo').ocultar(true);
			}
		}
		";
	}

}

?>