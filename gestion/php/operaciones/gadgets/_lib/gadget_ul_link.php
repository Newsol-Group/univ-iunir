<style type="text/css">
#c {
	text-align:left;
	margin-left:-35px;
	background:none
}

.titulo {
	position:relative;
	float:left;
	margin-top:-10px;
}

.lista-link:hover {
	text-decoration:none;
	font-weight:bold;
	color:#669900;
	font-weight:bold
}

.lista-link {
	text-decoration:none;
	font-weight:normal;
	color:#666666;
	font-weight:normal
}
</style>
<?php
class gadget_ul_link extends toba_gadget {

	protected $sql;
	protected $url;
	protected $ul;
	protected $tc=array();
	protected $lp=array();
	protected $proyecto;
	protected $item;	
	protected $clase;
	protected $estilo;	
	protected $icono = '';	
	
	function set_icono($icono) 
	{
		$this->icono = $icono;
	}

	function get_icono() 
	{
		if ($this->icono <> '') {
			return "<span class='titulo'><img src=img/" . $this->icono . "></span>";
		};
		return "";
	}

	function set_text_cols($tc) 
	{
		$this->tc = $tc;
	}

	function set_link_params($lp) 
	{
		$this->lp = $lp;		
	}
	
	function set_sql($sql) 
	{
		$this->sql = $sql;
	}
	
	function set_proyecto($proyecto) 
	{
		$this->proyecto = $proyecto;
	}

	function set_item($item) 
	{
		$this->item = $item;
	}
	
	function set_url($url) 
	{
		$this->url = $url;
	}
	
	function set_ul($ul) {
		$this->ul = $ul;
	}

	function set_clase_ul($clase) {
		$this->clase = $clase;
	}
	
	function set_estilo_ul($estilo) {
		$this->estilo = $estilo;
	}

	
	function generar_html() 
	{
		toba::logger()->seccion('== Inicio de gadget interno '.$this->get_titulo().' ==','toba');
		
		
		
		echo "<div id='gadget-chrome-{$this->get_orden()}' class='gadgets-gadget-chrome'>";
		echo "<div id='gadgets-gadget-title-bar-{$this->get_orden()}' class='gadgets-gadget-title-bar'>";
		echo "{$this->get_icono()}";
		echo "<span style='vertical-align:top' class='gadgets-gadget-title' id='remote_iframe_{$this->get_orden()}_title'>{$this->get_titulo()}</span> | ";
		echo "<span class='gadgets-gadget-title-button-bar' onclick='var c=document.getElementById(\"gadget-content-{$this->get_orden()}\");if(c.style.display==\"none\") {c.style.display=\"block\";this.innerHTML=\"Minimizar\";} else {c.style.display=\"none\";this.innerHTML=\"Maximizar\";}' style='text-decoration: underline; cursor:pointer'>Minimizar</span>";
		if ($this->es_eliminable()) {
		echo "<img style='float:right; margin-right: 5px; margin-left: 5px; margin-top: 1px; margin-bottom: 1px; width: 15px; height: 15px' onClick='this.parentNode.parentNode.parentNode.removeChild(this.parentNode.parentNode)' src='".toba_recurso::imagen_toba('finalizar_sesion.gif', false)."'/>";
		}
		echo "</div>\n";
		echo "<div id='gadget-content-{$this->get_orden()}' class='gadgets-gadget-content'>";
		echo "<div style='width: 250px; padding: 3px;'>";


		echo "<ul".($this->clase?" class='".$this->clase."'":'')." style='".($this->estilo?$this->estilo:'list-style-type: none; text-align: left')."'>";
		if(!isset($this->ul) || !$this->ul) {
			$r = guarani_db::consultar($this->sql, null, false);
			foreach($r as $fila) {
				$text = array();
				$params = array();
				foreach($fila as $k=>$col) {
					if(in_array($k, $this->tc)) {
						$text[] = $col;
					}
					if(in_array($k, $this->lp)) {
						if($this->url) {
							$params[] = urlencode($k).'='.urlencode($col);
						} else {
							$params[$k] = $col;
						}
					}
				}
				echo "<li id=c>";
				if ($this->url || ($this->proyecto && $this->item)) {
					if($this->url) {
						echo "<a href='".$this->url.($params?'?'.implode('&', $params):'')."'>".implode(' ',$text)."</a>";
					} else {
						echo toba::vinculador()->get_url($this->proyecto, $this->item, $params, array('param_html'=>array('texto'=>implode(' ', $text))));
					}
				} else {
					echo implode(' ',$text);
				}
				echo "</li>";
			}
		} else {
			foreach($this->ul as $li) {
				echo "<li id=c>";
				if(is_array($li)) {
					if(isset($li['proyecto']) && isset($li['item'])) {
						echo toba::vinculador()->get_url($li['proyecto'], $li['item'], (isset($li['params'])?$li['params']:null), array('param_html'=>array('texto'=>(isset($li['texto'])?$li['texto']:null))));
					} else {
						echo "<a href='".$li[1]."'>".$li[0]."</a>";
					}
				} else {
					echo $li;
				}
				echo "</li>";
			}
		}
		echo "</ul>";
		echo "</div>\n";
		echo "</div>\n";
		echo "</div>\n";
		toba::logger()->seccion('== Fin de gadget interno '.$this->get_titulo().' ==','toba');
	}
}
