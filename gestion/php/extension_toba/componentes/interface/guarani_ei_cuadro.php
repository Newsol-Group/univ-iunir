<?php
class guarani_ei_cuadro extends toba_ei_cuadro
{
	protected $_filtro_asociado;

	protected $_etiqueta_cantidad_filas_prof;

	function inicializar($parametros=array())
	{
		parent::inicializar($parametros);
		$this->set_manejador_salida('xml', 'guarani_ei_cuadro_salida_xml');
		$this->set_manejador_salida('html', 'guarani_ei_cuadro_salida_html');
		$this->set_manejador_salida('excel', 'guarani_ei_cuadro_salida_excel');
	}
	
	function set_filtro_asociado($filtro)
	{
		$this->_filtro_asociado = $filtro;
	}

	function hay_filtro_asociado()
	{
		return $this->_filtro_asociado ? true : false;
	}

	function generar_inicio()
	{
		if ($this->hay_filtro_asociado() && $this->_tipo_salida != 'html') {
			$metodo_vista = 'vista_'.$this->_tipo_salida;
			$salida = $this->_salida->get_resultado_generacion();
			$this->_filtro_asociado->$metodo_vista($salida);
		}
		parent::generar_inicio();
	}
	
	//---------------------------------------------------------------
	//------------------------- SALIDA XML --------------------------
	//---------------------------------------------------------------
	
	/**
	 * Genera el xml del componente
	 * @param boolean $inicial Si es el primer elemento llamado desde vista_xml
	 * @param string $xmlns Namespace para el componente
	 * @return string XML del componente
	 */
	function vista_xml($inicial=false, $xmlns=null)
	{
		$cant_pags = $this->_cantidad_paginas;
		if ($cant_pags > 1) {
	
			if ($xmlns) {
				$this->xml_set_ns($xmlns);
			}

			//Guardo la pagina actual
			$pag_actual = $this->get_pagina_actual();

			//Armo las tablas segun la cant de registros por paginas indicada en el controlador
			$salida = '<'.$this->xml_ns.'tabla'.$this->xml_ns_url . " break-after='page'";
			$this->xml_set_titulo($this->get_titulo());
			$salida .= $this->xml_get_att_comunes();
			$salida .= '><datos>';
			$salida .= $this->xml_get_elem_comunes();
		
			$salida_datos = '';
			for ($i = 0; $i < $cant_pags; $i++) {
				$offset = $i * $this->_tamanio_pagina;
				$this->datos = array_slice($this->controlador()->get_datos_cuadro_salida_xml(), $offset, $this->_tamanio_pagina);

				$this->generar_salida('xml', $salida_datos);
				$salida_datos = $this->_salida->get_resultado_generacion();
			}
			$salida_datos =  str_replace('<datos>', '', $salida_datos);
			$salida_datos =  str_replace('</datos>', '', $salida_datos);
			$salida .= $salida_datos . '</datos></' . $this->xml_ns . 'tabla>';

			//Restauro la pagina actual
			$this->set_pagina_actual($pag_actual);
		} else  {
			$salida = parent::vista_xml($inicial, $xmlns);
		}
		return $salida;
	}

	function vista_xml_parent($inicial=false, $xmlns=null)
	{
		return parent::vista_xml($inicial, $xmlns);
	}
	
	// Se redefine la asignacion de datos al cuadro para que almacene todos los datos asignados para realizar el paginado en la impresion de pdf en JASPER
	function set_datos($datos)
	{
		$this->controlador()->set_datos_cuadro_salida_xml($datos);
		parent::set_datos($datos);
	}
	
	function set_datos_parent($datos)
	{
		parent::set_datos($datos);
	}

	//-------------------------------------------------------------------------------
	//-- Cortes de Control
	//-------------------------------------------------------------------------------

	function set_etiqueta_cantidad_filas_profundidad($etiqueta, $profunidad)
	{
		$this->_etiqueta_cantidad_filas_prof[$profunidad] = $etiqueta;
	}

	function get_etiqueta_cantidad_filas_profundidad($profunidad)
	{
		return isset($this->_etiqueta_cantidad_filas_prof[$profunidad]) ? $this->_etiqueta_cantidad_filas_prof[$profunidad] : null;
	}
}
