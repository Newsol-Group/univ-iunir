<?php
namespace siu\operaciones\agenda_examenes;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\u;
use siu\modelo\datos\catalogo;
use siu\guarani;

class pagelet_reporte extends \siu\operaciones\_comun\operaciones\reporte\pagelet_reporte {
	protected $datos_consulta;
	
	public function get_nombre(){
        return 'reporte';
    }

	function configurar(){
		$this->set_rpp(15);
        $this->set_columnas_reporte($this->get_columnas());
		$this->agregar_corte('ACTIVIDAD_NOMBRE_COMPLETO', '{{valor}}');
        
		// Setea metodos que se ejecutan al final de cada corte. Con el subconjunto de datos del corte
		//$this->agregar_funcion_pie_corte('nombre_funcion', array(array(0, 10), 'Titulo: ', 1));
		//$this->agregar_mensaje_pie_corte('nombre_funcion_pie');

		// Setea metodos que se ejecutan al final del reporte.
		//$this->agregar_funcion_pie_reporte('nombre_funcion_pie_reporte', array(array(0, 10), 'Titulo: ', 1));
		//$this->agregar_mensaje_pie_reporte('nombre_funcion_pie_reporte');
		 
		// Configuracion pdf
		$institucion = guarani::get_nombre_universidad()."\n";
		$this->set_pdf_encabezado($institucion, '<b><u>Agenda de Exámenes</u></b>',110);
		$this->set_pdf_fuente("Courier.afm");
		$this->set_pdf_fuente_texto(10);
		$this->set_pdf_fuente_titulo(11);
		$this->set_pdf_fuente_subtitulo(11);
		$this->set_pdf_hoja("a4", 'landscape');
		
	}
	
	public function get_columnas(){
		$titulo = array();
		$titulo['ACTIVIDAD_NOMBRE_COMPLETO'] = 'Materia';
        $titulo['FECHA'] = 'Día';
        $titulo['HORARIO'] = 'Horario';
        $titulo['DOCENTE_ROL'] = 'Rol docente';
        $titulo['ANIO_ACADEMICO'] = 'Año';
        $titulo['TURNO_EXAMEN_NOMBRE'] = 'Turno';
        $titulo['MESA_EXAMEN_NOMBRE'] = 'Mesa';
        $titulo['LLAMADO_NOMBRE'] = 'Llamado';
        $titulo['AULA'] = 'Aula';
        //$titulo['EDIFICIO_NOMBRE'] = 'Edificio';
        $titulo['UBICACION_NOMBRE'] = 'Sede';
		
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
                $i++;
            }
        }
        return $nuevo;
	}
	
	protected function get_data(){
		if (!isset($this->datos_consulta)) {
			$parametros['docente'] = kernel::persona()->get_id_docente();
			$this->datos_consulta = catalogo::consultar('docente', 'agenda_examenes', $parametros);
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