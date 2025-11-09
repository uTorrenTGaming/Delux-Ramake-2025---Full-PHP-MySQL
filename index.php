<?php
session_start();

// 1. Verifica se o usuário está logado
if(!isset($_SESSION["user_id"])){
    header("Location: login.php");
    exit;
}

// 2. Pega os dados da sessão
$user_id = $_SESSION["user_id"];
$user_nome = $_SESSION["nome"];
$user_saldo = $_SESSION["saldo"] ?? 0;
$is_admin = $_SESSION["is_admin"] ?? 0;

// 3. Define a URL da foto de perfil
$cache_buster = "?t=" . time();
$foto_url = '';
if (!empty($_SESSION["foto_perfil"])) {
    $foto_url = "/delux-php/" . htmlspecialchars($_SESSION["foto_perfil"]) . $cache_buster;
} else {
    $inicial = strtoupper(substr($user_nome, 0, 1));
    $foto_url = "https://placehold.co/100x100/f4c430/000?text=$inicial";
}

// 4. NOVO: Verifica Notificações Não Lidas
include "conexao.php"; // Inclui a conexão
$unread_notifications = 0;
$sql_notif = $conn->prepare("SELECT COUNT(*) as unread_count FROM notificacoes WHERE user_id_destino = ? AND lida = 0");
$sql_notif->bind_param("i", $user_id);
$sql_notif->execute();
$result_notif = $sql_notif->get_result();
$notif_data = $result_notif->fetch_assoc();
if ($notif_data) {
    $unread_notifications = (int)$notif_data['unread_count'];
}
$sql_notif->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deluxe Edition</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        #video-container::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(0, 0, 0, 0.5); z-index: 1;
        }

        /* --- Animação Modal (Controle) --- */
        #animationModal {
            transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
            opacity: 0; visibility: hidden;
        }
        #animationModal.show { opacity: 1; visibility: visible; }
        
        /* --- NOVA Animação Caminhão (From Uiverse.io by vinodjangid07) --- */
        .loader {
          width: fit-content;
          height: fit-content;
          display: flex;
          align-items: center;
          justify-content: center;
        }

        .truckWrapper {
          width: 200px; /* Largura da animação */
          height: 100px;
          display: flex;
          flex-direction: column;
          position: relative;
          align-items: center;
          justify-content: flex-end;
          overflow-x: hidden;
        }
        /* truck upper body */
        .truckBody {
          width: 130px;
          height: auto; /* Altura auto para o SVG */
          margin-bottom: 6px;
          animation: motion 1s linear infinite;
        }
        /* truck suspension animation*/
        @keyframes motion {
          0% {
            transform: translateY(0px);
          }
          50% {
            transform: translateY(3px);
          }
          100% {
            transform: translateY(0px);
          }
        }
        /* truck's tires */
        .truckTires {
          width: 130px;
          height: fit-content;
          display: flex;
          align-items: center;
          justify-content: space-between;
          padding: 0px 10px 0px 15px; /* Ajustado para pneus */
          position: absolute;
          bottom: 0;
        }
        .truckTires svg {
          width: 24px;
        }

        /* ATUALIZADO: Cores originais do usuário */
        .road {
          width: 100%;
          height: 1.5px;
          background-color: #282828; /* Estrada escura */
          position: relative;
          bottom: 0;
          align-self: flex-end;
          border-radius: 3px;
        }
        .road::before {
          content: "";
          position: absolute;
          width: 20px;
          height: 100%;
          background-color: #282828; /* Faixa escura */
          right: -50%;
          border-radius: 3px;
          animation: roadAnimation 1.4s linear infinite;
          border-left: 10px solid white; /* Marcação branca */
        }
        .road::after {
          content: "";
          position: absolute;
          width: 10px;
          height: 100%;
          background-color: #282828; /* Faixa escura */
          right: -65%;
          border-radius: 3px;
          animation: roadAnimation 1.4s linear infinite;
          border-left: 4px solid white; /* Marcação branca */
        }

        .lampPost {
          position: absolute;
          bottom: 0;
          right: -90%;
          height: 90px;
          animation: roadAnimation 1.4s linear infinite;
        }

        @keyframes roadAnimation {
          0% {
            transform: translateX(0px);
          }
          100% {
            /* Move 350px para a esquerda */
            transform: translateX(-350px);
          }
        }
        /* --- FIM Nova Animação --- */
        
        
        /* --- Botão Finalizar Compra --- */
        .pay-btn {
          position: relative; padding: 12px 24px; font-size: 16px;
          background: #1a1a1a; color: white; border: none;
          border-radius: 8px; cursor: pointer; display: flex;
          align-items: center; justify-content: center; gap: 10px;
          transition: all 0.3s ease;
        }
        .pay-btn:hover {
          transform: translateY(-2px);
          box-shadow: 0 5px 15px rgba(0, 0, 0, 0.4);
        }
        .icon-container { position: relative; width: 24px; height: 24px; }
        .icon {
          position: absolute; top: 0; left: 0; width: 24px; height: 24px;
          color: #22c55e; opacity: 0; visibility: hidden;
        }
        .default-icon { opacity: 1; visibility: visible; color: #f4c430; }
        .pay-btn:hover .icon { animation: none; }
        .pay-btn:hover .wallet-icon { opacity: 0; visibility: hidden; }
        .pay-btn:hover .card-icon { animation: iconRotate 2.5s infinite; animation-delay: 0s; }
        .pay-btn:hover .payment-icon { animation: iconRotate 2.5s infinite; animation-delay: 0.5s; }
        .pay-btn:hover .dollar-icon { animation: iconRotate 2.5s infinite; animation-delay: 1s; }
        .pay-btn:hover .check-icon { animation: iconRotate 2.5s infinite; animation-delay: 1.5s; }
        .pay-btn:active .icon {
          animation: none; opacity: 0; visibility: hidden; transition: all 0.3s ease;
        }
        .pay-btn:active .check-icon {
          animation: checkmarkAppear 0.6s ease forwards; visibility: visible;
        }
        .btn-text { font-weight: 600; font-family: system-ui, -apple-system, sans-serif; }
        @keyframes iconRotate {
          0% { opacity: 0; visibility: hidden; transform: translateY(10px) scale(0.5); }
          5% { opacity: 1; visibility: visible; transform: translateY(0) scale(1); }
          15% { opacity: 1; visibility: visible; transform: translateY(0) scale(1); }
          20% { opacity: 0; visibility: hidden; transform: translateY(-10px) scale(0.5); }
          100% { opacity: 0; visibility: hidden; transform: translateY(-10px) scale(0.5); }
        }
        @keyframes checkmarkAppear {
          0% { opacity: 0; transform: scale(0.5) rotate(-45deg); }
          50% { opacity: 0.5; transform: scale(1.2) rotate(0deg); }
          100% { opacity: 1; transform: scale(1) rotate(0deg); }
        }

        /* --- Botão Menu --- */
        .menuButton {
          display: flex; justify-content: center; align-items: center;
          flex-direction: column; gap: 13%; color: #090909;
          width: 3.5em; height: 3.5em; border-radius: 0.5em;
          background: #171717; border: 1px solid #171717;
          transition: all .3s;
          box-shadow: inset 4px 4px 12px #3a3a3a, inset -4px -4px 12px #000000;
          cursor: pointer;
        }
        .menuButton:hover { border: 1px solid black; }
        .menuButton:active {
          color: #666;
          box-shadow: 6px 6px 12px #3a3a3a, -6px -6px 12px #000000;
        }
        input[type = "checkbox"] {
          -webkit-appearance: none; display: none; visibility: hidden;
        }
        .menuButton span {
          width: 30px; height: 4px; background: rgb(200,200,200);
          border-radius: 100px; transition: 0.3s ease;
        }
        input[type="checkbox"]:checked ~ span.top { transform: translateY(290%) rotate(45deg); width: 40px; }
        input[type="checkbox"]:checked ~ span.bot {
          transform: translateY(-270%) rotate(-45deg); width: 40px;
          box-shadow: 0 0 10px #495057;
        }
        input[type="checkbox"]:checked ~ span.mid { transform: translateX(-20px); opacity: 0; }
        
        /* --- Estilo do Menu Dropdown de Navegação --- */
        #nav-menu {
            display: none; position: absolute; right: 0; top: 100%; margin-top: 8px;
            width: 150px; background-color: #040f16; border-radius: 5px;
            box-shadow: 0 0 20px 0px #2e2e2e3a; z-index: 20; overflow: hidden;
        }
        #nav-menu a {
            display: block; padding: 12px 16px; color: #f5f5f5; text-decoration: none;
            font-size: 16px; font-family: Verdana, Geneva, Tahoma, sans-serif;
            transition: background-color 0.3s;
        }
        #nav-menu a:hover { background-color: #1a2a3a; }
        #nav-menu.show { display: block; }

        /* --- Botão Sair --- */
        .Btn {
          display: flex; align-items: center; justify-content: flex-start;
          width: 45px; height: 45px; border: none; border-radius: 50%;
          cursor: pointer; position: relative; overflow: hidden;
          transition-duration: .3s; box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.199);
          background-color: rgb(255, 65, 65); margin: 4px auto;
        }
        .sign {
          width: 100%; transition-duration: .3s; display: flex;
          align-items: center; justify-content: center;
        }
        .sign svg { width: 17px; }
        .sign svg path { fill: white; }
        .text-logout {
          position: absolute; right: 0%; width: 0%; opacity: 0;
          color: white; font-size: 1rem; font-weight: 600;
          transition-duration: .3s;
        }
        .Btn:hover {
          width: 125px; border-radius: 40px; transition-duration: .3s;
        }
        .Btn:hover .sign {
          width: 30%; transition-duration: .3s; padding-left: 20px;
        }
        .Btn:hover .text-logout {
          opacity: 1; width: 70%; transition-duration: .3s; padding-right: 10px;
        }
        .Btn:active { transform: translate(2px ,2px); }

        /* --- Estilo para Saldo e Total --- */
        #saldo-header {
            font-size: 0.9rem; color: #f4c430; font-weight: 600;
        }
        #cart-total { font-size: 1.1rem; color: #f5f5f5; font-weight: 700; }
        

        /* --- Botão Admin --- */
        .btn-admin {
          display: flex; justify-content: center; align-items: center;
          width: 13rem; overflow: hidden; height: 3rem;
          background-size: 300% 300%; backdrop-filter: blur(1rem);
          border-radius: 5rem; transition: 0.5s;
          animation: gradient_301 5s ease infinite;
          border: double 4px transparent;
          background-image: linear-gradient(#212121, #212121), linear-gradient(137.48deg, #ffdb3b 10%, #ff9b17d7 45%, #f9ff41 67%, #feb200d7 87%);
          background-origin: border-box; background-clip: content-box, border-box;
        }
        #container-stars {
          position: absolute; z-index: -1; width: 100%; height: 100%;
          overflow: hidden; transition: 0.5s;
          backdrop-filter: blur(1rem); border-radius: 5rem;
        }
        .btn-admin strong {
          z-index: 2; font-family: 'Poppins', sans-serif; font-size: 16px;
          letter-spacing: 3px; color: #FFFFFF; text-shadow: 0 0 4px rgb(0, 0, 0);
        }
        #glow { position: absolute; display: flex; width: 12rem; }
        .circle {
          width: 100%; height: 30px; filter: blur(2rem);
          animation: pulse_3011 4s infinite; z-index: -1;
        }
        .circle:nth-of-type(1) { background: rgba(255, 215, 0, 0.936); }
        .circle:nth-of-type(2) { background: rgba(255, 165, 0, 0.936); }
        .btn-admin:hover #container-stars { z-index: 1; background-color: #212121; }
        .btn-admin:hover { transform: scale(1.1) }
        .btn-admin:active {
          border: double 4px #FE53BB; background-origin: border-box;
          background-clip: content-box, border-box; animation: none;
        }
        .btn-admin:active .circle { background: #FE53BB; }
        #stars {
          position: relative; background: transparent;
          width: 200rem; height: 200rem;
        }
        #stars::after {
          content: ""; position: absolute; top: -10rem; left: -100rem;
          width: 100%; height: 100%;
          animation: animStarRotate 90s linear infinite;
          background-image: radial-gradient(#ffffff 1px, transparent 1%);
          background-size: 50px 50px;
        }
        #stars::before {
          content: ""; position: absolute; top: 0; left: -50%;
          width: 170%; height: 500%;
          animation: animStar 60s linear infinite;
          background-image: radial-gradient(#ffffff 1px, transparent 1%);
          background-size: 50px 50px; opacity: 0.5;
        }
        @keyframes animStar {
          from { transform: translateY(0); }
          to { transform: translateY(-135rem); }
        }
        @keyframes animStarRotate {
          from { transform: rotate(360deg); }
          to { transform: rotate(0); }
        }
        @keyframes gradient_301 {
          0% { background-position: 0% 50%; }
          50% { background-position: 100% 50%; }
          100% { background-position: 0% 50%; }
        }
        @keyframes pulse_3011 {
          0% { transform: scale(0.75); box-shadow: 0 0 0 0 rgba(0, 0, 0, 0.7); }
          70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(0, 0, 0, 0); }
          100% { transform: scale(0.75); box-shadow: 0 0 0 0 rgba(0, 0, 0, 0); }
        }
        /* --- FIM Botão Admin --- */

    </style>
</head>
<body class="bg-gray-900 text-white">

    <div id="video-container" class="fixed top-0 left-0 w-full h-full -z-10 overflow-hidden">
        <video id="background-video" autoplay muted loop class="absolute top-1/2 left-1/2 w-auto min-w-full min-h-full -translate-x-1/2 -translate-y-1/2 object-cover">
            <source src="/delux-php/assets/carro.mp4" type="video/mp4">
        </video>
    </div>

    <div class="relative z-10 min-h-screen flex flex-col">

        <header class="container mx-auto px-4 py-6 flex justify-between items-center">
            
            <div class="flex items-center gap-4">
                <h1 class="text-3xl font-bold text-yellow-400 tracking-wider">Deluxe</h1>
                
                <?php if ($is_admin == 2): // NÍVEL 2 ?>
                    <button class="btn-admin hidden md:flex">
                        <div id="container-stars"> <div id="stars"></div> </div>
                        <div id="glow"> <div class="circle"></div> <div class="circle"></div> </div>
                        <strong>SUPER ADMIN</strong>
                    </button>
                <?php elseif ($is_admin == 1): // NÍVEL 1 ?>
                    <button class="btn-admin hidden md:flex">
                        <div id="container-stars"> <div id="stars"></div> </div>
                        <div id="glow"> <div class="circle"></div> <div class="circle"></div> </div>
                        <strong>ADMINISTRADOR</strong>
                    </button>
                <?php endif; ?>
            </div>
            
            <nav class="flex items-center space-x-2 md:space-x-4">
                
                <div id="saldo-header" class="hidden sm:block text-sm md:text-base">
                    Saldo: R$ <span id="saldo-valor"><?php echo number_format($user_saldo, 2, ',', '.'); ?></span>
                </div>

                <!-- Menu Dropdown de Navegação -->
                <div class="relative">
                    <label class="menuButton" for="menuToggle">
                        <input id="menuToggle" type="checkbox" />
                        <span class="top"></span> <span class="mid"></span> <span class="bot"></span>
                    </label>
                    
                    <div id="nav-menu">
                        <a href="galeria_carros.php">Galeria</a>
                        <a href="listar.php">Usuários</a>
                    </div>
                </div>
                
                <!-- NOVO: Botão de Notificações -->
                <a href="perfil_usuario.php#tab4" class="relative text-gray-200 hover:text-yellow-400 transition" title="Notificações">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <?php if ($unread_notifications > 0): ?>
                        <span class="absolute -top-2 -right-2 bg-red-600 text-white text-xs font-bold w-5 h-5 rounded-full flex items-center justify-center border-2 border-gray-900">
                            <?php echo $unread_notifications; ?>
                        </span>
                    <?php endif; ?>
                </a>

                <!-- Botão de Carrinho -->
                <button onclick="toggleCart()" class="relative text-gray-200 hover:text-yellow-400 transition" title="Carrinho">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span id="cart-count" class="absolute -top-2 -right-2 bg-yellow-400 text-gray-900 text-xs font-bold w-5 h-5 rounded-full flex items-center justify-center">0</span>
                </button>
                
                <!-- Menu Perfil -->
                <div class="relative">
                    <button onclick="toggleProfileMenu(event)" class="block w-10 h-10 rounded-full overflow-hidden border-2 border-yellow-400">
                        <img id="profile-pic" src="<?php echo $foto_url; ?>" alt="Foto de Perfil" class="w-full h-full object-cover">
                    </button>
                    <!-- Dropdown Menu -->
                    <div id="profile-menu" class="hidden absolute right-0 mt-2 w-48 bg-gray-800 rounded-lg shadow-lg py-2 z-20 border border-gray-700">
                        <!-- NOVO: Link aponta para a #tab1 -->
                        <a href="perfil_usuario.php#tab1" class="block px-4 py-2 text-sm text-gray-200 hover:bg-gray-700 text-center">Meu Perfil</a>
                        
                        <div class="flex justify-center py-2">
                            <a href="logout.php" class="Btn" title="Sair">
                                <div class="sign">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.5 3.75a1.5 1.5 0 011.5 1.5v13.5a1.5 1.5 0 01-1.5 1.5h-6a1.5 1.5 0 01-1.5-1.5V15a.75.75 0 00-1.5 0v3.75a3 3 0 003 3h6a3 3 0 003-3V5.25a3 3 0 00-3-3h-6a3 3 0 00-3 3V9A.75.75 0 009 9V5.25a1.5 1.5 0 011.5-1.5h6zM5.78 8.47a.75.75 0 00-1.06 0l-3 3a.75.75 0 000 1.06l3 3a.75.75 0 001.06-1.06L3.81 12.75H15a.75.75 0 000-1.5H3.81l1.97-1.97a.75.75 0 000-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="text-logout">Sair</div>
                            </a>
                        </div>
                    </div>
                </div>
            </nav>
        </header>

        <main class="container mx-auto px-4 flex-grow flex flex-col justify-center">
            
            <section class="text-center my-12">
                <h2 class="text-5xl md:text-7xl font-extrabold mb-4">
                    Seu Carro, <span class="text-yellow-400">Suas Regras</span>.
                </h2>
                <p class="text-lg md:text-xl text-gray-300 max-w-2xl mx-auto">
                    Carros exclusivos com designs únicos inspirados na cultura JDM. Qualidade e estilo para quem é apaixonado por velocidade.
                </p>
            </section>

            <!-- Seção de Produtos -->
            <section class="mb-12">
                <h3 class="text-3xl font-bold text-center text-yellow-400 mb-8">Destaques</h3>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    
                    <div class="product-card bg-gray-800 bg-opacity-70 rounded-xl overflow-hidden shadow-lg border border-gray-700 flex flex-col">
                        <div class="h-48 bg-gray-700">
                             <img src="/delux-php/assets/images/r352.jpg" alt="Nissan GT-R (R35)" class="w-full h-full object-cover">
                        </div>
                        <div class="p-6 flex flex-col flex-grow">
                            <h2 class="text-xl font-bold text-yellow-400 mb-2">Nissan GT-R (R35)</h2>
                            <p class="text-sm text-gray-300 mb-4 flex-grow">O "Godzilla" moderno. Performance lendária que ultrapassa a barreira dos 500cv.</p>
                            <button
                              onclick="addToCart('GT-R (R35)', '/delux-php/assets/images/r352.jpg', 1, 350000.00)"
                              class="w-full bg-yellow-400 text-gray-900 font-bold py-2 px-4 rounded-lg hover:bg-yellow-300 transition duration-300 mt-auto"
                            >
                              Adicionar (R$ 350.000)
                            </button>
                        </div>
                    </div>

                    <div class="product-card bg-gray-800 bg-opacity-70 rounded-xl overflow-hidden shadow-lg border border-gray-700 flex flex-col">
                        <div class="h-48 bg-gray-700">
                            <img src="/delux-php/assets/images/r32.jpg" alt="Nissan GT-R (R32)" class="w-full h-full object-cover">
                        </div>
                        <div class="p-6 flex flex-col flex-grow">
                            <h2 class="text-xl font-bold text-yellow-400 mb-2">Nissan GT-R (R32)</h2>
                            <p class="text-sm text-gray-300 mb-4 flex-grow">O clássico. O motor RB26DETT que definiu uma geração de carros esportivos japoneses.</p>
                            <button
                              onclick="addToCart('GT-R (R32)', '/delux-php/assets/images/r32.jpg', 1, 180000.00)"
                              class="w-full bg-yellow-400 text-gray-900 font-bold py-2 px-4 rounded-lg hover:bg-yellow-300 transition duration-300 mt-auto"
                            >
                              Adicionar (R$ 180.000)
                            </button>
                        </div>
                    </div>

                    <div class="product-card bg-gray-800 bg-opacity-70 rounded-xl overflow-hidden shadow-lg border border-gray-700 flex flex-col">
                        <div class="h-48 bg-gray-700">
                            <img src="/delux-php/assets/images/mc.jpg" alt="McLaren 720S" class="w-full h-full object-cover">
                        </div>
                        <div class="p-6 flex flex-col flex-grow">
                            <h2 class="text-xl font-bold text-yellow-400 mb-2">McLaren 720S</h2>
                            <p class="text-sm text-gray-300 mb-4 flex-grow">Supercarro leve e forte que entrega performance implacável e uma experiência pura de conversível.</p>
                            <button
                              onclick="addToCart('McLaren 720S', '/delux-php/assets/images/mc.jpg', 1, 1200000.00)"
                              class="w-full bg-yellow-400 text-gray-900 font-bold py-2 px-4 rounded-lg hover:bg-yellow-300 transition duration-300 mt-auto"
                            >
                              Adicionar (R$ 1.200.000)
                            </button>
                        </div>
                    </div>

                    <div class="product-card bg-gray-800 bg-opacity-70 rounded-xl overflow-hidden shadow-lg border border-gray-700 flex flex-col">
                        <div class="h-48 bg-gray-700">
                            <img src="/delux-php/assets/images/p1.jpg" alt="McLaren P1" class="w-full h-full object-cover">
                        </div>
                        <div class="p-6 flex flex-col flex-grow">
                            <h2 class="text-xl font-bold text-yellow-400 mb-2">McLaren P1</h2>
                            <p class="text-sm text-gray-300 mb-4 flex-grow">O sucessor espiritual do McLaren F1. Um ícone da engenharia automotiva moderna.</p>
                            <button
                              onclick="addToCart('McLaren P1', '/delux-php/assets/images/p1.jpg', 1, 5000000.00)"
                              class="w-full bg-yellow-400 text-gray-900 font-bold py-2 px-4 rounded-lg hover:bg-yellow-300 transition duration-300 mt-auto"
                            >
                              Adicionar (R$ 5.000.000)
                            </button>
                        </div>
                    </div>

                </div>
            </section>

        </main>

        <footer class="bg-black bg-opacity-50 py-4 text-center">
            <p class="text-sm text-gray-400">© 2024 Deluxe Carros Exclusivos. Todos os direitos reservados.</p>
        </footer>
    </div>

    <!-- Modal do Carrinho (Overlay) -->
    <div id="cartModal" class="hidden fixed inset-0 z-40">
        <div class="absolute inset-0 bg-black bg-opacity-70" onclick="toggleCart()"></div>
        
        <div class="relative z-50 bg-gray-900 w-full max-w-md h-full ml-auto p-6 shadow-2xl flex flex-col" onclick="event.stopPropagation()">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-yellow-400">Seu Carrinho</h2>
                <button onclick="toggleCart()" class="text-gray-400 hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div id="cartItems" class="flex-grow overflow-y-auto space-y-4">
                <p id="empty-cart-msg" class="text-gray-400 text-center mt-10">Seu carrinho está vazio.</p>
            </div>
            
            <div id="cart-total-container" class="mt-6 border-t border-gray-700 pt-4 hidden">
                 <div class="flex justify-between items-center mb-4">
                    <span class="text-gray-400 text-lg">Total:</span>
                    <span id="cart-total" class="text-xl font-bold text-yellow-400">R$ 0,00</span>
                 </div>
                 <div id="saldo-error-msg" class="hidden text-center text-red-400 mb-4 p-3 bg-red-900 bg-opacity-50 rounded-lg">
                    Saldo insuficiente!
                 </div>
            </div>


            <div class="mt-2">
                <button onclick="finishPurchase()" class="pay-btn w-full">
                    <div class="icon-container">
                        <svg class="icon default-icon wallet-icon" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <svg class="icon card-icon" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <svg class="icon payment-icon" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="2">
                           <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        <svg class="icon dollar-icon" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="2">
                           <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8v1m0 6v1m0-1c-1.105 0-2.076.15-2.99.41m5.98 0A8.963 8.963 0 0112 14c-2.056 0-4-.78-5.455-2.042m10.91 0a8.963 8.963 0 00-5.455-2.042M12 14v1m0-1c-1.105 0 2.076.15 2.99.41M6.01 11.59A8.963 8.963 0 0012 14m0 0c2.056 0 4-.78 5.455-2.042M12 14v-1m0 1c-1.105 0-2.076-.15-2.99-.41M17.99 11.59a8.963 8.963 0 01-5.455 2.042m5.455-2.042v-1m0 1c-1.105 0-2.076-.15-2.99-.41m0 0A8.963 8.963 0 0112 10c-2.056 0-4 .78-5.455-2.042m10.91 0a8.963 8.963 0 00-5.455-2.042M12 10V9m0 1c-1.105 0-2.076.15-2.99.41m5.98 0A8.963 8.963 0 0112 10c-2.056 0-4 .78-5.455-2.042m0 0A8.963 8.963 0 016.01 11.59m0 0v-1m0 1c-1.105 0-2.076-.15-2.99-.41m0 0A8.963 8.963 0 013 10c0-2.056.78-4 2.042-5.455m10.91 0A8.963 8.963 0 0112 10c2.056 0 4 .78 5.455 2.042m-10.91 0A8.963 8.963 0 0012 10c-2.056 0-4-.78-5.455-2.042M12 10V9m0 1c1.105 0 2.076.15 2.99.41m-5.98 0A8.963 8.963 0 0012 10c2.056 0 4 .78 5.455 2.042m0 0A8.963 8.963 0 0117.99 11.59m0 0v-1m0 1c-1.105 0-2.076-.15-2.99-.41m0 0A8.963 8.963 0 0112 14c-2.056 0-4-.78-5.455-2.042" />
                        </svg>
                         <svg class="icon check-icon" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="3">
                           <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <span class="btn-text">Finalizar Compra</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Animação (Finalizar Compra) - ATUALIZADO -->
    <div id="animationModal" class="hidden fixed inset-0 z-50 flex items-center justify-center overflow-hidden">
        <div class="absolute inset-0 bg-black bg-opacity-80"></div>
        
        <!-- Container para centralizar -->
        <div class="relative flex flex-col items-center">
            <!-- Texto (agora posicionado acima da animação) -->
            <p class="w-full text-center text-xl text-white font-semibold mb-4">Compra Realizada!</p>
        
            <!-- O Loader (com a nova animação) -->
            <div class="loader">
                <div class="truckWrapper">
                    
                    <!-- ATUALIZADO: SVG do Corpo do Caminhão (Branco) -->
                    <svg class="truckBody" viewBox="0 0 130 70" xmlns="http://www.w3.org/2000/svg" fill="#FFFFFF">
                        <path d="M124.8,39.2c-2.4-8.8-10-15.2-19.2-15.2H89.6V12c0-3.2-2.4-5.6-5.6-5.6H11.2C7.2,6.4,4,9.6,4,13.6v34.4c0,3.2,2.4,5.6,5.6,5.6h4.8v4.8c0,3.2,2.4,5.6,5.6,5.6h12c3.2,0,5.6-2.4,5.6-5.6v-4.8h48.8c8,0,15.2-4.8,18.4-12L124.8,39.2z M32.8,53.6c-2.4,0-4-1.6-4-4s1.6-4,4-4s4,1.6,4,4S35.2,53.6,32.8,53.6z M100.8,53.6c-2.4,0-4-1.6-4-4s1.6-4,4-4s4,1.6,4,4S103.2,53.6,100.8,53.6z M112,36H89.6V29.6c0-3.2-2.4-5.6-5.6-5.6H16.8V12h67.2V36H112z"/>
                    </svg>
                    
                    <!-- ATUALIZADO: Pneus (Brancos) -->
                    <div class="truckTires">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="#FFFFFF">
                            <path d="M12,0C5.373,0,0,5.373,0,12s5.373,12,12,12s12-5.373,12-12S18.627,0,12,0z M12,19.2c-4.008,0-7.2-3.192-7.2-7.2s3.192-7.2,7.2-7.2s7.2,3.192,7.2,7.2S16.008,19.2,12,19.2z M12,8.4c-2.004,0-3.6,1.596-3.6,3.6s1.596,3.6,3.6,3.6s3.6-1.596,3.6-3.6S14.004,8.4,12,8.4z"/>
                        </svg>
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="#FFFFFF">
                            <path d="M12,0C5.373,0,0,5.373,0,12s5.373,12,12,12s12-5.373,12-12S18.627,0,12,0z M12,19.2c-4.008,0-7.2-3.192-7.2-7.2s3.192-7.2,7.2-7.2s7.2,3.192,7.2,7.2S16.008,19.2,12,19.2z M12,8.4c-2.004,0-3.6,1.596-3.6,3.6s1.596,3.6,3.6,3.6s3.6-1.596,3.6-3.6S14.004,8.4,12,8.4z"/>
                        </svg>
                    </div>
                    
                    <!-- Estrada -->
                    <div class="road"></div>
                    
                    <!-- ATUALIZADO: Poste de Luz (Branco) -->
                    <svg class="lampPost" viewBox="0 0 20 90" xmlns="http://www.w3.org/2000/svg" fill="#FFFFFF">
                        <path d="M16,0H4C2.4,0,0,1.2,0,2.8v4.8C0,9.2,2.4,10,4,10h12c1.6,0,4-0.8,4-2.4V2.8C20,1.2,17.6,0,16,0z M8,88h4v-4H8V88z M10,84 c-1.6,0-3-1.4-3-3V14h6v67C13,82.6,11.6,84,10,84z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>


    <script>
        // --- Controle do Menu Profile ---
        function toggleProfileMenu(event) {
            event.stopPropagation(); 
            document.getElementById('profile-menu').classList.toggle('hidden');
            document.getElementById('nav-menu').classList.remove('show');
            document.getElementById('menuToggle').checked = false; 
        }

        // --- Controle do Menu de Navegação ---
        function toggleNavMenu(event) {
            event.stopPropagation(); 
            document.getElementById('profile-menu').classList.add('hidden');
        }
        
        document.getElementById('menuToggle').addEventListener('change', function(event) {
            toggleNavMenu(event); 
            const navMenu = document.getElementById('nav-menu');
            if (this.checked) {
                navMenu.classList.add('show');
            } else {
                navMenu.classList.remove('show');
            }
        });


        // Fecha os menus se clicar fora
        window.onclick = function(event) {
            const profileButton = document.querySelector('button[onclick="toggleProfileMenu(event)"]');
            const navButton = document.querySelector('.menuButton');
            
            if (profileButton && !profileButton.contains(event.target) && !profileButton.closest('#profile-menu')) {
                document.getElementById('profile-menu').classList.add('hidden');
            }
            
            if (navButton && !navButton.contains(event.target) && !event.target.closest('#nav-menu')) {
                document.getElementById('nav-menu').classList.remove('show');
                document.getElementById('menuToggle').checked = false; 
            }
        }

        // --- Controle do Carrinho ---
        let cart = [];
        let currentUserBalance = <?php echo $user_saldo ?? 0; ?>;

        function formatCurrency(value) {
            return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        }

        function updateHeaderBalance(newBalance) {
            document.getElementById('saldo-valor').textContent = newBalance.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function toggleCart() {
            document.getElementById('cartModal').classList.toggle('hidden');
        }

        function addToCart(productName, productImage, quantity, price) {
            cart.push({ productName, productImage, quantity, price });
            document.getElementById('cart-count').textContent = cart.length;
            updateCart();
            toggleCart(); 
            document.getElementById('saldo-error-msg').classList.add('hidden');
        }

        function updateCart() {
            let cartItemsContainer = document.getElementById('cartItems');
            let totalContainer = document.getElementById('cart-total-container');
            let totalElement = document.getElementById('cart-total');
            let totalPrice = 0;

            cartItemsContainer.innerHTML = ''; 

            if (cart.length === 0) {
                cartItemsContainer.innerHTML = `
                    <p id="empty-cart-msg" class="text-gray-400 text-center mt-10">
                        Seu carrinho está vazio.
                    </p>
                `;
                totalContainer.classList.add('hidden'); 
            } else {
                totalContainer.classList.remove('hidden'); 
                cart.forEach((item, index) => {
                    totalPrice += item.price * item.quantity; 

                    let cartItem = document.createElement('div');
                    cartItem.className = 'flex items-center space-x-4 bg-gray-800 p-3 rounded-lg border border-gray-700';
                    cartItem.innerHTML = `
                        <img src="${item.productImage}" alt="${item.productName}" class="w-16 h-16 rounded-md object-cover">
                        <div class="flex-grow">
                            <h4 class="font-semibold text-white">${item.productName}</h4>
                            <p class="text-sm text-yellow-400">${formatCurrency(item.price)}</p>
                        </div>
                        <button onclick="removeFromCart(${index})" class="text-red-400 hover:text-red-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    `;
                    cartItemsContainer.appendChild(cartItem);
                });
                totalElement.textContent = formatCurrency(totalPrice);
            }
        }

        function removeFromCart(index) {
            cart.splice(index, 1);
            document.getElementById('cart-count').textContent = cart.length;
            updateCart();
            document.getElementById('saldo-error-msg').classList.add('hidden');
        }

        async function finishPurchase() {
            if (cart.length === 0) return; 

            let errorMsg = document.getElementById('saldo-error-msg');
            errorMsg.classList.add('hidden'); 

            let totalPrice = cart.reduce((total, item) => total + (item.price * item.quantity), 0);

            if (totalPrice > currentUserBalance) {
                errorMsg.classList.remove('hidden');
                return; 
            }

            try {
                const response = await fetch('comprar.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', },
                    body: JSON.stringify({ totalPrice: totalPrice }),
                });

                const result = await response.json();

                if (result.success) {
                    currentUserBalance = result.newBalance;
                    updateHeaderBalance(result.newBalance);
                    cart = [];
                    updateCart(); 
                    document.getElementById('cart-count').textContent = '0';
                    toggleCart();

                    const modal = document.getElementById('animationModal');
                    modal.classList.add('show');
                    modal.classList.remove('hidden');

                    // ATUALIZADO: A nova animação é um loop infinito,
                    // então vamos pará-la após 5 segundos.
                    setTimeout(() => {
                        modal.classList.remove('show');
                        modal.classList.add('hidden');
                    }, 5000);

                } else {
                    errorMsg.textContent = result.message; 
                    errorMsg.classList.remove('hidden');
                    location.reload(); 
                }
            } catch (error) {
                console.error('Erro ao processar a compra:', error);
                errorMsg.textContent = 'Erro de conexão. Tente novamente.';
                errorMsg.classList.remove('hidden');
            }
        }
    </script>

</body>
</html>