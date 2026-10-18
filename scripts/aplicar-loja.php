<?php
// Executado exclusivamente por WP-CLI no site Bela Stock.
if (!defined('ABSPATH') || !defined('WP_CLI')) exit(1);
if (!function_exists('wc_get_products')) { WP_CLI::error('WooCommerce nao disponivel'); }
$oldFront = (int)get_option('page_on_front');
$oldMode = get_option('show_on_front');
if (!get_option('bs_previous_front_saved')) {
 update_option('bs_previous_front_saved',['page'=>$oldFront,'mode'=>$oldMode],false);
}
$page=get_page_by_path('inicio-bela-stock');
if (!$page) {
 $id=wp_insert_post(['post_title'=>'Bela Stock | Inicio','post_name'=>'inicio-bela-stock','post_type'=>'page','post_status'=>'publish','post_content'=>'[belastock_home]'],true);
 if(is_wp_error($id)) WP_CLI::error($id->get_error_message());
} else {
 $id=(int)$page->ID;
 if(!has_shortcode($page->post_content,'belastock_home')) {
  update_post_meta($id,'_bs_previous_home_content',$page->post_content);
  wp_update_post(['ID'=>$id,'post_content'=>'[belastock_home]']);
 }
}
$studio=get_page_by_path('mockup-studio');
if(!$studio) {
 $sid=wp_insert_post(['post_title'=>'Mockup Studio','post_name'=>'mockup-studio','post_type'=>'page','post_status'=>'publish','post_content'=>'[belastock_mockup]'],true);
 if(is_wp_error($sid)) WP_CLI::error($sid->get_error_message());
}
update_option('show_on_front','page');
update_option('page_on_front',$id);
foreach ([
 'bs_publico'=>['Masculino','Feminino','Unissex','Infantil'],
 'bs_modelo'=>['Manga Curta','Manga Longa','Regata','Oversized','Baby Look','Cropped','Polo','Gola V'],
 'bs_tema'=>['Frases e Motivacionais','Humor e Memes','Anime e Geek','Series e Filmes','Musica e Bandas','Games','Pets e Animais','Profissoes','Esportes e Times','Nostalgia e Retro','Religiosos e Gospel','Regionalismo','Florais e Minimalistas','Arte e Ilustracoes','Datas Comemorativas','Personalizadas','Basicas e Lisas','Outros Temas']
] as $tax=>$names){
 if(!taxonomy_exists($tax)) WP_CLI::error("Taxonomia ausente: $tax");
 foreach($names as $name) if(!term_exists($name,$tax)) {
  $result=wp_insert_term($name,$tax);
  if(is_wp_error($result)) WP_CLI::warning($result->get_error_message());
 }
}
WP_CLI::success('Home e filtros oficiais aplicados; pagina antiga preservada.');