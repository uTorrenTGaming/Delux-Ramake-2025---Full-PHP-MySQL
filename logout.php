<?php
// 1. Inicia a sessão para poder acessá-la
session_start();

// 2. Remove todas as variáveis da sessão
session_unset();

// 3. Destrói a sessão
session_destroy();

// 4. Redireciona o usuário para a página de login
header("Location: login.php");
exit;
?>