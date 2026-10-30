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

    // Menu: o link "Login" vira "Sair" quando há sessão no servidor (PHP)
    fetch("sessao.php", { credentials: "same-origin" })
        .then(function (r) { return r.json(); })
        .then(function (s) {
            var lk = document.getElementById("linkLogin");
            if (!lk || !s.logado) return;
            var conta = document.createElement("a");
            conta.href = "minha_conta.php";
            conta.textContent = "Minha conta";
            lk.parentNode.insertBefore(conta, lk);
            if (s.tipo === "admin") {
                var a = document.createElement("a");
                a.href = "admin.php";
                a.textContent = "Admin";
                lk.parentNode.insertBefore(a, lk);
            }
            lk.textContent = "Sair (" + s.nome.split(" ")[0] + ")";
            lk.href = "logout.php";
        })
        .catch(function () {});

    // Mensagens vindas do PHP pelo endereço (?erro=... ou ?cadastro=ok)
    var q = new URLSearchParams(location.search);
    var erros = {
        campos: "Preencha todos os campos.",
        email_invalido: "E-mail inválido.",
        senha_curta: "A senha deve ter pelo menos 6 caracteres.",
        senhas_diferentes: "As senhas não conferem.",
        email_existe: "Este e-mail já está cadastrado.",
        login: "E-mail ou senha incorretos.",
        pagamento: "Confira o nome, o valor e a forma de pagamento.",
        erro: "Ocorreu um erro. Tente novamente."
    };
    if (q.get("erro")) aviso(erros[q.get("erro")] || erros.erro, false);
    if (q.get("pagamento") === "ok") aviso("Pagamento registrado! Obrigado!", true);
    if (q.get("cadastro") === "ok") aviso("Cadastro realizado! Agora é só entrar.", true);

    // Slideshow do banner: fotos da pasta "imagens" (fotos do Unsplash, licença livre)
    var slide = document.getElementById("slide");
    if (slide) {
        var imagens = [
            "imagens/foto1.jpg",
            "imagens/foto2.jpg",
            "imagens/foto3.jpg",
            "imagens/foto4.jpg",
            "imagens/foto5.jpg",
            "imagens/foto6.jpg",
            "imagens/foto7.jpg",
            "imagens/foto8.jpg"
        ];
        var i = 0;
        // Se alguma foto não existir na pasta, ela sai da lista e o slideshow segue com as outras
        slide.onerror = function () {
            imagens.splice(i, 1);
            if (imagens.length === 0) return;
            i = i % imagens.length;
            slide.src = imagens[i];
        };
        setInterval(function () {
            if (imagens.length < 2) return;
            i = (i + 1) % imagens.length;
            slide.src = imagens[i];
        }, 4000);
    }

    iniciarPagamento();
});
