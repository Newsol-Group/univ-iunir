<?php
namespace siu\operaciones\agenda_cursadas;

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
		$this->agregar_corte("MATERIA_NOMBRE_COMPLETO", "{{valor}}");
		
		// Configuracion pdf
		$this->set_pdf_encabezado("Lorem ipsum dolor", "Lorem ipsum dolor sit amet", 112);
		$this->set_pdf_fuente("Courier.afm");
		$this->set_pdf_fuente_texto(10);
		$this->set_pdf_fuente_titulo(13);
		$this->set_pdf_fuente_subtitulo(11);
		$this->set_pdf_hoja("a4");
	}
	
	public function get_columnas(){
		$titulo = array();
		$titulo['MATERIA_NOMBRE_COMPLETO'] = 'Materia';
        $titulo['DIA_SEMANA_DESC'] = 'Día';
        $titulo['HORA_COMIENZO_CLASE'] = 'Inicio';
        $titulo['HORA_FIN_CLASE'] = 'Fin';
        $titulo['COMISION_NOMBRE'] = 'Comisión';
        $titulo['COMISION_CATEDRA'] = 'Cátedra';
        $titulo['TURNO'] = 'Turno';
        $titulo['AULA_NOMBRE'] = 'Aula';
        $titulo['VCEDIFICIODESC'] = 'Edificio';
        $titulo['TIPO_CLASE_DESC'] = 'Tipo';
        $titulo['SEDE_DISPLAY'] = 'Sede';
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
        
		$datos = $this->get_datos();
        
        $nuevo = array();
        foreach($datos as $id => $dato) {
            if ($todo or ($i <= $reg_por_pagina && $id >= $minimo && $id < $maximo)){                
                $nuevo[$i] = $dato;
                $i++;
            }
        }
        return $nuevo;
	}
	
	function get_datos()
	{
		if (!isset($this->datos_consulta)) {
	        $parametros['nro_inscripcion'] = kernel::persona()->get_nro_inscripcion();
			$this->datos_consulta = catalogo::consultar('docente', 'agenda_cursadas', $parametros);
		}		
		return $this->datos_consulta;
	}

	/**
	 * Retorna la cantidad de datos
	 * @return entero
	 */
	protected function get_total_registros(){
		 return \guarani_arreglos::count($this->get_data_reporte(1, true));
	}
}
?>