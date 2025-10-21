-- schema.sql
-- Criação do banco de dados (se não existir)
CREATE DATABASE IF NOT EXISTS despesas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE despesas;

-- Tabela de Usuários/Pessoas
-- Armazena todos os envolvidos: você, sua esposa e outras pessoas.
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL, -- Armazenar a senha com hash!
    tipo_acesso ENUM('admin', 'visualizacao') NOT NULL DEFAULT 'visualizacao',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Tipos de Ganho
-- Ex: Salário, Freelance, Vendas, etc.
CREATE TABLE tipos_ganho (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT
);

-- Tabela de Ganhos Mensais
-- Registra os ganhos de cada usuário administrador.
CREATE TABLE ganhos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo_ganho_id INT NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    data_ganho DATE NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (tipo_ganho_id) REFERENCES tipos_ganho(id)
);

-- Tabela de Ganhos Recorrentes (Modelos)
-- Armazena os modelos para ganhos que se repetem (salários, aluguéis, etc.)
CREATE TABLE ganhos_recorrentes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo_ganho_id INT NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    valor_base DECIMAL(10, 2) NOT NULL,
    dia_geracao INT NOT NULL, -- Dia do mês que o ganho deve ser provisionado (1-31)
    data_inicio DATE NOT NULL,
    data_fim DATE NULL, -- Se NULO, a recorrência é indefinida
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (tipo_ganho_id) REFERENCES tipos_ganho(id)
);

-- Tabela de Cartões de Crédito
CREATE TABLE cartoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titular_id INT NOT NULL,
    nome_cartao VARCHAR(50) NOT NULL, -- Ex: "Visa Platinum - Banco X"
    dia_vencimento_fatura INT NOT NULL,
    dia_fechamento_fatura INT NOT NULL,
    data_validade_cartao VARCHAR(5) NOT NULL, -- Formato MM/YY
    FOREIGN KEY (titular_id) REFERENCES usuarios(id)
);

-- Tabela de Despesas Gerais
CREATE TABLE despesas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    data_despesa DATE NOT NULL,
    dono_divida_id INT NOT NULL, -- Quem é o "dono" da despesa (para quem foi comprado)
    comprador_id INT NOT NULL, -- Quem efetuou a compra (você ou sua esposa)
    metodo_pagamento ENUM('dinheiro', 'pix', 'cartao_credito', 'bemol_crediario', 'financiamento', 'consorcio', 'emprestimo') NOT NULL,
    cartao_id INT NULL, -- Se o pagamento for com cartão, qual cartão?
    emprestimo_id INT NULL, -- Se a despesa for parcela de um empréstimo
    automovel_id INT NULL, -- Se a despesa for parcela de um automóvel
    status ENUM('pendente', 'pago', 'atrasado') NOT NULL DEFAULT 'pendente',
    grupo_parcela_id VARCHAR(255) NULL DEFAULT NULL, -- Identificador para agrupar parcelas
    FOREIGN KEY (dono_divida_id) REFERENCES usuarios(id),
    FOREIGN KEY (comprador_id) REFERENCES usuarios(id),
    FOREIGN KEY (cartao_id) REFERENCES cartoes(id),
    FOREIGN KEY (emprestimo_id) REFERENCES emprestimos(id) ON DELETE CASCADE,
    FOREIGN KEY (automovel_id) REFERENCES automoveis(id) ON DELETE SET NULL
);

-- Tabela Específica para Despesas da Bemol
CREATE TABLE despesas_bemol (
    id INT AUTO_INCREMENT PRIMARY KEY,
    despesa_id INT NOT NULL, -- Vincula com a despesa geral
    titular_conta_bemol_id INT NOT NULL, -- Titular da conta na Bemol (você ou esposa)
    teve_entrada BOOLEAN DEFAULT FALSE,
    valor_entrada DECIMAL(10, 2) NULL,
    numero_parcelas INT DEFAULT 1,
    FOREIGN KEY (despesa_id) REFERENCES despesas(id) ON DELETE CASCADE,
    FOREIGN KEY (titular_conta_bemol_id) REFERENCES usuarios(id)
);

-- Tabela de Automóveis
CREATE TABLE automoveis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    modelo VARCHAR(100) NOT NULL,
    placa VARCHAR(10) UNIQUE,
    ano INT,
    data_compra DATE,
    valor_compra DECIMAL(10, 2),
    forma_pagamento ENUM('a_vista', 'parcelado') NULL,
    tipo_parcelamento ENUM('cartao_credito', 'financiamento', 'consorcio') NULL,
    cartao_id INT NULL,
    numero_parcelas INT NULL,
    valor_parcela DECIMAL(10, 2) NULL,
    dia_vencimento_parcela INT NULL,
    valor_lance DECIMAL(10, 2) NULL,
    data_lance DATE NULL,
    vendido BOOLEAN DEFAULT FALSE,
    data_venda DATE NULL,
    valor_venda DECIMAL(10, 2) NULL,
    FOREIGN KEY (cartao_id) REFERENCES cartoes(id)
);

-- Tabela de Despesas de Automóveis
-- Vincula uma despesa geral a um automóvel específico.
CREATE TABLE despesas_automoveis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    despesa_id INT NOT NULL,
    automovel_id INT NOT NULL,
    tipo_despesa ENUM('combustivel', 'manutencao', 'multa', 'estacionamento', 'seguro', 'ipva', 'outros') NOT NULL,
    quilometragem INT NULL, -- Para manutenções
    litros_combustivel DECIMAL(10, 2) NULL, -- Para combustível
    FOREIGN KEY (despesa_id) REFERENCES despesas(id) ON DELETE CASCADE,
    FOREIGN KEY (automovel_id) REFERENCES automoveis(id)
);

-- Tabela de Empréstimos
CREATE TABLE emprestimos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    banco VARCHAR(100) NOT NULL,
    descricao VARCHAR(255) NULL,
    valor_emprestimo DECIMAL(10, 2) NOT NULL,
    taxa_juros_anual DECIMAL(5, 2) NULL,
    numero_parcelas INT NOT NULL,
    valor_parcela DECIMAL(10, 2) NOT NULL,
    data_emprestimo DATE NOT NULL,
    dia_vencimento_parcela INT NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);
