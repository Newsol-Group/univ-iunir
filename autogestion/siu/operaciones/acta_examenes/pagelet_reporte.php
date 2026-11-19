<?php
namespace siu\operaciones\acta_examenes;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\u;
use siu\modelo\datos\catalogo;

class pagelet_reporte extends \siu\operaciones\_comun\operaciones\reporte\pagelet_reporte {
	protected $datos_consulta;	
	private $filtros = array('anio_academico' => '', 'turno' => '','nombre' => '');
	
	public function get_nombre(){
        return 'reporte';
    }

	function configurar(){
		$this->set_rpp(15);        
        $this->set_columnas_reporte($this->get_columnas());
        
		// Configuracion pdf
		$this->set_pdf_encabezado(kernel::traductor()->trans('header.menu.acta_examenes'), "", 112);
		$this->set_pdf_fuente("Courier.afm");
		$this->set_pdf_fuente_texto(10);
		$this->set_pdf_fuente_titulo(13);
		$this->set_pdf_fuente_subtitulo(11);
		$this->set_pdf_hoja("a4");
                
        $this->data['url_form_filtro'] = kernel::vinculador()->crear('acta_examenes', 'filtrar');
	}
	
	public function get_columnas(){
		$titulos = array();
		//Materia", "Sede", "Mesa", "Fecha", "Acta", "Tipo", "Estado", "Rol docente
		
        $titulos['MATERIA'] = "Materia";
        $titulos['SEDE'] = "Sede";
		$titulos['TURNO_EXAMEN'] = "Turno examen";
		$titulos['FECHA_CIERRE'] = "Fecha de cierre";
        $titulos['ACTA'] = "Acta";
        $titulos['TIPO_ACTA'] = "Tipo de acta";
        $titulos['ESTADO'] = "Estado";
        $titulos['ROL_DOCENTE'] = "Rol docente";
		return $titulos;
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
            $linea = array();
            if ($todo or ($i <= $reg_por_pagina && $id >= $minimo && $id < $maximo)){
                $linea['MATERIA'] = $dato['MATERIA'];
                $linea['SEDE'] = $dato['SEDE'];
                $linea['TURNO_EXAMEN'] = $dato['TURNO_EXAMEN'];
                $linea['FECHA_CIERRE'] = $dato['FECHA_CIERRE'];
                $acta = $dato['ACTA'];
                $tipo_acta = $dato['TIPO_ACTA'];                
                $linea['ACTA'] = $acta;
                $linea['TIPO_ACTA'] = $dato['TIPO_ACTA'];
                $linea['URL_LINEA'] = kernel::vinculador()->crear('acta_examenes', 'info_acta', array('acta'=>$acta, 'tipo_acta'=>$tipo_acta));
				$linea['ESTADO'] = $dato['ESTADO'];
                $linea['ROL_DOCENTE'] = $dato['ROL_DOCENTE'];
                $nuevo[$i] = $linea;
                $i++;
                unset($acta);
            }
        }
        return $nuevo;
	}
	
	protected function get_data(){
		if (!isset($this->datos_consulta)) {
			$parametros = $this->get_filtros();
			$parametros['nro_inscripcion'] = kernel::persona()->get_nro_inscripcion();
			$this->datos_consulta = catalogo::consultar('docente', 'acta_examenes', $parametros);
		}
		return $this->datos_consulta;
	}
	
	public function get_turnos_examen(){
		$parametros['nro_inscripcion'] = kernel::persona()->get_nro_inscripcion();
		$parametros["anio_academico"] = "";
        $parametros["turno"] = "";
        $parametros["nombre"] = "";
		$datos = catalogo::consultar('docente', 'acta_examenes', $parametros);
		
		$turnos = array();
		foreach($datos as $acta){
			if (!array_key_exists($acta['TURNO_EXAMEN'], $turnos)){
				$turnos[$acta['TURNO_EXAMEN']] = $acta['TURNO_EXAMEN'];				
			}
		}
		return $turnos;
	}

	/**
	 * Retorna la cantidad de datos
	 * @return entero
	 */
	protected function get_total_registros(){
		 return \guarani_arreglos::count($this->get_data_reporte(0, true));
	}
	
	public function set_filtros($filtros){
		$this->filtros = array_merge($this->filtros, $filtros);
	}
	
	public function get_filtros(){
		return $this->filtros;
	}
	
	public function hay_filtros(){
		if (!empty($this->filtros['anio_academico']) or !empty($this->filtros['turno']) or !empty($this->filtros['nombre'])){
			return $this->filtros;
		} 
		return false;
	}
	
	public function get_anio_acad_def(){
		$anio = array();
		$anio['inicial'] = date("Y") - 25;
		$anio['final'] = date("Y");
		return $anio;
	}
}
?>