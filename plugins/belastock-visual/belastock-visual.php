<?php
/**
 * Plugin Name: Bela Stock - Implantacao Visual
 * Description: Integra a home ao template existente e sincroniza filtros oficiais sem apagar dados.
 * Version: 0.1.0
 */
defined('ABSPATH') || exit;
final class BelaStock_Visual {
 const FLAG='bs_visual_initialized';
 static function init():void {
  add_action('admin_menu',[__CLASS__,'menu']);
  add_action('admin_post_bs_apply_home',[__CLASS__,'apply']);
 }
 static function menu():void {
  add_submenu_page('tools.php','Bela Stock - Publicacao','Bela Stock - Publicacao','manage_options','bs-visual',[__CLASS__,'page']);
 }
 static function page():void {
  if(!current_user_can('manage_options'))return;
  echo '<div class="wrap"><h1>Bela Stock — Página inicial</h1><p>Utiliza o tema atualmente instalado. Não substitui cabeçalho, rodapé nem Elementor.</p>';
  echo '<p>O modelo oficial pode ser aplicado por ação administrativa com backup da página anterior.</p>';
  echo '<form action="'.esc_url(admin_url('admin-post.php')).'" method="post">';
  wp_nonce_field('bs_apply_home');
  echo '<input type="hidden" name="action" value="bs_apply_home">';
  submit_button('Aplicar página inicial Bela Stock');
  echo '</form></div>';
 }
 static function apply():void {
  if(!current_user_can('manage_options'))wp_die('Sem permissão');
  check_admin_referer('bs_apply_home');
  $home=(int)get_option('page_on_front');
  if(!$home || get_post_status($home)!=='publish') {
   $found=get_page_by_path('inicio-bela-stock');
   if($found) $home=(int)$found->ID;
   else $home=wp_insert_post(['post_title'=>'Início Bela Stock','post_name'=>'inicio-bela-stock','post_type'=>'page','post_status'=>'publish','post_content'=>'[belastock_home]'],true);
   if(is_wp_error($home))wp_die(esc_html($home->get_error_message()));
  } else {
   $post=get_post($home);
   if($post && strpos($post->post_content,'[belastock_home]')===false) {
    update_post_meta($home,'_bs_previous_content_backup_'.gmdate('YmdHis'),$post->post_content);
    wp_update_post(['ID'=>$home,'post_content'=>$post->post_content."\n[belastock_home]"]);
   }
  }
  update_option('show_on_front','page');
  update_option('page_on_front',(int)$home);
  if(!get_page_by_path('mockup-studio')) {
   wp_insert_post(['post_title'=>'Mockup Studio','post_name'=>'mockup-studio','post_type'=>'page','post_status'=>'publish','post_content'=>'[belastock_mockup]']);
  }
  wp_safe_redirect(admin_url('tools.php?page=bs-visual&applied=1'));
  exit;
 }
}
BelaStock_Visual::init();
