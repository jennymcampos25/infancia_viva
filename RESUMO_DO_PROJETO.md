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
- **Visual:** estilo.css compartilhado por todas as páginas (roxo #403f9c, fundo #f5f5f5, mesmo menu em todas).
- **Pagamento é simulado:** nenhum gateway; os campos de cartão não têm `name`, então nada de cartão é enviado ou guardado. O ponto para um gateway futuro está comentado em pagamento.php.
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
4. Aparência: será tratada separadamente (menu quebra em telas estreitas, favicon).
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
index.html, doacao.html, sobre.html, faq.html, contato.html, login.html, cadastro.html, pagamento.html, admin.php, admin_usuario.php, admin_limpar.php, minha_conta.php, funcoes.php, atualizar_banco.php, doacao.php, pagamento.php, contato.php, avaliacao.php, cadastro.php, login.php, logout.php, sessao.php, conexao.php, estilo.css, script.js, banco.sql, criar_admin.php, teste_conexao.php, FOTOS.txt (+ pasta imagens com as fotos baixadas pelo usuário).
