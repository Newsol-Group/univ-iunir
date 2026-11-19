<?php

class guarani_etiquetas_nucleo
{

	protected $concepto;
	protected $tipo; //'C' es campo, 'T' es tabla, 'N' es nombre
	protected $genero;
	protected $etiquetas;
	protected $sin_etiqueta = '...';
	protected $conexion_extra;

	private function __construct($concepto, $tipo='C', $reusar_conexion=true)
	{
		$this->concepto = $concepto;
		$this->tipo = $tipo;
		$this->set_conexion_extra($reusar_conexion);
		$this->cargar_etiquetas($concepto, $tipo);
	}

 	/**
	Setea las etiquetas de una entidad
	*/
	private function cargar_etiquetas($concepto_pedido, $tipo='C')
	{
		if (!$concepto_pedido) {
			$concepto_pedido = '...';
		}
		$concepto_sql = toba::db()->quote($concepto_pedido);
		$tipo_sql = toba::db()->quote($tipo);
		$db = $this->conexion_extra;
		$sql = " SELECT adm_conceptos.nombre as singular,
						adm_conceptos.nombre_plural as plural,
						adm_conceptos.genero
				 FROM   adm_conceptos
				 WHERE  (adm_conceptos.columna 	= $concepto_sql and $tipo_sql = 'C')
					OR  (adm_conceptos.tabla 	= $concepto_sql and $tipo_sql = 'T')
					OR  ((adm_conceptos.nombre 	= $concepto_sql or adm_conceptos.nombre_plural	= $concepto_sql) and $tipo_sql = 'N')
						;";
		$rs = $db->consultar_fila($sql);
		if (!$rs || $rs == array()) {
			if ($tipo == 'T') {
				$etiquetas['singular'] = 'tabla '.$concepto_pedido;
				$etiquetas['plural'] = 'tablas '.$concepto_pedido;
				$etiquetas['genero'] = 'F';
			} elseif ($tipo == 'C') {
				$etiquetas['singular'] = 'campo '.$concepto_pedido;
				$etiquetas['plural'] = 'campos'.$concepto_pedido;
				$etiquetas['genero'] = 'M';
			} else {
				$etiquetas['singular'] = "instancia del tipo '".$concepto_pedido."'";
				$etiquetas['plural'] = "instancias del tipo '".$concepto_pedido."'";
				$etiquetas['genero'] = 'F';
			}
		} else {
			$etiquetas = $rs;
		}
		$this->etiquetas = $etiquetas;
		$this->genero = $etiquetas['genero'];
	}

	/**
	Esto es por si hubo un mensaje de error en la base que está esperando respuesta de esta clase
	*/
	protected function set_conexion_extra($reusar_conexion)
	{
		if (! isset($this->conexion_extra)) {
			$this->conexion_extra = toba_admin_fuentes::instancia()->get_fuente(null, null)->get_db($reusar_conexion);
		}
		return $this->conexion_extra;   
	}      
 
	function get($pedido='', $enfasis=false)
	{
		$ucfirst = (isset($pedido) && ($pedido == ucfirst($pedido)));
		$pedido = strtolower($pedido);
		 
		switch ($pedido) {     
			case 'la':
			case 'lo':
			case 'el':
				$etiqueta = $this->get_articulo().' '.$this->get_singular($enfasis);
				break;
			case 'de la':
			case 'de lo':
			case 'del':
			case 'de_la':
			case 'de_lo':
				$etiqueta = $this->get_contraccion().' '.$this->get_singular($enfasis);
				break;				
			case 'un':
			case 'una':
			case 'uno':
				$etiqueta = $this->get_articulo_indefinido().' '.$this->get_singular($enfasis);
				break;
			case 'algun':
			case 'alguna':
			case 'alguno':
				$etiqueta = $this->get_cuantificante().' '.$this->get_singular($enfasis);
				break;				
			case 'las':
			case 'los':
				$etiqueta = $this->get_articulo(true).' '.$this->get_plural($enfasis);	
				break;
			case 'de las':
			case 'de los':
			case 'de_las':
			case 'de_los':
				$etiqueta = $this->get_contraccion(true).' '.$this->get_plural($enfasis);
				break;										
			case 'unos':
			case 'unas':
				$etiqueta = $this->get_articulo_indefinido(true).' '.$this->get_plural($enfasis);
				break;
			case 'algunas':
			case 'algunos':
				$etiqueta = $this->get_cuantificante(true).' '.$this->get_plural($enfasis);
				break;					
			case 'plural':
				$etiqueta = $this->get_plural($enfasis);
				break;
			case 'singular':
			default:
				$etiqueta = $this->get_singular($enfasis);
		}
		
		if ($ucfirst) {
			$etiqueta = ucfirst($etiqueta);
		}
		
        return $etiqueta;
	}

	function get_enf($pedido='')
	{
		return $this->get($pedido, true);
	} 

	function get_enfasis($pedido='')
	{
		return $this->get_enf($pedido);
	}
	
	function get_etiqueta($pedido='', $enfasis=false)
	{
		return $this->get($pedido, $enfasis);
	}
	
	function get_singular($enfasis=false) 
	{
		$singular = ucfirst($this->etiquetas['singular']);
		if ($enfasis) {
			$singular = $this->poner_enfasis($singular);
		}
		return $singular;
	}
	
	function get_plural($enfasis=false) 
	{
		$plural = ucfirst($this->etiquetas['plural']);
		if ($enfasis) {
			$plural = $this->poner_enfasis($plural);
		}
		return $plural;
	}

	function get_genero()
	{
		return $this->genero;
	}
	
	function es_valida($pedido)
	{
		return ($this->get($pedido) != $this->sin_etiqueta);
	}

	static function get_concepto($nombre, $reusar_conexion=true)
	{
		return new guarani_etiquetas($nombre, 'N', $reusar_conexion);
	}

	static function get_tabla($tabla, $reusar_conexion=true)
	{
		return new guarani_etiquetas($tabla, 'T', $reusar_conexion);
	}

	static function get_campo($campo, $reusar_conexion=true)
	{
		return new guarani_etiquetas($campo, 'C', $reusar_conexion);
	}

	//------------------------------------------------------------------
	//----- Métodos auxiliares para obtener las etiquetas del concepto
	//------------------------------------------------------------------

	private function get_articulo($plural=false)
	{
		switch ($this->genero) {
			case 'M':
				return $plural ? 'los' : 'el';
			case 'F':
				return $plural ? 'las' : 'la';
			case 'N':
				return $plural ? 'los' : 'lo';
		}
		return '';
	}

	function get_contraccion($plural=false)
	{
		switch ($this->genero) {
			case 'M':
				return $plural ? 'de los' : 'del';
			case 'F':
				return $plural ? 'de las' : 'de la';
			case 'N':
				return $plural ? 'de los' : 'de lo';
		}
		return 'del';
	}

	function get_cuantificante($plural=false)
	{
		switch ($this->genero) {
			case 'M':
				return $plural ? 'algunos' : 'alguno';
			case 'F':
				return $plural ? 'algunas' : 'alguna';
			case 'N':
				return $plural ? 'algunos' : 'algún';
		}
		return 'alguno';
	}

	private function get_articulo_indefinido($plural=false)
	{
		switch ($this->genero) {
			case 'N':
			case 'M':
				return $plural ? 'unos' : 'un';
			case 'F':
				return $plural ? 'unas' : 'una';
			}
		return 'un';
	}

	function get_terminacion($terminacion, $plural=false)
	{
		switch ($terminacion) {
			case 'o':
			case 'a':
				switch ($this->genero) {
					case 'F':
						return 'a';
					case 'N':
					case 'M':
					default:
						return 'o';
				}
			case 'on':
			case 'ón':
			case 'ona':
				switch ($this->genero) {
					case 'F':
						return 'ona';
					case 'N':
					case 'M':
					default:
						return 'ón';
				}
			case 'os':
			case 'as':
				switch ($this->genero) {
					case 'F':
						return 'as';
					case 'N':
					case 'M':
					default:
						return 'os';
				}
			case 'ones':
			case 'onas':
				switch ($this->genero) {
					case 'F':
						return 'onas';
					case 'N':
					case 'M':
					default:
						return 'ones';
				}
			default:
				return '';		
		}
	}
	
	private function poner_enfasis($texto) 
	{
		if ($this->tipo == 'T') {
			$texto = '<strong>'.$texto.'</strong>';
		} else {
			$texto = '<em>'.$texto.'</em>';
		}
		return $texto;
	}
}
?>