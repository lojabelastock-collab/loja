<?php
// Repara o menu principal e a navegacao das categorias sem alterar produtos.
if(!defined('ABSPATH') || !defined('WP_CLI'))exit(1);
if(!taxonomy_exists('product_cat'))WP_CLI::error('WooCommerce nao disponivel');
$names=['Camisetas','Bonés','Bolsas','Produtos Gráficos'];
$menuName='Bela Stock | Menu principal';
$menu=wp_get_nav_menu_object($menuName);
$menuId=$menu?(int)$menu->term_id:wp_create_nav_menu($menuName);
if(is_wp_error($menuId))WP_CLI::error($menuId->get_error_message());
$items=wp_get_nav_menu_items($menuId)?:[];
foreach($items as $item){
 if($item->type==='custom' && str_contains($item->url,'#'))wp_delete_post($item->ID,true);
}
$seen=[];
foreach(wp_get_nav_menu_items($menuId)?:[] as $item){
 if($item->type==='taxonomy' && $item->object==='product_cat')$seen[(int)$item->object_id]=true;
}
foreach($names as $name){
 $term=get_term_by('name',$name,'product_cat');
 if(!$term){WP_CLI::warning('Categoria ausente: '.$name);continue;}
 if(empty($seen[$term->term_id]))wp_update_nav_menu_item($menuId,0,[
  'menu-item-title'=>$name,'menu-item-object'=>'product_cat',
  'menu-item-object-id'=>$term->term_id,'menu-item-type'=>'taxonomy','menu-item-status'=>'publish'
 ]);
 WP_CLI::log($name.' => '.get_term_link($term));
}
$base=get_term_by('name','Camisetas','product_cat');
if($base){
 $child=get_term_by('name','Quarto Crescente','product_cat');
 if($child && (int)$child->parent===(int)$base->term_id){
  $exists=false;
  foreach(wp_get_nav_menu_items($menuId)?:[] as $item){
   if($item->type==='taxonomy' && (int)$item->object_id===(int)$child->term_id)$exists=true;
  }
  if(!$exists)wp_update_nav_menu_item($menuId,0,[
   'menu-item-title'=>'Quarto Crescente','menu-item-object'=>'product_cat',
   'menu-item-object-id'=>$child->term_id,'menu-item-type'=>'taxonomy',
   'menu-item-status'=>'publish'
  ]);
  WP_CLI::log('Quarto Crescente => '.get_term_link($child));
 }
}
$locations=get_registered_nav_menus();
$chosen=get_theme_mod('nav_menu_locations',[]);
foreach(['primary','main-menu','primary-menu','main_menu','menu-1','header-menu'] as $name){
 if(isset($locations[$name])){$chosen[$name]=$menuId;WP_CLI::log('Menu atribuido: '.$name);}
}
set_theme_mod('nav_menu_locations',$chosen);
flush_rewrite_rules(true);
WP_CLI::success('Links canonicos e regras de URL atualizados. Menu de cabecalho personalizado XStore pode requerer ajuste no construtor.');
