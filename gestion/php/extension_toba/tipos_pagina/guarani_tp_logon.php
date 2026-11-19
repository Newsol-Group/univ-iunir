<?php
class guarani_tp_logon extends toba_tp_logon
{
	function pre_contenido()
	{
		if (!toba::memoria()->existe_dato('ag')) {
			/**
			 * Cuando se hace un pedido de login desde Autogestión no se muestra
			 * encabezado ni pie de página
			 */
			if (! comunes::es_navegador_recomendado()) {
				echo "	<script>
							function closeWarning() {
								document.getElementById('warningDiv').style.display = 'none';
							}
						</script>
						<div id='warningDiv'>
							<table width='100%' style='background:#FAFFBD; height:26px; border-top:none;'>
								<tr>
									<td width='16px'><div>".toba_recurso::imagen_toba('warning.gif', true)."</div></td>
									<td><span style='margin:2px; height:26px; vertical-align:super;'>".guarani_mensajes::get('800_navegador_recomendado', null, false)."</span></td>
									<td width='16px'><div style='cursor:pointer;' onclick='javascript:closeWarning();'>".toba_recurso::imagen_toba('finalizar_sesion.gif', true).'</div></td>
								</tr>
							</table>
						</div>
				';
			}
			echo "<div class='login-titulo'>". toba_recurso::imagen_proyecto("logo_medio.png",true);
			echo "<div>versión ".toba::proyecto()->get_version()."</div>";
			echo "</div>";
		} else {
			echo "	<style type='text/css'>
					<!-- 
						body {
							background-color: white !important;
						}
					-->
				</style>";
			if (toba::memoria()->existe_dato('msg_ag')) {
				echo "<div class='mensaje-login-ag-1'>";
				echo toba::memoria()->get_dato('msg_ag');
				echo "</div>";
			}
			
		}
		echo "\n<div align='center' class='cuerpo'>\n";	
	}

	function footer()
	{
		if (!toba::memoria()->existe_dato('ag')) {
			/**
			 * Cuando se hace un pedido de login desde Autogestión no se muestra
			 * encabezado ni pie de página
			 */
			echo "<div class='login-pie'>";
			echo "<div>Desarrollado por <strong><a href='http://www.siu.edu.ar' style='text-decoration: none' target='_blank'>SIU-CIN</a></strong></div>"
			. "<div>1999-".date('Y')."</div>";
			echo '</div>';
		}
	}

	protected function comienzo_cuerpo_basico()
	{
		$tp_basico = new guarani_tp_basico();
		$tp_basico->comienzo_cuerpo_basico();
	}
}
?>