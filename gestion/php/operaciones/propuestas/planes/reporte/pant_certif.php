<?php 
class pant_certif extends guarani_pers_ei_pantalla
{
	function generar_layout()
	{
		echo "<div style='float:left; width: 30%; border: 1px solid black; overflow: auto'>";
/*				<div id='div_sel_certif' style='width:100%;overflow:hidden'>
					<div style=' padding: 5px;width:200%'>
						<div style='float:left; width:48%'>";
							$this->dep('cuadro_sel_certif')->generar_html();
		echo "			</div>";*/
		if($this->existe_dependencia('cuadro_sel_certif')) {
//		echo '			<div style="float:right;width:48%">';
							$this->dep('cuadro_sel_certif')->generar_html();
/*		echo "			</div>
					</div>
				</div>
				<div style='clear:both;width:100%'>
					<div id='div_sel_certif_show_l' style='float:left;display:none;cursor:pointer' onclick='document.getElementById(\"div_sel_certif\").scrollLeft = 0; this.style.display = \"none\"; document.getElementById(\"div_sel_certif_show_r\").style.display = \"block\"'>
						<img src='".toba_recurso::imagen_toba('volver.png')."'/>
					</div>
					<div id='div_sel_certif_show_r' style='float:right;cursor:pointer' onclick='document.getElementById(\"div_sel_certif\").scrollLeft = document.getElementById(\"div_sel_certif\").scrollWidth; this.style.display = \"none\"; document.getElementById(\"div_sel_certif_show_l\").style.display = \"block\"'>
						<img src='".toba_recurso::imagen_toba('instanciar.png')."'/>
					</div>
				</div>";
		} else {
		echo '	</div>';*/
		}
		echo '</div>';
		if($this->existe_dependencia('arbol_certif')) {
		echo '<div style="float:right;padding-left: 5px; width: 65%">';
			$this->dep('arbol_certif')->generar_html();
		echo "</div>";
		}
		if($this->existe_dependencia('formulario_certif')) {
		echo "<div style='clear:both'>";
			$this->dep('formulario_certif')->generar_html();
		echo "</div>";
		}		
	}
	
}

?>