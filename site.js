/* ===========================================================
   Servidor Magnatas — scripts v2
   Revela as secoes ao rolar, consulta o status dos servidores
   e copia os IPs. Usado pela index.html e pela equipe.html.

   Ajustes ficam nos atributos do <body>:
     data-mostrar-status   "true" | "false"   (padrao true)
     data-intervalo-status  segundos, 15 a 300 (padrao 60)
     data-animacoes        "true" | "false"   (padrao true)
   =========================================================== */

(function () {
    'use strict';

    var SERVIDORES = {
        mgt: { ip: 'mgt.servidormagnatas.com.br', dot: 'mgt-dot', status: 'mgt-status' },
        atm: { ip: 'rotativo.servidormagnatas.com.br', dot: 'atm-dot', status: 'atm-status' }
    };

    var COR_CARREGANDO = '#FFD700';
    var COR_ONLINE = '#39FF14';
    var COR_OFFLINE = '#FF00FF';

    var opcoes = lerOpcoes();
    var dados = { mgt: null, atm: null };
    var timer = null;

    function lerOpcoes() {
        var d = document.body.dataset;
        var intervalo = parseInt(d.intervaloStatus, 10);
        if (isNaN(intervalo)) intervalo = 60;
        return {
            mostrarStatus: d.mostrarStatus !== 'false',
            intervaloStatus: Math.min(300, Math.max(15, intervalo)),
            animacoes: d.animacoes !== 'false'
        };
    }

    function texto(id, valor) {
        var el = document.getElementById(id);
        if (el) el.textContent = valor;
    }

    function cor(id, valor) {
        var el = document.getElementById(id);
        if (el) el.style.color = valor;
    }

    /* ---------- revelar ao rolar ---------- */

    function iniciarRevelacao() {
        var alvos = document.querySelectorAll('[data-reveal]');
        var reduzido = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (!opcoes.animacoes || reduzido || !('IntersectionObserver' in window)) {
            Array.prototype.forEach.call(alvos, function (el) { el.classList.add('in'); });
            return;
        }

        var io = new IntersectionObserver(function (entradas) {
            entradas.forEach(function (e) {
                if (e.isIntersecting) {
                    e.target.classList.add('in');
                    io.unobserve(e.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

        Array.prototype.forEach.call(alvos, function (el) { io.observe(el); });

        // Rede de seguranca: nada fica invisivel se o observer nao disparar.
        setTimeout(function () {
            Array.prototype.forEach.call(
                document.querySelectorAll('[data-reveal]:not(.in)'),
                function (el) { el.classList.add('in'); }
            );
        }, 2500);
    }

    /* ---------- status dos servidores ---------- */

    function buscarServidor(ip) {
        var urls = [
            'https://api.mcsrvstat.us/3/' + ip,
            'https://api.mcstatus.io/v2/status/java/' + ip
        ];

        return urls.reduce(function (encadeado, url) {
            return encadeado.then(function (resultado) {
                if (resultado) return resultado;
                return fetch(url)
                    .then(function (r) { return r.ok ? r.json() : null; })
                    .then(function (d) { return d && d.online !== undefined ? d : null; })
                    .catch(function () { return null; });
            });
        }, Promise.resolve(null));
    }

    function jogadores(d) {
        return (d && d.players && d.players.online) || 0;
    }

    function textoStatus(d) {
        if (d === null) return 'Verificando...';
        if (d.online) return jogadores(d) + '/' + ((d.players && d.players.max) || 0) + ' jogadores online';
        return 'Offline ou em manutenção';
    }

    function corDot(d) {
        if (d === null) return COR_CARREGANDO;
        return d.online ? COR_ONLINE : COR_OFFLINE;
    }

    function pintar() {
        Object.keys(SERVIDORES).forEach(function (chave) {
            var d = dados[chave];
            texto(SERVIDORES[chave].status, textoStatus(d));
            cor(SERVIDORES[chave].dot, corDot(d));
        });

        var carregou = dados.mgt !== null || dados.atm !== null;
        var online = [dados.mgt, dados.atm].filter(function (d) { return d && d.online; });
        var total = online.reduce(function (s, d) { return s + jogadores(d); }, 0);

        var rotulo = 'Verificando a rede...';
        if (carregou) {
            rotulo = online.length ? total + ' jogadores online agora' : 'Rede offline ou em manutenção';
        }

        texto('net-label', rotulo);
        texto('net-label-2', rotulo);
        cor('net-dot', !carregou ? COR_CARREGANDO : (online.length ? COR_ONLINE : COR_OFFLINE));
    }

    function carregar() {
        Promise.all([
            buscarServidor(SERVIDORES.mgt.ip),
            buscarServidor(SERVIDORES.atm.ip)
        ]).then(function (r) {
            dados.mgt = r[0];
            dados.atm = r[1];
            pintar();
        });
    }

    /* ---------- copiar IP ---------- */

    function copiar(botao) {
        var ip = botao.dataset.copy;

        var pronto = function () {
            botao.textContent = '✓ Copiado';
            setTimeout(function () { botao.textContent = 'Copiar'; }, 2000);
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(ip).then(pronto).catch(function () {});
            return;
        }

        var campo = document.createElement('input');
        campo.value = ip;
        document.body.appendChild(campo);
        campo.select();
        try {
            document.execCommand('copy');
            pronto();
        } catch (e) { /* navegador bloqueou a copia */ }
        document.body.removeChild(campo);
    }

    function iniciarCopia() {
        Array.prototype.forEach.call(document.querySelectorAll('.btn-copy'), function (botao) {
            botao.addEventListener('click', function () { copiar(botao); });
        });
    }

    /* ---------- inicio ---------- */

    iniciarRevelacao();
    iniciarCopia();

    if (opcoes.mostrarStatus) {
        carregar();
        timer = setInterval(carregar, opcoes.intervaloStatus * 1000);
        window.addEventListener('pagehide', function () { clearInterval(timer); });
    }
})();
