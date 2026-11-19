<?php 
abstract class ci_reporte extends guarani_pers_ci
{
	protected $s__filtro;
	protected $s__datos;
	protected $indx_msg_eof = '800_eof_cuadro';

	function ini()
	{
		toba::memoria()->desactivar_reciclado();
		parent::ini();
	}
	
	function conf()
	{
		if ($this->pantalla()->existe_evento('pdf')) {
			$datos = $this->cuadro()->get_datos();
			if (empty($datos)) {
				$this->pantalla()->eliminar_evento('pdf');
			}
		}
	}

	/**
	*	Permite recuperar el mensaje toba actualmente asociado al eof del cuadro.
	*/	
	function get_msg_eof($tabla=null)
	{
		if (!$tabla) $tabla = $this->get_tabla_principal();
		$indx_msg = $this->indx_msg_eof;
		if (isset($this->s__filtro)) {
			$indx_msg = $this->indx_msg_eof . '_filtrado';
		}
		$etiquetas = guarani_etiquetas::get_tabla($tabla);
		return guarani_mensajes::get($indx_msg, array($etiquetas->get_enf('plural')));
	}

	abstract function get_tabla_principal();
	
	abstract function get_datos($filtro);

	//---- cuadro -----------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_filtro_asociado($this->dep('filtro'));
		$cuadro->set_usar_ordenamiento_con_cortes();
		$datos = $this->get_datos($this->s__filtro);
		$cuadro->set_datos($datos);
		$cuadro->set_eof_mensaje($this->get_msg_eof());
	}

	//---- filtro -----------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		// Si el perfil de datos del usuario pertenece al dominio "Propuestas" es obligatorio filtrar los conceptos por ubicación.
		if (usuario::tiene_perfil_por_propuesta()) {
			$filtro->set_ubicacion_obligatoria();
		}
		if (isset($this->s__datos)) {
			$filtro->set_datos($this->s__datos);
		}		
	}
	
	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$this->s__filtro = $this->dep('filtro')->get_sql_where();
		} else {
			unset($this->s__datos);
			$this->s__filtro = "";
		}
	}
	
	function evt__filtro__cancelar()
	{
		unset($this->s__datos);
		$this->s__filtro = "";
	}
	

	//---- Salida JASPER-PDF ----------------------------------------------------------------

	// ------------------------------------------------------------------------------------------
	/* Se definen 2 metodos para obtener los datos del componente para la impresion del reporte.
	* Estos metodos son 'cuadro()' y 'encabezado()'. La idea es que se redefinan estos metodos 
	* con los objetos pertinentes para obtener la informacion a imprimir.
	* Con respecto a cuadro(), se refiere a un objeto toba_ei_cuadro q contiene los datos para
	* el detalle del reporte.
	* En cuanto a datos_encabezado, puede ser tanto un objeto toba_ei_filtro como un ev_info_datos.
	* La idea es que al solicitar el metodo (independientemente del objeto que utiliza el CI para
	* obtener el encabezado del reporte - filtro o ev) se obtengan los datos del encabezado con
	* el siguiente formato:
	* array (
	* 			fila_1 => 	(
	*							campo_1 =>	(
	* 											'etiq' =>	('xxxxxxxxxxxxx')
	* 											'valor' =>	(xxxxxxxxxxxx)
	*										)
	*							campo_2 =>	(
	* 											'etiq' =>	('xxxxxxxxxxxxx')
	* 											'valor' =>	(xxxxxxxxxxxx)
	*										)
	*						.....		 
	*						)
	*
	* 			fila_2 => 	(
	*							campo_1 =>	(
	* 											'etiq' =>	('xxxxxxxxxxxxx')
	* 											'valor' =>	(xxxxxxxxxxxx)
	*										)
	*							campo_2 =>	(
	* 											'etiq' =>	('xxxxxxxxxxxxx')
	* 											'valor' =>	(xxxxxxxxxxxx)
	*										)
	*						.....		 
	*						)
	*		)
	*
	*/

	function cuadro() 
	{
		return $this->dep('cuadro');
	}
	
	function datos_encabezado() 
	{
		if ($this->dependencia_cargada('filtro')) {
			$datos = $this->dep('filtro')->get_datos_impresion();
		} else {
			if (isset($this->ev_info)) {
				$datos = $this->ev_info->get_datos();
			} else {
				$datos = array();                            
			}
		}
		return $datos;
	}
	
	function ancho_etiquetas_encabezado()
	{
		return 100;
	}
	
	function get_template()
	{		
		return toba::proyecto()->get_path().'/exportaciones/jasper/' . 'template_guarani.jrxml';
	}
	
	// ------------------------------------------------------------------------------------------

	function vista_jasperreports(toba_vista_jasperreports $report) 
	{
		
		//Creo la plantilla (jrxml) para la creacion del reporte
		$jasper = new guarani_jasper();
		$jasper->set_template_nombre($this->get_template());
		
		// Parametros 
		$parametro['nombre'] = 'logo';
		$parametro['tipo'] = 'S';
		$pars[] = $parametro;		
		$jasper->set_parametros($pars);
		// Campos 
		$columnas = $this->cuadro()->get_columnas();
		$datos_fields =  array();			
		foreach ($columnas as $columna) {
			/*
			 * Si el formateo de la columna es de tipo date, entonces se envia el
			 * tipo D para que utilice esto al imprimir el valor de la columna
			 */			
			$tipo = 'S';
			if(isset($columna['formateo']) && $columna['formateo'] == 'fecha') {
				$tipo = 'D';
			}
			$datos_fields[] = array('nombre' => $columna['clave'], 'titulo' => $columna['titulo'], 'grupo' => $columna['grupo'], 'tipo' => $tipo);
		}		
		$jasper->set_fields($datos_fields);

		//Orientacion (Si cantidad de columnas > 5 se cambia la orientacion a horizontal)
		if (!isset($reporte_vertical)) {
			$reporte_vertical = (guarani_arreglos::count($columnas) < 6);
		}
		$jasper->set_orientacion_vertical($reporte_vertical);
		
		// Grupos 
		$grupos = $this->cuadro()->get_indice_cortes();
		$datos =  array();
		if (isset($grupos)) {
			foreach ($grupos as $g => $cols) {
				$cols_aux_id = explode(',', $cols['columnas_id']);
				foreach($cols_aux_id as $field) {
					$campo = trim($field);
					$jasper->add_fields_st(array('nombre' => $campo));
				}
				$cols_aux_desc = explode(',', $cols['columnas_descripcion']);
				foreach($cols_aux_desc as $field) {
					$campo = trim($field);
					$jasper->add_fields_st(array('nombre' => $campo));
				}
				$datos[] = array (
									'nombre'    => $cols['identificador'],
									'campo'     => $cols_aux_id,
									'campo_desc'=> $cols_aux_desc,
									'titulo'    => $cols['descripcion'],
									'pie'       => ($cols['pie_contar_filas'] == '1')
						  );
			}
		}
		$jasper->set_grupos($datos);
		// Titulo 
		$titulo = $this->get_titulo();
		$titulo = (empty($titulo)) ? 'Reporte' : $titulo;
		$jasper->set_titulo($titulo);
		// Encabezado
		$jasper->set_ancho_etiquetas_encabezado($this->ancho_etiquetas_encabezado());
		$jasper->set_datos_encabezado($this->datos_encabezado());
		//Obtengo el archivo jrxml y lo paso como plantilla al reporte
		$archivo = toba::proyecto()->get_path_temp() .'/'. guarani::get_id_operacion() . '.jrxml';
		$jasper->set_jrxml_archivo($archivo);
		
		//Reporte
		//Paso la plantilla 
		$report->compilar_reporte($archivo);
		//Obtengo los datos del cuadro y se lo paso al reporte 
		$xml = $this->cuadro()->vista_xml();
		/*
		// Queda comentado esto por si a futuro se decide guardar los datos pasados al reporte, como debug en un archivo
		$archivo = toba::proyecto()->get_path_temp() .'/'. guarani::get_id_operacion() . '_datos.xml';
		//Escribo el xml con los datos
		if (file_put_contents($archivo, utf8_encode($xml)) === false) {
			throw new toba_error("Error al guardar archivo de datos xml", "No es posible escribir en ".$archivo);
		}		
		$report->set_archivo_xml($archivo);
		*/
		$report->set_xml(utf8_e_seguro($xml));

		//Paso los parametros
		//Obtengo el logo a imprimir
		$path_logo = guarani::get_path_logo_impresion();
		$report->set_parametro('logo', 'S', $path_logo);		
			
	}	
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "
		{$this->objeto_js}.evt__pdf = function()
		{
			window.open(vinculador.get_url(null,null,'vista_jasperreports', null, [{$this->objeto_js}._id])); 
			return false;
		}
		";
	}
	
}