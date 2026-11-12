/* ===== Utilidades ===== */
function aviso(msg, ok) {
    var el = document.getElementById("aviso");
    if (el) { el.textContent = msg; el.className = "aviso " + (ok ? "ok" : "erro"); }
    return false;
}

/* ===== Login e cadastro =====
   Agora são feitos pelo PHP (login.php, cadastro.php, logout.php). */

/* ===== Doação em dinheiro (simulada) =====
   Etapa 1 (pagamento.html): valores rápidos e dados da conta.
   Etapa 2 (pagamento_pedido.php): QR Code + prazo + conferência do Pix, ou formulário do cartão.
   Etapa 4 (pagamento_recibo.php): botão de imprimir. */
function iniciarPagamento() {
    var f = document.getElementById("formPagamento");
    if (!f) return;
    var campo = f.valor;
    var botoes = f.querySelectorAll("[data-valor]");
    function marcar() {
        for (var i = 0; i < botoes.length; i++) {
            botoes[i].classList.toggle("escolhido", Number(botoes[i].getAttribute("data-valor")) === Number(campo.value));
        }
    }
    for (var i = 0; i < botoes.length; i++) {
        botoes[i].addEventListener("click", function () { campo.value = this.getAttribute("data-valor"); marcar(); });
    }
    campo.addEventListener("input", marcar);

    var v = new URLSearchParams(location.search).get("valor");
    if (v && !isNaN(v)) campo.value = Number(v);
    marcar();

    // Quem está logado já encontra o nome e o e-mail preenchidos
    fetch("sessao.php", { credentials: "same-origin" })
        .then(function (r) { return r.json(); })
        .then(function (s) {
            if (!s.logado) return;
            if (!f.nome.value) f.nome.value = s.nome;
            if (!f.email.value && s.email) f.email.value = s.email;
        })
        .catch(function () {});
}

function iniciarPix() {
    var caixa = document.getElementById("pixPagar");
    if (!caixa) return;
    var codigo = caixa.getAttribute("data-codigo");
    var restam = Number(caixa.getAttribute("data-segundos"));

    // Desenha o QR Code (biblioteca lib/qrcode/qrcode.js)
    var qrCaixa = document.getElementById("pixQr");
    if (window.qrcode) {
        var qr = qrcode(0, "M");
        qr.addData(qrCaixa.getAttribute("data-qr"));
        qr.make();
        qrCaixa.innerHTML = qr.createSvgTag({ cellSize: 6, margin: 2, scalable: true });
    }

    // Copiar o "copia e cola"
    var campo = document.getElementById("pixCodigo");
    var copiar = document.getElementById("pixCopiar");
    copiar.addEventListener("click", function () {
        campo.select();
        var feito = function () {
            copiar.textContent = "Copiado!";
            setTimeout(function () { copiar.textContent = "Copiar"; }, 2000);
        };
        if (navigator.clipboard) navigator.clipboard.writeText(campo.value).then(feito, function () { document.execCommand("copy"); feito(); });
        else { document.execCommand("copy"); feito(); }
    });

    // Prazo para pagar
    var tempo = document.getElementById("pixTempo");
    function mostrarTempo() {
        var m = Math.floor(restam / 60), s = restam % 60;
        tempo.textContent = (m < 10 ? "0" : "") + m + ":" + (s < 10 ? "0" : "") + s;
    }
    mostrarTempo();
    var relogio = setInterval(function () {
        restam = Math.max(0, restam - 1);
        mostrarTempo();
        if (restam === 0) { clearInterval(relogio); location.reload(); }
    }, 1000);

    // Pergunta ao servidor, a cada 3 segundos, se o pagamento chegou
    var status = document.getElementById("pixStatus");
    setInterval(function () {
        fetch("pagamento_status.php?c=" + encodeURIComponent(codigo), { cache: "no-store" })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.status === "Pago") {
                    status.className = "status-pedido pago";
                    status.textContent = "Pagamento recebido! Abrindo o recibo…";
                    setTimeout(function () { location.href = "pagamento_recibo.php?c=" + codigo; }, 1200);
                } else if (d.status !== "Aguardando") {
                    location.reload();
                }
            })
            .catch(function () {});
    }, 3000);
}

// Bandeira pelo começo do número
function bandeiraCartao(n) {
    if (/^(4011|4312|4389|4514|4576|5041|5066|5067|509|6277|6362|6363|650|6516|6550)/.test(n)) return "Elo";
    if (/^(606282|3841)/.test(n)) return "Hipercard";
    if (/^3[47]/.test(n)) return "American Express";
    if (/^4/.test(n)) return "Visa";
    if (/^(5[1-5]|2[2-7])/.test(n)) return "Mastercard";
    return "";
}

// Confere o dígito verificador do cartão (algoritmo de Luhn)
function cartaoValido(n) {
    var soma = 0, dobra = false;
    for (var i = n.length - 1; i >= 0; i--) {
        var d = Number(n.charAt(i));
        if (dobra) { d *= 2; if (d > 9) d -= 9; }
        soma += d;
        dobra = !dobra;
    }
    return n.length >= 13 && n.length <= 19 && soma % 10 === 0;
}

function iniciarCartao() {
    var f = document.getElementById("formCartao");
    if (!f) return;
    var numero = document.getElementById("cartaoNumero");
    var nome = document.getElementById("cartaoNome");
    var validade = document.getElementById("cartaoValidade");
    var cvv = document.getElementById("cartaoCvv");
    var rotulo = document.getElementById("cartaoBandeira");

    numero.addEventListener("input", function () {
        var n = numero.value.replace(/\D/g, "").slice(0, 19);
        numero.value = n.replace(/(\d{4})(?=\d)/g, "$1 ");
        rotulo.textContent = bandeiraCartao(n);
    });
    validade.addEventListener("input", function () {
        var v = validade.value.replace(/\D/g, "").slice(0, 4);
        validade.value = v.length > 2 ? v.slice(0, 2) + "/" + v.slice(2) : v;
    });
    cvv.addEventListener("input", function () { cvv.value = cvv.value.replace(/\D/g, "").slice(0, 4); });

    f.addEventListener("submit", function (e) {
        e.preventDefault();
        var n = numero.value.replace(/\D/g, "");
        var bandeira = bandeiraCartao(n);
        var mv = validade.value.match(/^(\d{2})\/(\d{2})$/);
        var hoje = new Date();
        var vencido = !mv || Number(mv[1]) < 1 || Number(mv[1]) > 12
            || new Date(2000 + Number(mv[2]), Number(mv[1]), 1) <= hoje;   // vale até o fim do mês

        if (!cartaoValido(n) || !bandeira) { aviso("Número do cartão inválido. Confira os dígitos.", false); numero.focus(); return; }
        if (nome.value.trim().length < 3) { aviso("Escreva o nome como está no cartão.", false); nome.focus(); return; }
        if (vencido) { aviso("Validade inválida ou cartão vencido.", false); validade.focus(); return; }
        if (!/^\d{3,4}$/.test(cvv.value)) { aviso("O CVV tem 3 dígitos (4 no American Express).", false); cvv.focus(); return; }
        aviso("", true);

        // Só a bandeira e os 4 últimos dígitos vão para o servidor
        f.bandeira.value = bandeira;
        f.final.value = n.slice(-4);
        document.getElementById("processando").hidden = false;
        setTimeout(function () { f.submit(); }, 2200);
    });
}

function iniciarRecibo() {
    var b = document.getElementById("imprimirRecibo");
    if (b) b.addEventListener("click", function () { window.print(); });
}

/* ===== Foto de perfil (cadastro e Minha conta) =====
   A foto da galeria é recortada em quadrado e reduzida para 256x256 aqui no navegador,
   então o servidor recebe uma imagem pequena (campo foto_upload). */
function iniciarEscolhaFoto(caixa) {
    var previa = caixa.querySelector(".foto-previa");
    var arquivo = caixa.querySelector("input[type=file]");
    var campoPadrao = caixa.querySelector("input[name=foto_padrao]");
    var campoUpload = caixa.querySelector("input[name=foto_upload]");
    var botoes = caixa.querySelectorAll("[data-avatar]");

    function marcar(botao) {
        for (var k = 0; k < botoes.length; k++) botoes[k].classList.toggle("escolhido", botoes[k] === botao);
    }

    for (var k = 0; k < botoes.length; k++) {
        botoes[k].addEventListener("click", function () {
            campoPadrao.value = this.getAttribute("data-avatar");
            campoUpload.value = "";
            previa.src = this.querySelector("img").src;
            marcar(this);
        });
    }

    arquivo.addEventListener("change", function () {
        var f = arquivo.files[0];
        if (!f) return;
        if (!/^image\//.test(f.type)) { alert("Escolha um arquivo de imagem."); return; }
        var leitor = new FileReader();
        leitor.onload = function () {
            var img = new Image();
            img.onload = function () {
                var lado = Math.min(img.width, img.height);
                var tela = document.createElement("canvas");
                tela.width = tela.height = 256;
                tela.getContext("2d").drawImage(img, (img.width - lado) / 2, (img.height - lado) / 2, lado, lado, 0, 0, 256, 256);
                campoUpload.value = tela.toDataURL("image/jpeg", 0.85);
                campoPadrao.value = "";
                previa.src = campoUpload.value;
                marcar(null);
            };
            img.onerror = function () { alert("Não foi possível abrir essa imagem. Tente outra."); };
            img.src = leitor.result;
        };
        leitor.readAsDataURL(f);
    });
}

/* ===== Bolinha de perfil no topo =====
   Sem login: imagem padrão que leva ao Login. Com login: a foto da pessoa abre um menu
   com Minha conta, Admin (só para o administrador) e Sair. */
function iniciarPerfil(sessao) {
    var topo = document.querySelector(".topo");
    var linkLogin = document.getElementById("linkLogin");
    if (!topo) return;
    if (linkLogin) linkLogin.parentNode.removeChild(linkLogin);

    var antigo = document.getElementById("perfil");
    if (antigo) antigo.parentNode.removeChild(antigo);

    var perfil = document.createElement("div");
    perfil.className = "perfil";
    perfil.id = "perfil";

    var foto = document.createElement("img");
    foto.src = sessao.logado ? sessao.foto : "imagens/avatares/padrao.svg";
    foto.alt = "";

    if (!sessao.logado) {
        var entrar = document.createElement("a");
        entrar.className = "perfil-botao";
        entrar.href = "login.html";
        entrar.title = "Entrar";
        entrar.setAttribute("aria-label", "Entrar");
        entrar.appendChild(foto);
        perfil.appendChild(entrar);
    } else {
        var botao = document.createElement("button");
        botao.type = "button";
        botao.className = "perfil-botao";
        botao.title = sessao.nome;
        botao.setAttribute("aria-label", "Menu da conta");
        botao.setAttribute("aria-expanded", "false");
        botao.appendChild(foto);

        var caixa = document.createElement("div");
        caixa.className = "perfil-menu";
        caixa.hidden = true;
        var nome = document.createElement("strong");
        nome.textContent = sessao.nome;   // textContent: o nome nunca vira HTML
        caixa.appendChild(nome);
        var itens = [["minha_conta.php", "Minha conta"]];
        if (sessao.tipo === "admin") itens.push(["admin.php", "Painel do administrador"]);
        itens.push(["logout.php", "Sair"]);
        for (var k = 0; k < itens.length; k++) {
            var a = document.createElement("a");
            a.href = itens[k][0];
            a.textContent = itens[k][1];
            caixa.appendChild(a);
        }

        botao.addEventListener("click", function (e) {
            e.stopPropagation();
            caixa.hidden = !caixa.hidden;
            botao.setAttribute("aria-expanded", caixa.hidden ? "false" : "true");
        });
        document.addEventListener("click", function (e) {
            if (!perfil.contains(e.target)) { caixa.hidden = true; botao.setAttribute("aria-expanded", "false"); }
        });
        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape") { caixa.hidden = true; botao.setAttribute("aria-expanded", "false"); }
        });

        perfil.appendChild(botao);
        perfil.appendChild(caixa);
    }
    topo.insertBefore(perfil, document.getElementById("menuBotao"));
}

/* ===== Número que "conta" de 0 até o valor (faixa de números da página inicial) ===== */
function contarAte(elemento, valorFinal) {
    var parado = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    if (parado || valorFinal === 0) { elemento.textContent = valorFinal.toLocaleString("pt-BR"); return; }
    var inicio = null;
    function passo(agora) {
        if (!inicio) inicio = agora;
        var p = Math.min(1, (agora - inicio) / 1500);
        elemento.textContent = Math.round(valorFinal * (1 - Math.pow(1 - p, 3))).toLocaleString("pt-BR");
        if (p < 1) requestAnimationFrame(passo);
    }
    requestAnimationFrame(passo);
}

/* ===== Inicialização ===== */
document.addEventListener("DOMContentLoaded", function () {
    // Menu: destaca o link da página atual (as páginas de doação usam data-menu="doacao.html" no <body>)
    var pagina = document.body.getAttribute("data-menu") || location.pathname.split("/").pop() || "index.html";
    var links = document.querySelectorAll("#menu a");
    for (var k = 0; k < links.length; k++) {
        if (links[k].getAttribute("href") === pagina) links[k].className = "ativo";
    }

    // Menu do celular: o botão ☰ abre e fecha
    var botaoMenu = document.getElementById("menuBotao");
    if (botaoMenu) {
        botaoMenu.addEventListener("click", function () {
            var aberto = document.getElementById("menu").classList.toggle("aberto");
            botaoMenu.setAttribute("aria-expanded", aberto ? "true" : "false");
        });
    }

    // Bolinha de perfil: aparece na hora com a imagem padrão e é atualizada quando o PHP
    // responde se há alguém logado
    iniciarPerfil({ logado: false });
    fetch("sessao.php", { credentials: "same-origin" })
        .then(function (r) { return r.json(); })
        .then(function (s) { if (s.logado) iniciarPerfil(s); })
        .catch(function () {});

    // Escolha da foto de perfil (cadastro e Minha conta)
    var caixasFoto = document.querySelectorAll("[data-escolha-foto]");
    for (var c = 0; c < caixasFoto.length; c++) iniciarEscolhaFoto(caixasFoto[c]);

    // Mensagens vindas do PHP pelo endereço (?erro=... ou ?cadastro=ok)
    var q = new URLSearchParams(location.search);
    var erros = {
        campos: "Preencha todos os campos.",
        email_invalido: "E-mail inválido.",
        email_inexistente: "Esse e-mail não parece existir. Confira se digitou certo.",
        nao_confirmado: "Você ainda não confirmou seu e-mail. Abra o link que enviamos para entrar.",
        link_invalido: "Esse link de confirmação não vale mais. Peça um link novo abaixo.",
        senha_curta: "A senha deve ter pelo menos 6 caracteres.",
        senhas_diferentes: "As senhas não conferem.",
        email_existe: "Este e-mail já está cadastrado.",
        login: "E-mail ou senha incorretos.",
        foto: "Não foi possível usar essa foto. Tente outra imagem.",
        pagamento: "Confira o nome, o valor (mínimo R$ 1,00) e a forma de pagamento.",
        pedido: "Não encontramos esse pedido. Faça um novo.",
        erro: "Ocorreu um erro. Tente novamente."
    };
    if (q.get("erro")) aviso(erros[q.get("erro")] || erros.erro, false);
    if (q.get("pedido") === "cancelado") aviso("Pedido cancelado. Nenhum valor foi cobrado.", true);
    if (q.get("foto") === "ok") aviso("Foto atualizada!", true);
    if (q.get("cadastro") === "ok") aviso("Cadastro realizado! Agora é só entrar.", true);
    if (q.get("cadastro") === "confirme") aviso("Conta criada! Enviamos um link para o seu e-mail. Confirme para poder entrar.", true);
    if (q.get("cadastro") === "falha_email") aviso("Conta criada, mas não conseguimos enviar o e-mail de confirmação. Tente reenviar abaixo.", false);
    if (q.get("confirmado") === "ok") aviso("E-mail confirmado! Agora é só entrar.", true);
    if (q.get("reenvio") === "ok") aviso("Se esse e-mail tiver uma conta sem confirmação, enviamos um link novo. Confira a caixa de entrada e o spam.", true);
    if (q.get("reenvio") === "falha") aviso("Não conseguimos enviar o e-mail agora. Tente de novo em alguns minutos.", false);

    // Tela de login: mostra o "Reenviar link" quando a pessoa precisa confirmar o e-mail
    var reenviar = document.getElementById("formReenviar");
    var precisaConfirmar = ["nao_confirmado", "link_invalido"].indexOf(q.get("erro")) !== -1
                        || ["confirme", "falha_email"].indexOf(q.get("cadastro")) !== -1;
    if (reenviar && precisaConfirmar) {
        reenviar.hidden = false;
        if (q.get("email")) {
            reenviar.email.value = q.get("email");
            var campoLogin = document.querySelector("#formLogin input[name=email]");
            if (campoLogin) campoLogin.value = q.get("email");
        }
    }

    // Fotos da abertura (página inicial): trocam a cada 4 segundos, com um esmaecer.
    // As fotos ficam no index.html, dentro de <div class="moldura">.
    var fotos = document.querySelectorAll(".moldura img");
    if (fotos.length > 1) {
        var atual = 0;
        setInterval(function () {
            fotos[atual].classList.remove("visivel");
            atual = (atual + 1) % fotos.length;
            fotos[atual].classList.add("visivel");
        }, 4000);
    }

    // Cartões de todas as páginas também aparecem ao rolar
    var cartoes = document.querySelectorAll(".cartao:not(.revela)");
    for (var k = 0; k < cartoes.length; k++) cartoes[k].classList.add("revela");

    // Elementos com a classe "revela" aparecem quando chegam na tela
    var revelar = document.querySelectorAll(".revela");
    if ("IntersectionObserver" in window) {
        var observador = new IntersectionObserver(function (itens) {
            itens.forEach(function (item) {
                if (item.isIntersecting) {
                    item.target.classList.add("visivel");
                    observador.unobserve(item.target);
                }
            });
        }, { threshold: 0.15 });
        for (var r = 0; r < revelar.length; r++) observador.observe(revelar[r]);
    } else {
        for (var r2 = 0; r2 < revelar.length; r2++) revelar[r2].classList.add("visivel");
    }

    // Faixa de números (página inicial): busca os totais no banco e "conta" até eles
    var numeros = document.querySelectorAll("[data-numero]");
    if (numeros.length) {
        fetch("numeros.php", { credentials: "same-origin" })
            .then(function (r) { return r.json(); })
            .then(function (dados) {
                for (var n = 0; n < numeros.length; n++) {
                    var chave = numeros[n].getAttribute("data-numero");
                    if (typeof dados[chave] === "number") contarAte(numeros[n], dados[chave]);
                }
            })
            .catch(function () {});
    }

    // Confete caindo nas faixas com data-confete
    var faixas = document.querySelectorAll("[data-confete]");
    var coresConfete = ["#f5a524", "#4f8a3a", "#2f7a80", "#ffffff"];
    for (var f = 0; f < faixas.length; f++) {
        for (var c = 0; c < 18; c++) {
            var pedaco = document.createElement("span");
            pedaco.className = "confete";
            pedaco.setAttribute("aria-hidden", "true");
            pedaco.style.left = (c * 5.6 + Math.random() * 3) + "%";
            pedaco.style.background = coresConfete[c % coresConfete.length];
            pedaco.style.animationDelay = (-Math.random() * 7) + "s";
            pedaco.style.animationDuration = (5 + Math.random() * 4) + "s";
            faixas[f].appendChild(pedaco);
        }
    }

    // Painel do administrador: a caixa de busca esconde o que não tem o texto digitado
    var buscas = document.querySelectorAll("[data-filtrar]");
    for (var b = 0; b < buscas.length; b++) {
        buscas[b].addEventListener("input", function () {
            var alvo = document.getElementById(this.getAttribute("data-filtrar"));
            if (!alvo) return;
            var termo = this.value.toLowerCase();
            // tabela: filtra as linhas; lista de cartões (mensagens/avaliações): filtra os cartões
            var itens = alvo.tagName === "TABLE" ? alvo.querySelectorAll("tr") : alvo.children;
            for (var l = 0; l < itens.length; l++) {
                if (alvo.tagName === "TABLE" && !itens[l].querySelector("td")) continue;   // linha de títulos
                itens[l].hidden = termo !== "" && itens[l].textContent.toLowerCase().indexOf(termo) === -1;
            }
        });
    }

    iniciarPagamento();
    iniciarPix();
    iniciarCartao();
    iniciarRecibo();
});
