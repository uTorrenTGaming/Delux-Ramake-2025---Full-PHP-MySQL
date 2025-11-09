<?php
session_start();
include "conexao.php";

// Define o tipo de conteúdo como JSON
header('Content-Type: application/json');

// 1. Verifica se o usuário está logado
if (!isset($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não logado.']);
    exit;
}

// 2. Pega os dados enviados pelo JavaScript (fetch)
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['totalPrice']) || !is_numeric($data['totalPrice']) || $data['totalPrice'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'Valor total inválido.']);
    exit;
}

$totalPrice = (float)$data['totalPrice'];
$user_id = $_SESSION["user_id"];

// 3. Lógica de Transação Segura
// Inicia uma transação para garantir que as duas etapas (verificar e subtrair) ocorram com segurança.
$conn->begin_transaction();

try {
    // 3.1. Busca o saldo ATUAL do usuário no banco (com 'FOR UPDATE' para bloquear a linha)
    $sql_saldo = $conn->prepare("SELECT saldo FROM usuarios WHERE id = ? FOR UPDATE");
    $sql_saldo->bind_param("i", $user_id);
    $sql_saldo->execute();
    $result = $sql_saldo->get_result();
    $user = $result->fetch_assoc();
    $sql_saldo->close();

    if (!$user) {
        throw new Exception('Usuário não encontrado.');
    }

    $current_balance = (float)$user['saldo'];

    // 3.2. Verifica se o usuário tem saldo suficiente (verificação no servidor)
    if ($current_balance < $totalPrice) {
        throw new Exception('Saldo insuficiente.');
    }

    // 3.3. Se tiver saldo, subtrai o valor
    $new_balance = $current_balance - $totalPrice;
    $sql_update = $conn->prepare("UPDATE usuarios SET saldo = ? WHERE id = ?");
    $sql_update->bind_param("di", $new_balance, $user_id);
    $sql_update->execute();
    $sql_update->close();

    // 4. Confirma a transação
    $conn->commit();

    // 5. Atualiza a sessão e envia a resposta de sucesso
    $_SESSION['saldo'] = $new_balance;
    echo json_encode(['success' => true, 'newBalance' => $new_balance]);

} catch (Exception $e) {
    // 6. Se algo der errado, desfaz a transação
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>