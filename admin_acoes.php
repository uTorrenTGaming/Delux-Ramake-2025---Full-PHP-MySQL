<?php
session_start();
include "conexao.php";

// 1. VERIFICAÇÃO DE PERMISSÃO
// Apenas Super Admins (nível 2) podem estar aqui.
if (!isset($_SESSION["user_id"]) || $_SESSION["is_admin"] != 2) {
    // Se não for super admin, redireciona com erro
    $_SESSION['admin_msg'] = "❌ Acesso negado.";
    header("Location: perfil_usuario.php#tab5"); // Volta para a aba Super Admin
    exit;
}

$admin_id = $_SESSION['user_id'];
$mensagem_sucesso = "";
$mensagem_erro = "";

// 2. PROCESSAR AÇÕES VINDAS DO FORMULÁRIO
try {
    // --- AÇÃO: MODIFICAR SALDO ---
    if (isset($_POST['admin_modificar_saldo'])) {
        $user_id_alvo = (int)$_POST['user_id_alvo'];
        $valor = (float)$_POST['valor']; // Pode ser positivo ou negativo

        if ($user_id_alvo == 0 || $valor == 0) {
            throw new Exception("ID do usuário ou valor inválido.");
        }

        // Adiciona ou remove o saldo
        $sql = $conn->prepare("UPDATE usuarios SET saldo = saldo + ? WHERE id = ?");
        $sql->bind_param("di", $valor, $user_id_alvo);
        $sql->execute();

        if ($sql->affected_rows > 0) {
            $acao = $valor > 0 ? "adicionado" : "removido";
            $mensagem_sucesso = "✅ Saldo (R$ " . number_format($valor, 2, ',', '.') . ") $acao para o usuário ID $user_id_alvo.";
        } else {
            throw new Exception("Usuário ID $user_id_alvo não encontrado.");
        }
    }

    // --- AÇÃO: REMOVER USUÁRIO ---
    elseif (isset($_POST['admin_remover_usuario'])) {
        $user_id_alvo = (int)$_POST['user_id_alvo'];

        if ($user_id_alvo == 0) {
            throw new Exception("ID do usuário inválido.");
        }
        if ($user_id_alvo == $admin_id) {
            throw new Exception("Você não pode remover a si mesmo.");
        }

        $sql = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
        $sql->bind_param("i", $user_id_alvo);
        $sql->execute();

        if ($sql->affected_rows > 0) {
            $mensagem_sucesso = "✅ Usuário ID $user_id_alvo foi removido com sucesso.";
        } else {
            throw new Exception("Usuário ID $user_id_alvo não encontrado.");
        }
    }

    // --- AÇÃO: PROMOVER/REBAIXAR ADMIN ---
    elseif (isset($_POST['admin_gerenciar_nivel'])) {
        $user_id_alvo = (int)$_POST['user_id_alvo'];
        $novo_nivel = (int)$_POST['novo_nivel']; // 0 = Usuário, 1 = Admin

        if ($user_id_alvo == 0) {
            throw new Exception("ID do usuário inválido.");
        }
        if ($user_id_alvo == $admin_id) {
            throw new Exception("Você não pode alterar seu próprio nível.");
        }
        if ($novo_nivel < 0 || $novo_nivel > 1) {
             throw new Exception("Nível de permissão inválido.");
        }

        $sql = $conn->prepare("UPDATE usuarios SET is_admin = ? WHERE id = ?");
        $sql->bind_param("ii", $novo_nivel, $user_id_alvo);
        $sql->execute();

        if ($sql->affected_rows > 0) {
            $acao = $novo_nivel == 1 ? "promovido a Admin" : "rebaixado a Usuário";
            $mensagem_sucesso = "✅ Usuário ID $user_id_alvo foi $acao.";
        } else {
            throw new Exception("Usuário ID $user_id_alvo não encontrado ou já está nesse nível.");
        }
    }

    // --- AÇÃO: ENVIAR NOTIFICAÇÃO ---
    elseif (isset($_POST['admin_enviar_notificacao'])) {
        $user_id_alvo = (int)$_POST['user_id_alvo'];
        $titulo = trim($_POST['titulo']);
        $mensagem = trim($_POST['mensagem']);

        if ($user_id_alvo == 0 || empty($titulo) || empty($mensagem)) {
            throw new Exception("ID, Título ou Mensagem não podem estar vazios.");
        }
        
        $sql = $conn->prepare("INSERT INTO notificacoes (user_id_destino, titulo, mensagem) VALUES (?, ?, ?)");
        $sql->bind_param("iss", $user_id_alvo, $titulo, $mensagem);
        $sql->execute();
        
        if ($sql->affected_rows > 0) {
            $mensagem_sucesso = "✅ Notificação enviada para o usuário ID $user_id_alvo.";
        } else {
            throw new Exception("Falha ao enviar notificação (verifique se o ID do usuário existe).");
        }
    }

    // Se chegou aqui, foi sucesso
    $_SESSION['admin_msg'] = $mensagem_sucesso;

} catch (Exception $e) {
    // Se deu erro em qualquer 'try'
    $_SESSION['admin_msg'] = "❌ " . $e->getMessage();
}

$conn->close();
// Redireciona de volta para a aba de Super Admin
header("Location: perfil_usuario.php#tab5");
exit;

?>