<?php
/**
 *
 *
 * @package Cursadas
 * @subpackage Comisiones
 */
class form_ml_comisiones_subcomisiones extends guarani_pers_ei_formulario_ml  
{

	function generar_layout_fila($clave_fila)
	{
		$datos = $this->controlador()->get_datos_comisiones_subcomisiones();
		$subcom = (isset($datos[$clave_fila]['subcomision'])) ? true : false;
            
		foreach ($this->_lista_ef_post as $ef) {
			$this->_elemento_formulario[$ef]->ir_a_fila($clave_fila);
			$id_form = $this->_elemento_formulario[$ef]->get_id_form();
			echo "<td class='";
			if ($subcom) { 
				echo "ei-ml-fila-subcom' id='cont_$id_form'>\n";
			} else {
				echo "{$this->estilo_celda_actual}' id='cont_$id_form'>\n";
			}
			$this->generar_input_ef($ef);
			echo "</td>\n";
		}
	}


	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
            // Armo el arreglo JS q contiene las comisiones => array(subcomisiones_asociadas) para tildar al seleccionar
            $datos = $this->controlador()->get_datos_comisiones_subcomisiones();
            echo " var datos = new Array();";
            $com = 0;
            $i = 0;
            $subcom = array();
            foreach ($datos as $dato) {
                if (isset($dato['subcomision'])) {
                    array_push($subcom, "'$i'");
                } else {
                    echo "
                            datos[$com] = new Array(" . implode(",",$subcom) . ");
                         ";
                    $subcom = array();
                    $com = $i;
                }
                $i++;   
            }
            // Agrego el ultimo
            echo "datos[$com] = new Array(" . implode(",",$subcom) . ");";
            
            echo "
            //---- Procesamiento de EFs --------------------------------

            {$this->objeto_js}.evt__inscripcion_habilitada__procesar = function(es_inicial, fila)
            {
                if (es_inicial) return;
                if (this.ef('inscripcion_habilitada').ir_a_fila(fila).chequeado()) {
                    for (id_fila in datos[fila]) {                        
                        f = parseInt(datos[fila][id_fila]);
                        this.ef('inscripcion_habilitada').ir_a_fila(f).activar();
                        this.ef('inscripcion_habilitada').ir_a_fila(f).chequear(true);
                    }
                } else {
                    for (id_fila in datos[fila]) {
                        f = parseInt(datos[fila][id_fila]);
                        this.ef('inscripcion_habilitada').ir_a_fila(f).resetear_estado();
                        this.ef('inscripcion_habilitada').ir_a_fila(f).desactivar();
                    }
                }
            }

            ";
	}       
    
}
?>