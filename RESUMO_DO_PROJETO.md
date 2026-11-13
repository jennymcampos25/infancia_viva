# Resumo do projeto: site "Infância Viva" (TCC)

## Contexto
- Site de uma ONG fictícia que arrecada brinquedos para crianças de instituições de acolhimento. É o TCC de um estudante que escreve em português e trabalha em dois computadores (casa e escola), levando tudo em um pendrive.
- Claude não tem acesso ao computador dele nem executou PHP: o fluxo é Claude gerar arquivos, o usuário baixar, testar e mandar prints ou mensagens de erro. Tudo o que está abaixo foi testado pelo usuário no EasyPHP.

## Tecnologia
- HTML, CSS e JavaScript no navegador, PHP no servidor e MySQL.
- Ambiente: EasyPHP Devserver 14.1 VC11 (Apache 2.4.7, **PHP 5.5.8**, MySQL 5.6, phpMyAdmin 4.1.4). Sites ficam em `data\localweb` (a pasta atual é `projects\Tcc_Claudinho`).
- Compatibilidade com PHP 5: não usar o operador `??` nem recursos do PHP 7+. `password_hash()` funciona (a partir do 5.5).
- phpMyAdmin está em português de Portugal ("Base de Dados", "Importar", "Exportar", "Executar").

## O que está pronto (tudo gravando no banco `infancia_viva`)
- **Páginas:** index (slideshow), doacao, sobre, faq, contato, login, cadastro, pagamento.
- **PHP:** cadastro.php, login.php, logout.php, sessao.php (diz ao JavaScript quem está logado, para o menu mostrar "Minha conta", "Sair" e "Admin"), doacao.php, pagamento.php, contato.php, avaliacao.php, admin.php, admin_usuario.php, admin_limpar.php, minha_conta.php, funcoes.php (proteção de login/admin, tabelas e histórico, usado pelas páginas de conta e admin), atualizar_banco.php.
- **Banco (conexao.php, PDO):** tabelas `usuarios` (senha só como hash, tipo doador/admin), `doacoes`, `pagamentos`, `mensagens`, `avaliacoes`. A estrutura está em `banco.sql`.
- **Ligação com a conta:** doacoes, pagamentos, mensagens e avaliacoes têm `usuario_id` (chave estrangeira para usuarios, ON DELETE SET NULL). Preenchido com o id da sessão quando a pessoa está logada; NULL = visitante sem conta. Doar sem conta continua permitido.
- **Minha conta (minha_conta.php):** o doador logado vê cartões com totais (doações, brinquedos, pagamentos, valor pago, mensagens, avaliações) e a lista de cada registro.
- **Painel admin (admin.php):** protegido no servidor (confere sessão e o tipo no banco a cada acesso). Mostra totais gerais, nota média e a lista de usuários com quantas doações/pagamentos/mensagens/avaliações cada um tem, mais a linha "Visitantes sem conta". Clicar no nome abre admin_usuario.php?id=N (id=0 = visitantes) com cartões de totais e a lista detalhada de tudo. Todo texto de visitantes é escapado ao exibir.
- **Ferramentas de teste (admin.php, fim da página, dentro de um <details> fechado):** 4 botões que enviam POST para admin_limpar.php com uma chave da sessão e pedem confirmação: apagar o último registro (o mais recente entre doações, pagamentos, mensagens, avaliações e contas de doadores), apagar registros de visitantes (usuario_id NULL), apagar contas de doadores junto com tudo o que fizeram, apagar tudo. Nenhum apaga o admin.
- **Formulários:** usam `usuario_logado_id()` (funcoes.php), que confere a conta no banco; se ela foi apagada, a sessão é encerrada e o registro vai como visitante.
- **Slideshow:** fotos locais `imagens/foto1.jpg` a `foto8.jpg` (Unsplash, licença livre; links em FOTOS.txt). Foto que não existir é pulada.
- **Doação:** o valor em dinheiro é opcional (opção "Não, quero doar só o brinquedo"); "Outro valor" vazio é bloqueado no JavaScript.
- **Visual (v1 do upgrade, aguardando o usuário dizer o que mudar):** estilo.css compartilhado; cores em variáveis no topo (roxo #403f9c, amarelo #ffc145, rosa #ff6f91, fundo #f7f6fb); fonte Nunito do Google Fonts (sem internet cai para Segoe UI/Arial). Topo fixo com logo + menu (link da página atual destacado; abaixo de 1100px vira botão ☰), rodapé com contato em todas as páginas, banner com botões, cartões no Início e no Sobre (Valores e Como ajudar), estrelas clicáveis no Contato, FAQ com +, quadro do Pix, tabelas listradas, páginas de admin/Minha conta mais largas (main.largo). favicon.svg resolve o erro do favicon. Emojis foram trocados por ícones de linha em SVG embutidos no HTML (classe .icone, sem dependência externa); estrelas da avaliação usam o símbolo ★; títulos e avisos ficaram sem emoji. Símbolos simples mantidos: ♥ no logo, ☰, ←, →, ✔/✘ nas páginas internas.
- **Pagamento é simulado:** nenhum gateway nem banco. Do cartão, só a bandeira e os 4 últimos dígitos chegam ao servidor (os campos do número e do CVV não têm `name`). Fluxo completo descrito em "Doação em dinheiro em 4 etapas" abaixo.
- O localStorage foi totalmente removido; o site só usa o banco.

## Atualização do banco (versão com usuario_id)
- Banco antigo: logar como admin e abrir `atualizar_banco.php` (cria as colunas e liga doações/mensagens antigas ao usuário pelo e-mail). Pode rodar mais de uma vez. O admin.php redireciona para ele se o banco estiver no formato antigo.
- Precisa rodar em cada computador, e de novo sempre que importar um .sql exportado antes da atualização.

## Credenciais de teste
- Administrador: `admin@infanciaviva.com` / `admin123` (criado por criar_admin.php). **Trocar antes de apresentar.**

## Pendências
1. Senha do admin: o usuário decidiu manter `admin123` (site não será publicado).
2. Apagar `criar_admin.php`, `teste_conexao.php` e `atualizar_banco.php` da pasta do servidor depois dos testes.
3. Fotos do slideshow: o usuário baixa as fotos de FOTOS.txt para a pasta `imagens`.
4. Aparência: primeira versão entregue; o usuário vai dizer os ajustes.
5. Ideias opcionais: botões no painel para apagar mensagens ou marcar doações como entregues.
6. Bug do admin "deslogando": o código do admin.php nunca apaga a sessão; cair no login significa que a sessão não foi enviada (troca entre `localhost` e `127.0.0.1`). O usuário decidiu não tratar. O sobre.html sem script.js (menu mostrava "Login" estando logado) foi corrigido.
7. Apagar `atualizar_banco.php` antes da apresentação (depois de atualizar os dois computadores).

## Como levar o banco entre computadores
- O banco mora no MySQL de cada computador, não na pasta do site.
- Exportar: phpMyAdmin → clicar em `infancia_viva` → Exportar → Rápido, SQL → Executar → salvar `infancia_viva.sql` no pendrive (leva tabelas e registros).
- Importar: no outro computador, criar o banco vazio `infancia_viva` (utf8mb4_general_ci), clicar nele → Importar → escolher o .sql.
- `banco.sql` cria só as tabelas vazias; nesse caso rodar `criar_admin.php` uma vez para recriar o admin.
- Exportar de novo sempre que sair de um computador.

## Computador da escola
- Apaga tudo o que é adicionado ao desligar; lá o usuário espera fazer poucos testes. O ambiente precisa ser montado do pendrive a cada uso.

## Preferências do usuário
- Gosta de passo a passo bem detalhado, com os nomes exatos de botões do EasyPHP e do phpMyAdmin.
- Às vezes se sente inseguro com a parte técnica: manter um tom acolhedor.
- Quando ele pede "arquivos do banco", são só banco.sql, conexao.php, criar_admin.php e teste_conexao.php; não reenviar o site inteiro sem ele pedir.
- O `qrcode_infancia_viva.png` (Pix) é arquivo do usuário e não está no zip.

## Arquivos (todos no zip `infancia_viva_site.zip`)
index.html, doacao.html, sobre.html, faq.html, contato.html, login.html, cadastro.html, pagamento.html, admin.php, admin_usuario.php, admin_limpar.php, minha_conta.php, funcoes.php, atualizar_banco.php, doacao.php, pagamento.php, contato.php, avaliacao.php, cadastro.php, login.php, logout.php, sessao.php, conexao.php, estilo.css, favicon.svg, script.js, banco.sql, criar_admin.php, teste_conexao.php, FOTOS.txt (+ pasta imagens com as fotos baixadas pelo usuário).

## Site no ar (Coolify) e foto de perfil
- O site está hospedado no Coolify (Dockerfile com php:8.2-apache; conexao.php lê DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD). O banco online é separado do EasyPHP. O repositório GitHub jennymcampos25/infancia_viva é a versão do servidor online; a versão local (EasyPHP) é mantida à parte pelo usuário.

- Bolinha de perfil no topo (script.js, iniciarPerfil): sem login mostra imagens/avatares/padrao.svg e leva ao Login; com login mostra a foto e abre um menu (Minha conta, Painel do administrador só para admin, Sair). Substituiu os links Login/Minha conta/Admin/Sair do menu.
- Foto de perfil: escolhida no cadastro e trocada em Minha conta (foto_salvar.php). Pode ser da galeria (o navegador recorta e reduz para 256x256 JPEG antes de enviar) ou uma das 6 prontas (imagens/avatares/avatar1..6.svg). Colunas usuarios.foto (NULL, avatarN ou upload:timestamp) e usuarios.foto_dados (MEDIUMBLOB). foto.php entrega a foto enviada só ao próprio usuário e ao admin. Banco antigo: rodar atualizar_banco.php (o admin.php redireciona para ele).
- Cache: as páginas carregam `estilo.css?v=N` e `script.js?v=N` (hoje v=3 e v=5). Sempre que o estilo.css ou o script.js mudarem, aumentar o N em todas as páginas, para os navegadores baixarem o arquivo novo sem precisar de Ctrl+F5.

## Logo e paleta (identidade visual nova)
- Logo oficial do grupo: "Infância Viva" com a casinha de brinquedo no "a" de "Viva" como símbolo principal. Arquivo: imagens/logo_infancia_viva.png (extraída do PDF com fundo transparente enviado pelo grupo).
- Paleta (variáveis no topo do estilo.css): vermelho #e8572a, amarelo #f5a524 (texto: #a35f00), verde #4f8a3a, azul #2f7a80, escuro #242d32, fundo creme #fff8ee. Fontes: Quicksand nos títulos, Nunito no texto.
- Topo branco (a palavra "Infância" da logo é quase preta e sumiria num fundo escuro), com a casinha em SVG e "Viva" com uma cor por letra. favicon.svg = casinha.
- Página inicial: abertura com a logo grande e fotos trocando numa moldura animada, faixa de números (numeros.php lê do banco: brinquedos doados, doações, doadores; pontos de coleta = 3), cartões, "Como funciona", galeria e chamada com confete. Animações: entrada do texto, brinquedos flutuando, aparecer ao rolar, contadores; todas desligam com "reduzir movimento" do sistema.

## Textos (conceito do projeto)
- A infância é feita de momentos que não voltam: o objetivo é dar a crianças de instituições de acolhimento um brinquedo/lembrança para guardar para sempre. Segundo objetivo: tirar as crianças das telas e fazê-las interagir com o mundo à sua volta.
- Doação: coletas em datas específicas em escolas parceiras; em algumas ações, oficinas de brinquedos com material reciclado (reaproveitamento e empatia). Trajetória curta, com o propósito de deixar a vida das crianças melhor e mais saudável.
- Textos reescritos em index.html ("O que nos move", cartões, "Como funciona"), sobre.html (objetivos, como trabalhamos, trajetória, missão, visão, valores), doacao.html (como funciona a coleta), faq.html e contato.html.
- Pendente: o formulário de doação ainda tem os pontos "Sede / Escola parceira / Centro comunitário" e data livre; não combina 100% com "datas específicas em escolas parceiras".

## Pedidos dos professores (11/11)
- Sobre nós mais chamativo: a página foi refeita no estilo da inicial (abertura com foto, "O que a Infância Viva quer" com uma frase de propósito, objetivos, como trabalhamos, trajetória em linha do tempo com próximos passos, missão e visão em destaque, valores, como ajudar e chamada com confete).
- Painel administrativo com cara de sistema (não parecido com o site): admin.php?pagina=geral|usuarios|doacoes|pagamentos|mensagens|avaliacoes e admin_usuario.php usam painel_inicio()/painel_fim() do funcoes.php: menu lateral escuro, topo com o nome do admin, sem o topo e o rodapé públicos. Visão geral com números e "Atividade recente"; busca nas tabelas (data-filtrar no script.js); mensagens em cartões com "Responder" por e-mail; avaliações com média e barras por nota. No celular, o menu lateral vira uma barra no topo.
- Avatares prontos recoloridos com a paleta da logo (foto_url() devolve ?v=2).

## Login que não cai, e-mails "reais" e edição no painel
- Sessões guardadas no banco (tabela `sessoes`, criada sozinha na primeira visita; também está no banco.sql). Todas as páginas usam iniciar_sessao() (funcoes.php) no lugar de session_start(). Motivos: o Coolify recria o servidor a cada atualização (sessões em arquivo sumiam) e o PHP apagava sessões paradas há 24 min.
- Login com a caixa "Manter conectado neste aparelho": cookie de 30 dias, renovado a cada visita. Sem marcar, o login acaba ao fechar o navegador. Sair apaga o cookie.
- email_real() (funcoes.php): recusa e-mails de domínios que não recebem e-mail (consulta DNS/MX), domínios reservados (example.com, .test...) e descartáveis (mailinator, yopmail...). Usado no cadastro, doação, contato e no painel. Não confirma que a caixa existe: para isso seria preciso enviar e-mail de confirmação (precisa de uma conta SMTP configurada no Coolify). Se o servidor não conseguir consultar DNS, aceita o e-mail para não bloquear ninguém por engano.
- Painel: "+ Novo", "Editar" e "Apagar" em usuários, doações, pagamentos, mensagens e avaliações (registros_config() em funcoes.php define os campos; admin_registro.php é o formulário; admin_salvar.php grava/apaga com a chave_admin()). Regras: o admin não pode apagar nem tirar o acesso de admin da própria conta; o site sempre fica com pelo menos um admin; e-mail de conta não pode repetir; apagar uma conta mantém as doações/mensagens dela como "Visitantes sem conta". Busca também nas mensagens e avaliações.
- Versões: estilo.css?v=5 e script.js?v=7.

## Confirmação de e-mail no cadastro
- Envio por SMTP com a biblioteca PHPMailer 6.12 (só os arquivos necessários, em lib/PHPMailer, licença LGPL). Dados do e-mail da ONG nas variáveis do Coolify (recurso infancia_viva): SMTP_HOST=smtp.gmail.com, SMTP_PORTA=587, SMTP_USUARIO, SMTP_SENHA (senha de app do Google, nunca no código), SMTP_NOME, SITE_URL=https://infanciaviva.artgenial.com.br. Opcional: SMTP_SEGURANCA (ssl, tls ou nenhuma).
- Cadastro novo nasce com email_confirmado=0 e recebe um link (confirmar.php?codigo=..., vale 24 h; no banco fica só o hash do código). O login recusa conta não confirmada e a tela de login mostra "Reenviar link" (reenviar_confirmacao.php, no máximo um e-mail por minuto, resposta igual exista a conta ou não).
- Contas antigas e contas criadas pelo admin já valem como confirmadas (coluna com padrão 1); o admin pode mudar em Editar. Na lista de usuários aparece a etiqueta "não confirmado".
- Sem as variáveis SMTP (ex.: EasyPHP), as contas já nascem confirmadas. As colunas são criadas sozinhas (garantir_colunas_confirmacao()).
- Versões: estilo.css?v=6 e script.js?v=8.

## Doação separada: brinquedo e dinheiro (pedido do professor Anderson)
- doacao.html virou a página de escolha, com dois cartões: "Doe um brinquedo" (doacao_brinquedo.php) e "Doe em dinheiro" (pagamento.html, com o Pix e o pagamento online de demonstração). As duas páginas têm abas no topo para trocar de uma para a outra; o menu continua marcando "Doação" (atributo data-menu no <body>).
- O formulário de brinquedo não pede mais dinheiro. O local de entrega vem da tabela `locais` (nome, tipo, endereço, dias de coleta, ativo), que o admin cadastra em Painel → Locais de coleta. A tabela é criada sozinha com 3 locais iniciais ("a definir") para serem trocados pelos de verdade. doacao.php confere o local no banco e grava o nome dele em doacoes.ponto.
- A faixa de números da página inicial conta os locais ativos. Textos trocados de "escolas parceiras" para "locais de coleta".
- Pendente: a data de entrega ainda é livre (o ideal seria escolher entre os dias de coleta do local); o endereço do doador continua obrigatório. Ideia para depois: aba "Oficina" com vídeos de brinquedos reciclados.
- Versões: estilo.css?v=7 e script.js?v=9.

## Doação em dinheiro em 4 etapas (simulada)
- **1. Valor e forma (pagamento.html → pagamento.php):** botões R$ 20/50/100/200 ou outro valor, Pix ou cartão, nome e e-mail (opcional, para o recibo; já vêm preenchidos se a pessoa estiver logada, via sessao.php). Cria um PEDIDO na tabela pagamentos com status "Aguardando", um `codigo` aleatório (vai no endereço das próximas páginas) e prazo de 15 minutos (`expira_em`).
- **2. Pagamento (pagamento_pedido.php?c=...):** número do pedido (IV-000001), resumo e:
  - **Pix:** QR Code (desenhado pela biblioteca lib/qrcode/qrcode.js, licença MIT), "Pix copia e cola" no padrão do Banco Central (com chave FICTÍCIA, então não cobra nada se alguém colar no banco), contagem do prazo e conferência automática a cada 3 s (pagamento_status.php). O QR Code abre pagamento_simular.php no celular, uma tela de teste que faz o papel do app do banco; ao confirmar lá, a tela do computador vai sozinha para o recibo. Também há o botão "Simular pagamento recebido".
  - **Cartão:** número (com bandeira e espaços automáticos, conferido pelo algoritmo de Luhn), nome, validade e CVV. Tela "Processando pagamento…". Cartões de teste: 4111 1111 1111 1111 aprovado, 4000 0000 0000 0002 recusado.
  - "Cancelar pedido" muda o status para Cancelado. Passado o prazo, o pedido vira Expirado.
- **3 e 4. Confirmação e recibo (pagamento_recibo.php?c=...):** "Pagamento confirmado!" e o recibo (número, valor por extenso, data, forma, código de autenticação, aviso de demonstração sem valor fiscal). "Imprimir recibo" imprime só o recibo (@media print) e "Voltar para o início". Se a pessoa deu e-mail, o recibo também é enviado por e-mail.
- **Banco:** colunas novas em pagamentos (codigo, email, detalhe, expira_em, pago_em, recibo_enviado), criadas sozinhas pelo garantir_colunas_pagamento() em funcoes.php. Status: Aguardando, Pago, Expirado, Cancelado ("Simulado"/"Confirmado" são do formato antigo e contam como pagos).
- **Painel e Minha conta:** mostram o número do pedido, a forma (ex.: "Cartão Visa final 1111") e a situação; os totais somam só os pedidos pagos.
- Topo e rodapé das páginas PHP novas vêm de site_inicio()/site_fim() em funcoes.php.


## Contatos
- E-mail infanciavivaong8@gmail.com, telefone (21) 95555-3333 e Instagram @onginfanciaviva_ em todos os rodapés (com links mailto:, tel: e para o Instagram) e em cartões na página Contato.

## Alinhamento com a documentação do TCC
- Sobre: foto nova no topo (sobre_maos_dadas.jpg), seção "Brincar é um direito" (ECA art. 16 e Constituição art. 227) com sobre_blocos.jpg, missão/visão com o texto da documentação, 6 valores da documentação (Solidariedade, Empatia, Respeito, Responsabilidade social, Inclusão, Compromisso com as crianças), seção "Nossos compromissos" (Transparência e Proteção das crianças), campanhas no Dia das Crianças e no Natal, avaliação dos brinquedos por segurança e faixa etária.
- Removido o tema "tirar as crianças das telas" (não está na documentação); no lugar, o desenvolvimento pelo brincar.
- Público-alvo: crianças em vulnerabilidade social, principalmente de instituições de acolhimento (Início, Sobre, FAQ).
- FAQ: livros infantis e jogos educativos, critérios de avaliação, parcerias com escolas/empresas, transparência.
- Contato: cartão da sede (NEAC, Rua Cantor Emílio Santiago, 118, Campo Grande), cidade no rodapé; tipo de local "Comércio ou empresa".

- Removidas as oficinas de brinquedos reciclados (não estão na documentação). O dinheiro agora "ajuda no transporte, nas embalagens e na divulgação das campanhas" (documentação 6.5). Como trabalhamos: coletas e campanhas, avaliação e separação, entrega.

## Preenchimento automático, pesquisa do painel e estoque
- Formulários com data-preencher (Doe um brinquedo, Doe em dinheiro, Contato): quem está logado recebe nome e e-mail da conta, e telefone e endereço da última doação (sessao.php devolve esses dados só para a própria pessoa). Só preenche campos vazios e mostra o aviso "Preenchemos com os dados da sua conta".
- Painel: pesquisa no topo com categoria (Tudo, Doações de brinquedos, Estoque, Pagamentos, Mensagens, Avaliações, Usuários, Locais) em admin.php?pagina=busca&cat=...&q=... Categoria sem texto mostra tudo daquela categoria.
- Estoque de brinquedos (admin.php?pagina=estoque, tabela estoque criada sozinha por garantir_tabela_estoque()): brinquedo, categoria, quantidade, faixa etária, situação (Em avaliação, Em estoque, Entregue, Descartado), doador, recebido em, instituição e data de entrega. Em Doações, o botão "Receber no estoque" cria o registro já preenchido e ligado à doação (doacao_id). O botão "Entregar" abre o registro com situação Entregue e a data de hoje; a instituição é obrigatória. Filtros por situação e contagem em estoque por faixa etária.
- Minha conta: cada doação mostra a situação (Aguardando entrega no local, Recebida pela ONG, Entregue a uma instituição).
