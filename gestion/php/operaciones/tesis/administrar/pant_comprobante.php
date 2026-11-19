<?php
/**
 * 
 *
 * @package 	Tesis
 * @subpackage 	
 * 
 */
class pant_comprobante extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		$datos = $this->controlador()->get_datos_imp();
		$source = toba_recurso::url_proyecto().'/img/logo.png';
		
		if (isset($datos['datos'])) {

			echo "
					<table border='1' class='ei-base ei-form-base'>
						<tbody>
						<tr>
								<td align='center' style='padding: 0pt;'>
									".toba_recurso::imagen($source)."
								</td>
						</tr>
							<tr>
								<td style='padding: 0pt;'>
				";

			foreach ($datos['datos'] as $key => $dato) {
				if (isset($dato['etiq']) && $dato['valor']) {
					echo "
										<div style='' class='ei-cuerpo ei-form-cuerpo'>
											<div style='' class='ei-form-fila'>
												<label class='ei-form-etiq-oblig' style=''>
													{$dato['etiq']} :
												</label>
											<div style='margin-left: 150px;'>
												<div class='ef-fijo'>
													{$dato['valor']}
												</div>
											</div>
										</div>
						";
				} else {
					if ($key == 'tabla') {
						echo '<table border="0">';
						foreach ($dato as $orden => $fila) {
							$class = ($orden % 2) ? 'ei-cuadro-celda-impar' : 'ei-cuadro-celda-par' ;
							echo "<tr class='$class'>";
							foreach ($dato[0] as $columna) {
								if ($orden == 0) {
									echo "<th class='ei-cuadro-col-tit'>{$columna['nombre']}</th>";
								} else {
									$valor = $fila[$columna['clave']];
									$style = '';
									if (isset($columna['style'])) {
										$estilos = '';
										foreach ($columna['style'] as $estilo => $val) {
											$estilos .= $estilo.': '.$val.';';
										}
										$style = " style='$estilos'";
									}
									echo "<td$style>";
									if (is_array($valor)) {
										$valor = implode('<br>', aplanar_matriz($valor, 'nombre'));
									}
									if (empty($valor)) {
										$valor = '&nbsp';
									}
									echo $valor;
									echo "</td>";
								}
							}
							echo '</tr>';
						}
						echo '</table>';
					}
				}
			}

			echo "
								</td>
							</tr>
						</tbody>
					</table>
				";
		}
	}
}
?>