<?php
namespace siu\operaciones\acta_promociones;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\u;
use siu\modelo\datos\catalogo;

class pagelet_reporte extends \siu\operaciones\_comun\operaciones\reporte\pagelet_reporte {
	protected $datos_consulta;	
	private $filtros = array('anio_academico' => '', 'periodo_lectivo' => '','nombre' => '');
	
	public function get_nombre(){
        return 'reporte';
    }

	function configurar(){
		$this->set_rpp(15);        
		$this->set_columnas_reporte($this->get_columnas());
        
		// Configuracion pdf
		$this->set_pdf_encabezado(kernel::traductor()->trans('actas_de_promocion'), "", 112);
		$this->set_pdf_fuente("Courier.afm");
		$this->set_pdf_fuente_texto(10);
		$this->set_pdf_fuente_titulo(13);
		$this->set_pdf_fuente_subtitulo(11);
		$this->set_pdf_hoja("a4");
                
        $this->data['url_form_filtro'] = kernel::vinculador()->crear('acta_promociones', 'filtrar');
	}
	
	public function get_columnas(){
		$titulos = array();
		$titulos['ANIO_ACADEMICO'] = "Año";
        $titulos['PERIODO_LECTIVO'] = "Período Lectivo";
        $titulos['SEDE'] = "Sede";
        $titulos['ACTA'] = "Acta";
        $titulos['NOMBRE_MATERIA'] = "Materia";
        $titulos['ESTADO_ACTA'] = "Estado";
        $titulos['FECHA_CIERRE'] = "Fecha cierre";
		
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
        
        $parametros['anio_academico'] = "";
        $parametros['periodo_lectivo'] = "";
        $parametros['nombre'] = "";
        
        $operacion = kernel::ruteador()->get_id_operacion();
        $datos = array();
        
		if ($this->memoria()->tiene('filtros'.$operacion)){
			$parametros = $this->memoria()->get('filtros'.$operacion);            
		}
        
        $datos = $this->get_data();
        
        $nuevo = array();
        foreach($datos as $id => $dato) {
            $linea = array();
            if ($todo or ($i <= $reg_por_pagina && $id >= $minimo && $id < $maximo)){        
                $linea['ANIO_ACADEMICO'] = $dato['ANIO_ACADEMICO'];
                $linea['PERIODO_LECTIVO'] = $dato['PERIODO_LECTIVO'];
                $linea['SEDE'] = $dato['NOMBRE_SEDE'];
                $acta = $dato['ACTA'];
                $linea['ACTA'] = $acta;
                $linea['URL_LINEA'] = kernel::vinculador()->crear($operacion, 'info_acta', array('accion'=>$acta));
                $linea['NOMBRE_MATERIA'] = $dato['NOMBRE_MATERIA']." (".$dato['MATERIA'].")";
                $linea['ESTADO_ACTA'] = $dato['ESTADO_ACTA'];
                $linea['FECHA_CIERRE'] = $dato['FECHA_CIERRE'];                
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
			$this->datos_consulta = catalogo::consultar('docente', 'acta_promociones', $parametros);
		}
		return $this->datos_consulta;
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
		if (!empty($this->filtros['anio_academico']) or !empty($this->filtros['periodo_lectivo']) or !empty($this->filtros['nombre'])){
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