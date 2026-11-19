<?php
class pant_edicion_anular extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$acta = $this->controlador()->controlador()->get_acta_seleccionada();
		$filtro = " sga_actas.id_acta = $acta";
		$datos = current(toba::consulta_php('co_actas')->get_listado_actas_examen($filtro));

		$this->controlador()->controlador()->ev_info()->generar_html();
		if ($this->existe_dependencia('form_acta')) {
			echo '<hr />';
			$this->dep('form_acta')->generar_html();
		}

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

		$this->dep('alumnos')->generar_html();
	}

}
?>