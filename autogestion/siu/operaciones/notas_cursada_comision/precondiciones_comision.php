<?php
namespace siu\operaciones\notas_cursada_comision;

use siu\errores\error_guarani;
/**
 * Esta clase se utiliza para analizar las diferentes condiciones que condicionan
 * el comportamiento de la interfaz de carga de notas
 *
 * @author andres
 */
class precondiciones_comision
{
	protected $cabecera;
	protected $filas;

	/**
	 * @param array $cabecera 
	 * @param array $filas
	 */
	function __construct(/* array */ $cabecera, /* array */ $filas)
	{
		$this->cabecera = $cabecera;
		$this->filas = $filas;
	}

	function tiene_instancia_regular()
	{
		return !empty($this->cabecera['instancia_regularidad']);
	}

	function tiene_instancia_promocion()
	{
		return !empty($this->cabecera['instancia_promocion']);
	}

	function get_escala_regular()
	{
		return $this->cabecera['escala_nota_regularidad'];
	}

	function get_escala_promocion()
	{
		return $this->cabecera['escala_nota_promocion'];
	}

	/**
	 * @return bool
	 */
	function tiene_solo_instancia_regular()
	{
		return $this->tiene_instancia_regular() && !$this->tiene_instancia_promocion();
	}

	/**
	 * @return bool
	 */
	function tiene_solo_instancia_promocion()
	{
		return $this->tiene_instancia_promocion() && !$this->tiene_instancia_regular();
	}

	function tiene_dos_instancias()
	{
		return !$this->tiene_solo_instancia_promocion() && !$this->tiene_solo_instancia_regular();
	}

	/**
	 * Si las dos instancias a las que pertenece tienen la misma escala. 
	 * 
	 * Antes de usar este método hay que usar solo_instancia_regular o solo_instancia_promocion
	 * para averiguar si tiene dos instancias
	 * 
	 * @return bool 
	 */
	function tiene_instancias_con_misma_escala()
	{
		return $this->get_escala_regular() === $this->get_escala_promocion();
	}

	function solo_puede_promocionar($id_alumno)
	{
		return $this->tiene_solo_instancia_regular() || ($this->puede_promocionar($id_alumno) && !$this->puede_regularizar($id_alumno));
	}

	function solo_puede_regularizar($id_alumno)
	{
		return $this->tiene_solo_instancia_regular() || (!$this->puede_promocionar($id_alumno) && $this->puede_regularizar($id_alumno));
	}

	/**
	 * @return bool
	 */
	function puede_promocionar($id_alumno)
	{
		$fila = $this->get_fila_alumno($id_alumno);
		return $fila['instancia_promocion'] == 1;
	}

	/**
	 * @return bool
	 */
	function puede_regularizar($id_alumno)
	{
		$fila = $this->get_fila_alumno($id_alumno);
		return $fila['instancia_cursada'] == 1;
	}

	/**
	 * Devuelve true si el alumno tiene los mismos valores en las dos instancias
	 * 
	 * @param $id_alumno
	 * @return bool 
	 */
	function tiene_mismos_valores_en_instancias($id_alumno)
	{
		$fila = $this->get_fila_alumno($id_alumno);

		$fechas_iguales = $fila['fecha_regular'] === $fila['fecha_promocion'];
		$notas_iguales = $fila['nota_cursada'] === $fila['nota_promocion'];
		$resultados_iguales = $fila['resultado_cursada'] === $fila['resultado_promocion'];
		$observaciones_iguales = $fila['observacion_cursada'] === $fila['observacion_promocion'];

		return $fechas_iguales && $notas_iguales && $resultados_iguales && $observaciones_iguales;
	}

	protected function get_fila_alumno($id_alumno)
	{
		if (!isset($this->filas[$id_alumno])) {
			throw new error_guarani("El alumno '$id_alumno' no está en la comisión '{$this->cabecera['comision']}'");
		}
		return $this->filas[$id_alumno];
	}

}