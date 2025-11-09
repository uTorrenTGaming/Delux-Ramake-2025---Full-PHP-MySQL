Para que o novo sistema de Carteira e Administrador funcione, você PRECISA atualizar sua tabela usuarios no banco de dados.Execute os seguintes comandos SQL no seu phpMyAdmin (ou ferramenta de sua preferência) para adicionar as colunas saldo e is_admin:-- 1. Adiciona a coluna 'saldo' para guardar o dinheiro (com um valor padrão de 50000 para novos usuários)
ALTER TABLE `usuarios`
ADD COLUMN `saldo` DECIMAL(10, 2) NOT NULL DEFAULT 50000.00 AFTER `senha`;

-- 2. Adiciona a coluna 'is_admin' para permissões (0 = não, 1 = sim)
ALTER TABLE `usuarios`
ADD COLUMN `is_admin` TINYINT(1) NOT NULL DEFAULT 0 AFTER `saldo`;
Opcional: Tornar seu usuário um AdministradorPara testar a função de adicionar dinheiro, torne seu próprio usuário um administrador. Descubra o seu id (você pode ver na página "Usuários") e execute:-- Troque 'SEU_ID' pelo ID do seu usuário (ex: 1)
UPDATE `usuarios`
SET `is_admin` = 1
WHERE `id` = SEU_ID;

Para que o novo sistema de Super Admin e Notificações funcione, você PRECISA atualizar seu banco de dados.Execute os seguintes comandos SQL no seu phpMyAdmin.1. Crie a tabela notificacoes:Esta tabela irá armazenar as mensagens enviadas pelos Super Admins.CREATE TABLE `notificacoes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id_destino` INT NOT NULL,
  `titulo` VARCHAR(255) NOT NULL,
  `mensagem` TEXT NOT NULL,
  `lida` TINYINT(1) NOT NULL DEFAULT 0,
  `data_envio` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id_destino`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
2. Torne-se um Super Admin (Nível 2):O Nível 1 é um Admin normal (que só adiciona dinheiro a si mesmo).O Nível 2 é o Super Admin (que pode gerenciar outros usuários).Primeiro, descubra o seu id (na página "Usuários").-- Troque 'SEU_ID' pelo ID do seu usuário (ex: 1)
UPDATE `usuarios`
SET `is_admin` = 2
WHERE `id` = SEU_ID;
Depois de executar esses comandos, as novas abas "Notificações" e "Super Admin" aparecerão no seu perfil.
