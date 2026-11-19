<?php

/**
 * Un filtro presenta una grilla donde es posible seleccionar criterios de búsqueda para las distintas columnas definidas.
 * Según el tipo de la columna se despliegan distintos criterios
 * @package Componentes
 * @subpackage Eis
 * @jsdoc ei_filtro ei_filtro
 * @wiki Referencia/Objetos/ei_filtro
 */
class guarani_ei_filtro extends toba_ei_filtro
{
	protected $s__con_sensitividad_tildes=true;
	protected $s__con_sensitividad_mayusc=true;
	protected $s__con_filtrado_inicial=true;
	protected $etiquetas;

	function inicializar($parametros=array())
	{
		if (! $this->get_titulo() || $this->get_titulo() == '') {
			$this->set_titulo('Filtro');
		}
		$this->_etiquetas = array('columna' => 'Columna', 'distinguir' => 'Distinguir', 'condicion' => 'Condición', 'valor' => 'Valor');
		parent::inicializar($parametros);
		$this->formatear_filtro_ubicacion();
	}
	
	/**
	 * Los filtros por ubicación sólo admiten la condición "Es igual a" por 
	 * cuestiones inherentes a perfiles de datos
	 */
	protected function formatear_filtro_ubicacion()
	{
		foreach ($this->_columnas as $key => $columna) {
			if (strpos($key, 'ubicacion') !== false) {
				$this->columna($key)->set_condicion_fija('es_igual_a');
			}
		}
	}

	function set_etiquetas($etiquetas)
	{
		$this->etiquetas = $etiquetas;
	}

	function set_sin_filtrado_inicial()
	{
		$this->s__con_filtrado_inicial = false;
	}

	/*
	 * Inhabilita la capacidad del filtro para hacer búsquedas sin distinción de tildes.
	 */
	function set_sin_sensitividad_tildes()
	{
		$this->s__con_sensitividad_tildes = false;
	}

	/*
	 * Inhabilita la capacidad del filtro para hacer búsquedas sin distinción de mayúsculas/minúsculas.
	 */
	function set_sin_sensitividad_mayusculas()
	{
		$this->s__con_sensitividad_mayusc = false;
	}

	function set_ubicacion_obligatoria()
	{
		$columnas = $this->get_ids_columnas();
		if (in_array('ubicacion', $columnas)) {
			$this->columna('ubicacion')->set_visible(true);
			$this->columna('ubicacion')->get_ef()->set_obligatorio(true);
		}
	}

	function con_filtrado_inicial()
	{
		return $this->s__con_filtrado_inicial;
	}

	function con_sensitividad_tildes()
	{
		return $this->s__con_sensitividad_tildes;
	}

	function con_sensitividad_mayusculas()
	{
		return $this->s__con_sensitividad_mayusc;
	}
	
	function esta_cargado()
	{
		$filtro = trim($this->get_sql_where());
		return (!empty($filtro) && $filtro != '1=1');
	}

        /**
         * Arma expresión SQL para filtros que distinguen may/min y tildes.
         *
         * @param string $expresion Columna o expresión a filtrar.
         * @param string $valor Valor a filtrar.
         * @param string $condicion_cs Distinguir may/min.
         * @param string $condicion_as Distinguir tildes.
         * @param string $condicion Condición, por defecto es 'contiene'.
         *
         * @return string Expresión SQL.
         */
	public static function get_expresion($expresion, $valor, $condicion_cs, $condicion_as, $condicion = 'contiene')
	{
		
		$pre = '';
		$post = '';
		// Si la "Condición" es "contiene"
		if ($condicion == 'contiene') {
			$pre = '%';
			$post = '%';
		}
		
		// Si Distinguir "may/min" esta chequeado le pongo 'LIKE' sino 'ILIKE'.
		$operador_sql = ($condicion_cs == comunes::on)? comunes::like : comunes::ilike;
		
		// Si Distinguir "tildes" no esta chequeado limpio los acentos.
		if ($condicion_as != comunes::on) {
			$expresion = "f_limpiar_acentos($expresion::text)";
			$valor = guarani_texto::limpiar_acentos($valor);
		}
		
		$filtro_condicion = new toba_filtro_condicion($condicion, $operador_sql, $pre, $post);
		return $filtro_condicion->get_sql($expresion, $valor);
	}
	
	/**
	 * Crea los objetos columna necesarios
	 */
	protected function crear_columnas()
	{
		$this->_columnas = array();
		$efs = array();
		$parametros_efs = array();
		foreach ($this->_info_filtro_col as $fila) {
			if ($fila['tipo'] == 'cadena' || $fila['tipo'] == 'fecha' || $fila['tipo'] == 'numero') {
				$clase = 'guarani_filtro_columna_'.$fila['tipo'];
			} else {
				$clase = 'toba_filtro_columna_'.$fila['tipo'];
			}	
			$this->_columnas[$fila['nombre']] = new $clase($fila, $this);
			$efs[$fila['nombre']] = $this->_columnas[$fila['nombre']]->get_ef();
			$parametros = $fila;
			if (isset($parametros['carga_sql']) && !isset($parametros['carga_fuente'])) {
				$parametros['carga_fuente']=$this->_info['fuente'];
			}			
			$parametros_efs[$fila['nombre']] = $parametros;
		}
		//--- Se registran las cascadas porque la validacion de efs puede hacer uso de la relacion maestro-esclavo
		$this->_carga_opciones_ef = new toba_carga_opciones_ef($this, $efs, $parametros_efs);
		$this->_carga_opciones_ef->registrar_cascadas();
	}

	/**
	 * @ignore 
	 */
	protected function generar_formulario_cuerpo()
	{
		echo toba::output()->get('Filtro')->getInicioFormularioFiltros();

		$estilo_celda = "ei-filtro-fila";
		foreach ($this->_columnas as $nombre_col => $columna) {
			$this->analizar_visualizacion_columna($columna);
			echo toba::output()->get('Filtro')->getInicioFiltro("{$this->objeto_js}_fila$nombre_col", $nombre_col, $columna->get_html_etiqueta(), $columna->es_visible(), "{$this->objeto_js}.seleccionar('$nombre_col')", $estilo_celda, $this->generar_vinculo_editor($nombre_col), $columna);

			//-- Diferenciar
			echo "<td class='$estilo_celda ei-filtro-cond'>";
			if ($columna instanceof guarani_filtro_columna_cadena) {
				echo $columna->get_html_diferenciar();
			} else {
				echo '&nbsp';
			}	
			echo "</td>\n";
			
			//-- Condición
			echo toba::output()->get('Filtro')->getParseCondicion($columna->get_html_condicion(), $estilo_celda);

			//-- Valor
			ob_start();
			$columna->get_html_valor();				//Esto se hace para mantener el contrato del metodo get_html_valor
			$html_columna_valor = ob_get_clean();
			echo toba::output()->get('Filtro')->getParseValor($html_columna_valor, $estilo_celda);

			//-- Borrar a nivel de fila
			echo toba::output()->get('Filtro')->getEliminarFiltro("{$this->objeto_js}_eliminar$nombre_col", "{$this->objeto_js}.seleccionar('$nombre_col');{$this->objeto_js}.eliminar_seleccionada();", $this->_rango_tabs[0]++, $columna->es_solo_lectura(), $columna->es_obligatorio() || $nombre_col == 'columnas_opc', $estilo_celda);

			echo toba::output()->get('Filtro')->getFinFiltro();
		}
		echo toba::output()->get('Filtro')->getFinFormularioFiltros();
	}
	
	protected function analizar_visualizacion_columna($columna)
	{
		parent::analizar_visualizacion_columna($columna);
		if (isset($this->_columnas['columnas_opc'])) {
			$this->_columnas['columnas_opc']->set_visible(true);
		}
	}

	/**
	 * Recupera los datos del Filtro con el formato requerido para la impresión genérica de Reportes en Jasper.
	 * Retorna una matriz indexada por filas, compuesta por los ids de los efs, con el arreglo asociativo que contiene
	 * tanto la etiqueta del mismo, como la descripción de la condición y del valor.
	 * Por ejemplo: array ( 0 => array ('anio_academico' => 
	 * 													array ( 'etiqueta' => 'Año Académico',
     * 															'valor' => 'es igual a 2012'
     *															),
  	 *									),
  	 *						1 => array ('turno_examen' => 
     *													array ( 'etiqueta' => 'Turno de Examen',
     * 															'valor' => 'es igual a 1223'
     *															),
  	 *									)
  	 *					)
  	 * 
  	 * Se utiliza este formato para mantenerlo análogo a los datos recuperados para la impresión de un ev por filas.
	 */
	function get_datos_impresion()
	{
		$datos = array();
		$this->_carga_opciones_ef->cargar();		
		foreach ($this->_columnas as $columna) {
			if ($columna->get_ef()->tiene_estado()) {
				$id = $columna->get_ef()->get_id();
				if ($id != 'columnas_opc') {
					$etiqueta = $columna->get_ef()->get_etiqueta();
					$condicion = $columna->condicion()->get_etiqueta();
					$valor = $columna->get_pdf_valor();
					array_push($datos, array($id => array('etiqueta' => $etiqueta, 'valor' => $condicion . ' ' . $valor)));
				}
			}
		}
		return $datos;
	}		
	
	//---------------------------------------------------------------
	//----------------------  SALIDA EXCEL --------------------------
	//---------------------------------------------------------------
		
	function vista_excel(toba_vista_excel $salida)
	{
		$this->_carga_opciones_ef->cargar();		
		$formateo = new $this->_clase_formateo('excel');
		$datos = array();
		foreach ( $this->_columnas as $columna ){
			if (!$columna->es_visible()){
				continue;
			}
			
			if ($columna->get_ef()->tiene_estado()) {
				
				$id = $columna->get_ef()->get_id();
				if ($id != 'columnas_opc') {
					//Se eliminan las columnas opcionales de la salida
					$opciones = array();
					$etiqueta = $columna->get_ef()->get_etiqueta();			
					//Hay que formatear?
					$estilo = array();
					$fn_formateo = $columna->get_formateo();
					if (! is_null($fn_formateo)){
						$funcion = "formato_" . $fn_formateo;
						$valor_real = $columna->get_ef()->get_estado();
						list($valor, $estilo) = $formateo->$funcion($valor_real);
					}else{
						list($valor, $estilo) = $columna->get_excel_valor();
					}

					$condicion = $columna->condicion()->get_etiqueta();				
					if (isset($estilo)) {
						$opciones['valor']['estilo'] = $estilo;
					}	
					$opciones['etiqueta']['estilo']['font']['bold'] = true;
					$opciones['etiqueta']['ancho'] = 'auto';
					$opciones['condicion']['ancho'] = 'auto';
					$opciones['valor']['ancho'] = 'auto';				
					$datos = array(array('etiqueta' => $etiqueta, 'condicion' => $condicion, 'valor' => $valor));
					$salida->tabla($datos, array(), $opciones);
				}
			}
		}		
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		parent::extender_objeto_js();
		$id_js_filtro = $this->get_id_objeto_js();

		if ($this->con_filtrado_inicial()) {
			return;
		}
		if ($this->etiquetas) {
			$msj_confirm_consulta = guarani_mensajes::get('800_conf_filtrovacio', array($this->etiquetas->get_plural()));
		} else {
			$msj_confirm_consulta = guarani_mensajes::get('800_conf_filtrovacio');
		}
		//Chequea si ninguno de los campos (columnas) del filtro tienen estado, es decir el filtro es vacío, el usuario
		//debe confirmar la ejecución de la consulta.
		echo "{$id_js_filtro}.evt__filtrar = function() {";
			//Obtiene las filas y los campos de la variable interna
			//que se mantiene en el filtro de columnas y valores (ei_filtro.js).
		echo "	var filas = this._filas;
				var campos = this._efs;
				var confirmar_cons = true;
				for (id_fila in filas) {
					var id_ef = this._filas[id_fila];
					if (campos[id_ef].tiene_estado()) {
						confirmar_cons = false;
						break;
					}
				}
				if (confirmar_cons) {
					return confirmar_consulta();
				}
			}

			function confirmar_consulta(proyecto, operacion, url, es_popup) {
				if (! es_popup || typeof(es_popup) == 'undefined') {
					resp = confirm('$msj_confirm_consulta');
					if (resp) {
						return true;
					} else {
						return false;
					}
				}
				return true;
			}

		";
	}
		
}