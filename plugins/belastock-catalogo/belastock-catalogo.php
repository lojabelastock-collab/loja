<?php
/**
 * Plugin Name: Bela Stock - Catalogo Oficial
 * Description: Sincroniza taxonomias de filtros conforme a estrutura documentada no Google Drive.
 * Version: 0.1.0
 */
defined('ABSPATH') || exit;
final class BelaStock_Catalogo {
 private static function grupos(): array {
  return [
   'publico'=>['Masculino','Feminino','Unissex','Infantil'],
   'modelo'=>['Manga Curta','Manga Longa','Regata','Oversized','Baby Look','Cropped','Polo','Gola V'],
   'tema'=>['Frases e Motivacionais','Humor e Memes','Anime e Geek','Series e Filmes','Musica e Bandas','Games','Pets e Animais','Profissoes','Esportes e Times','Nostalgia e Retro','Religiosos e Gospel','Regionalismo','Florais e Minimalistas','Arte e Ilustracoes','Datas Comemorativas','Personalizadas','Basicas e Lisas','Outros Temas']
  ];
 }
 static function init():void {
  add_action('init',[__CLASS__,'register']);
  add_action('admin_menu',[__CLASS__,'menu']);
  add_action('admin_post_belastock_sync_taxonomy',[__CLASS__,'sync']);
 }
 static function register(): void {
  foreach(self::grupos() as $slug=>$terms) {
   register_taxonomy('bs_'.$slug,['product'],[
    'label'=>'Bela Stock - '.ucfirst($slug),
    'public'=>true,'show_ui'=>true,'show_admin_column'=>true,'show_in_rest'=>true,
    'hierarchical'=>false,'rewrite'=>['slug'=>'bs-'.$slug]
   ]);
  }
 }
 static function menu():void {
  add_submenu_page('tools.php','Categorias Bela Stock','Categorias Bela Stock','manage_woocommerce','belastock-catalogo',[__CLASS__,'page']);
 }
 static function page():void {
  if(!current_user_can('manage_woocommerce'))return;
  echo '<div class="wrap"><h1>Bela Stock — Catalogo Oficial</h1><p>Filtros de publico, modelo e tema reproduzem as pastas do Google Drive. A sincronizacao apenas cria termos ausentes e nao exclui categorias, produtos ou imagens.</p>';
  if(isset($_GET['bs_synced'])) echo '<div class="notice notice-success"><p>Termos sincronizados.</p></div>';
  echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
  echo '<input type="hidden" name="action" value="belastock_sync_taxonomy">';
  wp_nonce_field('belastock_sync_taxonomy');
  submit_button('Criar apenas termos ausentes');
  echo '</form></div>';
 }
 static function sync():void {
  if(!current_user_can('manage_woocommerce')) wp_die('Permissao insuficiente');
  check_admin_referer('belastock_sync_taxonomy');
  foreach(self::grupos() as $slug=>$terms) {
   $taxonomy='bs_'.$slug;
   foreach($terms as $term) {
    if(!term_exists($term,$taxonomy)) wp_insert_term($term,$taxonomy);
   }
  }
  wp_safe_redirect(add_query_arg('bs_synced','1',admin_url('tools.php?page=belastock-catalogo')));
  exit;
 }
}
BelaStock_Catalogo::init();
