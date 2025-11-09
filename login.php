<?php
session_start();
include "conexao.php"; 

if(isset($_SESSION["user_id"])){
    header("Location: index.php");
    exit;
}

$msg = ""; 

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $email = $_POST["email"];
    $senha = $_POST["senha"];

    if(empty($email) || empty($senha)){
        $msg = "⚠️ Preencha todos os campos!";
    } else {
        if(isset($conn) && $conn) {
            $sql = $conn->prepare("SELECT * FROM usuarios WHERE email = ?");
            $sql->bind_param("s", $email);
            $sql->execute();
            $result = $sql->get_result();

            if($result->num_rows == 1){
                $user = $result->fetch_assoc();
                
                if(password_verify($senha, $user["senha"])){
                    $_SESSION["user_id"] = $user["id"];
                    $_SESSION["nome"] = $user["nome"];
                    $_SESSION["foto_perfil"] = $user["foto_perfil"];
                    $_SESSION["tema"] = $user["tema"];
                    $_SESSION["saldo"] = $user["saldo"] ?? 0;
                    $_SESSION["is_admin"] = $user["is_admin"] ?? 0;
                    // ATUALIZADO: Carrega o novo número da conta
                    $_SESSION["numero_conta"] = $user["numero_conta"] ?? null;
                    
                    header("Location: index.php");
                    exit;
                } else {
                    $msg = "❌ E-mail ou senha inválidos.";
                }
            } else {
                $msg = "❌ E-mail ou senha inválidos.";
            }
            $sql->close();
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
    <title>Login - Delux</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }

        /* --- Estilo do Botão (From Uiverse.io by alexmaracinaru) --- */
        .btn-login-custom {
          cursor: pointer;
          font-weight: 700;
          transition: all 0.2s;
          padding: 10px 20px;
          border-radius: 100px;
          background: #cfef00; /* Verde-limão */
          color: #111827; /* Texto escuro (para contraste) */
          border: 1px solid transparent;
          display: flex;
          align-items: center;
          font-size: 15px;
        }

        .btn-login-custom:hover {
          background: #c4e201; /* Verde-limão mais escuro */
        }

        .btn-login-custom > svg {
          width: 34px; /* Tamanho do SVG */
          margin-left: 10px;
          transition: transform 0.3s ease-in-out;
          stroke: #111827; /* Cor da seta (escura) */
        }

        .btn-login-custom:hover svg {
          transform: translateX(5px);
        }

        .btn-login-custom:active {
          transform: scale(0.95);
        }
        /* --- Fim do Estilo do Botão --- */
    </style>
</head>
<body class="bg-gray-900 text-white flex items-center justify-center min-h-screen">

    <div class="bg-gray-800 bg-opacity-70 backdrop-blur-lg p-8 md:p-10 rounded-2xl shadow-2xl w-full max-w-md border border-gray-700">
        
        <h1 class="text-3xl font-bold text-center text-yellow-400 mb-8">🔑 Login Delux</h1>

        <form method="POST" action="login.php" class="space-y-6">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-300 mb-2">E-mail:</label>
                <input type="email" id="email" name="email" placeholder="seu@email.com" required
                       class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent">
            </div>
            
            <div>
                <label for="senha" class="block text-sm font-medium text-gray-300 mb-2">Senha:</label>
                <input type="password" id="senha" name="senha" placeholder="Sua senha" required
                       class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent">
            </div>

            <!-- BOTÃO ATUALIZADO -->
            <button type="submit"
                    class="btn-login-custom w-full justify-center focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-900 focus:ring-lime-300">
                <span>Entrar</span>
                <!-- SVG de seta (para combinar com o CSS) -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
            </button>
        </form>

        <?php 
            if(!empty($msg)) {
                echo "<div class='mt-6 p-4 text-center text-sm bg-red-800 border border-red-700 text-red-100 rounded-lg'>$msg</div>";
            }
        ?>

        <p class="mt-8 text-center text-sm text-gray-400">
            Não tem conta? <a href="cadastro.php" class="font-medium text-yellow-400 hover:text-yellow-300">Cadastre-se</a>
        </p>
        
    </div>

</body>
</html>