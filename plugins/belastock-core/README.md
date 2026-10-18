# Bela Stock Core

Modulo administrativo proprio para WordPress + WooCommerce.

## Escopo inicial
- Painel administrativo privado (apenas `manage_options`)
- Links de gestao de produtos, pedidos e relatorios WooCommerce
- Dados de contato e mensagem de boas-vindas, salvos em opcoes WordPress
- Sem modificacoes no tema ou nas tabelas existentes

## Implantacao
O workflow `deploy-core.yml` faz backup do modulo anterior e atualiza **somente**
`wp-content/plugins/belastock-core`. A ativacao do plugin permanece separada.
Nao modifica os demais sites da VPS.
