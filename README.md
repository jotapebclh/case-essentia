# Case Técnico — Essentia

Este repositório contém as soluções desenvolvidas para o case técnico da Essentia, dividido em duas atividades: uma aplicação de cadastro de clientes utilizando Laravel e um módulo customizado de entrega para Magento 2.

## Estrutura do repositório

```text
case-essentia/
├── cadastro-clientes/    # Aplicação Laravel
├── magento/              # Módulo Magento 2
│   └── app/code/Essentia/RetiradaLoja/
└── README.md
```

Cada atividade possui sua própria documentação, com instruções de instalação, configuração e execução.

## Atividade 1 — Cadastro de Clientes (Laravel)

Desenvolvimento de uma aplicação web para gerenciamento de clientes utilizando **PHP e Laravel 12**.

Principais funcionalidades:

- Cadastro de clientes.
- Listagem e consulta dos registros.
- Edição de informações cadastrais.
- Exclusão de clientes.
- Validação dos dados informados.

**Tecnologias:** PHP, Laravel 12 e banco de dados relacional.

**Documentação:** [Cadastro de Clientes](cadastro-clientes/README.md)

## Atividade 2 — Retirada na Loja (Magento 2)

Desenvolvimento do módulo customizado **Essentia_RetiradaLoja**, compatível com Magento Open Source 2.4.7-p10.

Principais funcionalidades:

- Método de entrega personalizado "Retirado na Loja".
- Frete gratuito, com valor fixo de R$ 0,00.
- Configuração e habilitação pelo painel administrativo.
- Disponibilização do método no checkout.
- Persistência do método selecionado no pedido.
- Exibição do método de entrega na grade administrativa de pedidos.
- Filtro de pedidos por método de entrega.

A implementação utiliza os mecanismos nativos de extensão do Magento, sem alterações no código-fonte da plataforma ou integrações externas para cálculo de frete.

**Tecnologias:** PHP 8.3, Magento Open Source 2.4.7-p10 e XML.

**Documentação:** [Módulo Retirada na Loja](magento/app/code/Essentia/RetiradaLoja/README.md)

## Considerações

As duas atividades foram desenvolvidas de forma independente, permitindo sua instalação e execução separadamente.

As instruções de configuração, os requisitos e os procedimentos de validação estão disponíveis nos respectivos arquivos README.
