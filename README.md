# Sistemas de Apoio à Decisão (v10)

Sistema criado para ajudar a gerenciar um ambiente Linux online com diversos recursos: APIs dinâmicas, biblioteca, upload via WebSocket (Workerman), terminal, monitoramento e gerenciamento de sistemas.

## Stack

- PHP 8.1+, Laravel 10
- Workerman / WebSocket para transferência de arquivos
- PostgreSQL (configurável em `.env`)

## Instalação

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
```

## Serviços (Workerman / systemctl)

Consulte a documentação na raiz e em `docs/`:

- `COMO_USAR_WORKERMAN.md`
- `docs/COMO_INICIAR_SERVICOS.md`
- `script_worker_systemctl/README_WORKERMAN_SYSTEMCTL.txt`

## Repositório

https://github.com/Bruno-Gabriel-dos-santos/Sistemas_de_apoio_a_decis-o
