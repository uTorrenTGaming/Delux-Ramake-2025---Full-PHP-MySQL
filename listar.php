<?php
session_start();
include "conexao.php"; // Inclui a conexão com o banco

// Se a sessão "user_id" não estiver definida, redireciona para o login
if(!isset($_SESSION["user_id"])){
    header("Location: login.php");
    exit;
}

// Pega o ID do usuário logado
$user_id_logado = $_SESSION["user_id"];

// Busca todos os usuários no banco de dados
$sql = "SELECT id, nome, email, foto_perfil FROM usuarios";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lista de Usuários</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    body { 
        font-family: 'Inter', sans-serif; 
        background-color: #111827; /* bg-gray-900 */
        color: #f3f4f6; /* text-gray-100 */
    }
</style>
</head>
<body class="min-h-screen">

<header class="bg-gray-800 shadow-md">
    <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-yellow-400">👥 Lista de Usuários</h1>
        <div>
            <a href="index.php" class="text-gray-200 hover:text-yellow-400 transition mr-4">Início</a>
            <a href="perfil_usuario.php" class="text-gray-200 hover:text-yellow-400 transition mr-4">Meu Perfil</a>
            <a href="logout.php" class="text-red-400 hover:text-red-300 transition">Sair</a>
        </div>
    </nav>
</header>

<main class="container mx-auto px-4 py-8">
    <div class="bg-gray-800 bg-opacity-70 backdrop-blur-lg rounded-xl shadow-2xl border border-gray-700 overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-gray-700 bg-opacity-50">
                <tr>
                    <th class="px-6 py-3 text-sm font-semibold text-yellow-400 uppercase tracking-wider">Foto</th>
                    <th class="px-6 py-3 text-sm font-semibold text-yellow-400 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-sm font-semibold text-yellow-400 uppercase tracking-wider">Nome</th>
                    <th class="px-6 py-3 text-sm font-semibold text-yellow-400 uppercase tracking-wider">E-mail</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                <?php
                if($result && $result->num_rows > 0){
                    while($usuario = $result->fetch_assoc()){
                        // Define a foto de perfil ou o placeholder
                        $cache_buster = "?t=" . time();
                        $foto_url = '';
                        if (!empty($usuario["foto_perfil"])) {
                            // CORRIGIDO: Adicionado /delux-php/ ao caminho
                            $foto_url = "/delux-php/" . htmlspecialchars($usuario["foto_perfil"]) . $cache_buster;
                        } else {
                            $inicial = strtoupper(substr($usuario["nome"], 0, 1));
                            $foto_url = "https://placehold.co/100x100/f4c430/000?text=$inicial";
                        }
                        
                        // Destaca o usuário logado
                        $highlight_class = ($usuario["id"] == $user_id_logado) ? "bg-gray-700" : "hover:bg-gray-700 bg-opacity-50";

                        echo "<tr class='$highlight_class transition duration-200'>";
                        
                        echo "<td class='px-6 py-4'>";
                        echo "  <img src='$foto_url' alt='Foto de " . htmlspecialchars($usuario["nome"]) . "' class='w-10 h-10 rounded-full object-cover border-2 border-gray-600'>";
                        echo "</td>";
                        
                        echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-300'>" . $usuario["id"] . "</td>";
                        
                        echo "<td class='px-6 py-4 whitespace-nowrap font-medium text-white'>";
                        echo htmlspecialchars($usuario["nome"]);
                        if ($usuario["id"] == $user_id_logado) {
                            echo " <span class='ml-2 text-xs text-yellow-400'>(Você)</span>";
                        }
                        echo "</td>";

                        echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-300'>" . htmlspecialchars($usuario["email"]) . "</td>";
                        
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4' class='px-6 py-12 text-center text-gray-400'>Nenhum usuário cadastrado.</td></tr>";
                }
                $conn->close();
                ?>
            </tbody>
        </table>
    </div>
</main>

</body>
</html>