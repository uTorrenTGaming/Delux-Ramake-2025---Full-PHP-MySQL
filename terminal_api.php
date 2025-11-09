<?php
session_start();
include "conexao.php"; // Inclui sua conexão com o banco

// --- 1. SEGURANÇA: VERIFICAR PERMISSÃO DE SUPER ADMIN ---
header('Content-Type: application/json');
if (!isset($_SESSION["user_id"]) || $_SESSION["is_admin"] != 2) {
    echo json_encode(['success' => false, 'output' => 'ERRO: Acesso negado. Permissao de Super Admin necessaria.']);
    exit;
}

$admin_id = $_SESSION['user_id'];
$output = '';
$success = true;

try {
    // --- 2. PEGAR E PARSEAR O COMANDO ---
    $data = json_decode(file_get_contents('php://input'), true);
    $command_full = trim($data['command'] ?? '');
    
    // Divide o comando em partes. Ex: "give_saldo 2 500" -> ["give_saldo", "2", "500"]
    $parts = preg_split('/\s+/', $command_full); // Divide por espaços
    $command = strtolower($parts[0] ?? ''); // O comando principal
    $args = array_slice($parts, 1); // Os argumentos

    // --- 3. EXECUTAR O COMANDO ---
    switch ($command) {

        case 'list_users':
            $sql = "SELECT id, nome, email, saldo, is_admin FROM usuarios ORDER BY id ASC";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                // Formata a saída como uma tabela de texto
                $output = str_pad("ID", 5) . str_pad("NOME", 20) . str_pad("NIVEL", 13) . "SALDO\n";
                $output .= str_repeat("-", 50) . "\n";
                while ($row = $result->fetch_assoc()) {
                    $nivel = 'Usuario';
                    if ($row['is_admin'] == 1) $nivel = 'Admin';
                    if ($row['is_admin'] == 2) $nivel = 'Super Admin';
                    
                    $output .= str_pad($row['id'], 5);
                    $output .= str_pad(substr($row['nome'], 0, 18), 20); // Limita o nome
                    $output .= str_pad($nivel, 13);
                    $output .= "R$ " . number_format($row['saldo'], 2, ',', '.');
                    $output .= "\n";
                }
            } else {
                $output = "Nenhum usuario encontrado.";
            }
            break;

        case 'give_saldo':
        case 'take_saldo':
            if (count($args) != 2 || !is_numeric($args[0]) || !is_numeric($args[1])) {
                throw new Exception("Uso: $command [user_id] [valor]");
            }
            $user_id_alvo = (int)$args[0];
            $valor = (float)$args[1];
            
            if ($valor <= 0) throw new Exception("Valor deve ser positivo.");
            if ($command == 'take_saldo') $valor = -$valor; // Inverte para remover
            
            $sql = $conn->prepare("UPDATE usuarios SET saldo = saldo + ? WHERE id = ?");
            $sql->bind_param("di", $valor, $user_id_alvo);
            $sql->execute();
            
            if ($sql->affected_rows > 0) {
                $acao = $valor > 0 ? "adicionado" : "removido";
                $output = "✅ Saldo (R$ " . number_format(abs($valor), 2, ',', '.') . ") $acao para o usuario ID $user_id_alvo.";
            } else {
                throw new Exception("Usuario ID $user_id_alvo nao encontrado.");
            }
            break;

        case 'notify':
            if (count($args) < 2 || !is_numeric($args[0])) {
                throw new Exception("Uso: notify [user_id] [mensagem...]");
            }
            $user_id_alvo = (int)$args[0];
            $mensagem = implode(' ', array_slice($args, 1)); // Pega todo o resto como mensagem
            $titulo = "Msg do Admin";

            $sql = $conn->prepare("INSERT INTO notificacoes (user_id_destino, titulo, mensagem) VALUES (?, ?, ?)");
            $sql->bind_param("iss", $user_id_alvo, $titulo, $mensagem);
            $sql->execute();
            
            if ($sql->affected_rows > 0) {
                $output = "✅ Notificacao enviada para o usuario ID $user_id_alvo.";
            } else {
                throw new Exception("Falha ao enviar notificacao (ID $user_id_alvo existe?).");
            }
            break;

        case 'promote':
        case 'demote':
            if (count($args) != 1 || !is_numeric($args[0])) {
                throw new Exception("Uso: $command [user_id]");
            }
            $user_id_alvo = (int)$args[0];
            $novo_nivel = ($command == 'promote') ? 1 : 0; // 1 = Admin, 0 = Usuario
            
            if ($user_id_alvo == $admin_id) {
                throw new Exception("Voce nao pode alterar seu proprio nivel.");
            }
            
            $sql = $conn->prepare("UPDATE usuarios SET is_admin = ? WHERE id = ? AND is_admin != 2"); // Trava para não afetar Super Admins
            $sql->bind_param("ii", $novo_nivel, $user_id_alvo);
            $sql->execute();
            
            if ($sql->affected_rows > 0) {
                $acao = $novo_nivel == 1 ? "promovido a Admin" : "rebaixado a Usuario";
                $output = "✅ Usuario ID $user_id_alvo foi $acao.";
            } else {
                throw new Exception("Usuario ID $user_id_alvo nao encontrado, ou ja esta nesse nivel, ou e Super Admin.");
            }
            break;

        case 'remove_user':
            if (count($args) != 1 || !is_numeric($args[0])) {
                throw new Exception("Uso: remove_user [user_id]");
            }
            $user_id_alvo = (int)$args[0];
            
            if ($user_id_alvo == $admin_id) {
                throw new Exception("Voce nao pode remover a si mesmo.");
            }
            
            $sql = $conn->prepare("DELETE FROM usuarios WHERE id = ? AND is_admin != 2"); // Trava para não afetar Super Admins
            $sql->bind_param("i", $user_id_alvo);
            $sql->execute();
            
            if ($sql->affected_rows > 0) {
                $output = "✅ Usuario ID $user_id_alvo foi REMOVIDO.";
            } else {
                throw new Exception("Usuario ID $user_id_alvo nao encontrado ou e Super Admin (impossivel remover).");
            }
            break;

        case '':
            $output = ''; // Nenhuma saida para comando vazio
            break;

        default:
            $output = "Comando nao reconhecido: " . htmlspecialchars($command);
            $success = false;
            break;
    }

} catch (Exception $e) {
    // --- 4. CAPTURAR ERROS ---
    $output = "ERRO: " . $e->getMessage();
    $success = false;
}

// --- 5. ENVIAR RESPOSTA JSON ---
$conn->close();
echo json_encode([
    'success' => $success,
    'output' => $output
]);
?>