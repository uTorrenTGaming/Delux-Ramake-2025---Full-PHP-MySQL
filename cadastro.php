<?php
include "conexao.php"; 
$msg = ""; 

// --- NOVO: Função para gerar o número do cartão ---
if (!function_exists('gerarNumeroContaFormatado')) {
    function gerarNumeroContaFormatado() {
        // Gera 4 blocos de 4 números
        $num1 = mt_rand(1000, 9999);
        $num2 = mt_rand(1000, 9999);
        $num3 = mt_rand(1000, 9999);
        $num4 = mt_rand(1000, 9999);
        // Retorna formatado
        return "$num1 $num2 $num3 $num4";
    }
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $nome = $_POST["nome"];
    $email = $_POST["email"];
    $senha = $_POST["senha"];
    $confirma_senha = $_POST["confirma_senha"];

    if(empty($nome) || empty($email) || empty($senha) || empty($confirma_senha)){
        $msg = "⚠️ Preencha todos os campos!";
    } elseif ($senha !== $confirma_senha) {
        $msg = "⚠️ As senhas não coincidem!";
    } else {
        if(isset($conn) && $conn) {
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

            $check = $conn->prepare("SELECT * FROM usuarios WHERE email=?");
            $check->bind_param("s", $email);
            $check->execute();
            $result = $check->get_result();

            if($result->num_rows > 0){
                $msg = "⚠️ E-mail já cadastrado!";
            } else {
                // ATUALIZADO: Gera o número da conta
                $novo_numero_conta = gerarNumeroContaFormatado();

                // ATUALIZADO: Adiciona saldo (50000), admin (0) e o novo numero_conta
                $sql = $conn->prepare("INSERT INTO usuarios (nome, email, senha, tema, saldo, is_admin, numero_conta) VALUES (?, ?, ?, 'light', 50000.00, 0, ?)");
                // ATUALIZADO: "sss" -> "ssss" para o novo campo
                $sql->bind_param("ssss", $nome, $email, $senhaHash, $novo_numero_conta);
                
                if($sql->execute()){
                    $msg = "✅ Usuário cadastrado! Você já pode fazer o login.";
                } else {
                    $msg = "❌ Erro ao cadastrar usuário.";
                }
                $sql->close();
            }
            $check->close();
            $conn->close();
        } else {
            $msg = "Erro ao conectar ao banco de dados.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Delux</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-900 text-white flex items-center justify-center min-h-screen py-12">

    <div class="bg-gray-800 bg-opacity-70 backdrop-blur-lg p-8 md:p-10 rounded-2xl shadow-2xl w-full max-w-md border border-gray-700">
        
        <h1 class="text-3xl font-bold text-center text-yellow-400 mb-8">👤 Criar Conta</h1>

        <form method="POST" action="cadastro.php" class="space-y-6">
            <div>
                <label for="nome" class="block text-sm font-medium text-gray-300 mb-2">Nome:</label>
                <input type="text" id="nome" name="nome" placeholder="Seu nome completo" required
                       class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-300 mb-2">E-mail:</label>
                <input type="email" id="email" name="email" placeholder="seu@email.com" required
                       class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent">
            </div>
            
            <div>
                <label for="senha" class="block text-sm font-medium text-gray-300 mb-2">Senha:</label>
                <input type="password" id="senha" name="senha" placeholder="Crie uma senha forte" required
                       class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent">
            </div>

            <div>
                <label for="confirma_senha" class="block text-sm font-medium text-gray-300 mb-2">Confirmar Senha:</label>
                <input type="password" id="confirma_senha" name="confirma_senha" placeholder="Repita a senha" required
                       class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent">
            </div>

            <button type="submit"
                    class="w-full px-4 py-3 bg-yellow-400 text-gray-900 font-bold rounded-lg hover:bg-yellow-300 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-opacity-50 transition duration-300">
                Cadastrar
            </button>
        </form>

        <?php 
            if(!empty($msg)) {
                $isSuccess = strpos(trim($msg), "✅") === 0;
                $msgClass = $isSuccess ? 'bg-green-800 border-green-700 text-green-100' : 'bg-red-800 border-red-700 text-red-100';
                echo "<div class='mt-6 p-4 text-center text-sm $msgClass rounded-lg'>$msg</div>";
            }
        ?>

        <p class="mt-8 text-center text-sm text-gray-400">
            Já tem conta? <a href="login.php" class="font-medium text-yellow-400 hover:text-yellow-300">Fazer login</a>
        </p>
        
    </div>

</body>
</html>