<?php
class pant_previsualizacion_anular extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$datos = $this->controlador()->ev_info_acta()->get_datos(false);
		$this->controlador()->ev_info_acta()->generar_html();
		$this->dep('form_acta')->generar_html();

		echo "<hr /> 
                <div class='ci-simple-cont'> 
                        <table width='98%' align='center'> 
                                <tr class='ei-form-fila'> 
                                        <td align='left' width='10%'> 
                                                <strong> 
                                                        <span ei-form-etiq-oblig > 
                                                                Cantidad de Alumnos: 
                                                        </span> 
                                                </strong> 
                                        </td> 
                                        <td align='left'> 
                                                <span ei-form-etiq> 
                                                        {$datos['cant_alumnos']} 
                                                </span> 
                                        </td> 
                                </tr> 
                        </table> 
                </div>";

		$this->dep('cuadro_alumnos')->generar_html();
	}

}
?>