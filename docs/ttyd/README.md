# Documentação Completa do Terminal Web (ttyd)

## Índice
1. [Introdução](#introdução)
2. [Instalação](#instalação)
3. [Configuração](#configuração)
4. [Integração com Laravel](#integração-com-laravel)
5. [Scripts e Serviços](#scripts-e-serviços)
6. [Segurança](#segurança)
7. [Troubleshooting](#troubleshooting)
8. [Referências](#referências)

## Introdução

O ttyd é uma solução poderosa que permite compartilhar o terminal através do navegador web. Esta documentação fornece um guia completo para implementação do terminal web em projetos Laravel, incluindo todos os aspectos de instalação, configuração e manutenção.

### Características Principais
- Terminal web completo e interativo
- Suporte a cores e formatação ANSI
- Compatível com comandos interativos (vim, nano, etc)
- Baixa latência
- Suporte a múltiplas sessões
- Segurança configurável

## Instalação

### Pré-requisitos
- Sistema operacional Linux (Ubuntu/Debian recomendado)
- Permissões de sudo
- Git instalado
- Compilador C/C++

### Passo a Passo da Instalação

1. Atualizar o sistema:
```bash
sudo apt-get update
sudo apt-get upgrade -y
```

2. Instalar dependências:
```bash
sudo apt-get install -y build-essential cmake git libjson-c-dev libwebsockets-dev
```

3. Instalar o ttyd:
```bash
sudo apt-get install -y ttyd
```

Alternativamente, para compilar da fonte:
```bash
git clone https://github.com/tsl0922/ttyd.git
cd ttyd && mkdir build && cd build
cmake ..
make && sudo make install
```

## Configuração

### Configuração Básica do Serviço

1. Criar arquivo de serviço systemd:
```bash
sudo nano /etc/systemd/system/ttyd.service
```

2. Conteúdo do arquivo de serviço:
```ini
[Unit]
Description=TTY Web Service
After=network.target

[Service]
Type=simple
User=www-data
Environment=HOME=/home/www-data
WorkingDirectory=/home/www-data
ExecStart=/usr/bin/ttyd --writable -p 7681 -i lo bash
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

3. Configurar permissões:
```bash
sudo mkdir -p /home/www-data
sudo chown www-data:www-data /home/www-data
sudo usermod -s /bin/bash www-data
```

### Opções de Configuração do ttyd

Principais flags do ttyd:
- `--writable`: Permite entrada de texto
- `-p PORT`: Define a porta (padrão: 7681)
- `-i INTERFACE`: Interface de rede
- `-c USER:PASSWORD`: Credenciais de acesso
- `-t fontSize=VALUE`: Tamanho da fonte
- `-t theme={"background":"#000"}`: Tema do terminal

## Integração com Laravel

### Estrutura de Arquivos
```
resources/
└── views/
    └── terminal/
        └── index.blade.php  # View principal do terminal
app/
└── Http/
    └── Controllers/
        └── TerminalController.php  # Controlador do terminal
```

### View do Terminal (index.blade.php)
A view inclui:
- Interface responsiva
- Indicador de status de conexão
- Tratamento de erros
- Painel de ajuda
- Atalhos de teclado

### Controlador (TerminalController.php)
Responsabilidades:
- Renderização da view
- Gerenciamento de sessão
- Controle de acesso
- Logging de atividades

## Scripts e Serviços

### Script de Gerenciamento (ttyd-manager.sh)
```bash
#!/bin/bash

start_ttyd() {
    sudo systemctl start ttyd
    echo "TTY Web Service iniciado"
}

stop_ttyd() {
    sudo systemctl stop ttyd
    echo "TTY Web Service parado"
}

status_ttyd() {
    sudo systemctl status ttyd
}

case "$1" in
    start)  start_ttyd ;;
    stop)   stop_ttyd ;;
    status) status_ttyd ;;
    *)      echo "Uso: $0 {start|stop|status}" ;;
esac
```

## Segurança

### Melhores Práticas
1. Sempre use HTTPS em produção
2. Configure autenticação básica
3. Limite o acesso por IP
4. Use um usuário dedicado
5. Implemente rate limiting
6. Monitore logs de acesso

### Configuração de SSL
```bash
ttyd --ssl --ssl-cert /path/to/cert.pem --ssl-key /path/to/key.pem -p 7681 bash
```

## Troubleshooting

### Problemas Comuns

1. Terminal não carrega:
   - Verificar status do serviço
   - Confirmar porta aberta
   - Checar logs do sistema

2. Sem permissão de escrita:
   - Verificar flag --writable
   - Confirmar permissões do usuário

3. Problemas de conexão:
   - Verificar firewall
   - Confirmar configurações de rede
   - Testar com curl

### Comandos Úteis
```bash
# Verificar status
sudo systemctl status ttyd

# Ver logs
sudo journalctl -u ttyd -f

# Testar conexão
curl -v http://localhost:7681

# Verificar porta
sudo netstat -tulpn | grep 7681
```

## Referências

- [Documentação Oficial do ttyd](https://github.com/tsl0922/ttyd)
- [Libwebsockets Documentation](https://libwebsockets.org/)
- [Laravel Documentation](https://laravel.com/docs)
- [Systemd Service Documentation](https://www.freedesktop.org/software/systemd/man/systemd.service.html) 