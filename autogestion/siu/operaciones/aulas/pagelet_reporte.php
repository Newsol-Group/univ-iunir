<?php
namespace siu\operaciones\aulas;

use SIU\Chulupi\kernel;
use siu\modelo\datos\catalogo;

class pagelet_reporte extends \siu\operaciones\_comun\operaciones\reporte\pagelet_reporte
{
	protected $datos_consulta;

	function get_nombre()
	{
		return 'reporte';
	}

	function configurar()
	{
		$this->set_rpp(15);
		$this->set_columnas_reporte($this->get_columnas());
		// Configuracion pdf
		$this->set_pdf_encabezado(kernel::traductor()->trans('header.menu.aulas'), "", 112);
		$this->set_pdf_fuente("Courier.afm");
		$this->set_pdf_fuente_texto(10);
		$this->set_pdf_fuente_titulo(13);
		$this->set_pdf_fuente_subtitulo(11);
		$this->set_pdf_hoja("a4");
	}

	function get_columnas()
	{
		$titulo = array();
		$titulo['dia'] = "Día";
		$titulo['materia'] = "Materia";
		$titulo['aula'] = "Aula";
		$titulo['edificio'] = "Edificio";
		$titulo['inicio'] = "Hora Inicio";
		$titulo['fin'] = "Hora Fin";
		$titulo['tipo'] = "Tipo de clase";
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
	function get_data_reporte($pagina = 0, $todo = false)
	{
		$i = 0;
		$reg_por_pagina = $this->get_rpp();
		$pagina = $this->get_pagina();

		$minimo = ($pagina - 1) * $reg_por_pagina;
		$maximo = $minimo + $reg_por_pagina;

		$datos = $this->get_datos();

		$nuevo = array();
		foreach ($datos as $id => $dato) {
			$linea = array();
			if ($todo or ( $i <= $reg_por_pagina && $id >= $minimo && $id < $maximo)) {
				$linea['dia'] = $dato[9];
				$linea['materia'] = "(" . $dato[4] . ") " . $dato[18];
				$linea['aula'] = $dato[12];
				$linea['edificio'] = $dato[20];
				$linea['inicio'] = $dato[10];
				$linea['fin'] = $dato[14];
				$linea['tipo'] = $dato[16];
				$nuevo[$i] = $linea;
				$i++;
				unset($acta);
			}
		}
		return $nuevo;
	}

	function get_datos()
	{
		if (!isset($this->datos_consulta)) {
			$parametros['nro_inscripcion'] = kernel::persona()->get_nro_inscripcion();
			$this->datos_consulta = catalogo::consultar('alumno', 'aulas', $parametros);
		}
		return $this->datos_consulta;
	}

	/**
	 * Retorna la cantidad de datos
	 * @return entero
	 */
	protected function get_total_registros()
	{
		return \guarani_arreglos::count($this->get_data_reporte(0, true));
	}

	function get_nombre_archivo()
	{
		return "aulas";
	}

}
?>