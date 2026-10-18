<?php
/**
 * Plugin Name: Bela Stock - Gestão Inteligente
 * Version: 0.1.0
 * Description: Assistente local de gestão sem API paga ou transmissão de dados externos.
 */
defined('ABSPATH') || exit;
final class BS_Gestao_IA {
 static function boot():void {
  add_action('admin_menu',[__CLASS__,'menu']);
  add_action('admin_post_bs_ai_apply_language',[__CLASS__,'language']);
 }
 static function menu():void {add_menu_page('Bela Stock IA','Bela Stock IA','manage_woocommerce','bs-gestao-ia',[__CLASS__,'page'],'dashicons-superhero',32);}
 static function stats():array {
  $statuses=['publish','draft','pending'];
  $total=[];
  foreach($statuses as $status){$c=wp_count_posts('product');$total[$status]=(int)($c->$status??0);}
  $missing=0;$withoutPrice=0;$products=function_exists('wc_get_products')?wc_get_products(['limit'=>200,'status'=>'publish']):[];
  foreach($products as $p){if(!$p->get_image_id())$missing++;if($p->get_price()==='')$withoutPrice++;}
  return ['products'=>$total,'sample_size'=>count($products),'missing_images'=>$missing,'missing_price'=>$withoutPrice];
 }
 static function page():void {
  if(!current_user_can('manage_woocommerce'))wp_die('Acesso negado');
  $stats=self::stats();
  echo '<div class="wrap"><h1>Bela Stock | Assistente de Gestão</h1><p>Diagnóstico inteligente local. Nenhum dado enviado a provedores de IA externos.</p>';
  echo '<div class="notice notice-info inline"><p>O assistente identifica prioridades operacionais, sem inventar informações ou alterar produtos automaticamente.</p></div>';
  echo '<h2>Resumo de produtos</h2><ul>';
  foreach($stats['products'] as $key=>$value)echo '<li>'.esc_html($key).': '.esc_html((string)$value).'</li>';
  echo '</ul><h2>Prioridades da operação</h2>';
  if($stats['missing_images'])echo '<p>Produtos publicados sem imagem na amostra: '.esc_html((string)$stats['missing_images']).'. Prioridade: associar imagens reais.</p>';
  if($stats['missing_price'])echo '<p>Produtos sem preço na amostra: '.esc_html((string)$stats['missing_price']).'. Não criar preços automáticos.</p>';
  if(!$stats['missing_images']&&!$stats['missing_price'])echo '<p>Nenhuma pendência detectada na amostra de '.esc_html((string)$stats['sample_size']).' produtos.</p>';
  echo '<h2>Idioma da loja</h2><p>Português do Brasil para a interface WordPress. Strings de plugins dependem dos arquivos de tradução instalados.</p>';
  echo '<form action="'.esc_url(admin_url('admin-post.php')).'" method="post">';
  echo '<input type="hidden" name="action" value="bs_ai_apply_language">';
  wp_nonce_field('bs_ai_apply_language');submit_button('Definir idioma pt_BR');echo '</form></div>';
 }
 static function language():void {
  if(!current_user_can('manage_options'))wp_die('Acesso negado');
  check_admin_referer('bs_ai_apply_language');
  update_option('WPLANG','pt_BR');
  wp_safe_redirect(admin_url('admin.php?page=bs-gestao-ia'));exit;
 }
}
BS_Gestao_IA::boot();
