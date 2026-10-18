<?php
/**
 * Plugin Name: Bela Stock Core
 * Description: Painel modular e configuracoes administrativas da Bela Stock.
 * Version: 0.1.0
 * Requires PHP: 8.0
 * Text Domain: belastock-core
 */
if (!defined('ABSPATH')) { exit; }

final class BelaStock_Core {
    const OPTION = 'belastock_core_options';
    public static function init(): void {
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('admin_notices', [__CLASS__, 'woocommerce_notice']);
    }
    public static function menu(): void {
        add_menu_page('Bela Stock', 'Bela Stock', 'manage_options', 'belastock-dashboard', [__CLASS__, 'dashboard'], 'dashicons-store', 30);
        add_submenu_page('belastock-dashboard', 'Configuracoes', 'Configuracoes', 'manage_options', 'belastock-config', [__CLASS__, 'settings']);
    }
    public static function register_settings(): void {
        register_setting('belastock_core', self::OPTION, ['type'=>'array', 'sanitize_callback'=>[__CLASS__,'sanitize'], 'default'=>[]]);
    }
    public static function sanitize($value): array {
        if (!is_array($value)) { return []; }
        return [
            'atendimento_email' => sanitize_email($value['atendimento_email'] ?? ''),
            'atendimento_telefone' => sanitize_text_field($value['atendimento_telefone'] ?? ''),
            'mensagem_boas_vindas' => sanitize_textarea_field($value['mensagem_boas_vindas'] ?? '')
        ];
    }
    public static function dashboard(): void {
        if (!current_user_can('manage_options')) { wp_die(esc_html__('Sem permissao.', 'belastock-core')); }
        echo '<div class="wrap"><h1>Bela Stock — Gestao</h1><p>Painel central de operacoes da loja.</p>';
        $wc = class_exists('WooCommerce');
        echo '<p><strong>WooCommerce:</strong> ' . esc_html($wc ? 'Disponivel' : 'Nao encontrado') . '</p>';
        if ($wc) {
            $items = [
                ['Produtos', 'edit.php?post_type=product'],
                ['Pedidos', 'admin.php?page=wc-orders'],
                ['Cupons', 'edit.php?post_type=shop_coupon'],
                ['Relatorios', 'admin.php?page=wc-admin']
            ];
            echo '<ul>';
            foreach ($items as $item) {
                echo '<li><a href="' . esc_url(admin_url($item[1])) . '">' . esc_html($item[0]) . '</a></li>';
            }
            echo '</ul>';
        }
        echo '<p><a class="button button-primary" href="' . esc_url(admin_url('admin.php?page=belastock-config')) . '">Configurar atendimento</a></p></div>';
    }
    public static function settings(): void {
        if (!current_user_can('manage_options')) { wp_die(esc_html__('Sem permissao.', 'belastock-core')); }
        $opts = get_option(self::OPTION, []);
        if (!is_array($opts)) { $opts = []; }
        echo '<div class="wrap"><h1>Bela Stock — Configuracoes</h1><form method="post" action="options.php">';
        settings_fields('belastock_core');
        echo '<table class="form-table"><tbody>';
        $fields = [
            ['atendimento_email','E-mail de atendimento','email'],
            ['atendimento_telefone','Telefone de atendimento','text']
        ];
        foreach ($fields as $field) {
            echo '<tr><th><label for="' . esc_attr($field[0]) . '">' . esc_html($field[1]) . '</label></th><td><input class="regular-text" id="' . esc_attr($field[0]) . '" name="' . esc_attr(self::OPTION . '[' . $field[0] . ']') . '" type="' . esc_attr($field[2]) . '" value="' . esc_attr($opts[$field[0]] ?? '') . '"></td></tr>';
        }
        echo '<tr><th><label for="belastock_welcome">Mensagem de boas-vindas</label></th><td><textarea class="large-text" rows="4" id="belastock_welcome" name="' . esc_attr(self::OPTION . '[mensagem_boas_vindas]') . '">' . esc_textarea($opts['mensagem_boas_vindas'] ?? '') . '</textarea></td></tr>';
        echo '</tbody></table>';
        submit_button('Salvar configuracoes');
        echo '</form></div>';
    }
    public static function woocommerce_notice(): void {
        if (!current_user_can('manage_options') || !isset($_GET['page']) || strpos(sanitize_text_field(wp_unslash($_GET['page'])), 'belastock-') !== 0) { return; }
        if (!class_exists('WooCommerce')) {
            echo '<div class="notice notice-warning"><p>Bela Stock: WooCommerce nao esta ativo. Recursos de vendas ficarao indisponiveis.</p></div>';
        }
    }
}
BelaStock_Core::init();
