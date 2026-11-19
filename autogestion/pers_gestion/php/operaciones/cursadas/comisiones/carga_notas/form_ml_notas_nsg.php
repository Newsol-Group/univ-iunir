<?php
class form_ml_notas_nsg extends form_ml_notas
{

	/**Newsol - 06/2025 Se agrega el % de asistencia del alumno para ver si queda libre porque no llega al % de asistencia de la comisión
	 * Se redefine el método que genera la lista de EFs de cada fila.
	 * Se muestran u ocultan los EFs de las instancias cursar o promocionar dependiendo de las inscripciones del alumno.
	 * Si ambas instancias son válidas, cada fila se divide en dos.
	 */
	function generar_layout_fila($clave_fila)
	{
		$instancia_cursada = $this->_elemento_formulario['instancia_cursada']->get_estado();
		$instancia_promocion = $this->_elemento_formulario['instancia_promocion']->get_estado();
		$pct_asistencia = $this->_elemento_formulario['pct_asistencia']->get_estado();
		$porc_asistencia = $this->controlador()->get_porcentaje_asistencia();
		
		if (isset($instancia_cursada)) {
			if (isset($pct_asistencia) & ($pct_asistencia < $porc_asistencia)){
				$this->_elemento_formulario['cond_regularidad']->set_estado(1);
				$this->_elemento_formulario['resultado_cursada']->set_estado('U');
				$this->_elemento_formulario['cond_regularidad']->set_solo_lectura(array('cond_regularidad'), false);
				$this->_elemento_formulario['resultado_cursada']->set_solo_lectura(array('resultado_cursada'), false);
				$this->_elemento_formulario['nota_cursada']->set_solo_lectura(array('nota_cursada'), false);
			}
		}

		$extra = '';
		$nueva_fila = '';
		if ($instancia_cursada && $instancia_promocion) {
			$extra = 'rowspan=2';
			$nueva_fila = "</tr><tr>";
		}

		$oculto_cursada = '';
		if (!$instancia_cursada) {
			$oculto_cursada = "style='display:none'";
		}

		$oculto_promocion = '';
		if (!$instancia_promocion) {
			$oculto_promocion = "style='display:none'";
		}

		$i = 0;
		foreach ($this->_lista_ef_post as $ef) {
			if ($i == 0) { // El EF 'alumno' siempre es invisible. No se usa un 'ef_oculto' porque se necesita acceder a su valor desde JS.
				echo "<td $extra style='display:none'>";
			} elseif ($i < 4) { // Datos del alumno: ROWSPAN.
				echo "<td $extra>";
			} elseif ($i == 4) { // El EF 'instancia_cursar' siempre es invisible. No se usa un 'ef_oculto' porque se necesita acceder a su valor desde JS.
				echo "<td style='display:none'>";
			} elseif ($i < 12) { // Instancia cursar: ocultar.
				echo "<td $oculto_cursada>";
			} elseif ($i == 12) { // El EF 'instancia_promocion' siempre es invisible. No se usa un 'ef_oculto' porque se necesita acceder a su valor desde JS.
				echo "<td style='display:none'>";
			} else { // Instancia promocionar: ocultar.
				echo "<td $oculto_promocion>";
			}
			$this->generar_input_ef($ef);
			echo '</td>';
			if ($i == 11) { // Último EF de la instancia cursar: nueva fila.
				echo $nueva_fila;
			}
			if ($i == 15) { // Condición para la instancia promocionar: columna vacía.
				echo "<td $oculto_promocion>&nbsp;</td>";
			}
			$i++;
		}
	}
}
?>