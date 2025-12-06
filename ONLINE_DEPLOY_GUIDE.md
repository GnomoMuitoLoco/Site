# Guia simples: colocar o site servidormagnatas.com.br no ar (Ubuntu 24.04)

Este passo a passo é para quem está começando. Ele cobre do zero até o site ficar acessível pelo domínio, continuar online após reiniciar e usar HTTPS.

## Visão geral (o que vamos fazer)
1) Preparar o servidor Linux (atualizar, criar usuário, firewall).
2) Instalar Nginx + PHP (o site é em PHP) e configurar para iniciar sozinho.
3) Apontar o domínio no Cloudflare para o IP público.
4) Configurar o roteador para enviar as portas 80/443 para o servidor (IP interno 192.168.0.197).
5) Colocar os arquivos do site em /var/www/site e ajustar permissões.
6) Gerar e renovar automaticamente o certificado HTTPS (Let’s Encrypt) mesmo com Cloudflare.
7) Testar tudo e saber o que verificar se algo cair.

## 1) Preparar o servidor Ubuntu 24.04 (rodar no servidor)
```bash
# Atualizar pacotes
sudo apt update && sudo apt upgrade -y

# Criar um usuário administrativo (ex.: deploy)
sudo adduser deploy
sudo usermod -aG sudo deploy

# Ativar firewall básico (liberar SSH, HTTP, HTTPS)
sudo apt install -y ufw
sudo ufw allow OpenSSH
sudo ufw allow 80,443/tcp
sudo ufw enable

# (Opcional) Ajustar fuso horário
sudo timedatectl set-timezone America/Sao_Paulo
```
Conectar depois como `deploy` via SSH para trabalhar no dia a dia.

## 2) Instalar Nginx + PHP-FPM
```bash
sudo apt install -y nginx php-fpm php-cli php-mysql php-curl php-xml php-zip php-gd php-mbstring

# Verificar serviços
systemctl status nginx
systemctl status php8.2-fpm   # versão padrão do Ubuntu 24.04
```
Nginx e PHP-FPM já ficam configurados para iniciar automaticamente após reboot.

## 3) Configurar DNS no Cloudflare
1) Acesse o painel do Cloudflare do domínio `servidormagnatas.com.br`.
2) Crie/ajuste registros:
   - A `servidormagnatas.com.br` -> `38.224.194.161` (Proxy ON é ok para HTTPS e cache). 
   - CNAME `www` -> `servidormagnatas.com.br` (Proxy ON).
3) Aguarde a propagação (geralmente minutos). 
4) Teste: `nslookup servidormagnatas.com.br` deve retornar `38.224.194.161`.

## 4) Abrir portas no roteador (port forwarding)
- Encaminhar porta **80** (HTTP) e **443** (HTTPS) do roteador para o IP interno do servidor: **192.168.0.197**.
- Salvar e reiniciar o roteador se precisar.
- Testar do lado de fora (4G ou outra rede): `http://38.224.194.161` deve carregar a página padrão do Nginx.

## 5) Colocar o site em /var/www/site
```bash
# Como usuário deploy
cd ~
# Clonar ou copiar seu projeto; exemplo com git:
git clone https://seu-repositorio.git site
sudo rm -rf /var/www/site
sudo mv ~/site /var/www/site

# Ajustar permissões (Nginx lê os arquivos, o usuário deploy edita)
sudo chown -R deploy:www-data /var/www/site
sudo find /var/www/site -type d -exec chmod 755 {} \;
sudo find /var/www/site -type f -exec chmod 644 {} \;
```

## 6) Configurar Nginx para o domínio
Criar um host virtual apontando para a pasta do site.
```bash
sudo tee /etc/nginx/sites-available/servidormagnatas.com.br > /dev/null <<'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name servidormagnatas.com.br www.servidormagnatas.com.br;

    root /var/www/site;
    index index.php index.html;

    access_log /var/log/nginx/servidor.access.log;
    error_log  /var/log/nginx/servidor.error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
EOF

# Ativar o site
sudo ln -s /etc/nginx/sites-available/servidormagnatas.com.br /etc/nginx/sites-enabled/

# Desativar default (opcional)
sudo rm /etc/nginx/sites-enabled/default

# Testar sintaxe e recarregar
sudo nginx -t && sudo systemctl reload nginx
```

## 7) Certificado HTTPS automático (Let’s Encrypt + Cloudflare)
Usaremos Certbot com plugin Nginx. Se o proxy do Cloudflare estiver ligado, o HTTP-01 costuma funcionar; se não funcionar, use DNS-01.
```bash
sudo apt install -y certbot python3-certbot-nginx

# Tentar emitir com HTTP-01 (mais simples)
sudo certbot --nginx -d servidormagnatas.com.br -d www.servidormagnatas.com.br

# Renovações automáticas já ficam em /etc/cron.d; teste:
sudo certbot renew --dry-run
```
Se o HTTP-01 não funcionar por causa do proxy, no Cloudflare pause o proxy (modo DNS only) enquanto emite, ou use DNS-01:
```bash
# DNS-01 (pede token TXT; siga o passo a passo mostrado pelo certbot)
sudo certbot -d servidormagnatas.com.br -d www.servidormagnatas.com.br --manual --preferred-challenges dns
```
Depois reative o proxy.

## 8) Tornar persistente após reboot
- Nginx e PHP-FPM já estão com `systemd` e sobem sozinhos: `systemctl enable nginx php8.2-fpm` (já vêm enabled).
- Firewall UFW permanece ativo após reboot.
- Certbot renova automaticamente (cron já instalado). 
- Se usar git pull para atualizar o site, basta `sudo systemctl reload nginx` quando mudar configurações.

## 9) Checklist rápido de testes
1) **DNS**: `nslookup servidormagnatas.com.br` retorna `38.224.194.161`.
2) **HTTP**: `http://servidormagnatas.com.br` abre o site.
3) **HTTPS**: `https://servidormagnatas.com.br` mostra cadeado válido.
4) **www**: redireciona ou abre igual.
5) **Logs**: `tail -f /var/log/nginx/servidor.error.log` deve ficar limpo.
6) **Firewall**: `sudo ufw status` mostra 80/443/SSH liberados.

## 10) Se algo der errado
- DNS não resolve: confira registros A/CNAME no Cloudflare e propagação.
- IP público muda: atualize o registro A no Cloudflare (ou configure IP fixo na WAN).
- Sem acesso externo: verifique port forwarding 80/443 para 192.168.0.197 e se o ISP não bloqueia.
- Erro 502/404: veja logs em `/var/log/nginx/servidor.error.log` e se o PHP-FPM está rodando (`systemctl status php8.2-fpm`).
- HTTPS falha: tente renovar `sudo certbot renew --dry-run` ou pausar o proxy Cloudflare para emitir.

## 11) Manutenção básica
- Atualizar sistema: `sudo apt update && sudo apt upgrade -y` (mensal).
- Renovar cert: automático; opcional ver `sudo certbot renew --dry-run`.
- Backups: copie `/var/www/site` e (se existir) o banco MySQL (`mysqldump`).
- Monitorar espaço em disco: `df -h`.

Pronto! Seguindo estes passos, o site fica online em `https://servidormagnatas.com.br`, persiste após reinícios e com HTTPS válido.
