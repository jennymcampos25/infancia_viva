/* ===== Utilidades ===== */
function aviso(msg, ok) {
    var el = document.getElementById("aviso");
    if (el) { el.textContent = msg; el.className = "aviso " + (ok ? "ok" : "erro"); }
    return false;
}

/* ===== Doação (código original mantido) ===== */
function doar() {
    alert("Obrigado por querer fazer parte da Infância Viva!");
    window.location.href = "doacao.html";
}

function validarDoacao() {
    var nome = document.getElementsByName("nome")[0].value;
    var email = document.getElementsByName("email")[0].value;
    var telefone = document.getElementsByName("telefone")[0].value;
    var brinquedo = document.getElementsByName("brinquedo")[0].value;
    var quantidade = document.getElementsByName("quantidade")[0].value;
    var data = document.getElementsByName("data")[0].value;
    var endereco = document.getElementsByName("endereco")[0].value;
    var valor = document.getElementsByName("valor")[0].value;
    var ponto = document.getElementsByName("ponto")[0].value;

    if (nome == "" || email == "" || telefone == "" ||
        brinquedo == "" || quantidade == "" || data == "" ||
        endereco == "" || valor == "" || ponto == "") {
        alert("Por favor, preencha todos os campos obrigatórios.");
        return false;
    }

    // Escolheu "Outro valor": precisa digitar um valor maior que zero
    if (valor == "outro") {
        var outro = document.getElementById("campoOutroValor").value;
        if (outro == "" || Number(outro) <= 0) {
            alert("Digite o valor que deseja doar ou escolha outra opção.");
            return false;
        }
    }

    return true;
}

function mostrarOutroValor() {
    var valor = document.getElementById("valor").value;
    var outroValor = document.getElementById("outroValor");

    if (valor == "outro") {
        outroValor.innerHTML =
            '<label>Digite o valor:</label>' +
            '<input type="number" name="outroValor" id="campoOutroValor" step="0.01" min="0.01" onkeydown="confirmarValor(event)">';
    } else {
        outroValor.innerHTML = "";
    }
}

function confirmarValor(event) {
    if (event.key == "Enter") {
        event.preventDefault();
        var valor = document.getElementById("campoOutroValor").value;
        if (valor != "") {
            alert("Você escolheu doar R$ " + valor + ".");
        }
    }
}

/* ===== Login e cadastro =====
   Agora são feitos pelo PHP (login.php, cadastro.php, logout.php). */

/* ===== Pagamento (simulado) ===== */
function iniciarPagamento() {
    var f = document.getElementById("formPagamento");
    if (!f) return;
    var v = new URLSearchParams(location.search).get("valor");
    if (v && !isNaN(v)) f.valor.value = v;

    var m = f.metodo;
    function alternar() {
        document.getElementById("boxPix").hidden = m.value !== "pix";
        document.getElementById("boxCartao").hidden = m.value !== "cartao";
    }
    m.addEventListener("change", alternar);
    alternar();
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

/* ===== Inicialização ===== */
document.addEventListener("DOMContentLoaded", function () {
    // Menu: destaca o link da página atual
    var pagina = location.pathname.split("/").pop() || "index.html";
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
        senha_curta: "A senha deve ter pelo menos 6 caracteres.",
        senhas_diferentes: "As senhas não conferem.",
        email_existe: "Este e-mail já está cadastrado.",
        login: "E-mail ou senha incorretos.",
        foto: "Não foi possível usar essa foto. Tente outra imagem.",
        pagamento: "Confira o nome, o valor e a forma de pagamento.",
        erro: "Ocorreu um erro. Tente novamente."
    };
    if (q.get("erro")) aviso(erros[q.get("erro")] || erros.erro, false);
    if (q.get("pagamento") === "ok") aviso("Pagamento registrado! Obrigado!", true);
    if (q.get("foto") === "ok") aviso("Foto atualizada!", true);
    if (q.get("cadastro") === "ok") aviso("Cadastro realizado! Agora é só entrar.", true);

    // Slideshow do banner: fotos da pasta "imagens".
    // O "?v=2" no fim do nome obriga o navegador a baixar a foto de novo; aumente quando trocar uma foto
    // mantendo o mesmo nome de arquivo.
    // "foco" diz qual parte da foto fica visível quando o banner corta as bordas
    // (horizontal vertical; ex.: "50% 25%" = centro na largura, mais para cima na altura).
    var slide = document.getElementById("slide");
    if (slide) {
        var imagens = [
            { src: "imagens/foto1.jpg?v=2", foco: "50% 22%" },   // roda de crianças olhando para baixo
            { src: "imagens/foto2.jpg?v=2", foco: "50% 55%" },   // mãos com carinhas desenhadas
            { src: "imagens/foto3.jpg?v=2", foco: "50% 20%" }    // crianças correndo na grama
        ];
        var i = 0;
        function mostrar() {
            slide.style.objectPosition = imagens[i].foco;
            slide.src = imagens[i].src;
        }
        // Se alguma foto não existir na pasta, ela sai da lista e o slideshow segue com as outras
        slide.onerror = function () {
            imagens.splice(i, 1);
            if (imagens.length === 0) return;
            i = i % imagens.length;
            mostrar();
        };
        mostrar();
        setInterval(function () {
            if (imagens.length < 2) return;
            i = (i + 1) % imagens.length;
            mostrar();
        }, 4000);
    }

    iniciarPagamento();
});
