# Bela Stock — Loja

Repositório exclusivo da loja, independente de Super Amplitude.

## Implantação

A implantação via GitHub Actions exigirá configuração explícita de `DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_SSH_KEY`, `DEPLOY_PATH` e `DEPLOY_KNOWN_HOSTS` no repositório `loja`.

Não reutilizar credenciais, runners, diretórios ou workflows de Super Amplitude.

Este repositório ainda não contém o código do site. Nenhuma implantação de produção deve ser executada antes de confirmar o destino e realizar backup.