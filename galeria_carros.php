<?php
session_start();

// 1. Verificação de sessão PHP
if(!isset($_SESSION["user_id"])){
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galeria dos Carros</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Fonte Inter (para consistência) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Define a fonte padrão */
        body { 
            font-family: 'Inter', sans-serif; 
        }
    </style>
</head>

<body class="bg-gray-900 text-white">

    <header class="bg-gray-800 shadow-md">
        <nav class="container mx-auto px-4 py-6 flex justify-between items-center">
            <h1 class="text-3xl font-bold text-yellow-400 tracking-wider">Galeria</h1>
            <a href="index.php" class="text-yellow-400 hover:bg-yellow-400 hover:text-gray-900 border border-yellow-400 rounded-lg px-4 py-2 transition duration-300 ease-in-out">
                Voltar ao Início
            </a>
        </nav>
    </header>

    <!-- Container do Carrossel -->
    <main class="carousel-container w-full max-w-4xl mx-auto my-12 px-4">
        
        <!-- Viewport (Define o aspecto 16:9 e esconde o overflow) -->
        <div class="viewport w-full aspect-video overflow-hidden rounded-2xl shadow-2xl bg-black border border-gray-700">
            
            <!-- Film Strip (Move horizontalmente) -->
            <div id="carousel" class="flex h-full transition-transform duration-500 ease-in-out">
                
                <!-- Slide 1 -->
                <div class="slide relative w-full h-full flex-shrink-0">
                    <img src="/delux-php/assets/images/r35.jpg" alt="Nissan R35" class="absolute top-0 left-0 w-full h-full object-contain">
                </div>
                
                <!-- Slide 2 -->
                <div class="slide relative w-full h-full flex-shrink-0">
                    <img src="/delux-php/assets/images/r32.jpg" alt="Nissan R32" class="absolute top-0 left-0 w-full h-full object-contain">
                </div>
                
                <!-- Slide 3 -->
                <div class="slide relative w-full h-full flex-shrink-0">
                    <img src="/delux-php/assets/images/mc.jpg" alt="McLaren 720S" class="absolute top-0 left-0 w-full h-full object-contain">
                </div>
                
                <!-- Slide 4 -->
                <div class="slide relative w-full h-full flex-shrink-0">
                    <img src="/delux-php/assets/images/p1.jpg" alt="McLaren P1" class="absolute top-0 left-0 w-full h-full object-contain">
                </div>

            </div>
        </div>

        <!-- Botões de Navegação -->
        <div class="carousel-buttons flex justify-center gap-4 mt-6">
            <button id="prev" class="bg-yellow-400 text-gray-900 font-bold py-2 px-6 rounded-lg hover:bg-yellow-300 transition duration-300">
                Anterior
            </button>
            <button id="next" class="bg-yellow-400 text-gray-900 font-bold py-2 px-6 rounded-lg hover:bg-yellow-300 transition duration-300">
                Próximo
            </button>
        </div>

    </main>

    <footer class="text-center py-8 text-sm text-gray-500">
        <p>© 2024 Deluxe Carros Exclusivos. Todos os direitos reservados. <a href="#" class="text-yellow-400 hover:underline">Política de Privacidade</a></p>
    </footer>

    <script>
        // --- JavaScript do Carrossel (Modernizado) ---
        
        const carousel = document.getElementById('carousel');
        const slides = carousel.querySelectorAll('.slide'); // Seleciona os 'slides'
        const prevBtn = document.getElementById('prev');
        const nextBtn = document.getElementById('next');
        const container = document.querySelector('.carousel-container'); // O container principal

        let currentIndex = 0;
        let slideWidth = 0;

        function updateSlidePosition() {
            // Pega a largura atual do container (que é o nosso 'viewport')
            slideWidth = container.clientWidth;
            
            // Calcula a posição do slide
            const translateX = -currentIndex * slideWidth;
            
            // Aplica a posição SEM transição (para não bugar no resize)
            carousel.style.transition = 'none'; 
            carousel.style.transform = `translateX(${translateX}px)`;

            // Força o navegador a aplicar o estilo (reflow)
            // Isso é um truque para garantir que a próxima transição funcione
            void carousel.offsetWidth; 

            // Re-habilita a transição para os cliques
            carousel.style.transition = 'transform 0.5s ease-in-out';
        }

        prevBtn.addEventListener('click', () => {
            currentIndex = (currentIndex - 1 + slides.length) % slides.length;
            const translateX = -currentIndex * slideWidth;
            carousel.style.transform = `translateX(${translateX}px)`;
        });

        nextBtn.addEventListener('click', () => {
            currentIndex = (currentIndex + 1) % slides.length;
            const translateX = -currentIndex * slideWidth;
            carousel.style.transform = `translateX(${translateX}px)`;
        });

        // Atualiza a posição no carregamento da página
        window.addEventListener('load', updateSlidePosition);
        // Atualiza a posição se a janela for redimensionada
        window.addEventListener('resize', updateSlidePosition);

    </script>

</body>
</html>