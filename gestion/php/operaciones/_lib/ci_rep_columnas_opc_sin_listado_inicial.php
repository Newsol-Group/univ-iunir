<?php 
/**
 * 
 * Para implementar un reporte con columnas optativas (dinámicas):
 *  + Hacer extender a la clase del CI de ci_reporte_columnas_opc o 
 *    ci_rep_columnas_opc_sin_listado_inicial
 *  + Implementar la función get_opciones_columnas_optativas() como se indica en
 *    la documentación y en el ejemplo.
 *  + Agregar una columna al filtro, como última fila, 
 *		- ID: columnas_opc
 *		- Expresión: es indistinto, no se usará. Dejar valor por defecto (columnas_opc)
 *		- Etiqueta: Columnas opcionales
 *		- Visible inicialmente
 *		- Tipo de columna: Opciones. Marcar opciones múltiples.
 *		- EF: multicheck. 
 *			º Aplicar "Todos / Ninguno"
 *			º Opcionalmente agrupar opciones por columnas (ej: 5 elementos por fila)
 *			º Origen de la carga: método de consulta PHP
 *			º Ubicación del método: CI Controlador
 *			º Nombre del método: get_opciones_columnas_optativas
 *			º Columna clave: valor
 *			º Columna descripción: descr
 *  + Verificar que las siguientes funciones no estén redefinidas en la subclase,
 *    en cuyo caso debería trasladarse la lógica puntual agregada en esta clase:
 *		- evt__filtro__filtrar
 *		- evt__filtro__cancelar
 *		- conf__cuadro
 *
 */
abstract class ci_rep_columnas_opc_sin_listado_inicial extends ci_rep_sin_listado_inicial
{
	const ancho_max = 1200; // Ancho máximo que admite una pantalla que se ensancha dinámicamente
	const ancho_x_columna = 50; // Píxeles de ensanchado de pantalla por columna opcional
	
	protected $s__columnas = array();
	protected $s__ancho_inicial;
	
	/**
	 * Retornar un arreglo en formato recordset, como muestra el ejemplo, donde
	 * el campo 'valor' es el ID de columna del cuadro, y 'descr' la descripción
	 *
	 * Ej:
	 * return array(
			array('valor' => 'cant_alumnos', 'descr' => 'Cant. alumnos'),
			array('valor' => 'docentes', 'descr' => 'Docentes'),
		);
	 */
	abstract function get_opciones_columnas_optativas();
	
	function conf()
	{
		parent::conf();
		if (empty($this->s__ancho_inicial)) {
			$this->s__ancho_inicial = $this->pantalla('pant_inicial')->get_ancho();
		}
		$cant_columnas = guarani_arreglos::count($this->s__columnas);
		$ancho = $this->s__ancho_inicial + ($cant_columnas * self::ancho_x_columna);
		if ($ancho > self::ancho_max) {
			$ancho = self::ancho_max;
		}
		$this->pantalla('pant_inicial')->set_ancho($ancho);
	}

	//--------------------------------------------------------------------------
	//---- filtro --------------------------------------------------------------
	//--------------------------------------------------------------------------

	function evt__filtro__filtrar($datos)
	{
		//---- Manejo de columnas optativas ------------------------------------
		if (isset($datos)) {
			if (isset($datos['columnas_opc'])) {
				$this->s__columnas = $datos['columnas_opc']['valor'];
				$this->dep('filtro')->columna('columnas_opc')->resetear_estado();
			} else {
				$this->s__columnas = array();
			}
		}
		parent::evt__filtro__filtrar($datos);
	}
	
	function evt__filtro__cancelar() 
	{
		parent::evt__filtro__cancelar();
		$this->s__columnas = array();
    }
	
	//--------------------------------------------------------------------------
	//---- cuadro --------------------------------------------------------------
	//--------------------------------------------------------------------------

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		parent::conf__cuadro($cuadro);
		$columnas_optativas = aplanar_matriz($this->get_opciones_columnas_optativas(), 'valor');
		$columnas_eliminar = array_diff($columnas_optativas, $this->s__columnas);
		$cuadro->eliminar_columnas($columnas_eliminar);
	}
	
}
?>
