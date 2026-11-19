<?php
class zona_com_cursada extends guarani_zona_pers
{
	protected $set_back = false;
	
	function set_info($info) {
		toba::memoria()->set_dato('zona_com_cursada_info', $info);
	}
	
	function get_info($clave = NULL) {
		return toba::memoria()->get_dato('zona_com_cursada_info');
	}
	
	function set_back($url) {
		$this->set_back = true;
		if(!toba::memoria()->get_dato('zona_com_cursada_back')) {
			$back = array($url);
		} else {
			$back = toba::memoria()->get_dato('zona_com_cursada_back');
			if(in_array($url, $back)) {
				array_splice($back, array_search($url, $back)+1); 
			} else {
				array_push($back, $url);
			}
		}
		toba::memoria()->eliminar_dato('zona_com_cursada_back');
		toba::memoria()->set_dato('zona_com_cursada_back', $back);
	}

	function resetear()
	{
		parent::resetear();
		toba::memoria()->eliminar_dato('zona_com_cursada_back');
		toba::memoria()->eliminar_dato('zona_com_cursada_info');
	}
	
	function generar_html_barra_superior()
	{
		$back = toba::memoria()->get_dato('zona_com_cursada_back');
		if($back) {
			if($this->set_back) {
				array_pop($back);
			}
			echo "<div class='zona-barra-sup' id='zona_{$this->id}'>";
			echo "<div class='zona-items' style='padding-right: 10px'>";
			echo "<a href='".array_pop($back)."'>".toba_recurso::imagen_toba('volver.png',true,null,null,'Volver')."</a>";
			echo "</div>";		
			echo "</div>\n";
		}
	}
}
?>