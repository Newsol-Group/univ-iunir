<?php
namespace siu\operaciones\regularidades;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\u;
use siu\modelo\datos\catalogo;

class pagelet_reporte extends \siu\operaciones\_comun\operaciones\reporte\pagelet_reporte {
	protected $datos_consulta;
	
	public function get_nombre(){
        return 'reporte';
    }

	function configurar(){
		$this->set_rpp(15);
		$this->set_columnas_reporte($this->get_columnas());
		//$this->agregar_corte('FECHA_VENCIMIENTO', '{{valor}}');
		
		// Setea metodos que se ejecutan al final de cada corte. Con el subconjunto de datos del corte
		//$this->agregar_funcion_pie_corte('nombre_funcion', array(array(0, 10), 'Titulo: ', 1));
		//$this->agregar_mensaje_pie_corte('nombre_funcion_pie');

		// Setea metodos que se ejecutan al final del reporte.
		//$this->agregar_funcion_pie_reporte('nombre_funcion_pie_reporte', array(array(0, 10), 'Titulo: ', 1));
		//$this->agregar_mensaje_pie_reporte('nombre_funcion_pie_reporte');

		// Configuracion pdf
		$this->set_pdf_encabezado(kernel::traductor()->trans('header.menu.regularidades'), "", 112);
		$this->set_pdf_fuente("Courier.afm");
		$this->set_pdf_fuente_texto(10);
		$this->set_pdf_fuente_titulo(13);
		$this->set_pdf_fuente_subtitulo(11);
		$this->set_pdf_hoja("a4");
	}
	
	public function get_columnas(){
		$titulo = array();
		$titulo['MATERIA'] = array("Materia", array('ordenable'=>true));
		$titulo['MATERIA_NOMBRE'] = array("Nombre", array('ordenable'=>true, 'width'=>300));
		$titulo['RESULTADO'] = "Resultado de la cursada";
		$titulo['FECHA_REGULARIDAD'] = "Fecha de regularidad";
		$titulo['FECHA_VENCIMIENTO'] = "Fecha fin de regularidad";
		return $titulo;
	}
	
	/**
	 * Recupera los datos
	 * @param entero $pagina
	 * Numero de página que se desea recuperar
	 * @param buleano $todo
	 * Recuperar todos los datos
	 * @return array 
	 */
	public function get_data_reporte($pagina = 0, $todo = false){		
		$i=0;
        $reg_por_pagina = $this->get_rpp();
        $pagina = $this->get_pagina();
        
        $minimo = ($pagina - 1) * $reg_por_pagina;
        $maximo = $minimo + $reg_por_pagina;
        
        $datos = $this->get_data();
		
        $nuevo = array();
        foreach($datos as $id => $dato) {
            if ($todo or ($i <= $reg_por_pagina && $id >= $minimo && $id < $maximo)){				
                $nuevo[$i] = $dato;
				$nuevo[$i]['URL_LINEA'] = kernel::vinculador()->crear('regularidades', 'info_regularidad', array('parametro'=>1));
                $i++;
            }
        }
        return $nuevo;
	}

	protected function get_data(){
		if (!isset($this->datos_consulta)) {
			$parametros['carrera'] = kernel::persona()->get_id_propuesta_activa();
			$parametros['legajo'] = kernel::persona()->get_legajo_activo();
			$datos = catalogo::consultar('alumno', 'regularidades', $parametros);
			$this->datos_consulta = $datos;
		}
		$this->datos_consulta = $this->ordenar($this->datos_consulta, $this->columna, $this->orden);
		return $this->datos_consulta;
	}
	
	protected function ordenar($datos, $columna, $orden){		
		if (isset($columna) && isset($orden) && array_key_exists($columna, $this->get_columnas())){			
			$count = \guarani_arreglos::count($datos);
			for($i=0; $i<=$count; $i++){
				for($j=$count-1; $j>$i; $j=$j-1){
					if (isset($datos[$j][$columna]) && isset($datos[$j-1][$columna])){
						if ($orden == "asc" && $datos[$j][$columna] < $datos[$j-1][$columna]){
							$tmp = $datos[$j];
							$datos[$j] = $datos[$j-1];
							$datos[$j-1] = $tmp;
							unset($tmp);
						} elseif($orden == "desc" && $datos[$j][$columna] > $datos[$j-1][$columna]){
							$tmp = $datos[$j];
							$datos[$j] = $datos[$j-1];
							$datos[$j-1] = $tmp;
							unset($tmp);
						}
					}
				}
			}
		}
		return $datos;
	}
	
	/**
	 * Retorna la cantidad de datos
	 * @return entero
	 */
	protected function get_total_registros(){
		 return \guarani_arreglos::count($this->get_data_reporte(0, true));
	}
	
	public function get_nombre_archivo(){
		return "regularidades";
	}
}
?>