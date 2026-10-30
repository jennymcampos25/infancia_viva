javascript
/* ===== Utilidades ===== */
function aviso(msg, ok) {
    var el = document.getElementById("aviso");

    if (el) {
        el.textContent = msg;
        el.className = "aviso " + (ok ? "ok" : "erro");
    }

    return false;
}


/* ===== Doação ===== */
function doar() {
    alert("Obrigado por querer fazer parte da Infância Viva!");
    window.location.href = "doacao.php";
}


function validarDoacao() {
    var nome = document.getElementsByName("nome")[0];
    var email = document.getElementsByName("email")[0];
    var telefone = document.getElementsByName("telefone")[0];
    var brinquedo = document.getElementsByName("brinquedo")[0];
    var quantidade = document.getElementsByName("quantidade")[0];
    var data = document.getElementsByName("data")[0];
    var endereco = document.getElementsByName("endereco")[0];
    var valor = document.getElementsByName("valor")[0];
    var ponto = document.getElementsByName("ponto")[0];

    if (
        !nome || !email || !telefone || !brinquedo ||
        !quantidade || !data || !endereco || !valor || !ponto
    ) {
        alert("Não foi possível encontrar todos os campos do formulário.");
        return false;
    }

    if (
        nome.value == "" ||
        email.value == "" ||
        telefone.value == "" ||
        brinquedo.value == "" ||
        quantidade.value == "" ||
        data.value == "" ||
        endereco.value == "" ||
        valor.value == "" ||
        ponto.value == ""
    ) {
        alert("Por favor, preencha todos os campos obrigatórios.");
        return false;
    }

    // Se escolheu "Outro valor", precisa digitar um valor maior que zero
    if (valor.value == "outro") {
        var campoOutro = document.getElementById("campoOutroValor");

        if (!campoOutro || campoOutro.value == "" || Number(campoOutro.value) <= 0) {
            alert("Digite o valor que deseja doar ou escolha outra opção.");
            return false;
        }
    }

    return true;
}


function mostrarOutroValor() {
    var campoValor = document.getElementById("valor");
    var outroValor = document.getElementById("outroValor");

    if (!campoValor || !outroValor) {
        return;
    }

    if (campoValor.value == "outro") {
        outroValor.innerHTML =
            '<label for="campoOutroValor">Digite o valor:</label>' +
            '<input type="number" name="outroValor" id="campoOutroValor" step="0.01" min="0.01" onkeydown="confirmarValor(event)">';
    } else {
        outroValor.innerHTML = "";
    }
}


function confirmarValor(event) {
    if (event.key == "Enter") {
        event.preventDefault();

        var campoValor = document.getElementById("campoOutroValor");

        if (campoValor && campoValor.value != "") {
            alert("Você escolheu doar R$ " + campoValor.value + ".");
        }
    }
}


/* ===== Login e cadastro =====
   São feitos pelo PHP (login.php, cadastro.php, logout.php).
*/


/* ===== Pagamento (simulado) ===== */
function iniciarPagamento() {
    var f = document.getElementById("formPagamento");

    if (!f) {
        return;
    }

    var v = new URLSearchParams(location.search).get("valor");

    if (v && !isNaN(v) && f.valor) {
        f.valor.value = v;
    }

    var m = f.metodo;

    if (!m) {
        return;
    }

    function alternar() {
        var boxPix = document.getElementById("boxPix");
        var boxCartao = document.getElementById("boxCartao");

        if (boxPix) {
            boxPix.hidden = m.value !== "pix";
        }

        if (boxCartao) {
            boxCartao.hidden = m.value !== "cartao";
        }
    }

    m.addEventListener("change", alternar);
    alternar();
}


/* ===== Inicialização ===== */
document.addEventListener("DOMContentLoaded", function () {

    /*
       Menu:
       o link "Login" vira "Sair" quando existe uma sessão no servidor.
    */
    fetch("sessao.php", { credentials: "same-origin" })
        .then(function (r) {
            return r.json();
        })
        .then(function (s) {
            var lk = document.getElementById("linkLogin");

            if (!lk || !s.logado) {
                return;
            }

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

            var primeiroNome = "";

            if (s.nome) {
                primeiroNome = s.nome.split(" ")[0];
            }

            lk.textContent = primeiroNome != ""
                ? "Sair (" + primeiroNome + ")"
                : "Sair";

            lk.href = "logout.php";
        })
        .catch(function () {
            // Se não houver sessão disponível, mantém o menu normalmente.
        });


    /*
       Mensagens vindas do PHP pelo endereço:
       ?erro=...
       ?cadastro=ok
       ?pagamento=ok
    */
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

    if (q.get("erro")) {
        aviso(erros[q.get("erro")] || erros.erro, false);
    }

    if (q.get("pagamento") === "ok") {
        aviso("Pagamento registrado! Obrigado 💗", true);
    }

    if (q.get("cadastro") === "ok") {
        aviso("Cadastro realizado! Agora é só entrar.", true);
    }


    /*
       Slideshow do banner.
       As imagens ficam dentro da pasta "imagens".
    */
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

        /*
           Se alguma imagem não existir, ela é retirada da lista
           e o slideshow continua com as outras.
        */
        slide.onerror = function () {
            imagens.splice(i, 1);

            if (imagens.length === 0) {
                return;
            }

            i = i % imagens.length;
            slide.src = imagens[i];
        };

        setInterval(function () {
            if (imagens.length < 2) {
                return;
            }

            i = (i + 1) % imagens.length;
            slide.src = imagens[i];
        }, 4000);
    }


    /* Inicia as funções da página de pagamento, quando ela existir */
    iniciarPagamento();
});