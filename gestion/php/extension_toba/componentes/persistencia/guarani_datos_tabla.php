<?php
/**
 * Manipulación del componente de persistencia Datos Tabla.
 * @package Componentes
 * @subpackage Persistencia
 */

class guarani_datos_tabla extends toba_datos_tabla 
{
	/**
	 * Se extienden los métodos que validan los topes máximos y mínimos de los datos tabla
	 */
	
	/**
	 * Valida que la cantidad de filas supere el mínimo establecido
	 */
	protected function control_tope_minimo_filas()
	{
		$cant_filas = $this->get_cantidad_filas();
		if ($this->_tope_min_filas != 0 && $cant_filas < $this->_tope_min_filas) {
			$concepto_tabla = guarani_etiquetas::get_tabla($this->get_tabla(), false);
			$genero = $concepto_tabla->get_genero();

			$etiqueta_tabla = $concepto_tabla->get_etiqueta('plural', false);
			$minimo = "$this->_tope_min_filas $etiqueta_tabla";
			if ($this->_tope_min_filas == 1) {
				$minimo = ($genero == persona::sexo_femenino) ? $concepto_tabla->get('una') : $concepto_tabla->get('un');
			}

			$encontrados = guarani_mensajes::get('800_cant_encontrados', array($cant_filas));
			if ($cant_filas == 0) {
				$encontrados = ($genero == persona::sexo_femenino) ? guarani_mensajes::get('800_no_encontradas') : guarani_mensajes::get('800_no_encontrados');
			} elseif ($cant_filas == 1) {
				$encontrados = ($genero == persona::sexo_femenino) ? guarani_mensajes::get('800_una_encontrada') : guarani_mensajes::get('800_uno_encontrado');
			}

			$msj = guarani_mensajes::get('800_topemin_filas_dt', array($minimo, $encontrados));
			throw new toba_error_validacion($msj);
		}
	}

	/**
	 * Valida que la cantidad de filas a crear no supere el maximo establecido
	 */	
	protected function control_tope_maximo_filas($cantidad)
	{
		if ($this->_tope_max_filas != 0 && $cantidad > $this->_tope_max_filas) {
			$concepto_tabla = guarani_etiquetas::get_tabla($this->get_tabla(), false);
			$genero = $concepto_tabla->get_genero();

			$etiqueta_tabla = $concepto_tabla->get_etiqueta('plural', false);
			$maximo = "$this->_tope_max_filas $etiqueta_tabla";
			if ($this->_tope_max_filas == 1) {
				$maximo = ($genero == persona::sexo_femenino) ? $concepto_tabla->get('una') : $concepto_tabla->get('un');
			}

			$msj = guarani_mensajes::get('800_topemax_filas_dt', array($maximo, $cantidad));
			throw new toba_error_validacion($msj);
		}
	}

	/**
	 * Se define una función get_filas que dada una condición permita comparar algún campo por valores nulos (es decir que el 
	 * arreglo asociativo de condiciones permita un componente campo=>valor tal que valor sea igual a null).
	 * @param array 	$condiciones 	arreglo asociativo que representan las condiciones que deben cumplir las filas del datos tabla.
	 * @param boolean 	$usar_cursores 	indica si se deben usar los cursores de la tabla padre en las filas consideradas.
	 * @param char		$campo_nulo		indica el campo de la tabla incluído en las condiciones que es necesario verificar si es nulo.
	 * @return array	filas del datos tabla que verifican las condiciones dadas (incluyendo el campo con valor null).
	 */
	function get_filas_condicion_campo_nulo($condiciones, $usar_cursores=true, $campo_nulo)
	{
		//Se elimina del arreglo de condiciones el campo con valor nulo, para invocar normalmente a get_filas con la condición
		$condiciones_sin_nulo = array_diff($condiciones, array($campo_nulo=>null));
		$coincidencias = parent::get_filas($condiciones_sin_nulo, false, $usar_cursores);
		//Luego de las filas encontradas que coinciden con la condición se retornan sólo aquellas cuyo campo $campo_nulo tiene valor null.
		$filas_condicion = array();
		foreach ($coincidencias as $fila) {
			if (is_null($fila[$campo_nulo])) {
				//Si se verifica que para la fila el campo dado tiene valor nulo, entonces se agrega a las filas coincidentes.
				$filas_condicion[] = $fila;
			}
		}
		return $filas_condicion;
	}
	
	/**
	 * Se define una función get_ids_fila_condicion que dada una condición permita comparar algún campo por valores nulos 
	 * (es decir que el arreglo asociativo de condiciones permita un componente campo=>valor tal que valor sea igual a null).
	 * @param array 	$condiciones 	arreglo asociativo que representan las condiciones que deben cumplir las filas del datos tabla.
	 * @param boolean 	$usar_cursores 	indica si se deben usar los cursores de la tabla padre en las filas consideradas.
	 * @param char		$campo_nulo		indica el campo de la tabla incluído en las condiciones que es necesario verificar si es nulo.
	 * @return array	ids de las filas del datos tabla que verifican las condiciones dadas (incluyendo el campo con valor null).
	 */
	function get_id_fila_condicion_campo_nulo($condiciones, $usar_cursores=true, $campo_nulo)
	{
		//Se elimina del arreglo de condiciones el campo con valor nulo, para invocar normalmente a get_id_fila_condicion con la condición
		$condiciones_sin_nulo = array_diff($condiciones, array($campo_nulo=>null));
		$coincidencias = parent::get_id_fila_condicion($condiciones_sin_nulo, $usar_cursores);
		//Luego de los ids de filas encontradas que coinciden con la condición se retornan sólo aquellos cuyo campo $campo_nulo tiene valor null.
		$ids_filas_condicion = array();
		foreach ($coincidencias as $id_fila) {
			$fila = parent::get_fila($id_fila);
			if (is_null($fila[$campo_nulo])) {
				//Si se verifica que para la fila el campo dado tiene valor nulo, entonces se agrega su id a los ids de filas coincidentes.
				$ids_filas_condicion[] = $id_fila;
			}
		}
		return $ids_filas_condicion;
	}


	/**
	 * Retorna el contenido Original de una fila, a partir de su clave interna
	 * @param mixed $id Id. interno de la fila en memoria
	 * @return array columna => valor. En caso de no existir la fila retorna NULL
	 */

	function get_fila_original($id)
	{
		$id = $this->normalizar_id($id);
		if(isset($this->_cambios[$id]['original'])){
			$temp = $this->_cambios[$id]['original'];
			$temp[apex_datos_clave_fila] = $id;	//incorporo el ID del dbr
			return $temp;
		}else{
			return null;
			//throw new toba_error("Se solicito un registro incorrecto");
		}
	}

	/**
	 * Cantidad de filas Originales que tiene la tabla en memoria
	 * El conjunto de filas utilizado es afectado por la presencia de cursores en las tablas padres
	 * @return integer
	 */
	
	function get_cantidad_filas_originales($usar_cursores=true)
	{
		return guarani_arreglos::count($this->get_filas_originales(null,false,$usar_cursores));
	}

	/**
	 * Retorna el contenido Original de la fila donde se encuentra posicionado el cursor interno
	 * Si la tabla se definio admitiendo a lo sumo un registro, este cursor se posiciona automáticamente en la carga, sino se debe explicitar con el método set_cursor
	 * En caso de que no haya registros retorna NULL
	 */

	function get_original()
	{
		if ($this->get_cantidad_filas_originales() == 0) {
			return null;
		} elseif ($this->hay_cursor()) {
			return $this->get_fila_original($this->get_cursor());
		} else {
			throw new toba_error_def("No hay posicionado un cursor en la tabla, no es posible determinar la fila actual");
		}
	}
    
    /**
     * Retorna si un campo pasado por parametro tiene un cambio
     * @param string $campo es el campo por el que se quiere validar si hay cambios
     * @return boolean
     */
    function existe_cambio_en_campo($campo)
    {
        if (in_array($campo, $this->get_campos_con_cambios())) {
            return true;
        } 
        return false;
    }
    
    /**
     * Retorna los campos que tienen cambio en la fila donde esta posicionado el cursor
     * @return array_keys. Son los nombres de los campos con cambios
     */
    function get_campos_con_cambios()
    {
        if ($this->hay_cursor()) {
            return array_keys($this->get_cambios_fila($this->get_cursor()));
        } else {
            throw new toba_error_def("No hay posicionado un cursor en la tabla, no es posible determinar la fila actual");
        }
        
    }

	/**
	 * Retorna el conjunto de filas Originales que respeta las condiciones dadas
	 * Por defecto la búsqueda es afectada por la presencia de cursores en las tablas padres.
	 * @param array $condiciones Se utiliza este arreglo campo=>valor y se retornan los registros que cumplen (con condicion de igualdad) con estas restricciones. El valor no puede ser NULL porque siempre da falso
	 * @param boolean $usar_id_fila Hace que las claves del array resultante sean las claves internas del datos_tabla. Sino se usa una clave posicional y la clave viaja en la columna apex_datos_clave_fila
	 * @param boolean $usar_cursores Este conjunto de filas es afectado por la presencia de cursores en las tablas padres.
	 * @return array Formato tipo RecordSet
	 */


	function get_filas_originales($condiciones=null, $usar_id_fila=false, $usar_cursores=true)
	{
		$datos = array();
		$a = 0;
		foreach( $this->get_id_fila_condicion($condiciones, $usar_cursores) as $id_fila )
		{
			if (isset($this->_cambios[$id_fila]['original'])) {
				if($usar_id_fila){
					$datos[$id_fila] = $this->_cambios[$id_fila]['original'];
				}else{
					$datos[$a] = $this->_cambios[$id_fila]['original'];
					//esta columna indica cual fue la clave del registro
					$datos[$a][apex_datos_clave_fila] = $id_fila;
				}
			}
			$a++;
		}
		return $datos;
	}

	/**
	 * Marca para eliminar las filas que se corresponden con el o los ids internos recibidos.
	 * @param int/array		$ids		id interno o arreglo de ids internos (cursores) que se marcarán para eliminar.
	 *									Si el valor recibido es nulo, entonces todas las filas se marcarán para eliminar.
	 */
	protected function marcar_eliminacion($ids=null)
	{
		if (! $this->esta_cargada()) return;
		if (! isset($ids)) {
			$ids = $this->get_id_filas();
		} else {
			if (! is_array($ids)) {
				$this->eliminar_fila($ids);
				return;
			}
		}
		foreach ($ids as $id) {
			$this->eliminar_fila($id);
		}
	}
}

?>