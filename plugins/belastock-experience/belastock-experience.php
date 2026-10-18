<?php
/**
 * Plugin Name: Bela Stock Experience
 * Description: Pagina inicial integrada e Mockup Studio usando midias reais da loja.
 * Version: 0.1.0
 * Requires PHP: 8.0
 */
defined('ABSPATH') || exit;
final class BelaStock_Experience {
 const VERSION='0.1.0';
 static function init(): void {
  add_action('init',[__CLASS__,'shortcodes']);
  add_action('wp_enqueue_scripts',[__CLASS__,'assets']);
  add_action('admin_menu',[__CLASS__,'admin']);
  add_action('wp_ajax_belastock_save_mockup',[__CLASS__,'save']);
 }
 static function shortcodes(): void {
  add_shortcode('belastock_home',[__CLASS__,'home']);
  add_shortcode('belastock_mockup',[__CLASS__,'studio']);
 }
 static function assets(): void {
  if (!is_singular()) return;
  global $post;
  if (!$post || (!has_shortcode($post->post_content,'belastock_home') && !has_shortcode($post->post_content,'belastock_mockup'))) return;
  wp_enqueue_style('belastock-experience',plugins_url('assets/style.css',__FILE__),[],self::VERSION);
  wp_enqueue_script('belastock-experience',plugins_url('assets/studio.js',__FILE__),[],self::VERSION,true);
  wp_add_inline_script('belastock-experience','window.BelaStockData='.wp_json_encode(['ajax'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('belastock_save_mockup'),'canSave'=>current_user_can('edit_products')]).';','before');
 }
 static function home(): string {
  $logo=get_custom_logo();
  $shop=function_exists('wc_get_page_permalink')?wc_get_page_permalink('shop'):home_url('/loja/');
  $cats=function_exists('get_terms')?get_terms(['taxonomy'=>'product_cat','hide_empty'=>true,'number'=>8]):[];
  $products=function_exists('wc_get_products')?wc_get_products(['limit'=>8,'status'=>'publish','orderby'=>'date','order'=>'DESC']):[];
  ob_start(); ?>
  <section class="bs-home">
   <header class="bs-hero"><div class="bs-hero-copy">
    <?php if($logo) echo wp_kses_post($logo); ?>
    <span class="bs-eyebrow">BELA STOCK</span><h1>Seu estilo. Sua estampa.</h1>
    <p>Camisetas e produtos personalizados com identidade própria.</p>
    <a class="bs-btn" href="<?php echo esc_url($shop); ?>">Explorar produtos</a>
   </div></header>
   <?php if(!is_wp_error($cats)&&$cats): ?><section class="bs-section"><h2>Escolha sua categoria</h2><div class="bs-category-grid">
    <?php foreach($cats as $cat): $thumb=(int)get_term_meta($cat->term_id,'thumbnail_id',true); ?>
     <a class="bs-category" href="<?php echo esc_url(get_term_link($cat)); ?>">
      <?php if($thumb) echo wp_get_attachment_image($thumb,'medium',false,['loading'=>'lazy']); ?>
      <strong><?php echo esc_html($cat->name); ?></strong>
     </a>
    <?php endforeach; ?></div></section><?php endif; ?>
   <?php if($products): ?><section class="bs-section"><h2>Produtos da loja</h2><div class="bs-product-grid">
    <?php foreach($products as $product): ?><a class="bs-product" href="<?php echo esc_url(get_permalink($product->get_id())); ?>">
     <?php echo wp_kses_post($product->get_image('woocommerce_thumbnail')); ?>
     <strong><?php echo esc_html($product->get_name()); ?></strong>
     <span><?php echo wp_kses_post($product->get_price_html()); ?></span>
    </a><?php endforeach; ?></div></section><?php endif; ?>
  </section><?php return (string)ob_get_clean();
 }
 static function studio(): string {
  if(!function_exists('wc_get_products')) return '<p>O editor requer WooCommerce ativo.</p>';
  $products=wc_get_products(['limit'=>100,'status'=>'publish','orderby'=>'title','order'=>'ASC']);
  $items=[];
  foreach($products as $p) {
   $id=$p->get_image_id(); if(!$id) continue;
   $url=wp_get_attachment_image_url($id,'large'); if(!$url) continue;
   $items[]=['id'=>$p->get_id(),'title'=>$p->get_name(),'image'=>$url];
  }
  $stampIds=get_posts(['post_type'=>'attachment','post_status'=>'inherit','post_mime_type'=>'image','posts_per_page'=>100,'orderby'=>'date','order'=>'DESC','fields'=>'ids']);
  $stamps=[];
  foreach($stampIds as $id) {
   $url=wp_get_attachment_image_url($id,'medium_large');
   if($url) $stamps[]=['id'=>$id,'url'=>$url,'name'=>get_the_title($id)];
  }
  ob_start(); ?>
  <div class="bs-studio" data-bs-studio>
   <div class="bs-studio-panel">
    <h2>Mockup Studio</h2><p>Somente imagens reais da biblioteca e produtos cadastrados.</p>
    <label for="bs-item">Produto</label><select id="bs-item" data-bs-product></select>
    <label for="bs-stamp">Estampa</label><select id="bs-stamp" data-bs-stamp></select>
    <label for="bs-scale">Escala da estampa</label><input id="bs-scale" data-bs-scale type="range" min="8" max="46" value="24">
    <label for="bs-top">Posição vertical</label><input id="bs-top" data-bs-top type="range" min="15" max="65" value="30">
    <label><input type="checkbox" data-bs-lock checked> Padrão automático travado</label>
    <button type="button" data-bs-reset>Restaurar padrão</button>
    <button type="button" data-bs-export>Exportar imagem PNG</button>
    <p class="bs-studio-note">Remoção de fundo: arquivos com transparência são preservados. Não há remoção automática por IA habilitada; exige motor próprio e processamento autorizado.</p>
   </div>
   <div class="bs-studio-preview"><canvas data-bs-canvas width="1200" height="1400" aria-label="Prévia do mockup"></canvas><p data-bs-status role="status"></p></div>
  </div>
  <script type="application/json" id="bs-studio-data"><?php echo wp_json_encode(['products'=>$items,'stamps'=>$stamps],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?></script>
  <?php return (string)ob_get_clean();
 }
 static function admin(): void {
  add_submenu_page('tools.php','Bela Stock - Inicio','Bela Stock - Inicio','manage_options','belastock-home-setup',[__CLASS__,'admin_page']);
 }
 static function admin_page(): void {
  if(!current_user_can('manage_options'))return;
  echo '<div class="wrap"><h1>Bela Stock — Página inicial</h1><p>Insira o shortcode <code>[belastock_home]</code> na página inicial existente pelo editor. Não é necessário substituir o tema nem modificar o template.</p><p>Para o editor utilize <code>[belastock_mockup]</code> em uma página própria. O logo é obtido da Identidade do Site no tema atual.</p></div>';
 }
 static function save(): void { wp_send_json_error(['message'=>'Publicação de mockups requer confirmação do produto e da imagem original.'],403); }
}
BelaStock_Experience::init();
