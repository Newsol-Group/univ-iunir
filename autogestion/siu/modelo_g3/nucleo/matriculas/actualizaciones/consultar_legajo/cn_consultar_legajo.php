<?php
class cn_consultar_legajo extends guarani_pers_cn
{	
	protected $s__historia;
	protected $s__datos_persona;
	protected $s__consultado = false;
	protected $s__instituciones = array();
			
	function get_instituciones()
	{
		if (empty($this->s__instituciones)) {
			//Obtengo las instituciones
			$instituciones = rest_decode($this->get_listado_instituciones(), true);
			foreach ($instituciones as $institucion) {
				$this->s__instituciones[$institucion['id']] = $institucion;
			}
		}
		return $this->s__instituciones;
	}
	
	function get_datos_conexion($id) 
	{
		return $this->s__instituciones[$id];
	}
	
	function get_datos_persona($filtro)
	{
		//Datos de la persona. Por WS
		try {
			/*
			 * En el filtro tengo la institucion seleccionada por el usuario.
			 * Voy a buscar los datos de conexion para asi conectarme a esas apis
			 * Para los datos de la persona se utiliza la primer api que aparezca
			 */			
			$datos_personales = array();
			$query = array('query' => array('pais' => $filtro['pais'], 'tipo_documento' => $filtro['tipo_documento'], 'numero_documento' => $filtro['nro_documento']));			
			$conexion = $this->get_datos_conexion($filtro['institucion']);			
			
			$ws = new toba_servicio_web_cliente_rest($conexion['apis'][0], 'instituciones');			
			$this->s__datos_persona = rest_decode($ws->guzzle()->get('personas', $query)->getBody()->getContents());
			if (isset($this->s__datos_persona['persona'])) {
				//Con el valor devuelto por el ws uso el idpersona para buscar los datospersonales
				$datos_personales = rest_decode($ws->guzzle()->get("personas/{$this->s__datos_persona['persona']}/datospersonales")->getBody()->getContents());
			}
		}
		catch (\GuzzleHttp\Exception\ClientException $e) {
			toba::logger()->error($e->getRequest());
			toba::logger()->error($e->getResponse());
			toba::logger()->error($e->getMessage());
		}
		catch (Exception $e) {
			toba::logger()->error($e->getMessage());
		}
		unset($ws);
		return ($datos_personales);
	}
	
	function get_historia_academica_alumno($filtro)
	{
		try {
			$historia_academica = array();
			$aux = array();
			$conexion = $this->get_datos_conexion($filtro['institucion']);			
			/*
			 * Tengo que recorrer todas las apis para encontrar los datos academicos
			 * si es que lo tiene en varios.
			 */
			if (isset($this->s__datos_persona['persona'])) {
				foreach ($conexion['apis'] as $api) {
					$ws = new toba_servicio_web_cliente_rest($api, 'instituciones');
					//Con el valor devuelto por el ws uso el idpersona para buscar los datosanalitico
					$aux = rest_decode($ws->guzzle()->get("personas/{$this->s__datos_persona['persona']}/datosanalitico")->getBody()->getContents());
					unset($ws);
					$historia_academica = array_merge($historia_academica, $aux);
				}
			}
		}
		catch (\GuzzleHttp\Exception\ClientException $e) {
			toba::logger()->error($e->getRequest());
			toba::logger()->error($e->getResponse());
			toba::logger()->error($e->getMessage());				
		}
		catch (Exception $e) {
			toba::logger()->error($e->getMessage());
		}
		$this->s__consultado = true;		
		return $historia_academica;
	}
	
	/*
	 * Se trae la historia academica filtrada por el tipo.
	 * Tipo: A => Actividades, T => Trayectos
	 */
	function get_historia_filtrada($filtro, $tipo)
	{
		/*
		 * La variable consultado se utiliza para no llamar al ws dos veces 
		 * en el mismo request. Si la misma esta en false significa que voy 
		 * consultar al ws por primera vez.
		 */
		if (!$this->s__consultado) {		
			$this->s__historia = $this->get_historia_academica_alumno($filtro);
			$this->ordenar_historia();
		}		
		$datos = array();
		if (!empty($this->s__historia)) {		
			foreach(array_keys($this->s__historia) as $key) {
				if($this->s__historia[$key]['tipo'] == $tipo) {
					$datos[] = $this->s__historia[$key];
				}
			}
		}
		return $datos;
	}	
	
	/*
	 * Trae la historia academica filtrada por actividades
	 */
	function get_actividades($filtro)
	{
		return $this->get_historia_filtrada($filtro, 'A');
	}
	
	/*
	 * Trae la historia academica filtrada por trayectos
	 */
	function get_trayectos($filtro)
	{
		return $this->get_historia_filtrada($filtro, 'T');
	}
	
	/*
	 * Limpia las variables para que al hacer otra busqueda se use otra vez la 
	 * variable.
	 */
	function reset_variables()
	{
		unset($this->s__historia);
		unset($this->s__datos_persona);
		$this->s__consultado = false;		
	}
	
	/*
	 * Hace la conexion para buscar las instituciones y sus valores para la conexion
	 */
	function get_listado_instituciones()
	{
		$query = array('query' => array('activa_legajo' => 1));
		$datos = toba::servicio_web_rest('catalogoinstituciones')->guzzle()->get('instituciones', $query)->getBody()->getContents();
		return $datos;
	}
	
	function ordenar_historia()
	{
		$hist = $this->s__historia;		
		$rs_columna = array();
		foreach ($hist as $registro) {
			/*
			 * Hay un error cuando se quiere pasar a string la fecha y esta posee /
			 * algunos valores no los interpreta
			 */
			$t1 = strtotime($registro['fecha']);		
			if ($t1 === FALSE) {
			  $t1 = strtotime(str_replace('/', '-', $registro['fecha']));
			}
			$rs_columna[] = $t1;
		}
		array_multisort($rs_columna, SORT_ASC, SORT_NUMERIC, $hist);
		$this->s__historia = $hist;
	}
	
}
?>