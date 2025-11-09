-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 09/11/2025 às 16:44
-- Versão do servidor: 10.4.28-MariaDB
-- Versão do PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `delux-php`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `notificacoes`
--

CREATE TABLE `notificacoes` (
  `id` int(11) NOT NULL,
  `user_id_destino` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `mensagem` text NOT NULL,
  `lida` tinyint(1) NOT NULL DEFAULT 0,
  `data_envio` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `notificacoes`
--

INSERT INTO `notificacoes` (`id`, `user_id_destino`, `titulo`, `mensagem`, `lida`, `data_envio`) VALUES
(5, 5, 'Msg do Admin', 'Voce sera banido em breve', 1, '2025-11-08 18:46:02'),
(6, 7, 'Msg do Admin', 'Eu te amo', 1, '2025-11-08 19:13:33'),
(7, 7, 'Msg do Admin', 'Vou te pegar hoje emmmm', 1, '2025-11-08 19:14:12'),
(8, 7, 'Msg do Admin', 'Posso te pegar hoje', 1, '2025-11-08 19:14:53'),
(9, 1, 'Msg do Admin', 'O Melhor', 1, '2025-11-08 19:20:53');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `saldo` decimal(10,2) NOT NULL DEFAULT 50000.00,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `numero_conta` varchar(20) DEFAULT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `tema` varchar(50) DEFAULT 'light'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `saldo`, `is_admin`, `numero_conta`, `foto_perfil`, `tema`) VALUES
(1, 'Igor', 'developer@gmail.com', '$2y$10$x2HPeBEjJh7/aQqCIcc8I.MWb45/gcw2pzYMp6j2xf8fO4pKpJNDC', 85980000.00, 2, '4842 1342 2770 8561', 'assets/uploads/user_1.gif', 'light'),
(5, 'Laura - Test', 'test@gmail.com', '$2y$10$/0coNVXQyOgptTsgDfZiB.Qw7hSSzvFoNYWXktMWeSLUg4nQu8MxO', 10050000.00, 0, '8771 9675 5117 5748', 'assets/uploads/user_5.gif', 'light'),
(6, 'Doidinho - PT', 'doido12@gmail.com', '$2y$10$Ld7fMVY0yQI84WKAA4gvh.wQbTK/FBoe.a9ClVpPAz3qQ4q.fpaae', 50000.00, 0, '8206 6621 2622 3256', NULL, 'light'):;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id_destino` (`user_id_destino`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD CONSTRAINT `notificacoes_ibfk_1` FOREIGN KEY (`user_id_destino`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
