<?php
session_start();
include "conexao.php";

// --- NOVO: Função para gerar o número do cartão ---
if (!function_exists('gerarNumeroContaFormatado')) {
    function gerarNumeroContaFormatado() {
        $num1 = mt_rand(1000, 9999);
        $num2 = mt_rand(1000, 9999);
        $num3 = mt_rand(1000, 9999);
        $num4 = mt_rand(1000, 9999);
        return "$num1 $num2 $num3 $num4";
    }
}

// 1. Verifica se o usuário está logado
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$user_nome = $_SESSION["nome"];
$is_admin = $_SESSION["is_admin"] ?? 0;
$user_saldo = $_SESSION['saldo'] ?? 0;

// --- ATUALIZADO: Lógica para criar número de conta para usuários antigos ---
$user_numero_conta = $_SESSION['numero_conta'] ?? null;

// Se o usuário logou (tem sessão) mas NÃO TEM número da conta (é um usuário antigo)
if (empty($user_numero_conta) && $user_id) {
    $user_numero_conta = gerarNumeroContaFormatado();
    
    // Salva no banco
    $sql_update_conta = $conn->prepare("UPDATE usuarios SET numero_conta = ? WHERE id = ?");
    $sql_update_conta->bind_param("si", $user_numero_conta, $user_id);
    $sql_update_conta->execute();
    $sql_update_conta->close();
    
    // Salva na sessão
    $_SESSION['numero_conta'] = $user_numero_conta;
}
// --- FIM DA LÓGICA ---

// Gera uma data de validade falsa para o cartão
$validade_cartao = date('m/y', strtotime('+5 years'));

$msg_foto = "";
$msg_senha = "";
$msg_carteira = "";
$msg_admin_geral = ""; 

if (isset($_SESSION['admin_msg'])) {
    $msg_admin_geral = $_SESSION['admin_msg'];
    unset($_SESSION['admin_msg']); 
}

$active_tab = "tab1"; 

if (isset($_POST["adicionar_saldo"]) && $is_admin == 1) { 
    $active_tab = "tab3"; 
    $valor_adicionar = (float)$_POST["valor_saldo"];
    
    if ($valor_adicionar > 0) {
        $sql = $conn->prepare("UPDATE usuarios SET saldo = saldo + ? WHERE id = ?");
        $sql->bind_param("di", $valor_adicionar, $user_id);
        
        if ($sql->execute()) {
            $_SESSION["saldo"] = (float)$_SESSION["saldo"] + $valor_adicionar;
            $user_saldo = $_SESSION["saldo"]; 
            $msg_carteira = "<p class='text-green-400'>R$ " . number_format($valor_adicionar, 2, ',', '.') . " adicionados com sucesso!</p>";
        } else {
            $msg_carteira = "<p class='text-red-400'>Erro ao adicionar saldo.</p>";
        }
        $sql->close();
    } else {
        $msg_carteira = "<p class='text-red-400'>Valor inválido.</p>";
    }
}

if (isset($_POST["upload_foto"])) {
    $active_tab = "tab1"; 
    if (isset($_FILES["foto_perfil"]) && $_FILES["foto_perfil"]["error"] == 0) {
        $relative_dir = "assets/uploads/"; 
        $target_dir_absolute = __DIR__ . '/' . $relative_dir; 

        if (!file_exists($target_dir_absolute)) {
            if (!mkdir($target_dir_absolute, 0777, true)) {
                 $msg_foto = "<p class='text-red-400'>Falha ao criar diretório de uploads.</p>";
            }
        }

        if (empty($msg_foto)) {
            $extensao = strtolower(pathinfo($_FILES["foto_perfil"]["name"], PATHINFO_EXTENSION));
            $novo_nome = "user_" . $user_id . "." . $extensao; 
            $target_file_absolute_path = $target_dir_absolute . $novo_nome; 
            $target_file_relative_path = $relative_dir . $novo_nome; 

            $check = getimagesize($_FILES["foto_perfil"]["tmp_name"]);
            if ($check !== false) {
                $fotos_antigas = glob($target_dir_absolute . "user_" . $user_id . ".*");
                foreach($fotos_antigas as $foto) {
                    if ($foto != $target_file_absolute_path) {
                        unlink($foto);
                    }
                }

                if (move_uploaded_file($_FILES["foto_perfil"]["tmp_name"], $target_file_absolute_path)) {
                    $sql = $conn->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id = ?");
                    $sql->bind_param("si", $target_file_relative_path, $user_id);
                    
                    if ($sql->execute()) {
                        $_SESSION["foto_perfil"] = $target_file_relative_path; 
                        $msg_foto = "<p class='text-green-400'>Foto atualizada com sucesso!</p>";
                    } else {
                        $msg_foto = "<p class='text-red-400'>Erro ao salvar no banco de dados.</p>";
                    }
                    $sql->close();
                } else {
                    $msg_foto = "<p class='text-red-400'>Erro ao mover o arquivo.</p>";
                }
            } else {
                $msg_foto = "<p class='text-red-400'>O arquivo não é uma imagem válida.</p>";
            }
        }
        
    } else {
        $msg_foto = "<p class='text-red-400'>Nenhum arquivo enviado ou erro no upload.</p>";
    }
}

if (isset($_POST["trocar_senha"])) {
    $active_tab = "tab2"; 
    $senha_atual = $_POST["senha_atual"];
    $nova_senha = $_POST["nova_senha"];
    $confirma_nova_senha = $_POST["confirma_nova_senha"];

    if (empty($senha_atual) || empty($nova_senha) || empty($confirma_nova_senha)) {
        $msg_senha = "<p class='text-red-400'>Preencha todos os campos de senha.</p>";
    } elseif ($nova_senha !== $confirma_nova_senha) {
        $msg_senha = "<p class='text-red-400'>As novas senhas não coincidem.</p>";
    } else {
        $sql = $conn->prepare("SELECT senha FROM usuarios WHERE id = ?");
        $sql->bind_param("i", $user_id);
        $sql->execute();
        $result = $sql->get_result();
        $user = $result->fetch_assoc();
        $sql->close();

        if ($user && password_verify($senha_atual, $user["senha"])) {
            $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $sql_update = $conn->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
            $sql_update->bind_param("si", $nova_senha_hash, $user_id);
            if ($sql_update->execute()) {
                $msg_senha = "<p class='text-green-400'>Senha alterada com sucesso!</p>";
            } else {
                $msg_senha = "<p class='text-red-400'>Erro ao atualizar a senha.</p>";
            }
            $sql_update->close();
        } else {
            $msg_senha = "<p class='text-red-400'>A senha atual está incorreta.</p>";
        }
    }
}

$cache_buster = "?t=" . time();
$foto_url = '';
if (!empty($_SESSION["foto_perfil"])) {
    $foto_url = "/delux-php/" . htmlspecialchars($_SESSION["foto_perfil"]) . $cache_buster;
} else {
    $inicial = strtoupper(substr($user_nome, 0, 1));
    $foto_url = "https://placehold.co/150x150/f4c430/000?text=$inicial";
}

$notificacoes = [];
$todos_usuarios = []; 

$sql_notif = $conn->prepare("SELECT * FROM notificacoes WHERE user_id_destino = ? ORDER BY data_envio DESC");
$sql_notif->bind_param("i", $user_id);
$sql_notif->execute();
$result_notif = $sql_notif->get_result();
while ($row = $result_notif->fetch_assoc()) {
    $notificacoes[] = $row;
}
$sql_notif->close();

$sql_mark_read = $conn->prepare("UPDATE notificacoes SET lida = 1 WHERE user_id_destino = ? AND lida = 0");
$sql_mark_read->bind_param("i", $user_id);
$sql_mark_read->execute();
$sql_mark_read->close();


if ($is_admin == 2) {
    $result_users = $conn->query("SELECT id, nome, email, saldo, is_admin FROM usuarios ORDER BY id ASC");
    while ($row = $result_users->fetch_assoc()) {
        $todos_usuarios[] = $row;
    }
    $result_users->close();
}

$conn->close();

$tab1_checked = "checked"; 
$tab2_checked = "";
$tab3_checked = "";
$tab4_checked = ""; 
$tab5_checked = ""; 

if ($active_tab == "tab3") {
    $tab1_checked = "";
    $tab3_checked = "checked";
}
if (!empty($msg_admin_geral)) { 
    $tab1_checked = "";
    $tab5_checked = "checked";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Delux</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <!-- NOVO: Fonte para o Cartão -->
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        #image-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #f4c430;
        }
        
        .Documents-btn {
          display: flex; align-items: center; justify-content: flex-start;
          width: fit-content; height: 45px; border: none; padding: 0px 15px;
          border-radius: 5px; background-color: rgb(49, 49, 83);
          gap: 10px; cursor: pointer; transition: all 0.3s;
        }
        .folderContainer {
          width: 40px; height: fit-content; display: flex; flex-direction: column;
          align-items: center; justify-content: flex-end; position: relative;
        }
        .fileBack { z-index: 1; width: 80%; height: auto; fill: #aaa; }
        .filePage {
          width: 50%; height: auto; position: absolute; z-index: 2;
          transition: all 0.3s ease-out; fill: #f4c430;
        }
        .fileFront {
          width: 85%; height: auto; position: absolute; z-index: 3;
          opacity: 0.95; transform-origin: bottom; transition: all 0.3s ease-out; fill: #fff;
        }
        .text {
          color: white; font-size: 14px; font-weight: 600; letter-spacing: 0.5px;
        }
        .Documents-btn:hover .filePage { transform: translateY(-5px); }
        .Documents-btn:hover { background-color: rgb(58, 58, 94); }
        .Documents-btn:active { transform: scale(0.95); }
        .Documents-btn:hover .fileFront { transform: rotateX(30deg); }

        .tab-system {
            width: 100%; display: flex; flex-direction: column; align-items: center;
        }
        .tab-container {
          position: relative; display: flex; flex-direction: row;
          align-items: flex-start; padding: 2px; background-color: #dadadb;
          border-radius: 9px; width: 454px; 
          margin-bottom: 2rem; flex-wrap: wrap; 
          max-width: 100%; justify-content: center;
        }
        .indicator {
          content: ""; width: 90px; height: 28px; background: #ffffff; position: absolute;
          top: 2px; left: 2px; z-index: 9;
          border: 0.5px solid rgba(0, 0, 0, 0.04);
          box-shadow: 0px 3px 8px rgba(0, 0, 0, 0.12), 0px 3px 1px rgba(0, 0, 0, 0.04);
          border-radius: 7px;
          transition: all 0.2s ease-out;
        }
        .tab {
          width: 90px; height: 28px; position: absolute; z-index: 99;
          outline: none; opacity: 0; cursor: pointer;
        }
        .tab--1 { left: 2px; }
        .tab--2 { left: 92px; } 
        .tab--3 { left: 182px; } 
        .tab--4 { left: 272px; }
        .tab--5 { left: 362px; }
        .tab_label {
          width: 90px; height: 28px; position: relative; z-index: 999;
          display: flex; align-items: center; justify-content: center;
          border: 0; font-size: 0.70rem; font-weight: 600; color: #333; opacity: 0.6;
          text-align: center;
        }
        .tab--1:checked ~ .tab-container .indicator { left: 2px; }
        .tab--2:checked ~ .tab-container .indicator { left: calc(90px + 2px); }
        .tab--3:checked ~ .tab-container .indicator { left: calc(90px * 2 + 2px); }
        .tab--4:checked ~ .tab-container .indicator { left: calc(90px * 3 + 2px); }
        .tab--5:checked ~ .tab-container .indicator { left: calc(90px * 4 + 2px); }
        .tab-content {
            display: none; width: 100%; max-width: 36rem;
        }
        .tab-system .tab--1:checked ~ .content--1 { display: block; }
        .tab-system .tab--2:checked ~ .content--2 { display: block; }
        .tab-system .tab--3:checked ~ .content--3 { display: block; }
        .tab-system .tab--4:checked ~ .content--4 { display: block; }
        .tab-system .tab--5:checked ~ .content--5 { display: block; }

        .finmate-card {
          background: linear-gradient(145deg, #1a1a1f, #0e0e10);
          border: 1px solid rgba(255, 255, 255, 0.05);
          border-radius: 20px; padding: 25px;
          box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
          color: white; font-family: "Poppins", sans-serif;
        }
        .finmate-card-balance { margin-bottom: 20px; }
        .finmate-card-label {
          font-size: 0.9rem; color: #b0b0b0; margin-bottom: 5px;
        }
        .finmate-card-amount {
          font-size: 1.8rem; color: #f5f5f5; margin: 0; font-weight: 600;
        }
        
        .notification-item {
            background-color: #2d3748; 
            border-left: 4px solid #f4c430;
        }

        .admin-section {
            border-bottom: 2px solid #4a5568; 
            padding-bottom: 1.5rem; 
            margin-bottom: 1.5rem;
        }
        .admin-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .user-table {
            max-height: 300px;
            overflow-y: auto;
        }
        
        /* --- NOVO: Cartão Flip (From Uiverse.io by Praashoo7) --- */
        .flip-card {
          background-color: transparent;
          width: 240px; /* 240px */
          height: 154px; /* 154px */
          perspective: 1000px;
          color: white;
          font-family: 'Space Mono', monospace; /* Fonte de "cartão" */
        }
        .flip-card-inner {
          position: relative; width: 100%; height: 100%;
          text-align: center; transition: transform 0.8s;
          transform-style: preserve-3d;
        }
        .flip-card:hover .flip-card-inner {
          transform: rotateY(180deg);
        }
        .flip-card-front, .flip-card-back {
          box-shadow: 0 8px 14px 0 rgba(0,0,0,0.2);
          position: absolute; display: flex;
          width: 100%; height: 100%;
          -webkit-backface-visibility: hidden;
          backface-visibility: hidden;
          border-radius: 1rem;
        }
        .flip-card-front {
          box-shadow: rgba(0, 0, 0, 0.4) 0px 2px 2px, rgba(0, 0, 0, 0.3) 0px 7px 13px -3px, rgba(0, 0, 0, 0.2) 0px -1px 0px inset;
          background: linear-gradient(45deg, #1a1a1a, #3a3a3a); /* Gradiente escuro */
          padding: 1rem; /* 16px */
          box-sizing: border-box;
        }
        .flip-card-back {
          box-shadow: rgba(0, 0, 0, 0.4) 0px 2px 2px, rgba(0, 0, 0, 0.3) 0px 7px 13px -3px, rgba(0, 0, 0, 0.2) 0px -1px 0px inset;
          background: linear-gradient(45deg, #1a1a1a, #3a3a3a);
          transform: rotateY(180deg);
          padding-top: 2.4em;
        }
        
        /* Frente do Cartão */
        .card-heading { /* Título "DELUXE BANK" */
          font-size: 0.6em; /* 9.6px */
          letter-spacing: .2em;
          color: #f4c430; /* Amarelo */
          position: absolute; top: 1.5em; right: 1.5em;
        }
        .card-chip { /* Chip */
          position: absolute; top: 2.5em; left: 1.5em;
          width: 40px;
        }
        .card-contactless { /* Símbolo Contactless */
          position: absolute; top: 3.5em; right: 1.5em;
          width: 28px;
        }
        .card-number { /* Número da Conta */
          position: absolute; font-weight: bold;
          font-size: 0.8em; /* 12.8px */
          top: 6.5em; left: 1.5em;
          letter-spacing: 0.1em;
        }
        .card-date-label { /* "VALID THRU" */
          position: absolute; font-weight: normal;
          font-size: 0.5em; /* 8px */
          top: 8.5em; left: 1.5em;
          color: #aaa;
        }
        .card-date { /* Data */
          position: absolute; font-weight: bold;
          font-size: 0.6em; /* 9.6px */
          top: 9.5em; left: 1.5em;
        }
        .card-name { /* Nome do Usuário */
          position: absolute; font-weight: bold;
          font-size: 0.6em; /* 9.6px */
          top: 11em; left: 1.5em;
          text-transform: uppercase;
        }
        .card-logo { /* Logo (ex: Mastercard) */
          position: absolute; bottom: 1em; right: 1.5em;
          width: 45px;
        }
        
        /* Traseira do Cartão */
        .strip { /* Faixa preta */
          background-color: black; width: 100%;
          height: 2.5em;
          background: repeating-linear-gradient(
            45deg, #303030, #303030 10px, #202020 10px, #202020 20px
          );
        }
        .mstrip { /* Faixa branca assinatura */
          position: absolute; background-color: rgb(255, 255, 255);
          width: 80%; height: 2.5em; top: 6em; left: 1.5em;
          border-radius: 4px;
        }
        .code { /* Saldo (substituindo CVV) */
          font-weight: bold; text-align: right;
          color: black; font-family: 'Inter', sans-serif;
          padding: 0.5em; font-size: 0.8em;
        }
        /* --- FIM: Cartão Flip --- */
        
        
        /* --- INÍCIO: CSS DO TERMINAL (FORNECIDO PELO USUÁRIO E ADAPTADO) --- */
        /* From Uiverse.io by mrhyddenn */
        .terminal-container { /* Classe .container renomeada */
          /* width: 230px; */ /* Removido para responsividade */
          /* height: 194px; */ /* Removido para responsividade */
          
          /* Adicionado para responsividade */
          width: 100%;
          max-width: 48rem; /* max-w-xl */
          margin: 0 auto;
          height: 24rem; /* h-96 */
          display: flex;
          flex-direction: column;
        }

        .terminal_toolbar {
          display: flex;
          height: 30px;
          align-items: center;
          padding: 0 8px;
          box-sizing: border-box;
          border-top-left-radius: 5px;
          border-top-right-radius: 5px;
          background: linear-gradient(#504b45 0%, #3c3b37 100%);
        }

        .butt {
          display: flex;
          align-items: center;
        }

        .btn {
          display: flex;
          justify-content: center;
          align-items: center;
          padding: 0;
          margin-right: 5px;
          font-size: 8px;
          height: 12px;
          width: 12px;
          box-sizing: border-box;
          border: none;
          border-radius: 100%;
          background: linear-gradient(#7d7871 0%, #595953 100%);
          text-shadow: 0px 1px 0px rgba(255,255,255,0.2);
          box-shadow: 0px 0px 1px 0px #41403A, 0px 1px 1px 0px #474642;
        }

        .btn-color {
          background: #ee411a;
        }

        .btn:hover {
          cursor: pointer;
        }

        .btn:focus {
          outline: none;
        }

        .butt--exit {
          background: linear-gradient(#f37458 0%, #de4c12 100%);
        }

        .user {
          color: #d5d0ce;
          margin-left: 6px;
          font-size: 14px;
          line-height: 15px;
        }

        .terminal_body {
          background: rgba(56, 4, 40, 0.9);
          /* height: calc(100% - 30px); */ /* Modificado */
          flex-grow: 1; /* Adicionado */
          padding: 2px 8px 8px 8px; /* Padding ajustado */
          margin-top: -1px;
          font-size: 12px;
          border-bottom-left-radius: 5px;
          border-bottom-right-radius: 5px;
          
          /* Adicionado para funcionalidade */
          overflow-y: auto;
          display: flex;
          flex-direction: column;
          font-family: 'Space Mono', monospace; /* Fonte de terminal */
        }
        
        /* Adicionado para funcionalidade */
        #terminal-output {
            flex-grow: 1;
            color: #dddddd;
        }
        
        /* Adicionado para linhas de output */
        .terminal-line {
            line-height: 1.4;
            white-space: pre-wrap; /* Para quebrar linha e respeitar espaços */
        }
        .terminal-line.output-error {
            color: #ee411a; /* Vermelho */
        }
        .terminal-line.output-success {
            color: #7eda28; /* Verde */
        }
        .terminal-line.prompt-history {
            color: #d5d0ce; /* Cinza claro */
        }


        .terminal_promt {
          display: flex;
        }

        .terminal_promt span {
          margin-left: 4px;
        }

        .terminal_user {
          color: #7eda28;
        }

        .terminal_location {
          color: #4878c0;
        }

        .terminal_bling {
          color: #dddddd;
        }
        
        /* Adicionado: Estilo do Input real */
        #terminal-input {
            background-color: transparent;
            color: #ffffff;
            border: none;
            outline: none;
            flex-grow: 1;
            padding: 0;
            margin: 0;
            margin-left: 6px;
            font-family: 'Space Mono', monospace;
            font-size: 12px;
        }
        
        /* Esconde o cursor original quando o input está focado */
        #terminal-input:focus ~ .terminal_cursor {
            display: block;
        }
        
        .terminal_cursor {
          display: block;
          height: 14px;
          width: 5px;
          margin-left: 10px;
          animation: curbl 1200ms linear infinite;
          
          /* Adicionado: Esconder por padrão, mostrar com JS/focus */
          display: none;
        }

        @keyframes curbl {
          0% { background: #ffffff; }
          49% { background: #ffffff; }
          60% { background: transparent; }
          99% { background: transparent; }
          100% { background: #ffffff; }
        }
        /* --- FIM: CSS DO TERMINAL --- */

    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen">

    <header class="bg-gray-800 shadow-md">
        <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-yellow-400">Meu Perfil</h1>
            <div class="flex items-center space-x-4">
                <a href="index.php" class="text-gray-200 hover:text-yellow-400 transition">Início</a>
            </div>
        </nav>
    </header>

    <main class="container mx-auto px-4 py-8">
        
        <div class="tab-system">

            <input class="tab tab--1" id="tab1" type="radio" name="tabs" <?php echo $tab1_checked; ?>>
            <input class="tab tab--2" id="tab2" type="radio" name="tabs" <?php echo $tab2_checked; ?>>
            <input class="tab tab--3" id="tab3" type="radio" name="tabs" <?php echo $tab3_checked; ?>>
            <input class="tab tab--4" id="tab4" type="radio" name="tabs" <?php echo $tab4_checked; ?>>
            <?php if ($is_admin == 2): ?>
                <input class="tab tab--5" id="tab5" type="radio" name="tabs" <?php echo $tab5_checked; ?>>
            <?php endif; ?>


            <div class="tab-container">
                <label class="tab_label" for="tab1">Perfil</label>
                <label class="tab_label" for="tab2">Senha</label>
                <label class="tab_label" for="tab3">Carteira</label>
                <label class="tab_label" for="tab4">Notificações</label>
                <?php if ($is_admin == 2): ?>
                    <label class="tab_label" for="tab5">Super Admin</label>
                <?php endif; ?>
                
                <div class="indicator"></div>
            </div>

            <!-- Conteúdo da Tab 1: Perfil -->
            <div class="tab-content content--1">
                <div class="bg-gray-800 bg-opacity-70 p-8 rounded-2xl shadow-2xl border border-gray-700 max-w-md mx-auto">
                    <h2 class="text-2xl font-bold text-yellow-400 mb-6 text-center">Foto de Perfil</h2>
                    
                    <form method="POST" action="perfil_usuario.php" enctype="multipart/form-data" class="space-y-6">
                        <div class="flex flex-col items-center space-y-4">
                            <img id="image-preview" src="<?php echo $foto_url; ?>" alt="Preview da Foto de Perfil">
                            
                            <label for="foto_perfil" class="Documents-btn">
                                <div class="folderContainer">
                                    <svg class="fileBack" viewBox="0 0 38 45" xmlns="http://www.w.org/2000/svg"><path d="M38 45H0V0H24.7368L38 13.2812V45Z"></path></svg>
                                    <svg class="filePage" viewBox="0 0 22 28" xmlns="http://www.w.org/2000/svg"><path d="M22 28H0V0H14.2258L22 7.78571V28Z"></path></svg>
                                    <svg class="fileFront" viewBox="0 0 32 39" xmlns="http://www.w3.org/2000/svg"><path d="M32 39H0V0H20.9091L32 11.1176V39Z"></path></svg>
                                </div>
                                <span class="text">Escolher Nova Foto</span>
                            </label>
                            
                            <input type="file" id="foto_perfil" name="foto_perfil" class="hidden" accept="image/png, image/jpeg, image/gif" onchange="previewImage(event)">
                            <span id="file-name" class="text-sm text-gray-400"></span>
                        </div>

                        <button type="submit" name="upload_foto" class="w-full px-4 py-3 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-500 transition duration-300">
                            Salvar Nova Foto
                        </button>
                        
                        <div class="text-center text-sm mt-4">
                            <?php echo $msg_foto; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Conteúdo da Tab 2: Senha -->
            <div class="tab-content content--2">
                <div class="bg-gray-800 bg-opacity-70 p-8 rounded-2xl shadow-2xl border border-gray-700 max-w-md mx-auto">
                    <h2 class="text-2xl font-bold text-yellow-400 mb-6 text-center">Alterar Senha</h2>
                    
                    <form method="POST" action="perfil_usuario.php" class="space-y-6">
                        <div>
                            <label for="senha_atual" class="block text-sm font-medium text-gray-300 mb-2">Senha Atual:</label>
                            <input type="password" id="senha_atual" name="senha_atual" required
                                   class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="nova_senha" class="block text-sm font-medium text-gray-300 mb-2">Nova Senha:</label>
                            <input type="password" id="nova_senha" name="nova_senha" required
                                   class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent">
                        </div>

                        <div>
                            <label for="confirma_nova_senha" class="block text-sm font-medium text-gray-300 mb-2">Confirmar Nova Senha:</label>
                            <input type="password" id="confirma_nova_senha" name="confirma_nova_senha" required
                                   class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent">
                        </div>

                        <button type="submit" name="trocar_senha" class="w-full px-4 py-3 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-500 transition duration-300">
                            Alterar Senha
                        </button>

                        <div class="text-center text-sm mt-4">
                            <?php echo $msg_senha; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Conteúdo da Tab 3: Carteira (ATUALIZADO COM FLIP-CARD) -->
            <div class="tab-content content--3">
                <div class="bg-gray-800 bg-opacity-70 p-8 rounded-2xl shadow-2xl border border-gray-700 max-w-md mx-auto">
                    <h2 class="text-2xl font-bold text-yellow-400 mb-6 text-center">Minha Carteira</h2>
                    
                    <!-- NOVO: Cartão Flip -->
                    <div class="flex justify-center mb-8">
                        <div class="flip-card">
                            <div class="flip-card-inner">
                                <!-- Frente do Cartão -->
                                <div class="flip-card-front">
                                    <span class="card-heading">DELUXE BANK</span>
                                    
                                    <!-- Chip SVG -->
                                    <svg class="card-chip" viewBox="0 0 50 40" xmlns="http://www.w3.org/2000/svg" fill="#f4c430">
                                        <path d="M45,5H5C4.447,5,4,5.447,4,6v28c0,0.553,0.447,1,1,1h40c0.553,0,1-0.447,1-1V6C46,5.447,45.553,5,45,5z M32,26h-4V14h4V26z M26,26h-4V14h4V26z M20,26h-4V14h4V26z M14,26h-4V14h4V26z"/>
                                    </svg>
                                    
                                    <!-- Contactless SVG -->
                                    <svg class="card-contactless" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="#ffffff" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.5 8.5c2.5 0 4.5 2 4.5 4.5s-2 4.5-4.5 4.5M15.5 4.5c4.7 0 8.5 3.8 8.5 8.5s-3.8 8.5-8.5 8.5M15.5 12.5c.8 0 1.5.7 1.5 1.5s-.7 1.5-1.5 1.5"/>
                                        <path d="M4 4.5c4.7 0 8.5 3.8 8.5 8.5S8.7 21.5 4 21.5M4 8.5c2.5 0 4.5 2 4.5 4.5S6.5 17.5 4 17.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    
                                    <span class="card-number"><?php echo htmlspecialchars($user_numero_conta); ?></span>
                                    <span class="card-date-label">VALID THRU</span>
                                    <span class="card-date"><?php echo $validade_cartao; ?></span>
                                    <span class="card-name"><?php echo htmlspecialchars($user_nome); ?></span>
                                    
                                    <!-- Logo Mastercard SVG -->
                                    <svg class="card-logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512">
                                        <path fill="#EB001B" d="M192 32C86 32 0 118 0 224s86 192 192 192c105.9 0 192-86.1 192-192S297.9 32 192 32z"/>
                                        <path fill="#F79E1B" d="M305.1 135.5c-37.2-6-73.2 11.5-94.8 39.5-23.4 30.1-22.8 72.9 1.4 102.4 24.1 29.5 61.2 43.1 96.8 34.8 35.6-8.3 64.9-39.2 71.3-75.1 6.4-35.9-8.3-71.3-39.2-90.8-30.8-19.6-69.5-21.2-101.9-4.7-32.4 16.5-54.3 49.3-56.8 85.1-2.5 35.8 17.2 68.8 48.4 83.1 31.2 14.3 67.5 7.4 90.8-15.9 23.3-23.3 32.7-55.9 27.2-86.9-5.5-31.1-28.5-56.9-58.2-67.4-29.7-10.5-62.1-7.1-88.7 9.5-26.6 16.5-44.5 44.4-49.1 75.1-4.6 30.7 8.3 61.2 30 81.1 21.7 19.9 50.3 29.5 79.7 27.2 29.4-2.3 56.9-16.5 75.1-39.2 18.2-22.8 26.6-50.3 23.9-78.3-2.6-28-16.5-53.7-37.8-70.6-21.3-16.9-47.6-24.8-74.4-22.8-26.8 2-52.3 13.5-70.6 30.8-18.2 17.2-28 41-28 67.4 0 26.3 9.7 51 26.6 70.6 16.9 19.6 39.2 31.2 63.5 34.8 24.3 3.6 48.4-1.4 69.5-13.5 21.1-12.1 37.8-30 47.6-51.7 9.8-21.7 12.1-45.8 6.4-69.5-5.7-23.6-20-44.5-39.2-60.1-19.2-15.6-42.5-23.9-66.9-23.9-24.3 0-47.6 8.3-66.9 23.9-19.2 15.6-32.7 36.5-37.8 60.1-5.1 23.6-2.8 47.7 6.9 69.5 9.7 21.7 26.6 39.5 47.6 51.7 21.1 12.1 45.2 17.2 69.5 13.5 24.3-3.6 46.5-15.2 63.5-34.8 16.9-19.6 26.6-44.4 26.6-70.6 0-26.3-9.7-51-26.6-70.6-16.9-19.6-39.2-31.2-63.5-34.8-24.3-3.6-48.4 1.4-69.5 13.5-21.1 12.1-37.8 30-47.6 51.7-9.8 21.7-12.1 45.8-6.4 69.5 5.7 23.6 20 44.5 39.2 60.1 19.2 15.6 42.5 23.9 66.9 23.9 24.3 0 47.6-8.3 66.9-23.9 19.2-15.6 32.7-36.5 37.8-60.1 5.1-23.6 2.8-47.7-6.9-69.5-9.7-21.7-26.6-39.5-47.6-51.7C259.3 86.1 226 80.5 192 80.5c-34 0-67.3 5.7-97.1 17.2-29.7 11.5-54.3 30.8-70.6 56.2-16.2 25.4-23.9 55.3-23.9 86.3 0 31.1 7.7 61 23.9 86.3 16.2 25.4 40.8 44.7 70.6 56.2 29.7 11.5 63 17.2 97.1 17.2 34 0 67.3-5.7 97.1-17.2 29.7-11.5 54.3-30.8 70.6-56.2 16.2-25.4 23.9-55.3 23.9-86.3 0-31.1-7.7-61-23.9-86.3C359.3 166.3 334.7 147 305.1 135.5z"/>
                                    </svg>
                                </div>
                                
                                <!-- Traseira do Cartão -->
                                <div class="flip-card-back">
                                    <div class="strip"></div>
                                    <div class="mstrip">
                                        <!-- Usamos o saldo aqui em vez do CVV -->
                                        <div class="code">
                                            <span>R$ <?php echo number_format($user_saldo, 2, ',', '.'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Painel de Administrador (Nível 1) -->
                    <?php if ($is_admin == 1): ?>
                        <div class="mt-8 border-t border-gray-700 pt-6">
                            <h3 class="text-lg font-semibold text-center text-yellow-400 mb-4">Painel Admin: Adicionar Saldo (Pessoal)</h3>
                            <form method="POST" action="perfil_usuario.php" class="space-y-4">
                                <div>
                                    <label for="valor_saldo" class="block text-sm font-medium text-gray-300 mb-2">Valor a Adicionar:</label>
                                    <input type="number" id="valor_saldo" name="valor_saldo" min="0.01" step="0.01" required
                                           class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-400"
                                           placeholder="Ex: 1000.00">
                                </div>
                                <button type="submit" name="adicionar_saldo" class="w-full px-4 py-3 bg-green-600 text-white font-bold rounded-lg hover:bg-green-500 transition duration-300">
                                    Adicionar Fundos
                                </button>
                                <div class="text-center text-sm mt-4">
                                    <?php echo $msg_carteira; ?>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                    
                </div>
            </div>

            <!-- Conteúdo da Tab 4: Notificações -->
            <div class="tab-content content--4">
                <div class="bg-gray-800 bg-opacity-70 p-8 rounded-2xl shadow-2xl border border-gray-700 max-w-md mx-auto">
                    <h2 class="text-2xl font-bold text-yellow-400 mb-6 text-center">Notificações</h2>
                    <div class="space-y-4 max-h-96 overflow-y-auto">
                        <?php if (empty($notificacoes)): ?>
                            <p class="text-gray-400 text-center">Você não tem nenhuma notificação.</p>
                        <?php else: ?>
                            <?php foreach ($notificacoes as $notif): ?>
                                <div class="notification-item p-4 rounded-lg shadow-md">
                                    <div class="flex justify-between items-center mb-1">
                                        <h3 class="font-bold text-white text-lg"><?php echo htmlspecialchars($notif['titulo']); ?></h3>
                                        <span class="text-xs text-gray-400"><?php echo date('d/m/Y H:i', strtotime($notif['data_envio'])); ?></span>
                                    </div>
                                    <p class="text-gray-300 text-sm"><?php echo nl2br(htmlspecialchars($notif['mensagem'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Conteúdo da Tab 5: Super Admin (Condicional) -->
            <?php if ($is_admin == 2): ?>
                <div class="tab-content content--5">
                    <!-- Alterado: max-w-md para max-w-4xl (maior) -->
                    <div class="bg-gray-800 bg-opacity-70 p-8 rounded-2xl shadow-2xl border border-gray-700 max-w-4xl mx-auto">
                        <h2 class="text-2xl font-bold text-red-500 mb-6 text-center">PAINEL SUPER ADMIN</h2>
                        
                        <?php if (!empty($msg_admin_geral)): ?>
                            <?php
                                $isSuccess = strpos(trim($msg_admin_geral), "✅") === 0;
                                $msgClass = $isSuccess ? 'bg-green-800 border-green-700 text-green-100' : 'bg-red-800 border-red-700 text-red-100';
                            ?>
                            <div class='mb-6 p-4 text-center text-sm <?php echo $msgClass; ?> rounded-lg'><?php echo $msg_admin_geral; ?></div>
                        <?php endif; ?>

                        <!-- 
                            A PARTIR DAQUI O CONTEÚDO FOI REORGANIZADO PARA
                            MOSTRAR O TERMINAL PRIMEIRO 
                        -->
                        
                        <!-- --- NOVO: TERMINAL --- -->
                        <div class="admin-section">
                            <h3 class="text-lg font-semibold text-yellow-400 mb-4 text-center">Terminal de Comandos</h3>
                            
                            <!-- HTML do Terminal (baseado no CSS do usuário) -->
                            <div class="terminal-container">
                                <div class="terminal_toolbar">
                                    <div class="butt">
                                        <button class="btn btn-color"></button>
                                        <button class="btn"></button>
                                        <button class="btn butt--exit"></button>
                                    </div>
                                    <p class="user">root@deluxe-admin:~</p>
                                </div>
                                <div class="terminal_body" id="terminal-body">
                                    
                                    <!-- Área de Output do Terminal -->
                                    <div id="terminal-output">
                                        <p class="terminal-line">Bem-vindo ao Terminal Super Admin.</p>
                                        <p class="terminal-line">Digite <span class="text-yellow-400">'help'</span> para ver os comandos disponíveis.</p>
                                    </div>
                                    
                                    <!-- Linha de Input do Terminal -->
                                    <div class="terminal_promt">
                                        <span class="terminal_user">root@deluxe-admin:</span>
                                        <span class="terminal_location">~</span>
                                        <span class="terminal_bling">$</span>
                                        <input type="text" id="terminal-input" class="bg-transparent text-white focus:outline-none flex-grow ml-2" autocomplete="off" autofocus>
                                        <span class="terminal_cursor" id="terminal-cursor"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- --- FIM: TERMINAL --- -->


                        <div class="admin-section">
                            <h3 class="text-lg font-semibold text-yellow-400 mb-4">Lista de Usuários</h3>
                            <div class="user-table bg-gray-900 border border-gray-700 rounded-lg p-2">
                                <table class="w-full text-left text-sm">
                                    <thead class="text-xs text-yellow-400 uppercase bg-gray-700">
                                        <tr>
                                            <th class="p-2">ID</th>
                                            <th class="p-2">Nome</th>
                                            <th class="p-2">Saldo (R$)</th>
                                            <th class="p-2">Nível</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-700">
                                        <?php foreach ($todos_usuarios as $user): ?>
                                            <tr class="<?php echo ($user['id'] == $user_id) ? 'bg-blue-900' : ''; ?>">
                                                <td class="p-2"><?php echo $user['id']; ?></td>
                                                <td class="p-2"><?php echo htmlspecialchars($user['nome']); ?></td>
                                                <td class="p-2"><?php echo number_format($user['saldo'], 2, ',', '.'); ?></td>
                                                <td class="p-2">
                                                    <?php 
                                                        if ($user['is_admin'] == 2) echo 'Super Admin';
                                                        elseif ($user['is_admin'] == 1) echo 'Admin';
                                                        else echo 'Usuário';
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="admin-section">
                            <h3 class="text-lg font-semibold text-yellow-400 mb-4">Modificar Saldo de Usuário</h3>
                            <form method="POST" action="admin_acoes.php" class="space-y-4">
                                <div>
                                    <label for="user_id_alvo_saldo" class="block text-sm font-medium text-gray-300 mb-2">ID do Usuário:</label>
                                    <input type="number" name="user_id_alvo" id="user_id_alvo_saldo" required class="w-full px-4 py-3 bg-gray-700 rounded-lg text-white" placeholder="ID">
                                </div>
                                <div>
                                    <label for="valor" class="block text-sm font-medium text-gray-300 mb-2">Valor (use - para remover):</label>
                                    <input type="number" name="valor" id="valor" step="0.01" required class="w-full px-4 py-3 bg-gray-700 rounded-lg text-white" placeholder="Ex: 500.00 ou -100.00">
                                </div>
                                <button type="submit" name="admin_modificar_saldo" class="w-full px-4 py-3 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-500 transition duration-300">
                                    Executar Modificação de Saldo
                                </button>
                            </form>
                        </div>
                        
                        <div class="admin-section">
                            <h3 class="text-lg font-semibold text-yellow-400 mb-4">Enviar Notificação</h3>
                            <form method="POST" action="admin_acoes.php" class="space-y-4">
                                <div>
                                    <label for="user_id_alvo_notif" class="block text-sm font-medium text-gray-300 mb-2">ID do Usuário:</label>
                                    <input type="number" name="user_id_alvo" id="user_id_alvo_notif" required class="w-full px-4 py-3 bg-gray-700 rounded-lg text-white" placeholder="ID">
                                </div>
                                <div>
                                    <label for="titulo" class="block text-sm font-medium text-gray-300 mb-2">Título:</label>
                                    <input type="text" name="titulo" id="titulo" required class="w-full px-4 py-3 bg-gray-700 rounded-lg text-white" placeholder="Título da Mensagem">
                                </div>
                                <div>
                                    <label for="mensagem" class="block text-sm font-medium text-gray-300 mb-2">Mensagem:</label>
                                    <textarea name="mensagem" id="mensagem" rows="3" required class="w-full px-4 py-3 bg-gray-700 rounded-lg text-white" placeholder="Sua mensagem aqui..."></textarea>
                                </div>
                                <button type="submit" name="admin_enviar_notificacao" class="w-full px-4 py-3 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-500 transition duration-300">
                                    Enviar Notificação
                                </button>
                            </form>
                        </div>

                        <div class="admin-section">
                            <h3 class="text-lg font-semibold text-red-500 mb-4">Gerenciamento Perigoso</h3>
                            <form method="POST" action="admin_acoes.php" class="space-y-4">
                                <div>
                                    <label for="user_id_alvo_danger" class="block text-sm font-medium text-gray-300 mb-2">ID do Usuário:</label>
                                    <input type="number" name="user_id_alvo" id="user_id_alvo_danger" required class="w-full px-4 py-3 bg-gray-700 rounded-lg text-white" placeholder="ID">
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <button type="submit" name="admin_gerenciar_nivel" value="1" onclick="this.form.novo_nivel.value=1" class="w-full px-4 py-3 bg-green-600 text-white font-bold rounded-lg hover:bg-green-500 transition">
                                        Promover (Admin)
                                    </button>
                                    <button type="submit" name="admin_gerenciar_nivel" value="0" onclick="this.form.novo_nivel.value=0" class="w-full px-4 py-3 bg-yellow-600 text-white font-bold rounded-lg hover:bg-yellow-500 transition">
                                        Rebaixar (Usuário)
                                    </button>
                                    <button type="submit" name="admin_remover_usuario" class="w-full px-4 py-3 bg-red-700 text-white font-bold rounded-lg hover:bg-red-600 transition" onclick="return confirm('ATENÇÃO: Isso removerá o usuário permanentemente. Deseja continuar?');">
                                        REMOVER USUÁRIO
                                    </button>
                                </div>
                                <input type="hidden" name="novo_nivel" value="0">
                            </form>
                        </div>

                    </div>
                </div>
            <?php endif; ?>


        </div> 
    </main>

    <script>
        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function(){
                const output = document.getElementById('image-preview');
                output.src = reader.result;
            };
            if(event.target.files[0]) {
                reader.readAsDataURL(event.target.files[0]);
                document.getElementById('file-name').textContent = event.target.files[0].name;
            } else {
                output.src = "<?php echo $foto_url; ?>";
                document.getElementById('file-name').textContent = "";
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const hash = window.location.hash; 
            if (hash) {
                const tabInput = document.getElementById(hash.substring(1)); 
                if (tabInput) {
                    document.getElementById('tab1').checked = false;
                    tabInput.checked = true;
                }
            }

             <?php if ($tab5_checked === "checked"): ?>
                setTimeout(() => {
                    const tab5 = document.getElementById('tab5');
                    if(tab5) tab5.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 100);
            <?php endif; ?>
            
            
            // --- INÍCIO: LÓGICA DO TERMINAL ---
            <?php if ($is_admin == 2): ?>
            
            const terminalBody = document.getElementById('terminal-body');
            const terminalOutput = document.getElementById('terminal-output');
            const terminalInput = document.getElementById('terminal-input');
            const terminalCursor = document.getElementById('terminal-cursor');

            // Foca no input ao clicar em qualquer lugar do corpo do terminal
            terminalBody.addEventListener('click', () => {
                terminalInput.focus();
            });
            
            // Mostra o cursor piscando quando o input está focado
            terminalInput.addEventListener('focus', () => {
                terminalCursor.style.display = 'block';
            });
            
            // Esconde o cursor piscando quando o input perde o foco
            terminalInput.addEventListener('blur', () => {
                terminalCursor.style.display = 'none';
            });
            
            // Foca o input por padrão se a aba 5 estiver ativa
            if (document.getElementById('tab5').checked) {
                terminalInput.focus();
            }
            // E também foca se a aba 5 for clicada
            document.getElementById('tab5').addEventListener('change', () => {
                if(document.getElementById('tab5').checked) {
                    terminalInput.focus();
                }
            });

            // Processa o comando ao pressionar "Enter"
            terminalInput.addEventListener('keydown', async (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    
                    const commandText = terminalInput.value.trim();
                    terminalInput.value = ''; // Limpa o input
                    
                    if (commandText === '') {
                        return; // Não faz nada se o comando for vazio
                    }
                    
                    // 1. Exibe o comando digitado no histórico
                    appendOutput(`$ ${commandText}`, 'prompt-history');

                    // 2. Processa comandos do lado do cliente (help, clear)
                    if (commandText.toLowerCase() === 'clear') {
                        terminalOutput.innerHTML = ''; // Limpa o output
                        return;
                    }
                    
                    if (commandText.toLowerCase() === 'help') {
                        const helpText = [
                            "Comandos Disponiveis:",
                            "  help                 - Mostra esta ajuda.",
                            "  clear                - Limpa a tela do terminal.",
                            "  list_users           - Lista todos os usuarios.",
                            "  give_saldo [id] [valor] - Adiciona saldo a um usuario (ex: give_saldo 2 500.50).",
                            "  take_saldo [id] [valor] - Remove saldo de um usuario (ex: take_saldo 3 100).",
                            "  notify [id] [msg]    - Envia uma notificacao (ex: notify 2 Ola, isso e um teste).",
                            "  promote [id]         - Promove um usuario a Admin (Nivel 1).",
                            "  demote [id]          - Rebaixa um Admin para Usuario (Nivel 0).",
                            "  remove_user [id]     - (PERIGOSO) Remove um usuario permanentemente.",
                        ].join('\n');
                        appendOutput(helpText, 'output-default');
                        return;
                    }

                    // 3. Processa comandos do lado do servidor
                    try {
                        const response = await fetch('terminal_api.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ command: commandText })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            appendOutput(result.output, 'output-success');
                        } else {
                            appendOutput(result.output, 'output-error');
                        }
                        
                    } catch (error) {
                        appendOutput(`Erro de conexao: ${error.message}`, 'output-error');
                    }
                }
            });

            // Função helper para adicionar linhas ao output e rolar para o final
            function appendOutput(html, typeClass = 'output-default') {
                const line = document.createElement('pre'); // Usar <pre> preserva formatação e quebras de linha
                line.className = `terminal-line ${typeClass}`;
                line.textContent = html; // Usar textContent previne XSS
                terminalOutput.appendChild(line);
                
                // Rola para o final do corpo do terminal
                terminalBody.scrollTop = terminalBody.scrollHeight;
            }
            
            <?php endif; ?>
            // --- FIM: LÓGICA DO TERMINAL ---
            
        });
    </script>

</body>
</html>