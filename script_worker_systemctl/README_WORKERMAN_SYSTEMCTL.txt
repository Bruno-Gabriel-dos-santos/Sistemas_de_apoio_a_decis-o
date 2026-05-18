Script + service Workerman
==========================

Estrutura
---------
Diretório esperado no servidor: /var/www/Sitemas/script_worker_systemctl
Arquivos principais:
  - run-workerman-worker.sh
  - workerman-worker-20001.service
  - workerman-worker-20010.service
  - workerman-worker-20020.service
  - workerman-worker-20040.service
  - (opcional) workerman-worker@.service (template)

Passos de preparação
--------------------
1. Copie o sistema para /var/www/Sitemas (caso ainda não esteja):
     sudo mkdir -p /var/www/Sitemas
     sudo rsync -av /home/bruno/Sistemas_De_Apoio/Sistemas_de_Apoio_10/ /var/www/Sitemas/
     sudo chown -R bruno:bruno /var/www/Sitemas

2. Garanta permissão de execução:
     sudo chmod +x /var/www/Sitemas/script_worker_systemctl/run-workerman-worker.sh

Instalação dos services fixos
-----------------------------
1. Copie cada arquivo .service para o systemd:
     sudo cp /var/www/Sitemas/script_worker_systemctl/workerman-worker-20001.service /etc/systemd/system/
     sudo cp /var/www/Sitemas/script_worker_systemctl/workerman-worker-20010.service /etc/systemd/system/
     sudo cp /var/www/Sitemas/script_worker_systemctl/workerman-worker-20020.service /etc/systemd/system/
     sudo cp /var/www/Sitemas/script_worker_systemctl/workerman-worker-20040.service /etc/systemd/system/
     sudo systemctl daemon-reload

2. Habilite e inicie:
     sudo systemctl enable --now workerman-worker-20001.service
     sudo systemctl enable --now workerman-worker-20010.service
     sudo systemctl enable --now workerman-worker-20020.service
     sudo systemctl enable --now workerman-worker-20040.service

   - Para host diferente, adicione Environment=WORKER_HOST=0.0.0.0 em cada service.
   - Para outra pasta, altere APP_ROOT/WorkingDirectory no arquivo correspondente.

Comandos úteis
--------------
Status rápido:
    sudo systemctl status workerman-worker-20001

Logs em tempo real:
    sudo journalctl -u workerman-worker-20001 -f

Reiniciar após alterações:
    sudo systemctl restart workerman-worker-20001

Remover do boot/parar:
    sudo systemctl disable --now workerman-worker-20010

Verificar portas:
    sudo ss -ltnp | grep 2000

Com isso os workers Workerman iniciam automaticamente em cada boot.






