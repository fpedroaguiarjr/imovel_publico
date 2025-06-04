-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 25/05/2025 às 00:11
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `imovel_publico`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `averbacoes_processos`
--

CREATE TABLE `averbacoes_processos` (
  `id` int(11) NOT NULL,
  `processo_id` int(11) NOT NULL,
  `texto_averbacao` text DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `caminho_arquivo` varchar(255) DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `documentos_processos`
--

CREATE TABLE `documentos_processos` (
  `id` int(11) NOT NULL,
  `processo_id` varchar(50) NOT NULL,
  `descricao` text DEFAULT NULL,
  `caminho_arquivo` varchar(255) DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `documentos_processos`
--

INSERT INTO `documentos_processos` (`id`, `processo_id`, `descricao`, `caminho_arquivo`, `data_cadastro`) VALUES
(8, '3', 'Testando descrição de documentos', NULL, '2025-05-22 23:41:30'),
(9, '3', 'testando ', NULL, '2025-05-23 21:45:58'),
(10, '3', 'Why do we use it?\r\nIt is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using \'Content here, content here\', making it look like readable English. Many desktop publishing packages and web page editors now use Lorem Ipsum as their default model text, and a search for \'lorem ipsum\' will uncover many web sites still in their infancy. Various versions have evolved over the years, sometimes by accident, sometimes on purpose (injected humour and the like).', NULL, '2025-05-24 11:31:22');

-- --------------------------------------------------------

--
-- Estrutura para tabela `fotos_processos`
--

CREATE TABLE `fotos_processos` (
  `id` int(11) NOT NULL,
  `processo_id` int(11) NOT NULL,
  `caminho_imagem` varchar(255) DEFAULT NULL,
  `descricao` varchar(200) DEFAULT NULL,
  `ordem` int(11) DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `historico_processos`
--

CREATE TABLE `historico_processos` (
  `id` int(11) NOT NULL,
  `processo_id` int(11) NOT NULL,
  `texto_historico` text DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `historico_processos`
--

INSERT INTO `historico_processos` (`id`, `processo_id`, `texto_historico`, `data_cadastro`) VALUES
(29, 3, 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.', '2025-05-24 11:24:46');

-- --------------------------------------------------------

--
-- Estrutura para tabela `localizacao_processos`
--

CREATE TABLE `localizacao_processos` (
  `id` int(11) NOT NULL,
  `processo_id` int(11) NOT NULL,
  `caminho_imagem` varchar(255) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `ordem` int(11) DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `processos`
--

CREATE TABLE `processos` (
  `processo` int(11) NOT NULL,
  `ano` char(30) NOT NULL,
  `descricao` varchar(200) NOT NULL,
  `endereco` varchar(250) NOT NULL,
  `status` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `processos`
--

INSERT INTO `processos` (`processo`, `ano`, `descricao`, `endereco`, `status`) VALUES
(1, '1997', 'teste', 'Rua um', 'arquivados'),
(2, '1998', 'teste 2', 'rua dois mmmmm', 'parado'),
(3, '1999', 'teste três', 'avenida dois', 'andamento'),
(4, '2000', 'teste 4', 'estrada dez', 'registradoT'),
(5, '2025', 'registrado', 'centro', 'registradoT');

-- --------------------------------------------------------

--
-- Estrutura para tabela `rgi_processos`
--

CREATE TABLE `rgi_processos` (
  `id` int(11) NOT NULL,
  `processo_id` int(11) NOT NULL,
  `texto_rgi` text DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `caminho_arquivo` varchar(255) DEFAULT NULL,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `rgi_processos`
--

INSERT INTO `rgi_processos` (`id`, `processo_id`, `texto_rgi`, `descricao`, `caminho_arquivo`, `data_cadastro`) VALUES
(1, 3, 'TESTE', NULL, NULL, '2025-05-22 00:05:12'),
(2, 1, 'teste teste teste', NULL, NULL, '2025-05-22 00:06:24'),
(3, 3, 'Matrícula e dados do RGI, Imissão de Posse e etc', NULL, NULL, '2025-05-22 23:41:30');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `averbacoes_processos`
--
ALTER TABLE `averbacoes_processos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `processo_id` (`processo_id`);

--
-- Índices de tabela `documentos_processos`
--
ALTER TABLE `documentos_processos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `fotos_processos`
--
ALTER TABLE `fotos_processos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `processo_id` (`processo_id`);

--
-- Índices de tabela `historico_processos`
--
ALTER TABLE `historico_processos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `processo_id` (`processo_id`);

--
-- Índices de tabela `localizacao_processos`
--
ALTER TABLE `localizacao_processos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `processo_id` (`processo_id`);

--
-- Índices de tabela `processos`
--
ALTER TABLE `processos`
  ADD PRIMARY KEY (`processo`);

--
-- Índices de tabela `rgi_processos`
--
ALTER TABLE `rgi_processos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `processo_id` (`processo_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `averbacoes_processos`
--
ALTER TABLE `averbacoes_processos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `documentos_processos`
--
ALTER TABLE `documentos_processos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `fotos_processos`
--
ALTER TABLE `fotos_processos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `historico_processos`
--
ALTER TABLE `historico_processos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de tabela `localizacao_processos`
--
ALTER TABLE `localizacao_processos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `rgi_processos`
--
ALTER TABLE `rgi_processos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `averbacoes_processos`
--
ALTER TABLE `averbacoes_processos`
  ADD CONSTRAINT `averbacoes_processos_ibfk_1` FOREIGN KEY (`processo_id`) REFERENCES `processos` (`processo`) ON DELETE CASCADE;

--
-- Restrições para tabelas `fotos_processos`
--
ALTER TABLE `fotos_processos`
  ADD CONSTRAINT `fotos_processos_ibfk_1` FOREIGN KEY (`processo_id`) REFERENCES `processos` (`processo`) ON DELETE CASCADE;

--
-- Restrições para tabelas `historico_processos`
--
ALTER TABLE `historico_processos`
  ADD CONSTRAINT `historico_processos_ibfk_1` FOREIGN KEY (`processo_id`) REFERENCES `processos` (`processo`) ON DELETE CASCADE;

--
-- Restrições para tabelas `localizacao_processos`
--
ALTER TABLE `localizacao_processos`
  ADD CONSTRAINT `localizacao_processos_ibfk_1` FOREIGN KEY (`processo_id`) REFERENCES `processos` (`processo`) ON DELETE CASCADE;

--
-- Restrições para tabelas `rgi_processos`
--
ALTER TABLE `rgi_processos`
  ADD CONSTRAINT `rgi_processos_ibfk_1` FOREIGN KEY (`processo_id`) REFERENCES `processos` (`processo`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
