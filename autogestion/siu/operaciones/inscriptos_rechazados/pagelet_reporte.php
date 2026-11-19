<?php
namespace siu\operaciones\inscriptos_rechazados;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\modelo\ProcesadorVista;
use SIU\Chulupi\util\u;
use siu\operaciones\_comun\operaciones\reporte\pagelet_reporte as pagelet_reporte_padre;
use siu\modelo\datos\catalogo;

class pagelet_reporte extends pagelet_reporte_padre
{
	public function get_nombre(){
		return 'reporte';
	}

	function get_js_files()
	{
		$archivos = parent::get_js_files();
		$archivos = array_merge($this->get_vista_form()->get_js_files(), $archivos);
		return $archivos;
	}

	protected function configurar()
	{
		$this->set_rpp(15);
		$this->set_columnas_reporte($this->get_columnas());

		// Configuracion pdf
		$this->set_pdf_encabezado(kernel::traductor()->trans("tit_inscriptos_rechazados"), "", 112);
		$this->set_pdf_fuente("Courier.afm");
		$this->set_pdf_fuente_texto(10);
		$this->set_pdf_fuente_titulo(13);
		$this->set_pdf_fuente_subtitulo(11);
		$this->set_pdf_hoja("a4");
	}

	public function get_columnas(){
		$titulo = array();
		$titulo['actividad_orden'] = array("Actividad", array('justification' => 'center', 'width' => 70));
		$titulo['anio_academico'] = array("Año", array('justification' => 'center', 'width' => 35));
		$titulo['periodo_lectivo_nombre'] = array("Período Lectivo", array('justification' => 'center', 'width' => 65));
		$titulo['comision_nombre'] = array("Comisión", array('justification' => 'center', 'width' => 55));
		$titulo['ubicacion_nombre'] = array("Ubicación", array('justification' => 'center', 'width' => 60));
		$titulo['responsabilidad_nombre'] = array("Rol", array('justification' => 'center', 'width' => 60));
		$titulo['turno_nombre'] = array("Turno", array('justification' => 'center', 'width' => 50));
		$titulo['cantidad_rechazados'] = array("Rechazados", array('justification' => 'center', 'width' => 50));
		return $titulo;
	}

	protected function get_data_reporte($inicio = 0, $todo = false)
	{
		return $this->get_data();
	}

	protected function get_total_registros()
	{
		return \guarani_arreglos::count($this->get_data_reporte(0, true));
	}

	/**
	 * Recupera los datos
	 * @param entero $pagina
	 * Numero de página que se desea recuperar
	 * @param buleano $todo
	 * Recuperar todos los datos
	 * @return array 
	 */
	public function get_data()
	{
		$datos = $this->controlador->get_lista_comisiones();
		$columnas = array_keys($this->get_columnas());
		$nuevo = array();
		$i = 0;
		foreach($datos as $id => $dato)
		{
			foreach($columnas as $columna)
			{
				if (isset($dato[$columna]))
					$nuevo[$i][$columna] = $dato[$columna];
				else
					$nuevo[$i][$columna] = null;
			}
			$i++;
		}
		return $nuevo;
	}

	private function ordenar($datos)
	{
		$rs = $datos;
		$rs->link('LINK', 'inscriptos_rechazados', 'info_comision');
		$resultado = array();
		
		foreach($rs as $valor)
		{
			$resultado[$valor['anio_academico']]['anio_academico'] = $valor['anio_academico'];
			$resultado[$valor['anio_academico']]['periodo_lectivo'] = $valor['periodo_lectivo'];
			$resultado[$valor['anio_academico']]['periodo_lectivo_nombre'] = $valor['periodo_lectivo_nombre'];
			if (substr(trim($valor['periodo_lectivo_nombre']), -4) == $valor['anio_academico'])
				$resultado[$valor['anio_academico']]['periodo_lectivo_nombre'] = substr(trim($valor['periodo_lectivo_nombre']), 0, -4);
			$resultado[$valor['anio_academico']]['actividades'][$valor['actividad']]['nombre'] = $valor['actividad_orden'];

			$resultado[$valor['anio_academico']]['actividades'][$valor['actividad']]['comisiones'][$valor['comision']] = array(
				'comision_nombre' => $valor['comision_nombre'],
				'ubicacion_nombre' => $valor['ubicacion_nombre'],
				'turno_nombre' => $valor['turno_nombre'],
				'responsabilidad_nombre' => $valor['responsabilidad_nombre'],
				'instancias' => $valor['instancias'],
				'cantidad_rechazados' => $valor['cantidad_rechazados'],
				'LINK' => $valor['LINK'],
			);
		}
		return $resultado;
	}

	function prepare()
	{
		parent::prepare();
		$this->data['rechazados'] = $this->ordenar($this->controlador->get_lista_comisiones());
		
		$this->add_var_js('url_filtrar_comisiones', kernel::vinculador()->crear('inscriptos_rechazados', 'index'));
		$this->add_var_js('url_generar_pdf', kernel::vinculador()->crear('inscriptos_rechazados', 'generar_pdf'));
		$this->add_var_js('url_generar_xls', kernel::vinculador()->crear('inscriptos_rechazados', 'generar_excel'));
	}

	public function get_nombre_archivo(){
		return "inscriptos_rechazados";
	}

	public function get_vista_form()
	{
		return $this->controlador->get_vista_form();
	}
}
?>