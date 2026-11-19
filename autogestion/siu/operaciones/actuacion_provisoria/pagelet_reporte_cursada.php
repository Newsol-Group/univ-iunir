<?php

namespace siu\operaciones\actuacion_provisoria;

use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;
use siu\operaciones\_comun\operaciones\reporte\pagelet_reporte;

class pagelet_reporte_cursada extends pagelet_reporte {
	
	protected $datos_consulta;
	
	public function get_nombre(){
        return 'reporte';
    }

	function configurar(){
		$this->set_rpp(5);
		//$this->set_columnas_reporte($this->get_columnas());
		// Configuracion pdf
        $this->set_tiene_encabezado(true);
        $this->set_tiene_encabezado_long(true);
		$this->set_pdf_encabezado(kernel::traductor()->trans('header.menu.actuacion_provisoria_cursada'), "", 112);
		$this->set_pdf_fuente("Courier.afm");
		$this->set_pdf_fuente_texto(10);
		$this->set_pdf_fuente_titulo(13);
		$this->set_pdf_fuente_subtitulo(11);
		$this->set_pdf_hoja("a4", "landscape");
	}
	
	public function get_columnas()
	{
		$titulo = [];
        $titulo['propuesta_nombre_abreviado'] = kernel::traductor()->trans('actuacion_provisoria.propuesta');
		$titulo['actividad_nombre'] = kernel::traductor()->trans('actuacion_provisoria.actividad');
        $titulo['fecha_visual'] = kernel::traductor()->trans('actuacion_provisoria.fecha');
		$titulo['nota'] = kernel::traductor()->trans('actuacion_provisoria.nota');
		$titulo['resultado_desc'] = kernel::traductor()->trans('actuacion_provisoria.resultado');
		$titulo['nro_acta'] = kernel::traductor()->trans('actuacion_provisoria.nro_acta');
        $titulo['anio_academico'] = kernel::traductor()->trans('actuacion_provisoria.anio_academico');
        $titulo['periodo_lectivo_nombre'] = kernel::traductor()->trans('actuacion_provisoria.periodo_lectivo');
		$titulo['comision_nombre'] = kernel::traductor()->trans('actuacion_provisoria.comision');
		return $titulo;
	}
	
	function get_datos()
	{
		if (!isset($this->datos_consulta)) {
                $parametros['persona'] = kernel::persona()->get_id();
                $datos_brutos = catalogo::consultar('alumno', 'actuacion_provisoria_cursada', $parametros);
                $columnas = $this->get_columnas();
                foreach ( $datos_brutos as $actuacion ) {
                    $datos_aceptados = array();
                    foreach ($actuacion as $campo => $dato) {
                        if (array_key_exists($campo, $columnas)) {
                            $datos_aceptados[$campo] = $dato;
                        }
                    }
                    $this->datos_consulta[] = $datos_aceptados;
                }
		}
		return $this->datos_consulta;
	}
	
	/**
	 * Recupera los datos
	 * @param entero $pagina
	 * Numero de página que se desea recuperar
	 * @param buleano $todo
	 * Recuperar todos los datos
	 * @return array 
	 */
	public function get_data_reporte($pagina = 0, $todo = true){	
	$i=0;
        $reg_por_pagina = $this->get_rpp();
        $pagina = $this->get_pagina();
        
        $minimo = ($pagina - 1) * $reg_por_pagina;
        $maximo = $minimo + $reg_por_pagina;
        $datos = $this->get_datos();
		
        $nuevo = array();
        if(!empty($datos)){
            foreach($datos as $id => $dato) {
                if ($todo or ($i <= $reg_por_pagina && $id >= $minimo && $id < $maximo)){				
                    $nuevo[$i] = $dato;
                    $i++;
                }
            }
        }
        $nuevo['reporte']='cursadas';
        return $nuevo;
	}

	/**
	 * Retorna la cantidad de datos
	 * @return entero
	 */
	protected function get_total_registros(){
		 return \guarani_arreglos::count($this->get_data_reporte(0, true));
	}
	
	public function get_nombre_archivo(){
		return "actuacion_provisoria_cursada";
	}
}