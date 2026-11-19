<?php
class guarani_menu_1 extends toba_menu_css {
		
	protected $prof=1;
	protected $arbol;
	protected $op_item;
	
	function __construct() {
		parent::__construct();
		$this->op_item = toba::solicitud()->get_id_operacion();
		$this->set_codigos();
	}
	
	function set_codigos() {
		$db = toba_proyecto_db::get_db();
		$proyecto = $db->quote(toba_proyecto::get_id());
		$sql = "SELECT DISTINCT	i.item as item,
				i.parametro_a as codigo
				FROM 	apex_item i
				where	i.proyecto=$proyecto";
		$cod_items = $db->consultar($sql);
		//echo "<pre>",print_r($cod_items,true),print_r($this->items,true),"</pre>";
		foreach($this->items as $k=>$item) {
			foreach($cod_items as $cod_item) {
				if($item['item'] == $cod_item['item']) {
					$this->items[$k]['codigo']=$cod_item['codigo'];
					break;
				}
			}			
		}
	}
	
	function plantilla_css()
	{
		return "guarani_menu_1";
	}
	
	protected function preparar_arbol()
	{
		$path = null;
		for ($i=0;$i<guarani_arreglos::count($this->items);$i++) {
			if ($this->items[ $i ]['es_primer_nivel']) {
				if($this->items[ $i ]['item']==$this->op_item) {
					$path = ' &raquo;';
					break;
				}
				if($tpath = $this->set_path_item($i , ' &raquo; '.$this->items[ $i ]['nombre'].' &raquo;')) {
					$path = $tpath;
					break;
				}
			}
		}
		
		$this->arbol .= toba_js::abrir();
		$this->arbol .= "
				function showMenu(event) {
					var menu = document.getElementById('ventana_menu');
					if (menu.style.visibility == 'visible') {
						menu.style.visibility='hidden'; 
						document.getElementById('menu-h').style.visibility='hidden';
					} else {
						buscar_menu('');
						document.getElementById('buscar_text').value = '';
						cambiar_modo_busqueda(document.getElementById('toogle_modo'), true);
						menu.style.visibility='visible';
						document.getElementById('menu-h').style.visibility='visible';
						var uls = document.getElementById('menu-h').getElementsByTagName('ul');
						for(var i in uls) {
							if(uls[i].parentNode && uls[i].parentNode.parentNode) {
								uls[i].style.marginLeft = uls[i].parentNode.parentNode.clientWidth+'px';
							}
							var w = 0;
							var li = new Array();
							var a = new Array();
							for(var j in uls[i].childNodes) {
								if(uls[i].childNodes[j].tagName && uls[i].childNodes[j].tagName.toLowerCase() == 'li') {
									li[li.length] = uls[i].childNodes[j];  
									for (var k in uls[i].childNodes[j].childNodes) { 
										if(uls[i].childNodes[j].childNodes[k].tagName && uls[i].childNodes[j].childNodes[k].tagName.toLowerCase() == 'a') {
											a[a.length] = uls[i].childNodes[j].childNodes[k];
											if(uls[i].childNodes[j].childNodes[k].scrollWidth > w) {   
												w = uls[i].childNodes[j].childNodes[k].clientWidth;
											}
										}
									} 
								}
							}
							if(w > 200) {
								uls[i].style.width = w+'px';
								for(var i in li) {
									li[i].style.width = w+'px';
								}
							}
						}
					}
					if(event.stopPropagation) {
						event.stopPropagation();
					} else {
						event.cancelBubble = true;
					}
				}
				
				if(document.addEventListener) {
					document.addEventListener('click', chkClick, false);
				} else {
					document.attachEvent('onclick', chkClick);
				}
				
				function chkClick(event) {
					var target = event.target?event.target:event.srcElement;
					if(target.id != 'menu_img' && target.parentNode.id != 'menu_img') {
						var en_menu = false;
						var menu = document.getElementById('ventana_menu');
						//alert(event.target?event.target:event.srcElement); 
						var dvs = menu.getElementsByTagName(target.tagName);
						for(var i=0; i<dvs.length; i++) {
							if(dvs[i] === target) {
								en_menu = true;
								break;
							}
						}
						if(!en_menu) {
							menu.style.visibility='hidden';
							document.getElementById('menu-h').style.visibility='hidden';
						}
					}
				}
				
				function buscar_menu(srchstr) {
					var msch = document.getElementById('menu_buscar');
					if(srchstr == '') {
						msch.style.display='none';
						document.getElementById('menu-h').style.display='block';
					} else {
						var n = true;
						var c = true;
						if(document.getElementById('busqueda_filtro').style.display=='block') {
							n = document.getElementById('busc_nom').checked; 
							c = document.getElementById('busc_cod').checked;
						}
						var elems = msch.getElementsByTagName('div');
						var exp = new RegExp(normalizar_chars(srchstr),'i');
						for(var i=0; i<elems.length; i++) {
							if(	(n && elems[i].lastChild.nodeType == 3 && exp.test(normalizar_chars(elems[i].lastChild.nodeValue))) || 
								(c && hasAttribute(elems[i],'codigo') && exp.test(normalizar_chars(getAttribute(elems[i],'codigo'))))) {
								elems[i].style.display='block';
							} else {
								elems[i].style.display='none';
							}
						}
						if(msch.clientHeight == 0) { 
							document.getElementById('menu-h').style.display='none';
							msch.style.display='block';
						}
					}
				}
				
				function cambiar_modo_busqueda(img, reset) {
					var bf = document.getElementById('busqueda_filtro');
					if(!reset && bf.style.display == 'none') {
						bf.style.display = 'block';
						img.src = '".toba_recurso::imagen_proyecto('sentido_asc_menu.png')."';
					} else {
						bf.style.display = 'none';
						img.src = '".toba_recurso::imagen_proyecto('sentido_des_menu.png')."';
					}
					buscar_menu(document.getElementById('buscar_text').value);
				}
				
				function hasAttribute(elem, att) {
					if(elem.hasAttribute) {
						return elem.hasAttribute(att);
					} else {
						for(var i=0; i<elem.attributes.length; i++) {
							if(elem.attributes[i].name == att) {
								return true;
							}
						}
						return false; 
					}
				}
				
				function getAttribute(elem, att) {
					if(elem.getAttribute) {
						return elem.getAttribute(att);
					} else {
						for(var i=0; i<elem.attributes.length; i++) {
							if(elem.attributes[i].name == att) {
								return elem.attributes[i].nodeValue;
							}
						} 
						return '';
					}
				}
				
				function normalizar_chars(input) {
					return input.replace(/ñ/ig,'n').replace(/á/ig,'a').replace(/é/ig,'e').replace(/í/ig,'i').replace(/ó/ig,'o').replace(/ú/ig,'u').replace(/ü/ig,'u').replace(/^[ ]*|[ ]*$/g,'');
				}
				
				document.getElementById('barra_path_operacion').innerHTML = '".$path."';";
		if (toba::memoria()->get_celda_memoria_actual_id() != 'paralela') {
			$this->arbol .= '
				function on_menu_set_popup_on(e) {
				   	var id = (window.event) ? event.keyCode : e.keyCode;
					if (id == 16) {
						toba.set_menu_popup(true);
					}
				}
				function on_menu_set_popup_off(e) {
				   	var id = (window.event) ? event.keyCode : e.keyCode;
					if (id == 16) {
						toba.set_menu_popup(false);
					}			
				}
				agregarEvento(document, "keyup", on_menu_set_popup_off);
				agregarEvento(document, "keydown", on_menu_set_popup_on);
			';
		}
		$this->arbol .= toba_js::cerrar();
		$this->arbol .= '		
				<style type="text/css">
					ul.horizontal .carpeta {
						background-repeat: no-repeat;
						background-position: center right;
						background-image: url("'.$this->imagen_nodo.'");
					}
				</style>
				<!--[if IE 7]>
				<style type="text/css">
					ul.horizontal ul ul {
						margin-left: 200px;
					}
				</style>							
				<![endif]-->
			';
		$this->arbol .= "<div id='ventana_menu' class='toba_ventana_menu'>";
		$this->arbol .= "\n<ul id='menu-h'  class='horizontal'>\n";		
		//-- Recorro para encontrar la raiz
		$ops = '';
		for ($i=0;$i<guarani_arreglos::count($this->items);$i++) {
			//--- Se recorre el primer nivel
			if ($this->items[ $i ]['es_primer_nivel']) {
				$ops .= $this->get_padres($i);
			}
		}
		$this->arbol .= "</ul>";
		$this->arbol .= "<div class='toba_buscar_menu' id='menu_buscar'>".$ops."</div>";
		$this->arbol .= "<div id='search_menu' class='toba_buscar_barra_menu'>
							<div class='buscar_menu_1'>
								<img id='toogle_modo' src='".toba_recurso::imagen_proyecto('sentido_des_menu.png')."' onClick='cambiar_modo_busqueda(this)'/>
								<span>Buscar: </span>
								<input id='buscar_text' type='text' onKeyup='buscar_menu(this.value)'/> 
								<img src='".toba_recurso::imagen_proyecto('clear_left.png')."' onClick='document.getElementById(\"buscar_text\").value=\"\"; buscar_menu(\"\");'/>
							</div>
							<div id='busqueda_filtro' style='display: none;'>
								<input id='busc_cod' name='busc_cod_nom' type='radio' value='cod' onClick='buscar_menu(document.getElementById(\"buscar_text\").value)' checked='true'/> Código									 
								<input id='busc_nom' name='busc_cod_nom' type='radio' value='nom' onClick='buscar_menu(document.getElementById(\"buscar_text\").value)'/> Nombre
							</div>
						</div>";
		$this->arbol .= "</div>";
		$this->arbol .= toba_js::abrir();
		$this->arbol .= "
			document.getElementById('menu_buscar').style.height = document.getElementById('menu-h').clientHeight+'px';
			";
		$this->arbol .= toba_js::cerrar();	
	}
	
	protected function get_padres($nodo)
	{
		$inden = str_repeat("\t",$this->prof );
		$clase_base = '';//($this->prof == 1) ? 'nivel-0' : '';
		$op = '';
		if (!$this->items[$nodo]['carpeta']) {
			/*$vinculo = toba::vinculador()->get_url($this->items[$nodo]['proyecto'],
															 $this->items[$nodo]['item'], array(),
															 array('validar' => false, 'menu' => true, 'zona' => false));*/
			
			$proyecto = $this->items[$nodo]['proyecto'];
			$item = $this->items[$nodo]['item'];
			if (isset($this->items[$nodo]['js'])) {
				$js = $this->items[$nodo]['js']; 
			} else {
				$js = "return toba.ir_a_operacion(\"$proyecto\", \"$item\", false)";
			}
			$this->arbol .= $inden . "<li><a class='$clase_base' tabindex='32767' href='#' onclick='$js' title='".$this->items[$nodo]['nombre']."'>";
			if (!isset($this->items[$nodo]['js']) && $this->abrir_nueva_ventana) {
				$this->arbol .= '<img title="Abrir la operación en paralelo a la actual" class="menu-link-nueva-ventana" src="'. $this->imagen_nueva_ventana. '" ';
				$this->arbol .= " onclick='return toba.ir_a_operacion(\"$proyecto\", \"$item\", true)' />";
			}											
			$this->arbol .= $this->get_imagen($nodo).$this->items[$nodo]['nombre'];
			$this->arbol .= "</a>";
			$this->arbol .= $inden . "</li>\n";
			$op .= "<div class='toba_elemento_menu' onClick='$js' codigo='".(isset($this->items[$nodo]['codigo'])?$this->items[$nodo]['codigo']:'')."'>";
			$op .= $this->get_imagen($nodo).$this->items[$nodo]['nombre'];
			$op .= "</div>";
			$this->hay_algun_item = true;
		} else {
			//Es carpeta
			$clase_base .= " carpeta";//($this->prof > 1) ? " carpeta" : "";
			$this->arbol .= $inden . "<li><a class='$clase_base'>".$this->get_imagen($nodo). $this->items[$nodo]['nombre'] . "</a>\n";
			$this->arbol .= $inden . "\t<ul>\n";
			$rs = $this->get_hijos ($nodo);
			for ($i=0;$i<guarani_arreglos::count($rs);$i++) {
				$this->prof++;
				$op .= $this->get_padres($rs[ $i ]);
				$this->prof--;
			}
			$this->arbol .= $inden . "\t</ul>\n";
			$this->arbol .= $inden . "</li>\n";
		}
		return $op;
	}
	
	protected function get_imagen($nodo)
	{
		if (isset($this->items[$nodo]['imagen'])) {
			$url_img = toba_recurso::imagen_de_origen($this->items[$nodo]['imagen'], $this->items[$nodo]['imagen_recurso_origen']);
		} elseif($this->items[$nodo]['carpeta']) {
			$url_img = toba_recurso::imagen_proyecto('carpeta.png');
		} else {
			$url_img = toba_recurso::imagen_proyecto('item.png');
		}

		return "<img src='$url_img' border=0 alt='' /> ";
	}
	
	function mostrar()
	{
		$this->preparar_arbol();
		echo $this->arbol;
		if ($this->hay_algun_item) {
			toba_js::cargar_consumos_globales(array('basicos/listmenu'));
			echo toba_js::ejecutar("var horizontals = new simpleMenu('menu-h', 'vertical');");
		}
		$this->menu_enviado = true;
	}
	
	protected function set_path_item($padre, $path) {
		$hijos = $this->get_hijos($padre);
		foreach ($hijos as $i) {
			if($this->items[ $i ]['item']==$this->op_item) {
				return $path;
			}
			if($tpath = $this->set_path_item($i, $path.' '.$this->items[ $i ]['nombre'].' &raquo;')) {
				return $tpath;
			}
		}	
		return null;	
	}
}
?>