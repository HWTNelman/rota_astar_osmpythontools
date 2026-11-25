-- phpMyAdmin SQL Dump
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Banco de dados: `log_baa`

-- Estrutura para tabela `baa_carga`
CREATE TABLE `baa_carga` (
  `id_carga` int(11) NOT NULL,
  `car_descricao` char(200) DEFAULT NULL,
  `car_peso_kg` int(11) DEFAULT NULL,
  `car_origem` char(30) DEFAULT NULL,
  `car_destino` char(30) DEFAULT NULL,
  `car_status` char(20) DEFAULT NULL,
  `id_cliente` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `baa_carga` (`id_carga`, `car_descricao`, `car_peso_kg`, `car_origem`, `car_destino`, `car_status`, `id_cliente`) VALUES
(1, 'Gerador Industrial GX-2000', 1850, 'Campinas', 'Curitiba', 'iniciado', 3),
(2, 'Paletes de Cimento CP-II', 12000, 'São Paulo', 'Belo Horizonte', 'alocado', 5);

-- Estrutura para tabela `baa_cliente`
CREATE TABLE `baa_cliente` (
  `id_cliente` int(11) NOT NULL,
  `cli_nome` char(50) DEFAULT NULL,
  `cli_cpf_cnpj` varchar(14) DEFAULT NULL,
  `cli_telefone` varchar(11) DEFAULT NULL,
  `cli_cidade` char(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `baa_cliente` (`id_cliente`, `cli_nome`, `cli_cpf_cnpj`, `cli_telefone`, `cli_cidade`) VALUES
(1, 'Ana Carolina Souza', '12345678900', '11988772211', 'São Paulo');

-- Estrutura para tabela `baa_motorista`
CREATE TABLE `baa_motorista` (
  `id_motorista` int(11) NOT NULL,
  `mot_nome` char(50) DEFAULT NULL,
  `mot_cpf` varchar(11) DEFAULT NULL,
  `mot_telefone` varchar(11) DEFAULT NULL,
  `mot_cnh` char(10) DEFAULT NULL,
  `validade_cnh` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `baa_motorista` (`id_motorista`, `mot_nome`, `mot_cpf`, `mot_telefone`, `mot_cnh`, `validade_cnh`) VALUES
(1, 'Carlos Eduardo Ramos', '12345678901', '11999887766', '982345671', '2026-04-15');

-- Estrutura para tabela `baa_ocorrencia`
CREATE TABLE `baa_ocorrencia` (
  `id_ocorrencia` int(11) NOT NULL,
  `oco_tipo` char(20) DEFAULT NULL,
  `oco_descricao` char(200) DEFAULT NULL,
  `oco_data_ocorrencia` date DEFAULT NULL,
  `id_viagem` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Estrutura para tabela `baa_veiculo`
CREATE TABLE `baa_veiculo` (
  `id_veiculo` int(11) NOT NULL,
  `vei_placa` char(7) DEFAULT NULL,
  `vei_modelo` char(20) DEFAULT NULL,
  `vei_ano` int(5) DEFAULT NULL,
  `vei_capacidade_kg` int(11) DEFAULT NULL,
  `vei_status` char(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `baa_veiculo` (`id_veiculo`, `vei_placa`, `vei_modelo`, `vei_ano`, `vei_capacidade_kg`, `vei_status`) VALUES
(1, 'ABC1D23', 'Volvo FH 540', 2020, 30000, 'ocupado');

-- Estrutura para tabela `baa_rota` (ADICIONADA PARA CORRIGIR O ERRO)
CREATE TABLE `baa_rota` (
  `id_rota` int(11) NOT NULL AUTO_INCREMENT,
  `nome_rota` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_rota`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Estrutura para tabela `baa_viagem`
CREATE TABLE `baa_viagem` (
  `id_viagem` int(11) NOT NULL,
  `via_data_saida` date DEFAULT NULL,
  `via_data_chegada` date DEFAULT NULL,
  `via_status` char(30) DEFAULT NULL,
  `id_motorista` int(11) DEFAULT NULL,
  `id_veiculo` int(11) NOT NULL,
  `id_rota` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `baa_viagem` (`id_viagem`, `via_data_saida`, `via_data_chegada`, `via_status`, `id_motorista`, `id_veiculo`, `id_rota`) VALUES
(1, '2025-01-05', '2025-01-08', 'finalizado', 1, 1, NULL);

-- Índices e Restrições
ALTER TABLE `baa_carga` ADD PRIMARY KEY (`id_carga`), ADD KEY `id_cliente` (`id_cliente`);
ALTER TABLE `baa_cliente` ADD PRIMARY KEY (`id_cliente`);
ALTER TABLE `baa_motorista` ADD PRIMARY KEY (`id_motorista`);
ALTER TABLE `baa_ocorrencia` ADD PRIMARY KEY (`id_ocorrencia`), ADD KEY `id_viagem` (`id_viagem`);
ALTER TABLE `baa_veiculo` ADD PRIMARY KEY (`id_veiculo`);
ALTER TABLE `baa_viagem` ADD PRIMARY KEY (`id_viagem`), ADD KEY `id_motorista` (`id_motorista`), ADD KEY `id_veiculo` (`id_veiculo`), ADD KEY `baa_viagem_ibfk_3` (`id_rota`);

ALTER TABLE `baa_carga` MODIFY `id_carga` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
ALTER TABLE `baa_cliente` MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `baa_motorista` MODIFY `id_motorista` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `baa_ocorrencia` MODIFY `id_ocorrencia` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `baa_veiculo` MODIFY `id_veiculo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `baa_viagem` MODIFY `id_viagem` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `baa_carga` ADD CONSTRAINT `baa_carga_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `baa_cliente` (`id_cliente`);
ALTER TABLE `baa_ocorrencia` ADD CONSTRAINT `baa_ocorrencia_ibfk_1` FOREIGN KEY (`id_viagem`) REFERENCES `baa_viagem` (`id_viagem`);
ALTER TABLE `baa_viagem` ADD CONSTRAINT `baa_viagem_ibfk_1` FOREIGN KEY (`id_motorista`) REFERENCES `baa_motorista` (`id_motorista`), ADD CONSTRAINT `baa_viagem_ibfk_3` FOREIGN KEY (`id_rota`) REFERENCES `baa_rota` (`id_rota`) ON DELETE SET NULL, ADD CONSTRAINT `id_veiculo` FOREIGN KEY (`id_veiculo`) REFERENCES `baa_veiculo` (`id_veiculo`);

COMMIT;