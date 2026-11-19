<?php
class form_datos_generales extends guarani_pers_ei_formulario
{
	function generar_layout()
	{
		echo "	<table width='100%'>
					<tr>
						<td width='33%'>
							"; $this->generar_html_ef('nro_libro'); echo " 
						</td>
						<td width='34%'>
							"; $this->generar_html_ef('fecha_activacion', '120px'); echo " 
						</td>
						<td width='33%'>
							"; $this->generar_html_ef('fecha_fin_vigencia'); echo " 
						</td>
					</tr>
					<tr>
						<td colspan='3'>
							"; $this->generar_html_ef('nombre'); echo " 
						</td>
					</tr>
					<tr>
						<td colspan='3'>
							"; $this->generar_html_ef('descripcion'); echo " 
						</td>
					</tr>
				</table>
				";
		
		echo "	<table width='100%'>
					<tr>
						<td width='50%'>
							"; $this->generar_html_ef('tipo_libro'); echo " 
						</td>
						<td width='50%'>
							<table width='100%'>							
								<tr>
									<td>
										"; $this->generar_html_ef('anio_academico', '180px'); echo " 
									<td>
								</tr>
								<tr>
									<td>
										"; $this->generar_html_ef('es_libro_activo', '180px'); echo " 
									<td>
								</tr>
							</table>
						</td>						
					</tr>					
				</table>
				";
	}

}
?>