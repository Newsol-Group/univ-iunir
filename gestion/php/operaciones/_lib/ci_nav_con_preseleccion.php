<?php 

abstract class ci_nav_con_preseleccion extends ci_navegacion
{
	protected $s__filtro_preseleccion;
	protected $s__datos_preseleccion;
	protected $s__concepto_preseleccion = 'dato';

	protected $con_listado_inicial_preseleccion=true;

	// Inicializar con las columnas deseadas si se necesita utilizar el mapeo para la seleccion del cuadro
	protected $s__columnas_a_guardar_en_mapeo_preseleccion;
	protected $s__mapeo_cuadro_preseleccion;

	function get_msg_eof_preseleccion($con_filtro=false)
	{
		$indx_msg = $this->indx_msg_eof;
		if ($con_filtro) {
			$indx_msg = $this->indx_msg_eof . '_filtrado';
		}
		$etiquetas = guarani_etiquetas::get_concepto($this->s__concepto_preseleccion);
		$etiqueta = $etiquetas->get_enf('plural');
		return guarani_mensajes::get($indx_msg, array($etiqueta));
	}
	
	abstract function set_preseleccion($seleccion);
	
	function set_concepto_preseleccion($concepto) 
	{
		$this->s__concepto_preseleccion = $concepto;
	}


	function set_sin_listado_inicial_preseleccion()
	{
		$this->con_listado_inicial_preseleccion=false;
		$this->dep('filtro_preseleccion')->set_sin_filtrado_inicial();
		$this->dep('filtro_preseleccion')->set_etiquetas(guarani_etiquetas::get_concepto($this->s__concepto_preseleccion));
	}

	function con_listado_inicial_preseleccion()
	{
		return $this->con_listado_inicial_preseleccion;
	}
	
	//------ Filtro preselección ----------------------

	function get_filtro_preseleccion_condicion()
	{
		return isset($this->s__filtro_preseleccion) ? $this->s__filtro_preseleccion : null;
	}
	
	function evt__filtro_preseleccion__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_preseleccion = $datos;
			$this->s__filtro_preseleccion = $this->dep('filtro_preseleccion')->get_sql_where();
		} else {
			unset($this->s__datos_preseleccion);
			$this->s__filtro_preseleccion = '';
		}
	}

	function evt__filtro_preseleccion__cancelar()
	{
		unset($this->s__datos_preseleccion);
		unset($this->s__filtro_preseleccion);
	}

	function conf__filtro_preseleccion(toba_ei_filtro $filtro)
	{
		// Si el perfil de datos del usuario pertenece al dominio "Propuestas" es obligatorio filtrar los conceptos por ubicación.
		if (usuario::tiene_perfil_por_propuesta()) {
			$filtro->set_ubicacion_obligatoria();
		}
		if (isset($this->s__datos_preseleccion)) {
			$filtro->set_datos($this->s__datos_preseleccion);
		}		
	}

	//------ Cuadro Preselección ----------------------

	function evt__cuadro_preseleccion__seleccion($seleccion)
	{
		if($this->modo_seleccion_con_mapeo_preseleccion()) {
			$clave = $this->get_clave_cuadro_preseleccion();
			$seleccion = $this->s__mapeo_cuadro_preseleccion[$seleccion[$clave]];
			unset($this->s__mapeo_cuadro_preseleccion);
		}
		$this->set_preseleccion($seleccion);
		$this->set_pantalla('seleccion');
	}

	function get_clave_cuadro_preseleccion()
	{
		$clave_arr = array_keys($this->dep('cuadro_preseleccion')->get_clave_fila_array(0));
		//$clave_arr = $this->dep('cuadro_preseleccion')->get_columnas_clave(); // extendido toba ver si es viable
		if (guarani_arreglos::count($clave_arr) < 0 || guarani_arreglos::count($clave_arr) > 1) {
			$id = $this->dep('cuadro_preseleccion')->get_id();
			throw new toba_error('Error interno: para utulizar el modo mapeo para selección, se debe definir solo una columna clave en el cuadro: ' .$id[1]);
		}
		return $clave_arr[0];
	}

	function modo_seleccion_con_mapeo_preseleccion()
	{
		return isset($this->s__columnas_a_guardar_en_mapeo_preseleccion);
	}

	/**
	 * Métodp para ser extendido por las subclases , en caso de ser necesario la modificación del nombre del metodo con el
	 * que se carga el cuadro_preseleccion
	 *
	 * @return <type>
	 */
	protected function get_listado_preseleccion()
	{
		return $this->cn()->get_listado_preseleccion($this->get_filtro_preseleccion_condicion());
	}


	function conf__cuadro_preseleccion(toba_ei_cuadro $cuadro)
	{

		$this->indx_msj_eof = '800_eof_cuadro';
		if (!isset($this->s__filtro_preseleccion) && !$this->con_listado_inicial_preseleccion()) {
			$this->indx_msg_eof .= '_sinfiltro';
		} else {
			$datos = $this->get_listado_preseleccion();
			/*
			 *  Para utilizar el mapeo con preseleccion en el ini del ci_nav que extiende este ci setear la variable de session
			 *  $this->s__columnas_a_guardar_en_mapeo_preseleccion  con las columnas que se desean mantener en el mapeo
			 *  ejemplo  array('persona','apellido','nombres','documento_desc');
			 *  y en el set_preseleccion
			 * 	this->s__persona_seleccionada = $seleccion;
			*/
			if($this->modo_seleccion_con_mapeo_preseleccion()) {
				$clave = $this->get_clave_cuadro_preseleccion();
				if (!empty($datos) && array_key_exists($clave,current($datos))) {
					$string = implode(", ",array_keys(current($datos)));
					$id = $this->dep('cuadro_preseleccion')->get_id();
					throw new toba_error("Error interno: al utilizar el modo mapeo para selección, la columna clave '".$clave."' del cuadro_preseleccion $id[1] no debe ser una da las columnas predefinidas: " . $string);
				}
				foreach ($datos as $key => $dato) {
					$datos[$key][$clave]=$key;
					foreach ($this->s__columnas_a_guardar_en_mapeo_preseleccion as $columna) {
						$this->s__mapeo_cuadro_preseleccion[$key][$columna] = $datos[$key][$columna];
					}
				}
			}
			$cuadro->set_datos($datos);

		}
		$cuadro->set_eof_mensaje($this->get_msg_eof_preseleccion(isset($this->s__filtro_preseleccion)));
	}
	
	//------ Pantallas  ----------------------

	function evt__cancelar_preseleccion()
	{
		$this->cn()->resetear();
		$this->set_pantalla('preseleccion');
	}

}

?>